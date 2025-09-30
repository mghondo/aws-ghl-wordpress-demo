/**
 * MainPage JavaScript - Clean Version
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🔧 jQuery loaded:', typeof $ !== 'undefined');
    console.log('🔧 Document ready, starting slideshow initialization');
    
    // Make $ available globally for debugging
    window.$ = $;
    window.jQuery = jQuery;
    
    let slideshow = {
        images: [],
        currentIndex: 0,
        isRunning: false,
        intervalId: null
    };
    
    // Initialize
    init();
    
    function init() {
        console.log('🚀 MainPage init - Background type:', clarityMainPage.bgType);
        console.log('🔍 Available clarityMainPage object:', clarityMainPage);
        
        // Load image inventory (async) - slideshow will start after images are loaded
        loadImageInventory();
    }
    
    function loadImageInventory() {
        console.log('Loading image inventory...');
        
        const inventoryUrl = clarityMainPage.themeUri + '/assets/imageInventory.json';
        console.log('Fetching inventory from:', inventoryUrl);
        
        $.ajax({
            url: inventoryUrl,
            dataType: 'json',
            cache: false,
            success: function(data) {
                console.log('Successfully loaded imageInventory.json:', data);
                
                slideshow.images = [];
                
                if (data && typeof data === 'object') {
                    Object.keys(data).forEach(category => {
                        if (data[category] && typeof data[category] === 'object') {
                            Object.keys(data[category]).forEach(subcategory => {
                                if (Array.isArray(data[category][subcategory])) {
                                    data[category][subcategory].forEach(filename => {
                                        slideshow.images.push({
                                            path: category + '/' + subcategory + '/' + filename,
                                            filename: filename
                                        });
                                    });
                                }
                            });
                        }
                    });
                    
                    console.log('Found', slideshow.images.length, 'images across all categories');
                    console.log('Sample paths:', slideshow.images.slice(0, 5).map(img => img.path));
                } else {
                    console.warn('Invalid imageInventory.json structure, using fallback images');
                    loadFallbackImages();
                }
                
                slideshow.images = shuffleArray(slideshow.images);
                console.log('Shuffled images:', slideshow.images.slice(0, 5));
                
                if (clarityMainPage.bgType === 'slideshow') {
                    console.log('Starting slideshow with', slideshow.images.length, 'images...');
                    startSlideshow();
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load imageInventory.json:', error, 'Status:', status);
                console.log('Using fallback images instead');
                loadFallbackImages();
                
                slideshow.images = shuffleArray(slideshow.images);
                if (clarityMainPage.bgType === 'slideshow') {
                    console.log('Starting slideshow with fallback images...');
                    startSlideshow();
                }
            }
        });
    }
    
    function loadFallbackImages() {
        slideshow.images = [
            { path: 'A/Anywhere/Whisk_0071c25679.jpg', filename: 'Whisk_0071c25679.jpg' },
            { path: 'A/Anywhere/Whisk_04c695ef4e.jpg', filename: 'Whisk_04c695ef4e.jpg' },
            { path: 'A/Anywhere/Whisk_0854079d29.jpg', filename: 'Whisk_0854079d29.jpg' }
        ];
        console.log('Loaded', slideshow.images.length, 'fallback images');
    }
    
    function startSlideshow() {
        if (slideshow.isRunning) return;
        
        slideshow.isRunning = true;
        displayNextSlide();
        
        slideshow.intervalId = setInterval(() => {
            displayNextSlide();
        }, clarityMainPage.slideInterval);
    }
    
    function displayNextSlide() {
        if (slideshow.images.length === 0) {
            console.error('No images in slideshow array');
            return;
        }
        
        const imageObj = slideshow.images[slideshow.currentIndex];
        const imageUrl = clarityMainPage.netlifyUrl + '/' + imageObj.path;
        
        console.log('Setting hero background to:', imageUrl, '(from', imageObj.path, ')');
        
        // Apply background to hero-overlay (covers entire hero area)
        const $heroOverlay = $('.hero-overlay');
        $heroOverlay.css({
            'background-image': `url('${imageUrl}')`,
            'background-size': 'cover',
            'background-position': 'center',
            'background-repeat': 'no-repeat'
        });
        
        // Move to next image
        slideshow.currentIndex = (slideshow.currentIndex + 1) % slideshow.images.length;
        
        // Reshuffle when we complete a cycle
        if (slideshow.currentIndex === 0) {
            slideshow.images = shuffleArray(slideshow.images);
        }
    }
    
    function shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }
    
});