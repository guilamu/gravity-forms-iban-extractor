<?php
/**
 * IBAN Extractor Class
 *
 * Wrapper class for the php-iban library to validate and extract IBAN information.
 *
 * @package GravityFormsIBANExtractor
 */

namespace GravityFormsIBANExtractor;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class IBAN_Extractor
 *
 * Provides methods to validate IBANs and extract their component data.
 */
class IBAN_Extractor
{

    /**
     * Validate an IBAN.
     *
     * @param string $iban The IBAN to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate($iban)
    {
        if (empty($iban)) {
            return false;
        }

        return verify_iban($iban);
    }

    /**
     * Extract all available information from an IBAN.
     *
     * @param string $iban The IBAN to extract data from.
     * @return array Array containing extracted IBAN data.
     */
    public function extract($iban)
    {
        $result = array(
            'valid' => false,
            'account' => '',
            'bban' => '',
            'country_code' => '',
            'country_name' => '',
            'currency' => '',
            'bank_code' => '',
            'branch_code' => '',
            'formatted' => '',
            'checksum' => '',
        );

        if (empty($iban)) {
            return $result;
        }

        // Validate the IBAN first.
        $is_valid = verify_iban($iban);

        if (!$is_valid) {
            return $result;
        }

        $result['valid'] = true;

        // Convert to machine format for consistent parsing.
        $machine_iban = iban_to_machine_format($iban);

        // Extract country code first (needed for country-level functions).
        $country_code = iban_get_country_part($machine_iban);

        // Extract all parts.
        $result['account'] = iban_get_account_part($machine_iban);
        $result['bban'] = iban_get_bban_part($machine_iban);
        $result['country_code'] = $country_code;
        $result['country_name'] = iban_country_get_country_name($country_code);
        $result['currency'] = iban_country_get_currency_iso4217($country_code);
        $result['bank_code'] = iban_get_bank_part($machine_iban);
        $result['branch_code'] = iban_get_branch_part($machine_iban);
        $result['formatted'] = iban_to_human_format($machine_iban);
        $result['checksum'] = iban_get_checksum_part($machine_iban);

        // Additional country information.
        $result['is_sepa'] = iban_country_is_sepa($country_code);
        $result['central_bank'] = iban_country_get_central_bank_name($country_code);
        $result['central_bank_url'] = iban_country_get_central_bank_url($country_code);

        return $result;
    }

    /**
     * Get mistranscription suggestions for an invalid IBAN.
     *
     * @param string $iban The invalid IBAN.
     * @return array Array of suggested corrections.
     */
    public function get_suggestions($iban)
    {
        if (empty($iban)) {
            return array();
        }

        $suggestions = iban_mistranscription_suggestions($iban);

        return is_array($suggestions) ? $suggestions : array();
    }

    /**
     * Format an IBAN for human-readable display.
     *
     * @param string $iban The IBAN to format.
     * @return string The formatted IBAN with spaces.
     */
    public function format_for_display($iban)
    {
        if (empty($iban)) {
            return '';
        }

        return iban_to_human_format(iban_to_machine_format($iban));
    }

    /**
     * Convert IBAN to machine format (no spaces, uppercase).
     *
     * @param string $iban The IBAN to convert.
     * @return string The machine-formatted IBAN.
     */
    public function to_machine_format($iban)
    {
        if (empty($iban)) {
            return '';
        }

        return iban_to_machine_format($iban);
    }

    /**
     * Verify IBAN checksum.
     *
     * @param string $iban The IBAN to verify.
     * @return bool True if checksum is valid.
     */
    public function verify_checksum($iban)
    {
        if (empty($iban)) {
            return false;
        }

        return iban_verify_checksum($iban);
    }

    /**
     * Get all available parts from an IBAN.
     *
     * @param string $iban The IBAN to parse.
     * @return array Associative array of IBAN parts.
     */
    public function get_parts($iban)
    {
        if (empty($iban) || !verify_iban($iban)) {
            return array();
        }

        return iban_get_parts($iban);
    }
}
