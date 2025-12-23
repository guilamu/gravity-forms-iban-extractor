<?php
/**
 * Plugin Name: Gravity Forms IBAN Extractor
 * Plugin URI: https://github.com/guilamu/gravity-forms-iban-extractor
 * Description: Adds an IBAN extractor field type to Gravity Forms with real-time validation and data extraction.
 * Version: 1.0.0
 * Author: Guilamu
 * Author URI: https://github.com/guilamu
 * License: AGPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 * Text Domain: gravity-forms-iban-extractor
 * Domain Path: /languages
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
define('GF_IBAN_EXTRACTOR_VERSION', '1.0.0');
define('GF_IBAN_EXTRACTOR_PLUGIN_FILE', __FILE__);
define('GF_IBAN_EXTRACTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GF_IBAN_EXTRACTOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Load Composer autoloader.
 */
if (file_exists(GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once GF_IBAN_EXTRACTOR_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Gravity Forms IBAN Extractor: Please run "composer install" in the plugin directory.', 'gravity-forms-iban-extractor'); ?>
            </p>
        </div>
        <?php
    });
    return;
}

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
 * Plugin activation hook.
 */
function gf_iban_activate()
{
    // Activation tasks if needed.
    flush_rewrite_rules();
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
