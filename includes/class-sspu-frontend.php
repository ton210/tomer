<?php
if ( ! defined( 'WPINC' ) ) die;

class SSPU_Frontend {
    public function __construct() {
        // Register shortcodes
        add_shortcode( 'sspu_frontend_dashboard', [ $this, 'render_frontend_dashboard' ] );

        // Register AJAX endpoints
        add_action( 'wp_ajax_sspu_get_dashboard_partial', [ $this, 'ajax_get_dashboard_partial' ] );
        add_action( 'wp_ajax_nopriv_sspu_frontend_login', [ $this, 'ajax_handle_login' ] );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
    }

    public function enqueue_frontend_assets() {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'sspu_frontend_dashboard' ) ) {
            wp_enqueue_style( 'sspu-frontend-style', SSPU_PLUGIN_URL . 'assets/css/frontend-style.css', [], SSPU_VERSION );
            wp_enqueue_script( 'sspu-frontend-js', SSPU_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], SSPU_VERSION, true );
            wp_localize_script( 'sspu-frontend-js', 'sspu_frontend_ajax', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'sspu_frontend_nonce' ),
            ] );
        }
    }

    public function render_frontend_dashboard() {
        if ( !is_user_logged_in() || !current_user_can('upload_shopify_products') ) {
            return $this->render_login_form();
        }

        $current_user = wp_get_current_user();

        ob_start();
        ?>
        <div id="sspu-frontend-app">
            <aside class="sspu-sidebar">
                <div class="sspu-sidebar-header">
                    <h2>SPPU Dashboard</h2>
                </div>
                <nav class="sspu-nav">
                    <a href="#leaderboard" class="sspu-nav-item active" data-partial="leaderboard-page">
                        <span class="dashicons dashicons-dashboard"></span> Leaderboard
                    </a>
                    <a href="#uploader" class="sspu-nav-item" data-partial="uploader-page">
                        <span class="dashicons dashicons-upload"></span> Product Uploader
                    </a>
                    <a href="#live-editor" class="sspu-nav-item" data-partial="live-editor-page">
                        <span class="dashicons dashicons-edit"></span> Live Editor
                    </a>
                    <a href="#search" class="sspu-nav-item" data-partial="search-page">
                        <span class="dashicons dashicons-search"></span> Search
                    </a>
                    <a href="#alibaba-queue" class="sspu-nav-item" data-partial="alibaba-queue-page">
                        <span class="dashicons dashicons-list-view"></span> Alibaba Queue
                    </a>
                    <a href="#analytics" class="sspu-nav-item" data-partial="analytics-page">
                        <span class="dashicons dashicons-chart-area"></span> Analytics
                    </a>
                    <a href="#image-templates" class="sspu-nav-item" data-partial="image-templates-page">
                        <span class="dashicons dashicons-images-alt2"></span> Image Templates
                    </a>
                </nav>
                <div class="sspu-sidebar-footer">
                    <div class="sspu-user-info">
                        <?php echo get_avatar($current_user->ID, 40); ?>
                        <span><?php echo esc_html($current_user->display_name); ?></span>
                    </div>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="sspu-logout-btn">
                        <span class="dashicons dashicons-migrate"></span> Logout
                    </a>
                </div>
            </aside>
            <main id="sspu-main-content">
                <div class="sspu-loader">
                    <div class="sspu-spinner"></div>
                </div>
            </main>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_login_form() {
        ob_start();
        // Same login form HTML from previous answer
        ?>
        <div id="sspu-login-widget" class="sspu-login-widget">
            <h3>Staff Login</h3>
            <form id="sspu-login-form">
                <p class="sspu-form-row">
                    <label for="sspu-user-login">Username or Email</label>
                    <input type="text" name="log" id="sspu-user-login" class="sspu-input">
                </p>
                <p class="sspu-form-row">
                    <label for="sspu-user-pass">Password</label>
                    <input type="password" name="pwd" id="sspu-user-pass" class="sspu-input">
                </p>
                <p class="sspu-form-row">
                    <button type="submit" id="sspu-login-submit" class="sspu-button">Log In</button>
                </p>
                <p id="sspu-login-status"></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_handle_login() {
        check_ajax_referer('sspu_frontend_nonce', 'nonce');
        $creds = [
            'user_login'    => sanitize_user($_POST['log']),
            'user_password' => $_POST['pwd'],
            'remember'      => true,
        ];
        $user = wp_signon($creds, is_ssl());
        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'Invalid username or password.']);
        } else {
            wp_send_json_success(['message' => 'Login successful! Reloading...']);
        }
    }

    public function ajax_get_dashboard_partial() {
        check_ajax_referer('sspu_frontend_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $partial = sanitize_file_name($_POST['partial']);
        $partials = new SSPU_Admin_Partials(); // Use the existing partials renderer

        if ($partials->exists($partial)) {
            ob_start();
            $partials->render($partial);
            $html = ob_get_clean();
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => 'Partial not found.']);
        }
    }
}