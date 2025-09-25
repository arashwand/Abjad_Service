<?php
/**
 * Activation and deactivation handlers
 */
class Abjad_Activator {
    
    public static function activate() {
        // ایجاد جداول مورد نیاز در دیتابیس
        self::create_tables();
        
        // ایجاد صفحات مورد نیاز
        self::create_pages();
        
        // تنظیم options پیشفرض
        self::set_default_options();
        
        // برنامه‌ریزی cron jobs
        self::schedule_cron_jobs();
        
        // لاگ فعال‌سازی
        self::log_activation();
    }
    
    public static function deactivate() {
        // پاک کردن cron jobs
        self::clear_cron_jobs();
        
        // لاگ غیرفعال‌سازی
        self::log_deactivation();
    }
    
    public static function uninstall() {
        // پاک کردن options
        self::delete_options();
        
        // پاک کردن جداول (اختیاری)
        // self::drop_tables();
        
        // پاک کردن صفحات (اختیاری)
        // self::delete_pages();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'abjad_service_logs';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            service_key varchar(100) NOT NULL,
            input_text text,
            output_text text,
            execution_time float,
            status varchar(20) DEFAULT 'success',
            error_message text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY service_key (service_key),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private static function create_pages() {
        $pages = array(
            'my-services' => array(
                'title' => 'سرویس‌های من',
                'content' => '[abjad_service_panel]',
                'parent' => 'my-account'
            ),
            'abjad-service-help' => array(
                'title' => 'راهنمای سرویس ابجد',
                'content' => 'این صفحه راهنمای استفاده از سرویس‌های ابجد است.',
                'parent' => 0
            )
        );
        
        foreach ($pages as $slug => $page_data) {
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                $page_args = array(
                    'post_title' => $page_data['title'],
                    'post_name' => $slug,
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1,
                );
                
                if (!empty($page_data['parent'])) {
                    if ($page_data['parent'] === 'my-account') {
                        $account_page = get_page_by_path('my-account');
                        if ($account_page) {
                            $page_args['post_parent'] = $account_page->ID;
                        }
                    } else {
                        $page_args['post_parent'] = $page_data['parent'];
                    }
                }
                
                wp_insert_post($page_args);
            }
        }
    }
    
    private static function set_default_options() {
        $default_options = array(
            'abjad_api_url' => 'https://your-aspnet-app.com/api',
            'abjad_api_timeout' => 30,
            'abjad_max_text_length' => 10000,
            'abjad_enable_logging' => 'yes',
            'abjad_auto_save_draft' => 'yes',
            'abjad_usage_notifications' => 'yes',
            'abjad_default_expiry_days' => 30
        );
        
        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    private static function schedule_cron_jobs() {
        if (!wp_next_scheduled('abjad_daily_usage_reset')) {
            wp_schedule_event(time(), 'daily', 'abjad_daily_usage_reset');
        }
        
        if (!wp_next_scheduled('abjad_license_expiry_check')) {
            wp_schedule_event(time(), 'daily', 'abjad_license_expiry_check');
        }
        
        if (!wp_next_scheduled('abjad_cleanup_old_logs')) {
            wp_schedule_event(time(), 'weekly', 'abjad_cleanup_old_logs');
        }
    }
    
    private static function clear_cron_jobs() {
        wp_clear_scheduled_hook('abjad_daily_usage_reset');
        wp_clear_scheduled_hook('abjad_license_expiry_check');
        wp_clear_scheduled_hook('abjad_cleanup_old_logs');
    }
    
    private static function delete_options() {
        $options_to_delete = array(
            'abjad_api_url',
            'abjad_api_timeout',
            'abjad_max_text_length',
            'abjad_enable_logging',
            'abjad_auto_save_draft',
            'abjad_usage_notifications',
            'abjad_default_expiry_days'
        );
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
    }
    
    private static function log_activation() {
        $log_entry = array(
            'event' => 'plugin_activated',
            'version' => ABJAD_PLUGIN_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'timestamp' => current_time('mysql')
        );
        
        add_option('abjad_activation_log', $log_entry);
    }
    
    private static function log_deactivation() {
        $log_entry = array(
            'event' => 'plugin_deactivated',
            'timestamp' => current_time('mysql')
        );
        
        update_option('abjad_deactivation_log', $log_entry);
    }
}

// ثبت هوک‌های فعال‌سازی و غیرفعال‌سازی
register_activation_hook(__FILE__, array('Abjad_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Abjad_Activator', 'deactivate'));
register_uninstall_hook(__FILE__, array('Abjad_Activator', 'uninstall'));
?>