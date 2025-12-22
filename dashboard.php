<?php require_once 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - SalesFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>แดชบอร์ด</h1>
        </div>
        
        <div id="statsContainer" class="stats-grid">
            <!-- Stats will be loaded here -->
            <div class="loading">กำลังโหลดข้อมูล...</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">ยอดขายย้อนหลัง</h2>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button class="btn-secondary period-btn" data-period="month" style="padding: 0.5rem 1rem; font-size: 0.875rem;">เดือน</button>
                    <button class="btn-secondary period-btn" data-period="quarter" style="padding: 0.5rem 1rem; font-size: 0.875rem;">ไตรมาส</button>
                    <button class="btn-secondary period-btn active" data-period="year" style="padding: 0.5rem 1rem; font-size: 0.875rem;">ปี</button>
                </div>
            </div>
            <div style="position: relative; height: 400px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">ดีลล่าสุด</h2>
                </div>
                <div id="recentDeals" class="table-container">
                    <div class="loading">กำลังโหลด...</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">กิจกรรมที่ต้องทำ</h2>
                </div>
                <div id="upcomingActivities" class="table-container">
                    <div class="loading">กำลังโหลด...</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Sales Funnel</h2>
            </div>
            <canvas id="funnelChart"></canvas>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api';
        let revenueChartInstance = null;
        let funnelChartInstance = null;
        let currentPeriod = 'year'; // default to year
        
        async function loadDashboard(period = 'year') {
            try {
                const url = `${API_BASE}/dashboard${period ? '?period=' + period : ''}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.data) {
                    displayStats(result.data);
                    displayRecentDeals(result.data.recent_deals || []);
                    displayActivities(result.data.upcoming_activities || []);
                    
                    // Log revenue data for debugging
                    console.log('Revenue trend data for period', period, ':', result.data.revenue_trend);
                    
                    drawRevenueChart(result.data.revenue_trend || [], period);
                    drawFunnelChart(result.data.sales_funnel || {});
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }
        
        // Handle period button clicks
        document.addEventListener('DOMContentLoaded', function() {
            const periodButtons = document.querySelectorAll('.period-btn');
            
            periodButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Remove active class from ALL buttons first
                    periodButtons.forEach(b => {
                        b.classList.remove('active');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get period value
                    currentPeriod = this.getAttribute('data-period');
                    
                    // Reload dashboard with new period
                    loadDashboard(currentPeriod);
                });
            });
        });
        
        function displayStats(data) {
            const stats = [
                { title: 'ลูกค้าทั้งหมด', value: data.total_customers || 0, icon: 'fa-users', color: '#2563eb' },
                { title: 'ดีลที่เปิดอยู่', value: data.total_deals || 0, icon: 'fa-handshake', color: '#10b981' },
                { title: 'มูลค่าดีลทั้งหมด', value: formatCurrency(data.total_deal_value || 0), icon: 'fa-dollar-sign', color: '#f59e0b' },
                { title: 'รายได้ทั้งหมด', value: formatCurrency(data.total_revenue || 0), icon: 'fa-chart-line', color: '#2563eb' },
                { title: 'ค่าคอมมิชชั่นรวม', value: formatCurrency(data.total_commission || 0), icon: 'fa-percent', color: '#ef4444' },
                { title: 'รายได้สุทธิรวม', value: formatCurrency(data.total_net_income || 0), icon: 'fa-coins', color: '#10b981' },
                { title: 'กิจกรรมค้าง', value: data.pending_activities || 0, icon: 'fa-calendar-check', color: '#8b5cf6' },
                { title: 'กิจกรรมเกินกำหนด', value: data.overdue_activities || 0, icon: 'fa-exclamation-triangle', color: '#ef4444' }
            ];
            
            const html = stats.map(stat => `
                <div class="stat-card" style="border-left-color: ${stat.color}">
                    <i class="fas ${stat.icon} stat-icon" style="color: ${stat.color}"></i>
                    <h3>${stat.value}</h3>
                    <p>${stat.title}</p>
                </div>
            `).join('');
            
            document.getElementById('statsContainer').innerHTML = html;
        }
        
        function displayRecentDeals(deals) {
            if (deals.length === 0) {
                document.getElementById('recentDeals').innerHTML = '<p class="text-center">ไม่มีดีล</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อดีล</th>
                            <th>ลูกค้า</th>
                            <th>มูลค่า</th>
                            <th>ขั้นตอน</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${deals.map(deal => `
                            <tr>
                                <td>${deal.deal_name}</td>
                                <td>${deal.customer_name || '-'}</td>
                                <td>${formatCurrency(deal.amount)}</td>
                                <td><span class="badge badge-info">${deal.stage}</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('recentDeals').innerHTML = html;
        }
        
        function displayActivities(activities) {
            if (activities.length === 0) {
                document.getElementById('upcomingActivities').innerHTML = '<p class="text-center">ไม่มีกิจกรรม</p>';
                return;
            }
            
            const html = `
                <table>
                    <thead>
                        <tr>
                            <th>หัวข้อ</th>
                            <th>ลูกค้า</th>
                            <th>วันที่ครบกำหนด</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${activities.map(activity => `
                            <tr>
                                <td>${activity.subject}</td>
                                <td>${activity.customer_name || '-'}</td>
                                <td>${activity.due_date ? new Date(activity.due_date).toLocaleDateString('th-TH') : '-'}</td>
                                <td><span class="badge badge-${activity.status === 'completed' ? 'success' : 'warning'}">${activity.status}</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('upcomingActivities').innerHTML = html;
        }
        
        function drawRevenueChart(revenueData, period = 'year') {
            const canvas = document.getElementById('revenueChart');
            if (!canvas) {
                console.error('Canvas element not found');
                return;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Could not get 2d context from canvas');
                return;
            }
            
            // Destroy existing chart if it exists
            if (revenueChartInstance) {
                revenueChartInstance.destroy();
                revenueChartInstance = null;
            }
            
            // Check if we have data
            if (!revenueData || revenueData.length === 0) {
                console.warn('No revenue data found for period:', period);
                return;
            }
            
            // Sort data by period (ascending order for proper chronological display)
            const sortedData = [...revenueData].sort((a, b) => {
                return a.period.localeCompare(b.period);
            });
            
            // Format labels based on period
            const labels = sortedData.map(d => {
                const periodStr = d.period;
                if (period === 'year') {
                    return periodStr; // YYYY
                } else if (period === 'quarter') {
                    // Format: 2024-Q1, 2024-Q2, etc.
                    const parts = periodStr.split('-Q');
                    if (parts.length === 2) {
                        const year = parts[0];
                        const quarter = parts[1];
                        return `ไตรมาส ${quarter}/${year}`;
                    }
                    return periodStr;
                } else {
                    // Format: YYYY-MM to Thai month
                    const parts = periodStr.split('-');
                    if (parts.length === 2) {
                        const year = parts[0];
                        const month = parseInt(parts[1]);
                        const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 
                                          'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                        return `${monthNames[month - 1]} ${year}`;
                    }
                    return periodStr;
                }
            });
            
            const revenues = sortedData.map(d => parseFloat(d.revenue || 0));
            
            console.log('Chart labels:', labels);
            console.log('Chart revenues:', revenues);
            console.log('Data length:', labels.length, revenues.length);
            
            try {
                revenueChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'รายได้',
                            data: revenues,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 750
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('th-TH').format(value);
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'รายได้: ' + new Intl.NumberFormat('th-TH', {
                                            style: 'currency',
                                            currency: 'THB'
                                        }).format(context.parsed.y);
                                    }
                                }
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
                console.log('Chart created successfully');
            } catch (error) {
                console.error('Error creating chart:', error);
            }
        }
        
        function drawFunnelChart(funnelData) {
            const ctx = document.getElementById('funnelChart');
            
            // Destroy existing chart if it exists
            if (funnelChartInstance) {
                funnelChartInstance.destroy();
            }
            
            funnelChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Prospecting', 'Qualification', 'Proposal', 'Negotiation', 'Closed'],
                    datasets: [{
                        label: 'จำนวนดีล',
                        data: [
                            funnelData.prospecting || 0,
                            funnelData.qualification || 0,
                            funnelData.proposal || 0,
                            funnelData.negotiation || 0,
                            funnelData.closed || 0
                        ],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB'
            }).format(amount);
        }
        
        // Load dashboard on page load (default to year)
        loadDashboard('year');
    </script>
</body>
</html>

