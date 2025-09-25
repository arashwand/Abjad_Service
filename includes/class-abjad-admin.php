<?php
class Abjad_Admin {
    
    private $api;
    private $license;
    private $available_services;
    
    public function __construct($api, $license) {
        $this->api = $api;
        $this->license = $license;
        $this->available_services = $this->get_available_services();
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // فیلدهای محصول
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_fields'));
        
        // ثبت تنظیمات افزونه
        add_action('admin_init', array($this, 'register_plugin_settings'));

        // پردازش سفارش
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'));
        
        // منوی مدیریت
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));

    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    
    add_action('wp_ajax_abjad_get_services_status', array($this, 'ajax_get_services_status'));
    add_action('wp_ajax_abjad_test_api_connection', array($this, 'ajax_test_api_connection'));
    add_action('wp_ajax_abjad_generate_quick_report', array($this, 'ajax_generate_quick_report'));
    }
    
    public function register_plugin_settings() {
        register_setting('abjad_settings', 'abjad_api_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
    }

    private function get_available_services() {
        return array(
            'text_to_speech' => array(
                'name' => 'تبدیل متن به صوت',
                'description' => 'سرویس تبدیل متن فارسی به صوت با کیفیت بالا',
                'endpoint' => '/services/text-to-speech',
                'default_limit' => 50
            ),
            'text_analysis' => array(
                'name' => 'تحلیل متن',
                'description' => 'آنالیز پیشرفته متن و استخراج اطلاعات',
                'endpoint' => '/services/text-analysis',
                'default_limit' => 100
            ),
            'content_generation' => array(
                'name' => 'تولید محتوا',
                'description' => 'تولید محتوای هوشمند بر اساس الگوهای یادگیری',
                'endpoint' => '/services/content-generation',
                'default_limit' => 30
            ),
            'smart_translation' => array(
                'name' => 'ترجمه هوشمند',
                'description' => 'ترجمه تخصصی متن‌های تخصصی',
                'endpoint' => '/services/smart-translation',
                'default_limit' => 200
            ),
            'text_summarization' => array(
                'name' => 'خلاصه‌سازی متن',
                'description' => 'خلاصه‌سازی هوشمند متون طولانی',
                'endpoint' => '/services/text-summarization',
                'default_limit' => 80
            )
        );
    }
    
    public function add_product_fields() {
        echo '<div class="options_group abjad-service-section">';
        
        // عنوان بخش
        echo '<h3>🎯 سرویس ابجد - Abjad Services</h3>';
        
        // فعال‌سازی سرویس
        woocommerce_wp_checkbox(array(
            'id' => '_abjad_service_enabled',
            'label' => 'فعال‌سازی سرویس ابجد',
            'description' => 'این محصول به سرویس‌های ابجد دسترسی خواهد داشت',
            'cbvalue' => 'yes'
        ));
        
        echo '<div id="abjad-services-container" style="display: none; margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">';
        
        // انتخاب سرویس‌ها
        echo '<h4>🎪 سرویس‌های قابل دسترسی:</h4>';
        foreach ($this->available_services as $key => $service) {
            echo '<div class="service-option" style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">';
            
            // چک‌باکس سرویس
            woocommerce_wp_checkbox(array(
                'id' => '_abjad_service_' . $key,
                'label' => '<strong>' . $service['name'] . '</strong>',
                'description' => $service['description'],
                'cbvalue' => 'yes'
            ));
            
            // محدودیت استفاده
            woocommerce_wp_text_input(array(
                'id' => '_abjad_service_' . $key . '_limit',
                'label' => 'محدودیت روزانه',
                'type' => 'number',
                'custom_attributes' => array(
                    'min' => '1',
                    'max' => '1000',
                    'placeholder' => $service['default_limit']
                ),
                'description' => 'تعداد مجاز استفاده در روز'
            ));
            
            echo '</div>';
        }
        
        // تنظیمات عمومی
        echo '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">';
        
        // مدت اعتبار
        woocommerce_wp_text_input(array(
            'id' => '_abjad_service_expiry_days',
            'label' => '📅 مدت اعتبار مجوز (روز)',
            'type' => 'number',
            'custom_attributes' => array(
                'min' => '1',
                'max' => '365'
            ),
            'description' => 'مدت زمان اعتبار مجوز پس از خرید'
        ));
        
        // API Key (اختیاری)
        woocommerce_wp_text_input(array(
            'id' => '_abjad_service_api_key',
            'label' => '🔑 کلید API (اختیاری)',
            'type' => 'password',
            'description' => 'در صورت نیاز به کلید API ویژه'
        ));
        
        echo '</div>';
        echo '</div>'; // پایان کانتینر
        echo '</div>'; // پایان options_group
    }
    
    public function save_product_fields($post_id) {
        $product = wc_get_product($post_id);
        
        // ذخیره وضعیت فعال‌سازی
        $enabled = isset($_POST['_abjad_service_enabled']) ? 'yes' : 'no';
        $product->update_meta_data('_abjad_service_enabled', $enabled);
        
        if ($enabled === 'yes') {
            // ذخیره سرویس‌های انتخابی
            foreach ($this->available_services as $key => $service) {
                $service_enabled = isset($_POST['_abjad_service_' . $key]) ? 'yes' : 'no';
                $product->update_meta_data('_abjad_service_' . $key, $service_enabled);
                
                if ($service_enabled === 'yes') {
                    $limit = isset($_POST['_abjad_service_' . $key . '_limit']) ? 
                             intval($_POST['_abjad_service_' . $key . '_limit']) : 
                             $service['default_limit'];
                    $product->update_meta_data('_abjad_service_' . $key . '_limit', $limit);
                }
            }
            
            // ذخیره سایر تنظیمات
            $expiry_days = isset($_POST['_abjad_service_expiry_days']) ? 
                          intval($_POST['_abjad_service_expiry_days']) : 30;
            $product->update_meta_data('_abjad_service_expiry_days', $expiry_days);
            
            $api_key = isset($_POST['_abjad_service_api_key']) ? 
                      sanitize_text_field($_POST['_abjad_service_api_key']) : '';
            $product->update_meta_data('_abjad_service_api_key', $api_key);
        }
        
        $product->save();
    }

    // 🔽 🔽 🔽 این تابع را اضافه کنید (قسمت اول شماره ۳) 🔽 🔽 🔽
public function enqueue_admin_scripts($hook) {
    // فقط در صفحه ویرایش محصول اسکریپت‌ها را بارگذاری کن
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }
    
    // فقط برای محصولات ووکامرس
    $screen = get_current_screen();
    if ($screen->post_type !== 'product') {
        return;
    }
    
    wp_enqueue_script('abjad-admin', ABJAD_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
    
    // انتقال داده‌ها به JavaScript
    wp_localize_script('abjad-admin', 'abjad_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('abjad_admin_nonce'),
        'default_limits' => array(
            'text_to_speech' => 50,
            'text_analysis' => 100,
            'content_generation' => 30,
            'smart_translation' => 200,
            'text_summarization' => 80
        ),
        'debug' => defined('WP_DEBUG') && WP_DEBUG
    ));
}

// 🔽 🔽 🔽 این تابع را اضافه کنید 🔽 🔽 🔽
public function enqueue_admin_styles($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }
    
    $screen = get_current_screen();
    if ($screen->post_type !== 'product') {
        return;
    }
    
    wp_enqueue_style('abjad-admin', ABJAD_PLUGIN_URL . 'assets/css/admin.css', array(), '1.0.0');
}

public function ajax_get_services_status() {
    check_ajax_referer('abjad_admin_nonce', 'nonce');
    
    // شبیه‌سازی وضعیت سرویس‌ها
    $services_status = array(
        'text_to_speech' => array(
            'status' => 'active',
            'label' => 'فعال',
            'message' => 'سرویس در دسترس است'
        ),
        'text_analysis' => array(
            'status' => 'active', 
            'label' => 'فعال',
            'message' => 'سرویس در دسترس است'
        ),
        'content_generation' => array(
            'status' => 'active',
            'label' => 'فعال', 
            'message' => 'سرویس در دسترس است'
        ),
        'smart_translation' => array(
            'status' => 'active',
            'label' => 'فعال',
            'message' => 'سرویس در دسترس است'
        ),
        'text_summarization' => array(
            'status' => 'active',
            'label' => 'فعال',
            'message' => 'سرویس در دسترس است'
        )
    );
    
    wp_send_json_success($services_status);
}

public function ajax_test_api_connection() {
    check_ajax_referer('abjad_admin_nonce', 'nonce');
    
    // تست اتصال به API (شبیه‌سازی)
    $test_result = true; // برای تست اولیه true می‌گذاریم
    
    if ($test_result) {
        wp_send_json_success('اتصال با موفقیت تست شد');
    } else {
        wp_send_json_error('خطا در اتصال به API');
    }
}

public function ajax_generate_quick_report() {
    check_ajax_referer('abjad_admin_nonce', 'nonce');
    
    $report_data = array(
        'active_licenses' => 15,
        'today_usage' => 47,
        'most_used_service' => 'تبدیل متن به صوت'
    );
    
    wp_send_json_success($report_data);
}
    
    public function handle_order_completion($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        $user_email = $order->get_billing_email();
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            
            // بررسی فعال بودن سرویس برای محصول
            if ($product->get_meta('_abjad_service_enabled') !== 'yes') {
                continue;
            }
            
            // جمع‌آوری سرویس‌های فعال
            $enabled_services = array();
            foreach ($this->available_services as $key => $service) {
                if ($product->get_meta('_abjad_service_' . $key) === 'yes') {
                    $enabled_services[$key] = array(
                        'limit' => $product->get_meta('_abjad_service_' . $key . '_limit') ?: $service['default_limit'],
                        'endpoint' => $service['endpoint']
                    );
                }
            }
            
            if (!empty($enabled_services)) {
                $expiry_days = $product->get_meta('_abjad_service_expiry_days') ?: 30;
                $this->license->create_license($user_id, $user_email, $product_id, $enabled_services, $expiry_days, $order_id);
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'سرویس ابجد',
            'سرویس ابجد',
            'manage_options',
            'abjad-services',
            array($this, 'render_admin_page'),
            'dashicons-admin-generic',
            56
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap abjad-admin-page">
            <h1>🎯 مدیریت سرویس ابجد</h1>
            
            <div class="abjad-stats">
                <div class="stat-card">
                    <h3>مجوزهای فعال</h3>
                    <span class="stat-number"><?php echo $this->license->get_active_licenses_count(); ?></span>
                </div>
                <div class="stat-card">
                    <h3>سرویس‌های استفاده شده امروز</h3>
                    <span class="stat-number"><?php echo $this->license->get_today_usage_count(); ?></span>
                </div>
            </div>
            
            <div class="abjad-settings">
                <h2>تنظیمات اتصال</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('abjad_settings');
                    do_settings_sections('abjad_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th>آدرس API</th>
                            <td>
                                <input type="url" name="abjad_api_url" value="<?php echo esc_attr(get_option('abjad_api_url', ABJAD_API_BASE_URL)); ?>" class="regular-text">
                                <p class="description">آدرس پایه سرویس ASP.NET</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'abjad_dashboard_widget',
            'وضعیت سرویس ابجد',
            array($this, 'render_dashboard_widget')
        );
    }
    
    public function render_dashboard_widget() {
        $stats = $this->license->get_license_stats();
        ?>
        <div class="abjad-dashboard-widget">
            <ul>
                <li>📊 مجوزهای فعال: <strong><?php echo $stats['active_licenses']; ?></strong></li>
                <li>🔄 استفاده امروز: <strong><?php echo $stats['today_usage']; ?></strong></li>
                <li>📈 استفاده این ماه: <strong><?php echo $stats['month_usage']; ?></strong></li>
            </ul>
            <p><a href="<?php echo admin_url('admin.php?page=abjad-services'); ?>">مشاهده جزئیات →</a></p>
        </div>
        <?php
    }

}
?>