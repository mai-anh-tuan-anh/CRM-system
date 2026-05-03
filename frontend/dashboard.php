<?php
/**
 * Tổng quan
 * Trang tổng quan với thống kê và báo cáo
 */
$pageTitle = 'Tổng quan - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tổng quan</h1>
        <div>
            <a href="reports.php" class="btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-graph-up me-2"></i>Xem báo cáo chi tiết
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Thẻ khách hàng -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats primary h-100 py-2">
                <div class="card-body">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="customerCount">0</h3>
                            <p>Khách hàng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thẻ KH tiềm năng -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats info h-100 py-2">
                <div class="card-body">
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="leadCount">0</h3>
                            <p>Khách hàng tiềm năng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thẻ thỏa thuận -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats success h-100 py-2">
                <div class="card-body">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="dealCount">0</h3>
                            <p>Thỏa thuận</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thẻ doanh thu -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats warning h-100 py-2">
                <div class="card-body">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="revenueAmount">0 ₫</h3>
                            <p>Doanh thu tháng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Row -->
    <div class="row">
        <!-- Biểu đồ doanh thu -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-graph-up me-2"></i>Biểu đồ doanh thu
                    </h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            12 tháng
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-months="6">6 tháng</a></li>
                            <li><a class="dropdown-item" href="#" data-months="12">12 tháng</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tổng quan quy trình -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-funnel me-2"></i>Quy trình bán hàng
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="pipelineChart"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted">Tổng giá trị quy trình: <strong id="pipelineValue">0 ₫</strong></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row -->
    <div class="row">
        <!-- Recent Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i>Hoạt động gần đây
                    </h6>
                    <a href="activities.php" class="btn btn-sm btn-link">Xem tất cả</a>
                </div>
                <div class="card-body" id="activityList">
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Công việc sắp tới -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-calendar-check me-2"></i>Công việc sắp tới
                    </h6>
                    <a href="tasks.php" class="btn btn-sm btn-link">Xem tất cả</a>
                </div>
                <div class="card-body" id="taskList">
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hàng thứ 3 - Tổng quan thỏa thuận -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-briefcase me-2"></i>Thỏa thuận theo giai đoạn
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row" id="dealsByStage">
                        <!-- Dynamic content -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$inlineJS = '
// Load dashboard data
function loadDashboardData() {
    fetch(`${API_BASE_URL}/dashboard.php?action=overview`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboard(data.data);
            }
        })
        .catch(error => console.error("Lỗi tải dashboard:", error));
}

function updateDashboard(data) {
    // Update stats cards
    const stats = data.statistics;
    document.getElementById("customerCount").textContent = stats.customers?.total || 0;
    document.getElementById("leadCount").textContent = stats.leads?.total || 0;
    document.getElementById("dealCount").textContent = stats.deals?.total || 0;
    document.getElementById("revenueAmount").textContent = formatCurrency(stats.revenue?.this_month || 0);
    
    // Update pipeline value
    document.getElementById("pipelineValue").textContent = formatCurrency(stats.deals?.pipeline_value || 0);
    
    // Render charts
    renderRevenueChart(stats.revenue?.monthly_trend || []);
    renderPipelineChart(stats.deals?.by_stage || []);
    
    // Render deals by stage
    renderDealsByStage(stats.deals?.by_stage || []);
    
    // Render activity list
    renderActivityList(data.recent_activity || []);
    
    // Render upcoming tasks
    renderUpcomingTasks(data.upcoming?.tasks || []);
}

// Revenue Chart
let revenueChartInstance = null;
function renderRevenueChart(data) {
    const ctx = document.getElementById("revenueChart").getContext("2d");
    
    if (revenueChartInstance) {
        revenueChartInstance.destroy();
    }
    
    const labels = data.map(item => item.month_label);
    const values = data.map(item => item.revenue);
    
    revenueChartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: "Doanh thu",
                data: values,
                borderColor: "#4e73df",
                backgroundColor: "rgba(78, 115, 223, 0.1)",
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat("vi-VN", {
                                notation: "compact",
                                compactDisplay: "short"
                            }).format(value);
                        }
                    }
                }
            }
        }
    });
}

// Pipeline Chart
function renderPipelineChart(data) {
    const ctx = document.getElementById("pipelineChart").getContext("2d");
    
    const stageNames = {
        prospect: "Tiềm năng",
        qualification: "Xác minh",
        proposal: "Đề xuất",
        negotiation: "Thương lượng",
        won: "Thành công",
        lost: "Thất bại"
    };
    
    const filtered = data.filter(item => item.stage !== "won" && item.stage !== "lost");
    
    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: filtered.map(item => stageNames[item.stage] || item.stage),
            datasets: [{
                data: filtered.map(item => item.count),
                backgroundColor: ["#4e73df", "#36b9cc", "#f6c23e", "#e74a3b"]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: { boxWidth: 12 }
                }
            }
        }
    });
}

// Deals by Stage
function renderDealsByStage(data) {
    const container = document.getElementById("dealsByStage");
    const stageNames = {
        prospect: "Tiềm năng",
        qualification: "Xác minh",
        proposal: "Đề xuất",
        negotiation: "Thương lượng",
        won: "Thành công",
        lost: "Thất bại"
    };
    
    const colors = {
        prospect: "primary",
        qualification: "info",
        proposal: "warning",
        negotiation: "danger",
        won: "success",
        lost: "secondary"
    };
    
    container.innerHTML = data.map(item => `
        <div class="col-md-2 col-6 mb-3">
            <div class="card bg-${colors[item.stage] || "light"} text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">${item.count}</h3>
                    <small>${stageNames[item.stage] || item.stage}</small>
                    <div class="mt-2 fw-bold">${formatCurrency(item.total_value || 0)}</div>
                </div>
            </div>
        </div>
    `).join("");
}

// Activity List
function renderActivityList(activities) {
    const container = document.getElementById("activityList");
    
    if (activities.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-3">Không có hoạt động gần đây</div>`;
        return;
    }
    
    container.innerHTML = activities.map(activity => `
        <div class="activity-item">
            <div class="activity-avatar">
                ${activity.performed_by_name?.charAt(0).toUpperCase() || "U"}
            </div>
            <div class="activity-content">
                <div class="activity-text">${activity.description}</div>
                <div class="activity-time">
                    <i class="bi bi-clock me-1"></i>${timeAgo(activity.performed_at)}
                </div>
            </div>
        </div>
    `).join("");
}

// Upcoming Tasks
function renderUpcomingTasks(tasks) {
    const container = document.getElementById("taskList");
    
    if (tasks.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-3">Không có công việc sắp tới</div>`;
        return;
    }
    
    container.innerHTML = tasks.map(task => {
        const isOverdue = new Date(task.due_date) < new Date();
        return `
        <div class="d-flex align-items-center p-3 border-bottom">
            <div class="flex-shrink-0">
                <input type="checkbox" class="form-check-input" data-task-id="${task.id}">
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="fw-bold ${isOverdue ? "text-danger" : ""}">${task.title}</div>
                <small class="text-muted">
                    <i class="bi bi-${isOverdue ? "exclamation-circle text-danger" : "calendar"} me-1"></i>
                    ${formatDate(task.due_date)} ${isOverdue ? "(Quá hạn)" : ""}
                </small>
            </div>
            <div class="flex-shrink-0">
                <span class="badge badge-${task.priority}">${task.priority}</span>
            </div>
        </div>
        `;
    }).join("");
    
    // Add checkbox handlers
    container.querySelectorAll("input[type=\"checkbox\"]").forEach(checkbox => {
        checkbox.addEventListener("change", function() {
            if (this.checked) {
                const taskId = this.dataset.taskId;
                completeTask(taskId);
            }
        });
    });
}

function completeTask(taskId) {
    fetch(`${API_BASE_URL}/tasks.php`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: taskId, action: "complete" })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert("Đã hoàn thành công việc!", "success");
            loadDashboardData();
        }
    });
}

// Initial load
loadDashboardData();
';

include 'components/footer.php';
?>
