/**
 * IBAN Extractor Frontend JavaScript
 *
 * Handles real-time IBAN validation and data display.
 *
 * @package GravityFormsIBANExtractor
 */

(function($) {
    'use strict';

    // Debounce function.
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    // IBAN Extractor class.
    var IBANExtractor = {
        /**
         * Initialize the extractor.
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events to IBAN input fields.
         */
        bindEvents: function() {
            var self = this;

            // Use event delegation for dynamically added fields.
            $(document).on('input', '.gf-iban-input', debounce(function() {
                var $input = $(this);
                if ($input.data('enable-preview') === '1' || $input.data('enable-preview') === 1) {
                    self.validateIBAN($input);
                }
            }, 300));

            // Format IBAN on blur.
            $(document).on('blur', '.gf-iban-input', function() {
                var $input = $(this);
                self.formatIBAN($input);
            });

            // Clear formatting on focus for easier editing.
            $(document).on('focus', '.gf-iban-input', function() {
                var $input = $(this);
                var value = $input.val();
                // Remove spaces for editing.
                $input.val(value.replace(/\s/g, ''));
            });

            // Initialize existing fields.
            $('.gf-iban-input').each(function() {
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
        validateIBAN: function($input) {
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
            $status.html(this.getLoadingHTML()).addClass('loading').removeClass('valid invalid');
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
                success: function(response) {
                    $status.removeClass('loading');

                    if (response.success) {
                        self.displayValidResult($input, response.data);
                    } else {
                        self.displayInvalidResult($input, response.data);
                    }
                },
                error: function() {
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
        displayValidResult: function($input, data) {
            var $container = $input.closest('.ginput_container_iban');
            var $status = $container.find('.gf-iban-status');
            var $results = $container.find('.gf-iban-results');

            // Get display options from data attributes.
            var showAccount = $input.data('show-account') === '1' || $input.data('show-account') === 1;
            var showBban = $input.data('show-bban') === '1' || $input.data('show-bban') === 1;
            var showCurrency = $input.data('show-currency') === '1' || $input.data('show-currency') === 1;
            var showCountry = $input.data('show-country') === '1' || $input.data('show-country') === 1;
            var showBank = $input.data('show-bank') === '1' || $input.data('show-bank') === 1;
            var showBankInfo = $input.data('show-bank-info') === '1' || $input.data('show-bank-info') === 1;

            // Show valid status.
            $status.addClass('valid').removeClass('invalid');
            $status.html(this.getSuccessHTML(gfIbanExtractor.i18n.validIban));

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
        displayInvalidResult: function($input, data) {
            var $container = $input.closest('.ginput_container_iban');
            var $status = $container.find('.gf-iban-status');
            var $results = $container.find('.gf-iban-results');

            $status.addClass('invalid').removeClass('valid');
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
                $results.find('.gf-iban-suggestion-btn').on('click', function(e) {
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
        formatIBAN: function($input) {
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
        getSuccessHTML: function(message) {
            return '<span class="gf-iban-indicator valid" aria-hidden="true">✓</span> ' +
                   '<span class="gf-iban-message">' + this.escapeHtml(message) + '</span>';
        },

        /**
         * Get error HTML markup.
         *
         * @param {string} message The error message.
         * @return {string}
         */
        getErrorHTML: function(message) {
            return '<span class="gf-iban-indicator invalid" aria-hidden="true">✗</span> ' +
                   '<span class="gf-iban-message">' + this.escapeHtml(message) + '</span>';
        },

        /**
         * Get loading HTML markup.
         *
         * @return {string}
         */
        getLoadingHTML: function() {
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
        getDataRow: function(label, value) {
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
        escapeHtml: function(text) {
            if (!text) {
                return '';
            }
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on document ready.
    $(document).ready(function() {
        IBANExtractor.init();
    });

    // Reinitialize after Gravity Forms AJAX submission.
    $(document).on('gform_post_render', function() {
        // Fields are already bound via event delegation.
    });

})(jQuery);
