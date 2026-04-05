<?php
$page_title = 'Checkout';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to checkout'));
}

$user_id = (int)get_user_id();

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Fetch user's profile shipping address and coordinates
$profile_ship_address = '';
$profile_ship_lat = '';
$profile_ship_lng = '';
$profile_ship_place_name = '';
$ship_stmt = $conn->prepare("SELECT ship_address, ship_latitude, ship_longitude, ship_place_name FROM users WHERE user_id = ?");
$ship_stmt->bind_param('i', $user_id);
$ship_stmt->execute();
$ship_result = $ship_stmt->get_result();
if ($ship_row = $ship_result->fetch_assoc()) {
    $profile_ship_address = $ship_row['ship_address'] ?? '';
    $profile_ship_lat = $ship_row['ship_latitude'] ?? '';
    $profile_ship_lng = $ship_row['ship_longitude'] ?? '';
    $profile_ship_place_name = $ship_row['ship_place_name'] ?? '';
}
$ship_stmt->close();

$cart_items = [];
$total = 0;

// Check if user has a profile shipping address
$has_profile_address = !empty($profile_ship_address);

$stmt = $conn->prepare('
    SELECT c.cart_id, c.quantity, c.book_id, b.title, b.price, b.stock, b.cover_image
    FROM cart c
    JOIN books b ON c.book_id = b.book_id
    WHERE c.user_id = ?
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}
$stmt->close();

if (count($cart_items) === 0) {
    redirect(SITE_URL . '/order_cart_process/cart.php?error=' . urlencode('Your cart is empty'));
}
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/order_cart_process/css/checkout.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/order_cart_process/css/address.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<div class="checkout-container">
    <div class="checkout-header">
        <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        <p class="checkout-subtitle">Complete your order by providing your details below</p>
    </div>

    <div class="checkout-layout">
        <!-- Checkout Form -->
        <div class="checkout-form-wrapper">
            <form method="POST" action="<?php echo SITE_URL; ?>/order_cart_process/process/order_process.php">
                <input type="hidden" name="action" value="place">

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

                <!-- Shipping Section -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                    
                    <?php if ($has_profile_address): ?>
                        <!-- User has profile shipping address - show as default option -->
                        <div class="address-selection">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Select Shipping Address</label>
                           
                            <div class="address-option">
                                <label class="address-radio-label">
                                    <input type="radio" name="selected_address" value="profile" checked
                                           class="address-radio"
                                           onchange="toggleCustomAddress(this)">
                                    <div class="address-card">
                                        <div class="address-header">
                                            <span class="address-type">Profile Address</span>
                                            <span class="default-badge">Default</span>
                                            <?php if (!empty($profile_ship_lat) && !empty($profile_ship_lng)): ?>
                                            <span class="location-badge"><i class="fas fa-map-pin"></i> Map Selected</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="address-details">
                                            <p><?php echo nl2br(htmlspecialchars($profile_ship_address)); ?></p>
                                            <?php if (!empty($profile_ship_place_name)): ?>
                                            <small style="color: #6c757d; font-style: italic;"><?php echo htmlspecialchars($profile_ship_place_name); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                           
                            <div class="address-option">
                                <label class="address-radio-label">
                                    <input type="radio" name="selected_address" value="map" class="address-radio" onchange="toggleMapSelection(this)">
                                    <div class="address-card map-address-card">
                                        <div class="address-header">
                                            <span class="address-type">Select on Map</span>
                                        </div>
                                        <div class="address-details">
                                            <p><i class="fas fa-map"></i> Choose your delivery location on map</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="address-option">
                                <label class="address-radio-label">
                                    <input type="radio" name="selected_address" value="custom" class="address-radio" onchange="toggleCustomAddress(this)">
                                    <div class="address-card custom-address-card">
                                        <div class="address-header">
                                            <span class="address-type">Custom Address</span>
                                        </div>
                                        <div class="address-details">
                                            <p><i class="fas fa-plus"></i> Enter a different shipping address</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Map Selection Form (Hidden by default) -->
                        <div id="map-selection-form" style="display: none;" class="map-selection-section">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-map"></i> Select Delivery Location</label>
                                <div id="checkoutMap" style="height: 300px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 12px;"></div>
                                <div id="selectedLocationInfo" style="display: none; padding: 12px; background: #e8f5e8; border-radius: 6px; margin-bottom: 12px;">
                                    <strong><i class="fas fa-check-circle" style="color: #28a745;"></i> Selected Location:</strong>
                                    <p id="selectedAddressText" style="margin: 8px 0 0 0;"></p>
                                </div>
                                <input type="hidden" name="map_address" id="mapAddressInput" value="">
                                <input type="hidden" name="map_latitude" id="mapLatitudeInput" value="">
                                <input type="hidden" name="map_longitude" id="mapLongitudeInput" value="">
                                <input type="hidden" name="map_place_name" id="mapPlaceNameInput" value="">
                            </div>
                        </div>
                        
                        <!-- Custom Address Form (Hidden by default) -->
                        <div id="custom-address-form" style="display: none;" class="custom-address-section">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-map-marker-alt"></i> Custom Shipping Address</label>
                                <textarea name="ship_address" rows="4" class="form-textarea"
                                          placeholder="Enter your full address including street, city, and postal code..."></textarea>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- No profile address, show all options -->
                        <div class="address-selection">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Select Shipping Address</label>
                            
                            <div class="address-option">
                                <label class="address-radio-label">
                                    <input type="radio" name="selected_address" value="map" class="address-radio" onchange="toggleMapSelection(this)">
                                    <div class="address-card map-address-card">
                                        <div class="address-header">
                                            <span class="address-type">Select on Map</span>
                                        </div>
                                        <div class="address-details">
                                            <p><i class="fas fa-map"></i> Choose your delivery location on map</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="address-option">
                                <label class="address-radio-label">
                                    <input type="radio" name="selected_address" value="custom" checked class="address-radio" onchange="toggleCustomAddress(this)">
                                    <div class="address-card custom-address-card">
                                        <div class="address-header">
                                            <span class="address-type">Custom Address</span>
                                        </div>
                                        <div class="address-details">
                                            <p><i class="fas fa-plus"></i> Enter your shipping address</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Map Selection Form (Hidden by default) -->
                        <div id="map-selection-form" style="display: none;" class="map-selection-section">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-map"></i> Select Delivery Location</label>
                                <div id="checkoutMap" style="height: 300px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 12px;"></div>
                                <div id="selectedLocationInfo" style="display: none; padding: 12px; background: #e8f5e8; border-radius: 6px; margin-bottom: 12px;">
                                    <strong><i class="fas fa-check-circle" style="color: #28a745;"></i> Selected Location:</strong>
                                    <p id="selectedAddressText" style="margin: 8px 0 0 0;"></p>
                                </div>
                                <input type="hidden" name="map_address" id="mapAddressInput" value="">
                                <input type="hidden" name="map_latitude" id="mapLatitudeInput" value="">
                                <input type="hidden" name="map_longitude" id="mapLongitudeInput" value="">
                                <input type="hidden" name="map_place_name" id="mapPlaceNameInput" value="">
                            </div>
                        </div>
                        
                        <!-- Custom Address Form (Hidden by default) -->
                        <div id="custom-address-form" style="display: block;" class="custom-address-section">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-map-marker-alt"></i> Shipping Address</label>
                                <textarea name="ship_address" rows="4" required class="form-textarea"
                                          placeholder="Enter your full address including street, city, and postal code..."></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Summary (Mobile Only) -->
                <div class="mobile-order-summary">
                    <h3 class="summary-header"><i class="fas fa-shopping-bag"></i> Order Summary</h3>

                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item"> 
                                <div class="summary-item-image">
                                    <img src="<?php echo SITE_URL . '/' . get_book_cover($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                                </div>
                                <div class="summary-item-details">
                                    <div class="summary-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="summary-item-meta">Qty: <?php echo (int)$item['quantity']; ?> × <?php echo format_price($item['price']); ?></div>
                                </div>
                                <div class="summary-item-price"><?php echo format_price(($item['price'] ?? 0) * ($item['quantity'] ?? 0)); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr class="summary-divider">

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo format_price($total); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>
                    <div class="summary-row">
                        <span>VAT (13%)</span>
                        <span><?php echo format_price($total * 0.13); ?></span>
                    </div>

                    <div class="summary-total">
                        <span>Total (incl. VAT)</span>
                        <span><?php echo format_price($total * 1.13); ?></span>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-wallet"></i> Payment Method</h3>
                    
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash_on_delivery" checked>
                            <div class="payment-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="payment-details">
                                <div class="payment-name">Cash on Delivery</div>
                                <div class="payment-desc">Pay when you receive your order</div>
                            </div>
                        </label>
                        
                        <label class="payment-option" style="opacity: 0.6; cursor: not-allowed;">
                            <input type="radio" name="payment_method" value="card" disabled>
                            <div class="payment-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="payment-details">
                                <div class="payment-name">Credit/Debit Card</div>
                                <div class="payment-desc" style="color: #dc3545;">
                                    <i class="fas fa-info-circle"></i> Coming Soon - Not available yet
                                </div>
                                <div class="payment-notice" style="font-size: 0.8rem; color: #6c757d; margin-top: 4px;">
                                    This payment system is under development. Tune in for future updates!
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-place-order">
                    <i class="fas fa-lock"></i> Place Order
                </button>
            </form>
        </div>

        <!-- Order Summary (Desktop Only) -->
        <div class="order-summary desktop-summary">
            <h3 class="summary-header"><i class="fas fa-shopping-bag"></i> Order Summary</h3>

            <div class="summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item"> 
                        <div class="summary-item-image">
                            <img src="<?php echo SITE_URL . '/' . get_book_cover($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                        </div>
                        <div class="summary-item-details">
                            <div class="summary-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="summary-item-meta">Qty: <?php echo (int)$item['quantity']; ?> × <?php echo format_price($item['price']); ?></div>
                        </div>
                        <div class="summary-item-price"><?php echo format_price(($item['price'] ?? 0) * ($item['quantity'] ?? 0)); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr class="summary-divider">

            <div class="summary-row">
                <span>Subtotal</span>
                <span><?php echo format_price($total); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span>Free</span>
            </div>
            <div class="summary-row">
                <span>VAT (13%)</span>
                <span><?php echo format_price($total * 0.13); ?></span>
            </div>

            <div class="summary-total">
                <span>Total (incl. VAT)</span>
                <span><?php echo format_price($total * 1.13); ?></span>
            </div>

            <a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
var checkoutMap, checkoutMarker;

function toggleMapSelection(radio) {
    const mapForm = document.getElementById('map-selection-form');
    const customForm = document.getElementById('custom-address-form');
    
    if (radio.value === 'map') {
        mapForm.style.display = 'block';
        customForm.style.display = 'none';
        
        // Initialize map if not already done
        if (!checkoutMap) {
            initCheckoutMap();
        }
    } else {
        mapForm.style.display = 'none';
    }
}

function toggleCustomAddress(radio) {
    const customForm = document.getElementById('custom-address-form');
    const mapForm = document.getElementById('map-selection-form');
    
    if (radio.value === 'custom') {
        customForm.style.display = 'block';
        mapForm.style.display = 'none';
        // Make textarea required when custom is selected
        const textarea = customForm.querySelector('textarea');
        if (textarea) textarea.required = true;
    } else {
        customForm.style.display = 'none';
        // Remove required when custom is not selected
        const textarea = customForm.querySelector('textarea');
        if (textarea) textarea.required = false;
    }
}

function initCheckoutMap() {
    // Default to Kathmandu
    checkoutMap = L.map('checkoutMap').setView([27.7172, 85.3240], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: ' OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(checkoutMap);
    
    // Click on map to set marker
    checkoutMap.on('click', function(e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        
        // Reverse geocode to get place name
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var placeName = data.display_name || 'Selected Location';
                setCheckoutMarker(lat, lng, placeName);
            })
            .catch(function() {
                // Fallback if reverse geocoding fails
                setCheckoutMarker(lat, lng, 'Location at ' + lat.toFixed(4) + ', ' + lng.toFixed(4));
            });
    });
}

function setCheckoutMarker(lat, lng, name) {
    if (checkoutMarker) checkoutMap.removeLayer(checkoutMarker);
    checkoutMarker = L.marker([lat, lng]).addTo(checkoutMap);
    checkoutMap.setView([lat, lng], 16);
    
    // Update hidden inputs
    document.getElementById('mapLatitudeInput').value = lat.toFixed(6);
    document.getElementById('mapLongitudeInput').value = lng.toFixed(6);
    document.getElementById('mapPlaceNameInput').value = name;
    document.getElementById('mapAddressInput').value = name;
    
    // Show selected location info
    document.getElementById('selectedAddressText').textContent = name;
    document.getElementById('selectedLocationInfo').style.display = 'block';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkedRadio = document.querySelector('input[name="selected_address"]:checked');
    if (checkedRadio) {
        if (checkedRadio.value === 'map') {
            toggleMapSelection(checkedRadio);
        } else if (checkedRadio.value === 'custom') {
            toggleCustomAddress(checkedRadio);
        }
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
