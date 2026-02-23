<?php
/**
 * Cron job handlers
 */
class Abjad_Cron {
    
    public function __construct() {
        add_action('abjad_license_expiry_check', array($this, 'check_license_expiry'));
        add_action('abjad_cleanup_old_logs', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * بررسی انقضای مجوزها
     */
    public function check_license_expiry() {
        global $wpdb;
        
        // یافتن کاربران با مجوزهای منقضی شده
        $users_with_expired_licenses = $this->get_users_with_expired_licenses();
        
        foreach ($users_with_expired_licenses as $user_data) {
            $this->send_expiry_notification($user_data);
        }
        
        $this->log_cron_event('license_expiry_check', 
            sprintf('Checked %d users for expired licenses', count($users_with_expired_licenses)));
    }
    
    /**
     * پاک‌سازی لاگ‌های قدیمی
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'abjad_service_logs';
        $cleanup_days = get_option('abjad_log_retention_days', 30);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$cleanup_days days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        $this->log_cron_event('cleanup_old_logs', 
            sprintf('Cleaned up %d old log entries', $deleted));
    }
    
    private function get_users_with_expired_licenses() {
        // این تابع باید با دیتابیس سرویس ASP.NET ارتباط برقرار کند
        // در اینجا یک نمونه ساختگی برگردانده می‌شود
        
        $expired_users = array();
        
        // در واقعیت، این داده‌ها از API گرفته می‌شود
        // $api = new Abjad_API();
        // $expired_users = $api->get_expired_licenses();
        
        return $expired_users;
    }
    
    private function send_expiry_notification($user_data) {
        $user_id = $user_data['user_id'];
        $user_email = $user_data['email'];
        $expiry_date = $user_data['expiry_date'];
        
        $subject = 'تمدید مجوز سرویس ابجد';
        $message = $this->get_expiry_notification_template($user_data);
        
        wp_mail($user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
        
        // همچنین می‌توان نوتیفیکیشن درون‌سایتی نیز ارسال کرد
        $this->add_site_notification($user_id, 'مجوز سرویس ابجد شما منقضی شده است.');
    }
    
    private function get_expiry_notification_template($user_data) {
        return "
        <div style='font-family: Tahoma, sans-serif; direction: rtl; text-align: right;'>
            <h2 style='color: #dc3545;'>⚠️ مجوز سرویس ابجد شما منقضی شده است</h2>
            
            <p>مجوز دسترسی شما به سرویس‌های ابجد در تاریخ <strong>{$user_data['expiry_date']}</strong> منقضی شده است.</p>
            
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <h3>📋 اطلاعات مجوز:</h3>
                <ul>
                    <li>کاربر: {$user_data['display_name']}</li>
                    <li>ایمیل: {$user_data['email']}</li>
                    <li>تاریخ انقضا: {$user_data['expiry_date']}</li>
                </ul>
            </div>
            
            <p>برای تمدید مجوز و ادامه استفاده از سرویس‌ها، به صفحه <a href='" . get_permalink(wc_get_page_id('shop')) . "'>فروشگاه</a> مراجعه کنید.</p>
            
            <div style='margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;'>
                <strong>توجه:</strong> پس از انقضای مجوز، دسترسی شما به سرویس‌ها قطع خواهد شد.
            </div>
        </div>
        ";
    }
    
    private function add_site_notification($user_id, $message) {
        $notifications = get_user_meta($user_id, 'abjad_notifications', true);
        if (!is_array($notifications)) {
            $notifications = array();
        }
        
        $notifications[] = array(
            'message' => $message,
            'timestamp' => current_time('mysql'),
            'read' => false
        );
        
        update_user_meta($user_id, 'abjad_notifications', $notifications);
    }
    
    private function log_cron_event($event, $message) {
        if (get_option('abjad_enable_logging') === 'yes') {
            $log_entry = array(
                'event' => $event,
                'message' => $message,
                'timestamp' => current_time('mysql'),
                'memory_usage' => memory_get_usage(true)
            );
            
            $existing_logs = get_option('abjad_cron_logs', array());
            $existing_logs[] = $log_entry;
            
            // نگهداری فقط 100 مورد آخر
            if (count($existing_logs) > 100) {
                $existing_logs = array_slice($existing_logs, -100);
            }
            
            update_option('abjad_cron_logs', $existing_logs);
        }
    }
}

?>