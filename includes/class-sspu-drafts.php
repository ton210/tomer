<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Drafts {

    public function __construct() {
        add_action( 'wp_ajax_sspu_auto_save_draft', [ $this, 'handle_auto_save' ] );
        add_action( 'wp_ajax_sspu_get_drafts', [ $this, 'handle_get_drafts' ] );
        add_action( 'wp_ajax_sspu_delete_draft', [ $this, 'handle_delete_draft' ] );
    }

    public function save_draft($user_id, $draft_data, $is_auto_save = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_drafts';
        
        // Clean up old auto-saves (keep only last 5 auto-saves per user)
        if ($is_auto_save) {
            $old_auto_saves = $wpdb->get_results($wpdb->prepare("
                SELECT draft_id FROM {$table_name} 
                WHERE user_id = %d AND is_auto_save = 1 
                ORDER BY created_at DESC 
                LIMIT 5, 999999
            ", $user_id));
            
            foreach ($old_auto_saves as $draft) {
                $wpdb->delete($table_name, ['draft_id' => $draft->draft_id]);
            }
        }
        
        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'draft_data' => json_encode($draft_data),
            'is_auto_save' => $is_auto_save ? 1 : 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        return $result ? $wpdb->insert_id : false;
    }

    public function get_latest_draft($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_drafts';
        
        $draft = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE user_id = %d 
            ORDER BY updated_at DESC 
            LIMIT 1
        ", $user_id));
        
        if ($draft) {
            return json_decode($draft->draft_data, true);
        }
        
        return false;
    }

    public function get_user_drafts($user_id, $include_auto_save = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_drafts';
        
        $auto_save_condition = $include_auto_save ? '' : 'AND is_auto_save = 0';
        
        $drafts = $wpdb->get_results($wpdb->prepare("
            SELECT draft_id, created_at, updated_at, is_auto_save,
                   JSON_EXTRACT(draft_data, '$.product_name') as product_name
            FROM {$table_name} 
            WHERE user_id = %d {$auto_save_condition}
            ORDER BY updated_at DESC
        ", $user_id));
        
        return $drafts;
    }

    public function load_draft($draft_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_drafts';
        
        $draft = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE draft_id = %d AND user_id = %d
        ", $draft_id, $user_id));
        
        if ($draft) {
            return json_decode($draft->draft_data, true);
        }
        
        return false;
    }

    public function delete_draft($draft_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_drafts';
        
        return $wpdb->delete($table_name, [
            'draft_id' => $draft_id,
            'user_id' => $user_id
        ]);
    }

    public function handle_auto_save() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $draft_data = $_POST['draft_data'];
        $user_id = get_current_user_id();
        
        $draft_id = $this->save_draft($user_id, $draft_data, true);
        
        if ($draft_id) {
            wp_send_json_success(['draft_id' => $draft_id, 'timestamp' => current_time('mysql')]);
        } else {
            wp_send_json_error('Failed to auto-save draft');
        }
    }

    public function handle_get_drafts() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $include_auto_save = isset($_POST['include_auto_save']) && $_POST['include_auto_save'];
        
        $drafts = $this->get_user_drafts($user_id, $include_auto_save);
        
        wp_send_json_success($drafts);
    }

    public function handle_delete_draft() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $draft_id = absint($_POST['draft_id']);
        $user_id = get_current_user_id();
        
        $result = $this->delete_draft($draft_id, $user_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete draft');
        }
    }
}