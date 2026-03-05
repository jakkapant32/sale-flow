<?php require_once 'includes/auth_check.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการลูกค้า - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>จัดการลูกค้า</h1>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn-primary" onclick="openCustomerModal()">+ เพิ่มลูกค้าใหม่</button>
                <button class="btn-secondary" onclick="document.getElementById('importCustomerModal').classList.add('show')">นำเข้าจากไฟล์ (CSV/Excel)</button>
            </div>
        </div>
        
        <div class="card">
            <div class="search-filter-row" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="ค้นหาลูกค้า..." style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                <select id="statusFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกสถานะ</option>
                    <option value="active">ใช้งาน</option>
                    <option value="inactive">ไม่ใช้งาน</option>
                </select>
            </div>
            
            <div id="customersTable" class="table-container">
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>
    
    <!-- Customer Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มลูกค้าใหม่</h2>
                <button class="close-btn" onclick="closeCustomerModal()">&times;</button>
            </div>
            <form id="customerForm">
                <input type="hidden" id="customerId">
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อ *</label>
                        <input type="text" id="firstName" required>
                    </div>
                    <div class="form-group">
                        <label>นามสกุล *</label>
                        <input type="text" id="lastName" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>ชื่อบริษัท</label>
                    <input type="text" id="companyName">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>อีเมล</label>
                        <input type="email" id="email">
                    </div>
                    <div class="form-group">
                        <label>โทรศัพท์</label>
                        <input type="text" id="phone">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>มือถือ</label>
                        <input type="text" id="mobile">
                    </div>
                    <div class="form-group">
                        <label>ประเภทลูกค้า</label>
                        <select id="customerType">
                            <option value="individual">บุคคล</option>
                            <option value="company">บริษัท</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>จังหวัด</label>
                        <input type="text" id="province">
                    </div>
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select id="status">
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>ที่อยู่</label>
                    <textarea id="address" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>หมายเหตุ</label>
                    <textarea id="notes" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeCustomerModal()">ยกเลิก</button>
                    <button type="submit" class="btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Import Modal -->
    <div id="importCustomerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>นำเข้าลูกค้าจากไฟล์</h2>
                <button class="close-btn" onclick="document.getElementById('importCustomerModal').classList.remove('show')">&times;</button>
            </div>
            <p style="margin-bottom: 1rem; color: var(--text-secondary);">รองรับ CSV, XLSX, XLS — แถวแรกต้องเป็นหัวคอลัมน์ (เช่น *Customer Name, *Phone, *Email, *Address)</p>
            <form id="importCustomerForm">
                <div class="form-group">
                    <label>เลือกไฟล์</label>
                    <input type="file" id="importCustomerFile" accept=".csv,.xlsx,.xls" required>
                </div>
                <div id="importCustomerResult" style="display: none; margin: 1rem 0;"></div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('importCustomerModal').classList.remove('show')">ปิด</button>
                    <button type="submit" class="btn-primary" id="importCustomerBtn">นำเข้า</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api';
        let currentPage = 1;
        
        function loadCustomers(page = 1) {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            
            if (!searchInput || !statusFilter) {
                console.error('Required elements not found in loadCustomers');
                return;
            }
            
            const search = searchInput.value;
            const status = statusFilter.value;
            
            let url = `${API_BASE}/customers?page=${page}&limit=20`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&status=${status}`;
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        displayCustomers(data.data);
                        currentPage = data.pagination.page;
                    }
                })
                .catch(err => console.error('Error loading customers:', err));
        }
        
        function displayCustomers(customers) {
            if (customers.length === 0) {
                document.getElementById('customersTable').innerHTML = '<p class="text-center">ไม่พบข้อมูล</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>บริษัท</th>
                            <th>อีเมล</th>
                            <th>โทรศัพท์</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${customers.map(c => `
                            <tr>
                                <td>${c.customer_code || '-'}</td>
                                <td>${c.first_name} ${c.last_name}</td>
                                <td>${c.company_name || '-'}</td>
                                <td>${c.email || '-'}</td>
                                <td>${c.phone || c.mobile || '-'}</td>
                                <td><span class="badge badge-${c.status === 'active' ? 'success' : 'danger'}">${c.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'}</span></td>
                                <td>
                                    <button class="btn-primary btn-small" onclick="editCustomer('${c.id}')">แก้ไข</button>
                                    <button class="btn-danger btn-small" onclick="deleteCustomer('${c.id}')">ลบ</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('customersTable').innerHTML = html;
        }
        
        function openCustomerModal(id = null) {
            document.getElementById('customerModal').classList.add('show');
            document.getElementById('customerId').value = id || '';
            document.getElementById('modalTitle').textContent = id ? 'แก้ไขลูกค้า' : 'เพิ่มลูกค้าใหม่';
            
            if (id) {
                fetch(`${API_BASE}/customers/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.data) {
                            const c = data.data;
                            document.getElementById('firstName').value = c.first_name || '';
                            document.getElementById('lastName').value = c.last_name || '';
                            document.getElementById('companyName').value = c.company_name || '';
                            document.getElementById('email').value = c.email || '';
                            document.getElementById('phone').value = c.phone || '';
                            document.getElementById('mobile').value = c.mobile || '';
                            document.getElementById('customerType').value = c.customer_type || 'individual';
                            document.getElementById('province').value = c.province || '';
                            document.getElementById('status').value = c.status || 'active';
                            document.getElementById('address').value = c.address || '';
                            document.getElementById('notes').value = c.notes || '';
                        }
                    });
            } else {
                document.getElementById('customerForm').reset();
            }
        }
        
        function closeCustomerModal() {
            document.getElementById('customerModal').classList.remove('show');
        }
        
        function editCustomer(id) {
            openCustomerModal(id);
        }
        
        function deleteCustomer(id) {
            if (!confirm('คุณแน่ใจว่าต้องการลบลูกค้านี้?')) return;
            
            fetch(`${API_BASE}/customers/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    loadCustomers();
                });
        }
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Setup search and filter
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    loadCustomers(1);
                });
            } else {
                console.error('Search input not found!');
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    loadCustomers(1);
                });
            }
            
            // Setup customer form
            const customerForm = document.getElementById('customerForm');
            if (customerForm) {
                customerForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const id = document.getElementById('customerId').value;
                    const data = {
                        first_name: document.getElementById('firstName').value,
                        last_name: document.getElementById('lastName').value,
                        company_name: document.getElementById('companyName').value,
                        email: document.getElementById('email').value,
                        phone: document.getElementById('phone').value,
                        mobile: document.getElementById('mobile').value,
                        customer_type: document.getElementById('customerType').value,
                        province: document.getElementById('province').value,
                        status: document.getElementById('status').value,
                        address: document.getElementById('address').value,
                        notes: document.getElementById('notes').value
                    };
                    
                    const method = id ? 'PUT' : 'POST';
                    const url = id ? `${API_BASE}/customers/${id}` : `${API_BASE}/customers`;
                    
                    fetch(url, {
                        method: method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(data => {
                        closeCustomerModal();
                        loadCustomers();
                    });
                });
            }
            
            // Import form
            document.getElementById('importCustomerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                var fileInput = document.getElementById('importCustomerFile');
                var resultDiv = document.getElementById('importCustomerResult');
                var btn = document.getElementById('importCustomerBtn');
                if (!fileInput.files.length) return;
                var fd = new FormData();
                fd.append('type', 'customers');
                fd.append('file', fileInput.files[0]);
                btn.disabled = true;
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = 'กำลังนำเข้า...';
                resultDiv.className = 'alert alert-info';
                fetch(API_BASE + '/import', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.error) {
                            resultDiv.className = 'alert alert-error';
                            resultDiv.innerHTML = data.error;
                        } else {
                            resultDiv.className = 'alert alert-success';
                            resultDiv.innerHTML = 'นำเข้าสำเร็จ ' + data.imported + ' รายการ, ข้าม ' + data.skipped + ' รายการ.' +
                                (data.errors && data.errors.length ? '<br><small>' + data.errors.slice(0, 5).join('<br>') + (data.errors.length > 5 ? ' ...' : '') + '</small>' : '');
                            loadCustomers();
                            fileInput.value = '';
                        }
                        btn.disabled = false;
                    })
                    .catch(function(err) {
                        resultDiv.className = 'alert alert-error';
                        resultDiv.innerHTML = 'เกิดข้อผิดพลาด: ' + err.message;
                        btn.disabled = false;
                    });
            });
            
            // Load initial data
            loadCustomers();
        });
    </script>
</body>
</html>

