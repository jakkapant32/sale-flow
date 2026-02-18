<?php require_once 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการดีล - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>จัดการดีล</h1>
            <button class="btn-primary" onclick="openDealModal()">+ เพิ่มดีลใหม่</button>
        </div>
        
        <div class="card">
            <div class="search-filter-row" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="ค้นหาดีล..." style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                <select id="stageFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">ทุกขั้นตอน</option>
                    <option value="prospecting">Prospecting</option>
                    <option value="qualification">Qualification</option>
                    <option value="proposal">Proposal</option>
                    <option value="negotiation">Negotiation</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            
            <div id="dealsTable" class="table-container">
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>
    
    <!-- Deal Modal -->
    <div id="dealModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มดีลใหม่</h2>
                <button class="close-btn" onclick="closeDealModal()">&times;</button>
            </div>
            <form id="dealForm">
                <input type="hidden" id="dealId">
                <div class="form-group">
                    <label>ชื่อดีล *</label>
                    <input type="text" id="dealName" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ลูกค้า *</label>
                        <select id="customerId" required>
                            <option value="">เลือกลูกค้า</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>มูลค่า *</label>
                        <input type="number" id="amount" step="0.01" required onchange="updateDealCalculations()">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>อัตราค่าคอมมิชชั่น (%)</label>
                        <input type="number" id="commissionRate" step="0.01" min="0" max="100" value="7" onchange="updateDealCalculations()">
                        <small style="color: #666;">% ของมูลค่า</small>
                    </div>
                    <div class="form-group">
                        <label>โอกาสสำเร็จ (%)</label>
                        <input type="number" id="probability" min="0" max="100" value="0">
                    </div>
                </div>
                
                <!-- ส่วนแสดงผลการคำนวณ -->
                <div class="card" style="margin-top: 1.5rem; background: #f8f9fa; border: 1px solid #e0e0e0;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: #333;">สรุปการคำนวณ</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div><strong>มูลค่าดีล:</strong></div>
                        <div id="displayDealAmount" style="text-align: right; color: #2563eb; font-weight: 600;">฿0.00</div>
                        
                        <div><strong>ค่าคอมมิชชั่น (<span id="displayDealCommissionRate">7</span>%):</strong></div>
                        <div id="displayDealCommissionAmount" style="text-align: right; color: #ef4444;">-฿0.00</div>
                        
                        <div style="border-top: 2px solid #ddd; padding-top: 0.5rem; margin-top: 0.5rem;"><strong>รายได้สุทธิ:</strong></div>
                        <div id="displayDealNetIncome" style="text-align: right; font-size: 1.2rem; font-weight: bold; color: #10b981; border-top: 2px solid #ddd; padding-top: 0.5rem; margin-top: 0.5rem;">฿0.00</div>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label>ขั้นตอน</label>
                        <select id="stage">
                            <option value="prospecting">Prospecting</option>
                            <option value="qualification">Qualification</option>
                            <option value="proposal">Proposal</option>
                            <option value="negotiation">Negotiation</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>วันที่คาดว่าจะปิด</label>
                        <input type="date" id="expectedCloseDate">
                    </div>
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select id="status">
                            <option value="open">เปิด</option>
                            <option value="closed">ปิด</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea id="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>หมายเหตุ</label>
                    <textarea id="notes" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeDealModal()">ยกเลิก</button>
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
        
        function loadDeals() {
            const search = document.getElementById('searchInput').value;
            const stage = document.getElementById('stageFilter').value;
            
            let url = `${API_BASE}/deals?limit=50`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (stage) url += `&stage=${stage}`;
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        displayDeals(data.data);
                    }
                })
                .catch(err => console.error('Error:', err));
        }
        
        function displayDeals(deals) {
            if (deals.length === 0) {
                document.getElementById('dealsTable').innerHTML = '<p class="text-center">ไม่พบข้อมูล</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อดีล</th>
                            <th>ลูกค้า</th>
                            <th>มูลค่า</th>
                            <th>ค่าคอมมิชชั่น</th>
                            <th>รายได้สุทธิ</th>
                            <th>ขั้นตอน</th>
                            <th>โอกาสสำเร็จ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${deals.map(d => {
                            const commissionRate = d.commission_rate || 7;
                            const commissionAmount = d.commission_amount || 0;
                            const netIncome = d.net_income !== null && d.net_income !== undefined ? d.net_income : (d.amount - commissionAmount);
                            return `
                            <tr>
                                <td>${d.deal_code || '-'}</td>
                                <td>${d.deal_name}</td>
                                <td>${d.customer_name || '-'}</td>
                                <td>${formatCurrency(d.amount)}</td>
                                <td>
                                    <span style="color: #ef4444;">-${formatCurrency(commissionAmount)}</span>
                                    <small style="color: #666;">(${commissionRate}%)</small>
                                </td>
                                <td><strong style="color: #10b981;">${formatCurrency(netIncome)}</strong></td>
                                <td><span class="badge badge-info">${d.stage}</span></td>
                                <td>${d.probability}%</td>
                                <td><span class="badge badge-${d.status === 'open' ? 'success' : 'danger'}">${d.status}</span></td>
                                <td>
                                    <button class="btn-primary btn-small" onclick="editDeal('${d.id}')">แก้ไข</button>
                                    <button class="btn-danger btn-small" onclick="deleteDeal('${d.id}')">ลบ</button>
                                </td>
                            </tr>
                        `;
                        }).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('dealsTable').innerHTML = html;
        }
        
        function openDealModal(id = null) {
            document.getElementById('dealModal').classList.add('show');
            document.getElementById('dealId').value = id || '';
            document.getElementById('modalTitle').textContent = id ? 'แก้ไขดีล' : 'เพิ่มดีลใหม่';
            
            if (id) {
                fetch(`${API_BASE}/deals/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.data) {
                            const d = data.data;
                            document.getElementById('dealName').value = d.deal_name || '';
                            document.getElementById('customerId').value = d.customer_id || '';
                            document.getElementById('amount').value = d.amount || '';
                            document.getElementById('commissionRate').value = d.commission_rate || 7;
                            document.getElementById('stage').value = d.stage || 'prospecting';
                            document.getElementById('probability').value = d.probability || 0;
                            document.getElementById('expectedCloseDate').value = d.expected_close_date || '';
                            document.getElementById('status').value = d.status || 'open';
                            document.getElementById('description').value = d.description || '';
                            document.getElementById('notes').value = d.notes || '';
                            // คำนวณหลังจากโหลดข้อมูลเสร็จ
                            setTimeout(updateDealCalculations, 100);
                        }
                    });
            } else {
                document.getElementById('dealForm').reset();
                document.getElementById('commissionRate').value = 7;
                updateDealCalculations();
            }
        }
        
        function closeDealModal() {
            document.getElementById('dealModal').classList.remove('show');
        }
        
        function editDeal(id) {
            openDealModal(id);
        }
        
        function deleteDeal(id) {
            if (!confirm('คุณแน่ใจว่าต้องการลบดีลนี้?')) return;
            
            fetch(`${API_BASE}/deals/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    loadDeals();
                });
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB'
            }).format(amount);
        }
        
        // ฟังก์ชันคำนวณและแสดงผลการคำนวณสำหรับ Deals
        function updateDealCalculations() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const commissionRate = parseFloat(document.getElementById('commissionRate').value) || 0;
            const commissionAmount = amount * (commissionRate / 100);
            const netIncome = amount - commissionAmount;
            
            // แสดงผลการคำนวณ
            document.getElementById('displayDealAmount').textContent = formatCurrency(amount);
            document.getElementById('displayDealCommissionRate').textContent = commissionRate.toFixed(2);
            document.getElementById('displayDealCommissionAmount').textContent = formatCurrency(-commissionAmount);
            document.getElementById('displayDealNetIncome').textContent = formatCurrency(netIncome);
        }
        
        document.getElementById('dealForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('dealId').value;
            const data = {
                deal_name: document.getElementById('dealName').value,
                customer_id: document.getElementById('customerId').value,
                amount: parseFloat(document.getElementById('amount').value),
                commission_rate: parseFloat(document.getElementById('commissionRate').value) || 7,
                stage: document.getElementById('stage').value,
                probability: parseInt(document.getElementById('probability').value),
                expected_close_date: document.getElementById('expectedCloseDate').value || null,
                status: document.getElementById('status').value,
                description: document.getElementById('description').value,
                notes: document.getElementById('notes').value
            };
            
            const method = id ? 'PUT' : 'POST';
            const url = id ? `${API_BASE}/deals/${id}` : `${API_BASE}/deals`;
            
            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                closeDealModal();
                loadDeals();
            });
        });
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const stageFilter = document.getElementById('stageFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', loadDeals);
            }
            
            if (stageFilter) {
                stageFilter.addEventListener('change', loadDeals);
            }
            
            loadCustomers();
            loadDeals();
        });
    </script>
</body>
</html>

