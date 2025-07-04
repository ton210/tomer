<?php
/**
 * SSPU_Image_Retriever - Fixed version that works exactly like Python script
 */
class SSPU_Image_Retriever {

    public function __construct() {
        add_action('wp_ajax_sspu_retrieve_alibaba_images', [$this, 'handle_retrieve_images']);
        add_action('wp_ajax_sspu_download_external_image', [$this, 'handle_download_image']);
    }

    public function handle_retrieve_images() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $url = sanitize_url($_POST['alibaba_url']);

        if (empty($url)) {
            wp_send_json_error(['message' => 'No URL provided']);
            return;
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL) ||
            (!strpos($url, 'alibaba.com') && !strpos($url, '1688.com'))) {
            wp_send_json_error(['message' => 'Invalid Alibaba URL']);
            return;
        }

        // Fetch and parse the page
        $images = $this->fetch_alibaba_images($url);

        if ($images && count($images) > 0) {
            wp_send_json_success(['images' => $images]);
        } else {
            wp_send_json_error(['message' => 'No product images found. The page structure may have changed or the URL is invalid.']);
        }
    }

    /**
     * Fetch images from Alibaba page - exactly like Python script
     */
    private function fetch_alibaba_images($url) {
        error_log('[SSPU] Fetching images from: ' . $url);

        // Set up headers to mimic a real browser (like Python script)
        $args = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            error_log('[SSPU] Error fetching page: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('[SSPU] HTTP error: ' . $response_code);
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        if (empty($html)) {
            error_log('[SSPU] Empty response body');
            return false;
        }

        // Parse HTML to find images - exactly like Python script
        $images = $this->parse_alibaba_images($html);

        error_log('[SSPU] Found ' . count($images) . ' images');
        return $images;
    }

    /**
     * Parse Alibaba images - FIXED version
     */
    private function parse_alibaba_images($html) {
        $images = [];

        // Create DOMDocument but suppress warnings for malformed HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        // Load HTML with UTF-8 encoding
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        // Find all img tags
        $img_tags = $dom->getElementsByTagName('img');
        $suffix_to_find = '_720x720q50.jpg';

        error_log('[SSPU] Found ' . $img_tags->length . ' img tags total');

        foreach ($img_tags as $img) {
            // Check both data-src and src attributes (like Python script)
            $image_src = $img->getAttribute('data-src') ?: $img->getAttribute('src');

            if (!empty($image_src) && $this->ends_with($image_src, $suffix_to_find)) {
                error_log('[SSPU] Found matching image: ' . $image_src);

                // Clean the URL by removing the suffix
                // The original URL structure is: filename.jpg_720x720q50.jpg
                // After removing _720x720q50.jpg, we get: filename.jpg (which is what we want)
                $cleaned_url = $this->remove_suffix($image_src, $suffix_to_find);

                // Don't add .jpg back - it's already there after removing the suffix!

                // Fix protocol if missing (like Python script)
                if (substr($cleaned_url, 0, 2) === '//') {
                    $cleaned_url = 'https:' . $cleaned_url;
                }

                error_log('[SSPU] Cleaned URL: ' . $cleaned_url);
                $images[] = $cleaned_url;
            }
        }

        // Remove duplicates and return
        $unique_images = array_unique($images);
        error_log('[SSPU] Final unique images: ' . count($unique_images));

        return array_values($unique_images);
    }

    /**
     * Check if string ends with suffix
     */
    private function ends_with($haystack, $needle) {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Remove suffix from string (like Python's removesuffix)
     */
    private function remove_suffix($string, $suffix) {
        if ($this->ends_with($string, $suffix)) {
            return substr($string, 0, -strlen($suffix));
        }
        return $string;
    }

    /**
     * Handle downloading external images to WordPress Media Library
     */
    public function handle_download_image() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $image_url = esc_url_raw($_POST['image_url']);
        $filename = sanitize_file_name($_POST['filename']);

        if (empty($image_url)) {
            wp_send_json_error(['message' => 'Invalid image URL']);
            return;
        }

        error_log('[SSPU] Downloading image: ' . $image_url);

        // Include required files
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download image to temp location with better headers
        $tmp = download_url($image_url, 300, false); // 5 minute timeout, don't verify SSL

        if (is_wp_error($tmp)) {
            error_log('[SSPU] Download error: ' . $tmp->get_error_message());
            wp_send_json_error(['message' => 'Failed to download image: ' . $tmp->get_error_message()]);
            return;
        }

        // Verify file was downloaded
        if (!file_exists($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            wp_send_json_error(['message' => 'Downloaded file is empty or invalid']);
            return;
        }

        // Get file info
        $file_info = wp_check_filetype($tmp);
        if (!$file_info['ext']) {
            // If no extension found, try to determine from URL or assume jpg
            $url_ext = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (in_array(strtolower($url_ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $file_info['ext'] = strtolower($url_ext);
                $file_info['type'] = 'image/' . ($file_info['ext'] === 'jpg' ? 'jpeg' : $file_info['ext']);
            } else {
                $file_info['ext'] = 'jpg';
                $file_info['type'] = 'image/jpeg';
            }
        }

        $file_array = [
            'name' => $filename . '.' . $file_info['ext'],
            'type' => $file_info['type'],
            'tmp_name' => $tmp,
            'error' => 0,
            'size' => filesize($tmp),
        ];

        // Check for upload errors
        if ($file_array['size'] > wp_max_upload_size()) {
            @unlink($tmp);
            wp_send_json_error(['message' => 'File too large']);
            return;
        }

        // Validate it's actually an image
        $image_info = @getimagesize($tmp);
        if ($image_info === false) {
            @unlink($tmp);
            wp_send_json_error(['message' => 'File is not a valid image']);
            return;
        }

        // Do the validation and storage stuff
        $attachment_id = media_handle_sideload($file_array, 0, null, [
            'post_title' => $filename,
            'post_content' => 'Downloaded from Alibaba product page',
            'post_status' => 'inherit'
        ]);

        // Clean up temp file
        @unlink($tmp);

        if (is_wp_error($attachment_id)) {
            error_log('[SSPU] Sideload error: ' . $attachment_id->get_error_message());
            wp_send_json_error(['message' => 'Failed to create attachment: ' . $attachment_id->get_error_message()]);
            return;
        }

        // Get attachment URLs
        $attachment_url = wp_get_attachment_url($attachment_id);
        $thumb_url = wp_get_attachment_thumb_url($attachment_id);

        // Fallback if no thumbnail
        if (!$thumb_url) {
            $thumb_url = $attachment_url;
        }

        error_log('[SSPU] Successfully created attachment ID: ' . $attachment_id);

        // Log the activity
        if (class_exists('SSPU_Analytics')) {
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'alibaba_image_downloaded', [
                'attachment_id' => $attachment_id,
                'source_url' => $image_url,
                'filename' => $filename,
                'file_size' => $file_array['size']
            ]);
        }

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'url' => $attachment_url,
            'thumb_url' => $thumb_url,
            'filename' => $filename . '.' . $file_info['ext']
        ]);
    }

    /**
     * Scrape variant data (name and image) from an Alibaba product page.
     *
     * @param string $url The URL of the Alibaba product page.
     * @return array|false An array of variant data or false on failure.
     */
    public function scrape_variants_from_url($url) {
        error_log('[SSPU Variant Scraper] Starting variant scrape for URL: ' . $url);

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]);

        if (is_wp_error($response)) {
            error_log('[SSPU Variant Scraper] Failed to fetch URL: ' . $response->get_error_message());
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        if (empty($html)) {
            error_log('[SSPU Variant Scraper] Empty response body from URL.');
            return false;
        }

        if (preg_match('/window\.detailData\s*=\s*(\{.*?\});/s', $html, $matches)) {
            $json_string = $matches[1];
            $detail_data = json_decode($json_string, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('[SSPU Variant Scraper] Failed to decode JSON from detailData.');
                return false;
            }

            if (isset($detail_data['globalData']['product']['sku']['skuAttrs'])) {
                $scraped_variants = [];
                foreach ($detail_data['globalData']['product']['sku']['skuAttrs'] as $attribute) {
                    if (isset($attribute['name'], $attribute['values'])) {
                        foreach ($attribute['values'] as $value) {
                            if (!empty($value['name']) && !empty($value['originImage'])) {
                                $scraped_variants[] = [
                                    'name' => ucwords(strtolower(trim($attribute['name']))),
                                    'value' => ucwords(strtolower(trim($value['name']))),
                                    'image_url' => 'https:' . $value['originImage']
                                ];
                            }
                        }
                    }
                }
                error_log('[SSPU Variant Scraper] Found ' . count($scraped_variants) . ' variants.');
                return $scraped_variants;
            }
        }
        
        error_log('[SSPU Variant Scraper] Could not find window.detailData in the page source.');
        return false;
    }

    /**
     * Fetch MOQ (Minimum Order Quantity) from Alibaba page
     * @param string $url The Alibaba product URL
     * @return array|false Array with MOQ data or false on failure
     */
    public function fetch_alibaba_moq($url) {
        error_log('[SSPU MOQ] Fetching MOQ from: ' . $url);

        // Set up headers to mimic a real browser
        $args = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            error_log('[SSPU MOQ] Error fetching page: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('[SSPU MOQ] HTTP error: ' . $response_code);
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        if (empty($html)) {
            error_log('[SSPU MOQ] Empty response body');
            return false;
        }

        // Parse MOQ from the HTML
        $moq = $this->parse_alibaba_moq($html);
        
        return $moq;
    }

    /**
     * Parse MOQ from Alibaba HTML
     * @param string $html The HTML content
     * @return array|false Array with MOQ data or false if not found
     */
    private function parse_alibaba_moq($html) {
        $moq_data = [
            'moq' => null,
            'unit' => 'pieces'
        ];

        // Try multiple patterns to find MOQ
        
        // Pattern 1: Look for minOrderQuantity in JSON data
        if (preg_match('/"minOrderQuantity":\s*(\d+(?:\.\d+)?)/i', $html, $matches)) {
            $moq_data['moq'] = intval($matches[1]);
            error_log('[SSPU MOQ] Found MOQ via minOrderQuantity: ' . $moq_data['moq']);
        }
        
        // Pattern 2: Look for "moq" in JSON
        if (!$moq_data['moq'] && preg_match('/"moq":\s*(\d+)/i', $html, $matches)) {
            $moq_data['moq'] = intval($matches[1]);
            error_log('[SSPU MOQ] Found MOQ via moq: ' . $moq_data['moq']);
        }
        
        // Pattern 3: Look for formatMinOrderQuantity for unit info
        if (preg_match('/"formatMinOrderQuantity":\s*"(\d+)\s*([^"]+)"/i', $html, $matches)) {
            $moq_data['moq'] = intval($matches[1]);
            $moq_data['unit'] = trim($matches[2]);
            error_log('[SSPU MOQ] Found MOQ via formatMinOrderQuantity: ' . $moq_data['moq'] . ' ' . $moq_data['unit']);
        }
        
        // Pattern 4: Look in window.detailData
        if (!$moq_data['moq'] && preg_match('/window\.detailData\s*=\s*(\{.*?\});/s', $html, $matches)) {
            $json_string = $matches[1];
            $detail_data = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Navigate through the JSON structure
                if (isset($detail_data['globalData']['product']['moq'])) {
                    $moq_data['moq'] = intval($detail_data['globalData']['product']['moq']);
                    error_log('[SSPU MOQ] Found MOQ in detailData: ' . $moq_data['moq']);
                }
            }
        }
        
        // Pattern 5: Look for text patterns in HTML
        if (!$moq_data['moq']) {
            // Look for patterns like "Min. Order: 300 pieces"
            if (preg_match('/Min(?:imum)?\.?\s*Order[:\s]+(\d+)\s*([a-zA-Z]+)/i', $html, $matches)) {
                $moq_data['moq'] = intval($matches[1]);
                $moq_data['unit'] = strtolower(trim($matches[2]));
                error_log('[SSPU MOQ] Found MOQ via text pattern: ' . $moq_data['moq'] . ' ' . $moq_data['unit']);
            }
        }

        return $moq_data['moq'] ? $moq_data : false;
    }

    /**
     * Fetch product description/attributes from Alibaba page
     * @param string $url The Alibaba product URL
     * @return array|false Array with description data or false on failure
     */
    public function fetch_alibaba_description($url) {
        error_log('[SSPU Description] Fetching description from: ' . $url);

        // Set up headers to mimic a real browser
        $args = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            error_log('[SSPU Description] Error fetching page: ' . $response->get_error_message());
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        if (empty($html)) {
            error_log('[SSPU Description] Empty response body');
            return false;
        }

        // Parse description and attributes from the HTML
        $description_data = $this->parse_alibaba_description($html);
        
        return $description_data;
    }

    /**
     * Parse product description and attributes from Alibaba HTML
     * @param string $html The HTML content
     * @return array Array with description data
     */
    private function parse_alibaba_description($html) {
        $description_data = [
            'attributes' => [],
            'description' => '',
            'features' => []
        ];

        // Pattern 1: Look for attributes in JSON format {"attrName":"...","attrValue":"..."}
        if (preg_match_all('/\{"attrName":"([^"]+)","attrValue":"([^"]+)"\}/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attr_name = html_entity_decode($match[1]);
                $attr_value = html_entity_decode($match[2]);
                $description_data['attributes'][$attr_name] = $attr_value;
                error_log('[SSPU Description] Found attribute: ' . $attr_name . ' = ' . $attr_value);
            }
        }

        // Pattern 2: Look in window.detailData for structured data
        if (preg_match('/window\.detailData\s*=\s*(\{.*?\});/s', $html, $matches)) {
            $json_string = $matches[1];
            $detail_data = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Look for product attributes
                if (isset($detail_data['globalData']['product']['productAttribute'])) {
                    foreach ($detail_data['globalData']['product']['productAttribute'] as $attr) {
                        if (isset($attr['attrName']) && isset($attr['attrValue'])) {
                            $description_data['attributes'][$attr['attrName']] = $attr['attrValue'];
                        }
                    }
                }
                
                // Look for product description
                if (isset($detail_data['globalData']['product']['description'])) {
                    $description_data['description'] = strip_tags($detail_data['globalData']['product']['description']);
                }
            }
        }

        // Pattern 3: Look for product specifications in various formats
        // Try to find specification tables or lists
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        // Look for specification tables
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $row) {
                $cells = $row->getElementsByTagName('td');
                if ($cells->length >= 2) {
                    $key = trim($cells->item(0)->textContent);
                    $value = trim($cells->item(1)->textContent);
                    
                    // Check if this looks like a product attribute
                    if (!empty($key) && !empty($value) && strlen($key) < 50) {
                        $description_data['attributes'][$key] = $value;
                    }
                }
            }
        }

        // Look for feature lists
        $lists = $dom->getElementsByTagName('ul');
        foreach ($lists as $list) {
            // Check if this list is likely product features
            $parent = $list->parentNode;
            if ($parent && stripos($parent->textContent, 'feature') !== false) {
                $items = $list->getElementsByTagName('li');
                foreach ($items as $item) {
                    $feature = trim($item->textContent);
                    if (!empty($feature) && strlen($feature) > 10) {
                        $description_data['features'][] = $feature;
                    }
                }
            }
        }

        // Remove duplicate attributes
        $description_data['attributes'] = array_unique($description_data['attributes']);

        return $description_data;
    }

    /**
     * Format attributes as an HTML table
     * @param array $attributes Key-value pairs of attributes
     * @return string HTML table
     */
    public function format_attributes_as_table($attributes) {
        if (empty($attributes)) {
            return '';
        }

        $html = '<table class="alibaba-product-attributes" style="border-collapse: collapse; width: 100%;">';
        $html .= '<thead><tr><th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Attribute</th><th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Value</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($attributes as $name => $value) {
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($name) . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($value) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }
}