<?php
/**
 * Plugin Name: Gravity Forms IBAN Extractor
 * Plugin URI: https://github.com/guilamu/gravity-forms-iban-extractor
 * Description: Adds an IBAN extractor field type to Gravity Forms with real-time validation and data extraction.
 * Version: 1.3.2
 * Author: Guilamu
 * Author URI: https://github.com/guilamu
 * License: AGPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 * Text Domain: gravity-forms-iban-extractor
 * Domain Path: /languages
 * Update URI: https://github.com/guilamu/gravity-forms-iban-extractor/
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package GravityFormsIBANExtractor
 */

namespace GravityFormsIBANExtractor;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('GF_IBAN_EXTRACTOR_VERSION', '1.3.2');
define('GF_IBAN_EXTRACTOR_PLUGIN_FILE', __FILE__);
define('GF_IBAN_EXTRACTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GF_IBAN_EXTRACTOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Load bundled php-iban library.
 * Library source: https://github.com/globalcitizen/php-iban
 * License: LGPL-2.1-or-later (see lib/LICENSE-php-iban)
 */
require_once GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'lib/php-iban.php';

/**
 * Check for Gravity Forms dependency.
 *
 * @return bool
 */
function gf_iban_check_gravity_forms()
{
    return class_exists('GFForms');
}

/**
 * Display admin notice if Gravity Forms is not active.
 */
function gf_iban_admin_notice_missing_gf()
{
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: %s: Gravity Forms plugin name */
                esc_html__('Gravity Forms IBAN Extractor requires %s to be installed and activated.', 'gravity-forms-iban-extractor'),
                '<strong>Gravity Forms</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Initialize the plugin.
 */
function gf_iban_init()
{
    // Check for Gravity Forms.
    if (!gf_iban_check_gravity_forms()) {
        add_action('admin_notices', __NAMESPACE__ . '\\gf_iban_admin_notice_missing_gf');
        return;
    }

    // Load text domain.
    load_plugin_textdomain(
        'gravity-forms-iban-extractor',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    // Include required files.
    require_once GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'includes/class-iban-extractor.php';
    require_once GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'includes/class-gf-field-iban-extractor.php';
    require_once GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'includes/class-poe-api-service.php';
    require_once GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'includes/class-github-updater.php'; // Add GitHub Updater
    require_once GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'includes/admin-settings.php';

    // Register field type.
    if (class_exists('GF_Fields')) {
        \GF_Fields::register(new GF_Field_IBAN_Extractor());
    }
}
add_action('gform_loaded', __NAMESPACE__ . '\\gf_iban_init', 5);

/**
 * Enqueue frontend scripts and styles.
 */
function gf_iban_enqueue_scripts()
{
    if (!gf_iban_check_gravity_forms()) {
        return;
    }

    wp_enqueue_style(
        'gf-iban-extractor',
        GF_IBAN_EXTRACTOR_PLUGIN_URL . 'assets/css/iban-extractor.css',
        array(),
        GF_IBAN_EXTRACTOR_VERSION
    );

    wp_enqueue_script(
        'gf-iban-extractor',
        GF_IBAN_EXTRACTOR_PLUGIN_URL . 'assets/js/iban-extractor.js',
        array('jquery'),
        GF_IBAN_EXTRACTOR_VERSION,
        true
    );

    wp_localize_script(
        'gf-iban-extractor',
        'gfIbanExtractor',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gf_iban_validate'),
            'i18n' => array(
                'validIban' => __('Valid IBAN', 'gravity-forms-iban-extractor'),
                'invalidIban' => __('Invalid IBAN', 'gravity-forms-iban-extractor'),
                'validating' => __('Validating...', 'gravity-forms-iban-extractor'),
                'country' => __('Country', 'gravity-forms-iban-extractor'),
                'currency' => __('Currency', 'gravity-forms-iban-extractor'),
                'bban' => __('BBAN', 'gravity-forms-iban-extractor'),
                'bankCode' => __('Bank Code', 'gravity-forms-iban-extractor'),
                'branchCode' => __('Branch Code', 'gravity-forms-iban-extractor'),
                'accountNo' => __('Account No.', 'gravity-forms-iban-extractor'),
                'enterIban' => __('Enter an IBAN to validate', 'gravity-forms-iban-extractor'),
                'suggestion' => __('Did you mean:', 'gravity-forms-iban-extractor'),
                // Document extraction strings.
                'scanDocument' => __('Scan Document for IBAN', 'gravity-forms-iban-extractor'),
                'scanning' => __('Scanning document...', 'gravity-forms-iban-extractor'),
                'extractionComplete' => __('IBAN extracted successfully', 'gravity-forms-iban-extractor'),
                'extractionFailed' => __('Could not extract IBAN from document', 'gravity-forms-iban-extractor'),
                'invalidFile' => __('Please upload a PDF, PNG, JPG, or WEBP file', 'gravity-forms-iban-extractor'),
                'fileTooLarge' => __('File size must be less than 10MB', 'gravity-forms-iban-extractor'),
                'dropDocument' => __('Drop document here', 'gravity-forms-iban-extractor'),
                'orClickBrowse' => __('or click to browse', 'gravity-forms-iban-extractor'),
                'extractedInfo' => __('Extracted Information', 'gravity-forms-iban-extractor'),
                'accountHolder' => __('Account Holder', 'gravity-forms-iban-extractor'),
                'bankName' => __('Bank Name', 'gravity-forms-iban-extractor'),
                'bicSwift' => __('BIC/SWIFT', 'gravity-forms-iban-extractor'),
                'removeDocument' => __('Remove', 'gravity-forms-iban-extractor'),
            ),
        )
    );
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\gf_iban_enqueue_scripts');
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\gf_iban_enqueue_scripts');

/**
 * AJAX handler for IBAN validation.
 */
function gf_iban_ajax_validate()
{
    check_ajax_referer('gf_iban_validate', 'nonce');

    $iban = isset($_POST['iban']) ? sanitize_text_field(wp_unslash($_POST['iban'])) : '';

    if (empty($iban)) {
        wp_send_json_error(array('message' => __('Please enter an IBAN.', 'gravity-forms-iban-extractor')));
    }

    $extractor = new IBAN_Extractor();
    $result = $extractor->extract($iban);

    if ($result['valid']) {
        wp_send_json_success($result);
    } else {
        $response = array(
            'message' => __('Invalid IBAN format.', 'gravity-forms-iban-extractor'),
        );

        // Get suggestions for possible mistranscriptions.
        $suggestions = $extractor->get_suggestions($iban);
        if (!empty($suggestions)) {
            $response['suggestions'] = $suggestions;
        }

        wp_send_json_error($response);
    }
}
add_action('wp_ajax_gf_validate_iban', __NAMESPACE__ . '\\gf_iban_ajax_validate');
add_action('wp_ajax_nopriv_gf_validate_iban', __NAMESPACE__ . '\\gf_iban_ajax_validate');

/**
 * AJAX handler for document upload and IBAN extraction.
 */
function gf_iban_ajax_extract_from_document()
{
    check_ajax_referer('gf_iban_validate', 'nonce');

    // Get field settings.
    $field_id = isset($_POST['field_id']) ? absint($_POST['field_id']) : 0;
    $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

    if (empty($field_id) || empty($form_id)) {
        wp_send_json_error(array('message' => __('Invalid field or form ID.', 'gravity-forms-iban-extractor')));
    }

    // Get form and field.
    $form = \GFAPI::get_form($form_id);
    if (!$form) {
        wp_send_json_error(array('message' => __('Form not found.', 'gravity-forms-iban-extractor')));
    }

    $field = \GFAPI::get_field($form, $field_id);
    if (!$field) {
        wp_send_json_error(array('message' => __('Field not found.', 'gravity-forms-iban-extractor')));
    }

    // Get API settings from field.
    $api_key = $field->poe_api_key ?? '';
    $model = $field->poe_model ?? '';

    if (empty($api_key)) {
        wp_send_json_error(array('message' => __('POE API key not configured.', 'gravity-forms-iban-extractor')));
    }

    if (empty($model)) {
        wp_send_json_error(array('message' => __('AI model not configured.', 'gravity-forms-iban-extractor')));
    }

    // Check for uploaded file.
    if (!isset($_FILES['document']) || empty($_FILES['document']['tmp_name'])) {
        wp_send_json_error(array('message' => __('No file uploaded.', 'gravity-forms-iban-extractor')));
    }

    $file = $_FILES['document'];

    // Validate file type.
    $allowed_types = array('image/jpeg', 'image/png', 'image/webp', 'application/pdf');
    $file_type = wp_check_filetype($file['name']);
    $mime_type = $file_type['type'];

    // Also check actual mime type.
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actual_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($actual_mime, $allowed_types, true)) {
        wp_send_json_error(array('message' => __('Invalid file type. Please upload PDF, PNG, JPG, or WEBP.', 'gravity-forms-iban-extractor')));
    }

    // Validate file size (10MB max).
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        wp_send_json_error(array('message' => __('File too large. Maximum size is 10MB.', 'gravity-forms-iban-extractor')));
    }

    // Read file and convert to base64.
    $file_content = file_get_contents($file['tmp_name']);

    // Handle PDF conversion.
    if ('application/pdf' === $actual_mime) {
        $image_data = gf_iban_convert_pdf_to_image($file['tmp_name']);
        if (is_wp_error($image_data)) {
            wp_send_json_error(array('message' => $image_data->get_error_message()));
        }
        $base64 = $image_data;
    } else {
        $base64 = base64_encode($file_content);
    }

    // Call POE API.
    $result = POE_API_Service::extract_iban_from_document($api_key, $model, $base64);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    if (!$result['success']) {
        wp_send_json_error(array('message' => $result['error'] ?? __('Extraction failed.', 'gravity-forms-iban-extractor')));
    }

    // Upload and save the document.
    $upload_result = gf_iban_save_uploaded_document($file);
    $document_url = is_wp_error($upload_result) ? '' : $upload_result;

    // Store extraction data in transient for later saving on form submission.
    // Use a unique token that will be submitted with the form.
    $extraction_token = wp_generate_uuid4();
    $transient_key = 'gf_iban_extraction_' . $extraction_token;
    $extraction_data = array_merge($result['data'], array('document_url' => $document_url));
    set_transient($transient_key, $extraction_data, HOUR_IN_SECONDS);

    wp_send_json_success(array(
        'extracted_data' => $result['data'],
        'document_url' => $document_url,
        'extraction_token' => $extraction_token,
    ));
}
add_action('wp_ajax_gf_iban_extract_from_document', __NAMESPACE__ . '\\gf_iban_ajax_extract_from_document');
add_action('wp_ajax_nopriv_gf_iban_extract_from_document', __NAMESPACE__ . '\\gf_iban_ajax_extract_from_document');

/**
 * AJAX handler for fetching available POE models.
 */
function gf_iban_ajax_get_models()
{
    error_log('GF IBAN Extractor: AJAX get_models called');

    // Verify nonce - but log instead of dying silently.
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gf_iban_admin')) {
        error_log('GF IBAN Extractor: Nonce verification failed for get_models');
        wp_send_json_error(array('message' => __('Security check failed.', 'gravity-forms-iban-extractor')));
    }

    if (!current_user_can('edit_posts')) {
        error_log('GF IBAN Extractor: Permission denied for get_models');
        wp_send_json_error(array('message' => __('Permission denied.', 'gravity-forms-iban-extractor')));
    }

    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

    if (empty($api_key)) {
        error_log('GF IBAN Extractor: Empty API key');
        wp_send_json_error(array('message' => __('API key is required.', 'gravity-forms-iban-extractor')));
    }

    error_log('GF IBAN Extractor: Calling POE API with key: ' . substr($api_key, 0, 10) . '...');

    $models = POE_API_Service::get_models($api_key);

    if (is_wp_error($models)) {
        error_log('GF IBAN Extractor: POE API error - ' . $models->get_error_message());
        wp_send_json_error(array('message' => $models->get_error_message()));
    }

    error_log('GF IBAN Extractor: Successfully retrieved ' . count($models) . ' models');
    wp_send_json_success(array('models' => $models));
}
add_action('wp_ajax_gf_iban_get_models', __NAMESPACE__ . '\\gf_iban_ajax_get_models');

/**
 * Convert PDF first page to image.
 *
 * @param string $pdf_path Path to PDF file.
 * @return string|\WP_Error Base64 encoded image or error.
 */
function gf_iban_convert_pdf_to_image($pdf_path)
{
    // Check if Imagick is available.
    if (!class_exists('Imagick')) {
        return new \WP_Error('no_imagick', __('PDF processing requires Imagick PHP extension.', 'gravity-forms-iban-extractor'));
    }

    try {
        $imagick = new \Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($pdf_path . '[0]'); // First page only.
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(85);

        $image_data = $imagick->getImageBlob();
        $imagick->destroy();

        return base64_encode($image_data);
    } catch (\Exception $e) {
        return new \WP_Error('pdf_error', __('Failed to process PDF: ', 'gravity-forms-iban-extractor') . $e->getMessage());
    }
}

/**
 * Save uploaded document to WordPress uploads.
 *
 * @param array $file The uploaded file array.
 * @return string|\WP_Error URL of saved file or error.
 */
function gf_iban_save_uploaded_document($file)
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload = wp_handle_upload($file, array('test_form' => false));

    if (isset($upload['error'])) {
        return new \WP_Error('upload_error', $upload['error']);
    }

    return $upload['url'];
}

/**
 * Plugin activation hook.
 */
function gf_iban_activate()
{
    // Create extraction data table.
    gf_iban_create_extraction_table();

    flush_rewrite_rules();
}

/**
 * Create the extraction data table.
 */
function gf_iban_create_extraction_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'gf_iban_entry_extraction';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        entry_id bigint(20) UNSIGNED NOT NULL,
        field_id int(10) UNSIGNED NOT NULL,
        extracted_iban varchar(50) DEFAULT NULL,
        extracted_bic varchar(20) DEFAULT NULL,
        extracted_bank_name varchar(255) DEFAULT NULL,
        extracted_first_name varchar(100) DEFAULT NULL,
        extracted_last_name varchar(100) DEFAULT NULL,
        document_url text DEFAULT NULL,
        extraction_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY entry_field (entry_id, field_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\gf_iban_activate');

/**
 * Plugin deactivation hook.
 */
function gf_iban_deactivate()
{
    // Deactivation tasks if needed.
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\gf_iban_deactivate');

/**
 * Register with Guilamu Bug Reporter
 */
add_action('plugins_loaded', function () {
    if (class_exists('Guilamu_Bug_Reporter')) {
        \Guilamu_Bug_Reporter::register(array(
            'slug' => 'gravity-forms-iban-extractor',
            'name' => 'Gravity Forms IBAN Extractor',
            'version' => GF_IBAN_EXTRACTOR_VERSION,
            'github_repo' => 'guilamu/gravity-forms-iban-extractor',
        ));
    }
}, 20);

/**
 * Add 'Report a Bug' link to plugin row meta.
 *
 * @param array  $links Plugin row meta links.
 * @param string $file  Plugin file path.
 * @return array Modified links.
 */
function gf_iban_plugin_row_meta($links, $file)
{
    if (plugin_basename(GF_IBAN_EXTRACTOR_PLUGIN_FILE) !== $file) {
        return $links;
    }

    if (class_exists('Guilamu_Bug_Reporter')) {
        $links[] = sprintf(
            '<a href="#" class="guilamu-bug-report-btn" data-plugin-slug="gravity-forms-iban-extractor" data-plugin-name="%s">%s</a>',
            esc_attr__('Gravity Forms IBAN Extractor', 'gravity-forms-iban-extractor'),
            esc_html__('🐛 Report a Bug', 'gravity-forms-iban-extractor')
        );
    } else {
        $links[] = '<a href="https://github.com/guilamu/guilamu-bug-reporter/releases" target="_blank">🐛 Report a Bug (install Bug Reporter)</a>';
    }

    return $links;
}
add_filter('plugin_row_meta', __NAMESPACE__ . '\\gf_iban_plugin_row_meta', 10, 2);
