<?php
/**
 * legal/privacy.php
 * 
 * Privacy Policy page for BookHub.
 */

$page_title = 'Privacy Policy';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header_navbar.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/legal/css/legal.css">

<div class="legal-container">
    <div class="legal-card">
        
        <div class="legal-header">
            <h1 class="legal-title">Privacy Policy</h1>
            <p class="legal-subtitle">Last updated: February 2026</p>
        </div>
        
        <div class="legal-content">
            <section class="legal-section">
                <h2>1. Introduction</h2>
                <p>At BookHub, we respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and services.</p>
            </section>
            
            <section class="legal-section">
                <h2>2. Information We Collect</h2>
                <p>We collect the following types of information:</p>
                <ul>
                    <li><strong>Personal Information:</strong> Name, email address, phone number, shipping address, and billing information</li>
                    <li><strong>Account Information:</strong> Username, password, and profile preferences</li>
                    <li><strong>Order Information:</strong> Purchase history, items in cart, and wishlist</li>
                    <li><strong>Usage Data:</strong> How you interact with our website, including pages visited and features used</li>
                    <li><strong>Device Information:</strong> IP address, browser type, and operating system</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>3. How We Collect Information</h2>
                <p>We collect information through:</p>
                <ul>
                    <li>Direct input when you register, place orders, or update your profile</li>
                    <li>Automatic collection via cookies and similar technologies</li>
                    <li>Third-party payment processors for transaction processing</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>4. How We Use Your Information</h2>
                <p>We use your information to:</p>
                <ul>
                    <li>Process and fulfill your orders</li>
                    <li>Manage your account and provide customer support</li>
                    <li>Send order confirmations, shipping notifications, and updates</li>
                    <li>Improve our website and services</li>
                    <li>Prevent fraud and ensure security</li>
                    <li>Comply with legal obligations</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>5. Cookies and Tracking</h2>
                <p>We use cookies and similar technologies to:</p>
                <ul>
                    <li>Remember your login status and preferences</li>
                    <li>Maintain your shopping cart across sessions</li>
                    <li>Analyze website traffic and usage patterns</li>
                    <li>Enhance your browsing experience</li>
                </ul>
                <p>You can control cookies through your browser settings. However, disabling cookies may affect certain features of our service.</p>
            </section>
            
            <section class="legal-section">
                <h2>6. Information Sharing</h2>
                <p>We do not sell your personal information. We may share information with:</p>
                <ul>
                    <li><strong>Service Providers:</strong> Shipping companies and payment processors to fulfill orders</li>
                    <li><strong>Legal Authorities:</strong> When required by law or to protect our rights</li>
                    <li><strong>Business Transfers:</strong> In connection with a merger, sale, or acquisition</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>7. Data Security</h2>
                <p>We implement appropriate security measures to protect your information, including:</p>
                <ul>
                    <li>Encrypted connections (HTTPS) for all data transmission</li>
                    <li>Password hashing for account security</li>
                    <li>Regular security assessments and updates</li>
                    <li>Limited access to personal information by authorized personnel only</li>
                </ul>
            </section>
            
            <section class="legal-section">
                <h2>8. Data Retention</h2>
                <p>We retain your personal information for as long as necessary to:</p>
                <ul>
                    <li>Provide our services and fulfill the purposes outlined in this policy</li>
                    <li>Comply with legal obligations</li>
                    <li>Resolve disputes and enforce our agreements</li>
                </ul>
                <p>You may request deletion of your account and associated data by contacting us.</p>
            </section>
            
            <section class="legal-section">
                <h2>9. Your Rights</h2>
                <p>You have the right to:</p>
                <ul>
                    <li>Access your personal information</li>
                    <li>Correct inaccurate or incomplete information</li>
                    <li>Request deletion of your personal data</li>
                    <li>Opt out of marketing communications</li>
                    <li>Export your data in a portable format</li>
                </ul>
                <p>To exercise these rights, please contact us using the information provided below.</p>
            </section>
            
            <section class="legal-section">
                <h2>10. Children's Privacy</h2>
                <p>BookHub is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If we learn we have collected such information, we will delete it promptly.</p>
            </section>
            
            <section class="legal-section">
                <h2>11. Changes to This Policy</h2>
                <p>We may update this Privacy Policy from time to time. Changes will be posted on this page with a revised "Last updated" date. We encourage you to review this policy periodically.</p>
            </section>
            
            <section class="legal-section">
                <h2>12. Contact Us</h2>
                <p>If you have any questions or concerns about this Privacy Policy or our data practices, please contact us at:</p>
                <p><strong>Email:</strong> privacy@bookhub.com<br>
                <strong>Address:</strong> BookHub Headquarters, Kathmandu, Nepal<br>
                <strong>Phone:</strong> +977-1-XXXXXXX</p>
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
