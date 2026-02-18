<?php require_once 'includes/auth_check.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>จัดการคำสั่งซื้อ</h1>
            <button class="btn-primary" onclick="openOrderModal()">+ เพิ่มคำสั่งซื้อใหม่</button>
        </div>
        
        <div class="card">
            <div class="search-filter-row" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="ค้นหาคำสั่งซื้อ..." style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                <select id="statusFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending">รอดำเนินการ</option>
                    <option value="processing">กำลังดำเนินการ</option>
                    <option value="completed">เสร็จแล้ว</option>
                    <option value="cancelled">ยกเลิก</option>
                </select>
            </div>
            
            <div id="ordersTable" class="table-container">
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>
    
    <!-- Order Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มคำสั่งซื้อใหม่</h2>
                <button class="close-btn" onclick="closeOrderModal()">&times;</button>
            </div>
            <form id="orderForm">
                <input type="hidden" id="orderId">
                <div class="form-row">
                    <div class="form-group">
                        <label>ลูกค้า *</label>
                        <select id="customerId" required>
                            <option value="">เลือกลูกค้า</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>วันที่สั่งซื้อ *</label>
                        <input type="date" id="orderDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>วันที่ส่งมอบ</label>
                        <input type="date" id="deliveryDate">
                    </div>
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select id="status">
                            <option value="pending">รอดำเนินการ</option>
                            <option value="processing">กำลังดำเนินการ</option>
                            <option value="completed">เสร็จแล้ว</option>
                        </select>
                    </div>
                </div>
                
                <h3 style="margin: 1.5rem 0 1rem 0;">รายการสินค้า</h3>
                <div id="orderItems">
                    <div class="order-item" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; margin-bottom: 1rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>สินค้า</label>
                            <select class="item-product" required>
                                <option value="">เลือกสินค้า</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>จำนวน</label>
                            <input type="number" class="item-quantity" min="1" value="1" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>ราคาต่อหน่วย</label>
                            <input type="number" class="item-price" step="0.01" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>ส่วนลด</label>
                            <input type="number" class="item-discount" step="0.01" value="0">
                        </div>
                        <button type="button" class="btn-danger btn-small" onclick="removeItem(this)">ลบ</button>
                    </div>
                </div>
                <button type="button" class="btn-secondary" onclick="addOrderItem()">+ เพิ่มสินค้า</button>
                
                <div class="form-row" style="margin-top: 2rem;">
                    <div class="form-group">
                        <label>ส่วนลดรวม</label>
                        <input type="number" id="discount" step="0.01" value="0" onchange="updateCalculations()">
                    </div>
                    <div class="form-group">
                        <label>อัตราภาษี (%)</label>
                        <input type="number" id="taxRate" step="0.01" min="0" max="100" value="7" onchange="calculateTax()">
                        <small style="color: #666;">% ของยอดรวมก่อนภาษี</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ภาษี</label>
                        <input type="number" id="tax" step="0.01" value="0" readonly style="background-color: #f5f5f5;">
                        <small style="color: #666;">คำนวณอัตโนมัติจากอัตราภาษี</small>
                    </div>
                    <div class="form-group">
                        <label>อัตราค่าคอมมิชชั่น (%)</label>
                        <input type="number" id="commissionRate" step="0.01" min="0" max="100" value="7" onchange="updateCalculations()">
                        <small style="color: #666;">% ของยอดรวม</small>
                    </div>
                </div>
                
                <!-- ส่วนแสดงผลการคำนวณ -->
                <div class="card" style="margin-top: 1.5rem; background: #f8f9fa; border: 1px solid #e0e0e0;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: #333;">สรุปการคำนวณ</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div><strong>ยอดรวมทั้งหมด:</strong></div>
                        <div id="displayTotalAmount" style="text-align: right; color: #2563eb; font-weight: 600;">฿0.00</div>
                        
                        <div><strong>ค่าคอมมิชชั่น (<span id="displayCommissionRate">7</span>%):</strong></div>
                        <div id="displayCommissionAmount" style="text-align: right; color: #ef4444;">-฿0.00</div>
                        
                        <div><strong>ภาษี:</strong></div>
                        <div id="displayTax" style="text-align: right; color: #ef4444;">-฿0.00</div>
                        
                        <div style="border-top: 2px solid #ddd; padding-top: 0.5rem; margin-top: 0.5rem;"><strong>รายได้สุทธิ:</strong></div>
                        <div id="displayNetIncome" style="text-align: right; font-size: 1.2rem; font-weight: bold; color: #10b981; border-top: 2px solid #ddd; padding-top: 0.5rem; margin-top: 0.5rem;">฿0.00</div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>หมายเหตุ</label>
                    <textarea id="notes" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeOrderModal()">ยกเลิก</button>
                    <button type="submit" class="btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api';
        let products = [];
        let customers = [];
        
        function loadCustomers() {
            fetch(`${API_BASE}/customers?limit=1000`)
                .then(res => res.json())
                .then(data => {
                    customers = data.data;
                    const select = document.getElementById('customerId');
                    select.innerHTML = '<option value="">เลือกลูกค้า</option>' +
                        customers.map(c => `<option value="${c.id}">${c.first_name} ${c.last_name}${c.company_name ? ' (' + c.company_name + ')' : ''}</option>`).join('');
                });
        }
        
        function loadProducts() {
            fetch(`${API_BASE}/products?limit=1000`)
                .then(res => res.json())
                .then(data => {
                    products = data.data;
                    updateProductSelects();
                });
        }
        
        function updateProductSelects() {
            document.querySelectorAll('.item-product').forEach(select => {
                const currentValue = select.value;
                select.innerHTML = '<option value="">เลือกสินค้า</option>' +
                    products.map(p => `<option value="${p.id}" data-price="${p.unit_price}">${p.product_name} (${formatCurrency(p.unit_price)})</option>`).join('');
                if (currentValue) select.value = currentValue;
            });
        }
        
        function loadOrders() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            
            let url = `${API_BASE}/orders?limit=50`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&status=${status}`;
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        displayOrders(data.data);
                    }
                })
                .catch(err => console.error('Error:', err));
        }
        
        function displayOrders(orders) {
            if (orders.length === 0) {
                document.getElementById('ordersTable').innerHTML = '<p class="text-center">ไม่พบข้อมูล</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>เลขที่คำสั่งซื้อ</th>
                            <th>ลูกค้า</th>
                            <th>วันที่สั่งซื้อ</th>
                            <th>ยอดรวม</th>
                            <th>ค่าคอมมิชชั่น</th>
                            <th>รายได้สุทธิ</th>
                            <th>สถานะ</th>
                            <th>สถานะการชำระ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orders.map(o => {
                            const commissionRate = o.commission_rate || 7;
                            const commissionAmount = o.commission_amount || 0;
                            const netIncome = o.net_income !== null && o.net_income !== undefined ? o.net_income : (o.total_amount - commissionAmount - (o.tax || 0));
                            return `
                            <tr>
                                <td>${o.order_number}</td>
                                <td>${o.customer_name || '-'}</td>
                                <td>${new Date(o.order_date).toLocaleDateString('th-TH')}</td>
                                <td>${formatCurrency(o.total_amount)}</td>
                                <td>
                                    <span style="color: #ef4444;">-${formatCurrency(commissionAmount)}</span>
                                    <small style="color: #666;">(${commissionRate}%)</small>
                                </td>
                                <td><strong style="color: #10b981;">${formatCurrency(netIncome)}</strong></td>
                                <td><span class="badge badge-info">${o.status}</span></td>
                                <td><span class="badge badge-${o.payment_status === 'paid' ? 'success' : 'warning'}">${o.payment_status === 'paid' ? 'ชำระแล้ว' : 'ยังไม่ชำระ'}</span></td>
                                <td>
                                    <button class="btn-primary btn-small" onclick="viewOrder('${o.id}')">ดู</button>
                                    <button class="btn-danger btn-small" onclick="deleteOrder('${o.id}')">ลบ</button>
                                </td>
                            </tr>
                        `;
                        }).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('ordersTable').innerHTML = html;
        }
        
        function addOrderItem() {
            const container = document.getElementById('orderItems');
            const newItem = document.createElement('div');
            newItem.className = 'order-item';
            newItem.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; margin-bottom: 1rem; align-items: end;';
            newItem.innerHTML = `
                <div class="form-group" style="margin-bottom: 0;">
                    <label>สินค้า</label>
                    <select class="item-product" required>
                        <option value="">เลือกสินค้า</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>จำนวน</label>
                    <input type="number" class="item-quantity" min="1" value="1" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>ราคาต่อหน่วย</label>
                    <input type="number" class="item-price" step="0.01" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>ส่วนลด</label>
                    <input type="number" class="item-discount" step="0.01" value="0">
                </div>
                <button type="button" class="btn-danger btn-small" onclick="removeItem(this)">ลบ</button>
            `;
            container.appendChild(newItem);
            updateProductSelects();
        }
        
        function removeItem(btn) {
            if (document.querySelectorAll('.order-item').length > 1) {
                btn.closest('.order-item').remove();
            }
        }
        
        function openOrderModal(id = null) {
            document.getElementById('orderModal').classList.add('show');
            document.getElementById('orderId').value = id || '';
            document.getElementById('modalTitle').textContent = id ? 'แก้ไขคำสั่งซื้อ' : 'เพิ่มคำสั่งซื้อใหม่';
            
            // Set default order date to today
            if (!id) {
                document.getElementById('orderDate').value = new Date().toISOString().split('T')[0];
                document.getElementById('commissionRate').value = 7;
                document.getElementById('taxRate').value = 7;
            }
            
            if (id) {
                fetch(`${API_BASE}/orders/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.data) {
                            const o = data.data;
                            document.getElementById('customerId').value = o.customer_id || '';
                            document.getElementById('orderDate').value = o.order_date || '';
                            document.getElementById('deliveryDate').value = o.delivery_date || '';
                            document.getElementById('status').value = o.status || 'pending';
                            document.getElementById('tax').value = o.tax || 0;
                            document.getElementById('discount').value = o.discount || 0;
                            document.getElementById('commissionRate').value = o.commission_rate || 7;
                            document.getElementById('taxRate').value = o.tax_rate || 7;
                            document.getElementById('notes').value = o.notes || '';
                            
                            // Load order items
                            if (o.items && o.items.length > 0) {
                                document.getElementById('orderItems').innerHTML = '';
                                o.items.forEach(item => {
                                    addOrderItem();
                                    const items = document.querySelectorAll('.order-item');
                                    const lastItem = items[items.length - 1];
                                    lastItem.querySelector('.item-product').value = item.product_id;
                                    lastItem.querySelector('.item-quantity').value = item.quantity;
                                    lastItem.querySelector('.item-price').value = item.unit_price;
                                    lastItem.querySelector('.item-discount').value = item.discount || 0;
                                });
                            }
                            updateProductSelects();
                            // คำนวณหลังจากโหลดข้อมูลเสร็จ
                            setTimeout(() => {
                                calculateTax();
                                updateCalculations();
                            }, 100);
                        }
                    });
            } else {
                document.getElementById('orderForm').reset();
                document.getElementById('orderItems').innerHTML = '';
                document.getElementById('commissionRate').value = 7;
                document.getElementById('taxRate').value = 7;
                addOrderItem();
                updateCalculations();
            }
        }
        
        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('show');
        }
        
        function viewOrder(id) {
            openOrderModal(id);
        }
        
        function deleteOrder(id) {
            if (!confirm('คุณแน่ใจว่าต้องการลบคำสั่งซื้อนี้?')) return;
            
            fetch(`${API_BASE}/orders/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    loadOrders();
                });
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB'
            }).format(amount);
        }
        
        // ฟังก์ชันคำนวณ subtotal
        function calculateSubtotal() {
            let subtotal = 0;
            document.querySelectorAll('.order-item').forEach(itemEl => {
                const quantity = parseFloat(itemEl.querySelector('.item-quantity').value) || 0;
                const unitPrice = parseFloat(itemEl.querySelector('.item-price').value) || 0;
                const discount = parseFloat(itemEl.querySelector('.item-discount').value) || 0;
                subtotal += (quantity * unitPrice) - discount;
            });
            return subtotal;
        }
        
        // ฟังก์ชันคำนวณภาษี
        function calculateTax() {
            const taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
            const subtotal = calculateSubtotal();
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const tax = (subtotal - discount) * (taxRate / 100);
            document.getElementById('tax').value = tax.toFixed(2);
            updateCalculations();
        }
        
        // ฟังก์ชันคำนวณและแสดงผลการคำนวณ
        function updateCalculations() {
            const subtotal = calculateSubtotal();
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const tax = parseFloat(document.getElementById('tax').value) || 0;
            const total = subtotal + tax - discount;
            
            const commissionRate = parseFloat(document.getElementById('commissionRate').value) || 0;
            const commissionAmount = total * (commissionRate / 100);
            const netIncome = total - commissionAmount - tax;
            
            // แสดงผลการคำนวณ
            document.getElementById('displayTotalAmount').textContent = formatCurrency(total);
            document.getElementById('displayCommissionRate').textContent = commissionRate.toFixed(2);
            document.getElementById('displayCommissionAmount').textContent = formatCurrency(-commissionAmount);
            document.getElementById('displayTax').textContent = formatCurrency(-tax);
            document.getElementById('displayNetIncome').textContent = formatCurrency(netIncome);
        }
        
        // Auto-fill price when product is selected
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('item-product')) {
                const option = e.target.options[e.target.selectedIndex];
                if (option.dataset.price) {
                    e.target.closest('.order-item').querySelector('.item-price').value = option.dataset.price;
                    updateCalculations();
                }
            }
        });
        
        // เมื่อมีการเปลี่ยนแปลงรายการสินค้า
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('item-quantity') || 
                e.target.classList.contains('item-price') || 
                e.target.classList.contains('item-discount')) {
                calculateTax();
                updateCalculations();
            }
        });
        
        document.getElementById('orderForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('orderId').value;
            
            const items = [];
            document.querySelectorAll('.order-item').forEach(itemEl => {
                const productId = itemEl.querySelector('.item-product').value;
                const quantity = parseInt(itemEl.querySelector('.item-quantity').value);
                const unitPrice = parseFloat(itemEl.querySelector('.item-price').value);
                const discount = parseFloat(itemEl.querySelector('.item-discount').value) || 0;
                
                if (productId && quantity && unitPrice) {
                    items.push({ product_id: productId, quantity, unit_price: unitPrice, discount });
                }
            });
            
            if (items.length === 0) {
                alert('กรุณาเพิ่มสินค้าอย่างน้อย 1 รายการ');
                return;
            }
            
            const data = {
                customer_id: document.getElementById('customerId').value,
                order_date: document.getElementById('orderDate').value,
                delivery_date: document.getElementById('deliveryDate').value || null,
                status: document.getElementById('status').value,
                tax: parseFloat(document.getElementById('tax').value) || 0,
                discount: parseFloat(document.getElementById('discount').value) || 0,
                commission_rate: parseFloat(document.getElementById('commissionRate').value) || 7,
                tax_rate: parseFloat(document.getElementById('taxRate').value) || 7,
                notes: document.getElementById('notes').value,
                items: items
            };
            
            const method = id ? 'PUT' : 'POST';
            const url = id ? `${API_BASE}/orders/${id}` : `${API_BASE}/orders`;
            
            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                closeOrderModal();
                loadOrders();
            });
        });
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', loadOrders);
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', loadOrders);
            }
            
            loadCustomers();
            loadProducts();
            loadOrders();
        });
    </script>
</body>
</html>

