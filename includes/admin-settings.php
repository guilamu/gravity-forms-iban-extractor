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
            }
        });

        // Register field settings visibility.
        fieldSettings.iban_extractor = '.label_setting, .description_setting, .rules_setting, .placeholder_setting, .input_class_setting, .css_class_setting, .size_setting, .admin_label_setting, .default_value_setting, .visibility_setting, .conditional_logic_field_setting, .prepopulate_field_setting, .error_message_setting, .iban_display_options_setting, .iban_preview_setting';
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
