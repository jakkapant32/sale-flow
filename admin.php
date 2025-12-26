<?php 
require_once 'config/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ตรวจสอบว่าเป็น admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}
require_once 'includes/header.php'; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-shield"></i> จัดการผู้ใช้</h1>
            <button class="btn-primary" onclick="openUserModal()">+ เพิ่มผู้ใช้ใหม่</button>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card" style="border-left-color: #2563eb;">
                <i class="fas fa-users stat-icon" style="color: #2563eb;"></i>
                <h3 id="totalUsers">0</h3>
                <p>ผู้ใช้ทั้งหมด</p>
            </div>
            <div class="stat-card" style="border-left-color: #10b981;">
                <i class="fas fa-user-shield stat-icon" style="color: #10b981;"></i>
                <h3 id="totalAdmins">0</h3>
                <p>ผู้ดูแลระบบ</p>
            </div>
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <i class="fas fa-user-check stat-icon" style="color: #f59e0b;"></i>
                <h3 id="activeUsers">0</h3>
                <p>ผู้ใช้ที่ใช้งาน</p>
            </div>
            <div class="stat-card" style="border-left-color: #ef4444;">
                <i class="fas fa-user-times stat-icon" style="color: #ef4444;"></i>
                <h3 id="inactiveUsers">0</h3>
                <p>ผู้ใช้ที่ถูกระงับ</p>
            </div>
        </div>
        
        <div class="card">
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="ค้นหาผู้ใช้..." style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                <select id="roleFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกบทบาท</option>
                    <option value="admin">ผู้ดูแลระบบ</option>
                    <option value="user">ผู้ใช้</option>
                </select>
                <select id="statusFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกสถานะ</option>
                    <option value="active">ใช้งาน</option>
                    <option value="inactive">ไม่ใช้งาน</option>
                </select>
            </div>
            
            <div id="usersTable" class="table-container">
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>
    
    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มผู้ใช้ใหม่</h2>
                <button class="close-btn" onclick="closeUserModal()">&times;</button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อผู้ใช้ *</label>
                        <input type="text" id="username" required>
                    </div>
                    <div class="form-group">
                        <label>อีเมล *</label>
                        <input type="email" id="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>ชื่อ-นามสกุล *</label>
                    <input type="text" id="fullName" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>บทบาท *</label>
                        <select id="role" required>
                            <option value="user">ผู้ใช้</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>สถานะ *</label>
                        <select id="status" required>
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label>รหัสผ่าน <span id="passwordRequired">*</span></label>
                    <input type="password" id="password" minlength="6">
                    <small style="color: #666;">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</small>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeUserModal()">ยกเลิก</button>
                    <button type="submit" class="btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api';
        let currentPage = 1;
        let currentUserId = null;
        
        function loadStatistics() {
            fetch(`${API_BASE}/users?limit=1000`)
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        const users = data.data;
                        const total = users.length;
                        const admins = users.filter(u => u.role === 'admin').length;
                        const active = users.filter(u => u.status === 'active').length;
                        const inactive = users.filter(u => u.status === 'inactive').length;
                        
                        document.getElementById('totalUsers').textContent = total;
                        document.getElementById('totalAdmins').textContent = admins;
                        document.getElementById('activeUsers').textContent = active;
                        document.getElementById('inactiveUsers').textContent = inactive;
                    }
                })
                .catch(err => console.error('Error loading statistics:', err));
        }
        
        function loadUsers(page = 1) {
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const statusFilter = document.getElementById('statusFilter');
            
            if (!searchInput || !roleFilter || !statusFilter) {
                console.error('Required elements not found');
                return;
            }
            
            const search = searchInput.value;
            const role = roleFilter.value;
            const status = statusFilter.value;
            
            let url = `${API_BASE}/users?page=${page}&limit=20`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&status=${status}`;
            if (role) url += `&role=${role}`;
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        displayUsers(data.data);
                        currentPage = data.pagination.page;
                    }
                })
                .catch(err => {
                    console.error('Error loading users:', err);
                    if (err.message.includes('403')) {
                        alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
                        window.location.href = 'dashboard.php';
                    }
                });
        }
        
        function displayUsers(users) {
            if (users.length === 0) {
                document.getElementById('usersTable').innerHTML = '<p class="text-center">ไม่พบข้อมูล</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>บทบาท</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${users.map(u => `
                            <tr>
                                <td>${u.username}</td>
                                <td>${u.email}</td>
                                <td>${u.full_name}</td>
                                <td><span class="badge badge-${u.role === 'admin' ? 'danger' : 'info'}">${u.role === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้'}</span></td>
                                <td><span class="badge badge-${u.status === 'active' ? 'success' : 'danger'}">${u.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'}</span></td>
                                <td>${new Date(u.created_at).toLocaleDateString('th-TH')}</td>
                                <td>
                                    <button class="btn-primary btn-small" onclick="editUser('${u.id}')">แก้ไข</button>
                                    <button class="btn-danger btn-small" onclick="deleteUser('${u.id}')">ลบ</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('usersTable').innerHTML = html;
        }
        
        function openUserModal(id = null) {
            document.getElementById('userModal').classList.add('show');
            document.getElementById('userId').value = id || '';
            document.getElementById('modalTitle').textContent = id ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่';
            currentUserId = id;
            
            if (id) {
                fetch(`${API_BASE}/users/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.data) {
                            const u = data.data;
                            document.getElementById('username').value = u.username || '';
                            document.getElementById('email').value = u.email || '';
                            document.getElementById('fullName').value = u.full_name || '';
                            document.getElementById('role').value = u.role || 'user';
                            document.getElementById('status').value = u.status || 'active';
                            document.getElementById('password').required = false;
                            document.getElementById('passwordRequired').style.display = 'none';
                        }
                    })
                    .catch(err => {
                        console.error('Error loading user:', err);
                        if (err.message.includes('403')) {
                            alert('คุณไม่มีสิทธิ์เข้าถึง');
                        }
                    });
            } else {
                document.getElementById('userForm').reset();
                document.getElementById('password').required = true;
                document.getElementById('passwordRequired').style.display = 'inline';
            }
        }
        
        function closeUserModal() {
            document.getElementById('userModal').classList.remove('show');
            document.getElementById('userForm').reset();
            currentUserId = null;
        }
        
        function editUser(id) {
            openUserModal(id);
        }
        
        function deleteUser(id) {
            if (!confirm('คุณแน่ใจว่าต้องการลบผู้ใช้นี้?')) return;
            
            fetch(`${API_BASE}/users/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    loadUsers();
                    loadStatistics();
                })
                .catch(err => {
                    console.error('Error deleting user:', err);
                    alert('เกิดข้อผิดพลาดในการลบผู้ใช้');
                });
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB'
            }).format(amount);
        }
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const statusFilter = document.getElementById('statusFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', () => loadUsers(1));
            }
            
            if (roleFilter) {
                roleFilter.addEventListener('change', () => loadUsers(1));
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', () => loadUsers(1));
            }
            
            // Setup user form
            const userForm = document.getElementById('userForm');
            if (userForm) {
                userForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const id = currentUserId;
                    const data = {
                        username: document.getElementById('username').value,
                        email: document.getElementById('email').value,
                        full_name: document.getElementById('fullName').value,
                        role: document.getElementById('role').value,
                        status: document.getElementById('status').value
                    };
                    
                    const password = document.getElementById('password').value;
                    if (password) {
                        data.password = password;
                    } else if (!id) {
                        alert('กรุณากรอกรหัสผ่าน');
                        return;
                    }
                    
                    const method = id ? 'PUT' : 'POST';
                    const url = id ? `${API_BASE}/users/${id}` : `${API_BASE}/users`;
                    
                    fetch(url, {
                        method: method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert('เกิดข้อผิดพลาด: ' + data.error);
                        } else {
                            closeUserModal();
                            loadUsers();
                            loadStatistics();
                        }
                    })
                    .catch(err => {
                        console.error('Error saving user:', err);
                        alert('เกิดข้อผิดพลาดในการบันทึก');
                    });
                });
            }
            
            // Load initial data
            loadStatistics();
            loadUsers();
        });
    </script>
</body>
</html>

