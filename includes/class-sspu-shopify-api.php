<?php
/**
 * SSPU Shopify API Class
 *
 * Handles all communication with the Shopify API.
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Shopify_API {

    /**
     * The Shopify store name.
     * @var string
     */
    private $store_name;

    /**
     * The Shopify access token.
     * @var string
     */
    private $access_token;

    /**
     * The Shopify GraphQL API endpoint.
     * @var string
     */
    private $graphql_endpoint;

    /**
     * API version
     * @var string
     */
    const API_VERSION = '2024-04';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->store_name = get_option('sspu_shopify_store_name');
        $this->access_token = get_option('sspu_shopify_access_token');
        $this->graphql_endpoint = sprintf("https://%s.myshopify.com/admin/api/%s/graphql.json", $this->store_name, self::API_VERSION);
    }

    /**
     * Get the configured store name.
     * @return string
     */
    public function get_store_name() {
        return $this->store_name;
    }

    /**
     * Sends a request to the Shopify REST API.
     *
     * @param string $endpoint The API endpoint to request.
     * @param string $method   The HTTP method (GET, POST, etc.).
     * @param array  $payload  The data to send with the request.
     * @return array The decoded JSON response from the API.
     */
    public function send_request(string $endpoint, string $method = 'GET', array $payload = []): array {
        if (empty($this->store_name) || empty($this->access_token)) {
            error_log('SSPU: Shopify credentials not configured');
            return ['errors' => 'API credentials are not set in the settings.'];
        }

        $url = sprintf(
            "https://%s.myshopify.com/admin/api/%s/%s",
            $this->store_name,
            self::API_VERSION,
            $endpoint
        );

        $args = [
            'method'  => $method,
            'headers' => [
                'Content-Type'           => 'application/json',
                'X-Shopify-Access-Token' => $this->access_token,
            ],
            'body'    => ($method !== 'GET' && !empty($payload)) ? json_encode($payload) : null,
            'timeout' => 60,
        ];

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('SSPU: Shopify request error - ' . $response->get_error_message());
            return ['errors' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('SSPU: Invalid Shopify response - ' . substr($body, 0, 500));
            return ['errors' => 'Invalid response from the Shopify API.'];
        }

        $headers = wp_remote_retrieve_headers($response);
        if (isset($headers['link'])) {
            $decoded_body['headers']['link'] = $headers['link'];
        }

        return $decoded_body;
    }
    
    /**
     * Sends a request to the Shopify GraphQL API.
     *
     * @param string $query The GraphQL query or mutation.
     * @param array  $variables The variables for the query.
     * @return array The decoded JSON response from the API.
     */
    public function send_graphql_request(string $query, array $variables = []): array {
        if (empty($this->store_name) || empty($this->access_token)) {
            error_log('SSPU: Shopify credentials not configured for GraphQL');
            return ['errors' => 'API credentials are not set in the settings.'];
        }

        $payload = ['query' => $query];
        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Content-Type'           => 'application/json',
                'X-Shopify-Access-Token' => $this->access_token,
            ],
            'body'    => json_encode($payload),
            'timeout' => 90,
        ];

        $response = wp_remote_post($this->graphql_endpoint, $args);

        if (is_wp_error($response)) {
            error_log('SSPU: Shopify GraphQL request error - ' . $response->get_error_message());
            return ['errors' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('SSPU: Invalid Shopify GraphQL response - ' . substr($body, 0, 500));
            return ['errors' => 'Invalid response from the Shopify GraphQL API.'];
        }

        return $decoded_body;
    }

    /**
     * Uploads a file from a URL to Shopify's Files section via GraphQL.
     *
     * @param string $file_url The URL of the file to upload.
     * @param string $original_filename The desired filename for the upload.
     * @return string|null The permanent Shopify CDN URL of the uploaded file, or null on failure.
     */
    public function upload_file_from_url(string $file_url, string $original_filename): ?string {
        // Step 1: Download the file to a temporary location
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $temp_file_path = download_url($file_url, 300);

        if (is_wp_error($temp_file_path)) {
            error_log('SSPU File Upload: Failed to download file from URL: ' . $file_url . ' - ' . $temp_file_path->get_error_message());
            return null;
        }

        $mime_type = mime_content_type($temp_file_path);
        $file_size = filesize($temp_file_path);
        $filename = basename($original_filename);

        // Step 2: Create a staged upload via GraphQL
        $staged_upload_query = <<<'GRAPHQL'
            mutation stagedUploadsCreate($input: [StagedUploadInput!]!) {
                stagedUploadsCreate(input: $input) {
                    stagedTargets {
                        url
                        resourceUrl
                        parameters {
                            name
                            value
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $staged_upload_vars = [
            'input' => [
                'filename' => $filename,
                'mimeType' => $mime_type,
                'resource' => 'FILE',
                'httpMethod' => 'POST',
                'fileSize' => (string)$file_size,
            ]
        ];
        
        $staged_response = $this->send_graphql_request($staged_upload_query, $staged_upload_vars);

        if (!empty($staged_response['data']['stagedUploadsCreate']['userErrors'])) {
            $error_message = $staged_response['data']['stagedUploadsCreate']['userErrors'][0]['message'];
            error_log('SSPU File Upload: Staged upload creation failed: ' . $error_message);
            @unlink($temp_file_path);
            return null;
        }

        $staged_target = $staged_response['data']['stagedUploadsCreate']['stagedTargets'][0];
        $staged_url = $staged_target['url'];
        $resource_url = $staged_target['resourceUrl'];
        $staged_params = $staged_target['parameters'];

        // Step 3: Upload the file to the staged URL using cURL for multipart/form-data
        $curl_handle = curl_init();
        $curl_post_fields = [];
        foreach ($staged_params as $param) {
            $curl_post_fields[$param['name']] = $param['value'];
        }
        $curl_post_fields['file'] = new CURLFile($temp_file_path, $mime_type, $filename);

        curl_setopt_array($curl_handle, [
            CURLOPT_URL => $staged_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $curl_post_fields,
            CURLOPT_TIMEOUT => 90,
        ]);
        
        $upload_response = curl_exec($curl_handle);
        $http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);
        
        if ($http_code >= 300) {
            error_log('SSPU File Upload: Failed to upload file to staged URL. HTTP Code: ' . $http_code . ' Response: ' . $upload_response);
            @unlink($temp_file_path);
            return null;
        }

        // Step 4: Create the file in Shopify from the staged resource
        $file_create_query = <<<'GRAPHQL'
            mutation fileCreate($files: [FileCreateInput!]!) {
                fileCreate(files: $files) {
                    files {
                        ... on GenericFile {
                            id
                            url
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;
        
        $file_create_vars = [
            'files' => [
                'originalSource' => $resource_url,
                'contentType' => 'FILE',
            ]
        ];

        $file_create_response = $this->send_graphql_request($file_create_query, $file_create_vars);
        @unlink($temp_file_path);

        if (!empty($file_create_response['data']['fileCreate']['userErrors'])) {
            $error_message = $file_create_response['data']['fileCreate']['userErrors'][0]['message'];
            error_log('SSPU File Upload: File creation failed: ' . $error_message);
            return null;
        }
        
        if (isset($file_create_response['data']['fileCreate']['files'][0]['url'])) {
            $final_url = $file_create_response['data']['fileCreate']['files'][0]['url'];
            error_log('SSPU File Upload: File successfully created on Shopify. URL: ' . $final_url);
            return $final_url;
        }
        
        error_log('SSPU File Upload: Failed to get final URL after file creation.');
        return null;
    }

    /**
     * Uploads an image from a file path to Shopify using Base64.
     *
     * @param string $file_path The local path to the image file.
     * @param int $product_id The ID of the product to associate the image with.
     * @return array The uploaded image data or an array with an error.
     */
    public function upload_image_from_path(string $file_path, int $product_id) {
        if (!file_exists($file_path)) {
            return ['errors' => 'File not found at path: ' . $file_path];
        }

        $image_data = file_get_contents($file_path);
        if ($image_data === false) {
            return ['errors' => 'Could not read file contents.'];
        }

        $payload = [
            'image' => [
                'attachment' => base64_encode($image_data),
                'filename'   => basename($file_path),
            ]
        ];

        $endpoint = sprintf('products/%d/images.json', $product_id);
        return $this->send_request($endpoint, 'POST', $payload);
    }

    /**
     * Retrieves all collections from Shopify, handling pagination.
     *
     * @return array A sorted list of all collections.
     */
    public function get_all_collections(): array {
        $all_collections = [];
        $endpoints = [
            'custom_collections' => 'custom_collections.json?limit=250',
            'smart_collections'  => 'smart_collections.json?limit=250',
        ];

        foreach ($endpoints as $key => $endpoint) {
            $page_info = null;
            $has_more = true;

            while ($has_more) {
                $current_endpoint = $endpoint;
                if ($page_info) {
                     // The Shopify REST API pagination via Link header gives a full URL, we just need the page_info token
                     $current_endpoint = add_query_arg('page_info', $page_info, $endpoint);
                }

                $response = $this->send_request($current_endpoint);

                if (isset($response[$key])) {
                    $all_collections = array_merge($all_collections, $response[$key]);
                }

                if (isset($response['headers']['link'])) {
                    $link_header = $this->parseLinkHeader($response['headers']['link']);
                    if (isset($link_header['next'])) {
                        $page_info = $link_header['next']['page_info'];
                    } else {
                        $has_more = false;
                    }
                } else {
                    $has_more = false;
                }
            }
        }

        usort($all_collections, function ($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        return $all_collections;
    }

    /**
     * Parses the Link header from the Shopify API response for pagination.
     *
     * @param string $header The Link header string.
     * @return array Parsed link data.
     */
    private function parseLinkHeader(string $header): array {
        $links = [];
        if (empty($header)) {
            return $links;
        }

        $parts = explode(',', $header);
        foreach ($parts as $part) {
            if (preg_match('/<([^>]+)>;\s*rel="([^"]+)"/', trim($part), $matches)) {
                $url = $matches[1];
                $rel = $matches[2];
                parse_str(parse_url($url, PHP_URL_QUERY), $query_params);
                if (isset($query_params['page_info'])) {
                    $links[$rel] = [
                        'url'       => $url,
                        'page_info' => $query_params['page_info'],
                    ];
                }
            }
        }
        return $links;
    }

    public function search_products($params = []) {
        $query_parts = [];
        if(!empty($params['query'])) $query_parts[] = 'title:' . $params['query'] . '*';
        if(!empty($params['status'])) $query_parts[] = 'status:' . $params['status'];
        if(!empty($params['vendor'])) $query_parts[] = 'vendor:' . $params['vendor'];

        $query_string = implode(' AND ', $query_parts);

        $endpoint_params = ['query' => $query_string, 'limit' => $params['limit'] ?? 50];
        if(!empty($params['page_info'])) {
            $endpoint_params['page_info'] = $params['page_info'];
        }

        $endpoint = 'products.json?' . http_build_query($endpoint_params);

        if(!empty($params['collection_id'])) {
             $endpoint = sprintf('collections/%d/products.json?%s', $params['collection_id'], http_build_query($endpoint_params));
        }

        $response = $this->send_request($endpoint, 'GET');

        if (isset($response['headers']['link'])) {
            $link_data = $this->parseLinkHeader($response['headers']['link']);
            if (isset($link_data['next'])) $response['next_page_info'] = $link_data['next']['page_info'];
            if (isset($link_data['previous'])) $response['prev_page_info'] = $link_data['previous']['page_info'];
        }
        
        return $response;
    }

    public function get_product($product_id) {
        $response = $this->send_request(sprintf('products/%d.json', $product_id));
        if(isset($response['product'])) {
            $meta_response = $this->send_request(sprintf('products/%d/metafields.json', $product_id));
            if(isset($meta_response['metafields'])) $response['product']['metafields'] = $meta_response['metafields'];
            foreach($response['product']['variants'] as &$variant) {
                if(!empty($variant['inventory_item_id'])) {
                    $inv_response = $this->send_request('inventory_levels.json?inventory_item_ids=' . $variant['inventory_item_id']);
                    if(isset($inv_response['inventory_levels'][0])) $variant['inventory_level'] = $inv_response['inventory_levels'][0];
                }
                $var_meta_response = $this->send_request(sprintf('variants/%d/metafields.json', $variant['id']));
                if(isset($var_meta_response['metafields'])) $variant['metafields'] = $var_meta_response['metafields'];
            }
        }
        return $response;
    }

    public function update_product($product_id, $product_data) {
        return $this->send_request(sprintf('products/%d.json', $product_id), 'PUT', ['product' => $product_data]);
    }

    public function update_images_order($product_id, $image_ids) {
        $images = array_map(function($id, $pos) { return ['id' => $id, 'position' => $pos + 1]; }, $image_ids, array_keys($image_ids));
        return $this->update_product($product_id, ['images' => $images]);
    }

    public function delete_product_image($product_id, $image_id) {
        return $this->send_request(sprintf('products/%d/images/%d.json', $product_id, $image_id), 'DELETE');
    }

    public function update_inventory_level($inventory_item_id, $available, $location_id) {
        return $this->send_request('inventory_levels/set.json', 'POST', ['location_id' => $location_id, 'inventory_item_id' => $inventory_item_id, 'available' => $available]);
    }

    public function get_locations() {
        return $this->send_request('locations.json');
    }

    public function update_product_metafield($product_id, $metafield_data) {
        $endpoint = isset($metafield_data['id']) ? sprintf('products/%d/metafields/%d.json', $product_id, $metafield_data['id']) : sprintf('products/%d/metafields.json', $product_id);
        $method = isset($metafield_data['id']) ? 'PUT' : 'POST';
        return $this->send_request($endpoint, $method, ['metafield' => $metafield_data]);
    }

    public function update_variant_metafield($variant_id, $metafield_data) {
        $endpoint = isset($metafield_data['id']) ? sprintf('variants/%d/metafields/%d.json', $variant_id, $metafield_data['id']) : sprintf('variants/%d/metafields.json', $variant_id);
        $method = isset($metafield_data['id']) ? 'PUT' : 'POST';
        return $this->send_request($endpoint, $method, ['metafield' => $metafield_data]);
    }

    public function duplicate_product($product_id, $new_title) {
        $original = $this->get_product($product_id);
        if(!isset($original['product'])) return ['errors' => 'Original product not found'];
        $product = $original['product'];
        $new_product = [
            'title' => $new_title, 'body_html' => $product['body_html'], 'vendor' => $product['vendor'],
            'product_type' => $product['product_type'], 'tags' => $product['tags'], 'options' => $product['options'],
            'variants' => array_map(function($v) { $v['sku'] .= '-COPY'; unset($v['id'], $v['product_id'], $v['inventory_item_id']); return $v; }, $product['variants']),
            'images' => array_map(function($i) { unset($i['id'], $i['product_id']); return $i; }, $product['images'])
        ];
        return $this->send_request('products.json', 'POST', ['product' => $new_product]);
    }

    public function get_vendors() {
        $response = $this->send_request('products.json?limit=250&fields=vendor');
        $vendors = isset($response['products']) ? array_unique(array_column($response['products'], 'vendor')) : [];
        sort($vendors);
        return array_filter($vendors);
    }

    public function add_to_collections($product_id, $collection_ids) {
        $results = [];
        foreach($collection_ids as $cid) {
            $results[] = $this->send_request('collects.json', 'POST', ['collect' => ['product_id' => $product_id, 'collection_id' => $cid]]);
        }
        return $results;
    }

    public function remove_from_collection($product_id, $collection_id) {
        $response = $this->send_request(sprintf('collects.json?product_id=%d&collection_id=%d', $product_id, $collection_id));
        if(isset($response['collects'][0]['id'])) {
            return $this->send_request(sprintf('collects/%d.json', $response['collects'][0]['id']), 'DELETE');
        }
        return ['errors' => 'Collect not found'];
    }

    public function get_product_collections($product_id) {
        $response = $this->send_request(sprintf('collects.json?product_id=%d', $product_id));
        return isset($response['collects']) ? array_column($response['collects'], 'collection_id') : [];
    }
}