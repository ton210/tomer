<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * SSPU_OpenAI
 *
 * Helper class for talking to the OpenAI Chat Completion API
 * and generating product-related content for the Shopify Product Uploader
 * WordPress plugin.
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
     *  Public helpers
     * ------------------------------------------------------------------- */

    /**
     * Return the last recorded error (if any).
     */
    public function get_last_error() {
        return $this->last_error;
    }

    /**
     * Generate a full HTML product description using the fixed template.
     */
    public function generate_product_description( $input_text, $image_ids = [] ) {
        if ( empty( $this->api_key ) ) {
            $this->last_error = 'OpenAI API key is not configured. Please add it in the plugin settings.';
            return false;
        }

        /* ---------- Prompt construction ---------- */
        $messages = [
            [
                'role'    => 'system',
                'content' =>
                    'You are a product description generator for Qstomize.com, a B2B wholesale company specializing in custom branded products and merchandise for businesses. Your descriptions should emphasize bulk ordering, customization options, branding opportunities, and wholesale pricing benefits. Focus on how these products help businesses with their branding, marketing, and promotional needs. Create product descriptions using this EXACT HTML template with embedded CSS. This is a FIXED TEMPLATE - only change the text content inside the elements, NEVER modify the HTML structure or CSS.

<style>
/* Base Styles */
.product-page-wrapper {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 1.5rem;
    background-color: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    font-family: \'Poppins\', sans-serif;
    color: #333;
    line-height: 1.6;
}

/* Typography */
.product-title {
    font-size: 2.8rem;
    text-align: center;
    margin-bottom: 0.5rem;
    font-family: \'Montserrat\', sans-serif;
    color: #2c3e50;
    line-height: 1.2;
}

.product-tagline {
    font-size: 1.3rem;
    color: #666;
    text-align: center;
    margin-bottom: 2rem;
}

h2 {
    font-size: 1.8rem;
    margin-top: 2rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 0.5rem;
    font-family: \'Montserrat\', sans-serif;
    color: #2c3e50;
}

/* Main Content Layout */
.product-main-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2.5rem;
    margin-bottom: 3rem;
}

@media (max-width: 768px) {
    .product-main-content {
        grid-template-columns: 1fr;
    }
}

/* Left Column */
.description-features-column {
    order: 1;
}

.product-features-list {
    list-style: disc;
    margin-left: 1.5rem;
    margin-bottom: 1.8rem;
    line-height: 1.7;
}

.product-features-list li {
    margin-bottom: 0.8rem;
}

.product-features-list strong {
    color: #34495e;
}

.product-long-description {
    line-height: 1.7;
    color: #555;
    margin-bottom: 2rem;
}

/* Right Column */
.product-details-column {
    order: 2;
}

.product-specifications,
.decoration-methods {
    margin-bottom: 2.5rem;
}

.spec-item {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 0.7rem 0;
    border-bottom: 1px dashed #f0f0f0;
}

.spec-item:last-child {
    border-bottom: none;
}

.spec-label {
    font-weight: bold;
    color: #444;
    flex-shrink: 0;
    padding-right: 1rem;
}

.spec-value {
    color: #666;
    text-align: right;
    flex-grow: 1;
}

.spec-value-list {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: right;
    color: #666;
}

.spec-value-list li {
    margin-bottom: 0.5rem;
}
</style>

<div class="product-page-wrapper">
    <div class="product-header">
        <h1 class="product-title">[Product Name - Make it compelling for B2B buyers, like "Custom Logo 40oz Tumbler - Bulk Wholesale for Corporate Branding"]</h1>
        <p class="product-tagline">[B2B focused tagline emphasizing customization, bulk ordering, or branding benefits.]</p>
    </div>

    <div class="product-main-content">
        <div class="description-features-column">
            <h2>Key Features</h2>
            <ul class="product-features-list">
                <li>
                    <strong>[Feature Name]:</strong> [Focus on business benefits like "Custom Logo Placement: Your brand prominently displayed with high-quality printing that withstands daily use"]
                </li>
                <li>
                    <strong>[Feature Name]:</strong> [Detailed feature description with benefits.]
                </li>
                [Add 6-8 detailed features total]
            </ul>
            <p class="product-long-description">
                [Write a compelling paragraph about how this product helps businesses with their branding, marketing, or promotional needs. Mention bulk ordering benefits, customization capabilities, and how it can represent their brand professionally. Make it B2B focused, around 3-4 sentences.]
            </p>
        </div>

        <div class="product-details-column">
            <div class="product-specifications">
                <h2>Product Details</h2>
                <div class="spec-item">
                    <p class="spec-label">SKU:</p>
                    <p class="spec-value">[Generate appropriate SKU]</p>
                </div>
                <div class="spec-item">
                    <p class="spec-label">Material:</p>
                    <p class="spec-value">[List materials]</p>
                </div>
                <div class="spec-item">
                    <p class="spec-label">Capacity:</p>
                    <p class="spec-value">[Include measurements with units]</p>
                </div>
                <div class="spec-item">
                    <p class="spec-label">Dimensions:</p>
                    <p class="spec-value">
                        [Metric measurements] <br>[Imperial measurements]
                    </p>
                </div>
                <div class="spec-item">
                    <p class="spec-label">Product Weight:</p>
                    <p class="spec-value">[Metric weight] <br>[Imperial weight]</p>
                </div>
            </div>

            <div class="decoration-methods">
                <h2>Decoration Method</h2>
                <div class="spec-item">
                    <p class="spec-label">Method:</p>
                    <ul class="spec-value-list">
                        <li>[Decoration method 1]</li>
                        <li>[Decoration method 2]</li>
                        [Add 2-5 appropriate decoration methods]
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

CRITICAL RULES:
1. Include the ENTIRE template above, including the <style> section
2. Replace only the text in square brackets [...] with appropriate content
3. DO NOT modify any HTML tags, CSS classes, or CSS styles
4. DO NOT add any additional styling or elements
5. Use proper HTML entities like &amp; for & symbols
6. Use <br> tags for line breaks within spec-value elements
7. Make content professional and B2B focused for wholesale buyers
8. Keep the exact structure - <style> tag followed by the HTML
9. The output should be ready to paste directly into Shopify\'s product description field
10. Always emphasize: bulk/wholesale pricing, customization options, branding opportunities, minimum order quantities, and business benefits
11. Use professional B2B language suitable for corporate buyers and procurement teams
12. Highlight how products can be customized with company logos, brand colors, and messaging'
            ]
        ];

        // Build user message (text + optional images)
        $user_message = [
            'role'    => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => "Generate a B2B wholesale product description for Qstomize.com for: {$input_text}\n\nRemember this is for businesses looking to order custom branded products in bulk for their employees, clients, or marketing campaigns."
                ],
            ],
        ];

        // Attach images (if any) as base-64 data URIs
        foreach ( $image_ids as $image_id ) {
            $base64 = $this->get_image_as_base64( $image_id );
            if ( $base64 ) {
                $user_message['content'][] = [
                    'type'      => 'image_url',
                    'image_url' => [ 'url' => $base64 ],
                ];
            } else {
                error_log( 'SSPU OpenAI: Failed to convert image ID ' . $image_id . ' to base64' );
            }
        }

        $messages[] = $user_message;

        return $this->make_openai_request( $messages, 2500 );
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
     * Generate an SEO title under 60 chars.
     */
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
     * Generate 5-10 volume discount tiers (JSON array).
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
- Use typical wholesale quantity breaks (e.g., 50, 100, 250, 500, 1000, 2500, 5000)
- Apply intelligent discounts based on product type (typically 5-30% off base price)
- Higher quantities should have better discounts
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
                            $valid[] = [
                                'min_quantity' => (int) $tier['min_quantity'],
                                'price'        => round( (float) $tier['price'], 2 ),
                            ];
                        }
                    }
                    usort( $valid, fn ( $a, $b ) => $a['min_quantity'] <=> $b['min_quantity'] );
                    return $valid;
                }
            }

            $this->last_error = 'Failed to parse tier data from AI response';
            error_log( 'SSPU OpenAI: Failed to parse tiers JSON from: ' . $result );
        }

        return false;
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
            $last       = end( $tiers );
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
     *  Internal helpers
     * ------------------------------------------------------------------- */

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
            'model'       => 'gpt-4o',      // change to gpt-4o-mini if preferred
            'messages'    => $messages,
            'max_tokens'  => $max_tokens,
            'temperature' => 0.7,
        ];

        $response = wp_remote_post(
            $this->api_url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode( $data ),
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            $this->last_error = 'Network error: ' . $response->get_error_message();
            error_log( 'SSPU OpenAI Network Error: ' . $this->last_error );
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );

        // Log first 500 chars for debugging
        error_log( 'SSPU OpenAI Response Code: ' . $code );
        error_log( 'SSPU OpenAI Response Body: ' . substr( $body, 0, 500 ) );

        if ( $code !== 200 ) {
            $this->last_error = isset( $json['error']['message'] )
                ? 'OpenAI API Error: ' . $json['error']['message']
                : 'OpenAI API Error: HTTP ' . $code;
            error_log( 'SSPU OpenAI API Error: ' . $this->last_error );
            return false;
        }

        if ( isset( $json['choices'][0]['message']['content'] ) ) {
            return trim( $json['choices'][0]['message']['content'] );
        }

        $this->last_error = 'Invalid response format from OpenAI';
        error_log( 'SSPU OpenAI Error: Invalid response format' );
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

        $mime       = get_post_mime_type( $image_id );
        $allowed    = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
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