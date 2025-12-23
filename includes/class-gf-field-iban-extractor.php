<?php
/**
 * GF Field IBAN Extractor
 *
 * Custom Gravity Forms field type for IBAN validation and extraction.
 *
 * @package GravityFormsIBANExtractor
 */

namespace GravityFormsIBANExtractor;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GF_Field_IBAN_Extractor
 *
 * Extends GF_Field to create a custom IBAN extractor field.
 */
class GF_Field_IBAN_Extractor extends \GF_Field
{

    /**
     * Field type identifier.
     *
     * @var string
     */
    public $type = 'iban_extractor';

    /**
     * Get the field title for the form editor.
     *
     * @return string
     */
    public function get_form_editor_field_title()
    {
        return esc_attr__('IBAN Extractor', 'gravity-forms-iban-extractor');
    }

    /**
     * Get the field button configuration for the form editor.
     *
     * @return array
     */
    public function get_form_editor_button()
    {
        return array(
            'group' => 'advanced_fields',
            'text' => $this->get_form_editor_field_title(),
        );
    }

    /**
     * Get the field settings that appear in the form editor.
     *
     * @return array
     */
    public function get_form_editor_field_settings()
    {
        return array(
            'label_setting',
            'description_setting',
            'rules_setting',
            'placeholder_setting',
            'input_class_setting',
            'css_class_setting',
            'size_setting',
            'admin_label_setting',
            'default_value_setting',
            'visibility_setting',
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            // Custom settings.
            'iban_display_options_setting',
            'iban_preview_setting',
        );
    }

    /**
     * Define default field properties.
     *
     * @return array
     */
    public function get_form_editor_field_default_attributes()
    {
        return array(
            'show_account' => true,
            'show_bban' => true,
            'show_currency' => true,
            'show_country' => true,
            'show_bank' => true,
            'show_bank_info' => false,
            'enable_preview' => true,
        );
    }

    /**
     * Get the field input HTML.
     *
     * @param array  $form  The form data.
     * @param string $value The field value.
     * @param array  $entry The entry data.
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null)
    {
        $form_id = absint($form['id']);
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor = $this->is_form_editor();
        $id = (int) $this->id;
        $field_id = $is_entry_detail || $is_form_editor ? "input_{$id}" : "input_{$form_id}_{$id}";

        $size_class = $this->size;
        $class_suffix = $is_entry_detail ? '_admin' : '';
        $class = $size_class . $class_suffix;
        $disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
        $placeholder = $this->get_field_placeholder_attribute();
        $required = $this->isRequired ? 'aria-required="true"' : '';
        $invalid = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
        $describedby = $this->get_aria_describedby();

        // Get display options.
        $show_account = $this->show_account ?? true;
        $show_bban = $this->show_bban ?? true;
        $show_currency = $this->show_currency ?? true;
        $show_country = $this->show_country ?? true;
        $show_bank = $this->show_bank ?? true;
        $show_bank_info = $this->show_bank_info ?? false;
        $enable_preview = $this->enable_preview ?? true;

        // Build data attributes for JS.
        $data_attrs = sprintf(
            'data-show-account="%s" data-show-bban="%s" data-show-currency="%s" data-show-country="%s" data-show-bank="%s" data-show-bank-info="%s" data-enable-preview="%s"',
            esc_attr($show_account ? '1' : '0'),
            esc_attr($show_bban ? '1' : '0'),
            esc_attr($show_currency ? '1' : '0'),
            esc_attr($show_country ? '1' : '0'),
            esc_attr($show_bank ? '1' : '0'),
            esc_attr($show_bank_info ? '1' : '0'),
            esc_attr($enable_preview ? '1' : '0')
        );

        $value = esc_attr($value);

        // Build the input HTML.
        $input = sprintf(
            '<div class="ginput_container ginput_container_iban">
                <input name="input_%d" id="%s" type="text" value="%s" class="%s gf-iban-input" %s %s %s %s %s autocomplete="off" />
                <div class="gf-iban-status" aria-live="polite"></div>
                <div class="gf-iban-results" aria-live="polite" role="region" aria-label="%s"></div>
            </div>',
            $id,
            esc_attr($field_id),
            $value,
            esc_attr($class),
            $disabled_text,
            $placeholder,
            $required,
            $invalid,
            $data_attrs,
            esc_attr__('IBAN validation results', 'gravity-forms-iban-extractor')
        );

        return $input;
    }

    /**
     * Validate the field value.
     *
     * @param string|array $value The field value.
     * @param array        $form  The form data.
     */
    public function validate($value, $form)
    {
        if ($this->isRequired && empty($value)) {
            $this->failed_validation = true;
            $this->validation_message = empty($this->errorMessage)
                ? __('This field is required.', 'gravity-forms-iban-extractor')
                : $this->errorMessage;
            return;
        }

        if (!empty($value)) {
            $extractor = new IBAN_Extractor();
            $is_valid = $extractor->validate($value);

            if (!$is_valid) {
                $this->failed_validation = true;

                // Get suggestions for the error message.
                $suggestions = $extractor->get_suggestions($value);
                if (!empty($suggestions)) {
                    $this->validation_message = sprintf(
                        /* translators: %s: suggested IBAN correction */
                        __('Invalid IBAN format. Did you mean: %s?', 'gravity-forms-iban-extractor'),
                        esc_html($suggestions[0])
                    );
                } else {
                    $this->validation_message = empty($this->errorMessage)
                        ? __('Please enter a valid IBAN.', 'gravity-forms-iban-extractor')
                        : $this->errorMessage;
                }
            }
        }
    }

    /**
     * Format the value for saving to the entry.
     *
     * @param string $value      The submitted value.
     * @param array  $form       The form data.
     * @param string $input_name The input name.
     * @param int    $lead_id    The entry ID.
     * @param array  $lead       The entry data.
     * @return string
     */
    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        if (empty($value)) {
            return '';
        }

        $extractor = new IBAN_Extractor();

        // Store in machine format for consistency.
        return $extractor->to_machine_format($value);
    }

    /**
     * Format the value for display in the entry detail.
     *
     * @param string $value    The entry value.
     * @param string $currency The currency code.
     * @param bool   $use_text Whether to use text mode.
     * @param string $format   The format type.
     * @param string $media    The media type (screen/email).
     * @return string
     */
    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen')
    {
        if (empty($value)) {
            return '';
        }

        $extractor = new IBAN_Extractor();
        $data = $extractor->extract($value);

        if ('text' === $format || $use_text) {
            return $extractor->format_for_display($value);
        }

        // Build HTML display with extracted data.
        $output = '<div class="gf-iban-entry-detail">';
        $output .= '<strong>' . esc_html($data['formatted']) . '</strong>';

        if ($data['valid']) {
            $output .= '<ul class="gf-iban-details">';

            if (!empty($data['country_name'])) {
                $output .= '<li><span class="label">' . esc_html__('Country:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($data['country_name']) . '</li>';
            }

            if (!empty($data['currency'])) {
                $output .= '<li><span class="label">' . esc_html__('Currency:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($data['currency']) . '</li>';
            }

            if (!empty($data['bank_code'])) {
                $output .= '<li><span class="label">' . esc_html__('Bank Code:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($data['bank_code']) . '</li>';
            }

            if (!empty($data['account'])) {
                $output .= '<li><span class="label">' . esc_html__('Account No.:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($data['account']) . '</li>';
            }

            if (!empty($data['bban'])) {
                $output .= '<li><span class="label">' . esc_html__('BBAN:', 'gravity-forms-iban-extractor') . '</span> ' . esc_html($data['bban']) . '</li>';
            }

            $output .= '</ul>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Get the value for merge tags.
     *
     * @param string       $value      The field value.
     * @param string       $input_id   The input ID.
     * @param array        $entry      The entry data.
     * @param array        $form       The form data.
     * @param string       $modifier   The modifier.
     * @param string|array $raw_value  The raw value.
     * @param bool         $url_encode Whether to URL encode.
     * @param bool         $esc_html   Whether to escape HTML.
     * @param string       $format     The format type.
     * @param bool         $nl2br      Whether to convert newlines.
     * @return string
     */
    public function get_value_merge_tag($value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br)
    {
        if (empty($value)) {
            return '';
        }

        $extractor = new IBAN_Extractor();

        // Handle modifiers for specific data extraction.
        switch ($modifier) {
            case 'formatted':
                return $extractor->format_for_display($value);
            case 'country':
                $data = $extractor->extract($value);
                return $data['country_name'] ?? '';
            case 'currency':
                $data = $extractor->extract($value);
                return $data['currency'] ?? '';
            case 'bank':
                $data = $extractor->extract($value);
                return $data['bank_code'] ?? '';
            case 'account':
                $data = $extractor->extract($value);
                return $data['account'] ?? '';
            case 'bban':
                $data = $extractor->extract($value);
                return $data['bban'] ?? '';
            default:
                return $extractor->format_for_display($value);
        }
    }

    /**
     * Save field-specific settings.
     *
     * @param array $form_meta The form meta.
     * @return array
     */
    public function sanitize_settings()
    {
        parent::sanitize_settings();

        $this->show_account = (bool) $this->show_account;
        $this->show_bban = (bool) $this->show_bban;
        $this->show_currency = (bool) $this->show_currency;
        $this->show_country = (bool) $this->show_country;
        $this->show_bank = (bool) $this->show_bank;
        $this->show_bank_info = (bool) $this->show_bank_info;
        $this->enable_preview = (bool) $this->enable_preview;
    }
}
