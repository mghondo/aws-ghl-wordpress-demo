/**
 * MainPage JavaScript - Dynamic Hero Backgrounds
 * 
 * Handles slideshow, custom image controls, and background switching
 */

jQuery(document).ready(function($) {
    'use strict';
    
    let slideshow = {
        images: [],
        currentIndex: 0,
        isRunning: false,
        intervalId: null,
        directions: [
            'slideInFromTop', 'slideInFromBottom', 'slideInFromLeft', 'slideInFromRight',
            'slideInFromTopLeft', 'slideInFromTopRight', 'slideInFromBottomLeft', 'slideInFromBottomRight'
        ]
    };
    
    // Initialize
    init();
    
    function init() {
        loadImageInventory();
        
        // Only setup controls if admin - removed since controls are now in admin panel
        // setupBackgroundControls();
        // setupImageUpload();
        
        console.log('Background type:', clarityMainPage.bgType);
        
        // Start slideshow if it's the active background type
        if (clarityMainPage.bgType === 'slideshow') {
            console.log('Starting slideshow...');
            startSlideshow();
        }
    }
    
    /**
     * Load image inventory from the JSON file
     */
    function loadImageInventory() {
        // For now, we'll use a sample of images from the inventory
        // In production, you'd fetch the actual JSON file
        slideshow.images = [
            'Whisk_0071c25679.jpg', 'Whisk_04c695ef4e.jpg', 'Whisk_0854079d29.jpg',
            'Whisk_0c19ce0c66.jpg', 'Whisk_0d879baa3e.jpg', 'Whisk_10400bb2d7.jpg',
            'Whisk_10573e69ba.jpg', 'Whisk_11d88a71e6.jpg', 'Whisk_13c610fc24.jpg',
            'Whisk_1985c72415.jpg', 'Whisk_1f534983a1.jpg', 'Whisk_1f8fc4aebd.jpg',
            'Whisk_1fc167de2b.jpg', 'Whisk_20c062af40.jpg', 'Whisk_23eb99588e.jpg',
            'Whisk_262c2adb25.jpg', 'Whisk_279ff18fba.jpg', 'Whisk_28b2a9719a.jpg',
            'Whisk_2aab5b6acc.jpg', 'Whisk_2c59a484d0.jpg', 'Whisk_2d65c0c0b8.jpg',
            'Whisk_33830a76fb.jpg', 'Whisk_359a4a288c.jpg', 'Whisk_36a1a142f8.jpg'
        ];
        
        // Shuffle the array for random order
        slideshow.images = shuffleArray(slideshow.images);
    }
    
    /**
     * Start the slideshow
     */
    function startSlideshow() {
        if (slideshow.isRunning) return;
        
        slideshow.isRunning = true;
        displayNextSlide();
        
        slideshow.intervalId = setInterval(() => {
            displayNextSlide();
        }, clarityMainPage.slideInterval);
    }
    
    /**
     * Stop the slideshow
     */
    function stopSlideshow() {
        slideshow.isRunning = false;
        if (slideshow.intervalId) {
            clearInterval(slideshow.intervalId);
            slideshow.intervalId = null;
        }
    }
    
    /**
     * Display next slide with random animation
     */
    function displayNextSlide() {
        if (slideshow.images.length === 0) {
            console.error('No images in slideshow array');
            return;
        }
        
        const imageUrl = clarityMainPage.netlifyUrl + '/' + slideshow.images[slideshow.currentIndex];
        const direction = slideshow.directions[Math.floor(Math.random() * slideshow.directions.length)];
        
        console.log('Loading slide:', imageUrl, 'Direction:', direction);
        
        // Create new slide element
        const $newSlide = $(`
            <div class="slideshow-slide ${direction}" style="background-image: url('${imageUrl}')">
                <div class="slide-overlay"></div>
            </div>
        `);
        
        // Add to container
        const $container = $('.slideshow-images');
        $container.append($newSlide);
        
        // Trigger animation
        setTimeout(() => {
            $newSlide.addClass('active');
        }, 100);
        
        // Remove old slides after animation
        setTimeout(() => {
            $container.find('.slideshow-slide').not(':last-child').remove();
        }, 1500);
        
        // Move to next image
        slideshow.currentIndex = (slideshow.currentIndex + 1) % slideshow.images.length;
        
        // Reshuffle when we complete a cycle
        if (slideshow.currentIndex === 0) {
            slideshow.images = shuffleArray(slideshow.images);
        }
    }
    
    /**
     * Setup background control panel
     * DEPRECATED: Controls moved to admin panel
     */
    function setupBackgroundControls() {
        // This function is no longer used since controls are in the admin panel
        return;
        
        // Original code commented out:
        /*
        // Toggle control panel
        $('#toggle-bg-controls').on('click', function() {
            $('#bg-controls-panel').slideToggle();
        });
        
        // Background type change
        $('input[name="bg_type"]').on('change', function() {
            const bgType = $(this).val();
            switchBackgroundType(bgType);
            
            // Show/hide relevant controls
            if (bgType === 'custom') {
                $('#custom-image-controls').slideDown();
                $('#slideshow-controls').slideUp();
            } else if (bgType === 'slideshow') {
                $('#custom-image-controls').slideUp();
                $('#slideshow-controls').slideDown();
            } else {
                $('#custom-image-controls').slideUp();
                $('#slideshow-controls').slideUp();
            }
        });
        
        // Image position controls
        $('.pos-btn').on('click', function() {
            $('.pos-btn').removeClass('active');
            $(this).addClass('active');
            
            const position = $(this).data('position');
            updateImagePosition(position);
        });
        
        // Preview slideshow
        $('#preview-slideshow').on('click', function() {
            if (slideshow.isRunning) {
                stopSlideshow();
                $(this).text('Preview Slideshow');
            } else {
                switchBackgroundType('slideshow');
                startSlideshow();
                $(this).text('Stop Preview');
            }
        });
        
        // Save settings
        $('#save-bg-settings').on('click', saveBgSettings);
        
        // Reset settings
        $('#reset-bg-settings').on('click', resetBgSettings);
    }
    
    /**
     * Setup image upload functionality
     * DEPRECATED: Controls moved to admin panel
     */
    function setupImageUpload() {
        // This function is no longer used since controls are in the admin panel
        return;
        
        // Original code commented out:
        /*
        const $dropZone = $('#image-drop-zone');
        const $fileInput = $('#bg-image-upload');
        
        // Click to upload
        $dropZone.on('click', function() {
            $fileInput.click();
        });
        
        // File input change
        $fileInput.on('change', function() {
            const file = this.files[0];
            if (file) {
                handleImageUpload(file);
            }
        });
        
        // Drag and drop
        $dropZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });
        
        $dropZone.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
        });
        
        $dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleImageUpload(files[0]);
            }
        });
    }
    
    /**
     * Handle image upload
     */
    function handleImageUpload(file) {
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageUrl = e.target.result;
            
            // Update preview
            const $dropZone = $('#image-drop-zone');
            $dropZone.html(`<img src="${imageUrl}" alt="Custom Background" id="preview-image">`);
            
            // Update background
            updateCustomBackground(imageUrl);
        };
        reader.readAsDataURL(file);
    }
    
    /**
     * Switch background type
     */
    function switchBackgroundType(bgType) {
        // Hide all backgrounds
        $('.hero-bg-elements, .hero-slideshow-container, .hero-custom-bg').removeClass('active');
        
        // Stop slideshow if running
        stopSlideshow();
        
        // Show selected background
        switch (bgType) {
            case 'slideshow':
                $('.hero-slideshow-container').addClass('active');
                startSlideshow();
                break;
            case 'custom':
                $('.hero-custom-bg').addClass('active');
                break;
            default:
                $('.hero-bg-elements').addClass('active');
        }
        
        // Update hero section data attribute
        $('#hero').attr('data-bg-type', bgType);
    }
    
    /**
     * Update custom background image
     */
    function updateCustomBackground(imageUrl) {
        const position = $('.pos-btn.active').data('position') || 'center center';
        $('.hero-custom-bg').css({
            'background-image': `url(${imageUrl})`,
            'background-position': position
        });
    }
    
    /**
     * Update image position
     */
    function updateImagePosition(position) {
        $('.hero-custom-bg').css('background-position', position);
    }
    
    /**
     * Save background settings
     * DEPRECATED: Now handled in admin panel
     */
    function saveBgSettings() {
        // This function is no longer used since settings are saved in the admin panel
        return;
        
        // Original code commented out:
        /*
        const bgType = $('input[name="bg_type"]:checked').val();
        const customImage = $('#preview-image').length ? $('#preview-image').attr('src') : '';
        const imagePosition = $('.pos-btn.active').data('position') || 'center center';
        
        // Save via AJAX
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_save_hero_bg_settings',
                nonce: clarityAjax.nonce,
                bg_type: bgType,
                custom_image: customImage,
                image_position: imagePosition
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Background settings saved successfully!', 'success');
                } else {
                    showMessage('Error saving settings: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error saving settings. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Reset background settings
     * DEPRECATED: Now handled in admin panel
     */
    function resetBgSettings() {
        // This function is no longer used since settings are managed in the admin panel
        return;
    }
    
    /**
     * Utility: Shuffle array
     */
    function shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        const className = type === 'error' ? 'alert-danger' : 'alert-success';
        const $message = $(`<div class="alert ${className}">${message}</div>`);
        
        // Add to controls panel
        $('.bg-controls-panel').prepend($message);
        
        // Remove after 3 seconds
        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 3000);
    }
    
    /**
     * Smooth scrolling for navigation
     */
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });
    
    /**
     * Mobile menu toggle
     */
    $('.mobile-nav-toggle').on('click', function() {
        $('body').toggleClass('mobile-nav-active');
    });
});