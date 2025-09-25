jQuery(document).ready(function($) {
    // مدیریت اجرای سرویس
    $('.execute-btn').on('click', function() {
        const button = $(this);
        const serviceItem = button.closest('.service-item');
        const serviceKey = serviceItem.data('service');
        const inputText = serviceItem.find('textarea').val();
        const resultDiv = serviceItem.find('.service-result');
        
        if (!inputText.trim()) {
            alert('لطفا متن مورد نظر را وارد کنید');
            return;
        }
        
        // غیرفعال کردن دکمه
        button.prop('disabled', true).text(abjad_ajax.i18n.processing);
        resultDiv.html('<div class="loading">⏳ در حال پردازش...</div>');
        
        // ارسال درخواست AJAX
        $.ajax({
            url: abjad_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'abjad_execute_service',
                service_key: serviceKey,
                input_text: inputText,
                nonce: abjad_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // نمایش نتیجه موفق
                    resultDiv.html(`
                        <div class="success-result">
                            <h5>✅ نتیجه سرویس:</h5>
                            <div class="result-content">${response.data.result || response.data}</div>
                            <div class="usage-info">
                                <small>تعداد باقیمانده: ${response.data.remainingUsage || '---'}</small>
                            </div>
                            <div class="execution-time">
                                <small>زمان اجرا: ${response.data.executionTime || '---'} ثانیه</small>
                            </div>
                        </div>
                    `);
                    
                    // بروزرسانی شمارنده استفاده
                    const badge = serviceItem.find('.usage-badge');
                    const currentText = badge.text();
                    const parts = currentText.split(' / ');
                    if (parts.length === 2 && response.data.remainingUsage !== undefined) {
                        badge.text(`${response.data.remainingUsage} / ${parts[1]}`);
                    }
                    
                    // انیمیشن موفقیت
                    resultDiv.hide().slideDown(300);
                    serviceItem.addClass('executed-success');
                    setTimeout(() => {
                        serviceItem.removeClass('executed-success');
                    }, 2000);
                    
                } else {
                    // نمایش خطا
                    resultDiv.html(`
                        <div class="error-result">
                            <h5>❌ خطا در اجرای سرویس</h5>
                            <p>${response.data || 'خطای ناشناخته رخ داد'}</p>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                resultDiv.html(`
                    <div class="error-result">
                        <h5>❌ خطای ارتباطی</h5>
                        <p>خطا در ارتباط با سرور: ${error}</p>
                        <small>لطفا اتصال اینترنت خود را بررسی کنید</small>
                    </div>
                `);
            },
            complete: function() {
                // فعال کردن مجدد دکمه
                button.prop('disabled', false).text('اجرای سرویس');
            }
        });
    });
    
    // مدیریت auto-resize برای textarea
    $('.service-interface textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // ویژگی clear textarea
    $('.clear-btn').on('click', function() {
        const serviceItem = $(this).closest('.service-item');
        serviceItem.find('textarea').val('').height('auto');
        serviceItem.find('.service-result').empty();
    });
    
    // ویژگی copy result
    $(document).on('click', '.copy-result-btn', function() {
        const resultText = $(this).closest('.success-result').find('.result-content').text();
        navigator.clipboard.writeText(resultText).then(function() {
            const btn = $(this);
            btn.text('کپی شد!');
            setTimeout(() => btn.text('کپی نتیجه'), 2000);
        }.bind(this));
    });
    
    // بارگذاری وضعیت مجوزها هنگام لود صفحه
    function loadLicenseStatus() {
        $('.license-status').each(function() {
            const statusElement = $(this);
            const licenseId = statusElement.data('license-id');
            
            $.ajax({
                url: abjad_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'abjad_get_license_status',
                    license_id: licenseId,
                    nonce: abjad_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        statusElement.html(`
                            <div class="status-badge active">
                                <span>فعال</span>
                                <small>انقضا: ${response.data.expiry_date}</small>
                            </div>
                        `);
                    } else {
                        statusElement.html(`
                            <div class="status-badge expired">
                                <span>منقضی شده</span>
                                <small><a href="/shop">تمدید کنید</a></small>
                            </div>
                        `);
                    }
                }
            });
        });
    }
    
    // ویژگی جستجو در سرویس‌ها
    $('#abjad-service-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.service-item').each(function() {
            const serviceName = $(this).find('h4').text().toLowerCase();
            const serviceDesc = $(this).find('.service-desc').text().toLowerCase();
            
            if (serviceName.includes(searchTerm) || serviceDesc.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // ویژگی فیلتر سرویس‌ها
    $('.service-filter-btn').on('click', function() {
        const filter = $(this).data('filter');
        $('.service-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        if (filter === 'all') {
            $('.service-item').show();
        } else {
            $('.service-item').hide();
            $(`.service-item[data-service="${filter}"]`).show();
        }
    });
    
    // ویژگی نمایش/پنهان کردن جزئیات سرویس
    $('.service-toggle-details').on('click', function() {
        const details = $(this).closest('.service-item').find('.service-details');
        details.slideToggle(300);
        $(this).find('.toggle-icon').text(
            $(this).find('.toggle-icon').text() === '▼' ? '▲' : '▼'
        );
    });
    
    // ویژگی پیش‌نمایش کاراکتر
    $('.service-interface textarea').on('input', function() {
        const charCount = $(this).val().length;
        const counter = $(this).closest('.service-interface').find('.char-counter');
        counter.text(`تعداد کاراکتر: ${charCount}`);
        
        // تغییر رنگ برای محدودیت‌ها
        if (charCount > 1000) {
            counter.addClass('warning');
        } else {
            counter.removeClass('warning');
        }
    });
    
    // ویژگی ذخیره پیش‌نویس
    let draftTimer;
    $('.service-interface textarea').on('input', function() {
        clearTimeout(draftTimer);
        const serviceKey = $(this).closest('.service-item').data('service');
        
        draftTimer = setTimeout(() => {
            const text = $(this).val();
            localStorage.setItem(`abjad_draft_${serviceKey}`, text);
            
            // نمایش پیغام ذخیره
            const draftMsg = $(this).closest('.service-interface').find('.draft-message');
            draftMsg.fadeIn().text('پیش‌نویس ذخیره شد');
            setTimeout(() => draftMsg.fadeOut(), 2000);
        }, 1000);
    });
    
    // بازیابی پیش‌نویس‌ها هنگام لود صفحه
    $('.service-item').each(function() {
        const serviceKey = $(this).data('service');
        const draft = localStorage.getItem(`abjad_draft_${serviceKey}`);
        if (draft) {
            $(this).find('textarea').val(draft).trigger('input');
        }
    });
    
    // مدیریت تب‌ها در پنل سرویس
    $('.abjad-tab-btn').on('click', function() {
        const tabId = $(this).data('tab');
        
        // غیرفعال کردن همه تب‌ها
        $('.abjad-tab-btn').removeClass('active');
        $('.abjad-tab-content').removeClass('active');
        
        // فعال کردن تب انتخاب شده
        $(this).addClass('active');
        $(`#${tabId}`).addClass('active');
    });
    
    // ویژگی export نتیجه
    $(document).on('click', '.export-result-btn', function() {
        const resultContent = $(this).closest('.success-result').find('.result-content').text();
        const serviceName = $(this).closest('.service-item').find('h4').text();
        const timestamp = new Date().toLocaleString('fa-IR');
        
        const blob = new Blob([`سرویس: ${serviceName}\nزمان: ${timestamp}\n\n${resultContent}`], {
            type: 'text/plain;charset=utf-8'
        });
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `abjad-result-${Date.now()}.txt`;
        link.click();
    });
    
    // ویژگی به‌اشتراک‌گذاری نتیجه
    $(document).on('click', '.share-result-btn', function() {
        const resultText = $(this).closest('.success-result').find('.result-content').text();
        
        if (navigator.share) {
            navigator.share({
                title: 'نتیجه سرویس ابجد',
                text: resultText.substring(0, 100) + '...',
                url: window.location.href
            });
        } else {
            // fallback برای مرورگرهای قدیمی
            prompt('برای به‌اشتراک‌گذاری، متن زیر را کپی کنید:', resultText);
        }
    });
    
    // راه‌اندازی اولیه
    loadLicenseStatus();
    
    // رفرش خودکار وضعیت هر 2 دقیقه
    setInterval(loadLicenseStatus, 120000);
    
    // ویژگی responsive menu برای سرویس‌ها
    $('.abjad-mobile-menu-btn').on('click', function() {
        $('.abjad-services-sidebar').toggleClass('active');
    });
    
    // بستن منو با کلیک خارج
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.abjad-services-sidebar, .abjad-mobile-menu-btn').length) {
            $('.abjad-services-sidebar').removeClass('active');
        }
    });
    
    // مدیریت keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+Enter برای اجرای سرویس
        if (e.ctrlKey && e.key === 'Enter') {
            const focusedTextarea = $('.service-interface textarea:focus');
            if (focusedTextarea.length) {
                focusedTextarea.closest('.service-item').find('.execute-btn').click();
            }
        }
        
        // Escape برای پاک کردن
        if (e.key === 'Escape') {
            $('.service-interface textarea:focus').val('').trigger('input');
        }
    });
    
    // نمایش راهنمای keyboard shortcuts
    $('#abjad-keyboard-help').on('click', function() {
        $('#abjad-keyboard-modal').fadeIn();
    });
    
    $('.modal-close').on('click', function() {
        $(this).closest('.modal').fadeOut();
    });
});

// ویژگی‌های global برای استفاده در سایر قسمت‌ها
window.AbjadFrontend = {
    // پاک کردن همه پیش‌نویس‌ها
    clearAllDrafts: function() {
        const keys = Object.keys(localStorage).filter(key => key.startsWith('abjad_draft_'));
        keys.forEach(key => localStorage.removeItem(key));
        alert('همه پیش‌نویس‌ها پاک شدند');
    },
    
    // دانلود همه نتایج
    exportAllResults: function() {
        const results = [];
        $('.success-result').each(function() {
            const serviceName = $(this).closest('.service-item').find('h4').text();
            const content = $(this).find('.result-content').text();
            results.push(`=== ${serviceName} ===\n${content}\n\n`);
        });
        
        if (results.length > 0) {
            const blob = new Blob([results.join('')], { type: 'text/plain;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `abjad-all-results-${Date.now()}.txt`;
            link.click();
        } else {
            alert('هیچ نتیجه‌ای برای ذخیره وجود ندارد');
        }
    },
    
    // بررسی وضعیت آنلاین بودن سرویس
    checkServiceStatus: function() {
        fetch(abjad_ajax.api_base + '/health')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'healthy') {
                    this.showNotification('سرویس آنلاین است ✅', 'success');
                } else {
                    this.showNotification('سرویس موقتاً در دسترس نیست ⚠️', 'warning');
                }
            })
            .catch(() => {
                this.showNotification('خطا در ارتباط با سرویس ❌', 'error');
            });
    },
    
    // نمایش نوتیفیکیشن
    showNotification: function(message, type = 'info') {
        const notification = $(`
            <div class="abjad-notification ${type}">
                <span>${message}</span>
                <button class="close-notification">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        notification.slideDown();
        
        setTimeout(() => {
            notification.slideUp(() => notification.remove());
        }, 5000);
        
        notification.find('.close-notification').on('click', function() {
            notification.slideUp(() => notification.remove());
        });
    }
};

// ویژگی‌های پیشرفته برای توسعه‌دهندگان
if (window.console) {
    console.log('🎯 Abjad Services Frontend Loaded');
    console.log('🔧 Available commands:');
    console.log('   - AbjadFrontend.clearAllDrafts()');
    console.log('   - AbjadFrontend.exportAllResults()');
    console.log('   - AbjadFrontend.checkServiceStatus()');
}