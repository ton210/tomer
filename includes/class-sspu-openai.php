<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SSPU_OpenAI
 *
 * Implements a two-step process for generating product descriptions.
 * 1. Generate a simple, semantic HTML description based on text and image analysis.
 * 2. Reformat the simple HTML into a complex, styled template after Shopify upload.
 */
class SSPU_OpenAI {

	/** @var string */
	private $api_key;

	/** @var string */
	private $api_url = 'https://api.openai.com/v1/chat/completions';

	/** @var string */
	private $last_error = '';

	/**
	 * Constructor – grabs the saved API key.
	 */
	public function __construct() {
		$this->api_key = get_option( 'sspu_openai_api_key' );
	}

	/* ---------------------------------------------------------------------
	 * Public helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Return the last recorded error (if any).
	 */
	public function get_last_error() {
		return $this->last_error;
	}

    /**
     * Downloads an image from a URL, converts it if necessary (AVIF), and returns as Base64 data URI.
     *
     * @param string $url The URL of the image to fetch.
     * @return string|false The Base64 data URI or false on failure.
     */
    private function get_image_from_url_as_base64($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            error_log('SSPU OpenAI: Invalid URL provided for image download: ' . $url);
            return false;
        }

        $response = wp_remote_get($url, ['timeout' => 30]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log('SSPU OpenAI: Failed to download image from URL: ' . $url);
            return false;
        }

        $image_data = wp_remote_retrieve_body($response);
        $mime_type = wp_remote_retrieve_header($response, 'content-type');

        // Handle AVIF conversion
        if ($mime_type === 'image/avif') {
            if (function_exists('imagecreatefromavif')) {
                $image_resource = @imagecreatefromavif('data://image/avif;base64,' . base64_encode($image_data));
                if ($image_resource) {
                    // Convert to PNG in memory
                    ob_start();
                    imagepng($image_resource);
                    $image_data = ob_get_clean();
                    $mime_type = 'image/png';
                    imagedestroy($image_resource);
                } else {
                    error_log('SSPU OpenAI: Failed to create image resource from AVIF data. URL: ' . $url);
                    return false;
                }
            } else {
                error_log('SSPU OpenAI: AVIF format detected, but the server does not have AVIF support in the GD library (PHP 8.1+ required).');
                // We can't process it, so we fail gracefully for this image.
                return false;
            }
        }

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime_type, $allowed_mimes, true)) {
            error_log('SSPU OpenAI: Unsupported image type downloaded from URL: ' . $mime_type);
            return false;
        }

        return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
    }

	/**
	 * STEP 1: Generates a SIMPLE HTML product description using text and image analysis.
	 */
	public function generate_product_description( $input_text, $image_ids = [], $image_urls = [] ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured.';
			return false;
		}

		// Build the multi-part user message, starting with the text prompt.
		$user_content = [
			[
				'type' => 'text',
				'text' => "Analyze the following product information and any provided images to generate a B2B wholesale product description for Qstomize.com. Return ONLY simple HTML as instructed in the system prompt.\n\nProduct Info: {$input_text}"
			]
		];

		// Attach images from WordPress Media Library.
		foreach ( $image_ids as $image_id ) {
			$base64_image = $this->get_image_as_base64( $image_id );
			if ( $base64_image ) {
				$user_content[] = [
					'type'      => 'image_url',
					'image_url' => [ 'url' => $base64_image ],
				];
			} else {
				error_log( 'SSPU OpenAI: Failed to process image ID ' . $image_id . ' for description generation.' );
			}
		}

        // Attach images from URLs.
        foreach ( $image_urls as $url ) {
            if(empty($url)) continue;
            $base64_image = $this->get_image_from_url_as_base64( $url );
            if ( $base64_image ) {
                $user_content[] = [
                    'type'      => 'image_url',
                    'image_url' => [ 'url' => $base64_image ],
                ];
            } else {
                error_log( 'SSPU OpenAI: Failed to process image URL ' . $url . ' for description generation.' );
            }
        }

		$messages = [
			[
				'role'    => 'system',
				'content' => $this->get_simple_html_prompt()
			],
			[
				'role'    => 'user',
				'content' => $user_content
			]
		];

		$result = $this->make_openai_request( $messages, 2000 );
		
		if ($result) {
			return $this->clean_html_response($result);
		}

		return $result;
	}

	/**
	 * STEP 2: Takes simple HTML and other product data, and reformats it with the full styled template.
	 * Updated version with exact HTML structure preservation for Qstomize.com
	 *
	 * @param string $simple_html The basic HTML generated in step 1.
	 * @param array $attributes An array of product attributes (variants, moq, etc.).
	 * @return string|false The final, styled HTML or false on failure.
	 */
	public function reformat_description_with_style($simple_html, $attributes = []) {
		$this->last_error = '';

		if (empty($this->api_key)) {
			$this->last_error = 'OpenAI API key not configured';
			return false;
		}

		// Define the exact HTML template structure
		$html_template = '
<div class="shopify-product-description-container">
    <h1 class="description-title">
        {{PRODUCT_TITLE}}
    </h1>

    <div class="description-content-wrapper">
        <div class="description-main-content">
            <h2 class="description-subtitle">
                {{PRODUCT_SUBTITLE}}
            </h2>
            <ul class="description-features-list">
                {{FEATURES_LIST}}
            </ul>

            <p class="description-paragraph">
                {{MAIN_DESCRIPTION}}
            </p>
        </div>

        <div class="description-details-sidebar">
            <h2 class="description-subtitle">Product Details</h2>
            <div class="details-group">
                {{PRODUCT_DETAILS}}
            </div>

            <h2 class="description-subtitle">Customization Options</h2>
            <div class="details-group">
                {{CUSTOMIZATION_OPTIONS}}
            </div>
        </div>
    </div>
</div>';

		// Build the prompt with explicit template instructions
		$prompt = "You are a professional e-commerce product description writer for Qstomize.com, a leading bulk and wholesale custom accessories supplier. You must reformat the following product description using the EXACT HTML structure provided.\n\n";

		$prompt .= "COMPANY CONTEXT:\n";
		$prompt .= "- Qstomize.com specializes in bulk orders of custom promotional accessories\n";
		$prompt .= "- Target audience: Businesses, organizations, event planners, and marketing agencies\n";
		$prompt .= "- Focus on wholesale quantities with customization options\n";
		$prompt .= "- Emphasize bulk pricing advantages and branding opportunities\n\n";

		$prompt .= "CRITICAL INSTRUCTIONS:\n";
		$prompt .= "1. You MUST use the exact HTML structure and class names provided in the template\n";
		$prompt .= "2. Do NOT add or remove any HTML tags or classes\n";
		$prompt .= "3. Only replace the placeholder content with relevant product information\n";
		$prompt .= "4. Maintain the exact div structure and hierarchy\n";
		$prompt .= "5. Write from the perspective of a bulk/wholesale supplier\n";
		$prompt .= "6. Emphasize customization, branding, and bulk order benefits\n\n";

		$prompt .= "Product Information:\n";
		$prompt .= "- Product Name: " . ($attributes['product_name'] ?? 'Product') . "\n";

		if (!empty($attributes['moq'])) {
			$prompt .= "- Minimum Order Quantity: " . $attributes['moq'] . " units\n";
		}

		if (!empty($attributes['print_methods']) && is_array($attributes['print_methods'])) {
			$methods = array_map(function($method) {
				return ucwords(str_replace('_', ' ', $method));
			}, $attributes['print_methods']);
			$prompt .= "- Available Print Methods: " . implode(', ', $methods) . "\n";
		}

		if (!empty($attributes['variants']) && is_array($attributes['variants'])) {
			$prompt .= "- Available Variants: " . count($attributes['variants']) . " options\n";
			// Include variant details if available
			foreach ($attributes['variants'] as $variant) {
				if (isset($variant['option1'])) {
					$prompt .= "  - " . $variant['option1'] . "\n";
				}
			}
		}

		// Add any additional product details
		if (!empty($attributes['material'])) {
			$prompt .= "- Material: " . $attributes['material'] . "\n";
		}
		if (!empty($attributes['dimensions'])) {
			$prompt .= "- Dimensions: " . $attributes['dimensions'] . "\n";
		}
		if (!empty($attributes['product_type'])) {
			$prompt .= "- Product Type: " . $attributes['product_type'] . "\n";
		}
		if (!empty($attributes['weight'])) {
			$prompt .= "- Weight: " . $attributes['weight'] . " lbs\n";
		}

		$prompt .= "\nOriginal Description:\n" . strip_tags($simple_html) . "\n\n";

		$prompt .= "HTML TEMPLATE TO USE (YOU MUST USE THIS EXACT STRUCTURE):\n" . $html_template . "\n\n";

		$prompt .= "Replace the following placeholders with appropriate content:\n";
		$prompt .= "- {{PRODUCT_TITLE}}: The product name/title with focus on customization aspect\n";
		$prompt .= "- {{PRODUCT_SUBTITLE}}: A compelling subtitle emphasizing bulk/wholesale benefits\n";
		$prompt .= "- {{FEATURES_LIST}}: Generate 5-7 feature items focusing on:\n";
		$prompt .= "  * Customization capabilities\n";
		$prompt .= "  * Bulk order advantages\n";
		$prompt .= "  * Quality for promotional use\n";
		$prompt .= "  * Branding opportunities\n";
		$prompt .= "  Format: <li><strong>Feature Name:</strong> Feature description</li>\n";
		$prompt .= "- {{MAIN_DESCRIPTION}}: A compelling paragraph that:\n";
		$prompt .= "  * Positions product as ideal for bulk orders\n";
		$prompt .= "  * Highlights customization options\n";
		$prompt .= "  * Appeals to businesses and organizations\n";
		$prompt .= "  * Mentions promotional/marketing use cases\n";
		$prompt .= "- {{PRODUCT_DETAILS}}: Product specifications, each wrapped in:\n";
		$prompt .= '  <div><p class="detail-heading">Detail Name:</p><p class="detail-text">Detail Value</p></div>' . "\n";
		$prompt .= "- {{CUSTOMIZATION_OPTIONS}}: Customization details emphasizing bulk printing capabilities\n\n";

		$prompt .= "IMPORTANT:\n";
		$prompt .= "- Always mention 'Qstomize.com' naturally in the description\n";
		$prompt .= "- Emphasize bulk pricing advantages\n";
		$prompt .= "- Use language that appeals to B2B customers\n";
		$prompt .= "- Highlight ROI of custom promotional products\n";
		$prompt .= "- Include MOQ prominently in Product Details\n";
		$prompt .= "- Position products as perfect for corporate gifts, events, marketing campaigns\n";
		$prompt .= "- Include print methods in Customization Options section\n";
		$prompt .= "- Maintain professional B2B tone while being engaging\n";
		$prompt .= "- Use the exact class names and structure shown in the template\n\n";

		$prompt .= "Return ONLY the complete HTML starting with <div class=\"shopify-product-description-container\"> and ending with the closing </div>. No explanations.";

		// Make the API call
		$response = wp_remote_post($this->api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json',
			],
			'body' => json_encode([
				'model' => 'gpt-4o',
				'messages' => [
					['role' => 'system', 'content' => 'You are an expert B2B e-commerce copywriter for Qstomize.com, specializing in bulk custom promotional products. You follow HTML templates exactly while creating compelling descriptions for wholesale buyers.'],
					['role' => 'user', 'content' => $prompt]
				],
				'temperature' => 0.7,
				'max_tokens' => 2000
			]),
			'timeout' => 60
		]);

		if (is_wp_error($response)) {
			$this->last_error = 'API request failed: ' . $response->get_error_message();
			error_log('SSPU OpenAI Error: ' . $this->last_error);
			return false;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['choices'][0]['message']['content'])) {
			// Clean up the response
			$formatted_description = trim($body['choices'][0]['message']['content']);

			// Validate that the response contains the required structure
			if (strpos($formatted_description, 'class="shopify-product-description-container"') === false) {
				$this->last_error = 'Generated description does not match required HTML structure';
				error_log('SSPU Format Error: ' . $this->last_error);
				return false;
			}

			// Ensure it's valid HTML while preserving the structure
			$formatted_description = wp_kses($formatted_description, [
				'div' => ['class' => []],
				'h1' => ['class' => []],
				'h2' => ['class' => []],
				'p' => ['class' => []],
				'ul' => ['class' => []],
				'li' => [],
				'strong' => [],
				'br' => []
			]);

			return $formatted_description;
		}

		if (isset($body['error'])) {
			$this->last_error = 'OpenAI API error: ' . $body['error']['message'];
			error_log('SSPU OpenAI API Error: ' . $this->last_error);
		}

		return false;
	}

	/**
	 * Helper method to extract product attributes from various sources
	 * Call this before reformat_description_with_style to gather all attributes
	 *
	 * @param WC_Product $product The WooCommerce product object
	 * @return array Formatted attributes array
	 */
	public function extract_product_attributes($product) {
		$attributes = [
			'product_name' => $product->get_name(),
			'product_type' => $product->get_type(),
			'moq' => get_post_meta($product->get_id(), '_moq', true) ?: '100',
			'print_methods' => [],
			'variants' => [],
			'material' => '',
			'dimensions' => '',
			'weight' => $product->get_weight() ?: ''
		];

		// Extract print methods from product attributes
		$product_attributes = $product->get_attributes();
		foreach ($product_attributes as $attribute) {
			if ($attribute->get_name() === 'pa_print_method' || $attribute->get_name() === 'print_method') {
				$attributes['print_methods'] = $attribute->get_options();
			}
			if ($attribute->get_name() === 'pa_material' || $attribute->get_name() === 'material') {
				$attributes['material'] = implode(', ', $attribute->get_options());
			}
		}

		// Get variants for variable products
		if ($product->is_type('variable')) {
			$variations = $product->get_available_variations();
			foreach ($variations as $variation) {
				$attributes['variants'][] = $variation['attributes'];
			}
		}

		// Extract dimensions if available
		if ($product->has_dimensions()) {
			$dimensions = [];
			if ($product->get_length()) $dimensions[] = 'Length: ' . $product->get_length() . get_option('woocommerce_dimension_unit');
			if ($product->get_width()) $dimensions[] = 'Width: ' . $product->get_width() . get_option('woocommerce_dimension_unit');
			if ($product->get_height()) $dimensions[] = 'Height: ' . $product->get_height() . get_option('woocommerce_dimension_unit');
			$attributes['dimensions'] = implode(', ', $dimensions);
		}

		return $attributes;
	}

	/**
	 * Generate relevant product tags (comma-separated list).
	 */
	public function generate_tags( $input_text ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'Generate relevant product tags for Qstomize.com, a B2B wholesale custom products company. Return only a comma-separated list of 8-12 tags that would help businesses find this product. Focus on: product type, customization options, industry use, corporate gifts, promotional items, branding, bulk orders, material, and business applications. Keep tags focused on B2B wholesale and custom branding.',
			],
			[
				'role'    => 'user',
				'content' => "Generate B2B wholesale tags for this custom product: {$input_text}",
			],
		];

		return $this->make_openai_request( $messages, 200 );
	}

	/**
	 * Estimate product weight using AI based on product name and image.
	 */
	public function estimate_product_weight( $product_name, $image_id ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'You are a product weight estimation expert for Qstomize.com, a wholesale custom products company. Analyze the product image and name to estimate the weight in pounds (lbs). Consider typical materials, construction, and size based on the product type. For example: a ceramic mug might be 0.8-1.2 lbs, a t-shirt 0.3-0.5 lbs, a metal water bottle 0.5-1.5 lbs, a laptop bag 1-3 lbs. Be realistic and consider that these are promotional/custom items. Return ONLY a numeric weight in pounds like 1.5 or 0.75, nothing else.',
			],
		];

		$user_message = [
			'role'    => 'user',
			'content' => [
				[
					'type' => 'text',
					'text' => "Estimate the weight in pounds for this product: \"{$product_name}\". Consider typical materials and construction for promotional/custom products.",
				],
			],
		];

		// Attach image if available
		$base64 = $this->get_image_as_base64( $image_id );
		if ( $base64 ) {
			$user_message['content'][] = [
				'type'      => 'image_url',
				'image_url' => [ 'url' => $base64 ],
			];
		}

		$messages[] = $user_message;

		$result = $this->make_openai_request( $messages, 50 );

		if ( $result && preg_match( '/(\d+\.?\d*)/', $result, $m ) ) {
			return round( (float) $m[1], 2 );
		}

		return false;
	}

	/**
	 * Generate an SEO-optimized title under 60 chars.
	 */
	public function generate_seo_title( $product_name, $description ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'Create an SEO-optimized title for Qstomize.com wholesale products. Must be under 60 characters. Include keywords like: custom, bulk, wholesale, branded, promotional, or corporate. Make it compelling for B2B buyers searching for custom branded products. IMPORTANT: Return ONLY the title text without any quotation marks, quotes, or punctuation wrapping.',
			],
			[
				'role'    => 'user',
				'content' => "Product: {$product_name}\nDescription: " . substr( $description, 0, 500 ) . "\n\nReturn only the SEO title without quotation marks.",
			],
		];

		$result = $this->make_openai_request( $messages, 100 );

		if ( $result ) {
			// remove any stray quotes / back-ticks
			$result = trim( $result, "\"'`" );
			$result = str_replace( [ '"', "'", '`' ], '', $result );
		}

		return $result;
	}

	/**
	 * Generate a meta description under 160 chars.
	 */
	public function generate_meta_description( $product_name, $description ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'Create a meta description for Qstomize.com wholesale products. Must be under 160 characters. Include: bulk/wholesale ordering, custom branding capabilities, and a B2B call to action. Target corporate buyers and procurement teams. IMPORTANT: Return ONLY the description text without any quotation marks, quotes, or punctuation wrapping.',
			],
			[
				'role'    => 'user',
				'content' => "Product: {$product_name}\nDescription: " . substr( $description, 0, 500 ) . "\n\nReturn only the meta description without quotation marks.",
			],
		];

		$result = $this->make_openai_request( $messages, 200 );

		if ( $result ) {
			$result = trim( $result, "\"'`" );
			$result = str_replace( [ '"', "'", '`' ], '', $result );
		}

		return $result;
	}

	/**
	 * Suggest a competitive wholesale price from name/desc/variant.
	 */
	public function suggest_price( $product_name, $description, $variant_info = '' ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'You are a wholesale pricing expert for Qstomize.com, specializing in custom branded products for businesses. Analyze the product and suggest a competitive WHOLESALE price per unit in USD, assuming bulk orders with custom branding. Consider: materials, customization complexity, typical minimum order quantities (25-5000 units), and B2B market standards. Return only a numeric price (e.g., 12.99) without currency symbol. This should be the starting price for the minimum order quantity.',
			],
			[
				'role'    => 'user',
				'content' => "Product: {$product_name}\nDescription: {$description}\nVariant: {$variant_info}\n\nSuggest a competitive wholesale price per unit for bulk orders with custom branding.",
			],
		];

		$result = $this->make_openai_request( $messages, 50 );

		if ( $result && preg_match( '/(\d+\.?\d*)/', $result, $m ) ) {
			return (float) $m[1];
		}

		return false;
	}

	/**
	 * Suggest wholesale price using only the description.
	 */
	public function suggest_price_from_description( $description ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'Analyze this product description and suggest a competitive wholesale price per unit in USD for Qstomize.com custom branded products. Consider materials, features, complexity, and bulk ordering. Return only a numeric price (e.g., 24.99).',
			],
			[
				'role'    => 'user',
				'content' => $description,
			],
		];

		$result = $this->make_openai_request( $messages, 50 );

		if ( $result && preg_match( '/(\d+\.?\d*)/', $result, $m ) ) {
			return (float) $m[1];
		}

		return false;
	}

	/**
	 * Shorten & polish product names for B2B buyers.
	 */
	public function format_product_name( $long_product_name ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'You are a product name formatter for Qstomize.com\'s B2B wholesale platform. Format product names to be clear, professional, and appealing to business buyers looking for custom branded products.

Rules:
1. Keep the most important product features
2. Include "Custom" or "Branded" when appropriate
3. Make it suitable for B2B buyers (professional, clear, searchable)
4. Remove unnecessary adjectives and filler words
5. Maintain the core product identity
6. Use title case (capitalize each main word)
7. Target 4-7 words maximum
8. Focus on what procurement teams and marketing managers would search for

Examples:
"Custom Logo 40oz Stainless Steel Insulated Car Cup Portable Double Travel Coffee Mug Layer Large Capacity Water Cup with Handle" → "Custom Logo 40oz Insulated Tumbler"
"Premium Quality Soft Cotton Comfortable Long Sleeve T-Shirt with Custom Embroidery Logo" → "Custom Embroidered Long Sleeve Shirt"
"Professional Business Card Holder Leather Executive Desktop Organizer with Multiple Compartments" → "Executive Leather Business Card Holder"

Return ONLY the formatted product name, nothing else.',
			],
			[
				'role'    => 'user',
				'content' => "Format this product name for B2B wholesale: {$long_product_name}",
			],
		];

		return $this->make_openai_request( $messages, 50 );
	}

	/**
	 * Suggest a base price using an image and min-quantity.
	 */
	public function suggest_base_price_with_image( $product_name, $image_id, $min_quantity ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'You are an ecommerce pricing expert for Qstomize, a wholesale custom products and promotional items store. Your job is to suggest competitive wholesale pricing for customized products. Consider factors like material quality, customization complexity, market standards, and bulk wholesale pricing. Always return ONLY a number like 10.50 or 24.99 with no currency symbol or other text.',
			],
		];

		$user_message = [
			'role'    => 'user',
			'content' => [
				[
					'type' => 'text',
					'text' => "Suggest the initial starting price in USD for this customized promotional product: \"{$product_name}\", starting at quantity {$min_quantity} units. This is for wholesale/bulk orders of customized items. Just return a number like 10.50",
				],
			],
		];

		$base64 = $this->get_image_as_base64( $image_id );
		if ( $base64 ) {
			$user_message['content'][] = [
				'type'      => 'image_url',
				'image_url' => [ 'url' => $base64 ],
			];
		} else {
			error_log( 'SSPU OpenAI: Could not get image for pricing suggestion' );
		}

		$messages[] = $user_message;

		$result = $this->make_openai_request( $messages, 50 );

		if ( $result && preg_match( '/(\d+\.?\d*)/', $result, $m ) ) {
			return (float) $m[1];
		}

		return false;
	}

	/**
	 * Generate 5-10 volume discount tiers starting AFTER the minimum quantity.
	 */
	public function suggest_volume_tiers( $product_name, $base_price, $min_quantity ) {
		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			return false;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' =>
					'You are an ecommerce pricing expert for Qstomize, a wholesale custom products store. Generate intelligent volume pricing tiers for promotional products. Create 5-10 tiers with progressively better discounts for larger quantities. Consider typical wholesale quantity breaks and discount percentages. Return ONLY valid JSON array with objects containing min_quantity and price fields.',
			],
			[
				'role'    => 'user',
				'content' => "Generate 5-10 volume pricing tiers for the product \"{$product_name}\" with base price \${$base_price} at {$min_quantity} units.

Requirements:
- Start with the first tier AFTER the base quantity (don't include {$min_quantity})
- Use intelligent quantity breaks starting from {$min_quantity} (e.g., if min is 25, use 50, 100, 250, 500, 1000, 2500, 5000)
- If min is 100, use 250, 500, 1000, 2500, 5000, 10000
- If min is 10, use 25, 50, 100, 250, 500, 1000, 2500
- Apply intelligent discounts based on product type (typically 5-30% off base price)
- Higher quantities should have progressively better discounts
- Prices should be rounded to 2 decimal places
- Return as JSON array like: [{\"min_quantity\": 50, \"price\": 9.25}, {\"min_quantity\": 100, \"price\": 8.50}]

Return ONLY the JSON array, no other text.",
			],
		];

		$result = $this->make_openai_request( $messages, 500 );

		if ( $result ) {
			// Attempt to extract JSON block
			if ( preg_match( '/\[[\s\S]*\]/', $result, $m ) ) {
				$tiers = json_decode( $m[0], true );

				if ( is_array( $tiers ) ) {
					$valid = [];
					foreach ( $tiers as $tier ) {
						if ( isset( $tier['min_quantity'], $tier['price'] ) ) {
							$tierQty = (int) $tier['min_quantity'];
							$tierPrice = round( (float) $tier['price'], 2 );

							// Ensure tier quantity is greater than minimum
							if ( $tierQty > $min_quantity ) {
								$valid[] = [
									'min_quantity' => $tierQty,
									'price'        => $tierPrice,
								];
							}
						}
					}

					// Sort by quantity ascending
					usort( $valid, fn ( $a, $b ) => $a['min_quantity'] <=> $b['min_quantity'] );

					// If we don't have enough tiers or they don't start properly, generate fallback tiers
					if ( count($valid) < 3 ) {
						error_log("SSPU: Generated insufficient tiers, creating fallback tiers for min_quantity: {$min_quantity}");
						return $this->generate_fallback_tiers( $base_price, $min_quantity );
					}

					return $valid;
				}
			}

			$this->last_error = 'Failed to parse tier data from AI response';
			error_log( 'SSPU OpenAI: Failed to parse tiers JSON from: ' . $result );
		}

		// If AI fails, generate fallback tiers
		return $this->generate_fallback_tiers( $base_price, $min_quantity );
	}

	/**
	 * Generate fallback volume tiers when AI fails
	 */
	private function generate_fallback_tiers( $base_price, $min_quantity ) {
		$multipliers = get_option('sspu_volume_tier_multipliers', '0.95,0.90,0.85,0.80,0.75');
		$multipliers = array_map('floatval', explode(',', $multipliers));

		// Generate intelligent quantity breaks based on minimum
		$quantity_breaks = [];

		if ( $min_quantity <= 25 ) {
			$quantity_breaks = [50, 100, 250, 500, 1000, 2500];
		} elseif ( $min_quantity <= 50 ) {
			$quantity_breaks = [100, 250, 500, 1000, 2500, 5000];
		} elseif ( $min_quantity <= 100 ) {
			$quantity_breaks = [250, 500, 1000, 2500, 5000, 10000];
		} else {
			// For higher minimums, use multiples
			$base = ceil( $min_quantity / 100 ) * 100; // Round up to nearest 100
			$quantity_breaks = [
				$base * 2,
				$base * 5,
				$base * 10,
				$base * 25,
				$base * 50,
				$base * 100
			];
		}

		$tiers = [];
		foreach ( $multipliers as $index => $multiplier ) {
			if ( isset( $quantity_breaks[$index] ) ) {
				$qty = $quantity_breaks[$index];
				// Only add if quantity is greater than minimum
				if ( $qty > $min_quantity ) {
					$tiers[] = [
						'min_quantity' => $qty,
						'price'        => round( $base_price * $multiplier, 2 ),
					];
				}
			}
		}

		error_log("SSPU: Generated fallback tiers for min_quantity {$min_quantity}: " . json_encode($tiers));
		return $tiers;
	}

	/**
	 * Provide a short explanation of the pricing logic.
	 */
	public function get_pricing_rationale( $product_name, $base_price, $tiers ) {
		if ( empty( $this->api_key ) ) {
			return 'Competitive wholesale pricing with volume discounts for bulk orders.';
		}

		$max_discount = 0;
		if ( $tiers ) {
			$last         = end( $tiers );
			$max_discount = round( ( 1 - ( $last['price'] / $base_price ) ) * 100 );
		}

		$messages = [
			[
				'role'    => 'system',
				'content' => 'You are a pricing expert. Provide a brief explanation of the pricing strategy in 1-2 sentences.',
			],
			[
				'role'    => 'user',
				'content' => "Explain why \${$base_price} is a good starting price for \"{$product_name}\" as a customized promotional product, with volume discounts up to {$max_discount}% for large orders.",
			],
		];

		$result = $this->make_openai_request( $messages, 150 );

		return $result ?: 'Competitive wholesale pricing with volume discounts for bulk orders.';
	}

	/**
	 * Quick health-check – returns TRUE if the API is reachable.
	 */
	public function test_api_connection() {
		$messages = [
			[ 'role' => 'system', 'content' => 'You are a helpful assistant.' ],
			[ 'role' => 'user',   'content' => 'Say "Hello, the API is working!"' ],
		];

		return $this->make_openai_request( $messages, 50 ) !== false;
	}

	/* ---------------------------------------------------------------------
	 * Internal helpers
	 * ------------------------------------------------------------------- */
	
	/**
	 * Returns the system prompt for generating simple, semantic HTML.
	 */
	private function get_simple_html_prompt() {
		return 'You are an expert B2B copywriter for Qstomize.com. Generate a product description using ONLY simple, semantic HTML (h2, h3, p, ul, li).

Your entire response must be valid HTML.

Structure:
- Start with an <h2> for the main product title.
- Follow with a <p> paragraph summarizing the product.
- Create an <h3> titled "Key Features".
- List 4-6 key features in a <ul>.
- Create an <h3> titled "Perfect For".
- List 3-5 target use cases in a <ul>.

EXAMPLE:
<h2>Custom Logo Insulated Tumbler</h2>
<p>Keep your brand top-of-mind with this premium insulated tumbler. Perfect for client gifts or employee appreciation, it features durable stainless steel construction and offers a large imprint area for your logo. Order in bulk for your next event.</p>
<h3>Key Features</h3>
<ul>
<li>20oz double-wall stainless steel construction</li>
<li>Keeps drinks hot for 8 hours or cold for 16 hours</li>
<li>Spill-resistant slide-action lid</li>
<li>Large, high-visibility branding area</li>
<li>BPA-free and non-toxic materials</li>
</ul>
<h3>Perfect For</h3>
<ul>
<li>Corporate Giveaways & Events</li>
<li>New Hire Welcome Kits</li>
<li>Client Appreciation Gifts</li>
<li>Trade Show Promotions</li>
</ul>';
	}

	/**
	 * Returns the system prompt for reformatting simple HTML into the final, styled template.
	 */
	private function get_full_styled_html_prompt() {
		return <<<PROMPT
You are an expert B2B copywriter and HTML/CSS designer for Qstomize.com. 
Your task is to take the provided product data (simple HTML and a list of attributes) and perfectly reformat it into the provided complex, styled HTML template.

**CRITICAL RULES:**
1.  **USE THE TEMPLATE**: You MUST use the exact HTML structure and class names provided in the template below.
2.  **MAP THE CONTENT**:
    * Take the main heading from the simple HTML and place it inside the `<h1 class="description-title">`.
    * Take the main paragraph from the simple HTML and place it inside the `<p class="description-paragraph">`.
    * Take the list items from the "Key Features" section of the simple HTML and place them as `<li>` elements inside the `<ul class="description-features-list">`.
    * Use the provided key-value product attributes to populate the `<div class="description-details-sidebar">`. For each attribute, create a `<div>` containing a `<p class="detail-heading">` and a `<p class="detail-text">` or `<ul class="detail-list">`.
3.  **NO EXTRA TEXT**: Your response must ONLY be the final, complete HTML block, starting with `<div class="shopify-product-description-container">` and ending with the last `</div>`. Do not add any explanations or markdown formatting like ```html.

**HERE IS THE TEMPLATE TO POPULATE:**
<div class="shopify-product-description-container">
    <h1 class="description-title">
        </h1>
    <div class="description-content-wrapper">
        <div class="description-main-content">
            <h2 class="description-subtitle">
                </h2>
            <ul class="description-features-list">
                </ul>
            <p class="description-paragraph">
                </p>
        </div>
        <div class="description-details-sidebar">
            <h2 class="description-subtitle">Product Details</h2>
            <div class="details-group">
                </div>
            <h2 class="description-subtitle">Customization Options</h2>
            <div class="details-group">
                </div>
        </div>
    </div>
</div>
PROMPT;
	}

	/**
	 * Clean up the OpenAI response to ensure proper HTML format
	 */
	private function clean_html_response($response) {
		if (!$response) {
			return $response;
		}

		// Remove markdown code block wrappers
		$response = preg_replace('/^```html\s*/i', '', $response);
		$response = preg_replace('/```\s*$/i', '', $response);
		$response = preg_replace('/^```\s*/i', '', $response);

		// Remove any <p> tags around the entire response
		$response = preg_replace('/^<p>\s*/i', '', $response);
		$response = preg_replace('/\s*<\/p>$/i', '', $response);

		// Ensure style tag is properly formatted if CSS is present but no style tags
		if (strpos($response, '<style>') === false && strpos($response, 'product-page-wrapper') !== false) {
			// Look for CSS at the beginning and wrap it in style tags
			$response = preg_replace('/^(.+?)(\s*<div class="product-page-wrapper">)/s', '<style>$1</style>$2', $response);
		}

		// Fix any malformed spec-items that are missing labels
		$response = preg_replace('/(<div class="spec-item"><p class="spec-value">)([^<]+)(<\/p><\/div>)/', '<div class="spec-item"><p class="spec-label">Item:</p><p class="spec-value">$2</p></div>', $response);

		// Clean up any extra whitespace
		$response = trim($response);

		return $response;
	}

	/**
	 * Make the actual HTTP request to OpenAI.
	 */
	private function make_openai_request( $messages, $max_tokens = 500 ) {
		$this->last_error = '';

		if ( empty( $this->api_key ) ) {
			$this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
			error_log( 'SSPU OpenAI Error: No API key configured' );
			return false;
		}

		$data = [
			'model'       => 'gpt-4o',
			'messages'    => $messages,
			'max_tokens'  => $max_tokens,
			'temperature' => 0.7,
		];

		error_log( '[SSPU OpenAI] Preparing to send request to OpenAI.' );
		error_log( '[SSPU OpenAI] Request Payload: ' . json_encode($data) );

		$response = wp_remote_post(
			$this->api_url,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => json_encode( $data ),
				'timeout' => 60, // Set timeout to 60 seconds
			]
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->last_error = 'Network error during request to OpenAI: ' . $error_message;
			error_log( '[SSPU OpenAI] WP_Error during API call: ' . $error_message );

			if (strpos(strtolower($error_message), 'timed out') !== false || strpos(strtolower($error_message), 'timeout') !== false) {
				$this->last_error = 'The request to the OpenAI API timed out after 60 seconds. This can happen during peak times or with complex requests involving many images. Please try again in a few moments.';
				error_log('[SSPU OpenAI] API call officially timed out.');
			}
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		error_log( '[SSPU OpenAI] API Response Code: ' . $code );
		error_log( '[SSPU OpenAI] API Response Body (truncated): ' . substr( $body, 0, 500 ) );

		if ( $code !== 200 ) {
			$api_error_message = isset( $json['error']['message'] )
				? 'OpenAI API Error: ' . $json['error']['message']
				: 'OpenAI API returned a non-200 HTTP status code: ' . $code;
			$this->last_error = $api_error_message;
			error_log( '[SSPU OpenAI] API Error Response: ' . $this->last_error );
			return false;
		}

		if ( isset( $json['choices'][0]['message']['content'] ) ) {
			error_log( '[SSPU OpenAI] Successfully extracted content from API response.' );
			return trim( $json['choices'][0]['message']['content'] );
		}

		$this->last_error = 'Invalid response format from OpenAI. Could not find content in the response.';
		error_log( '[SSPU OpenAI] Error: Invalid response format. Full Body: ' . $body );
		return false;
	}

	/**
	 * Convert an attachment to base-64 data URI (JPEG / PNG / GIF / WEBP).
	 */
	private function get_image_as_base64( $image_id ) {
		$file_path = get_attached_file( $image_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			error_log( 'SSPU OpenAI: Image file not found for ID ' . $image_id );
			return false;
		}

		$mime     = get_post_mime_type( $image_id );
		$allowed  = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
		if ( ! in_array( $mime, $allowed, true ) ) {
			error_log( 'SSPU OpenAI: Unsupported image type ' . $mime );
			return false;
		}

		$data = file_get_contents( $file_path );
		if ( $data === false ) {
			error_log( 'SSPU OpenAI: Failed to read image file' );
			return false;
		}

		// Resize if >20 MB
		if ( strlen( $data ) > 20 * 1024 * 1024 ) {
			$resized = $this->resize_image_if_needed( $file_path, $mime );
			if ( $resized ) {
				$data = $resized;
			} else {
				error_log( 'SSPU OpenAI: Image too large and could not be resized' );
				return false;
			}
		}

		return 'data:' . $mime . ';base64,' . base64_encode( $data );
	}

	/**
	 * Resize an image so that the longest side ≤2048 px.
	 */
	private function resize_image_if_needed( $file_path, $mime ) {
		$editor = wp_get_image_editor( $file_path );
		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$size   = $editor->get_size();
		$w      = $size['width'];
		$h      = $size['height'];
		$max    = 2048;

		if ( $w > $max || $h > $max ) {
			if ( $w >= $h ) {
				$new_w = $max;
				$new_h = (int) ( $h * ( $max / $w ) );
			} else {
				$new_h = $max;
				$new_w = (int) ( $w * ( $max / $h ) );
			}
			$editor->resize( $new_w, $new_h, false );
		}

		$editor->set_quality( 85 );
		$tmp = wp_tempnam();
		$save = $editor->save( $tmp );
		if ( is_wp_error( $save ) ) {
			return false;
		}

		$data = file_get_contents( $tmp );
		@unlink( $tmp );

		return $data;
	}
}