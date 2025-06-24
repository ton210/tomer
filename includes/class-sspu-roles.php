<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Roles {

    /**
     * Runs on plugin activation.
     */
    public static function activate() {
        self::add_role();
        self::add_caps_to_admin();
        self::create_log_table();
        self::create_drafts_table();
        self::create_activity_log_table();
        self::create_alibaba_queue_table();
        self::create_image_templates_table();
        self::create_ai_chat_history_table();
        self::update_database_schema();
    }

    /**
     * Runs on plugin deactivation.
     */
    public static function deactivate() {
        self::remove_role();
        self::remove_caps_from_admin();
    }

    /**
     * Add the custom user role.
     */
    public static function add_role() {
    add_role(
        'shopify_uploader',
        __( 'Shopify Uploader', 'sspu' ),
        [
            'read' => true, // Basic dashboard access
            'upload_files' => true, // ✅ Allows media uploads
            'upload_shopify_products' => true // Custom capability
        ]
    );
}


    /**
     * Remove the custom user role.
     */
    public static function remove_role() {
        remove_role( 'shopify_uploader' );
    }

    /**
     * Grant the custom capability to the Administrator role.
     */
    public static function add_caps_to_admin() {
        $admin_role = get_role( 'administrator' );
        if ( ! empty( $admin_role ) ) {
            $admin_role->add_cap( 'upload_shopify_products' );
        }
    }

    /**
     * Remove the custom capability from the Administrator role.
     */
    public static function remove_caps_from_admin() {
        $admin_role = get_role( 'administrator' );
        if ( ! empty( $admin_role ) ) {
            $admin_role->remove_cap( 'upload_shopify_products' );
        }
    }

    /**
     * Creates the custom database table for logging uploads.
     */
    public static function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_product_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            log_id mediumint(9) NOT NULL AUTO_INCREMENT,
            upload_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            wp_user_id bigint(20) NOT NULL,
            shopify_product_id bigint(20) DEFAULT NULL,
            product_title text NOT NULL,
            status varchar(50) DEFAULT 'pending' NOT NULL,
            upload_duration float DEFAULT 0 NOT NULL,
            error_data text DEFAULT NULL,
            PRIMARY KEY  (log_id),
            KEY user_timestamp (wp_user_id, upload_timestamp),
            KEY status_timestamp (status, upload_timestamp),
            KEY product_id (shopify_product_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Creates the drafts table for saving product drafts.
     */
    public static function create_drafts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_drafts';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            draft_id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            draft_data longtext NOT NULL,
            is_auto_save tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (draft_id),
            KEY user_updated (user_id, updated_at),
            KEY auto_save (is_auto_save, user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Creates the activity log table for analytics.
     */
    public static function create_activity_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_activity_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            activity_id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            metadata longtext DEFAULT NULL,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            ip_address varchar(100) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY  (activity_id),
            KEY user_action_timestamp (user_id, action, timestamp),
            KEY action_timestamp (action, timestamp),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Creates the Alibaba URL queue table.
     */
    public static function create_alibaba_queue_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            queue_id mediumint(9) NOT NULL AUTO_INCREMENT,
            url text NOT NULL,
            status varchar(20) DEFAULT 'available' NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            created_by bigint(20) NOT NULL,
            assigned_to bigint(20) DEFAULT NULL,
            assigned_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY  (queue_id),
            KEY status (status),
            KEY assigned_to (assigned_to),
            KEY created_at (created_at),
            KEY assigned_at (assigned_at)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Creates the image templates table for AI editing.
     */
    public static function create_image_templates_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            template_id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            prompt text NOT NULL,
            example_images longtext DEFAULT NULL,
            ai_service varchar(20) DEFAULT 'chatgpt',
            is_global tinyint(1) DEFAULT 0 NOT NULL,
            category varchar(50) DEFAULT NULL,
            usage_count int DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (template_id),
            KEY user_id (user_id),
            KEY category (category),
            KEY is_global (is_global)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Creates the AI chat history table.
     */
    public static function create_ai_chat_history_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_ai_chat_history';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            chat_id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            message_type varchar(20) NOT NULL,
            message text NOT NULL,
            image_data longtext DEFAULT NULL,
            ai_service varchar(20) DEFAULT NULL,
            parent_image_id bigint(20) DEFAULT NULL,
            generated_image_id bigint(20) DEFAULT NULL,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (chat_id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Update database schema for existing installations
     */
    public static function update_database_schema() {
        global $wpdb;
        
        // Check if we need to add indexes to existing tables
        $product_log_table = $wpdb->prefix . 'sspu_product_log';
        $drafts_table = $wpdb->prefix . 'sspu_drafts';
        $activity_log_table = $wpdb->prefix . 'sspu_activity_log';
        $alibaba_queue_table = $wpdb->prefix . 'sspu_alibaba_queue';
        $image_templates_table = $wpdb->prefix . 'sspu_image_templates';
        $ai_chat_history_table = $wpdb->prefix . 'sspu_ai_chat_history';
        
        // Add indexes if they don't exist
        $indexes = [
            $product_log_table => [
                'user_timestamp' => "ALTER TABLE {$product_log_table} ADD INDEX user_timestamp (wp_user_id, upload_timestamp)",
                'status_timestamp' => "ALTER TABLE {$product_log_table} ADD INDEX status_timestamp (status, upload_timestamp)",
                'product_id' => "ALTER TABLE {$product_log_table} ADD INDEX product_id (shopify_product_id)"
            ],
            $drafts_table => [
                'user_updated' => "ALTER TABLE {$drafts_table} ADD INDEX user_updated (user_id, updated_at)",
                'auto_save' => "ALTER TABLE {$drafts_table} ADD INDEX auto_save (is_auto_save, user_id)"
            ],
            $activity_log_table => [
                'user_action_timestamp' => "ALTER TABLE {$activity_log_table} ADD INDEX user_action_timestamp (user_id, action, timestamp)",
                'action_timestamp' => "ALTER TABLE {$activity_log_table} ADD INDEX action_timestamp (action, timestamp)",
                'timestamp' => "ALTER TABLE {$activity_log_table} ADD INDEX timestamp (timestamp)"
            ],
            $alibaba_queue_table => [
                'status' => "ALTER TABLE {$alibaba_queue_table} ADD INDEX status (status)",
                'assigned_to' => "ALTER TABLE {$alibaba_queue_table} ADD INDEX assigned_to (assigned_to)",
                'created_at' => "ALTER TABLE {$alibaba_queue_table} ADD INDEX created_at (created_at)",
                'assigned_at' => "ALTER TABLE {$alibaba_queue_table} ADD INDEX assigned_at (assigned_at)"
            ],
            $image_templates_table => [
                'user_id' => "ALTER TABLE {$image_templates_table} ADD INDEX user_id (user_id)",
                'category' => "ALTER TABLE {$image_templates_table} ADD INDEX category (category)",
                'is_global' => "ALTER TABLE {$image_templates_table} ADD INDEX is_global (is_global)"
            ],
            $ai_chat_history_table => [
                'session_id' => "ALTER TABLE {$ai_chat_history_table} ADD INDEX session_id (session_id)",
                'user_id' => "ALTER TABLE {$ai_chat_history_table} ADD INDEX user_id (user_id)",
                'timestamp' => "ALTER TABLE {$ai_chat_history_table} ADD INDEX timestamp (timestamp)"
            ]
        ];
        
        foreach ($indexes as $table => $table_indexes) {
            // Check if table exists first
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if (!$table_exists) {
                continue;
            }
            
            foreach ($table_indexes as $index_name => $sql) {
                // Check if index already exists
                $existing = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = '{$index_name}'");
                if (empty($existing)) {
                    $wpdb->query($sql);
                }
            }
        }
        
        // Insert default templates if they don't exist
        self::insert_default_templates();
        
        // Check if shopify_data column exists in product_log table
        $product_log_table = $wpdb->prefix . 'sspu_product_log';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$product_log_table} LIKE 'shopify_data'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$product_log_table} ADD COLUMN shopify_data LONGTEXT NULL AFTER error_data");
            error_log('SSPU: Added shopify_data column to product_log table');
        }

        // Update the database version
        update_option('sspu_db_version', '1.5.0');
    }

    /**
     * Insert default image editing templates
     */
    private static function insert_default_templates() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        // Check if we already have templates
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        if ($existing > 0) {
            return;
        }
        
        $default_templates = [
            [
                'name' => 'Remove Background',
                'prompt' => 'Remove the background and make it transparent or white. Keep only the main product.',
                'category' => 'background',
                'ai_service' => 'chatgpt',
                'is_global' => 1
            ],
            [
                'name' => 'Add Lifestyle Context',
                'prompt' => 'Place this product in a lifestyle setting that shows it being used. Make it look professional and appealing for marketing.',
                'category' => 'lifestyle',
                'ai_service' => 'chatgpt',
                'is_global' => 1
            ],
            [
                'name' => 'Color Variations',
                'prompt' => 'Create variations of this product in different colors while maintaining the same style and quality.',
                'category' => 'variations',
                'ai_service' => 'chatgpt',
                'is_global' => 1
            ],
            [
                'name' => 'Add Company Logo',
                'prompt' => 'Add a placeholder company logo to this product in a professional way that shows customization options.',
                'category' => 'branding',
                'ai_service' => 'chatgpt',
                'is_global' => 1
            ],
            [
                'name' => 'Enhance Product Quality',
                'prompt' => 'Enhance the image quality, fix lighting, remove imperfections, and make the product look more professional.',
                'category' => 'enhancement',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'Create Hero Shot',
                'prompt' => 'Transform this into a hero product shot with dramatic lighting and professional composition suitable for homepage banner.',
                'category' => 'hero',
                'ai_service' => 'chatgpt',
                'is_global' => 1
            ]
        ];
        
        foreach ($default_templates as $template) {
            $wpdb->insert($table_name, array_merge($template, [
                'user_id' => 0, // System templates
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]));
        }
    }
}