// Order Details Modal
function showOrderDetails(orderId) {
    console.log('Loading order details for ID:', orderId);
    const url = `order_details_simple.php?order_id=${orderId}`;
    console.log('Fetching from:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            if (data.error) {
                alert('Error: ' + data.error);
                return;
            }
            if (!data.order) {
                alert('Invalid response format');
                return;
            }
            renderOrderDetails(data);
            document.getElementById('orderDetailsModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            alert('Failed to load order details: ' + error.message);
        });
}

function closeOrderDetails() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

function renderOrderDetails(data) {
    const order = data.order;
    const items = data.items || [];
    const address = data.address;
    
    // Order info
    document.getElementById('detailOrderId').textContent = `#${order.order_id}`;
    document.getElementById('detailOrderDate').textContent = new Date(order.created_at).toLocaleString();
    document.getElementById('detailOrderStatus').textContent = data.status_label;
    document.getElementById('detailOrderStatus').className = `badge ${order.status}`;
    document.getElementById('detailOrderTotal').textContent = formatPrice(order.total_amount);
    document.getElementById('detailPaymentMethod').textContent = order.payment_method ? order.payment_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown';
    
    // User info
    document.getElementById('detailUserName').textContent = order.full_name || order.username;
    document.getElementById('detailUserEmail').textContent = order.email || 'N/A';
    document.getElementById('detailUserPhone').textContent = order.user_phone || 'N/A';
    
    // Address info
    const addressContainer = document.getElementById('detailShippingAddress');
    if (address) {
        addressContainer.innerHTML = `
            <div class="order-info-item">
                <span class="order-info-label">Full Name</span>
                <span class="order-info-value">${address.full_name}</span>
            </div>
            <div class="order-info-item">
                <span class="order-info-label">Address Line 1</span>
                <span class="order-info-value">${address.address_line1}</span>
            </div>
            ${address.address_line2 ? `
            <div class="order-info-item">
                <span class="order-info-label">Address Line 2</span>
                <span class="order-info-value">${address.address_line2}</span>
            </div>
            ` : ''}
            <div class="order-info-item">
                <span class="order-info-label">City</span>
                <span class="order-info-value">${address.city}</span>
            </div>
            <div class="order-info-item">
                <span class="order-info-label">State</span>
                <span class="order-info-value">${address.state || 'N/A'}</span>
            </div>
            <div class="order-info-item">
                <span class="order-info-label">Postal Code</span>
                <span class="order-info-value">${address.postal_code}</span>
            </div>
            <div class="order-info-item">
                <span class="order-info-label">Country</span>
                <span class="order-info-value">${address.country}</span>
            </div>
            ${address.phone ? `
            <div class="order-info-item">
                <span class="order-info-label">Phone</span>
                <span class="order-info-value">${address.phone}</span>
            </div>
            ` : ''}
        `;
    } else if (order.shipping_address) {
        addressContainer.innerHTML = `
            <div class="order-info-item" style="grid-column: 1 / -1;">
                <span class="order-info-label">Shipping Address</span>
                <span class="order-info-value" style="white-space: pre-line;">${order.shipping_address}</span>
            </div>
        `;
    } else {
        addressContainer.innerHTML = '<div class="order-info-item"><span class="order-info-value">No address information</span></div>';
    }
    
    // Order items
    const itemsContainer = document.getElementById('detailOrderItems');
    if (items.length === 0) {
        itemsContainer.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 1rem;">No items found</td></tr>';
        document.getElementById('detailGrandTotal').textContent = formatPrice(0);
    } else {
        let grandTotal = 0;
        itemsContainer.innerHTML = items.map(item => {
            const coverPath = item.cover_image ? `<?php echo SITE_URL; ?>/` + item.cover_image : '<?php echo SITE_URL; ?>/assets/images/default-book.png';
            const itemTotal = item.price_at_time * item.quantity;
            grandTotal += itemTotal;
            return `
                <tr>
                    <td>
                        <div class="order-item-book">
                            <img src="${coverPath}" alt="${item.title}" class="order-item-cover" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-book.png'">
                            <div class="order-item-details">
                                <div class="order-item-title">${item.title}</div>
                                <div class="order-item-meta">by ${item.author || 'Unknown'}</div>
                                ${item.isbn ? `<div class="order-item-meta">ISBN: ${item.isbn}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="order-item-quantity">${item.quantity}</td>
                    <td class="order-item-price">${formatPrice(item.price_at_time)}</td>
                    <td class="order-item-price">${formatPrice(itemTotal)}</td>
                </tr>
            `;
        }).join('');
        document.getElementById('detailGrandTotal').textContent = formatPrice(grandTotal);
    }
}

function formatPrice(amount) {
    const num = parseFloat(amount) || 0;
    return 'Rs. ' + num.toFixed(2);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('orderDetailsModal');
    if (event.target === modal) {
        closeOrderDetails();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeOrderDetails();
    }
});
