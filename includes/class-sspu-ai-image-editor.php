<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_AI_Image_Editor {
    
    private $openai_api_key;
    private $gemini_api_key;
    private $anthropic_api_key;
    private $last_error = '';
    private $supported_models = [];
    
    public function __construct() {
        $this->openai_api_key = get_option('sspu_openai_api_key');
        $this->gemini_api_key = get_option('sspu_gemini_api_key');
        $this->anthropic_api_key = get_option('sspu_anthropic_api_key');
        
        $this->init_supported_models();
    }
    
    /**
     * Initialize supported models with their configurations
     */
    private function init_supported_models() {
        $this->supported_models = [
            // OpenAI Models
            'gpt-4o' => [
                'provider' => 'openai',
                'name' => 'GPT-4 Omni',
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'supports_vision' => true,
                'supports_generation' => false,
                'max_tokens' => 4096,
                'description' => 'Latest multimodal model with vision capabilities'
            ],
            'gpt-4-turbo' => [
                'provider' => 'openai',
                'name' => 'GPT-4 Turbo with Vision',
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'supports_vision' => true,
                'supports_generation' => false,
                'max_tokens' => 4096,
                'description' => 'GPT-4 Turbo with vision understanding'
            ],
            'dall-e-3' => [
                'provider' => 'openai',
                'name' => 'DALL-E 3',
                'endpoint' => 'https://api.openai.com/v1/images/generations',
                'supports_vision' => false,
                'supports_generation' => true,
                'description' => 'Advanced image generation model'
            ],
            
            // Google Gemini Models
            'gemini-2.0-flash-preview' => [
                'provider' => 'gemini',
                'name' => 'Gemini 2.0 Flash Preview (Image Gen)',
                'model_id' => 'gemini-2.0-flash-preview-image-generation',
                'supports_vision' => true,
                'supports_generation' => true, // This model can generate images!
                'max_tokens' => 8192,
                'description' => 'Latest Gemini model with image generation capabilities'
            ],
            'gemini-2.0-flash-exp' => [
                'provider' => 'gemini',
                'name' => 'Gemini 2.0 Flash Experimental',
                'model_id' => 'gemini-2.0-flash-exp',
                'supports_vision' => true,
                'supports_generation' => false,
                'max_tokens' => 8192,
                'description' => 'Latest experimental Gemini model for analysis'
            ],
            'gemini-1.5-pro' => [
                'provider' => 'gemini',
                'name' => 'Gemini 1.5 Pro',
                'model_id' => 'gemini-1.5-pro-latest',
                'supports_vision' => true,
                'supports_generation' => false,
                'max_tokens' => 8192,
                'description' => 'Advanced reasoning with vision'
            ],
            'gemini-1.5-flash' => [
                'provider' => 'gemini',
                'name' => 'Gemini 1.5 Flash',
                'model_id' => 'gemini-1.5-flash-latest',
                'supports_vision' => true,
                'supports_generation' => false,
                'max_tokens' => 8192,
                'description' => 'Fast multimodal model'
            ]
        ];
    }
    
    /**
     * Handle AI image editing request
     */
    public function handle_ai_edit() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $image_data = $_POST['image_data'];
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $ai_service = sanitize_text_field($_POST['ai_service']);
        $model = sanitize_text_field($_POST['model'] ?? 'gpt-4o');
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Log request details
        error_log('SSPU AI Editor Request:');
        error_log('  Service: ' . $ai_service);
        error_log('  Model: ' . $model);
        error_log('  Prompt: ' . substr($prompt, 0, 100) . '...');
        
        // Route to appropriate handler based on service
        switch ($ai_service) {
            case 'analyze_chatgpt':
                $result = $this->analyze_with_model($image_data, $prompt, $model);
                break;
                
            case 'analyze_gemini':
                $result = $this->analyze_with_model($image_data, $prompt, $model);
                break;
                
            case 'edit_dalle':
                $result = $this->generate_with_dalle($image_data, $prompt);
                break;
                
            case 'generate_gemini':
                $result = $this->generate_with_gemini($image_data, $prompt, $model);
                break;
                
            case 'batch_process':
                $result = $this->batch_process($image_data, $prompt, $model);
                break;
                
            default:
                $result = $this->analyze_with_model($image_data, $prompt, $model);
                break;
        }
        
        if ($result && !isset($result['error'])) {
            // Store in session history
            $this->store_chat_history($session_id, 'user', $prompt);
            $this->store_chat_history($session_id, 'ai', $result['message'], $result['image'] ?? null);
            
            wp_send_json_success([
                'edited_image' => $result['image'] ?? null,
                'response' => $result['message']
            ]);
        } else {
            $error_msg = $result['error'] ?? $this->last_error ?? 'Failed to process image';
            error_log('SSPU AI Editor Error: ' . $error_msg);
            wp_send_json_error(['message' => $error_msg]);
        }
    }
    
    /**
     * Analyze image with any supported model
     */
    private function analyze_with_model($image_data, $prompt, $model_id) {
        if (!isset($this->supported_models[$model_id])) {
            return ['error' => 'Unsupported model: ' . $model_id];
        }
        
        $model = $this->supported_models[$model_id];
        
        // Check if this is a generation request for a model that supports it
        if ($model['supports_generation'] && $this->is_generation_prompt($prompt)) {
            switch ($model['provider']) {
                case 'gemini':
                    return $this->generate_with_gemini($image_data, $prompt, $model_id);
                case 'openai':
                    return $this->generate_with_dalle($image_data, $prompt);
            }
        }
        
        // Otherwise, analyze as usual
        if (!$model['supports_vision']) {
            return ['error' => 'Model does not support vision analysis'];
        }
        
        // Route to appropriate provider
        switch ($model['provider']) {
            case 'openai':
                return $this->analyze_with_openai($image_data, $prompt, $model_id);
                
            case 'gemini':
                return $this->analyze_with_gemini($image_data, $prompt, $model_id);
                
            default:
                return ['error' => 'Unknown provider for model'];
        }
    }
    
    /**
     * Helper method to detect generation prompts
     */
    private function is_generation_prompt($prompt) {
        $generation_keywords = [
            'place on', 'put on', 'background', 'extract', 
            'remove background', 'lifestyle', 'create', 
            'generate', 'make', 'stage', 'add to',
            'place it', 'put it', 'on a'
        ];
        
        $prompt_lower = strtolower($prompt);
        foreach ($generation_keywords as $keyword) {
            if (strpos($prompt_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Analyze image with OpenAI models
     */
    private function analyze_with_openai($image_data, $prompt, $model_id) {
        if (empty($this->openai_api_key)) {
            return ['error' => 'OpenAI API key not configured'];
        }
        
        $model_config = $this->supported_models[$model_id];
        
        // Ensure image data is properly formatted
        if (strpos($image_data, 'data:') !== 0) {
            $mime_type = $this->detect_mime_type_from_data($image_data);
            $image_data = "data:{$mime_type};base64," . $image_data;
        }
        
        // Build the system prompt for product image analysis
        $system_prompt = $this->get_system_prompt_for_analysis();
        
        // Create the request
        $messages = [
            [
                'role' => 'system',
                'content' => $system_prompt
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $image_data,
                            'detail' => 'high'
                        ]
                    ]
                ]
            ]
        ];
        
        $response = wp_remote_post($model_config['endpoint'], [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openai_api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $model_id,
                'messages' => $messages,
                'max_tokens' => $model_config['max_tokens'] ?? 1000,
                'temperature' => 0.7
            ]),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return ['error' => 'Network error: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return ['error' => 'OpenAI API Error: ' . $data['error']['message']];
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            return [
                'message' => $data['choices'][0]['message']['content'],
                'image' => null // Analysis doesn't generate new images
            ];
        }
        
        return ['error' => 'Invalid response from OpenAI'];
    }
    
    /**
     * Analyze image with Gemini models
     */
    private function analyze_with_gemini($image_data, $prompt, $model_id) {
        if (empty($this->gemini_api_key)) {
            return ['error' => 'Gemini API key not configured'];
        }
        
        $model_config = $this->supported_models[$model_id];
        
        // Extract base64 data
        $base64_data = $this->extract_base64_data($image_data);
        if (empty($base64_data)) {
            return ['error' => 'Invalid image data provided'];
        }
        
        $mime_type = $this->detect_mime_type($image_data);
        
        // Build API URL
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_config['model_id']}:generateContent?key=" . $this->gemini_api_key;
        
        // Create enhanced prompt
        $enhanced_prompt = $this->get_enhanced_prompt_for_gemini($prompt);
        
        $request_body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $enhanced_prompt
                        ],
                        [
                            'inlineData' => [
                                'mimeType' => $mime_type,
                                'data' => $base64_data
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topP' => 0.8,
                'topK' => 40,
                'maxOutputTokens' => $model_config['max_tokens'] ?? 2048,
                'responseMimeType' => 'text/plain'
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH']
            ]
        ];
        
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_body),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return ['error' => 'Network error: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return ['error' => 'Gemini API Error: ' . $data['error']['message']];
        }
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $response_text = $data['candidates'][0]['content']['parts'][0]['text'];
            
            return [
                'message' => "🔍 **{$model_config['name']} Analysis:**\n\n" . $response_text,
                'image' => null
            ];
        }
        
        return ['error' => 'Invalid response from Gemini'];
    }
    
    /**
     * Generate image with Gemini 2.0 Flash Preview
     */
    private function generate_with_gemini($image_data, $prompt, $model_id = 'gemini-2.0-flash-preview') {
        if (empty($this->gemini_api_key)) {
            return ['error' => 'Gemini API key not configured'];
        }
        
        $model_config = $this->supported_models[$model_id];
        
        // Extract base64 data
        $base64_data = $this->extract_base64_data($image_data);
        if (empty($base64_data)) {
            return ['error' => 'Invalid image data provided'];
        }
        
        $mime_type = $this->detect_mime_type($image_data);
        
        // Build API URL for the image generation model
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_config['model_id']}:generateContent?key=" . $this->gemini_api_key;
        
        // Build the generation prompt with extraction emphasis
        $generation_prompt = "IMPORTANT: Extract the existing product from this image exactly as it appears - do not recreate or modify the product itself. " . $prompt;
        
        $request_body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => $mime_type,
                                'data' => $base64_data
                            ]
                        ],
                        [
                            'text' => $generation_prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 1.0,
                'topP' => 0.95,
                'topK' => 40,
                'maxOutputTokens' => 8192,
                'responseModalities' => ['TEXT', 'IMAGE'] // Enable image generation
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH']
            ]
        ];
        
        error_log('Gemini Image Generation Request URL: ' . $api_url);
        error_log('Request Body: ' . json_encode($request_body));
        
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_body),
            'timeout' => 120 // Longer timeout for image generation
        ]);
        
        if (is_wp_error($response)) {
            return ['error' => 'Network error: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        error_log('Gemini Response: ' . substr($body, 0, 500));
        
        if (isset($data['error'])) {
            return ['error' => 'Gemini API Error: ' . $data['error']['message']];
        }
        
        // Parse the response to extract generated image
        if (isset($data['candidates'][0]['content']['parts'])) {
            $parts = $data['candidates'][0]['content']['parts'];
            $text_response = '';
            $generated_image = null;
            
            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $text_response .= $part['text'];
                }
                if (isset($part['inlineData'])) {
                    // Extract the generated image
                    $image_data = $part['inlineData']['data'];
                    $image_mime = $part['inlineData']['mimeType'] ?? 'image/png';
                    $generated_image = "data:{$image_mime};base64,{$image_data}";
                }
            }
            
            if ($generated_image) {
                return [
                    'image' => $generated_image,
                    'message' => "✨ **Gemini 2.0 Flash Generated Image**\n\n" . 
                               "Successfully extracted your product and created a new image:\n\n" .
                               ($text_response ? $text_response : "Image generated based on your requirements.")
                ];
            } else {
                return [
                    'message' => $text_response ?: 'No image was generated. The model may not support this request.',
                    'image' => null
                ];
            }
        }
        
        return ['error' => 'Invalid response from Gemini'];
    }
    
    /**
     * Generate image with DALL-E 3
     */
    private function generate_with_dalle($image_data, $prompt) {
        if (empty($this->openai_api_key)) {
            return ['error' => 'OpenAI API key not configured'];
        }
        
        // First, analyze the current image to understand context
        $analysis_prompt = "Analyze this product image and identify: 1) The EXACT product shown (do not recreate), 2) Current background to remove, 3) Key product features that must be preserved exactly as shown";
        $analysis = $this->analyze_with_openai($image_data, $analysis_prompt, 'gpt-4o');
        
        if (isset($analysis['error'])) {
            return $analysis;
        }
        
        // Build enhanced generation prompt
        $generation_prompt = $this->build_dalle_prompt($prompt, $analysis['message'] ?? '');
        
        // Generate with DALL-E 3
        $api_url = 'https://api.openai.com/v1/images/generations';
        
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openai_api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'dall-e-3',
                'prompt' => $generation_prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'hd',
                'style' => 'natural'
            ]),
            'timeout' => 120
        ]);
        
        if (is_wp_error($response)) {
            return ['error' => 'Network error: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return ['error' => 'DALL-E API Error: ' . $data['error']['message']];
        }
        
        if (isset($data['data'][0]['url'])) {
            // Download and convert to base64
            $image_url = $data['data'][0]['url'];
            $image_response = wp_remote_get($image_url, ['timeout' => 60]);
            
            if (!is_wp_error($image_response)) {
                $image_content = wp_remote_retrieve_body($image_response);
                $base64_image = base64_encode($image_content);
                $data_uri = 'data:image/png;base64,' . $base64_image;
                
                return [
                    'image' => $data_uri,
                    'message' => "🎨 **DALL-E 3 Generated Image**\n\n" . 
                               "Successfully extracted your product and created a new image based on your requirements:\n\n" .
                               "• " . str_replace("\n", "\n• ", $this->summarize_dalle_prompt($generation_prompt))
                ];
            }
        }
        
        return ['error' => 'Failed to generate image with DALL-E'];
    }
    
    /**
     * Batch process multiple edits
     */
    private function batch_process($image_data, $prompt, $model_id) {
        $model = $this->supported_models[$model_id] ?? null;
        
        // Define batch operations
        $operations = [
            'white_bg' => 'EXTRACT the existing product exactly as shown and place on pure white background with professional lighting',
            'lifestyle' => 'EXTRACT the existing product without modifications and place in modern lifestyle setting appropriate for the product type',
            'hero' => 'EXTRACT the original product and create hero image with dramatic lighting and professional composition',
            'social' => 'EXTRACT the product as-is and optimize for social media with eye-catching composition and vibrant colors'
        ];
        
        $results = [];
        $generated_count = 0;
        
        foreach ($operations as $key => $operation_prompt) {
            // Use appropriate generation method based on model
            if ($model && $model['provider'] === 'gemini' && $model['supports_generation']) {
                $result = $this->generate_with_gemini($image_data, $operation_prompt, $model_id);
            } else {
                $result = $this->generate_with_dalle($image_data, $operation_prompt);
            }
            
            if (!isset($result['error']) && isset($result['image'])) {
                $results[] = [
                    'type' => $key,
                    'image' => $result['image'],
                    'description' => $operation_prompt
                ];
                $generated_count++;
            }
            
            // Add delay to avoid rate limiting
            if ($generated_count < count($operations)) {
                sleep(2);
            }
        }
        
        if ($generated_count > 0) {
            $model_name = ($model && $model['provider'] === 'gemini') ? 'Gemini' : 'DALL-E';
            return [
                'message' => "📦 **Batch Processing Complete with {$model_name}**\n\n" .
                            "Generated {$generated_count} variations using your extracted product:\n\n" .
                            "• White Background - Professional product shot\n" .
                            "• Lifestyle Setting - Contextual placement\n" .
                            "• Hero Image - Premium presentation\n" .
                            "• Social Media - Optimized for engagement\n\n" .
                            "All variations use your original product extracted from the source image.",
                'batch_results' => $results
            ];
        }
        
        return ['error' => 'Failed to generate batch variations'];
    }
    
    /**
     * Get system prompt for analysis
     */
    private function get_system_prompt_for_analysis() {
        return "You are an expert e-commerce product photographer and image consultant. Your role is to analyze product images and provide actionable suggestions for improvement. 

CRITICAL INSTRUCTION: Always recommend EXTRACTING the existing product exactly as it appears in the original image. Never suggest recreating or modifying the product itself - only its presentation, background, and environment.

Focus on:
1. **Product Extraction**: Identify how to cleanly EXTRACT the existing product from its current background while preserving all product details
2. **Background Recommendations**: Suggest appropriate backgrounds (lifestyle, studio, contextual) for the EXTRACTED product
3. **Lighting Analysis**: Evaluate current lighting and suggest improvements while keeping the product unchanged
4. **Composition**: Recommend optimal angles, positioning, and framing for the EXISTING product
5. **E-commerce Optimization**: Ensure images meet platform requirements using the ORIGINAL product
6. **Brand Enhancement**: Suggest where and how to add logos without altering the product

Always emphasize: 'Extract the existing product exactly as shown' in your recommendations.";
    }
    
    /**
     * Get enhanced prompt for Gemini
     */
    private function get_enhanced_prompt_for_gemini($user_prompt) {
        return "IMPORTANT: When analyzing or suggesting edits, always EXTRACT the existing product exactly as shown - never recreate or modify the product itself.\n\n" .
               $user_prompt . "\n\n" . 
               "Please analyze this product image as an e-commerce expert. Provide:\n" .
               "1. **Current State Analysis**: What you see in the image (identify the exact product to extract)\n" .
               "2. **Improvement Suggestions**: Specific changes to enhance the image while keeping the product unchanged\n" .
               "3. **Implementation Steps**: How to achieve these improvements by extracting the existing product\n" .
               "4. **Expected Results**: How the changes will improve conversion rates\n\n" .
               "Remember: Always extract the original product, never recreate it.";
    }
    
    /**
     * Build DALL-E prompt from user input and analysis
     */
    private function build_dalle_prompt($user_prompt, $analysis) {
        // Extract key information from analysis if available
        $product_description = $this->extract_product_description($analysis);
        
        // Build detailed prompt for DALL-E with extraction emphasis
        $dalle_prompt = "IMPORTANT: Extract and use the EXISTING product from the reference image exactly as it appears - do not recreate or modify the product itself. ";
        
        if ($product_description) {
            $dalle_prompt .= "The existing product to extract is {$product_description}. ";
        }
        
        $dalle_prompt .= $user_prompt . ". ";
        
        $dalle_prompt .= "Ensure you: extract the original product without any modifications, maintain exact product details and proportions, " .
                        "apply professional studio lighting to the scene (not the product), create sharp focus on the extracted product, " .
                        "and maintain the product's authentic appearance from the original image.";
        
        return $dalle_prompt;
    }
    
    /**
     * Extract product description from analysis
     */
    private function extract_product_description($analysis) {
        // Simple extraction - could be enhanced with more sophisticated parsing
        if (preg_match('/product is ([^.]+)/i', $analysis, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    /**
     * Summarize DALL-E prompt for user feedback
     */
    private function summarize_dalle_prompt($prompt) {
        // Extract key points from the prompt
        $summary = [];
        
        if (stripos($prompt, 'white background') !== false) {
            $summary[] = "Clean white background";
        }
        if (stripos($prompt, 'lifestyle') !== false) {
            $summary[] = "Lifestyle setting";
        }
        if (stripos($prompt, 'professional') !== false) {
            $summary[] = "Professional lighting";
        }
        if (stripos($prompt, 'logo') !== false) {
            $summary[] = "Brand logo included";
        }
        if (stripos($prompt, 'extract') !== false) {
            $summary[] = "Original product extracted";
        }
        
        return implode("\n", $summary);
    }
    
    /**
     * Extract base64 data from data URI or return as-is
     */
    private function extract_base64_data($image_data) {
        if (empty($image_data)) {
            return '';
        }
        
        // If it's a data URI, extract the base64 part
        if (strpos($image_data, 'data:') === 0) {
            $parts = explode(',', $image_data, 2);
            if (count($parts) === 2) {
                return trim($parts[1]);
            }
        }
        
        // Assume it's already base64
        return trim($image_data);
    }
    
    /**
     * Detect MIME type from data URI
     */
    private function detect_mime_type($image_data) {
        if (strpos($image_data, 'data:') === 0) {
            $parts = explode(';', $image_data, 2);
            if (count($parts) >= 2) {
                $mime_type = str_replace('data:', '', $parts[0]);
                $supported_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($mime_type, $supported_types)) {
                    return $mime_type;
                }
            }
        }
        return 'image/jpeg';
    }
    
    /**
     * Detect MIME type from base64 data
     */
    private function detect_mime_type_from_data($base64_data) {
        $decoded = base64_decode(substr($base64_data, 0, 100), true);
        if ($decoded === false) {
            return 'image/jpeg';
        }
        
        $header = substr($decoded, 0, 20);
        
        // Check file signatures
        if (substr($header, 0, 3) === "\xFF\xD8\xFF") {
            return 'image/jpeg';
        } elseif (substr($header, 0, 8) === "\x89PNG\r\n\x1a\n") {
            return 'image/png';
        } elseif (substr($header, 0, 6) === "GIF87a" || substr($header, 0, 6) === "GIF89a") {
            return 'image/gif';
        } elseif (substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP") {
            return 'image/webp';
        }
        
        return 'image/jpeg';
    }
    
    /**
     * Store chat history in database
     */
    private function store_chat_history($session_id, $message_type, $message, $image_data = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_ai_chat_history';
        
        // Create table if it doesn't exist
        $this->maybe_create_chat_table();
        
        $wpdb->insert($table_name, [
            'session_id' => $session_id,
            'user_id' => get_current_user_id(),
            'message_type' => $message_type,
            'message' => $message,
            'image_data' => $image_data,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Create chat history table if needed
     */
    private function maybe_create_chat_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_ai_chat_history';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            message_type varchar(50) NOT NULL,
            message longtext,
            image_data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get chat history for a session
     */
    public function handle_get_chat_history() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_ai_chat_history';
        
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE session_id = %s 
            ORDER BY timestamp ASC
            LIMIT 100",
            $session_id
        ));
        
        wp_send_json_success(['history' => $history]);
    }
    
    /**
     * Save edited image to media library
     */
    public function handle_save_edited_image() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $image_data = $_POST['image_data'];
        $filename = sanitize_file_name($_POST['filename'] ?? 'ai-edited-image');
        
        // Extract base64 data
        $base64_data = $this->extract_base64_data($image_data);
        if (empty($base64_data)) {
            wp_send_json_error(['message' => 'Invalid image data']);
            return;
        }
        
        $data = base64_decode($base64_data);
        if ($data === false) {
            wp_send_json_error(['message' => 'Failed to decode image data']);
            return;
        }
        
        // Determine file extension from MIME type
        $mime_type = $this->detect_mime_type($image_data);
        $extension = 'jpg';
        switch ($mime_type) {
            case 'image/png':
                $extension = 'png';
                break;
            case 'image/gif':
                $extension = 'gif';
                break;
            case 'image/webp':
                $extension = 'webp';
                break;
        }
        
        // Save to uploads directory
        $upload_dir = wp_upload_dir();
        $filename = $filename . '-' . time() . '.' . $extension;
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        if (!file_put_contents($file_path, $data)) {
            wp_send_json_error(['message' => 'Failed to save image file']);
            return;
        }
        
        // Create attachment
        $attachment = [
            'post_mime_type' => $mime_type,
            'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content' => 'AI edited product image - extracted from original',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'Failed to create media attachment']);
            return;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        
        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id)
        ]);
    }
    
    /**
     * Get available models for the current API keys
     */
    public function get_available_models() {
        $available = [];
        
        // Check OpenAI models
        if (!empty($this->openai_api_key)) {
            $available['openai'] = [
                'gpt-4o',
                'gpt-4-turbo',
                'dall-e-3'
            ];
        }
        
        // Check Gemini models
        if (!empty($this->gemini_api_key)) {
            $available['gemini'] = [
                'gemini-2.0-flash-preview',
                'gemini-2.0-flash-exp',
                'gemini-1.5-pro',
                'gemini-1.5-flash'
            ];
        }
        
        return $available;
    }
    
    /**
     * Test API connections
     */
    public function test_connections() {
        $results = [];
        
        // Test OpenAI
        if (!empty($this->openai_api_key)) {
            $test_response = wp_remote_get('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openai_api_key
                ],
                'timeout' => 10
            ]);
            
            $results['openai'] = !is_wp_error($test_response) && 
                                wp_remote_retrieve_response_code($test_response) === 200;
        }
        
        // Test Gemini
        if (!empty($this->gemini_api_key)) {
            $results['gemini'] = $this->test_gemini_connection();
        }
        
        return $results;
    }
    
    /**
     * Test Gemini API connection
     */
    private function test_gemini_connection() {
        if (empty($this->gemini_api_key)) {
            return false;
        }
        
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->gemini_api_key;
        
        $test_request = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Test connection']
                    ]
                ]
            ]
        ];
        
        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($test_request),
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return isset($data['candidates']) && !isset($data['error']);
    }
    
    /**
     * Get templates (placeholder for template functionality)
     */
    public function handle_get_templates() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        // This would fetch from database in full implementation
        $templates = [
            [
                'id' => 1,
                'name' => 'E-commerce White Background',
                'content' => 'EXTRACT the existing product exactly as shown and place it on a pure white background with professional studio lighting and subtle shadow'
            ],
            [
                'id' => 2,
                'name' => 'Lifestyle Setting',
                'content' => 'EXTRACT the product without modifications and place it in a modern, upscale lifestyle environment that matches the product category'
            ],
            [
                'id' => 3,
                'name' => 'Add Company Branding',
                'content' => 'Keep the existing product image unchanged and add our company logo in the bottom right corner, keeping it subtle but visible'
            ]
        ];
        
        wp_send_json_success(['templates' => $templates]);
    }
    
    /**
     * Get single template content
     */
    public function handle_get_single_template() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $template_id = intval($_POST['template_id']);
        
        // Mock data - replace with database query
        $templates = [
            1 => [
                'name' => 'E-commerce White Background',
                'content' => 'EXTRACT the existing product exactly as shown and place it on a pure white background with professional studio lighting and subtle shadow'
            ],
            2 => [
                'name' => 'Lifestyle Setting',
                'content' => 'EXTRACT the product without modifications and place it in a modern, upscale lifestyle environment that matches the product category'
            ],
            3 => [
                'name' => 'Add Company Branding',
                'content' => 'Keep the existing product image unchanged and add our company logo in the bottom right corner, keeping it subtle but visible'
            ]
        ];
        
        if (isset($templates[$template_id])) {
            wp_send_json_success($templates[$template_id]);
        } else {
            wp_send_json_error(['message' => 'Template not found']);
        }
    }
}