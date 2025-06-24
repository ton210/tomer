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
        $this->api_url = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload";
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
        
        $params_to_sign = [
            'folder' => $folder,
            'public_id' => $public_id,
            'timestamp' => $timestamp,
        ];
        
        // If using an upload preset, it might not need signing depending on preset settings.
        // For signed uploads (more secure):
        $signature = $this->sign_parameters($params_to_sign);

        $payload = [
            'file' => 'data:image/png;base64,' . base64_encode(file_get_contents($file_path)),
            'api_key' => $this->api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => $folder,
            'public_id' => $public_id,
        ];
        
        // If using an unsigned preset instead:
        // unset($payload['timestamp'], $payload['signature'], $payload['api_key']);
        // $payload['upload_preset'] = $this->upload_preset;

        $args = [
            'method' => 'POST',
            'timeout' => 90,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
        ];

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            error_log('Cloudinary Upload Error: ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            error_log('Cloudinary API Error: ' . $data['error']['message']);
            return new WP_Error('cloudinary_error', $data['error']['message']);
        }

        return $data;
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
}