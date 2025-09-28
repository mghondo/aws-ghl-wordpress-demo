/**
 * Admin JavaScript for Clarity AWS GHL Integration
 */

jQuery(document).ready(function($) {
    
    /**
     * Test S3 Connection
     */
    $('#test-s3-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#s3-test-result');
        
        $button.text(clarityAjax.strings.testing).prop('disabled', true);
        $result.html('');
        
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_test_s3_connection',
                nonce: clarityAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + clarityAjax.strings.success + ': ' + response.data + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + clarityAjax.strings.error + ': ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>' + clarityAjax.strings.error + ': Connection failed</p></div>');
            },
            complete: function() {
                $button.text('Test S3 Connection').prop('disabled', false);
            }
        });
    });
    
    /**
     * Test Webhook Endpoint
     */
    $('#test-webhook-endpoint').on('click', function() {
        var $button = $(this);
        var $result = $('#webhook-test-result');
        
        $button.text(clarityAjax.strings.testing).prop('disabled', true);
        $result.html('');
        
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_test_webhook',
                nonce: clarityAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + clarityAjax.strings.success + ': ' + response.data + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + clarityAjax.strings.error + ': ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>' + clarityAjax.strings.error + ': Test failed</p></div>');
            },
            complete: function() {
                $button.text('Test Webhook Endpoint').prop('disabled', false);
            }
        });
    });
    
    /**
     * Clear All Logs
     */
    $('#clear-all-logs').on('click', function() {
        if (!confirm(clarityAjax.strings.confirm_clear)) {
            return;
        }
        
        var $button = $(this);
        
        $button.text('Clearing...').prop('disabled', true);
        
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_clear_logs',
                nonce: clarityAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(clarityAjax.strings.error + ': ' + response.data);
                }
            },
            error: function() {
                alert(clarityAjax.strings.error + ': Clear logs failed');
            },
            complete: function() {
                $button.text('Clear All Logs').prop('disabled', false);
            }
        });
    });
    
    /**
     * Copy webhook URL to clipboard
     */
    $(document).on('click', '.copy-webhook-url', function() {
        var $input = $(this).siblings('input');
        $input.select();
        
        try {
            document.execCommand('copy');
            $(this).text('Copied!').addClass('button-primary');
            
            setTimeout(function() {
                $('.copy-webhook-url').text('Copy').removeClass('button-primary');
            }, 2000);
        } catch (err) {
            console.log('Copy failed', err);
        }
    });
    
    /**
     * Sync contact/opportunity now
     */
    $('#sync-contact-now, #sync-opportunity-now').on('click', function() {
        var $button = $(this);
        var type = $button.attr('id').includes('contact') ? 'contact' : 'opportunity';
        var id = $button.data(type + '-id');
        
        $button.text('Syncing...').prop('disabled', true);
        
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_sync_' + type,
                nonce: clarityAjax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(clarityAjax.strings.error + ': ' + response.data);
                }
            },
            error: function() {
                alert(clarityAjax.strings.error + ': Sync failed');
            },
            complete: function() {
                $button.text('Sync Now').prop('disabled', false);
            }
        });
    });
    
    /**
     * Auto-refresh dashboard stats every 30 seconds
     */
    if ($('.clarity-dashboard-grid').length > 0) {
        setInterval(function() {
            // Only refresh if the page is visible
            if (!document.hidden) {
                $.ajax({
                    url: clarityAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'clarity_refresh_stats',
                        nonce: clarityAjax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('.clarity-status-cards').html(response.data.html);
                        }
                    }
                });
            }
        }, 30000); // 30 seconds
    }
    
    /**
     * Toggle password visibility
     */
    $('.clarity-toggle-password').on('click', function() {
        var $input = $(this).siblings('input[type="password"], input[type="text"]');
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        
        $input.attr('type', type);
        $(this).text(type === 'password' ? 'Show' : 'Hide');
    });
    
    /**
     * Form validation
     */
    $('form').on('submit', function() {
        var isValid = true;
        
        // Check required fields
        $(this).find('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields');
            return false;
        }
    });
    
    /**
     * Real-time validation feedback
     */
    $('input[required]').on('blur', function() {
        if (!$(this).val()) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    /**
     * Webhook logs filtering
     */
    $('#filter-logs').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: formData + '&action=clarity_filter_logs&nonce=' + clarityAjax.nonce,
            success: function(response) {
                if (response.success) {
                    $('#logs-table-container').html(response.data.html);
                }
            }
        });
    });
    
    /**
     * Export logs
     */
    $('#export-logs').on('click', function() {
        var filters = $('#filter-logs').serialize();
        window.location = clarityAjax.ajax_url + '?action=clarity_export_logs&nonce=' + clarityAjax.nonce + '&' + filters;
    });
    
    /**
     * Initialize tooltips
     */
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-tooltip]').tooltip();
    }
    
    /**
     * Initialize tabs
     */
    $('.clarity-tabs .tab-link').on('click', function(e) {
        e.preventDefault();
        
        var $tab = $(this);
        var target = $tab.attr('href');
        
        // Update active tab
        $tab.siblings().removeClass('active');
        $tab.addClass('active');
        
        // Update active content
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    /**
     * Collapsible sections
     */
    $('.clarity-collapsible .toggle').on('click', function() {
        var $section = $(this).closest('.clarity-collapsible');
        var $content = $section.find('.collapsible-content');
        
        $section.toggleClass('collapsed');
        $content.slideToggle();
    });
});

/**
 * Utility functions
 */
window.clarityUtils = {
    
    /**
     * Format file size
     */
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    /**
     * Format timestamp
     */
    formatTime: function(timestamp) {
        var date = new Date(timestamp * 1000);
        return date.toLocaleString();
    },
    
    /**
     * Show success message
     */
    showSuccess: function(message) {
        this.showNotice(message, 'success');
    },
    
    /**
     * Show error message
     */
    showError: function(message) {
        this.showNotice(message, 'error');
    },
    
    /**
     * Show notice
     */
    showNotice: function(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    }
};