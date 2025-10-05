<?php
/**
 * Template Name: Checkout Page
 * 
 * Mock payment checkout page for course enrollment
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check for demo mode
$is_demo = isset($_GET['demo']) && $_GET['demo'] === 'true';

// Require user to be logged in (unless in demo mode)
if (!is_user_logged_in() && !$is_demo) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
    exit;
}

// Get course ID from URL parameter (default to 2 for testing)
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 2;

// Initialize course routing for intelligent cart
$course_routing = new Clarity_AWS_GHL_Course_Routing();

// Get course data
global $wpdb;
$courses_table = $wpdb->prefix . 'clarity_courses';
$course = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$courses_table} WHERE id = %d AND course_status = 'published'",
    $course_id
));

// If course not found, redirect to dashboard
if (!$course) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Build intelligent cart with bundling
$user_id = is_user_logged_in() ? get_current_user_id() : 0;
$cart = $course_routing->build_checkout_cart($user_id, $course_id);

// Check if already enrolled (skip in demo mode)
$enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
if (!$is_demo && is_user_logged_in()) {
    $existing_enrollment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$enrollments_table} WHERE user_id = %d AND course_id = %d",
        get_current_user_id(),
        $course_id
    ));

    if ($existing_enrollment) {
        wp_redirect(home_url('/course/' . $course->course_slug));
        exit;
    }
}

// Get current user or use demo user
if ($is_demo && !is_user_logged_in()) {
    // Create a fake user object for demo
    $current_user = (object) array(
        'user_login' => 'demo_user',
        'display_name' => 'Demo User',
        'ID' => 0
    );
} else {
    $current_user = wp_get_current_user();
}

// Handle form submission (disabled in demo mode)
if (isset($_POST['process_payment']) && !$is_demo) {
    // Verify nonce
    if (wp_verify_nonce($_POST['payment_nonce'], 'process_mock_payment')) {
        
        // Process enrollment for all courses in cart
        $enrollment_ids = $course_routing->process_post_payment_enrollment(
            get_current_user_id(),
            $cart['courses'],
            $cart['total']
        );
        
        // Redirect to dashboard with success
        wp_redirect(home_url('/dashboard?enrolled=success'));
        exit;
    }
}

get_header();
?>

<div class="checkout-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Demo Notice -->
                <div class="demo-notice">
                    <i class="bi bi-info-circle"></i>
                    This is a demo checkout - no real payment will be processed
                    <?php if ($is_demo): ?>
                        <br><strong>🎯 DEMO MODE ACTIVE - Not logged in</strong>
                    <?php endif; ?>
                </div>

                <!-- Course Summary -->
                <div class="course-summary">
                    <h2>Order Summary</h2>
                    
                    <?php if (!empty($cart['bundle_message'])): ?>
                    <div class="bundle-message">
                        <i class="bi bi-info-circle"></i>
                        <?php echo esc_html($cart['bundle_message']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cart-items">
                        <?php foreach ($cart['courses'] as $cart_course): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <?php if (!empty($cart_course->featured_image)): ?>
                                <div class="col-md-2">
                                    <img src="<?php echo esc_attr($cart_course->featured_image); ?>" 
                                         alt="<?php echo esc_attr($cart_course->course_title); ?>" 
                                         class="cart-item-thumbnail">
                                </div>
                                <div class="col-md-10">
                                <?php else: ?>
                                <div class="col-md-12">
                                <?php endif; ?>
                                    <div class="cart-item-details">
                                        <h4><?php echo esc_html($cart_course->course_title); ?></h4>
                                        <p class="cart-item-desc"><?php echo esc_html($cart_course->course_description); ?></p>
                                        <div class="cart-item-price">
                                            <?php if ($cart_course->course_price == 0): ?>
                                                <span class="badge bg-success">FREE</span>
                                            <?php else: ?>
                                                <span class="price">$<?php echo number_format($cart_course->course_price, 0); ?></span>
                                            <?php endif; ?>
                                            <?php if ($cart_course->course_tier == 1): ?>
                                                <span class="badge bg-info ms-2">Prerequisite</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Price Breakdown -->
                    <div class="price-breakdown">
                        <?php if ($cart['discount'] > 0): ?>
                        <div class="price-line">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($cart['subtotal'], 2); ?></span>
                        </div>
                        <div class="price-line discount">
                            <span>Bundle Discount (<?php echo $cart['discount_percentage']; ?>%):</span>
                            <span>-$<?php echo number_format($cart['discount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="price-line total">
                            <span>Total:</span>
                            <span class="total-amount">$<?php echo number_format($cart['total'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="payment-form-wrapper">
                    <h2>Payment Information</h2>
                    
                    <!-- Generate Mock Data Button -->
                    <div class="generate-button-wrapper">
                        <button type="button" id="generate-mock-data" class="btn btn-generate">
                            <i class="bi bi-credit-card"></i>
                            Generate Mock Payment Info
                        </button>
                        <small class="text-muted d-block mt-2">Click to auto-fill with test data</small>
                    </div>

                    <form method="post" id="payment-form" class="payment-form">
                        <?php wp_nonce_field('process_mock_payment', 'payment_nonce'); ?>
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="card_number">Card Number</label>
                                    <input type="text" 
                                           id="card_number" 
                                           name="card_number" 
                                           class="form-control" 
                                           placeholder="0000 0000 0000 0000"
                                           maxlength="19"
                                           required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expiry_date">Expiration Date</label>
                                    <input type="text" 
                                           id="expiry_date" 
                                           name="expiry_date" 
                                           class="form-control" 
                                           placeholder="MM/YY"
                                           maxlength="5"
                                           required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cvv">CVV</label>
                                    <input type="text" 
                                           id="cvv" 
                                           name="cvv" 
                                           class="form-control" 
                                           placeholder="000"
                                           maxlength="3"
                                           required>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="cardholder_name">Cardholder Name</label>
                                    <input type="text" 
                                           id="cardholder_name" 
                                           name="cardholder_name" 
                                           class="form-control" 
                                           placeholder="Name on card"
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="submit-wrapper" id="submit-wrapper">
                            <button type="submit" name="process_payment" class="btn btn-primary btn-submit">
                                <span class="button-text">
                                    <i class="bi bi-lock"></i>
                                    <?php if ($cart['total'] == 0): ?>
                                        Start Learning Free
                                    <?php else: ?>
                                        Process Payment - $<?php echo number_format($cart['total'], 2); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="spinner-border spinner-border-sm" role="status" style="display: none;">
                                    <span class="visually-hidden">Processing...</span>
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Processing Message -->
                    <div id="processing-message" class="processing-message" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Processing...</span>
                        </div>
                        <p>Processing payment...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 0;
}

.demo-notice {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    text-align: center;
    font-weight: 500;
}

.demo-notice i {
    margin-right: 8px;
}

.course-summary {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.course-summary h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 25px;
    color: #2c3e50;
}

.bundle-message {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.bundle-message i {
    margin-right: 8px;
}

.cart-items {
    border-top: 1px solid #e9ecef;
    padding-top: 20px;
}

.cart-item {
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-thumbnail {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-item h4 {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.cart-item-desc {
    color: #6c757d;
    margin-bottom: 10px;
    font-size: 13px;
}

.cart-item-price .price {
    font-size: 20px;
    font-weight: 600;
    color: #667eea;
}

.price-breakdown {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #e9ecef;
}

.price-line {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 16px;
}

.price-line.discount {
    color: #28a745;
    font-weight: 500;
}

.price-line.total {
    font-size: 20px;
    font-weight: 700;
    padding-top: 12px;
    border-top: 1px solid #e9ecef;
    margin-top: 8px;
}

.total-amount {
    color: #667eea;
}

.price-amount {
    font-size: 32px;
    font-weight: 700;
    color: #667eea;
}

.price-free {
    background: #28a745;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
}

.payment-form-wrapper {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.payment-form-wrapper h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 25px;
    color: #2c3e50;
}

.generate-button-wrapper {
    text-align: center;
    padding: 20px;
    margin-bottom: 30px;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    background: #f8f9fa;
}

.btn-generate {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 40px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 30px;
    transition: transform 0.2s ease;
}

.btn-generate:hover {
    transform: translateY(-2px);
    color: white;
}

.payment-form {
    border-top: 1px solid #e9ecef;
    padding-top: 30px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.submit-wrapper {
    margin-top: 30px;
    text-align: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 40px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 30px;
    width: 100%;
    transition: transform 0.2s ease;
}

.btn-submit:hover {
    transform: translateY(-2px);
}

.processing-message {
    text-align: center;
    padding: 40px;
}

.processing-message .spinner-border {
    width: 3rem;
    height: 3rem;
    margin-bottom: 20px;
}

.processing-message p {
    font-size: 18px;
    color: #6c757d;
}

@media (max-width: 768px) {
    .checkout-container {
        padding: 30px 0;
    }
    
    .course-summary,
    .payment-form-wrapper {
        padding: 20px;
    }
    
    .btn-generate {
        font-size: 16px;
        padding: 12px 30px;
    }
}
</style>

<script>
// Add debug logging
console.log('Checkout page loaded');
console.log('jQuery available:', typeof jQuery !== 'undefined');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready');
    
    // Generate mock payment data
    document.getElementById('generate-mock-data').addEventListener('click', function() {
        // Generate card number (starting with 6, 7, 8, or 9)
        var firstDigit = Math.floor(Math.random() * 4) + 6; // 6, 7, 8, or 9
        var cardNumber = firstDigit.toString();
        for (var i = 0; i < 15; i++) {
            cardNumber += Math.floor(Math.random() * 10);
            if ((i + 2) % 4 === 0 && i < 14) {
                cardNumber += ' ';
            }
        }
        
        // Generate expiration date (1-3 years from now)
        var currentDate = new Date();
        var futureYear = currentDate.getFullYear() + Math.floor(Math.random() * 3) + 1;
        var month = Math.floor(Math.random() * 12) + 1;
        var monthStr = month < 10 ? '0' + month : month.toString();
        var yearStr = futureYear.toString().substr(-2);
        var expiryDate = monthStr + '/' + yearStr;
        
        // Generate CVV
        var cvv = Math.floor(Math.random() * 900) + 100;
        
        // Get current username
        var cardholderName = '<?php echo esc_js($current_user->user_login); ?>';
        
        // Fill the form
        document.getElementById('card_number').value = cardNumber;
        document.getElementById('expiry_date').value = expiryDate;
        document.getElementById('cvv').value = cvv.toString();
        document.getElementById('cardholder_name').value = cardholderName;
        
        // Show success message
        console.log('Mock data generated and filled');
        
        // Scroll to form
        document.getElementById('payment-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Hide the generate button
        this.disabled = true;
        this.textContent = 'Mock Data Generated';
    });
    
    // Format card number input
    document.getElementById('card_number').addEventListener('input', function() {
        var value = this.value.replace(/\s/g, '');
        var formattedValue = '';
        for (var i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        this.value = formattedValue;
    });
    
    // Format expiry date input
    document.getElementById('expiry_date').addEventListener('input', function() {
        var value = this.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substr(0, 2) + '/' + value.substr(2, 2);
        }
        this.value = value;
    });
    
    // Only allow numbers for CVV
    document.getElementById('cvv').addEventListener('input', function() {
        var value = this.value.replace(/\D/g, '');
        this.value = value;
    });
    
    // Handle form submission
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show processing message
        document.getElementById('payment-form').style.display = 'none';
        document.getElementById('processing-message').style.display = 'block';
        
        <?php if ($is_demo): ?>
        // In demo mode, just show processing then fake success
        setTimeout(function() {
            alert('🎯 DEMO MODE: Payment processed successfully!\n\nIn real mode, you would be enrolled and redirected to dashboard.');
            location.reload();
        }, 2000);
        <?php else: ?>
        // Wait 2 seconds then submit for real
        setTimeout(function() {
            document.getElementById('payment-form').submit();
        }, 2000);
        <?php endif; ?>
    });
});
</script>

<?php get_footer(); ?>