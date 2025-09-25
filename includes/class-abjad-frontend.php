<?php
class Abjad_Frontend {
    
    private $api;
    private $license;
    
    public function __construct($api, $license) {
        $this->api = $api;
        $this->license = $license;
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_abjad_execute_service', array($this, 'ajax_execute_service'));
        add_action('wp_ajax_nopriv_abjad_execute_service', array($this, 'ajax_no_permission'));
        add_action('wp_ajax_abjad_get_license_status', array($this, 'ajax_get_license_status'));
    }
    
    public function render_service_panel($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }
        
        $user_id = get_current_user_id();
        $licenses = $this->license->get_user_licenses($user_id);
        
        if (empty($licenses)) {
            return $this->render_no_license();
        }
        
        ob_start();
        include ABJAD_PLUGIN_PATH . 'templates/service-panel.php';
        return ob_get_clean();
    }
    
    public function render_service_status($atts) {
        if (!is_user_logged_in()) {
            return '<p class="abjad-alert">لطفا وارد حساب کاربری خود شوید.</p>';
        }
        
        $user_id = get_current_user_id();
        $licenses = $this->license->get_user_licenses($user_id);
        
        ob_start();
        ?>
        <div class="abjad-status-panel">
            <h3>وضعیت سرویس‌های ابجد</h3>
            <?php if (empty($licenses)): ?>
                <p>شما هیچ مجوز فعالی ندارید.</p>
            <?php else: ?>
                <?php foreach ($licenses as $license): ?>
                <div class="license-status-card">
                    <h4><?php echo get_the_title($license['product_id']); ?></h4>
                    <div class="service-status-list">
                        <?php foreach ($license['services'] as $service): ?>
                        <div class="service-status-item">
                            <span class="service-name"><?php echo $service['name']; ?></span>
                            <?php
                            // فرض می‌کنیم API فیلدهای total_used و total_limit را برمی‌گرداند
                            $total_limit = isset($service['total_limit']) ? (int)$service['total_limit'] : (isset($service['daily_limit']) ? (int)$service['daily_limit'] : 0);
                            $total_used = isset($service['total_used']) ? (int)$service['total_used'] : (isset($service['used_today']) ? (int)$service['used_today'] : 0);
                            $percentage = ($total_limit > 0) ? min(($total_used / $total_limit) * 100, 100) : 0;
                            ?>
                            <span class="service-usage">تعداد استفاده: <?php echo $total_used; ?> / <?php echo $total_limit; ?></span>
                            <div class="usage-bar">
                                <div class="usage-progress" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <?php if ($total_limit > 0 && $total_used >= $total_limit): ?>
                                <div class="abjad-alert abjad-error" style="margin-top: 10px;">اعتبار این سرویس به اتمام رسیده است.</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_login_required() {
        return '
        <div class="abjad-login-required">
            <div class="abjad-alert abjad-warning">
                <h4>⚠️ دسترسی محدود</h4>
                <p>برای استفاده از سرویس‌های ابجد باید وارد حساب کاربری خود شوید.</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="abjad-btn">ورود به حساب</a>
            </div>
        </div>';
    }
    
    private function render_no_license() {
        return '
        <div class="abjad-no-license">
            <div class="abjad-alert abjad-info">
                <h4>📦 سرویس فعال ندارید</h4>
                <p>شما هیچ مجوز فعالی برای استفاده از سرویس‌های ابجد ندارید.</p>
                <a href="' . get_permalink(wc_get_page_id('shop')) . '" class="abjad-btn">خرید سرویس</a>
            </div>
        </div>';
    }
    
    public function ajax_execute_service() {
        // بررسی nonce
        if (!wp_verify_nonce($_POST['nonce'], 'abjad_nonce')) {
            wp_die('خطای امنیتی');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('لطفا وارد حساب کاربری خود شوید');
        }
        
        $user_id = get_current_user_id();
        $service_key = sanitize_text_field($_POST['service_key']);
        $input_text = sanitize_textarea_field($_POST['input_text']);

        // اعتبارسنجی سمت سرور قبل از ارسال به API
        $licenses = $this->license->get_user_licenses($user_id);
        $can_execute = false;
        if (!empty($licenses)) {
            foreach ($licenses as $license) {
                if (isset($license['services'][$service_key])) {
                    $service = $license['services'][$service_key];
                    $total_limit = isset($service['total_limit']) ? (int)$service['total_limit'] : (isset($service['daily_limit']) ? (int)$service['daily_limit'] : 0);
                    $total_used = isset($service['total_used']) ? (int)$service['total_used'] : (isset($service['used_today']) ? (int)$service['used_today'] : 0);

                    if ($total_limit === 0 || $total_used < $total_limit) { // 0 means unlimited
                        $can_execute = true;
                        break;
                    }
                }
            }
        }

        if (!$can_execute) {
            wp_send_json_error(array('error' => 'اعتبار شما برای استفاده از این سرویس به اتمام رسیده است.'));
            return;
        }
        
        // اجرای سرویس از طریق API
        $result = $this->api->execute_service($user_id, $service_key, $input_text);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    public function ajax_get_license_status() {
        if (!is_user_logged_in()) {
            wp_send_json_error('لطفا وارد حساب کاربری خود شوید');
        }
        
        $user_id = get_current_user_id();
        $status = $this->license->get_user_license_status($user_id);
        
        wp_send_json_success($status);
    }
    
    public function ajax_no_permission() {
        wp_send_json_error('دسترسی غیرمجاز');
    }
}
?>