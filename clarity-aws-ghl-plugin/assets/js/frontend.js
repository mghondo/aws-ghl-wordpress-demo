/**
 * Frontend JavaScript for Clarity AWS GHL Plugin
 * 
 * Handles student registration, login, and dashboard interactions
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Registration form handling is in the template file
    // Login form handling is in the template file
    // Dashboard interactions
    
    /**
     * Show message helper
     */
    function showMessage(message, type, container) {
        var className = type === 'error' ? 'error-message' : type === 'success' ? 'sent-message' : 'info-message';
        var $container = container || $('.clarity-messages');
        
        if ($container.length === 0) {
            $container = $('<div class="clarity-messages"></div>');
            $('body').prepend($container);
        }
        
        var $message = $(`<div class="${className}">${message}</div>`);
        $container.empty().append($message);
        
        $('html, body').animate({
            scrollTop: $container.offset().top - 100
        }, 500);
        
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $message.fadeOut();
            }, 4000);
        }
    }
    
    /**
     * Course access controls
     */
    $('.course-btn.locked-btn').on('click', function(e) {
        e.preventDefault();
        showMessage('You need to upgrade your access level to view this content.', 'error');
    });
    
    /**
     * Progress tracking (placeholder for future implementation)
     */
    $('.continue-btn, .start-btn').on('click', function(e) {
        // This would track when users start/continue courses
        console.log('Course interaction tracked');
    });
    
    /**
     * Certificate download (placeholder)
     */
    $('.certificate-btn').on('click', function(e) {
        e.preventDefault();
        showMessage('Certificate generation is coming soon!', 'info');
    });
    
    /**
     * Smooth scrolling for anchor links
     */
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    /**
     * Form validation helper
     */
    function validateForm($form) {
        var isValid = true;
        
        $form.find('input[required], textarea[required], select[required]').each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (!value) {
                $field.addClass('form-field-error');
                isValid = false;
            } else {
                $field.removeClass('form-field-error');
            }
        });
        
        // Email validation
        $form.find('input[type="email"]').each(function() {
            var $field = $(this);
            var email = $field.val().trim();
            
            if (email && !isValidEmail(email)) {
                $field.addClass('form-field-error');
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Email validation
     */
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Remove error styling on input
     */
    $(document).on('input', '.form-field-error', function() {
        $(this).removeClass('form-field-error');
    });
});