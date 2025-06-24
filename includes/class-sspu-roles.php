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
        self::create_mimic_images_table();
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
     * Creates the mimic reference images table.
     */
    public static function create_mimic_images_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_mimic_images';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            mimic_id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            image_id bigint(20) NOT NULL,
            image_url text NOT NULL,
            category varchar(50) DEFAULT 'general',
            style_keywords text DEFAULT NULL,
            is_global tinyint(1) DEFAULT 0 NOT NULL,
            usage_count int DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (mimic_id),
            KEY user_id (user_id),
            KEY category (category),
            KEY is_global (is_global),
            KEY image_id (image_id),
            KEY usage_count (usage_count)
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
        $mimic_images_table = $wpdb->prefix . 'sspu_mimic_images';
        
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
            ],
            $mimic_images_table => [
                'user_id' => "ALTER TABLE {$mimic_images_table} ADD INDEX user_id (user_id)",
                'category' => "ALTER TABLE {$mimic_images_table} ADD INDEX category (category)",
                'is_global' => "ALTER TABLE {$mimic_images_table} ADD INDEX is_global (is_global)",
                'image_id' => "ALTER TABLE {$mimic_images_table} ADD INDEX image_id (image_id)",
                'usage_count' => "ALTER TABLE {$mimic_images_table} ADD INDEX usage_count (usage_count)"
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
        
        // Insert default mimic images if they don't exist
        self::insert_default_mimic_images();
        
        // Check if shopify_data column exists in product_log table
        $product_log_table = $wpdb->prefix . 'sspu_product_log';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$product_log_table} LIKE 'shopify_data'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$product_log_table} ADD COLUMN shopify_data LONGTEXT NULL AFTER error_data");
            error_log('SSPU: Added shopify_data column to product_log table');
        }

        // Update the database version
        update_option('sspu_db_version', '1.6.0');
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
                'prompt' => 'EXTRACT the existing product exactly as shown and remove the background completely, creating a clean transparent or white background while preserving all product details.',
                'category' => 'background',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'Add Lifestyle Context',
                'prompt' => 'EXTRACT the existing product without any modifications and place it in a professional lifestyle setting that shows it being used naturally. Make it look professional and appealing for marketing.',
                'category' => 'lifestyle',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'E-commerce White Background',
                'prompt' => 'EXTRACT the existing product exactly as it appears and place it on a pure white background with professional studio lighting, subtle shadow, and proper padding for e-commerce listing.',
                'category' => 'ecommerce',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'Add Company Logo',
                'prompt' => 'Keep the existing product image unchanged and add a placeholder company logo in the bottom right corner in a professional way that shows customization options.',
                'category' => 'branding',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'Enhance Product Quality',
                'prompt' => 'PRESERVE the existing product exactly as shown but enhance the image quality, fix lighting, remove imperfections, and make the product look more professional and appealing.',
                'category' => 'enhancement',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'Create Hero Shot',
                'prompt' => 'EXTRACT the existing product without any changes and transform this into a hero product shot with dramatic lighting and professional composition suitable for homepage banner.',
                'category' => 'hero',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'Amazon Listing Ready',
                'prompt' => 'EXTRACT the existing product exactly as shown and optimize for Amazon listing requirements: pure white background, centered product, proper margins, and professional presentation.',
                'category' => 'ecommerce',
                'ai_service' => 'gemini',
                'is_global' => 1
            ],
            [
                'name' => 'Social Media Optimized',
                'prompt' => 'EXTRACT the existing product without modifications and create a social media optimized version with eye-catching composition, vibrant colors, and engaging presentation.',
                'category' => 'social',
                'ai_service' => 'gemini',
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

    /**
     * Insert default mimic reference images
     */
    private static function insert_default_mimic_images() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_mimic_images';
        
        // Check if we already have mimic images
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        if ($existing > 0) {
            return;
        }
        
        $default_mimic_images = [
            [
                'name' => 'E-commerce White Background Standard',
                'description' => 'Clean white background with 10% padding, subtle drop shadow, professional studio lighting - perfect for online stores',
                'category' => 'ecommerce',
                'style_keywords' => 'white background, padding, shadow, professional, clean, studio lighting, e-commerce',
                'is_global' => 1
            ],
            [
                'name' => 'Lifestyle Kitchen Setting',
                'description' => 'Product shown in modern kitchen environment with natural lighting and lifestyle context',
                'category' => 'lifestyle',
                'style_keywords' => 'lifestyle, kitchen, natural lighting, context, environment, realistic, home',
                'is_global' => 1
            ],
            [
                'name' => 'Hero Banner Dramatic',
                'description' => 'Premium presentation with dramatic lighting, dark background, and hero shot composition for marketing banners',
                'category' => 'hero',
                'style_keywords' => 'dramatic, premium, hero, composition, lighting, dark background, marketing',
                'is_global' => 1
            ],
            [
                'name' => 'Amazon Listing Style',
                'description' => 'Amazon-compliant product shot with pure white background, centered positioning, and proper margins',
                'category' => 'ecommerce',
                'style_keywords' => 'amazon, pure white, centered, margins, compliant, listing, standard',
                'is_global' => 1
            ],
            [
                'name' => 'Social Media Square',
                'description' => 'Square format optimized for Instagram and Facebook with vibrant colors and engaging composition',
                'category' => 'social',
                'style_keywords' => 'social media, square, instagram, facebook, vibrant, engaging, colorful',
                'is_global' => 1
            ],
            [
                'name' => 'Minimalist Modern',
                'description' => 'Clean minimalist style with soft shadows and modern aesthetic perfect for premium brands',
                'category' => 'modern',
                'style_keywords' => 'minimalist, modern, clean, soft shadows, premium, aesthetic, simple',
                'is_global' => 1
            ]
        ];
        
        foreach ($default_mimic_images as $mimic) {
            $wpdb->insert($table_name, array_merge($mimic, [
                'user_id' => 0, // System images
                'image_id' => 0, // Will be updated when actual images are uploaded
                'image_url' => '', // Will be updated when actual images are uploaded
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]));
        }
        
        error_log('SSPU: Inserted ' . count($default_mimic_images) . ' default mimic reference images');
    }

    /**
     * Get default mimic images that need actual image uploads
     */
    public static function get_pending_mimic_images() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_mimic_images';
        
        return $wpdb->get_results(
            "SELECT * FROM {$table_name} 
            WHERE is_global = 1 AND image_id = 0 
            ORDER BY mimic_id ASC"
        );
    }

    /**
     * Update mimic image with actual image data
     */
    public static function update_mimic_image($mimic_id, $image_id, $image_url) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_mimic_images';
        
        return $wpdb->update(
            $table_name,
            [
                'image_id' => $image_id,
                'image_url' => $image_url,
                'updated_at' => current_time('mysql')
            ],
            ['mimic_id' => $mimic_id]
        );
    }
}
