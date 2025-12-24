<?php
/**
 * Admin Settings for IBAN Extractor Field
 *
 * Registers custom field settings in the Gravity Forms form editor.
 *
 * @package GravityFormsIBANExtractor
 */

namespace GravityFormsIBANExtractor;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom field settings to the form editor.
 *
 * @param int $position The position to add the settings.
 * @param int $form_id  The form ID.
 */
function gf_iban_field_settings($position, $form_id)
{
    // Position 25 is after the admin label setting.
    if (25 === $position) {
        ?>
        <!-- IBAN Display Options Setting -->
        <li class="iban_display_options_setting field_setting">
            <label class="section_label">
                <?php esc_html_e('Display Options', 'gravity-forms-iban-extractor'); ?>
                <?php gform_tooltip('iban_display_options'); ?>
            </label>

            <div class="gf-iban-options-grid">
                <input type="checkbox" id="field_show_account" onclick="SetFieldProperty('show_account', this.checked);" />
                <label for="field_show_account" class="inline">
                    <?php esc_html_e('Show Account No.', 'gravity-forms-iban-extractor'); ?>
                </label>
                <br />

                <input type="checkbox" id="field_show_bban" onclick="SetFieldProperty('show_bban', this.checked);" />
                <label for="field_show_bban" class="inline">
                    <?php esc_html_e('Show BBAN', 'gravity-forms-iban-extractor'); ?>
                </label>
                <br />

                <input type="checkbox" id="field_show_currency" onclick="SetFieldProperty('show_currency', this.checked);" />
                <label for="field_show_currency" class="inline">
                    <?php esc_html_e('Show Country Currency', 'gravity-forms-iban-extractor'); ?>
                </label>
                <br />

                <input type="checkbox" id="field_show_country" onclick="SetFieldProperty('show_country', this.checked);" />
                <label for="field_show_country" class="inline">
                    <?php esc_html_e('Show Country Name', 'gravity-forms-iban-extractor'); ?>
                </label>
                <br />

                <input type="checkbox" id="field_show_bank" onclick="SetFieldProperty('show_bank', this.checked);" />
                <label for="field_show_bank" class="inline">
                    <?php esc_html_e('Show BIC/Bank Code', 'gravity-forms-iban-extractor'); ?>
                </label>
                <br />

                <input type="checkbox" id="field_show_bank_info" onclick="SetFieldProperty('show_bank_info', this.checked);" />
                <label for="field_show_bank_info" class="inline">
                    <?php esc_html_e('Show Bank Info', 'gravity-forms-iban-extractor'); ?>
                </label>
            </div>
        </li>

        <!-- IBAN Preview Setting -->
        <li class="iban_preview_setting field_setting">
            <input type="checkbox" id="field_enable_preview" onclick="SetFieldProperty('enable_preview', this.checked);" />
            <label for="field_enable_preview" class="inline">
                <?php esc_html_e('Enable Real-time Preview', 'gravity-forms-iban-extractor'); ?>
                <?php gform_tooltip('iban_enable_preview'); ?>
            </label>
        </li>
        <?php
    }
}
add_action('gform_field_standard_settings', __NAMESPACE__ . '\\gf_iban_field_settings', 10, 2);

/**
 * Add document extraction field settings.
 *
 * @param int $position The position to add the settings.
 * @param int $form_id  The form ID.
 */
function gf_iban_document_extraction_settings($position, $form_id)
{
    // Position 50 is after other custom settings.
    if (50 === $position) {
        ?>
        <!-- IBAN Document Extraction Setting -->
        <li class="iban_document_extraction_setting field_setting">
            <label class="section_label">
                <?php esc_html_e('Document Extraction', 'gravity-forms-iban-extractor'); ?>
                <?php gform_tooltip('iban_document_extraction'); ?>
            </label>

            <div class="gf-iban-extraction-options">
                <input type="checkbox" id="field_enable_document_extraction"
                    onclick="SetFieldProperty('enable_document_extraction', this.checked); jQuery('.gf-iban-api-settings').toggle(this.checked);" />
                <label for="field_enable_document_extraction" class="inline">
                    <?php esc_html_e('Enable Document Scanning', 'gravity-forms-iban-extractor'); ?>
                </label>

                <div class="gf-iban-api-settings" style="display:none; margin-top: 15px;">
                    <div class="gf-iban-api-key-wrap" style="margin-bottom: 10px;">
                        <label for="field_poe_api_key" style="display:block; margin-bottom:5px;">
                            <?php esc_html_e('POE API Key', 'gravity-forms-iban-extractor'); ?>
                        </label>
                        <input type="password" id="field_poe_api_key" style="width:100%;"
                            onchange="SetFieldProperty('poe_api_key', this.value);" />
                        <span class="gf_settings_description" style="font-size:12px;">
                            <?php esc_html_e('Get your API key from poe.com', 'gravity-forms-iban-extractor'); ?>
                        </span>
                    </div>

                    <div class="gf-iban-model-wrap">
                        <label for="field_poe_model" style="display:block; margin-bottom:5px;">
                            <?php esc_html_e('AI Model', 'gravity-forms-iban-extractor'); ?>
                        </label>
                        <select id="field_poe_model" style="width:100%;" onchange="SetFieldProperty('poe_model', this.value);">
                            <option value=""><?php esc_html_e('-- Select Model --', 'gravity-forms-iban-extractor'); ?></option>
                        </select>
                        <button type="button" id="gf_iban_load_models" class="button" style="margin-top:5px;">
                            <?php esc_html_e('Load Models', 'gravity-forms-iban-extractor'); ?>
                        </button>
                        <span class="gf-iban-model-status" style="margin-left:10px;"></span>
                    </div>
                </div>
            </div>
        </li>
        <?php
    }
}
add_action('gform_field_standard_settings', __NAMESPACE__ . '\\gf_iban_document_extraction_settings', 10, 2);

/**
 * Add tooltips for custom settings.
 *
 * @param array $tooltips Existing tooltips.
 * @return array
 */
function gf_iban_tooltips($tooltips)
{
    $tooltips['iban_display_options'] = sprintf(
        '<h6>%s</h6>%s',
        esc_html__('Display Options', 'gravity-forms-iban-extractor'),
        esc_html__('Select which extracted IBAN data fields to display below the input.', 'gravity-forms-iban-extractor')
    );

    $tooltips['iban_enable_preview'] = sprintf(
        '<h6>%s</h6>%s',
        esc_html__('Real-time Preview', 'gravity-forms-iban-extractor'),
        esc_html__('Enable live validation and data extraction as the user types.', 'gravity-forms-iban-extractor')
    );

    $tooltips['iban_document_extraction'] = sprintf(
        '<h6>%s</h6>%s',
        esc_html__('Document Extraction', 'gravity-forms-iban-extractor'),
        esc_html__('Allow users to upload a document (PDF, image) to automatically extract the IBAN using AI.', 'gravity-forms-iban-extractor')
    );

    return $tooltips;
}
add_filter('gform_tooltips', __NAMESPACE__ . '\\gf_iban_tooltips');

/**
 * Enqueue editor scripts to handle custom field settings.
 */
function gf_iban_editor_script()
{
    ?>
    <script type="text/javascript">
        // Add settings to the field types.
        jQuery(document).on('gform_load_field_settings', function (event, field, form) {
            if (field.type === 'iban_extractor') {
                // Set checkbox states from field properties.
                jQuery('#field_show_account').prop('checked', field.show_account == true);
                jQuery('#field_show_bban').prop('checked', field.show_bban == true);
                jQuery('#field_show_currency').prop('checked', field.show_currency == true);
                jQuery('#field_show_country').prop('checked', field.show_country == true);
                jQuery('#field_show_bank').prop('checked', field.show_bank == true);
                jQuery('#field_show_bank_info').prop('checked', field.show_bank_info == true);
                jQuery('#field_enable_preview').prop('checked', field.enable_preview !== false); // Default true.

                // Document extraction settings.
                jQuery('#field_enable_document_extraction').prop('checked', field.enable_document_extraction == true);
                jQuery('.gf-iban-api-settings').toggle(field.enable_document_extraction == true);
                jQuery('#field_poe_api_key').val(field.poe_api_key || '');
                jQuery('#field_poe_model').val(field.poe_model || '');

                // If we have an API key and saved model, try to load models to populate dropdown.
                if (field.poe_api_key && field.poe_model) {
                    gfIbanLoadModels(field.poe_api_key, field.poe_model);
                }
            }
        });

        // Load models button click handler - use direct binding, not delegation.
        // (Gravity Forms blocks event propagation on buttons)
        jQuery('#gf_iban_load_models').off('click').on('click', function (e) {
            e.preventDefault();
            console.log('GF IBAN: Load Models button clicked');
            var apiKey = jQuery('#field_poe_api_key').val();
            console.log('GF IBAN: API Key value:', apiKey ? apiKey.substr(0, 10) + '...' : 'empty');
            if (!apiKey) {
                alert('<?php echo esc_js(__('Please enter an API key first.', 'gravity-forms-iban-extractor')); ?>');
                return;
            }
            gfIbanLoadModels(apiKey);
        });

        // Function to load models via AJAX.
        function gfIbanLoadModels(apiKey, selectedModel) {
            console.log('GF IBAN: gfIbanLoadModels called');
            var $status = jQuery('.gf-iban-model-status');
            var $select = jQuery('#field_poe_model');

            $status.text('<?php echo esc_js(__('Loading...', 'gravity-forms-iban-extractor')); ?>');

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gf_iban_get_models',
                    nonce: '<?php echo wp_create_nonce('gf_iban_admin'); ?>',
                    api_key: apiKey
                },
                success: function (response) {
                    if (response.success && response.data.models) {
                        $select.empty();
                        $select.append('<option value=""><?php echo esc_js(__('-- Select Model --', 'gravity-forms-iban-extractor')); ?></option>');

                        response.data.models.forEach(function (model) {
                            var selected = (selectedModel && selectedModel === model.id) ? 'selected' : '';
                            $select.append('<option value="' + model.id + '" ' + selected + '>' + model.name + '</option>');
                        });

                        $status.text('<?php echo esc_js(__('Loaded!', 'gravity-forms-iban-extractor')); ?>');
                        setTimeout(function () { $status.text(''); }, 2000);
                    } else {
                        $status.text(response.data.message || '<?php echo esc_js(__('Error loading models.', 'gravity-forms-iban-extractor')); ?>');
                    }
                },
                error: function () {
                    $status.text('<?php echo esc_js(__('Request failed.', 'gravity-forms-iban-extractor')); ?>');
                }
            });
        }

        // Register field settings visibility.
        fieldSettings.iban_extractor = '.label_setting, .description_setting, .rules_setting, .placeholder_setting, .input_class_setting, .css_class_setting, .size_setting, .admin_label_setting, .default_value_setting, .visibility_setting, .conditional_logic_field_setting, .prepopulate_field_setting, .error_message_setting, .iban_display_options_setting, .iban_preview_setting, .iban_document_extraction_setting';
    </script>
    <?php
}
add_action('gform_editor_js', __NAMESPACE__ . '\\gf_iban_editor_script');

/**
 * Add editor styles for the settings panel.
 */
function gf_iban_editor_styles()
{
    ?>
    <style>
        .iban_display_options_setting .gf-iban-options-grid {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
        }

        .iban_display_options_setting .gf-iban-options-grid label {
            display: inline-block;
            margin-left: 5px;
            font-weight: normal;
        }

        .iban_display_options_setting .gf-iban-options-grid input[type="checkbox"] {
            margin: 5px 0;
        }

        .iban_preview_setting {
            margin-top: 15px !important;
        }
    </style>
    <?php
}
add_action('gform_editor_js', __NAMESPACE__ . '\\gf_iban_editor_styles');

/**
 * Store extracted IBAN data in entry meta.
 *
 * @param array $entry The entry data.
 * @param array $form  The form data.
 * @return array
 */
function gf_iban_save_entry_meta($entry, $form)
{
    foreach ($form['fields'] as $field) {
        if ('iban_extractor' !== $field->type) {
            continue;
        }

        $field_id = $field->id;
        $value = rgar($entry, $field_id);

        if (empty($value)) {
            continue;
        }

        $extractor = new IBAN_Extractor();
        $data = $extractor->extract($value);

        if (!$data['valid']) {
            continue;
        }

        // Store each extracted field in entry meta.
        gform_update_meta($entry['id'], "iban_{$field_id}_country", $data['country_name']);
        gform_update_meta($entry['id'], "iban_{$field_id}_currency", $data['currency']);
        gform_update_meta($entry['id'], "iban_{$field_id}_bank_code", $data['bank_code']);
        gform_update_meta($entry['id'], "iban_{$field_id}_branch_code", $data['branch_code']);
        gform_update_meta($entry['id'], "iban_{$field_id}_account", $data['account']);
        gform_update_meta($entry['id'], "iban_{$field_id}_bban", $data['bban']);
        gform_update_meta($entry['id'], "iban_{$field_id}_formatted", $data['formatted']);
        gform_update_meta($entry['id'], "iban_{$field_id}_is_sepa", $data['is_sepa'] ? '1' : '0');
    }

    return $entry;
}
add_action('gform_entry_created', __NAMESPACE__ . '\\gf_iban_save_entry_meta', 10, 2);

/**
 * Save extraction data from document upload.
 *
 * @param array $entry The entry data.
 * @param array $form  The form data.
 */
function gf_iban_save_extraction_data($entry, $form)
{
    global $wpdb;

    // Check for extraction data from submitted token.
    foreach ($form['fields'] as $field) {
        if ('iban_extractor' !== $field->type) {
            continue;
        }

        $field_id = $field->id;
        $token_field_name = 'gf_iban_extraction_token_' . $field_id;

        // Get token from POST data.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by Gravity Forms.
        $extraction_token = isset($_POST[$token_field_name]) ? sanitize_text_field(wp_unslash($_POST[$token_field_name])) : '';

        if (empty($extraction_token)) {
            continue;
        }

        $transient_key = 'gf_iban_extraction_' . $extraction_token;
        $extraction_data = get_transient($transient_key);

        if (!$extraction_data) {
            continue;
        }

        // Insert into extraction table.
        $table_name = $wpdb->prefix . 'gf_iban_entry_extraction';

        $wpdb->insert(
            $table_name,
            array(
                'entry_id'             => $entry['id'],
                'field_id'             => $field_id,
                'extracted_iban'       => sanitize_text_field($extraction_data['iban'] ?? ''),
                'extracted_bic'        => sanitize_text_field($extraction_data['bic'] ?? ''),
                'extracted_bank_name'  => sanitize_text_field($extraction_data['bank_name'] ?? ''),
                'extracted_first_name' => sanitize_text_field($extraction_data['first_name'] ?? ''),
                'extracted_last_name'  => sanitize_text_field($extraction_data['last_name'] ?? ''),
                'document_url'         => esc_url_raw($extraction_data['document_url'] ?? ''),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // Clear transient.
        delete_transient($transient_key);
    }
}
add_action('gform_entry_created', __NAMESPACE__ . '\\gf_iban_save_extraction_data', 15, 2);

/**
 * Display extracted data in entry detail.
 *
 * @param string   $field_content The field content.
 * @param array    $field         The field object.
 * @param string   $value         The field value.
 * @param int      $entry_id      The entry ID.
 * @param int      $form_id       The form ID.
 * @return string
 */
function gf_iban_display_extraction_in_entry($field_content, $field, $value, $entry_id, $form_id)
{
    if ('iban_extractor' !== $field->type) {
        return $field_content;
    }

    // Skip if no valid entry ID (e.g., in form editor preview).
    if (empty($entry_id) || !is_numeric($entry_id) || $entry_id <= 0) {
        return $field_content;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'gf_iban_entry_extraction';

    // Check if table exists.
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if (!$table_exists) {
        return $field_content;
    }

    $extraction = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE entry_id = %d AND field_id = %d",
            $entry_id,
            $field->id
        )
    );

    if (!$extraction) {
        return $field_content;
    }

    // Build extraction display as list items.
    $list_items = '';

    if (!empty($extraction->extracted_first_name) || !empty($extraction->extracted_last_name)) {
        $holder_name = trim($extraction->extracted_first_name . ' ' . $extraction->extracted_last_name);
        $list_items .= '<li><span class="label">' . esc_html__('Account Holder:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($holder_name) . '</li>';
    }

    if (!empty($extraction->extracted_bank_name)) {
        $list_items .= '<li><span class="label">' . esc_html__('Bank Name:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($extraction->extracted_bank_name) . '</li>';
    }

    if (!empty($extraction->extracted_bic)) {
        $list_items .= '<li><span class="label">' . esc_html__('BIC/SWIFT:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($extraction->extracted_bic) . '</li>';
    }

    if (!empty($extraction->extracted_iban)) {
        $list_items .= '<li><span class="label">' . esc_html__('Extracted IBAN:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($extraction->extracted_iban) . '</li>';
    }

    if (!empty($extraction->document_url)) {
        $list_items .= '<li><span class="label">' . esc_html__('Source Document:', 'gravity-forms-iban-extractor') . '</span> <a href="' . esc_url($extraction->document_url) . '" target="_blank">' . esc_html__('View Document', 'gravity-forms-iban-extractor') . '</a></li>';
    }

    // Inject into existing list if possible.
    if (strpos($field_content, '</ul>') !== false) {
        $field_content = str_replace('</ul>', $list_items . '</ul>', $field_content);
    } else {
        // Fallback if no list found (e.g. invalid IBAN or other display mode).
        $field_content .= '<div class="gf-iban-entry-extraction"><ul class="gf-iban-details">' . $list_items . '</ul></div>';
    }

    return $field_content;
}
add_filter('gform_field_content', __NAMESPACE__ . '\\gf_iban_display_extraction_in_entry', 10, 5);
