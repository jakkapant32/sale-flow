<?php require_once 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>จัดการสินค้า</h1>
            <button class="btn-primary" onclick="openProductModal()">+ เพิ่มสินค้าใหม่</button>
        </div>
        
        <div class="card">
            <div class="search-filter-row" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="ค้นหาสินค้า..." style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                <select id="statusFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกสถานะ</option>
                    <option value="active">ใช้งาน</option>
                    <option value="inactive">ไม่ใช้งาน</option>
                </select>
            </div>
            
            <div id="productsTable" class="table-container">
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>
    
    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มสินค้าใหม่</h2>
                <button class="close-btn" onclick="closeProductModal()">&times;</button>
            </div>
            <form id="productForm">
                <input type="hidden" id="productId">
                <div class="form-row">
                    <div class="form-group">
                        <label>รหัสสินค้า *</label>
                        <input type="text" id="productCode" required>
                    </div>
                    <div class="form-group">
                        <label>ชื่อสินค้า *</label>
                        <input type="text" id="productName" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <input type="text" id="category">
                    </div>
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select id="status">
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ราคาขาย *</label>
                        <input type="number" id="unitPrice" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>ราคาทุน</label>
                        <input type="number" id="costPrice" step="0.01">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>จำนวนคงเหลือ</label>
                        <input type="number" id="stockQuantity" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea id="description" rows="4"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeProductModal()">ยกเลิก</button>
                    <button type="submit" class="btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api';
        
        function loadProducts() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            
            let url = `${API_BASE}/products?limit=100`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&status=${status}`;
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        displayProducts(data.data);
                    }
                })
                .catch(err => console.error('Error:', err));
        }
        
        function displayProducts(products) {
            if (products.length === 0) {
                document.getElementById('productsTable').innerHTML = '<p class="text-center">ไม่พบข้อมูล</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th>ราคาขาย</th>
                            <th>ราคาทุน</th>
                            <th>คงเหลือ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${products.map(p => `
                            <tr>
                                <td>${p.product_code}</td>
                                <td>${p.product_name}</td>
                                <td>${p.category || '-'}</td>
                                <td>${formatCurrency(p.unit_price)}</td>
                                <td>${p.cost_price ? formatCurrency(p.cost_price) : '-'}</td>
                                <td>${p.stock_quantity || 0}</td>
                                <td><span class="badge badge-${p.status === 'active' ? 'success' : 'danger'}">${p.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'}</span></td>
                                <td>
                                    <button class="btn-primary btn-small" onclick="editProduct('${p.id}')">แก้ไข</button>
                                    <button class="btn-danger btn-small" onclick="deleteProduct('${p.id}')">ลบ</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('productsTable').innerHTML = html;
        }
        
        function openProductModal(id = null) {
            document.getElementById('productModal').classList.add('show');
            document.getElementById('productId').value = id || '';
            document.getElementById('modalTitle').textContent = id ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่';
            
            if (id) {
                fetch(`${API_BASE}/products/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.data) {
                            const p = data.data;
                            document.getElementById('productCode').value = p.product_code || '';
                            document.getElementById('productName').value = p.product_name || '';
                            document.getElementById('category').value = p.category || '';
                            document.getElementById('unitPrice').value = p.unit_price || '';
                            document.getElementById('costPrice').value = p.cost_price || '';
                            document.getElementById('stockQuantity').value = p.stock_quantity || 0;
                            document.getElementById('status').value = p.status || 'active';
                            document.getElementById('description').value = p.description || '';
                        }
                    });
            } else {
                document.getElementById('productForm').reset();
            }
        }
        
        function closeProductModal() {
            document.getElementById('productModal').classList.remove('show');
        }
        
        function editProduct(id) {
            openProductModal(id);
        }
        
        function deleteProduct(id) {
            if (!confirm('คุณแน่ใจว่าต้องการลบสินค้านี้?')) return;
            
            fetch(`${API_BASE}/products/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    loadProducts();
                });
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB'
            }).format(amount);
        }
        
        document.getElementById('productForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('productId').value;
            const data = {
                product_code: document.getElementById('productCode').value,
                product_name: document.getElementById('productName').value,
                category: document.getElementById('category').value,
                unit_price: parseFloat(document.getElementById('unitPrice').value),
                cost_price: document.getElementById('costPrice').value ? parseFloat(document.getElementById('costPrice').value) : null,
                stock_quantity: parseInt(document.getElementById('stockQuantity').value) || 0,
                status: document.getElementById('status').value,
                description: document.getElementById('description').value
            };
            
            const method = id ? 'PUT' : 'POST';
            const url = id ? `${API_BASE}/products/${id}` : `${API_BASE}/products`;
            
            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                closeProductModal();
                loadProducts();
            });
        });
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', loadProducts);
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', loadProducts);
            }
            
            loadProducts();
        });
    </script>
</body>
</html>

