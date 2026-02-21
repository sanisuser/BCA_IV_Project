<?php
/**
 * legal/terms.php
 * 
 * Terms of Service page for BookHub.
 */

$page_title = 'Terms of Service';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header_navbar.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/legal/css/legal.css">

<div class="legal-container">
    <div class="legal-card">
        
        <div class="legal-header">
            <h1 class="legal-title">Terms of Service</h1>
            <p class="legal-subtitle">Last updated: February 2026</p>
        </div>
        
        <div class="legal-content">
            <section class="legal-section">
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using BookHub, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our service.</p>
            </section>
            
            <section class="legal-section">
                <h2>2. Description of Service</h2>
                <p>BookHub is an online platform that allows users to browse, purchase, and manage book orders. We provide a catalog of books across various genres including fiction, history, and science.</p>
            </section>
            
            <section class="legal-section">
                <h2>3. User Accounts</h2>
                <p>To use certain features of our service, you must register for an account. You agree to:</p>
                <ul>
                    <li>Provide accurate and complete information during registration</li>
                    <li>Maintain the security of your account password</li>
                    <li>Accept responsibility for all activities that occur under your account</li>
                    <li>Notify us immediately of any unauthorized use of your account</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>4. Ordering and Payment</h2>
                <p>When you place an order through BookHub:</p>
                <ul>
                    <li>You agree to provide current, complete, and accurate purchase information</li>
                    <li>All prices are displayed in Nepalese Rupees (Rs) and include applicable VAT (13%)</li>
                    <li>We reserve the right to refuse or cancel orders at any time</li>
                    <li>Stock availability is subject to change without notice</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>5. Shipping and Delivery</h2>
                <p>We deliver to the shipping address you provide during checkout. You are responsible for ensuring the accuracy of your shipping information. We are not liable for delays caused by incorrect addresses.</p>
            </section>
            
            <section class="legal-section">
                <h2>6. Returns and Refunds</h2>
                <p>Please contact us within 7 days of receiving your order if you wish to request a return or refund. Items must be in original condition. Refunds will be processed to the original payment method.</p>
            </section>
            
            <section class="legal-section">
                <h2>7. User Conduct</h2>
                <p>You agree not to use BookHub for any unlawful purpose or to:</p>
                <ul>
                    <li>Harass, abuse, or harm other users</li>
                    <li>Upload malicious code or attempt to breach our security</li>
                    <li>Use automated systems to access our service without permission</li>
                    <li>Resell books purchased through our platform without authorization</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>8. Intellectual Property</h2>
                <p>All content on BookHub, including text, graphics, logos, and software, is the property of BookHub or its content suppliers and is protected by copyright and other intellectual property laws.</p>
            </section>
            
            <section class="legal-section">
                <h2>9. Limitation of Liability</h2>
                <p>BookHub shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of our service. Our total liability shall not exceed the amount you paid for the specific transaction giving rise to the claim.</p>
            </section>
            
            <section class="legal-section">
                <h2>10. Changes to Terms</h2>
                <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of BookHub after changes constitutes acceptance of the revised terms.</p>
            </section>
            
            <section class="legal-section">
                <h2>11. Contact Information</h2>
                <p>If you have any questions about these Terms of Service, please contact us at:</p>
                <p><strong>Email:</strong> support@bookhub.com<br>
                <strong>Address:</strong> BookHub Headquarters, Kathmandu, Nepal</p>
            </section>
        </div>
        
        <div class="legal-footer">
            <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-secondary">Back to Registration</a>
            <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-primary">Go to Home</a>
        </div>
        
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
