<?php
/**
 * Template for service panel
 */
if (!defined('ABSPATH')) {
    exit;
}

global $abjad_services_available;
$abjad_services_available = array(
    'text_to_speech' => array(
        'name' => 'تبدیل متن به صوت',
        'description' => 'سرویس تبدیل متن فارسی به صوت با کیفیت بالا و لهجه طبیعی',
        'icon' => '🎵',
        'placeholder' => 'متن فارسی خود را برای تبدیل به صوت وارد کنید...',
        'max_chars' => 2000
    ),
    'text_analysis' => array(
        'name' => 'تحلیل متن',
        'description' => 'آنالیز پیشرفته متن، استخراج کلمات کلیدی و آنالیز احساسات',
        'icon' => '📊',
        'placeholder' => 'متن خود را برای تحلیل و آنالیز وارد کنید...',
        'max_chars' => 5000
    ),
    'content_generation' => array(
        'name' => 'تولید محتوا',
        'description' => 'تولید محتوای هوشمند بر اساس موضوع و کلمات کلیدی',
        'icon' => '✍️',
        'placeholder' => 'موضوع یا کلمات کلیدی مورد نظر را وارد کنید...',
        'max_chars' => 500
    ),
    'smart_translation' => array(
        'name' => 'ترجمه هوشمند',
        'description' => 'ترجمه تخصصی متن‌های فارسی به انگلیسی و بالعکس',
        'icon' => '🌐',
        'placeholder' => 'متن مورد نظر برای ترجمه را وارد کنید...',
        'max_chars' => 3000
    ),
    'text_summarization' => array(
        'name' => 'خلاصه‌سازی متن',
        'description' => 'خلاصه‌سازی هوشمند متون طولانی با حفظ مفهوم اصلی',
        'icon' => '📝',
        'placeholder' => 'متن طولانی خود را برای خلاصه‌سازی وارد کنید...',
        'max_chars' => 10000
    )
);
?>

<div class="abjad-service-panel">
    <!-- هدر پنل -->
    <div class="abjad-panel-header">
        <div class="header-content">
            <h1 class="panel-title">
                <span class="abjad-logo">🎯</span>
                پنل سرویس‌های ابجد
            </h1>
            <p class="panel-subtitle">دسترسی به سرویس‌های هوشمند پردازش متن</p>
        </div>
        
        <div class="header-actions">
            <button class="abjad-btn abjad-btn-secondary" id="abjad-refresh-status">
                <span class="btn-icon">🔄</span>
                بروزرسانی وضعیت
            </button>
            <button class="abjad-btn abjad-btn-info" id="abjad-keyboard-help">
                <span class="btn-icon">⌨️</span>
                راهنمای کلیدها
            </button>
        </div>
    </div>

    <!-- نوار وضعیت -->
    <div class="abjad-status-bar">
        <div class="status-items">
            <div class="status-item">
                <span class="status-label">وضعیت اتصال:</span>
                <span class="status-value connection-status" id="connection-status">
                    <span class="status-dot connecting"></span>
                    در حال بررسی...
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">مجوزهای فعال:</span>
                <span class="status-value" id="active-licenses-count"><?php echo count($licenses); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">استفاده امروز:</span>
                <span class="status-value" id="today-usage-count">0</span>
            </div>
        </div>
    </div>

    <!-- جستجو و فیلتر -->
    <div class="abjad-controls-row">
        <div class="search-box">
            <input type="text" id="abjad-service-search" placeholder="جستجو در سرویس‌ها..." class="search-input">
            <span class="search-icon">🔍</span>
        </div>
        
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">همه سرویس‌ها</button>
            <button class="filter-btn" data-filter="available">فقط فعال</button>
            <button class="filter-btn" data-filter="limited">دارای محدودیت</button>
        </div>
    </div>

    <!-- لیست مجوزها -->
    <div class="abjad-licenses-container">
        <?php if (empty($licenses)): ?>
            <div class="abjad-empty-state">
                <div class="empty-icon">📦</div>
                <h3>هیچ مجوز فعالی ندارید</h3>
                <p>برای دسترسی به سرویس‌های ابجد، یکی از پکیج‌های ما را خریداری کنید.</p>
                <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="abjad-btn abjad-btn-primary">
                    مشاهده پکیج‌ها
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($licenses as $license_index => $license): ?>
                <div class="license-card" data-license-id="<?php echo $license['id']; ?>">
                    <!-- هدر مجوز -->
                    <div class="license-header">
                        <div class="license-info">
                            <h3 class="license-title">
                                <span class="product-icon">📦</span>
                                <?php echo get_the_title($license['product_id']); ?>
                            </h3>
                            <div class="license-meta">
                                <span class="meta-item">
                                    <span class="meta-label">تاریخ انقضا:</span>
                                    <span class="meta-value expiry-date"><?php echo $license['expiry_date']; ?></span>
                                </span>
                                <span class="meta-item">
                                    <span class="meta-label">وضعیت:</span>
                                    <span class="meta-value status-badge <?php echo $license['is_active'] ? 'active' : 'expired'; ?>">
                                        <?php echo $license['is_active'] ? 'فعال' : 'منقضی شده'; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="license-actions">
                            <button class="abjad-btn abjad-btn-outline toggle-license-details">
                                <span class="btn-text">نمایش جزئیات</span>
                                <span class="toggle-icon">▼</span>
                            </button>
                        </div>
                    </div>

                    <!-- جزئیات مجوز (قابل collapse) -->
                    <div class="license-details" style="display: none;">
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">شناسه مجوز:</span>
                                <span class="detail-value license-token"><?php echo substr($license['token'], 0, 20) . '...'; ?></span>
                                <button class="copy-token-btn" data-token="<?php echo $license['token']; ?>">
                                    کپی
                                </button>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تاریخ ایجاد:</span>
                                <span class="detail-value"><?php echo $license['created_date']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تعداد استفاده کل:</span>
                                <span class="detail-value"><?php echo $license['total_used']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- سرویس‌های این مجوز -->
                    <div class="license-services">
                        <h4 class="services-title">سرویس‌های قابل دسترسی</h4>
                        
                        <div class="services-grid">
                            <?php foreach ($license['services'] as $service_key => $service): ?>
                                <?php 
                                $service_info = $abjad_services_available[$service_key] ?? array(
                                    'name' => $service_key,
                                    'description' => 'سرویس پردازش متن',
                                    'icon' => '⚙️',
                                    'placeholder' => 'متن خود را وارد کنید...'
                                );
                                ?>
                                
                                <?php
                                // منطق جدید برای محاسبه استفاده کلی
                                $total_limit = isset($service['total_limit']) ? (int)$service['total_limit'] : (isset($service['daily_limit']) ? (int)$service['daily_limit'] : 0);
                                $total_used = isset($service['total_used']) ? (int)$service['total_used'] : (isset($service['used_today']) ? (int)$service['used_today'] : 0);
                                $percentage = ($total_limit > 0) ? min(100, ($total_used / $total_limit) * 100) : 0;
                                $is_service_expired = ($total_limit > 0 && $total_used >= $total_limit);
                                ?>
                                <div class="service-item <?php if ($is_service_expired) echo 'service-disabled'; ?>" data-service="<?php echo $service_key; ?>"
                                     data-license-id="<?php echo $license['id']; ?>">

                                    <!-- هدر سرویس -->
                                    <div class="service-header">
                                        <div class="service-title">
                                            <span class="service-icon"><?php echo $service_info['icon']; ?></span>
                                            <h5><?php echo $service_info['name']; ?></h5>
                                        </div>

                                        <div class="service-stats">
                                            <div class="usage-info">
                                                <span class="usage-text">
                                                    <?php echo $total_used; ?> / <?php echo $total_limit; ?>
                                                </span>
                                                <span class="usage-label">میزان استفاده</span>
                                            </div>
                                            <div class="usage-bar">
                                                <div class="usage-progress"
                                                     style="width: <?php echo $percentage; ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- توضیحات سرویس -->
                                    <div class="service-description">
                                        <p><?php echo $service_info['description']; ?></p>
                                    </div>

                                    <!-- رابط کاربری سرویس -->
                                    <div class="service-interface">
                                        <?php if ($is_service_expired): ?>
                                            <div class="abjad-alert abjad-error">اعتبار این سرویس به اتمام رسیده است.</div>
                                        <?php else: ?>
                                            <div class="input-section">
                                                <textarea
                                                    class="service-textarea"
                                                    placeholder="<?php echo $service_info['placeholder']; ?>"
                                                    maxlength="<?php echo $service_info['max_chars'] ?? 5000; ?>"
                                                    data-service="<?php echo $service_key; ?>"
                                                ></textarea>

                                                <div class="textarea-footer">
                                                    <span class="char-counter">0/<?php echo $service_info['max_chars'] ?? 5000; ?> کاراکتر</span>
                                                    <button class="clear-btn abjad-btn-text">
                                                        پاک کردن
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="action-buttons">
                                                <button class="execute-btn abjad-btn abjad-btn-primary">
                                                    <span class="btn-icon">⚡</span>
                                                    اجرای سرویس
                                                </button>

                                                <div class="secondary-actions">
                                                    <button class="abjad-btn-text toggle-details-btn">
                                                        <span class="btn-icon">🔍</span>
                                                        تنظیمات پیشرفته
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- تنظیمات پیشرفته (قابل collapse) -->
                                            <div class="advanced-settings" style="display: none;">
                                                <div class="settings-grid">
                                                    <div class="setting-item">
                                                        <label>کیفیت خروجی:</label>
                                                        <select class="quality-select">
                                                            <option value="standard">استاندارد</option>
                                                            <option value="high">بالا</option>
                                                            <option value="premium">پریمیوم</option>
                                                        </select>
                                                    </div>
                                                    <div class="setting-item">
                                                        <label>سرعت پردازش:</label>
                                                        <select class="speed-select">
                                                            <option value="normal">عادی</option>
                                                            <option value="fast">سریع</option>
                                                            <option value="turbo">توربو</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- نتیجه سرویس -->
                                            <div class="service-result"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- پاورش توسط ابجد -->
    <div class="abjad-footer">
        <div class="footer-content">
            <p class="powered-by">
                <span>پشتیبانی شده توسط</span>
                <span class="abjad-brand">سرویس ابجد</span>
            </p>
            <div class="footer-links">
                <a href="<?php echo site_url('/help'); ?>" class="footer-link">راهنما</a>
                <a href="<?php echo site_url('/contact'); ?>" class="footer-link">پشتیبانی</a>
                <a href="<?php echo site_url('/terms'); ?>" class="footer-link">قوانین</a>
            </div>
        </div>
    </div>
</div>

<!-- مودال راهنمای کلیدها -->
<div id="abjad-keyboard-modal" class="abjad-modal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3>⌨️ راهنمای کلیدهای میانبر</h3>
        
        <div class="keyboard-shortcuts">
            <div class="shortcut-item">
                <span class="key-combination">
                    <kbd>Ctrl</kbd> + <kbd>Enter</kbd>
                </span>
                <span class="shortcut-description">اجرای سرویس</span>
            </div>
            <div class="shortcut-item">
                <span class="key-combination">
                    <kbd>Esc</kbd>
                </span>
                <span class="shortcut-description">پاک کردن متن</span>
            </div>
            <div class="shortcut-item">
                <span class="key-combination">
                    <kbd>Ctrl</kbd> + <kbd>S</kbd>
                </span>
                <span class="shortcut-description">ذخیره پیش‌نویس</span>
            </div>
            <div class="shortcut-item">
                <span class="key-combination">
                    <kbd>Ctrl</kbd> + <kbd>D</kbd>
                </span>
                <span class="shortcut-description">پاک کردن همه پیش‌نویس‌ها</span>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="abjad-btn abjad-btn-secondary modal-close">بستن</button>
        </div>
    </div>
</div>

<!-- مودال کپی توکن -->
<div id="abjad-token-modal" class="abjad-modal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3>🔑 کپی توکن مجوز</h3>
        
        <div class="token-display">
            <code id="token-value" class="token-code"></code>
            <button class="abjad-btn abjad-btn-primary" id="copy-token-full">
                کپی توکن
            </button>
        </div>
        
        <div class="token-warning">
            <p>⚠️ این توکن را در اختیار کسی قرار ندهید. این توکن دسترسی کامل به سرویس‌های شما را فراهم می‌کند.</p>
        </div>
        
        <div class="modal-footer">
            <button class="abjad-btn abjad-btn-secondary modal-close">بستن</button>
        </div>
    </div>
</div>