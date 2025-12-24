/**
 * IBAN Extractor Frontend JavaScript
 *
 * Handles real-time IBAN validation and data display.
 *
 * @package GravityFormsIBANExtractor
 */

(function ($) {
    'use strict';

    // Debounce function.
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                func.apply(context, args);
            }, wait);
        };
    }

    // IBAN Extractor class.
    var IBANExtractor = {
        /**
         * Initialize the extractor.
         */
        init: function () {
            this.bindEvents();
            this.alignLabels();
        },

        /**
         * Move field label inside input container for side-by-side alignment.
         */
        alignLabels: function () {
            $('.gf-iban-wrapper').each(function () {
                var $wrapper = $(this);
                var $gfield = $wrapper.closest('.gfield');
                var $label = $gfield.find('.gfield_label').first();
                var $leftCol = $wrapper.find('.ginput_container_iban');

                // If label exists and is not already inside the left column.
                if ($label.length > 0 && $leftCol.length > 0 && $leftCol.find('.gfield_label').length === 0) {
                    $leftCol.prepend($label);
                }
            });
        },

        /**
         * Bind events to IBAN input fields.
         */
        bindEvents: function () {
            var self = this;

            // Use event delegation for dynamically added fields.
            $(document).on('input', '.gf-iban-input', debounce(function () {
                var $input = $(this);
                if ($input.data('enable-preview') === '1' || $input.data('enable-preview') === 1) {
                    self.validateIBAN($input);
                }
            }, 300));

            // Format IBAN on blur.
            $(document).on('blur', '.gf-iban-input', function () {
                var $input = $(this);
                self.formatIBAN($input);
            });

            // Clear formatting on focus for easier editing.
            $(document).on('focus', '.gf-iban-input', function () {
                var $input = $(this);
                var value = $input.val();
                // Remove spaces for editing.
                $input.val(value.replace(/\s/g, ''));
            });

            // Initialize existing fields.
            $('.gf-iban-input').each(function () {
                var $input = $(this);
                if ($input.val()) {
                    if ($input.data('enable-preview') === '1' || $input.data('enable-preview') === 1) {
                        self.validateIBAN($input);
                    }
                }
            });
        },

        /**
         * Validate IBAN via AJAX.
         *
         * @param {jQuery} $input The input element.
         */
        validateIBAN: function ($input) {
            var self = this;
            var iban = $input.val().replace(/\s/g, '').toUpperCase();
            var $container = $input.closest('.ginput_container_iban');
            var $status = $container.find('.gf-iban-status');
            var $results = $container.find('.gf-iban-results');

            // Clear if empty.
            if (!iban) {
                $status.html('').removeClass('valid invalid loading');
                $results.html('');
                return;
            }

            // Minimum IBAN length check (shortest is 15 characters).
            if (iban.length < 15) {
                $status.html('').removeClass('valid invalid loading');
                $results.html('');
                return;
            }

            // Show loading state.
            $status.show().html(this.getLoadingHTML()).addClass('loading').removeClass('valid invalid');
            $results.html('');

            // AJAX validation.
            $.ajax({
                url: gfIbanExtractor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gf_validate_iban',
                    iban: iban,
                    nonce: gfIbanExtractor.nonce
                },
                success: function (response) {
                    $status.removeClass('loading');

                    if (response.success) {
                        self.displayValidResult($input, response.data);
                    } else {
                        self.displayInvalidResult($input, response.data);
                    }
                },
                error: function () {
                    $status.removeClass('loading').addClass('invalid');
                    $status.html(self.getErrorHTML(gfIbanExtractor.i18n.invalidIban));
                    $results.html('');
                }
            });
        },

        /**
         * Display valid IBAN result.
         *
         * @param {jQuery} $input The input element.
         * @param {Object} data   The extracted data.
         */
        displayValidResult: function ($input, data) {
            var $container = $input.closest('.ginput_container_iban');
            var $gfield = $input.closest('.gfield');
            var $status = $container.find('.gf-iban-status');
            var $results = $gfield.find('.gf-iban-results');

            // Get display options from data attributes.
            var showAccount = $input.data('show-account') === '1' || $input.data('show-account') === 1;
            var showBban = $input.data('show-bban') === '1' || $input.data('show-bban') === 1;
            var showCurrency = $input.data('show-currency') === '1' || $input.data('show-currency') === 1;
            var showCountry = $input.data('show-country') === '1' || $input.data('show-country') === 1;
            var showBank = $input.data('show-bank') === '1' || $input.data('show-bank') === 1;
            var showBankInfo = $input.data('show-bank-info') === '1' || $input.data('show-bank-info') === 1;

            // Hide status when valid (table is sufficient confirmation).
            $status.hide().removeClass('valid invalid loading').html('');

            // Build results HTML.
            var html = '<div class="gf-iban-data">';
            html += '<table class="gf-iban-table" role="presentation">';
            html += '<tbody>';

            if (showCountry && data.country_name) {
                html += this.getDataRow(gfIbanExtractor.i18n.country, data.country_name);
            }

            if (showCurrency && data.currency) {
                html += this.getDataRow(gfIbanExtractor.i18n.currency, data.currency);
            }

            if (showBank && data.bank_code) {
                html += this.getDataRow(gfIbanExtractor.i18n.bankCode, data.bank_code);
            }

            if (showBankInfo && data.branch_code) {
                html += this.getDataRow(gfIbanExtractor.i18n.branchCode, data.branch_code);
            }

            if (showAccount && data.account) {
                html += this.getDataRow(gfIbanExtractor.i18n.accountNo, data.account);
            }

            if (showBban && data.bban) {
                html += this.getDataRow(gfIbanExtractor.i18n.bban, data.bban);
            }

            html += '</tbody>';
            html += '</table>';
            html += '</div>';

            $results.html(html);
        },

        /**
         * Display invalid IBAN result.
         *
         * @param {jQuery} $input The input element.
         * @param {Object} data   The error data.
         */
        displayInvalidResult: function ($input, data) {
            var $container = $input.closest('.ginput_container_iban');
            var $gfield = $input.closest('.gfield');
            var $status = $container.find('.gf-iban-status');
            var $results = $gfield.find('.gf-iban-results');

            $status.show().addClass('invalid').removeClass('valid');
            $status.html(this.getErrorHTML(gfIbanExtractor.i18n.invalidIban));

            // Show suggestions if available.
            if (data && data.suggestions && data.suggestions.length > 0) {
                var html = '<div class="gf-iban-suggestions">';
                html += '<span class="suggestion-label">' + gfIbanExtractor.i18n.suggestion + '</span> ';
                html += '<button type="button" class="gf-iban-suggestion-btn" data-iban="' + this.escapeHtml(data.suggestions[0]) + '">';
                html += this.escapeHtml(data.suggestions[0]);
                html += '</button>';
                html += '</div>';
                $results.html(html);

                // Bind click handler for suggestion.
                var self = this;
                $results.find('.gf-iban-suggestion-btn').on('click', function (e) {
                    e.preventDefault();
                    var suggestedIban = $(this).data('iban');
                    $input.val(suggestedIban);
                    self.validateIBAN($input);
                });
            } else {
                $results.html('');
            }
        },

        /**
         * Format IBAN with spaces (human readable).
         *
         * @param {jQuery} $input The input element.
         */
        formatIBAN: function ($input) {
            var value = $input.val().replace(/\s/g, '').toUpperCase();
            if (value.length >= 15) {
                // Add a space every 4 characters.
                var formatted = value.match(/.{1,4}/g);
                if (formatted) {
                    $input.val(formatted.join(' '));
                }
            }
        },

        /**
         * Get success HTML markup.
         *
         * @param {string} message The success message.
         * @return {string}
         */
        getSuccessHTML: function (message) {
            return '<span class="gf-iban-indicator valid" aria-hidden="true">✓</span> ' +
                '<span class="gf-iban-message">' + this.escapeHtml(message) + '</span>';
        },

        /**
         * Get error HTML markup.
         *
         * @param {string} message The error message.
         * @return {string}
         */
        getErrorHTML: function (message) {
            return '<span class="gf-iban-indicator invalid" aria-hidden="true">✗</span> ' +
                '<span class="gf-iban-message">' + this.escapeHtml(message) + '</span>';
        },

        /**
         * Get loading HTML markup.
         *
         * @return {string}
         */
        getLoadingHTML: function () {
            return '<span class="gf-iban-spinner" aria-hidden="true"></span> ' +
                '<span class="gf-iban-message">' + this.escapeHtml(gfIbanExtractor.i18n.validating) + '</span>';
        },

        /**
         * Get data row HTML.
         *
         * @param {string} label The label.
         * @param {string} value The value.
         * @return {string}
         */
        getDataRow: function (label, value) {
            return '<tr class="gf-iban-row">' +
                '<th scope="row" class="gf-iban-label">' + this.escapeHtml(label) + '</th>' +
                '<td class="gf-iban-value">' + this.escapeHtml(value) + '</td>' +
                '</tr>';
        },

        /**
         * Escape HTML entities.
         *
         * @param {string} text The text to escape.
         * @return {string}
         */
        escapeHtml: function (text) {
            if (!text) {
                return '';
            }
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Document Extractor - Handles document upload and IBAN extraction.
     */
    var DocumentExtractor = {
        /**
         * Initialize the document extractor.
         */
        init: function () {
            this.bindEvents();
        },

        /**
         * Bind events for document extraction.
         */
        bindEvents: function () {
            var self = this;

            // Scan button click handler removed (using standard file input).

            // File input change.
            $(document).on('change', '.gf-iban-document-input', function (e) {
                self.handleFileSelect(e.target);
            });

            // Drag and drop on upload zone.
            $(document).on('dragover dragenter', '.gf-iban-upload-zone', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            $(document).on('dragleave dragend', '.gf-iban-upload-zone', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            $(document).on('drop', '.gf-iban-upload-zone', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    var $wrap = $(this).closest('.gf-iban-document-extraction');
                    var $input = $wrap.find('.gf-iban-document-input');

                    var dataTransfer = new DataTransfer();
                    dataTransfer.items.add(files[0]);
                    $input[0].files = dataTransfer.files;

                    self.handleFileSelect($input[0]);
                }
            });

            // Remove document button.
            $(document).on('click', '.gf-iban-remove-doc', function (e) {
                e.preventDefault();
                var $wrap = $(this).closest('.gf-iban-document-extraction');
                self.resetDocumentUpload($wrap);
            });
        },

        /**
         * Handle file selection.
         *
         * @param {HTMLElement} input The file input element.
         */
        handleFileSelect: function (input) {
            var self = this;
            var $wrap = $(input).closest('.gf-iban-document-extraction');
            var file = input.files[0];

            if (!file) {
                return;
            }

            // Validate file type.
            var validTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (validTypes.indexOf(file.type) === -1) {
                alert(gfIbanExtractor.i18n.invalidFile);
                this.resetDocumentUpload($wrap);
                return;
            }

            // Validate file size (10MB max).
            var maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert(gfIbanExtractor.i18n.fileTooLarge);
                this.resetDocumentUpload($wrap);
                return;
            }

            // Show preview.
            this.showPreview($wrap, file);

            // Show loading status.
            var $status = $wrap.find('.gf-iban-extraction-status');
            var $statusText = $status.find('.gf-iban-extraction-text');
            $status.show();
            $statusText.text(gfIbanExtractor.i18n.scanning);

            // Upload and extract.
            this.uploadAndExtract($wrap, file);
        },

        /**
         * Show document preview.
         *
         * @param {jQuery} $wrap The extraction wrapper element.
         * @param {File} file The file object.
         */
        showPreview: function ($wrap, file) {
            // Elements to toggle.
            var $label = $wrap.find('.gf-iban-scan-label');
            var $input = $wrap.find('.gf-iban-document-input');
            var $hint = $wrap.find('.gf-iban-upload-hint');

            var $preview = $wrap.find('.gf-iban-extraction-preview');
            var $img = $preview.find('img');
            var $filename = $preview.find('.gf-iban-filename');

            // Hide file input and hint, but keep label visible for alignment.
            $input.hide();
            $hint.hide();

            // Create preview image (for images only).
            if (file.type.startsWith('image/')) {
                var objectUrl = URL.createObjectURL(file);
                $img.attr('src', objectUrl);
            } else {
                // PDF icon placeholder.
                $img.attr('src', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM2NjY2NjYiIHN0cm9rZS13aWR0aD0iMiI+PHBhdGggZD0iTTE0IDJINmEyIDIgMCAwIDAtMiAydjE2YTIgMiAwIDAgMCAyIDJoMTJhMiAyIDAgMCAwIDItMlY4eiIvPjxwb2x5bGluZSBwb2ludHM9IjE0IDIgMTQgOCAyMCA4Ii8+PC9zdmc+');
            }

            $filename.text(file.name + ' (' + this.formatFileSize(file.size) + ')');
            $preview.show();
        },

        /**
         * Upload file and extract IBAN.
         *
         * @param {jQuery} $wrap The extraction wrapper element.
         * @param {File} file The file object.
         */
        uploadAndExtract: function ($wrap, file) {
            var self = this;
            var formId = $wrap.data('form-id');
            var fieldId = $wrap.data('field-id');

            var formData = new FormData();
            formData.append('action', 'gf_iban_extract_from_document');
            formData.append('nonce', gfIbanExtractor.nonce);
            formData.append('document', file);
            formData.append('form_id', formId);
            formData.append('field_id', fieldId);

            $.ajax({
                url: gfIbanExtractor.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    var $status = $wrap.find('.gf-iban-extraction-status');
                    var $statusText = $status.find('.gf-iban-extraction-text');

                    if (response.success && response.data.extracted_data) {
                        var data = response.data.extracted_data;

                        // Populate IBAN field.
                        if (data.iban) {
                            var $ibanInput = $wrap.closest('.ginput_container_iban').find('.gf-iban-input');
                            if ($ibanInput.length === 0) {
                                // Try to find it as a sibling.
                                $ibanInput = $wrap.siblings('.ginput_container_iban').find('.gf-iban-input');
                            }
                            if ($ibanInput.length === 0) {
                                // Try to find in parent gfield.
                                $ibanInput = $wrap.closest('.gfield').find('.gf-iban-input');
                            }

                            if ($ibanInput.length > 0) {
                                $ibanInput.val(data.iban);
                                $ibanInput.trigger('input');
                                $ibanInput.trigger('change');
                                self.highlightField($ibanInput);
                            }
                        }

                        // Show extraction results.
                        self.showExtractionResults($wrap, data);

                        // Store extraction token in hidden field for form submission.
                        if (response.data.extraction_token) {
                            var tokenFieldName = 'gf_iban_extraction_token_' + fieldId;
                            var $form = $wrap.closest('form');
                            // Remove any existing token field.
                            $form.find('input[name="' + tokenFieldName + '"]').remove();
                            // Add new hidden field with the token.
                            $form.append('<input type="hidden" name="' + tokenFieldName + '" value="' + response.data.extraction_token + '">');
                        }

                        // Show success status.
                        $statusText.text(gfIbanExtractor.i18n.extractionComplete);
                        $status.addClass('success');

                        // Hide status after delay.
                        setTimeout(function () {
                            $status.fadeOut();
                        }, 3000);
                    } else {
                        // Show error.
                        $statusText.text(response.data.message || gfIbanExtractor.i18n.extractionFailed);
                        $status.addClass('error');
                    }
                },
                error: function () {
                    var $status = $wrap.find('.gf-iban-extraction-status');
                    var $statusText = $status.find('.gf-iban-extraction-text');
                    $statusText.text(gfIbanExtractor.i18n.extractionFailed);
                    $status.addClass('error');
                }
            });
        },

        /**
         * Show extraction results.
         *
         * @param {jQuery} $wrap The extraction wrapper element.
         * @param {Object} data The extracted data.
         */
        showExtractionResults: function ($wrap, data, attempt) {
            var self = this;
            attempt = attempt || 0;
            var maxAttempts = 10;
            var delay = 500; // ms

            console.log('GF IBAN: showExtractionResults called, attempt:', attempt);

            // Find the existing IBAN results table (inside .gf-iban-results in the same gfield).
            var $gfield = $wrap.closest('.gfield');
            var $ibanResults = $gfield.find('.gf-iban-results');

            // Try to find tbody in the existing table.
            var $existingTbody = $ibanResults.find('.gf-iban-table tbody');
            console.log('GF IBAN: $existingTbody found:', $existingTbody.length);

            // Build extraction rows HTML.
            var extractionRows = '';

            if (data.first_name || data.last_name) {
                var holderName = (data.first_name || '') + ' ' + (data.last_name || '');
                extractionRows += '<tr class="gf-iban-row gf-iban-extraction-row"><th scope="row" class="gf-iban-label">' + gfIbanExtractor.i18n.accountHolder + '</th>';
                extractionRows += '<td class="gf-iban-value">' + this.escapeHtml(holderName.trim()) + '</td></tr>';
            }

            if (data.bank_name) {
                extractionRows += '<tr class="gf-iban-row gf-iban-extraction-row"><th scope="row" class="gf-iban-label">' + gfIbanExtractor.i18n.bankName + '</th>';
                extractionRows += '<td class="gf-iban-value">' + this.escapeHtml(data.bank_name) + '</td></tr>';
            }

            if (data.bic) {
                extractionRows += '<tr class="gf-iban-row gf-iban-extraction-row"><th scope="row" class="gf-iban-label">' + gfIbanExtractor.i18n.bicSwift + '</th>';
                extractionRows += '<td class="gf-iban-value">' + this.escapeHtml(data.bic) + '</td></tr>';
            }

            console.log('GF IBAN: extractionRows:', extractionRows ? 'has data' : 'empty');

            // Check if we found the table.
            if ($existingTbody.length > 0) {
                if (extractionRows) {
                    // Append to existing IBAN validation table.
                    console.log('GF IBAN: Appending to existing tbody');
                    $existingTbody.find('.gf-iban-extraction-row').remove();
                    $existingTbody.append(extractionRows);
                    // Hide the separate extraction results area.
                    $wrap.find('.gf-iban-extraction-results').hide();
                }
            } else if (attempt < maxAttempts) {
                // Table not found yet, wait and retry.
                console.log('GF IBAN: Table not found, retrying in ' + delay + 'ms...');
                setTimeout(function () {
                    self.showExtractionResults($wrap, data, attempt + 1);
                }, delay);
            } else {
                // Max attempts reached, fallback to separate table.
                if (extractionRows) {
                    console.log('GF IBAN: Max attempts reached, fallback to separate results');
                    var $results = $wrap.find('.gf-iban-extraction-results');
                    var html = '<div class="gf-iban-data">';
                    html += '<table class="gf-iban-table" role="presentation"><tbody>';
                    html += extractionRows;
                    html += '</tbody></table></div>';
                    $results.html(html).show();
                }
            }
        },

        /**
         * Reset document upload state.
         *
         * @param {jQuery} $wrap The extraction wrapper element.
         */
        resetDocumentUpload: function ($wrap) {
            var $label = $wrap.find('.gf-iban-scan-label');
            var $input = $wrap.find('.gf-iban-document-input');
            var $hint = $wrap.find('.gf-iban-upload-hint');

            var $preview = $wrap.find('.gf-iban-extraction-preview');
            var $status = $wrap.find('.gf-iban-extraction-status');
            var $results = $wrap.find('.gf-iban-extraction-results');

            $input.val('');
            $preview.hide();
            $status.hide().removeClass('success error');
            $results.hide().empty();

            // Show upload UI (label stays visible, just show input and hint).
            $input.show();
            $hint.show();
        },

        /**
         * Highlight a field briefly.
         *
         * @param {jQuery} $field The field element.
         */
        highlightField: function ($field) {
            $field.addClass('gf-iban-highlight');
            setTimeout(function () {
                $field.removeClass('gf-iban-highlight');
            }, 1500);
        },

        /**
         * Format file size.
         *
         * @param {number} bytes The size in bytes.
         * @return {string} Formatted size.
         */
        formatFileSize: function (bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Escape HTML entities.
         *
         * @param {string} text The text to escape.
         * @return {string}
         */
        escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on document ready.
    $(document).ready(function () {
        IBANExtractor.init();
        DocumentExtractor.init();
    });

    // Reinitialize after Gravity Forms AJAX submission.
    $(document).on('gform_post_render', function () {
        // Aling labels again after render.
        IBANExtractor.alignLabels();
    });

})(jQuery);
