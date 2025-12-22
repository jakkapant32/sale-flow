<?php require_once 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการกิจกรรม - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>จัดการกิจกรรม</h1>
            <button class="btn-primary" onclick="openActivityModal()">+ เพิ่มกิจกรรมใหม่</button>
        </div>
        
        <div class="card">
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="ค้นหากิจกรรม..." style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                <select id="statusFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending">ค้าง</option>
                    <option value="completed">เสร็จแล้ว</option>
                </select>
                <select id="typeFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกประเภท</option>
                    <option value="call">โทรศัพท์</option>
                    <option value="meeting">ประชุม</option>
                    <option value="email">อีเมล</option>
                    <option value="task">งาน</option>
                </select>
            </div>
            
            <div id="activitiesTable" class="table-container">
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>
    
    <!-- Activity Modal -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มกิจกรรมใหม่</h2>
                <button class="close-btn" onclick="closeActivityModal()">&times;</button>
            </div>
            <form id="activityForm">
                <input type="hidden" id="activityId">
                <div class="form-row">
                    <div class="form-group">
                        <label>ประเภท *</label>
                        <select id="activityType" required>
                            <option value="call">โทรศัพท์</option>
                            <option value="meeting">ประชุม</option>
                            <option value="email">อีเมล</option>
                            <option value="task">งาน</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ลูกค้า</label>
                        <select id="customerId">
                            <option value="">เลือกลูกค้า</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>หัวข้อ *</label>
                    <input type="text" id="subject" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>วันที่ครบกำหนด</label>
                        <input type="date" id="dueDate">
                    </div>
                    <div class="form-group">
                        <label>ความสำคัญ</label>
                        <select id="priority">
                            <option value="low">ต่ำ</option>
                            <option value="medium" selected>กลาง</option>
                            <option value="high">สูง</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select id="status">
                            <option value="pending">ค้าง</option>
                            <option value="completed">เสร็จแล้ว</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ระยะเวลา (นาที)</label>
                        <input type="number" id="durationMinutes" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea id="description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>สถานที่</label>
                    <input type="text" id="location">
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeActivityModal()">ยกเลิก</button>
                    <button type="submit" class="btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api';
        
        function loadCustomers() {
            fetch(`${API_BASE}/customers?limit=1000`)
                .then(res => res.json())
                .then(data => {
                    const select = document.getElementById('customerId');
                    select.innerHTML = '<option value="">เลือกลูกค้า</option>' +
                        data.data.map(c => `<option value="${c.id}">${c.first_name} ${c.last_name}${c.company_name ? ' (' + c.company_name + ')' : ''}</option>`).join('');
                });
        }
        
        function loadActivities() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const type = document.getElementById('typeFilter').value;
            
            let url = `${API_BASE}/activities?limit=50`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&status=${status}`;
            if (type) url += `&activity_type=${type}`;
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        displayActivities(data.data);
                    }
                })
                .catch(err => console.error('Error:', err));
        }
        
        function displayActivities(activities) {
            if (activities.length === 0) {
                document.getElementById('activitiesTable').innerHTML = '<p class="text-center">ไม่พบข้อมูล</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>ประเภท</th>
                            <th>หัวข้อ</th>
                            <th>ลูกค้า</th>
                            <th>วันที่ครบกำหนด</th>
                            <th>ความสำคัญ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${activities.map(a => {
                            const typeNames = { call: 'โทรศัพท์', meeting: 'ประชุม', email: 'อีเมล', task: 'งาน' };
                            const priorityNames = { low: 'ต่ำ', medium: 'กลาง', high: 'สูง' };
                            const priorityColors = { low: 'info', medium: 'warning', high: 'danger' };
                            const dueDate = a.due_date ? new Date(a.due_date).toLocaleDateString('th-TH') : '-';
                            const isOverdue = a.due_date && new Date(a.due_date) < new Date() && a.status === 'pending';
                            
                            return `
                                <tr ${isOverdue ? 'style="background: #fee;"' : ''}>
                                    <td>${typeNames[a.activity_type] || a.activity_type}</td>
                                    <td>${a.subject}</td>
                                    <td>${a.customer_name || '-'}</td>
                                    <td ${isOverdue ? 'style="color: red; font-weight: bold;"' : ''}>${dueDate}</td>
                                    <td><span class="badge badge-${priorityColors[a.priority] || 'info'}">${priorityNames[a.priority] || a.priority}</span></td>
                                    <td><span class="badge badge-${a.status === 'completed' ? 'success' : 'warning'}">${a.status === 'completed' ? 'เสร็จแล้ว' : 'ค้าง'}</span></td>
                                    <td>
                                        <button class="btn-primary btn-small" onclick="editActivity('${a.id}')">แก้ไข</button>
                                        <button class="btn-danger btn-small" onclick="deleteActivity('${a.id}')">ลบ</button>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('activitiesTable').innerHTML = html;
        }
        
        function openActivityModal(id = null) {
            document.getElementById('activityModal').classList.add('show');
            document.getElementById('activityId').value = id || '';
            document.getElementById('modalTitle').textContent = id ? 'แก้ไขกิจกรรม' : 'เพิ่มกิจกรรมใหม่';
            
            if (id) {
                fetch(`${API_BASE}/activities/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.data) {
                            const a = data.data;
                            document.getElementById('activityType').value = a.activity_type || 'call';
                            document.getElementById('customerId').value = a.customer_id || '';
                            document.getElementById('subject').value = a.subject || '';
                            document.getElementById('dueDate').value = a.due_date || '';
                            document.getElementById('priority').value = a.priority || 'medium';
                            document.getElementById('status').value = a.status || 'pending';
                            document.getElementById('durationMinutes').value = a.duration_minutes || '';
                            document.getElementById('description').value = a.description || '';
                            document.getElementById('location').value = a.location || '';
                        }
                    });
            } else {
                document.getElementById('activityForm').reset();
            }
        }
        
        function closeActivityModal() {
            document.getElementById('activityModal').classList.remove('show');
        }
        
        function editActivity(id) {
            openActivityModal(id);
        }
        
        function deleteActivity(id) {
            if (!confirm('คุณแน่ใจว่าต้องการลบกิจกรรมนี้?')) return;
            
            fetch(`${API_BASE}/activities/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    loadActivities();
                });
        }
        
        document.getElementById('activityForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('activityId').value;
            const data = {
                activity_type: document.getElementById('activityType').value,
                customer_id: document.getElementById('customerId').value || null,
                subject: document.getElementById('subject').value,
                due_date: document.getElementById('dueDate').value || null,
                priority: document.getElementById('priority').value,
                status: document.getElementById('status').value,
                duration_minutes: document.getElementById('durationMinutes').value || null,
                description: document.getElementById('description').value,
                location: document.getElementById('location').value
            };
            
            const method = id ? 'PUT' : 'POST';
            const url = id ? `${API_BASE}/activities/${id}` : `${API_BASE}/activities`;
            
            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                closeActivityModal();
                loadActivities();
            });
        });
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', loadActivities);
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', loadActivities);
            }
            
            if (typeFilter) {
                typeFilter.addEventListener('change', loadActivities);
            }
            
            loadCustomers();
            loadActivities();
        });
    </script>
</body>
</html>

