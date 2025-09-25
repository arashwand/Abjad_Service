jQuery(document).ready(function($) {
    'use strict';

    // بررسی وجود آبجکت abjad_admin
    if (typeof abjad_admin === 'undefined') {
        console.error('Abjad Admin: abjad_admin object is not defined');
        return;
    }

    // مدیریت نمایش/پنهان کردن بخش سرویس ابجد
    function toggleAbjadServices() {
        if ($('#_abjad_service_enabled').is(':checked')) {
            $('#abjad-services-container').slideDown(300);
            // فقط اگر آبجکت تعریف شده باشد بارگذاری کن
            if (typeof abjad_admin !== 'undefined') {
                loadServicesStatus();
            }
        } else {
            $('#abjad-services-container').slideUp(300);
        }
    }

    // بارگذاری وضعیت سرویس‌ها از سرور
    function loadServicesStatus() {
        $('#abjad-services-container').addClass('loading');
        
        $.ajax({
            url: abjad_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'abjad_get_services_status',
                nonce: abjad_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateServicesUI(response.data);
                } else {
                    showAdminNotice('خطا در بارگذاری وضعیت سرویس‌ها', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Abjad Admin AJAX Error:', error);
                showAdminNotice('خطا در ارتباط با سرور', 'error');
            },
            complete: function() {
                $('#abjad-services-container').removeClass('loading');
            }
        });
    }

    // بروزرسانی UI بر اساس وضعیت سرویس‌ها
    function updateServicesUI(servicesData) {
        $.each(servicesData, function(serviceKey, status) {
            const serviceElement = $(`[id*="_abjad_service_${serviceKey}"]`).closest('.service-option');
            
            if (serviceElement.length) {
                // افزودن نشانگر وضعیت
                let statusBadge = serviceElement.find('.service-status-badge');
                if (statusBadge.length === 0) {
                    statusBadge = $('<span class="service-status-badge"></span>');
                    serviceElement.find('.service-option-header').append(statusBadge);
                }

                statusBadge.removeClass('active error unknown')
                           .addClass(status.status)
                           .attr('title', status.message)
                           .text(status.label);
            }
        });
    }

    // نمایش نوتیفیکیشن در ادمین
    function showAdminNotice(message, type = 'info') {
        const noticeClass = type === 'error' ? 'notice-error' : 
                           type === 'success' ? 'notice-success' : 'notice-info';
        
        const noticeHTML = `
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">بستن این پیغام</span>
                </button>
            </div>
        `;
        
        $('#abjad-services-container').before(noticeHTML);
        
        // حذف خودکار پس از 5 ثانیه
        setTimeout(() => {
            $('.notice').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // اعتبارسنجی محدودیت استفاده
    function validateUsageLimit(input) {
        const value = parseInt(input.val());
        const min = parseInt(input.attr('min')) || 1;
        const max = parseInt(input.attr('max')) || 1000;
        
        if (isNaN(value) || value < min || value > max) {
            input.addClass('error');
            showAdminNotice(`محدودیت استفاده باید بین ${min} تا ${max} باشد`, 'error');
            return false;
        }
        
        input.removeClass('error');
        return true;
    }

    // مدیریت انتخاب/عدم انتخاب سرویس‌ها
    function handleServiceSelection(checkbox) {
        const serviceKey = checkbox.attr('id').replace('_abjad_service_', '');
        const limitInput = $(`#_abjad_service_${serviceKey}_limit`);
        
        if (checkbox.is(':checked')) {
            limitInput.prop('disabled', false).closest('p').show();
            // مقدار پیش‌فرض را تنظیم کن
            if (!limitInput.val()) {
                const defaultLimit = abjad_admin.default_limits[serviceKey] || 50;
                limitInput.val(defaultLimit);
            }
        } else {
            limitInput.prop('disabled', true).closest('p').hide();
        }
    }

    // تست اتصال به API
    function testAPIConnection() {
        $('#test-api-connection').prop('disabled', true).text('در حال تست...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'abjad_test_api_connection',
                nonce: abjad_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('اتصال به API با موفقیت برقرار شد ✅', 'success');
                    updateConnectionStatus('connected', response.data);
                } else {
                    showAdminNotice('خطا در اتصال به API: ' + response.data, 'error');
                    updateConnectionStatus('error', response.data);
                }
            },
            error: function() {
                showAdminNotice('خطا در ارتباط با سرور', 'error');
                updateConnectionStatus('error', 'خطای شبکه');
            },
            complete: function() {
                $('#test-api-connection').prop('disabled', false).text('تست اتصال API');
            }
        });
    }

    // بروزرسانی وضعیت اتصال
    function updateConnectionStatus(status, message) {
        const statusElement = $('#api-connection-status');
        statusElement.removeClass('connected error unknown').addClass(status);
        
        switch(status) {
            case 'connected':
                statusElement.html('<span class="status-dot connected"></span> متصل');
                break;
            case 'error':
                statusElement.html('<span class="status-dot error"></span> قطع: ' + message);
                break;
            default:
                statusElement.html('<span class="status-dot unknown"></span> نامشخص');
        }
    }

    // تولید خودکار API Key
    function generateAPIKey() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let key = 'abjad_';
        
        for (let i = 0; i < 32; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        $('#_abjad_service_api_key').val(key);
        showAdminNotice('کلید API با موفقیت تولید شد', 'success');
    }

    // مدیریت پیش‌نمایش محصول
    function setupProductPreview() {
        $('#post-preview').on('click', function(e) {
            if ($('#_abjad_service_enabled').is(':checked')) {
                // ذخیره موقت تنظیمات
                const abjadSettings = {
                    enabled: true,
                    services: {}
                };

                $('[id^="_abjad_service_"]').each(function() {
                    const id = $(this).attr('id');
                    if (id.includes('_limit')) return;
                    
                    const serviceKey = id.replace('_abjad_service_', '');
                    if ($(this).is(':checked')) {
                        abjadSettings.services[serviceKey] = {
                            limit: $(`#_abjad_service_${serviceKey}_limit`).val() || 50
                        };
                    }
                });

                localStorage.setItem('abjad_preview_settings', JSON.stringify(abjadSettings));
            }
        });
    }

    // مقداردهی اولیه
    function initializeAdmin() {
        // مدیریت فعال‌سازی سرویس ابجد
        $('#_abjad_service_enabled').on('change', toggleAbjadServices);
        toggleAbjadServices(); // وضعیت اولیه

        // مدیریت انتخاب سرویس‌ها
        $('[id^="_abjad_service_"]').not('[id*="_limit"]').on('change', function() {
            handleServiceSelection($(this));
        });

        // اعتبارسنجی محدودیت استفاده
        $('[id*="_limit"]').on('blur', function() {
            validateUsageLimit($(this));
        });

        // تست اتصال API
        $('#test-api-connection').on('click', testAPIConnection);

        // تولید API Key
        $('#generate-api-key').on('click', generateAPIKey);

        // مدیریت پیش‌نمایش محصول
        setupProductPreview();

        // ذخیره خودکار تنظیمات
        setupAutoSave();

        // راهنمای tooltip
        setupTooltips();

        // گزارش‌گیری سریع
        setupQuickReports();
    }

    // ذخیره خودکار تنظیمات
    function setupAutoSave() {
        let saveTimer;
        const saveDelay = 1000; // 1 ثانیه

        $('.abjad-service-section input, .abjad-service-section select').on('input change', function() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveDraftSettings, saveDelay);
        });
    }

    // ذخیره پیش‌نویس تنظیمات
    function saveDraftSettings() {
        const settings = {
            enabled: $('#_abjad_service_enabled').is(':checked'),
            expiry_days: $('#_abjad_service_expiry_days').val(),
            api_key: $('#_abjad_service_api_key').val(),
            services: {}
        };

        $('[id^="_abjad_service_"]').not('[id*="_limit"]').each(function() {
            const serviceKey = $(this).attr('id').replace('_abjad_service_', '');
            if ($(this).is(':checked')) {
                settings.services[serviceKey] = {
                    limit: $(`#_abjad_service_${serviceKey}_limit`).val()
                };
            }
        });

        localStorage.setItem('abjad_settings_draft', JSON.stringify(settings));
        showAutoSaveNotice();
    }

    // نمایش پیغام ذخیره خودکار
    function showAutoSaveNotice() {
        let notice = $('.auto-save-notice');
        if (notice.length === 0) {
            notice = $('<div class="auto-save-notice"></div>');
            $('.abjad-service-section').append(notice);
        }

        notice.text('تنظیمات ذخیره شدند ✓').fadeIn().delay(1000).fadeOut();
    }

    // راه‌اندازی tooltip‌ها
    function setupTooltips() {
        $('.abjad-tooltip').each(function() {
            const tooltipText = $(this).data('tooltip');
            if (tooltipText) {
                $(this).attr('title', tooltipText);
            }
        });

        // فعال‌سازی tooltip‌های وردپرس
        if (typeof jQuery.fn.tooltip !== 'undefined') {
            $('.abjad-tooltip').tooltip({
                track: true,
                content: function() {
                    return $(this).data('tooltip');
                }
            });
        }
    }

    // گزارش‌گیری سریع
    function setupQuickReports() {
        $('#abjad-quick-report').on('click', function() {
            const $button = $(this);
            $button.prop('disabled', true).text('در حال تولید گزارش...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'abjad_generate_quick_report',
                    nonce: abjad_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showQuickReportModal(response.data);
                    } else {
                        showAdminNotice('خطا در تولید گزارش', 'error');
                    }
                },
                error: function() {
                    showAdminNotice('خطا در ارتباط با سرور', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('گزارش سریع');
                }
            });
        });
    }

    // نمایش مودال گزارش
    function showQuickReportModal(reportData) {
        const modalHTML = `
            <div id="abjad-report-modal" class="abjad-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>📊 گزارش سریع سرویس ابجد</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="report-stats">
                            <div class="stat-item">
                                <span class="stat-label">مجوزهای فعال:</span>
                                <span class="stat-value">${reportData.active_licenses}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">استفاده امروز:</span>
                                <span class="stat-value">${reportData.today_usage}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">سرویس پراستفاده:</span>
                                <span class="stat-value">${reportData.most_used_service}</span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <button class="button button-primary" id="export-report">خروجی Excel</button>
                            <button class="button" id="print-report">چاپ گزارش</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHTML);
        $('#abjad-report-modal').fadeIn();

        // مدیریت بستن مودال
        $('.modal-close, #abjad-report-modal').on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('modal-close')) {
                $('#abjad-report-modal').fadeOut(function() {
                    $(this).remove();
                });
            }
        });

        // جلوگیری از بستن با کلیک روی محتوا
        $('.modal-content').on('click', function(e) {
            e.stopPropagation();
        });
    }

    // مدیریت bulk actions
    function setupBulkActions() {
        $('#bulk-enable-services').on('click', function() {
            $('[id^="_abjad_service_"]').not('[id*="_limit"]').prop('checked', true).trigger('change');
            showAdminNotice('همه سرویس‌ها فعال شدند', 'success');
        });

        $('#bulk-disable-services').on('click', function() {
            $('[id^="_abjad_service_"]').not('[id*="_limit"]').prop('checked', false).trigger('change');
            showAdminNotice('همه سرویس‌ها غیرفعال شدند', 'info');
        });

        $('#bulk-set-defaults').on('click', function() {
            $('[id*="_limit"]').each(function() {
                const serviceKey = $(this).attr('id').replace('_abjad_service_', '').replace('_limit', '');
                const defaultLimit = abjad_admin.default_limits[serviceKey] || 50;
                $(this).val(defaultLimit);
            });
            showAdminNotice('مقادیر پیش‌فرض اعمال شدند', 'success');
        });
    }

    // راه‌اندازی اولیه
    initializeAdmin();
    setupBulkActions();

    // global functions برای استفاده در console
    window.AbjadAdmin = {
        testConnection: testAPIConnection,
        generateKey: generateAPIKey,
        validateSettings: function() {
            let isValid = true;
            $('[id*="_limit"]').each(function() {
                if (!validateUsageLimit($(this))) {
                    isValid = false;
                }
            });
            return isValid;
        },
        getSettings: function() {
            const settings = {
                enabled: $('#_abjad_service_enabled').is(':checked'),
                expiry_days: $('#_abjad_service_expiry_days').val(),
                api_key: $('#_abjad_service_api_key').val(),
                services: {}
            };

            $('[id^="_abjad_service_"]').not('[id*="_limit"]').each(function() {
                const serviceKey = $(this).attr('id').replace('_abjad_service_', '');
                if ($(this).is(':checked')) {
                    settings.services[serviceKey] = {
                        limit: $(`#_abjad_service_${serviceKey}_limit`).val()
                    };
                }
            });

            return settings;
        }
    };

    // لاگ برای توسعه
    if (abjad_admin.debug) {
        console.log('🎯 Abjad Admin JS Loaded');
        console.log('🔧 Available commands: AbjadAdmin.testConnection(), AbjadAdmin.generateKey()');
    }
});