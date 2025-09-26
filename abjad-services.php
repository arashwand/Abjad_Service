<?php
/**
 * Plugin Name: سرویس ابجد
 * Plugin URI: https://vip.elm-asma.ir
 * Description: یکپارچه‌سازی سرویس‌های هوشمند ابجد با وردپرس
 * Version: 1.0.9
 * Author: آرش کارگر
 * Text Domain: abjad-services
 * Domain Path: /languages
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌ها
define('ABJAD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABJAD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ABJAD_API_BASE_URL', 'https://your-aspnet-app.com/api'); // تغییر به آدرس واقعی

// بررسی فعال بودن ووکامرس
register_activation_hook(__FILE__, 'abjad_check_woocommerce_active');

function abjad_check_woocommerce_active() {
    // اطمینان از بارگذاری فایل مورد نیاز برای تابع is_plugin_active
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('برای استفاده از افزونه سرویس ابجد، باید ووکامرس فعال باشد. لطفا ابتدا ووکامرس را نصب و فعال کنید.');
    }
}

// بارگذاری فایل‌های مورد نیاز
require_once ABJAD_PLUGIN_PATH . 'includes/class-abjad-activator.php';
require_once ABJAD_PLUGIN_PATH . 'includes/class-abjad-cron.php';
require_once ABJAD_PLUGIN_PATH . 'includes/class-abjad-admin.php';
require_once ABJAD_PLUGIN_PATH . 'includes/class-abjad-frontend.php';
require_once ABJAD_PLUGIN_PATH . 'includes/class-abjad-api.php';
require_once ABJAD_PLUGIN_PATH . 'includes/class-abjad-license.php';

// ثبت هوک‌های فعال‌سازی و غیرفعال‌سازی
register_activation_hook(__FILE__, array('Abjad_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Abjad_Activator', 'deactivate'));

// راه‌اندازی افزونه
class AbjadServices {
    
    private static $instance = null;
    public $admin;
    public $frontend;
    public $api;
    public $license;
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // بارگذاری فایل‌های ترجمه
        load_plugin_textdomain('abjad-services', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // راه‌اندازی کامپوننت‌ها
        $this->api = new Abjad_API();
        $this->license = new Abjad_License();
        $this->admin = new Abjad_Admin($this->api, $this->license);
        $this->frontend = new Abjad_Frontend($this->api, $this->license);
        
        // راه‌اندازی وظایف پس‌زمینه
        if (defined('DOING_CRON') && DOING_CRON) {
            new Abjad_Cron();
        }

        // ثبت هوک‌های عمومی
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function register_shortcodes() {
        add_shortcode('abjad_service_panel', array($this->frontend, 'render_service_panel'));
        add_shortcode('abjad_service_status', array($this->frontend, 'render_service_status'));
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('abjad-frontend', ABJAD_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');
        wp_enqueue_script('abjad-frontend', ABJAD_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), '1.0.0', true);
        
        // انتقال داده‌ها به JavaScript
        wp_localize_script('abjad-frontend', 'abjad_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_base' => ABJAD_API_BASE_URL,
            'nonce' => wp_create_nonce('abjad_nonce'),
            'i18n' => array(
                'processing' => 'در حال پردازش...',
                'error' => 'خطا رخ داد',
                'success' => 'عملیات موفق بود'
            )
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        wp_enqueue_style('abjad-admin', ABJAD_PLUGIN_URL . 'assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('abjad-admin', ABJAD_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
    }
}

// راه‌اندازی افزونه
function abjad_services() {
    return AbjadServices::get_instance();
}

// شروع اجرا
add_action('plugins_loaded', 'abjad_services');
?>