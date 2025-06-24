<?php
/**
 * SSPU AI Image Editor
 *
 * Handles AI-powered image editing functionality using OpenAI DALL-E and Google Gemini
 * Updated to properly use Gemini 2.0 Flash Preview Image Generation model
 *
 * @package SSPU
 * @since 2.28.0
 */

if (!defined('WPINC')) {
    die;
}

class SSPU_AI_Image_Editor {
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Last error message
     */
    private $last_error = '';

    /**
     * Available AI models - Updated with correct model name
     */
    private $available_models = [
        'openai' => [
            'dall-e-3' => [
                'name' => 'DALL-E 3',
                'supports_vision' => false,
                'supports_generation' => true,
                'description' => 'Advanced text-to-image generation'
            ]
        ],
        'gemini' => [
            'gemini-2.0-flash-preview-image-generation' => [
                'name' => 'Gemini 2.0 Flash Preview (Image Generation)',
                'supports_vision' => true,
                'supports_generation' => true,
                'description' => 'Fast multimodal model for image editing',
                'api_model' => 'gemini-2.0-flash-preview-image-generation'
            ],
            'gemini-1.5-pro' => [
                'name' => 'Gemini 1.5 Pro',
                'supports_vision' => true,
                'supports_generation' => false,
                'description' => 'Advanced reasoning with vision'
            ],
            'gemini-1.5-flash' => [
                'name' => 'Gemini 1.5 Flash',
                'supports_vision' => true,
                'supports_generation' => false,
                'description' => 'Fast and efficient analysis'
            ]
        ],
        'vertex' => [
            'vertex-gemini-2.0' => [
                'name' => 'Vertex AI Gemini 2.0',
                'supports_vision' => true,
                'supports_generation' => true,
                'description' => 'Enterprise-grade Gemini via Vertex AI'
            ]
        ]
    ];

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize hooks if needed
    }

    /**
     * Handle AI edit request
     */
    public function handle_ai_edit() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';
            $prompt = sanitize_textarea_field($_POST['prompt']);
            $model = sanitize_text_field($_POST['model']);
            $session_id = sanitize_text_field($_POST['session_id']);

            if (empty($image_data) || empty($prompt)) {
                wp_send_json_error(['message' => 'Image and prompt are required']);
                return;
            }

            // Log the request
            error_log('[SSPU AI Editor] Processing request with model: ' . $model);

            // Process based on model provider
            if (strpos($model, 'dall-e') !== false) {
                $result = $this->process_dalle_request($image_data, $prompt);
            } elseif (strpos($model, 'gemini') !== false || strpos($model, 'vertex') !== false) {
                $result = $this->process_gemini_request($image_data, $prompt, $model);
            } else {
                wp_send_json_error(['message' => 'Unsupported model: ' . $model]);
                return;
            }

            if ($result['success']) {
                // Log activity
                if (class_exists('SSPU_Analytics')) {
                    $analytics = new SSPU_Analytics();
                    $analytics->log_activity(get_current_user_id(), 'ai_image_edited', [
                        'model' => $model,
                        'session_id' => $session_id
                    ]);
                }

                wp_send_json_success([
                    'edited_image' => $result['image'],
                    'response' => $result['message']
                ]);
            } else {
                wp_send_json_error(['message' => $result['error']]);
            }

        } catch (Exception $e) {
            error_log('[SSPU AI Editor] Exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Process DALL-E request
     */
    private function process_dalle_request($image_data, $prompt) {
        $api_key = get_option('sspu_openai_api_key');
        if (empty($api_key)) {
            return ['success' => false, 'error' => 'OpenAI API key not configured'];
        }

        try {
            // For DALL-E 3, we need to generate from text only
            $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                    'quality' => 'standard',
                    'response_format' => 'b64_json'
                ]),
                'timeout' => 60
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'error' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                return ['success' => false, 'error' => $body['error']['message']];
            }

            if (isset($body['data'][0]['b64_json'])) {
                return [
                    'success' => true,
                    'image' => 'data:image/png;base64,' . $body['data'][0]['b64_json'],
                    'message' => 'Image generated successfully with DALL-E 3'
                ];
            }

            return ['success' => false, 'error' => 'Unexpected response format'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process Gemini request - Updated to match working example
     */
    private function process_gemini_request($image_data, $prompt, $model) {
        $api_key = get_option('sspu_gemini_api_key');
        if (empty($api_key)) {
            return ['success' => false, 'error' => 'Gemini API key not configured'];
        }

        try {
            // Extract base64 data from data URI
            $base64_parts = explode(',', $image_data, 2);
            $base64_data = isset($base64_parts[1]) ? $base64_parts[1] : $base64_parts[0];

            // Log image size
            $image_size_bytes = strlen(base64_decode($base64_data));
            $image_size_mb = round($image_size_bytes / 1024 / 1024, 2);
            error_log('[SSPU AI Editor] Image size: ' . $image_size_mb . ' MB');

            // Get the correct model name
            $api_model = 'gemini-2.0-flash-preview-image-generation';
            if (isset($this->available_models['gemini'][$model]['api_model'])) {
                $api_model = $this->available_models['gemini'][$model]['api_model'];
            }

            // Build API URL
            $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$api_model}:generateContent?key=" . $api_key;

            // Build request payload matching the working example
            $request_body = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $base64_data
                                ]
                            ],
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generation_config' => [
                    'temperature' => 1.0,
                    'top_p' => 0.95,
                    'candidate_count' => 1,
                    'max_output_tokens' => 8192,
                    'response_modalities' => ['IMAGE', 'TEXT']  // Critical for image generation!
                ]
            ];

            // Log the request
            error_log('[SSPU AI Editor] API URL: ' . str_replace($api_key, 'HIDDEN', $api_url));
            error_log('[SSPU AI Editor] Request prompt: ' . $prompt);
            error_log('[SSPU AI Editor] Generation config: ' . json_encode($request_body['generation_config'], JSON_PRETTY_PRINT));

            // Make the API request
            $response = wp_remote_post($api_url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($request_body),
                'timeout' => 180
            ]);

            if (is_wp_error($response)) {
                $error = 'Network error: ' . $response->get_error_message();
                error_log('[SSPU AI Editor] Network Error: ' . $error);
                return ['success' => false, 'error' => $error];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $body = json_decode($response_body, true);

            // Log response
            error_log('[SSPU AI Editor] Response Code: ' . $response_code);

            if (isset($body['error'])) {
                $error_message = 'API Error: ' . $body['error']['message'];
                if (isset($body['error']['details'])) {
                    $error_message .= ' Details: ' . json_encode($body['error']['details']);
                }
                error_log('[SSPU AI Editor] API Error Details: ' . json_encode($body['error'], JSON_PRETTY_PRINT));
                return ['success' => false, 'error' => $error_message];
            }

            // Check for generated image in response
            if (isset($body['candidates'][0]['content']['parts'])) {
                $generated_image = null;
                $generated_text = '';

                error_log('[SSPU AI Editor] Number of parts in response: ' . count($body['candidates'][0]['content']['parts']));

                foreach ($body['candidates'][0]['content']['parts'] as $index => $part) {
                    error_log('[SSPU AI Editor] Part ' . $index . ' type: ' . json_encode(array_keys($part)));

                    // Check for both snake_case and camelCase formats (matching example plugin)
                    if (isset($part['inline_data']['data']) || isset($part['inlineData']['data'])) {
                        if (isset($part['inline_data'])) {
                            $generated_image = 'data:' . $part['inline_data']['mime_type'] . ';base64,' . $part['inline_data']['data'];
                            error_log('[SSPU AI Editor] Found image in part ' . $index . ', mime type: ' . $part['inline_data']['mime_type']);
                        } else {
                            // Handle camelCase format
                            $generated_image = 'data:' . $part['inlineData']['mimeType'] . ';base64,' . $part['inlineData']['data'];
                            error_log('[SSPU AI Editor] Found image in part ' . $index . ', mime type: ' . $part['inlineData']['mimeType']);
                        }
                    }

                    if (isset($part['text'])) {
                        $generated_text .= $part['text'] . ' ';
                        error_log('[SSPU AI Editor] Found text in part ' . $index . ': ' . substr($part['text'], 0, 100) . '...');
                    }
                }

                if ($generated_image) {
                    error_log('[SSPU AI Editor] SUCCESS: Image generated');
                    return [
                        'success' => true,
                        'image' => $generated_image,
                        'message' => !empty(trim($generated_text)) ? trim($generated_text) : 'Image processed successfully with Gemini'
                    ];
                }

                // If we have text but no image
                if (!empty($generated_text)) {
                    error_log('[SSPU AI Editor] FAIL: Only text returned, no image');
                    return [
                        'success' => false,
                        'error' => 'Model returned text instead of image. Please ensure you are using the image generation model.'
                    ];
                }
            }

            error_log('[SSPU AI Editor] FAIL: No valid content in response');
            return [
                'success' => false,
                'error' => 'No image generated. The model may not support image generation for this type of request.'
            ];

        } catch (Exception $e) {
            error_log('[SSPU AI Editor] Exception in process_gemini_request: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Handle smart rotate
     */
    public function handle_smart_rotate() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';

            if (empty($image_data)) {
                wp_send_json_error(['message' => 'Image data is required']);
                return;
            }

            // Use Gemini 2.0 Flash Preview Image Generation for smart rotate
$prompt = "Extract the main product and create a professional e-commerce image:\n\n" .
         "SPECS:\n" .
         "• Pure white background (#FFFFFF)\n" .
         "• Center product perfectly at 0° angle (no tilt/rotation)\n" .
         "• Product fills 80% of image area with 10% margins all sides\n" .
         "• Subtle drop shadow: 15% opacity, soft blur, directly below\n" .
         "• Even studio lighting, maintain original colors\n" .
         "• Sharp focus, clean edges, no background remnants\n\n" .
         "CRITICAL: Output must be identical each time - same size, position, margins, and shadow for consistent results.";

            $result = $this->process_gemini_request($image_data, $prompt, 'gemini-2.0-flash-preview-image-generation');

            if ($result['success']) {
                wp_send_json_success([
                    'edited_image' => $result['image'],
                    'response' => 'Smart rotate applied successfully'
                ]);
            } else {
                wp_send_json_error(['message' => $result['error']]);
            }

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle mimic style
     */
    public function handle_mimic_style() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $source_image_data = isset($_POST['source_image_data']) ? $_POST['source_image_data'] : '';
            $reference_image_id = absint($_POST['reference_image_id']);
            $custom_prompt = sanitize_textarea_field($_POST['custom_prompt']);
            $session_id = sanitize_text_field($_POST['session_id']);

            if (empty($source_image_data) || !$reference_image_id) {
                wp_send_json_error(['message' => 'Source image and reference image are required']);
                return;
            }

            // Get reference image
            $reference_url = wp_get_attachment_url($reference_image_id);
            if (!$reference_url) {
                wp_send_json_error(['message' => 'Reference image not found']);
                return;
            }

            // Convert reference image to base64
            $reference_base64 = $this->url_to_base64($reference_url);
            if (!$reference_base64) {
                wp_send_json_error(['message' => 'Failed to process reference image']);
                return;
            }

            // Build mimic prompt
            $prompt = "You are looking at two images:\n";
            $prompt .= "1. A reference image showing the desired style, background, and composition\n";
            $prompt .= "2. A source image containing the product to be styled\n\n";
            $prompt .= "Your task: Extract the product from the source image and apply the EXACT style, lighting, background, and composition from the reference image.\n";
            $prompt .= "Maintain all product details while matching the reference style perfectly.\n";

            if (!empty($custom_prompt)) {
                $prompt .= "\nAdditional instructions: " . $custom_prompt;
            }

            // Process with Gemini using two images
            $result = $this->process_mimic_request($source_image_data, $reference_base64, $prompt);

            if ($result['success']) {
                // Update mimic usage count
                $usage_count = intval(get_post_meta($reference_image_id, '_sspu_mimic_usage_count', true));
                update_post_meta($reference_image_id, '_sspu_mimic_usage_count', $usage_count + 1);

                wp_send_json_success([
                    'edited_image' => $result['image'],
                    'response' => 'Style successfully mimicked from reference image'
                ]);
            } else {
                wp_send_json_error(['message' => $result['error']]);
            }

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle mimic all variants with enhanced debugging - UPDATED TO HANDLE BOTH FORMATS
     */
    public function handle_mimic_all_variants() {
        try {
            // Enhanced debug logging
            error_log('[SSPU Mimic All] ===== START MIMIC ALL VARIANTS =====');
            error_log('[SSPU Mimic All] POST data keys: ' . implode(', ', array_keys($_POST)));
            
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            
            if (!current_user_can('upload_shopify_products')) {
                error_log('[SSPU Mimic All] Permission denied for user ID: ' . get_current_user_id());
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }
            
            // Check which format we're receiving
            if (isset($_POST['first_variant_image']) && isset($_POST['other_variant_images'])) {
                // New format from a different UI
                error_log('[SSPU Mimic All] Using new format with first_variant_image and other_variant_images');
                $this->handle_mimic_all_new_format();
                return;
            }
            
            // Original format handling
            error_log('[SSPU Mimic All] Using original format with source_variant_index and variants_data');
            
            // Get and validate source variant index
            $source_variant_index = isset($_POST['source_variant_index']) ? intval($_POST['source_variant_index']) : -1;
            error_log('[SSPU Mimic All] Source variant index: ' . $source_variant_index);
            
            if ($source_variant_index < 0) {
                error_log('[SSPU Mimic All] Invalid source variant index');
                wp_send_json_error(['message' => 'Invalid source variant index']);
                return;
            }
            
            // Get variants data
            $variants_data_json = isset($_POST['variants_data']) ? $_POST['variants_data'] : '';
            error_log('[SSPU Mimic All] Variants data JSON length: ' . strlen($variants_data_json));
            
            if (empty($variants_data_json)) {
                error_log('[SSPU Mimic All] Empty variants data');
                wp_send_json_error(['message' => 'No variants data provided']);
                return;
            }
            
            // Try to decode JSON with error handling
            $variants_data = json_decode(stripslashes($variants_data_json), true);
            $json_error = json_last_error();
            
            if ($json_error !== JSON_ERROR_NONE) {
                error_log('[SSPU Mimic All] JSON decode error: ' . json_last_error_msg());
                error_log('[SSPU Mimic All] First 200 chars of JSON: ' . substr($variants_data_json, 0, 200));
                wp_send_json_error(['message' => 'Invalid JSON data: ' . json_last_error_msg()]);
                return;
            }
            
            error_log('[SSPU Mimic All] Decoded variants count: ' . (is_array($variants_data) ? count($variants_data) : 'NOT AN ARRAY'));
            
            if (!is_array($variants_data)) {
                error_log('[SSPU Mimic All] Variants data is not an array. Type: ' . gettype($variants_data));
                wp_send_json_error(['message' => 'Variants data must be an array']);
                return;
            }
            
            if (empty($variants_data)) {
                error_log('[SSPU Mimic All] Variants array is empty');
                wp_send_json_error(['message' => 'No variants found in data']);
                return;
            }
            
            // Log each variant's structure
            foreach ($variants_data as $index => $variant) {
                error_log('[SSPU Mimic All] Variant ' . $index . ' structure: ' . json_encode($variant));
            }

            // Get the source variant
            if (!isset($variants_data[$source_variant_index])) {
                error_log('[SSPU Mimic All] Error: Source variant not found at index ' . $source_variant_index);
                wp_send_json_error(['message' => 'Source variant not found']);
                return;
            }

            $source_variant = $variants_data[$source_variant_index];
            $source_image_id = isset($source_variant['image_id']) ? intval($source_variant['image_id']) : 0;

            error_log('[SSPU Mimic All] Source image ID: ' . $source_image_id);

            if (!$source_image_id) {
                error_log('[SSPU Mimic All] Error: Source variant has no image');
                wp_send_json_error(['message' => 'Source variant has no image']);
                return;
            }

            // Get source image URL and validate it exists
            $source_image_url = wp_get_attachment_url($source_image_id);
            if (!$source_image_url) {
                error_log('[SSPU Mimic All] Error: Could not get source image URL for ID ' . $source_image_id);
                wp_send_json_error(['message' => 'Could not get source image URL']);
                return;
            }

            error_log('[SSPU Mimic All] Source image URL: ' . $source_image_url);

            // Initialize results array
            $results = [];
            $processed_count = 0;
            $failed_count = 0;
            $skipped_count = 0;

            // Rate limiting setup
            $rate_limit_delay = 2; // 2 seconds between requests
            $max_batch_size = 10; // Process maximum 10 variants at once

            error_log('[SSPU Mimic All] Processing variants with rate limit of ' . $rate_limit_delay . ' seconds');

            // Process each variant (except the source)
            foreach ($variants_data as $index => $variant) {
                // Skip the source variant
                if ($index === $source_variant_index) {
                    error_log('[SSPU Mimic All] Skipping source variant at index ' . $index);
                    $skipped_count++;
                    continue;
                }

                // Check if we've hit the batch limit
                if ($processed_count >= $max_batch_size) {
                    error_log('[SSPU Mimic All] Reached batch limit of ' . $max_batch_size);
                    break;
                }

                $target_image_id = isset($variant['image_id']) ? intval($variant['image_id']) : 0;

                // Skip variants without images
                if (!$target_image_id) {
                    error_log('[SSPU Mimic All] Skipping variant at index ' . $index . ' - no image');
                    $skipped_count++;
                    continue;
                }

                // Get target image URL
                $target_image_url = wp_get_attachment_url($target_image_id);
                if (!$target_image_url) {
                    error_log('[SSPU Mimic All] Failed to get URL for image ID ' . $target_image_id);
                    $failed_count++;
                    continue;
                }

                error_log('[SSPU Mimic All] Processing variant ' . $index . ' with image ID ' . $target_image_id);

                // Apply rate limiting (except for first request)
                if ($processed_count > 0) {
                    error_log('[SSPU Mimic All] Rate limiting - sleeping for ' . $rate_limit_delay . ' seconds');
                    sleep($rate_limit_delay);
                }

                try {
                    // Build the mimic prompt
                    $variant_name = isset($variant['option_value']) ? $variant['option_value'] : 'Variant ' . $index;
                    $prompt = $this->build_mimic_prompt($source_variant, $variant, $variant_name);

                    error_log('[SSPU Mimic All] Generated prompt: ' . substr($prompt, 0, 200) . '...');

                    // Convert URLs to base64
                    $target_base64 = $this->url_to_base64($target_image_url);
                    $reference_base64 = $this->url_to_base64($source_image_url);

                    if (!$target_base64 || !$reference_base64) {
                        throw new Exception('Failed to convert images to base64');
                    }

                    // Call process_mimic_request with base64 images
                    $result = $this->process_mimic_request($target_base64, $reference_base64, $prompt);

                    if ($result && isset($result['image'])) {
                        // Save the new image
                        $new_attachment_id = $this->save_generated_image(
                            $result['image'],
                            'variant-' . sanitize_title($variant_name) . '-mimicked'
                        );

                        if ($new_attachment_id) {
                            $results[] = [
                                'index' => $index,
                                'success' => true,
                                'new_image_id' => $new_attachment_id,
                                'new_image_url' => wp_get_attachment_url($new_attachment_id),
                                'variant_name' => $variant_name
                            ];
                            $processed_count++;
                            error_log('[SSPU Mimic All] Successfully processed variant ' . $index);
                        } else {
                            $results[] = [
                                'index' => $index,
                                'success' => false,
                                'error' => 'Failed to save generated image',
                                'variant_name' => $variant_name
                            ];
                            $failed_count++;
                            error_log('[SSPU Mimic All] Failed to save image for variant ' . $index);
                        }
                    } else {
                        $error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
                        $results[] = [
                            'index' => $index,
                            'success' => false,
                            'error' => 'API request failed: ' . $error_msg,
                            'variant_name' => $variant_name
                        ];
                        $failed_count++;
                        error_log('[SSPU Mimic All] API request failed for variant ' . $index . ': ' . $error_msg);
                    }
                } catch (Exception $e) {
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => 'Exception: ' . $e->getMessage(),
                        'variant_name' => $variant_name
                    ];
                    $failed_count++;
                    error_log('[SSPU Mimic All] Exception for variant ' . $index . ': ' . $e->getMessage());
                }
            }

            // Log final results
            error_log('[SSPU Mimic All] Process complete. Processed: ' . $processed_count . ', Failed: ' . $failed_count . ', Skipped: ' . $skipped_count);
            error_log('[SSPU Mimic All] ===== END MIMIC ALL VARIANTS =====');

            // Return results
            if ($processed_count > 0) {
                wp_send_json_success([
                    'message' => sprintf(
                        'Mimic process complete. Successfully processed %d variants, %d failed, %d skipped.',
                        $processed_count,
                        $failed_count,
                        $skipped_count
                    ),
                    'results' => $results,
                    'stats' => [
                        'processed' => $processed_count,
                        'failed' => $failed_count,
                        'skipped' => $skipped_count,
                        'total' => count($variants_data)
                    ]
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Failed to process any variants. Check the error log for details.',
                    'results' => $results,
                    'stats' => [
                        'processed' => $processed_count,
                        'failed' => $failed_count,
                        'skipped' => $skipped_count,
                        'total' => count($variants_data)
                    ]
                ]);
            }

        } catch (Exception $e) {
            error_log('[SSPU Mimic All] Fatal error: ' . $e->getMessage());
            error_log('[SSPU Mimic All] Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error([
                'message' => 'Fatal error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the new format of mimic all variants
     */
    private function handle_mimic_all_new_format() {
        try {
            error_log('[SSPU Mimic All New Format] Processing with new format');
            
            $first_variant_image = isset($_POST['first_variant_image']) ? intval($_POST['first_variant_image']) : 0;
            $other_variant_images = isset($_POST['other_variant_images']) ? $_POST['other_variant_images'] : [];
            $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gemini-2.0-flash-preview-image-generation';
            $additional_instructions = isset($_POST['additional_instructions']) ? sanitize_textarea_field($_POST['additional_instructions']) : '';
            
            error_log('[SSPU Mimic All New Format] First variant image ID: ' . $first_variant_image);
            error_log('[SSPU Mimic All New Format] Other variant images: ' . json_encode($other_variant_images));
            
            if (!$first_variant_image) {
                error_log('[SSPU Mimic All New Format] No first variant image provided, attempting to find from variants');
                
                // Try to find the first variant with an image
                // This is a fallback for when the JS doesn't properly send the image ID
                wp_send_json_error(['message' => 'No reference image provided. Please ensure the first variant has an edited image.']);
                return;
            }
            
            if (empty($other_variant_images)) {
                wp_send_json_error(['message' => 'No target images provided']);
                return;
            }
            
            // Get reference image URL
            $reference_url = wp_get_attachment_url($first_variant_image);
            if (!$reference_url) {
                wp_send_json_error(['message' => 'Could not get reference image URL']);
                return;
            }
            
            // Initialize results
            $results = [];
            $processed_count = 0;
            $failed_count = 0;
            
            // Process each target image
            foreach ($other_variant_images as $index => $target_image_id) {
                $target_image_id = intval($target_image_id);
                if (!$target_image_id) {
                    $failed_count++;
                    continue;
                }
                
                $target_url = wp_get_attachment_url($target_image_id);
                if (!$target_url) {
                    $failed_count++;
                    continue;
                }
                
                // Rate limiting
                if ($processed_count > 0) {
                    sleep(2);
                }
                
                try {
                    // Build prompt
                    $prompt = "You are looking at two product images:\n";
                    $prompt .= "1. REFERENCE IMAGE: A professionally edited product photo with the desired style\n";
                    $prompt .= "2. TARGET IMAGE: A product that needs to match the reference style\n\n";
                    $prompt .= "Extract the product from the target image and apply the EXACT style, lighting, background, and composition from the reference image.\n";
                    $prompt .= "Maintain all product details while matching the reference style perfectly.\n";
                    
                    if (!empty($additional_instructions)) {
                        $prompt .= "\nAdditional instructions: " . $additional_instructions;
                    }
                    
                    // Convert to base64
                    $target_base64 = $this->url_to_base64($target_url);
                    $reference_base64 = $this->url_to_base64($reference_url);
                    
                    if (!$target_base64 || !$reference_base64) {
                        throw new Exception('Failed to convert images to base64');
                    }
                    
                    // Process with Gemini
                    $result = $this->process_mimic_request($target_base64, $reference_base64, $prompt);
                    
                    if ($result && isset($result['image'])) {
                        // Save the new image
                        $new_attachment_id = $this->save_generated_image(
                            $result['image'],
                            'variant-mimicked-' . time() . '-' . $index
                        );
                        
                        if ($new_attachment_id) {
                            $results[] = [
                                'original_id' => $target_image_id,
                                'new_image_id' => $new_attachment_id,
                                'new_image_url' => wp_get_attachment_url($new_attachment_id),
                                'success' => true
                            ];
                            $processed_count++;
                        } else {
                            $results[] = [
                                'original_id' => $target_image_id,
                                'success' => false,
                                'error' => 'Failed to save generated image'
                            ];
                            $failed_count++;
                        }
                    } else {
                        $results[] = [
                            'original_id' => $target_image_id,
                            'success' => false,
                            'error' => isset($result['error']) ? $result['error'] : 'Unknown error'
                        ];
                        $failed_count++;
                    }
                } catch (Exception $e) {
                    $results[] = [
                        'original_id' => $target_image_id,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    $failed_count++;
                }
            }
            
            error_log('[SSPU Mimic All New Format] Process complete. Processed: ' . $processed_count . ', Failed: ' . $failed_count);
            
            if ($processed_count > 0) {
                wp_send_json_success([
                    'message' => sprintf('Successfully processed %d images, %d failed.', $processed_count, $failed_count),
                    'results' => $results,
                    'processed' => $processed_count,
                    'failed' => $failed_count
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Failed to process any images',
                    'results' => $results
                ]);
            }
            
        } catch (Exception $e) {
            error_log('[SSPU Mimic All New Format] Exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Process mimic request with two images - Updated to use correct Gemini API format
     */
    private function process_mimic_request($target_image_base64, $reference_image_base64, $prompt) {
        try {
            $api_key = get_option('sspu_gemini_api_key');
            if (empty($api_key)) {
                return ['success' => false, 'error' => 'Gemini API key not configured'];
            }

            // Extract base64 data from both images
            $target_parts = explode(',', $target_image_base64, 2);
            $target_data = isset($target_parts[1]) ? $target_parts[1] : $target_parts[0];

            $reference_parts = explode(',', $reference_image_base64, 2);
            $reference_data = isset($reference_parts[1]) ? $reference_parts[1] : $reference_parts[0];

            // Use the correct model name
            $api_model = 'gemini-2.0-flash-preview-image-generation';
            $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$api_model}:generateContent?key=" . $api_key;

            // Build request payload with both images
            $request_body = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $reference_data
                                ]
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $target_data
                                ]
                            ],
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generation_config' => [
                    'temperature' => 1.0,
                    'top_p' => 0.95,
                    'candidate_count' => 1,
                    'max_output_tokens' => 8192,
                    'response_modalities' => ['IMAGE', 'TEXT']  // Critical!
                ]
            ];

            error_log('[SSPU Mimic Request] API URL: ' . str_replace($api_key, 'HIDDEN', $api_url));
            error_log('[SSPU Mimic Request] Sending request with 2 images and prompt');

            $response = wp_remote_post($api_url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($request_body),
                'timeout' => 180
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'error' => 'Network error: ' . $response->get_error_message()];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                return ['success' => false, 'error' => 'API Error: ' . $body['error']['message']];
            }

            // Parse response for generated image
            if (isset($body['candidates'][0]['content']['parts'])) {
                foreach ($body['candidates'][0]['content']['parts'] as $part) {
                    // Check for both formats
                    if (isset($part['inline_data']['data']) || isset($part['inlineData']['data'])) {
                        if (isset($part['inline_data'])) {
                            $generated_image = 'data:' . $part['inline_data']['mime_type'] . ';base64,' . $part['inline_data']['data'];
                        } else {
                            $generated_image = 'data:' . $part['inlineData']['mimeType'] . ';base64,' . $part['inlineData']['data'];
                        }

                        error_log('[SSPU Mimic Request] SUCCESS: Image generated');
                        return [
                            'success' => true,
                            'image' => $generated_image
                        ];
                    }
                }
            }

            return ['success' => false, 'error' => 'No image generated in response'];

        } catch (Exception $e) {
            error_log('[SSPU Mimic Request] Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build the mimic prompt for a specific variant
     */
    /**
 * Build the mimic prompt for a specific variant - ENHANCED VERSION
 */
private function build_mimic_prompt($source_variant, $target_variant, $variant_name) {
    $source_name = isset($source_variant['option_value']) ? $source_variant['option_value'] : 'source';
    $target_color = isset($target_variant['option_value']) ? $target_variant['option_value'] : $variant_name;

    $prompt = "CRITICAL TASK: You are looking at two images of the EXACT SAME PRODUCT MODEL in different colors.\n\n";
    
    $prompt .= "IMAGE 1 (REFERENCE): The '{$source_name}' colored variant - professionally edited with perfect styling\n";
    $prompt .= "IMAGE 2 (TARGET): The '{$target_color}' colored variant - needs the exact same styling applied\n\n";
    
    $prompt .= "YOUR PRECISE INSTRUCTIONS:\n";
    $prompt .= "1. IDENTIFY the exact product in both images - they are the SAME product model, just different colors\n";
    $prompt .= "2. EXTRACT the product from the target image (Image 2)\n";
    $prompt .= "3. RECREATE the EXACT scene from the reference image but with the target product:\n";
    $prompt .= "   • EXACT same product size (no scaling changes)\n";
    $prompt .= "   • EXACT same product position (pixel-perfect placement)\n";
    $prompt .= "   • EXACT same product angle and rotation\n";
    $prompt .= "   • EXACT same background (every detail must match)\n";
    $prompt .= "   • EXACT same lighting (direction, intensity, reflections)\n";
    $prompt .= "   • EXACT same shadows (size, opacity, blur, position)\n";
    $prompt .= "   • EXACT same composition and framing\n";
    $prompt .= "   • EXACT same depth of field and focus\n\n";
    
    $prompt .= "4. CRITICAL COLOR RULE:\n";
    $prompt .= "   • The ONLY difference should be the product's color\n";
    $prompt .= "   • PRESERVE the target product's original color ('{$target_color}')\n";
    $prompt .= "   • Do NOT change the product color to match the reference\n";
    $prompt .= "   • The '{$target_color}' color must remain exactly as shown in the target image\n\n";
    
    $prompt .= "5. QUALITY REQUIREMENTS:\n";
    $prompt .= "   • The final image must look IDENTICAL to the reference except for product color\n";
    $prompt .= "   • Both images must appear to be from the same professional photoshoot\n";
    $prompt .= "   • Maintain the same image dimensions and aspect ratio\n";
    $prompt .= "   • Ensure edge quality and anti-aliasing matches the reference\n\n";
    
    $prompt .= "SUMMARY: Create an exact replica of the reference image's composition, but featuring the target product with its original '{$target_color}' color intact.";

    return $prompt;
}

    /**
     * Convert URL to base64
     */
    private function url_to_base64($url) {
        try {
            $response = wp_remote_get($url, ['timeout' => 30]);

            if (is_wp_error($response)) {
                error_log('[SSPU] Failed to fetch image from URL: ' . $url . ' - Error: ' . $response->get_error_message());
                return false;
            }

            $image_data = wp_remote_retrieve_body($response);
            $mime_type = wp_remote_retrieve_header($response, 'content-type');

            if (empty($mime_type)) {
                $mime_type = 'image/jpeg'; // Default fallback
            }

            return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);

        } catch (Exception $e) {
            error_log('[SSPU] Exception in url_to_base64: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save generated image
     */
    private function save_generated_image($image_data_or_url, $filename) {
        try {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // Check if it's a data URI or URL
            if (strpos($image_data_or_url, 'data:') === 0) {
                // It's a data URI
                $data = explode(',', $image_data_or_url);
                $image_data = base64_decode($data[1]);

                // Determine file extension from mime type
                $mime_info = explode(';', $data[0]);
                $mime_type = str_replace('data:', '', $mime_info[0]);
                $extension = 'jpg'; // Default

                if ($mime_type === 'image/png') {
                    $extension = 'png';
                } elseif ($mime_type === 'image/gif') {
                    $extension = 'gif';
                } elseif ($mime_type === 'image/webp') {
                    $extension = 'webp';
                }
            } else {
                // It's a URL - download it
                $tmp = download_url($image_data_or_url);
                if (is_wp_error($tmp)) {
                    error_log('[SSPU] Failed to download image: ' . $tmp->get_error_message());
                    return false;
                }

                $image_data = file_get_contents($tmp);
                @unlink($tmp);
                $extension = 'jpg'; // Default
            }

            // Create a temporary file
            $upload_dir = wp_upload_dir();
            $filename = sanitize_file_name($filename . '.' . $extension);
            $file_path = $upload_dir['path'] . '/' . $filename;

            // Save the image data to file
            $saved = file_put_contents($file_path, $image_data);
            if ($saved === false) {
                error_log('[SSPU] Failed to save image to file: ' . $file_path);
                return false;
            }

            // Create attachment
            $attachment = array(
                'post_mime_type' => $mime_type ?? 'image/jpeg',
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $file_path);

            if (!is_wp_error($attach_id)) {
                $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                wp_update_attachment_metadata($attach_id, $attach_data);
                return $attach_id;
            } else {
                error_log('[SSPU] Failed to create attachment: ' . $attach_id->get_error_message());
                @unlink($file_path);
                return false;
            }

        } catch (Exception $e) {
            error_log('[SSPU] Exception in save_generated_image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle saving edited image
     */
    public function handle_save_edited_image() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';
            $filename = sanitize_file_name($_POST['filename']);

            if (empty($image_data) || !strpos($image_data, 'data:') === 0) {
                wp_send_json_error(['message' => 'Invalid image data']);
                return;
            }

            $attachment_id = $this->save_generated_image($image_data, $filename);

            if ($attachment_id) {
                $attachment_url = wp_get_attachment_url($attachment_id);

                // Log activity
                if (class_exists('SSPU_Analytics')) {
                    $analytics = new SSPU_Analytics();
                    $analytics->log_activity(get_current_user_id(), 'ai_image_saved', [
                        'attachment_id' => $attachment_id,
                        'filename' => $filename
                    ]);
                }

                wp_send_json_success([
                    'attachment_id' => $attachment_id,
                    'url' => $attachment_url,
                    'filename' => $filename
                ]);
            } else {
                wp_send_json_error(['message' => 'Failed to save image']);
            }

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle getting chat history
     */
    public function handle_get_chat_history() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $session_id = sanitize_text_field($_POST['session_id']);

            // For now, return empty history as we don't persist chat history
            // This could be enhanced to store history in transients or user meta
            wp_send_json_success([
                'history' => []
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Test Gemini connection
     */
    public function test_gemini_connection() {
        $api_key = get_option('sspu_gemini_api_key');
        if (empty($api_key)) {
            $this->last_error = 'Gemini API key not configured';
            return false;
        }

        try {
            // Test with a simple text generation request
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Say "Hello" if this API key is working.']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 10,
                ]
            ];

            $response = wp_remote_post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key,
                [
                    'timeout' => 15,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($payload)
                ]
            );

            if (is_wp_error($response)) {
                $this->last_error = $response->get_error_message();
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                return true;
            } else {
                $response_body = wp_remote_retrieve_body($response);
                $error_data = json_decode($response_body, true);
                $this->last_error = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'API Error ' . $response_code;
                return false;
            }

        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get last error
     */
    public function get_last_error() {
        return $this->last_error;
    }
}

// Initialize the singleton
SSPU_AI_Image_Editor::get_instance();