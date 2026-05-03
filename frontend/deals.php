<?php
/**
 * Deals Page
 * Deals management interface
 */
$pageTitle = 'Thỏa thuận - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Quản lý thỏa thuận</h1>
            <p class="text-muted mb-0">Theo dõi cơ hội kinh doanh và quy trình bán hàng</p>
        </div>
        <div class="d-flex gap-2">
            <a href="pipeline.php" class="btn btn-outline-primary">
                <i class="bi bi-kanban me-2"></i>Xem quy trình
            </a>
            <a href="deals.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Thêm mới
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-white border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Tổng thỏa thuận</div>
                            <h3 class="mb-0" id="totalDeals">0</h3>
                        </div>
                        <i class="bi bi-briefcase text-primary fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Giá trị quy trình</div>
                            <h3 class="mb-0" id="pipelineValue">0 ₫</h3>
                        </div>
                        <i class="bi bi-graph-up text-success fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Tỷ lệ thắng</div>
                            <h3 class="mb-0" id="winRate">0%</h3>
                        </div>
                        <i class="bi bi-trophy text-info fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Giá trị TB</div>
                            <h3 class="mb-0" id="avgDealSize">0 ₫</h3>
                        </div>
                        <i class="bi bi-currency-dollar text-warning fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm thỏa thuận...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="stageFilter">
                        <option value="">Tất cả giai đoạn</option>
                        <option value="prospect">Tiềm năng</option>
                        <option value="qualification">Xác minh</option>
                        <option value="proposal">Đề xuất</option>
                        <option value="negotiation">Thương lượng</option>
                        <option value="won">Thành công</option>
                        <option value="lost">Thất bại</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="customerFilter">
                        <option value="">Tất cả KH</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="assignedFilter">
                        <option value="">Tất cả NV</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" id="minValue" placeholder="Giá trị từ">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Deals Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-crm mb-0">
                    <thead>
                        <tr>
                            <th>Thỏa thuận</th>
                            <th>Khách hàng</th>
                            <th>Giá trị</th>
                            <th>Giai đoạn</th>
                            <th>Xác suất</th>
                            <th>Ngày đóng (dự kiến)</th>
                            <th>Người phụ trách</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="dealsTable">
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div id="pagination"></div>
        </div>
    </div>
</div>

<!-- Add/Edit Deal Modal -->
<div class="modal fade" id="dealModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Thêm thỏa thuận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="dealForm">
                <div class="modal-body">
                    <input type="hidden" id="dealId">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Khách hàng <span class="text-danger">*</span></label>
                            <select class="form-select" id="customerId" required>
                                <option value="">Chọn khách hàng</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lead (nếu có)</label>
                            <select class="form-select" id="leadId">
                                <option value="">Không có</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giá trị (VND)</label>
                            <input type="number" class="form-control" id="value" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giai đoạn</label>
                            <select class="form-select" id="stage">
                                <option value="prospect">Tiềm năng</option>
                                <option value="qualification">Xác minh</option>
                                <option value="proposal">Đề xuất</option>
                                <option value="negotiation">Thương lượng</option>
                                <option value="won">Thành công</option>
                                <option value="lost">Thất bại</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngày đóng dự kiến</label>
                            <input type="date" class="form-control" id="expectedCloseDate">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nguồn</label>
                            <select class="form-select" id="source">
                                <option value="">Chọn nguồn</option>
                                <option value="Website">Website</option>
                                <option value="Referral">Giới thiệu</option>
                                <option value="Cold Call">Gọi lạnh</option>
                                <option value="Event">Sự kiện</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Người phụ trách</label>
                            <select class="form-select" id="assignedTo">
                                <option value="">Chọn nhân viên</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$inlineJS = '
let currentPage = 1;
let dealModal;

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    dealModal = new bootstrap.Modal(document.getElementById("dealModal"));
    
    loadDeals();
    loadStats();
    loadFilterOptions();
    
    // Search and filters
    document.getElementById("searchInput").addEventListener("input", debounce(function() {
        currentPage = 1;
        loadDeals();
    }, 300));
    
    ["stageFilter", "customerFilter", "assignedFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", function() {
            currentPage = 1;
            loadDeals();
        });
    });
    
    document.getElementById("minValue")?.addEventListener("input", debounce(function() {
        currentPage = 1;
        loadDeals();
    }, 300));
    
    document.getElementById("dealForm").addEventListener("submit", saveDeal);
    
    // URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("action") === "add") {
        openModal();
    }
});

function loadStats() {
    fetch(`${API_BASE_URL}/dashboard.php?action=deals-summary`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById("totalDeals").textContent = stats.total || 0;
                document.getElementById("pipelineValue").textContent = formatCurrency(stats.pipeline_value || 0);
                document.getElementById("winRate").textContent = (stats.win_rate || 0) + "%";
                document.getElementById("avgDealSize").textContent = formatCurrency(stats.avg_value || 0);
            }
        });
}

function loadDeals() {
    const params = new URLSearchParams({
        page: currentPage,
        per_page: 20
    });
    
    const search = document.getElementById("searchInput")?.value;
    const stage = document.getElementById("stageFilter")?.value;
    const customer = document.getElementById("customerFilter")?.value;
    const assigned = document.getElementById("assignedFilter")?.value;
    const minValue = document.getElementById("minValue")?.value;
    
    if (search) params.append("search", search);
    if (stage) params.append("stage", stage);
    if (customer) params.append("customer_id", customer);
    if (assigned) params.append("assigned_to", assigned);
    if (minValue) params.append("min_value", minValue);
    
    fetch(`${API_BASE_URL}/deals.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTable(data.data.data);
                renderPagination(data.data.pagination);
            }
        });
}

function renderTable(deals) {
    const tbody = document.getElementById("dealsTable");
    
    if (deals.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Không có deals nào</td></tr>`;
        return;
    }
    
    tbody.innerHTML = deals.map(d => `
        <tr>
            <td>
                <div class="fw-bold">${d.title}</div>
                <small class="text-muted">${d.deal_code}</small>
            </td>
            <td>
                <div>${d.customer_name || "-"}</div>
                <small class="text-muted">${d.customer_company || ""}</small>
            </td>
            <td class="fw-bold text-success">${formatCurrency(d.value || 0)}</td>
            <td><span class="badge badge-${d.stage}">${d.stage}</span></td>
            <td>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: ${d.probability || 0}%"></div>
                </div>
                <small class="text-muted">${d.probability || 0}%</small>
            </td>
            <td>${d.expected_close_date ? formatDate(d.expected_close_date) : "-"}</td>
            <td>${d.assigned_to_name || "-"}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editDeal(${d.id})" data-bs-toggle="tooltip" title="Sửa">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteDeal(${d.id})" data-bs-toggle="tooltip" title="Xóa">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join("");
    
    initTooltips();
}

function renderPagination(pagination) {
    const container = document.getElementById("pagination");
    container.innerHTML = createPagination(pagination.last_page, pagination.current_page, function(page) {
        currentPage = page;
        loadDeals();
    });
}

function loadFilterOptions() {
    // Load customers
    fetch(`${API_BASE_URL}/customers.php?per_page=1000`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selects = ["customerId", "customerFilter"];
                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        data.data.data.forEach(c => {
                            select.innerHTML += `<option value="${c.id}">${c.full_name} ${c.company_name ? "(" + c.company_name + ")" : ""}</option>`;
                        });
                    }
                });
            }
        });
    
    // Load users
    fetch(`${API_BASE_URL}/users.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selects = ["assignedTo", "assignedFilter"];
                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        data.data.data.forEach(u => {
                            select.innerHTML += `<option value="${u.id}">${u.full_name}</option>`;
                        });
                    }
                });
            }
        });
    
    // Load leads
    fetch(`${API_BASE_URL}/leads.php?status=new&per_page=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("leadId");
                data.data.data.forEach(l => {
                    select.innerHTML += `<option value="${l.id}">${l.full_name} (${l.lead_code})</option>`;
                });
            }
        });
}

function openModal() {
    document.getElementById("dealForm").reset();
    document.getElementById("dealId").value = "";
    document.getElementById("modalTitle").textContent = "Thêm Deal";
    dealModal.show();
}

function editDeal(id) {
    fetch(`${API_BASE_URL}/deals.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const d = data.data;
                document.getElementById("dealId").value = d.id;
                document.getElementById("title").value = d.title;
                document.getElementById("description").value = d.description || "";
                document.getElementById("customerId").value = d.customer_id;
                document.getElementById("leadId").value = d.lead_id || "";
                document.getElementById("value").value = d.value || 0;
                document.getElementById("stage").value = d.stage;
                document.getElementById("expectedCloseDate").value = d.expected_close_date || "";
                document.getElementById("source").value = d.source || "";
                document.getElementById("assignedTo").value = d.assigned_to || "";
                document.getElementById("notes").value = d.notes || "";
                
                document.getElementById("modalTitle").textContent = "Sửa Deal";
                dealModal.show();
            }
        });
}

function saveDeal(e) {
    e.preventDefault();
    
    const id = document.getElementById("dealId").value;
    const data = {
        title: document.getElementById("title").value,
        description: document.getElementById("description").value,
        customer_id: document.getElementById("customerId").value,
        lead_id: document.getElementById("leadId").value,
        value: document.getElementById("value").value,
        stage: document.getElementById("stage").value,
        expected_close_date: document.getElementById("expectedCloseDate").value,
        source: document.getElementById("source").value,
        assigned_to: document.getElementById("assignedTo").value,
        notes: document.getElementById("notes").value
    };
    
    if (id) data.id = id;
    
    fetch(`${API_BASE_URL}/deals.php`, {
        method: id ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert(id ? "Cập nhật thành công!" : "Thêm deal thành công!", "success");
            dealModal.hide();
            loadDeals();
            loadStats();
        } else {
            showAlert(result.message, "danger");
        }
    });
}

function deleteDeal(id) {
    confirmDialog("Bạn có chắc chắn muốn xóa deal này?", function() {
        fetch(`${API_BASE_URL}/deals.php?id=${id}`, {
            method: "DELETE"
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert("Đã xóa deal!", "success");
                loadDeals();
                loadStats();
            } else {
                showAlert(result.message, "danger");
            }
        });
    });
}

function resetFilters() {
    document.getElementById("searchInput").value = "";
    document.getElementById("stageFilter").value = "";
    document.getElementById("customerFilter").value = "";
    document.getElementById("assignedFilter").value = "";
    document.getElementById("minValue").value = "";
    currentPage = 1;
    loadDeals();
}
';

include 'components/footer.php';
?>
