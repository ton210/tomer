<?php
/**
 * SSPU Cloudinary API Class
 *
 * Handles all communication with the Cloudinary API for image uploads.
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Cloudinary_API {

    private $cloud_name;
    private $api_key;
    private $api_secret;
    private $upload_preset;
    private $api_url;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->cloud_name = get_option('sspu_cloudinary_cloud_name');
        $this->api_key = get_option('sspu_cloudinary_api_key');
        $this->api_secret = get_option('sspu_cloudinary_api_secret');
        $this->upload_preset = get_option('sspu_cloudinary_upload_preset'); // Optional preset
        
        if (!empty($this->cloud_name)) {
            $this->api_url = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload";
        }
    }
    
    /**
     * Checks if the Cloudinary API is configured.
     * @return bool
     */
    public function is_configured() {
        return !empty($this->cloud_name) && !empty($this->api_key) && !empty($this->api_secret);
    }

    /**
     * Uploads an image from a file path to Cloudinary.
     *
     * @param string $file_path The local path to the image file.
     * @param string $public_id A unique identifier for the image in Cloudinary.
     * @param string $folder An optional folder name to store the image in.
     * @return array|WP_Error The Cloudinary response on success, or a WP_Error on failure.
     */
    public function upload_image($file_path, $public_id, $folder = 'sspu_designs') {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'Cloudinary API is not configured.');
        }

        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'File not found at path: ' . $file_path);
        }

        $timestamp = time();
        
        // Parameters for signing
        $params_to_sign = [
            'folder' => $folder,
            'public_id' => $public_id,
            'timestamp' => $timestamp,
        ];
        
        $signature = $this->sign_parameters($params_to_sign);

        // Use multipart/form-data for file uploads
        $boundary = wp_generate_password(24, false);
        
        $payload = '';
        
        // Add regular form fields
        $form_fields = [
            'api_key' => $this->api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => $folder,
            'public_id' => $public_id,
        ];
        
        foreach ($form_fields as $name => $value) {
            $payload .= "--{$boundary}\r\n";
            $payload .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            $payload .= $value . "\r\n";
        }
        
        // Add file field
        $file_contents = file_get_contents($file_path);
        $filename = basename($file_path);
        $mime_type = $this->get_mime_type($file_path);
        
        $payload .= "--{$boundary}\r\n";
        $payload .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
        $payload .= "Content-Type: {$mime_type}\r\n\r\n";
        $payload .= $file_contents . "\r\n";
        $payload .= "--{$boundary}--\r\n";

        $args = [
            'method' => 'POST',
            'timeout' => 90,
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                'Content-Length' => strlen($payload)
            ],
            'body' => $payload,
        ];

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            error_log('Cloudinary Upload Error: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Log response for debugging
        error_log('Cloudinary Response Code: ' . $response_code);
        error_log('Cloudinary Response Body: ' . $body);
        
        $data = json_decode($body, true);

        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            error_log('Cloudinary API Error: ' . $error_message);
            return new WP_Error('cloudinary_error', $error_message);
        }

        if (isset($data['error'])) {
            error_log('Cloudinary API Error: ' . $data['error']['message']);
            return new WP_Error('cloudinary_error', $data['error']['message']);
        }

        return $data;
    }
    
    /**
     * Alternative upload method using cURL (more reliable for large files)
     */
    public function upload_image_curl($file_path, $public_id, $folder = 'sspu_designs') {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'Cloudinary API is not configured.');
        }

        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'File not found at path: ' . $file_path);
        }

        if (!function_exists('curl_init')) {
            return new WP_Error('curl_missing', 'cURL is not available.');
        }

        $timestamp = time();
        
        $params_to_sign = [
            'folder' => $folder,
            'public_id' => $public_id,
            'timestamp' => $timestamp,
        ];
        
        $signature = $this->sign_parameters($params_to_sign);

        $post_fields = [
            'api_key' => $this->api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => $folder,
            'public_id' => $public_id,
            'file' => new CURLFile($file_path)
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'SSPU WordPress Plugin'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log('Cloudinary cURL Error: ' . $curl_error);
            return new WP_Error('curl_error', $curl_error);
        }

        error_log('Cloudinary cURL Response Code: ' . $http_code);
        error_log('Cloudinary cURL Response: ' . $response);

        $data = json_decode($response, true);

        if ($http_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            return new WP_Error('cloudinary_error', $error_message);
        }

        if (isset($data['error'])) {
            return new WP_Error('cloudinary_error', $data['error']['message']);
        }

        return $data;
    }
    
    /**
     * Get MIME type of file
     */
    private function get_mime_type($file_path) {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            return $mime_type;
        } elseif (function_exists('mime_content_type')) {
            return mime_content_type($file_path);
        } else {
            // Fallback based on file extension
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            $mime_types = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            return isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';
        }
    }
    
    /**
     * Creates a signature for signed Cloudinary uploads.
     * @param array $params The parameters to sign.
     * @return string The generated SHA1 signature.
     */
    private function sign_parameters($params) {
        ksort($params);
        $string_to_sign = [];
        foreach ($params as $key => $value) {
            $string_to_sign[] = "$key=$value";
        }
        return sha1(implode('&', $string_to_sign) . $this->api_secret);
    }
    
    /**
     * Test the Cloudinary connection
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'Cloudinary API is not configured.');
        }

        // Create a small test image
        $test_image = imagecreate(100, 100);
        $bg_color = imagecolorallocate($test_image, 255, 255, 255);
        $text_color = imagecolorallocate($test_image, 0, 0, 0);
        imagestring($test_image, 5, 10, 40, 'TEST', $text_color);
        
        $temp_file = wp_tempnam('cloudinary_test.png');
        imagepng($test_image, $temp_file);
        imagedestroy($test_image);
        
        $result = $this->upload_image_curl($temp_file, 'test_connection_' . time(), 'sspu_test');
        
        unlink($temp_file);
        
        return $result;
    }
}