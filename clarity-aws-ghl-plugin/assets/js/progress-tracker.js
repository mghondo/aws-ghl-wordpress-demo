/**
 * Progress Tracker Frontend JavaScript
 *
 * Handles user interactions for progress tracking and navigation
 *
 * @package Clarity_AWS_GHL
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initProgressTracker();
    });
    
    /**
     * Initialize progress tracker functionality
     */
    function initProgressTracker() {
        initLessonNavigation();
        initProgressUpdates();
        initAutoSave();
        initActivityTracking();
    }
    
    /**
     * Initialize lesson navigation
     */
    function initLessonNavigation() {
        // Complete lesson button
        $(document).on('click', '.complete-lesson', function() {
            var $button = $(this);
            var lessonId = $button.data('lesson-id');
            
            if (confirm(clarityProgress.strings.confirm_complete)) {
                completeLessonAjax(lessonId, $button);
            }
        });
        
        // Get certificate button
        $(document).on('click', '.get-certificate', function() {
            var $button = $(this);
            var userId = $button.data('user-id');
            var courseId = $button.data('course-id');
            
            generateCertificate(userId, courseId, $button);
        });
        
        // Start lesson button
        $(document).on('click', '.start-lesson', function() {
            var lessonId = $(this).data('lesson-id');
            var lessonUrl = $(this).data('lesson-url') || '/lesson/' + lessonId;
            
            // Track lesson start
            trackLessonStart(lessonId);
            
            // Navigate to lesson
            window.location.href = lessonUrl;
        });
        
        // Next lesson navigation
        $(document).on('click', '.nav-next:not(.disabled)', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            
            // Smooth transition
            $('body').addClass('clarity-loading');
            
            setTimeout(function() {
                window.location.href = href;
            }, 300);
        });
        
        // Previous lesson navigation
        $(document).on('click', '.nav-previous', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            
            // Smooth transition
            $('body').addClass('clarity-loading');
            
            setTimeout(function() {
                window.location.href = href;
            }, 300);
        });
    }
    
    /**
     * Initialize progress updates
     */
    function initProgressUpdates() {
        // Auto-update progress every 30 seconds for active lessons
        if ($('.clarity-lesson-navigation').length > 0) {
            setInterval(function() {
                updateLessonProgress();
            }, 30000);
        }
        
        // Update progress on page visibility change
        $(document).on('visibilitychange', function() {
            if (!document.hidden) {
                updateLessonProgress();
            }
        });
        
        // Update progress before page unload
        $(window).on('beforeunload', function() {
            updateLessonProgress();
        });
    }
    
    /**
     * Initialize auto-save functionality
     */
    function initAutoSave() {
        var saveTimeout;
        
        // Auto-save lesson notes
        $(document).on('input', '.lesson-notes textarea', function() {
            var $textarea = $(this);
            var lessonId = $textarea.data('lesson-id');
            var notes = $textarea.val();
            
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                saveLessonNotes(lessonId, notes);
            }, 2000);
        });
        
        // Manual save button
        $(document).on('click', '.save-notes', function() {
            var $button = $(this);
            var lessonId = $button.data('lesson-id');
            var notes = $('.lesson-notes textarea[data-lesson-id="' + lessonId + '"]').val();
            
            saveLessonNotes(lessonId, notes, $button);
        });
    }
    
    /**
     * Initialize activity tracking
     */
    function initActivityTracking() {
        var startTime = Date.now();
        var lastUpdate = startTime;
        
        // Track time spent
        setInterval(function() {
            var currentTime = Date.now();
            var timeSpent = Math.floor((currentTime - lastUpdate) / 1000);
            
            if (timeSpent > 0 && document.visibilityState === 'visible') {
                updateTimeSpent(timeSpent);
                lastUpdate = currentTime;
            }
        }, 10000); // Update every 10 seconds
        
        // Track scroll position for video lessons
        if ($('.lesson-video').length > 0) {
            var $video = $('.lesson-video video, .lesson-video iframe');
            
            if ($video.length > 0 && $video[0].tagName === 'VIDEO') {
                $video[0].addEventListener('timeupdate', function() {
                    var position = Math.floor(this.currentTime);
                    updateVideoPosition(position);
                });
            }
        }
    }
    
    /**
     * Complete lesson via AJAX
     */
    function completeLessonAjax(lessonId, $button) {
        var originalText = $button.text();
        
        $button.prop('disabled', true).text(clarityProgress.strings.loading);
        
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_mark_lesson_complete',
                nonce: clarityProgress.nonce,
                lesson_id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    // Update UI
                    $button.closest('.lesson-center-actions').html(
                        '<span class="lesson-completed-indicator">' +
                        '<span class="dashicons dashicons-yes-alt"></span>' +
                        clarityProgress.strings.lesson_completed +
                        '</span>'
                    );
                    
                    // Update lesson item in list
                    $('.lesson-item[data-lesson-id="' + lessonId + '"]')
                        .addClass('completed')
                        .removeClass('accessible');
                    
                    // Show success message
                    showNotification(clarityProgress.strings.lesson_completed, 'success');
                    
                    // Check for next lesson
                    checkNextLesson(lessonId);
                    
                    // Update progress bars
                    updateProgressBars();
                    
                } else {
                    showNotification(response.data || clarityProgress.strings.error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotification(clarityProgress.strings.error, 'error');
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Track lesson start
     */
    function trackLessonStart(lessonId) {
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_track_lesson_start',
                nonce: clarityProgress.nonce,
                lesson_id: lessonId
            }
        });
    }
    
    /**
     * Generate certificate
     */
    function generateCertificate(userId, courseId, $button) {
        var originalText = $button.text();
        
        $button.prop('disabled', true).text(clarityProgress.strings.generating_certificate);
        
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_generate_user_certificate',
                nonce: clarityProgress.nonce,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    // Replace button with download link
                    $button.closest('.course-completed').html(
                        '<span class="completion-badge">' +
                        '<span class="dashicons dashicons-awards"></span>' +
                        'Course Complete!' +
                        '</span>' +
                        '<a href="' + response.data.certificate_url + '" class="button button-primary certificate-download" target="_blank">' +
                        'Download Certificate' +
                        '</a>'
                    );
                    
                    showNotification(clarityProgress.strings.certificate_generated, 'success');
                    
                    // Refresh page after 2 seconds
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                    
                } else {
                    showNotification(response.data || clarityProgress.strings.certificate_error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotification(clarityProgress.strings.certificate_error, 'error');
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Update lesson progress
     */
    function updateLessonProgress() {
        var $navigation = $('.clarity-lesson-navigation');
        if ($navigation.length === 0) return;
        
        var lessonId = $navigation.data('lesson-id');
        var timeSpent = Math.floor((Date.now() - window.clarityStartTime) / 1000);
        var position = getCurrentVideoPosition();
        
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_update_lesson_progress',
                nonce: clarityProgress.nonce,
                lesson_id: lessonId,
                time_spent: timeSpent,
                position: position
            }
        });
    }
    
    /**
     * Save lesson notes
     */
    function saveLessonNotes(lessonId, notes, $button) {
        if ($button) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Saving...');
        }
        
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_save_lesson_notes',
                nonce: clarityProgress.nonce,
                lesson_id: lessonId,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    if ($button) {
                        $button.text('Saved!');
                        setTimeout(function() {
                            $button.prop('disabled', false).text(originalText);
                        }, 2000);
                    }
                    showNotification('Notes saved', 'success');
                } else {
                    if ($button) {
                        $button.prop('disabled', false).text(originalText);
                    }
                    showNotification('Failed to save notes', 'error');
                }
            },
            error: function() {
                if ($button) {
                    $button.prop('disabled', false).text(originalText);
                }
                showNotification('Failed to save notes', 'error');
            }
        });
    }
    
    /**
     * Update time spent tracking
     */
    function updateTimeSpent(timeSpent) {
        // This would typically update a hidden field or send to server
        // For now, we'll just track it locally
        if (!window.clarityTimeSpent) {
            window.clarityTimeSpent = 0;
        }
        window.clarityTimeSpent += timeSpent;
    }
    
    /**
     * Update video position tracking
     */
    function updateVideoPosition(position) {
        // Track video position for resume functionality
        window.clarityVideoPosition = position;
    }
    
    /**
     * Get current video position
     */
    function getCurrentVideoPosition() {
        var $video = $('.lesson-video video');
        if ($video.length > 0 && $video[0].currentTime) {
            return Math.floor($video[0].currentTime);
        }
        return window.clarityVideoPosition || 0;
    }
    
    /**
     * Check for next lesson
     */
    function checkNextLesson(currentLessonId) {
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_next_lesson',
                nonce: clarityProgress.nonce,
                lesson_id: currentLessonId
            },
            success: function(response) {
                if (response.success && response.data.can_access) {
                    // Enable next lesson button if it exists
                    var $nextButton = $('.nav-next');
                    if ($nextButton.hasClass('disabled')) {
                        $nextButton.removeClass('disabled');
                        showNotification('Next lesson unlocked!', 'success');
                    }
                    
                    // Update lesson list
                    $('.lesson-item[data-lesson-id="' + response.data.lesson_id + '"]')
                        .removeClass('locked')
                        .addClass('accessible');
                }
            }
        });
    }
    
    /**
     * Update progress bars
     */
    function updateProgressBars() {
        // Animate progress bar updates
        $('.progress-fill').each(function() {
            var $fill = $(this);
            var targetWidth = $fill.data('target-width') || $fill.css('width');
            
            $fill.animate({
                width: targetWidth
            }, 1000, 'easeOutCubic');
        });
        
        // Update circular progress
        $('.circular-chart .circle').each(function() {
            var $circle = $(this);
            var percentage = $circle.data('percentage');
            
            if (percentage) {
                $circle.css('stroke-dasharray', percentage + ', 100');
            }
        });
    }
    
    /**
     * Show notification message
     */
    function showNotification(message, type) {
        var $notification = $('<div class="clarity-notification ' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        // Position notification
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: type === 'success' ? '#28a745' : '#dc3545',
            color: 'white',
            padding: '12px 20px',
            borderRadius: '4px',
            zIndex: 9999,
            fontSize: '14px',
            fontWeight: '500',
            boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
            opacity: 0,
            transform: 'translateX(100%)'
        });
        
        // Animate in
        $notification.animate({
            opacity: 1,
            transform: 'translateX(0)'
        }, 300);
        
        // Auto remove
        setTimeout(function() {
            $notification.animate({
                opacity: 0,
                transform: 'translateX(100%)'
            }, 300, function() {
                $notification.remove();
            });
        }, 4000);
    }
    
    /**
     * Initialize on page load
     */
    function initPageLoad() {
        // Set start time for tracking
        window.clarityStartTime = Date.now();
        
        // Animate progress bars on load
        setTimeout(function() {
            updateProgressBars();
        }, 500);
        
        // Smooth scroll to lesson content if hash present
        if (window.location.hash) {
            var $target = $(window.location.hash);
            if ($target.length) {
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 80
                    }, 800);
                }, 100);
            }
        }
    }
    
    // Initialize on page load
    initPageLoad();
    
    /**
     * Utility functions
     */
    
    // Format time duration
    function formatDuration(seconds) {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        
        if (hours > 0) {
            return hours + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        } else {
            return minutes + ':' + String(secs).padStart(2, '0');
        }
    }
    
    // Get time ago string
    function timeAgo(timestamp) {
        var now = Date.now();
        var diff = Math.floor((now - timestamp) / 1000);
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        return Math.floor(diff / 86400) + ' days ago';
    }
    
    // Debounce function
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Throttle function
    function throttle(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(function() {
                    inThrottle = false;
                }, limit);
            }
        };
    }
    
})(jQuery);