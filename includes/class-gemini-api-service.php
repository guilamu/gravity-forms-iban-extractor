<?php
/**
 * Gemini API Service for IBAN Extraction
 *
 * Handles communication with Google Gemini API for document analysis and IBAN extraction.
 *
 * @package GravityFormsIBANExtractor
 */

namespace GravityFormsIBANExtractor;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Gemini_API_Service
 *
 * Provides methods to interact with the Google Gemini API for document analysis.
 */
class Gemini_API_Service
{

    /**
     * Gemini API base URL (OpenAI-compatible endpoint).
     */
    private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/openai';

    /**
     * Get available Gemini models (static list).
     *
     * @return array Array of models.
     */
    public static function get_models()
    {
        return array(
            array(
                'id'   => 'gemini-2.0-flash-lite',
                'name' => 'Gemini 2.0 Flash Lite',
            ),
            array(
                'id'   => 'gemini-2.0-flash',
                'name' => 'Gemini 2.0 Flash',
            ),
            array(
                'id'   => 'gemini-2.5-flash-preview-05-20',
                'name' => 'Gemini 2.5 Flash Preview',
            ),
            array(
                'id'   => 'gemini-2.5-pro-preview-05-06',
                'name' => 'Gemini 2.5 Pro Preview',
            ),
        );
    }

    /**
     * Extract IBAN information from a document image.
     *
     * @param string $api_key      Gemini API key.
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

        // Reuse the same prompt from POE service.
        $prompt = POE_API_Service::get_iban_extraction_prompt();

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
            'model'       => $model,
            'messages'    => array(
                array(
                    'role'    => 'user',
                    'content' => $message_content,
                ),
            ),
            'temperature' => 0.1,
            'max_tokens'  => 1000,
        );

        $response = self::make_api_request($api_key, $payload);

        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            return POE_API_Service::parse_extraction_response($content);
        }

        return new \WP_Error('invalid_response', __('Invalid API response', 'gravity-forms-iban-extractor'));
    }

    /**
     * Make a request to the Gemini API.
     *
     * @param string $api_key The API key.
     * @param array  $payload The request payload.
     * @return array|\WP_Error Decoded response or error.
     */
    private static function make_api_request($api_key, $payload)
    {
        $response = wp_remote_post(self::API_BASE_URL . '/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode($payload),
            'timeout' => 90,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if (200 !== $status_code) {
            $error_body = wp_remote_retrieve_body($response);
            error_log('GF IBAN Extractor Gemini API Error: ' . $error_body);
            return new \WP_Error('api_error', sprintf(__('API returned status %d', 'gravity-forms-iban-extractor'), $status_code));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return new \WP_Error('json_error', __('Failed to parse API response', 'gravity-forms-iban-extractor'));
        }

        return $body;
    }
}
