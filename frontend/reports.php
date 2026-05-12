<?php
/**
 * Báo cáo
 * Trang xem báo cáo và thống kê
 */
$pageTitle = 'Báo cáo - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <!-- Permission Check -->
    <script>
        // Check if user has permission to access this page
        fetch('/customer_management/backend/api/auth.php?action=me')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const role = data.data.role;
                    // Sales cannot access reports
                    if (role === 'sales') {
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    window.location.href = 'login.php';
                }
            })
            .catch(() => {
                window.location.href = 'login.php';
            });
    </script>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Báo cáo</h1>
            <p class="text-muted mb-0">Thống kê và phân tích dữ liệu CRM</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select" id="yearFilter" style="width: 120px;">
                <?php
                $currentYear = date('Y');
                for ($year = 2024; $year <= 2027; $year++) {
                    $selected = ($year == $currentYear) ? 'selected' : '';
                    echo "<option value=\"$year\" $selected>$year</option>";
                }
                ?>
            </select>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel me-2"></i>Excel
            </button>
            <button class="btn btn-danger" onclick="exportToPDF()">
                <i class="bi bi-file-earmark-pdf me-2"></i>PDF
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summaryCards">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Tổng doanh thu</h6>
                            <h3 class="mb-0" id="totalRevenue">0 đ</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded p-3">
                            <i class="bi bi-currency-dollar text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Thỏa thuận thắng</h6>
                            <h3 class="mb-0" id="wonDeals">0</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded p-3">
                            <i class="bi bi-check-circle text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Giá trị quy trình</h6>
                            <h3 class="mb-0" id="pipelineValue">0 đ</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-3">
                            <i class="bi bi-funnel text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Doanh thu theo tháng</h5>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary active" onclick="switchChartType('monthly')">Theo tháng</button>
                <button class="btn btn-outline-primary" onclick="switchChartType('yearly')">Theo năm</button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>

    <div class="row">
        <!-- Deals by Stage -->
        <div class="col-12 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Thỏa thuận theo giai đoạn</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <canvas id="dealsByStageChart"></canvas>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table table-sm" id="dealsByStageTable">
                                    <thead>
                                        <tr>
                                            <th>Giai đoạn</th>
                                            <th>Số lượng</th>
                                            <th>Tổng giá trị</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Sales Performance -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Hiệu suất nhân viên</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-crm" id="performanceTable">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Thỏa thuận</th>
                            <th>Thắng</th>
                            <th>Thua</th>
                            <th>Doanh thu</th>
                            <th>Tỷ lệ thắng</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Lead Sources -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Nguồn khách hàng tiềm năng</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="leadSourcesChart"></canvas>
                </div>
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-sm" id="sourcesTable">
                            <thead>
                                <tr>
                                    <th>Nguồn</th>
                                    <th>Số lượng</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Win Rate by Source -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Tỷ lệ thắng theo nguồn</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5" style="height: 300px; position: relative;">
                            <canvas id="winRateBySourceChart"></canvas>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table table-sm" id="winRateBySourceTable">
                                    <thead>
                                        <tr>
                                            <th>Nguồn</th>
                                            <th>Tổng</th>
                                            <th>Thắng</th>
                                            <th>Thua</th>
                                            <th>Tỷ lệ</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers by Industry -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-buildings me-2"></i>Khách hàng theo ngành nghề</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5" style="height: 300px; position: relative;">
                            <canvas id="customersByIndustryChart"></canvas>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table table-sm" id="customersByIndustryTable">
                                    <thead>
                                        <tr>
                                            <th>Ngành nghề</th>
                                            <th>Số lượng</th>
                                            <th>Hoạt động</th>
                                            <th>Không hoạt động</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xuất báo cáo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Chọn dữ liệu cần xuất:</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="exportRevenue" checked>
                    <label class="form-check-label" for="exportRevenue">Doanh thu theo tháng</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="exportDeals" checked>
                    <label class="form-check-label" for="exportDeals">Thỏa thuận theo giai đoạn</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="exportPerformance" checked>
                    <label class="form-check-label" for="exportPerformance">Hiệu suất nhân viên</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="confirmExport()">Xuất</button>
            </div>
        </div>
    </div>
</div>

<?php
$inlineJS = '
let currentChartType = "monthly";
let chartInstances = {};
let reportData = {};

// Helper function to render avatar
function renderAvatar(avatar, name, size = \'md\', extraClass = \'\') {
    const initials = (name || \'U\').charAt(0).toUpperCase();
    const sizeStyles = {
        sm: \'width:24px;height:24px;font-size:0.75rem;\',
        md: \'width:32px;height:32px;font-size:0.875rem;\',
        lg: \'width:40px;height:40px;font-size:1rem;\'
    };
    const style = sizeStyles[size] || sizeStyles.md;
    const baseClass = `rounded-circle d-flex align-items-center justify-content-center overflow-hidden ${extraClass}`;

    if (avatar) {
        return `<div class="${baseClass}" style="${style}">
            <img src="${avatar}" alt="${name}" style="width:100%;height:100%;object-fit:cover;">
        </div>`;
    }
    return `<div class="${baseClass} bg-primary text-white" style="${style}">${initials}</div>`;
}

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    loadReports();
    
    // Year filter change
    document.getElementById("yearFilter")?.addEventListener("change", function() {
        loadReports();
    });
});

function loadReports() {
    const year = document.getElementById("yearFilter")?.value || new Date().getFullYear();
    
    // Load all report data
    Promise.all([
        fetch(`${API_BASE_URL}/reports.php?action=revenue&year=${year}`).then(r => r.json()),
        fetch(`${API_BASE_URL}/reports.php?action=deals_summary&year=${year}`).then(r => r.json()),
        fetch(`${API_BASE_URL}/reports.php?action=performance&year=${year}`).then(r => r.json()),
        fetch(`${API_BASE_URL}/reports.php?action=sources&year=${year}`).then(r => r.json()),
        fetch(`${API_BASE_URL}/reports.php?action=win_rate_by_source&year=${year}`).then(r => r.json()),
        fetch(`${API_BASE_URL}/reports.php?action=customers_by_industry&year=${year}`).then(r => r.json())
    ]).then(([revenue, deals, performance, sources, winRate, customersByIndustry]) => {
        if (revenue.success) reportData.revenue = revenue.data;
        if (deals.success) reportData.deals = deals.data;
        if (performance.success) reportData.performance = performance.data;
        if (sources.success) reportData.sources = sources.data;
        if (winRate.success) reportData.winRate = winRate.data;
        if (customersByIndustry.success) reportData.customersByIndustry = customersByIndustry.data;

        renderSummary();
        renderRevenueChart();
        renderDealsByStageChart();
        renderPerformanceTable();
        renderLeadSources();
        renderWinRateBySource();
        renderCustomersByIndustry();
    });
}

function renderSummary() {
    if (!reportData.deals) return;

    const totals = reportData.deals.totals;
    document.getElementById("totalRevenue").textContent = formatCurrency(totals.total_won || 0);
    document.getElementById("wonDeals").textContent = totals.won || 0;
    document.getElementById("pipelineValue").textContent = formatCurrency(totals.total_pipeline || 0);
}

function renderRevenueChart() {
    const ctx = document.getElementById("revenueChart");
    if (!ctx || !reportData.revenue) return;
    
    if (chartInstances.revenue) {
        chartInstances.revenue.destroy();
    }
    
    const data = reportData.revenue.data;
    const months = ["T1", "T2", "T3", "T4", "T5", "T6", "T7", "T8", "T9", "T10", "T11", "T12"];
    
    chartInstances.revenue = new Chart(ctx, {
        type: "bar",
        data: {
            labels: months,
            datasets: [{
                label: "Doanh thu (đ)",
                data: data.map(d => d.won_revenue),
                backgroundColor: "#4e73df",
                borderRadius: 4
            }, {
                label: "Pipeline (đ)",
                data: data.map(d => d.pipeline_value),
                backgroundColor: "#f6c23e",
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "top" },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ": " + formatCurrency(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

function renderDealsByStageChart() {
    const ctx = document.getElementById("dealsByStageChart");
    if (!ctx || !reportData.deals) return;
    
    if (chartInstances.dealsByStage) {
        chartInstances.dealsByStage.destroy();
    }
    
    const stageNames = {
        prospect: "Tiềm năng",
        qualification: "Xác minh",
        proposal: "Đề xuất",
        negotiation: "Thương lượng",
        won: "Thắng",
        lost: "Thua"
    };
    
    const colors = {
        prospect: "#4e73df",
        qualification: "#36b9cc",
        proposal: "#f6c23e",
        negotiation: "#e74a3b",
        won: "#1cc88a",
        lost: "#858796"
    };
    
    const data = reportData.deals.by_stage;
    
    chartInstances.dealsByStage = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: data.map(d => stageNames[d.stage] || d.stage),
            datasets: [{
                data: data.map(d => d.count),
                backgroundColor: data.map(d => colors[d.stage] || "#858796")
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: "right" }
            }
        }
    });
    
    // Table
    const tbody = document.querySelector("#dealsByStageTable tbody");
    if (tbody && reportData.deals.by_stage) {
        tbody.innerHTML = reportData.deals.by_stage.map(d => {
            return `
                <tr>
                    <td>${stageNames[d.stage] || d.stage}</td>
                    <td>${d.count}</td>
                    <td>${formatCurrency(d.total_value)}</td>
                </tr>
            `;
        }).join("");
    }
}

function renderPerformanceTable() {
    const tbody = document.querySelector("#performanceTable tbody");
    if (!tbody || !reportData.performance) return;
    
    const data = reportData.performance.data;
    
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>`;
        return;
    }
    
    tbody.innerHTML = data.map(p => {
        const total = p.won_count + p.lost_count;
        const winRate = total > 0 ? Math.round((p.won_count / total) * 100) : 0;
        
        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        ${renderAvatar(p.avatar, p.full_name, \'md\', \'me-2\')}
                        <span class="fw-bold">${p.full_name}</span>
                    </div>
                </td>
                <td>${p.deals_count || 0}</td>
                <td class="text-success">${p.won_count || 0}</td>
                <td class="text-danger">${p.lost_count || 0}</td>
                <td class="fw-bold">${formatCurrency(p.won_value || 0)}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: ${winRate}%"></div>
                        </div>
                        <span>${winRate}%</span>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function renderLeadSources() {
    if (!reportData.sources) return;
    
    const sourceNames = {
        // Keys
        website: "Website",
        referral: "Giới thiệu",
        social_media: "Mạng xã hội",
        cold_call: "Gọi lạnh",
        event: "Sự kiện",
        phone: "Điện thoại",
        email: "Email",
        other: "Khác",
        // English names from API
        "Social Media": "Mạng xã hội",
        "Referral": "Giới thiệu",
        "Event": "Sự kiện",
        "Email": "Email",
        "Website": "Website",
        "Cold Call": "Gọi lạnh",
        "Phone": "Điện thoại",
        "Other": "Khác"
    };
    
    // Chart
    const ctx = document.getElementById("leadSourcesChart");
    if (ctx && reportData.sources.leads) {
        if (chartInstances.sources) {
            chartInstances.sources.destroy();
        }
        
        const data = reportData.sources.leads;
        chartInstances.sources = new Chart(ctx, {
            type: "pie",
            data: {
                labels: data.map(d => sourceNames[d.source] || d.source),
                datasets: [{
                    data: data.map(d => d.count),
                    backgroundColor: ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b", "#6f42c1", "#fd7e14", "#858796"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: "right" }
                }
            }
        });
    }
    
    // Table
    const tbody = document.querySelector("#sourcesTable tbody");
    if (tbody && reportData.sources.leads) {
        tbody.innerHTML = reportData.sources.leads.map(s => {
            return `
                <tr>
                    <td>${sourceNames[s.source] || s.source}</td>
                    <td>${s.count}</td>
                </tr>
            `;
        }).join("");
    }
}

function renderWinRateBySource() {
    if (!reportData.winRate) return;
    
    const data = reportData.winRate.data;
    const sourceNames = {
        website: "Website",
        referral: "Giới thiệu",
        social_media: "Mạng xã hội",
        cold_call: "Gọi lạnh",
        event: "Sự kiện",
        phone: "Điện thoại",
        email: "Email",
        other: "Khác",
        "Social Media": "Mạng xã hội",
        "Referral": "Giới thiệu",
        "Event": "Sự kiện",
        "Email": "Email",
        "Website": "Website",
        "Cold Call": "Gọi lạnh",
        "Phone": "Điện thoại",
        "Other": "Khác"
    };
    
    const ctx = document.getElementById("winRateBySourceChart");
    if (ctx && data) {
        if (chartInstances.winRate) {
            chartInstances.winRate.destroy();
        }
        
        chartInstances.winRate = new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.map(d => sourceNames[d.source] || d.source),
                datasets: [{
                    label: "Tỷ lệ thắng (%)",
                    data: data.map(d => d.win_rate),
                    backgroundColor: "#4e73df"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    const tbody = document.querySelector("#winRateBySourceTable tbody");
    if (tbody && data) {
        tbody.innerHTML = data.map(d => {
            return `
                <tr>
                    <td>${sourceNames[d.source] || d.source}</td>
                    <td>${d.total_deals}</td>
                    <td>${d.won_deals}</td>
                    <td>${d.lost_deals}</td>
                    <td>${d.win_rate}%</td>
                </tr>
            `;
        }).join("");
    }
}

function renderCustomersByIndustry() {
    if (!reportData.customersByIndustry) return;
    
    const data = reportData.customersByIndustry.data;
    
    const industryNames = {
        "Healthcare": "Y tế",
        "Technology": "Công nghệ",
        "Retail": "Bán lẻ",
        "Manufacturing": "Sản xuất",
        "Finance": "Tài chính",
        "Education": "Giáo dục",
        "Real Estate": "Bất động sản",
        "Consulting": "Tư vấn",
        "Other": "Khác",
        "Khác": "Khác"
    };
    
    const colors = ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b", "#6f42c1", "#fd7e14", "#858796"];
    
    const ctx = document.getElementById("customersByIndustryChart");
    if (ctx && data) {
        if (chartInstances.industry) {
            chartInstances.industry.destroy();
        }
        
        chartInstances.industry = new Chart(ctx, {
            type: "pie",
            data: {
                labels: data.map(d => industryNames[d.industry] || d.industry),
                datasets: [{
                    data: data.map(d => d.count),
                    backgroundColor: colors.slice(0, data.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 1,
                plugins: {
                    legend: { position: "right" }
                }
            }
        });
    }
    
    const tbody = document.querySelector("#customersByIndustryTable tbody");
    if (tbody && data) {
        tbody.innerHTML = data.map(d => {
            return `
                <tr>
                    <td>${industryNames[d.industry] || d.industry}</td>
                    <td>${d.count}</td>
                    <td>${d.active_count}</td>
                    <td>${d.inactive_count}</td>
                </tr>
            `;
        }).join("");
    }
}

function switchChartType(type) {
    currentChartType = type;
    const year = document.getElementById("yearFilter")?.value || new Date().getFullYear();
    
    if (type === "yearly") {
        fetch(`${API_BASE_URL}/reports.php?action=revenue_yearly&start_year=2020&end_year=${year}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    reportData.revenue_yearly = data.data;
                    renderYearlyChart();
                }
            });
    } else {
        renderRevenueChart();
    }
    
    // Update button states
    document.querySelectorAll(".btn-group .btn").forEach(btn => {
        btn.classList.remove("active");
    });
    event.target.classList.add("active");
}

function renderYearlyChart() {
    const ctx = document.getElementById("revenueChart");
    if (!ctx || !reportData.revenue_yearly) return;
    
    if (chartInstances.revenue) {
        chartInstances.revenue.destroy();
    }
    
    const data = reportData.revenue_yearly.data;
    
    chartInstances.revenue = new Chart(ctx, {
        type: "line",
        data: {
            labels: data.map(d => d.year),
            datasets: [{
                label: "Doanh thu (đ)",
                data: data.map(d => d.won_revenue),
                borderColor: "#4e73df",
                backgroundColor: "rgba(78, 115, 223, 0.1)",
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: "top" }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

// Export functions
function exportToExcel() {
    const modal = new bootstrap.Modal(document.getElementById("exportModal"));
    modal.show();
}

function confirmExport() {
    const year = document.getElementById("yearFilter")?.value || new Date().getFullYear();
    
    // Create CSV content
    let csv = "\\uFEFF"; // BOM for UTF-8
    csv += "BÁO CÁO CRM - NĂM " + year + "\\n\\n";
    
    // Revenue data
    if (document.getElementById("exportRevenue")?.checked && reportData.revenue) {
        csv += "DOANH THU THEO THÁNG\\n";
        csv += "Tháng,Doanh thu,Pipeline,Số thỏa thuận thắng,Số thỏa thuận thua,Số đang hoạt động\\n";
        const months = ["T1", "T2", "T3", "T4", "T5", "T6", "T7", "T8", "T9", "T10", "T11", "T12"];
        reportData.revenue.data.forEach((d, i) => {
            csv += `${months[i]},${d.won_revenue},${d.pipeline_value},${d.won_count},${d.lost_count},${d.active_count}\\n`;
        });
        csv += "\\n";
    }
    
    // Deals summary
    if (document.getElementById("exportDeals")?.checked && reportData.deals) {
        csv += "THỎA THUẬN THEO GIAI ĐOẠN\\n";
        csv += "Giai đoạn,Số lượng,Tổng giá trị,Giá trị TB\\n";
        reportData.deals.by_stage.forEach(d => {
            csv += `${d.stage},${d.count},${d.total_value},${d.avg_value}\\n`;
        });
        csv += "\\n";
    }
    
    // Performance
    if (document.getElementById("exportPerformance")?.checked && reportData.performance) {
        csv += "HIỆU SUẤT NHÂN VIÊN\\n";
        csv += "Nhân viên,Thỏa thuận,Thắng,Thua,Doanh thu\\n";
        reportData.performance.data.forEach(p => {
            csv += `${p.full_name},${p.deals_count},${p.won_count},${p.lost_count},${p.won_value}\\n`;
        });
    }
    
    // Download
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `bao-cao-crm-${year}.csv`;
    link.click();
    
    bootstrap.Modal.getInstance(document.getElementById("exportModal")).hide();
}

function exportToPDF() {
    alert("Tính năng xuất PDF cần thư viện thêm (jsPDF/html2canvas). Hiện tại vui lòng dùng chức năng in trang (Ctrl+P) và chọn Save as PDF.");
}
';

include 'components/footer.php';
?>