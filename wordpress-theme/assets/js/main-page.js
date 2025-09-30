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
        intervalId: null,
        currentLayer: 'layer1', // Track which layer is currently visible
        kenBurnsEffects: [
            'pan-up', 'pan-down', 'pan-left', 'pan-right',
            'pan-top-left', 'pan-top-right', 'pan-bottom-left', 'pan-bottom-right'
        ]
    };
    
    // Initialize
    init();
    
    function init() {
        console.log('🚀 MainPage init - Background type:', clarityMainPage.bgType);
        console.log('🔍 Available clarityMainPage object:', clarityMainPage);
        
        // Create dual background layers for smooth crossfade
        if (clarityMainPage.bgType === 'slideshow') {
            createDualBackgroundLayers();
        }
        
        // Load image inventory (async) - slideshow will start after images are loaded
        loadImageInventory();
    }
    
    function createDualBackgroundLayers() {
        const $heroOverlay = $('.hero-overlay');
        
        // Create two background layers
        const layer1 = $('<div class="slideshow-layer" id="slideshow-layer1"></div>');
        const layer2 = $('<div class="slideshow-layer" id="slideshow-layer2"></div>');
        
        // Create darkness overlay layer
        const darkness = clarityMainPage.heroDarkness / 100;
        const darknessLayer = $(`<div class="slideshow-darkness" style="
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, ${darkness});
            z-index: 3;
            pointer-events: none;
        "></div>`);
        
        // Clear hero-overlay background and insert all layers
        $heroOverlay.css('background', 'none');
        $heroOverlay.prepend(layer1, layer2, darknessLayer);
        
        console.log('Created dual background layers for smooth crossfade with darkness:', darkness);
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
        const kenBurnsEffect = slideshow.kenBurnsEffects[Math.floor(Math.random() * slideshow.kenBurnsEffects.length)];
        
        console.log('Setting hero background to:', imageUrl, 'with Ken Burns effect:', kenBurnsEffect);
        
        // Determine which layer to use for new image
        const nextLayer = slideshow.currentLayer === 'layer1' ? 'layer2' : 'layer1';
        const currentLayerEl = $('#slideshow-' + slideshow.currentLayer);
        const nextLayerEl = $('#slideshow-' + nextLayer);
        
        // Update overlay darkness based on admin setting
        const darkness = clarityMainPage.heroDarkness / 100;
        
        // Set up the new image on the hidden layer
        nextLayerEl.css({
            'background-image': `url('${imageUrl}')`,
            'background-size': 'cover',
            'background-position': 'center',
            'background-repeat': 'no-repeat',
            'opacity': 0
        });
        
        // Remove old Ken Burns classes from current layer
        currentLayerEl.removeClass('ken-burns pan-up pan-down pan-left pan-right pan-top-left pan-top-right pan-bottom-left pan-bottom-right');
        
        // Add Ken Burns effect to new layer and fade it in
        nextLayerEl.addClass('ken-burns ' + kenBurnsEffect);
        
        // Crossfade: fade out current, fade in next (0.5s)
        currentLayerEl.css('transition', 'opacity 0.5s ease-in-out');
        nextLayerEl.css('transition', 'opacity 0.5s ease-in-out');
        
        currentLayerEl.css('opacity', 0);
        nextLayerEl.css('opacity', 1);
        
        // Update which layer is current
        slideshow.currentLayer = nextLayer;
        
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