<?php
/**
 * Template for individual service item
 * Used for rendering each service separately
 */
if (!defined('ABSPATH')) {
    exit;
}

// این تابع برای رندر کردن تک تک سرویس‌ها استفاده می‌شود
function abjad_render_service_item($service_key, $service_data, $license_data = array()) {
    global $abjad_services_available;
    
    $service_info = $abjad_services_available[$service_key] ?? array(
        'name' => $service_key,
        'description' => 'سرویس پردازش متن',
        'icon' => '⚙️',
        'placeholder' => 'متن خود را وارد کنید...',
        'max_chars' => 5000
    );
    
    $usage_percentage = 0;
    $remaining_usage = 0;
    
    if (!empty($license_data) && isset($license_data['services'][$service_key])) {
        $service_usage = $license_data['services'][$service_key];
        $used_today = $service_usage['used_today'];
        $daily_limit = $service_usage['daily_limit'];
        $usage_percentage = min(100, ($used_today / $daily_limit) * 100);
        $remaining_usage = $daily_limit - $used_today;
    }
    
    ob_start();
    ?>
    
    <div class="service-item-wrapper" data-service="<?php echo esc_attr($service_key); ?>">
        <div class="service-item-card">
            <!-- هدر سرویس -->
            <div class="service-card-header">
                <div class="service-icon-title">
                    <span class="service-main-icon"><?php echo $service_info['icon']; ?></span>
                    <div class="service-title-section">
                        <h4 class="service-name"><?php echo $service_info['name']; ?></h4>
                        <span class="service-badge">سرویس ابجد</span>
                    </div>
                </div>
                
                <?php if (!empty($license_data)): ?>
                <div class="service-usage-widget">
                    <div class="usage-meter">
                        <div class="meter-label">امروز</div>
                        <div class="meter-bar">
                            <div class="meter-fill" style="width: <?php echo $usage_percentage; ?>%"></div>
                        </div>
                        <div class="meter-numbers">
                            <span class="used"><?php echo $used_today; ?></span>
                            <span class="separator">/</span>
                            <span class="total"><?php echo $daily_limit; ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- توضیحات سرویس -->
            <div class="service-card-description">
                <p><?php echo $service_info['description']; ?></p>
            </div>

            <!-- وضعیت دسترسی -->
            <div class="service-access-status">
                <?php if (empty($license_data)): ?>
                    <div class="access-status not-available">
                        <span class="status-icon">🔒</span>
                        <span class="status-text">برای دسترسی نیاز به خرید مجوز دارید</span>
                        <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="access-link">
                            خرید مجوز
                        </a>
                    </div>
                <?php elseif ($remaining_usage <= 0): ?>
                    <div class="access-status limited">
                        <span class="status-icon">⏸️</span>
                        <span class="status-text">محدودیت استفاده امروز به پایان رسیده</span>
                    </div>
                <?php else: ?>
                    <div class="access-status available">
                        <span class="status-icon">✅</span>
                        <span class="status-text">
                            قابل استفاده - 
                            <strong><?php echo $remaining_usage; ?></strong> 
                            بار باقیمانده
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- رابط کاربری سرویس (فقط اگر مجوز فعال باشد) -->
            <?php if (!empty($license_data) && $remaining_usage > 0): ?>
            <div class="service-card-interface">
                <!-- نوار ابزار ویرایشگر -->
                <div class="editor-toolbar">
                    <div class="toolbar-left">
                        <button class="toolbar-btn format-btn" data-format="bold" title="پررنگ">
                            <strong>B</strong>
                        </button>
                        <button class="toolbar-btn format-btn" data-format="italic" title="ایتالیک">
                            <em>I</em>
                        </button>
                        <button class="toolbar-btn clear-format-btn" title="پاک کردن فرمت">
                            🧹
                        </button>
                    </div>
                    <div class="toolbar-right">
                        <span class="word-count">کلمات: 0</span>
                    </div>
                </div>

                <!-- ویرایشگر متن -->
                <div class="service-editor">
                    <textarea 
                        class="service-text-editor" 
                        placeholder="<?php echo $service_info['placeholder']; ?>"
                        data-service="<?php echo $service_key; ?>"
                        data-license-token="<?php echo $license_data['token']; ?>"
                        maxlength="<?php echo $service_info['max_chars']; ?>"
                    ></textarea>
                    
                    <div class="editor-footer">
                        <div class="footer-left">
                            <span class="char-count">0/<?php echo $service_info['max_chars']; ?> کاراکتر</span>
                            <span class="line-count">خط: 1</span>
                        </div>
                        <div class="footer-right">
                            <button class="editor-btn clear-editor" title="پاک کردن">
                                🗑️
                            </button>
                            <button class="editor-btn save-draft" title="ذخیره پیش‌نویس">
                                💾
                            </button>
                        </div>
                    </div>
                </div>

                <!-- تنظیمات سرویس -->
                <div class="service-settings">
                    <div class="settings-toggle">
                        <button class="settings-toggle-btn">
                            <span class="toggle-icon">⚙️</span>
                            تنظیمات پیشرفته
                            <span class="arrow">▼</span>
                        </button>
                    </div>
                    
                    <div class="settings-content" style="display: none;">
                        <div class="settings-grid">
                            <!-- تنظیمات مخصوص هر سرویس -->
                            <?php if ($service_key === 'text_to_speech'): ?>
                                <div class="setting-group">
                                    <label>صدای گوینده:</label>
                                    <select class="voice-select">
                                        <option value="male1">مرد - صدای ۱</option>
                                        <option value="male2">مرد - صدای ۲</option>
                                        <option value="female1">زن - صدای ۱</option>
                                        <option value="female2">زن - صدای ۲</option>
                                    </select>
                                </div>
                                <div class="setting-group">
                                    <label>سرعت گفتار:</label>
                                    <select class="speed-select">
                                        <option value="slow">آهسته</option>
                                        <option value="normal" selected>عادی</option>
                                        <option value="fast">سریع</option>
                                    </select>
                                </div>
                            <?php elseif ($service_key === 'text_analysis'): ?>
                                <div class="setting-group">
                                    <label>سطح تحلیل:</label>
                                    <select class="analysis-level">
                                        <option value="basic">پایه</option>
                                        <option value="advanced" selected>پیشرفته</option>
                                        <option value="expert">تخصصی</option>
                                    </select>
                                </div>
                                <div class="setting-group">
                                    <label>خروجی شامل:</label>
                                    <div class="checkbox-group">
                                        <label><input type="checkbox" name="output_keywords" checked> کلمات کلیدی</label>
                                        <label><input type="checkbox" name="output_sentiment" checked> تحلیل احساسات</label>
                                        <label><input type="checkbox" name="output_entities"> موجودیت‌ها</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- تنظیمات عمومی -->
                            <div class="setting-group">
                                <label>قالب خروجی:</label>
                                <select class="output-format">
                                    <option value="text">متنی</option>
                                    <option value="json">JSON</option>
                                    <option value="html">HTML</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- دکمه‌های action -->
                <div class="service-actions">
                    <button class="action-btn primary-btn execute-service-btn">
                        <span class="btn-icon">⚡</span>
                        اجرای سرویس
                        <span class="shortcut-hint">(Ctrl+Enter)</span>
                    </button>
                    
                    <div class="secondary-actions">
                        <button class="action-btn secondary-btn preview-btn">
                            <span class="btn-icon">👁️</span>
                            پیش‌نمایش
                        </button>
                        <button class="action-btn secondary-btn reset-btn">
                            <span class="btn-icon">🔄</span>
                            بازنشانی
                        </button>
                    </div>
                </div>

                <!-- ناحیه نتیجه -->
                <div class="service-result-container">
                    <div class="result-header">
                        <h5>نتیجه سرویس</h5>
                        <div class="result-actions">
                            <button class="result-btn copy-result" title="کپی نتیجه">
                                📋
                            </button>
                            <button class="result-btn download-result" title="دانلود">
                                ⬇️
                            </button>
                            <button class="result-btn clear-result" title="پاک کردن">
                                🗑️
                            </button>
                        </div>
                    </div>
                    
                    <div class="result-content">
                        <div class="result-placeholder">
                            <span class="placeholder-icon">📄</span>
                            <p>نتیجه سرویس اینجا نمایش داده می‌شود</p>
                        </div>
                    </div>
                    
                    <div class="result-footer">
                        <div class="execution-info">
                            <span class="info-item">زمان اجرا: <span class="execution-time">--</span></span>
                            <span class="info-item">حجم داده: <span class="data-size">--</span></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- اطلاعات بیشتر -->
            <div class="service-card-footer">
                <div class="footer-links">
                    <a href="#" class="footer-link docs-link" data-service="<?php echo $service_key; ?>">
                        📚 مستندات
                    </a>
                    <a href="#" class="footer-link examples-link" data-service="<?php echo $service_key; ?>">
                        💡 مثال‌ها
                    </a>
                    <a href="#" class="footer-link support-link" data-service="<?php echo $service_key; ?>">
                        🆘 پشتیبانی
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}

// تابع کمکی برای رندر کردن کارت سرویس ساده
function abjad_render_service_card($service_key, $compact = false) {
    global $abjad_services_available;
    
    $service_info = $abjad_services_available[$service_key] ?? array(
        'name' => $service_key,
        'description' => 'سرویس پردازش متن',
        'icon' => '⚙️'
    );
    
    $card_class = $compact ? 'service-card-compact' : 'service-card-normal';
    
    ob_start();
    ?>
    
    <div class="service-card <?php echo $card_class; ?>" data-service="<?php echo $service_key; ?>">
        <div class="card-header">
            <span class="service-icon"><?php echo $service_info['icon']; ?></span>
            <h4><?php echo $service_info['name']; ?></h4>
        </div>
        
        <?php if (!$compact): ?>
        <div class="card-body">
            <p class="service-desc"><?php echo $service_info['description']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="card-footer">
            <button class="card-action-btn" data-service="<?php echo $service_key; ?>">
                <?php echo $compact ? 'انتخاب' : 'استفاده از سرویس'; ?>
            </button>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
?>