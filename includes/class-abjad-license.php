<?php
class Abjad_License {
    
    public function create_license($user_id, $user_email, $product_id, $services, $expiry_days, $order_id) {
        // تولید توکن منحصر به فرد
        $token = $this->generate_license_token($user_id, $order_id);
        
        // داده‌های مجوز
        $license_data = array(
            'userId' => strval($user_id),
            'userEmail' => $user_email,
            'productId' => strval($product_id),
            'token' => $token,
            'services' => $services,
            'expiryDate' => date('Y-m-d', strtotime("+$expiry_days days")),
            'createdAt' => current_time('mysql')
        );
        
        // ارسال به سرویس ASP.NET
        $api = new Abjad_API();
        $success = $api->create_license($license_data);
        
        if ($success) {
            // ذخیره توکن در پروفایل کاربر
            update_user_meta($user_id, 'abjad_service_token', $token);
            update_user_meta($user_id, 'abjad_license_data', $license_data);
            
            // ارسال ایمیل به کاربر
            $this->send_license_email($user_email, $token, $services, $expiry_days);
            
            return true;
        }
        
        return false;
    }
    
    public function get_user_licenses($user_id) {
        $token = get_user_meta($user_id, 'abjad_service_token', true);
        if (!$token) {
            return array();
        }
        
        // دریافت اطلاعات از سرویس ASP.NET
        $api = new Abjad_API();
        $license_info = $api->validate_license($token);
        
        if (!$license_info || !$license_info['valid']) {
            return array();
        }
        
        return array($license_info);
    }
    
    private function generate_license_token($user_id, $order_id) {
        return md5($user_id . '_' . $order_id . '_' . time() . '_' . wp_generate_password(16, false));
    }
    
    private function send_license_email($email, $token, $services, $expiry_days) {
        $subject = 'فعال‌سازی سرویس ابجد - Abjad Services';
        
        $services_list = '';
        foreach ($services as $key => $service) {
            $services_list .= "<li>{$service['name']} - {$service['limit']} استفاده روزانه</li>";
        }
        
        $message = "
        <div style='font-family: Tahoma, sans-serif; direction: rtl; text-align: right;'>
            <h2 style='color: #007cba;'>🎯 سرویس ابجد شما فعال شد!</h2>
            
            <p>مجوز دسترسی به سرویس‌های ابجد با موفقیت فعال گردید.</p>
            
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <h3>📋 جزئیات مجوز:</h3>
                <ul>
                    <li><strong>توکن مجوز:</strong> $token</li>
                    <li><strong>مدت اعتبار:</strong> $expiry_days روز</li>
                    <li><strong>سرویس‌های فعال:</strong></li>
                    $services_list
                </ul>
            </div>
            
            <p>برای استفاده از سرویس‌ها به صفحه <a href='" . get_permalink(get_page_by_path('my-services')) . "'>سرویس من</a> مراجعه کنید.</p>
            
            <hr style='margin: 20px 0;'>
            <p style='color: #666; font-size: 12px;'>این ایمیل به صورت خودکار ارسال شده است.</p>
        </div>
        ";
        
        wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
    
    public function get_active_licenses_count() {
        // شمارش مجوزهای فعال از دیتابیس
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'abjad_service_token'
        ");
    }
    
    public function get_today_usage_count() {
        // این تابع نیاز به پیاده‌سازی با دیتابیس سرویس ASP.NET دارد
        return 0; // مقدار موقت
    }
    
    public function get_license_stats() {
        return array(
            'active_licenses' => $this->get_active_licenses_count(),
            'today_usage' => $this->get_today_usage_count(),
            'month_usage' => 0 // پیاده‌سازی مشابه
        );
    }
}
?>