<?php
/**
 * POE API Service for IBAN Extraction
 *
 * Handles communication with POE API for document analysis and IBAN extraction.
 *
 * @package GravityFormsIBANExtractor
 */

namespace GravityFormsIBANExtractor;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class POE_API_Service
 *
 * Provides methods to interact with the POE API for document analysis.
 */
class POE_API_Service
{

    /**
     * POE API base URL.
     */
    private const API_BASE_URL = 'https://api.poe.com';

    /**
     * Get available models that support image input.
     *
     * @param string $api_key POE API key.
     * @return array|\WP_Error Array of models or error.
     */
    public static function get_models($api_key)
    {
        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', __('API key is required', 'gravity-forms-iban-extractor'));
        }

        // Check cache.
        $cache_key = 'gf_iban_poe_models_' . md5($api_key);
        $cached_models = get_transient($cache_key);

        if (false !== $cached_models && !empty($cached_models)) {
            error_log('GF IBAN Extractor POE API: Using cached models');
            return $cached_models;
        }

        error_log('GF IBAN Extractor POE API: Calling ' . self::API_BASE_URL . '/v1/models');

        $response = wp_remote_get(self::API_BASE_URL . '/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            error_log('GF IBAN Extractor POE API: WP Error - ' . $response->get_error_message());
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if (200 !== $status_code) {
            $error_body = wp_remote_retrieve_body($response);
            error_log('GF IBAN Extractor POE API: Error body - ' . substr($error_body, 0, 500));
            return new \WP_Error('api_error', sprintf(__('API returned status %d', 'gravity-forms-iban-extractor'), $status_code));
        }

        $raw_body = wp_remote_retrieve_body($response);
        error_log('GF IBAN Extractor POE API: Response length ' . strlen($raw_body) . ' bytes');

        $body = json_decode($raw_body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            error_log('GF IBAN Extractor POE API: JSON decode error - ' . json_last_error_msg());
            return new \WP_Error('json_error', __('Failed to parse API response', 'gravity-forms-iban-extractor'));
        }

        $models = array();
        $total_models = count($body['data'] ?? array());
        error_log('GF IBAN Extractor POE API: Total models in response: ' . $total_models);

        foreach ($body['data'] ?? array() as $model) {
            $input_modalities = $model['architecture']['input_modalities'] ?? array();

            // Only include models that support image input.
            if (in_array('image', $input_modalities)) {
                $models[] = array(
                    'id' => $model['id'],
                    'name' => $model['metadata']['display_name'] ?? $model['id'],
                );
            }
        }

        error_log('GF IBAN Extractor POE API: Found ' . count($models) . ' image-capable models');

        // Sort alphabetically by name.
        usort($models, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Only cache if we got models.
        if (!empty($models)) {
            set_transient($cache_key, $models, HOUR_IN_SECONDS);
        }

        return $models;
    }

    /**
     * Extract IBAN information from a document image.
     *
     * @param string $api_key      POE API key.
     * @param string $model        Model ID to use.
     * @param string $image_base64 Base64 encoded image.
     * @return array|\WP_Error Extracted data or error.
     */
    public static function extract_iban_from_document($api_key, $model, $image_base64)
    {
        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', __('API key is required', 'gravity-forms-iban-extractor'));
        }

        if (empty($model)) {
            return new \WP_Error('missing_model', __('Model is required', 'gravity-forms-iban-extractor'));
        }

        if (empty($image_base64)) {
            return new \WP_Error('missing_image', __('Image is required', 'gravity-forms-iban-extractor'));
        }

        // Build the prompt for IBAN extraction.
        $prompt = self::get_iban_extraction_prompt();

        // Build multimodal content.
        $message_content = array(
            array(
                'type' => 'text',
                'text' => $prompt,
            ),
            array(
                'type' => 'image_url',
                'image_url' => array(
                    'url' => 'data:image/jpeg;base64,' . $image_base64,
                ),
            ),
        );

        $payload = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $message_content,
                ),
            ),
            'temperature' => 0.1,
            'max_tokens' => 1000,
        );

        $response = wp_remote_post(self::API_BASE_URL . '/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 90,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if (200 !== $status_code) {
            $error_body = wp_remote_retrieve_body($response);
            error_log('GF IBAN Extractor POE API Error: ' . $error_body);
            return new \WP_Error('api_error', sprintf(__('API returned status %d', 'gravity-forms-iban-extractor'), $status_code));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['choices'][0]['message']['content'])) {
            $content = $body['choices'][0]['message']['content'];
            return self::parse_extraction_response($content);
        }

        return new \WP_Error('invalid_response', __('Invalid API response', 'gravity-forms-iban-extractor'));
    }

    /**
     * Get the prompt for IBAN extraction.
     *
     * @return string
     */
    public static function get_iban_extraction_prompt()
    {
        return "You are an expert at extracting bank account information from documents.

Analyze this image (which may be a bank statement, RIB, bank letter, or any document containing IBAN information) and extract the following information:

1. IBAN - The full International Bank Account Number (e.g., FR76 1234 5678 9012 3456 7890 123)
2. BIC/SWIFT - The BIC or SWIFT code (8 or 11 characters, e.g., BNPAFRPP)
3. Bank Name - The name of the bank
4. Account Holder First Name - The first name of the account holder
5. Account Holder Last Name - The last name/surname of the account holder

Return ONLY valid JSON with the exact keys below. Use null for any field you cannot find:

{
  \"iban\": \"FR76 1234 5678 9012 3456 7890 123\",
  \"bic\": \"BNPAFRPP\",
  \"bank_name\": \"BNP Paribas\",
  \"first_name\": \"Jean\",
  \"last_name\": \"Dupont\"
}

RULES:
1. Extract the IBAN exactly as shown, preserving any spaces or formatting
2. The BIC/SWIFT code is usually 8 or 11 alphanumeric characters
3. For names, extract only the account holder's name, not the bank's name
4. Return ONLY the JSON, no explanations or markdown formatting

JSON:";
    }

    /**
     * Parse the extraction response from the API.
     *
     * @param string $content The API response content.
     * @return array Parsed extraction data.
     */
    public static function parse_extraction_response($content)
    {
        // Try to extract JSON from the response.
        $json = self::extract_json_from_text($content);

        if (!$json) {
            return array(
                'success' => false,
                'error' => __('Could not parse extraction response', 'gravity-forms-iban-extractor'),
            );
        }

        // Normalize the response.
        $extracted_data = array(
            'iban' => $json['iban'] ?? null,
            'bic' => $json['bic'] ?? null,
            'bank_name' => $json['bank_name'] ?? null,
            'first_name' => $json['first_name'] ?? null,
            'last_name' => $json['last_name'] ?? null,
        );

        // Check if we got at least an IBAN.
        if (empty($extracted_data['iban'])) {
            return array(
                'success' => false,
                'error' => __('No IBAN found in the document', 'gravity-forms-iban-extractor'),
            );
        }

        return array(
            'success' => true,
            'data' => $extracted_data,
        );
    }

    /**
     * Extract JSON from text (handles markdown code blocks and raw JSON).
     *
     * @param string $text The text to parse.
     * @return array|null Parsed JSON or null.
     */
    private static function extract_json_from_text($text)
    {
        // Try to find markdown code block.
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            $json = json_decode($matches[1], true);
            if ($json) {
                return $json;
            }
        }

        // Try to find outer braces.
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return $json;
            }
        }

        // Fallback: try raw text.
        return json_decode($text, true);
    }
}
