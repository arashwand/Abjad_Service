<?php
/**
 * Uninstall script for Abjad Services plugin
 * 
 * @package AbjadServices
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// حذف options از دیتابیس
$options_to_delete = array(
    'abjad_api_url',
    'abjad_settings',
    'abjad_version',
    'abjad_activation_date',
    'abjad_license_key'
);

foreach ($options_to_delete as $option) {
    delete_option($option);
    delete_site_option($option); // برای multisite
}

// حذف user metaها
global $wpdb;
$user_meta_keys = array(
    'abjad_service_token',
    'abjad_license_data',
    'abjad_last_service_usage',
    'abjad_user_settings'
);

foreach ($user_meta_keys as $meta_key) {
    $wpdb->delete(
        $wpdb->usermeta,
        array('meta_key' => $meta_key),
        array('%s')
    );
}

// حذف post metaهای محصولات
$post_meta_keys = array(
    '_abjad_service_enabled',
    '_abjad_service_expiry_days',
    '_abjad_service_api_key'
);

// کلیدهای سرویس‌ها (text_to_speech, text_analysis, etc.)
$service_keys = array('text_to_speech', 'text_analysis', 'content_generation', 'smart_translation', 'text_summarization');
foreach ($service_keys as $service_key) {
    $post_meta_keys[] = '_abjad_service_' . $service_key;
    $post_meta_keys[] = '_abjad_service_' . $service_key . '_limit';
}

foreach ($post_meta_keys as $meta_key) {
    $wpdb->delete(
        $wpdb->postmeta,
        array('meta_key' => $meta_key),
        array('%s')
    );
}

// حذف custom tables (اگر ایجاد کرده بودید)
$custom_tables = array(
    $wpdb->prefix . 'abjad_licenses',
    $wpdb->prefix . 'abjad_usage_logs',
    $wpdb->prefix . 'abjad_service_stats'
);

foreach ($custom_tables as $table_name) {
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}

// حذف cron jobs
$cron_hooks = array(
    'abjad_daily_usage_reset',
    'abjad_license_expiry_check',
    'abjad_weekly_report'
);

foreach ($cron_hooks as $hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
    }
}

// حذف transients
$transients = array(
    'abjad_services_status',
    'abjad_api_connection_test',
    'abjad_license_stats'
);

foreach ($transients as $transient) {
    delete_transient($transient);
    delete_site_transient($transient); // برای multisite
}

// لاگ کردن حذف افزونه (اختیاری)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Abjad Services plugin uninstalled successfully');
}

// نمایش پیغام خداحافظی (اختیاری - فقط در حالت دیباگ)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p>سرویس ابجد با موفقیت حذف شد. امیدواریم باز هم از خدمات ما استفاده کنید.</p></div>';
    });
}
?>