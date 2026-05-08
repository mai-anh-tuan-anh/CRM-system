<?php
/**
 * Khách hàng tiềm năng
 * Giao diện quản lý khách hàng tiềm năng
 */
$pageTitle = 'Khách hàng tiềm năng - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Quản lý khách hàng tiềm năng</h1>
            <p class="text-muted mb-0">Quản lý khách hàng tiềm năng và chuyển đổi thành khách hàng chính thức</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-2"></i>Nhập
            </button>
            <button class="btn btn-outline-secondary" onclick="exportLeads()">
                <i class="bi bi-download me-2"></i>Xuất
            </button>
            <a href="leads.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Thêm mới
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-white border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Tổng khách hàng tiềm năng</div>
                            <h3 class="mb-0" id="totalLeads">0</h3>
                        </div>
                        <i class="bi bi-bullseye text-primary fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-white border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Khách hàng tiềm năng mới</div>
                            <h3 class="mb-0" id="newLeads">0</h3>
                        </div>
                        <i class="bi bi-star text-warning fs-2"></i>
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
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Tìm kiếm khách hàng tiềm năng...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="new">Mới</option>
                        <option value="contacted">Đã liên hệ</option>
                        <option value="qualified">Đủ điều kiện</option>
                        <option value="lost">Mất</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="priorityFilter">
                        <option value="">Tất cả ưu tiên</option>
                        <option value="low">Thấp</option>
                        <option value="medium">Trung bình</option>
                        <option value="high">Cao</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sourceFilter">
                        <option value="">Tất cả nguồn</option>
                        <option value="Website">Website</option>
                        <option value="Referral">Giới thiệu</option>
                        <option value="Event">Sự kiện</option>
                        <option value="Email">Email</option>
                        <option value="Social Media">Mạng xã hội</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="assignedFilter">
                        <option value="">Tất cả nhân viên</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng khách hàng tiềm năng -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-crm mb-0">
                    <thead>
                        <tr>
                            <th>KH tiềm năng</th>
                            <th>Liên hệ</th>
                            <th>Công ty</th>
                            <th>Nguồn</th>
                            <th>Trạng thái</th>
                            <th>Điểm</th>
                            <th>Ưu tiên</th>
                            <th>Người phụ trách</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="leadsTable">
                        <tr>
                            <td colspan="9" class="text-center py-4">
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

<!-- Modal thêm/sửa KH tiềm năng -->
<div class="modal fade" id="leadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Thêm khách hàng tiềm năng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="leadForm">
                <div class="modal-body">
                    <input type="hidden" id="leadId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fullName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chức vụ</label>
                            <input type="text" class="form-control" id="jobTitle">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Điện thoại</label>
                            <input type="tel" class="form-control" id="phone">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" class="form-control" id="dob">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Công ty</label>
                            <input type="text" class="form-control" id="companyName">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nguồn</label>
                            <select class="form-select" id="source">
                                <option value="">Chọn nguồn</option>
                                <option value="Website">Website</option>
                                <option value="Referral">Giới thiệu</option>
                                <option value="Event">Sự kiện</option>
                                <option value="Email">Email</option>
                                <option value="Social Media">Mạng xã hội</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" id="address">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Thành phố</label>
                            <input type="text" class="form-control" id="city">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Website</label>
                            <input type="text" class="form-control" id="website">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" id="status">
                                <option value="new">Mới</option>
                                <option value="contacted">Đã liên hệ</option>
                                <option value="qualified">Đủ điều kiện</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ưu tiên</label>
                            <select class="form-select" id="priority">
                                <option value="low">Thấp</option>
                                <option value="medium" selected>Trung bình</option>
                                <option value="high">Cao</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Điểm (0-100)</label>
                            <input type="number" class="form-control" id="score" min="0" max="100" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Người phụ trách</label>
                        <select class="form-select" id="assignedTo">
                            <option value="">Chọn nhân viên</option>
                        </select>
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

<!-- Modal chuyển đổi KH tiềm năng -->
<div class="modal fade" id="convertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chuyển đổi khách hàng tiềm năng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="convertForm">
                <div class="modal-body">
                    <input type="hidden" id="convertLeadId">
                    <p>Khách hàng tiềm năng này sẽ được chuyển đổi thành khách hàng. Bạn có thể tạo thỏa thuận kèm theo.
                    </p>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="createDeal" checked>
                            <label class="form-check-label" for="createDeal">
                                Tạo thỏa thuận cho khách hàng này
                            </label>
                        </div>
                    </div>

                    <div id="dealFields">
                        <div class="mb-3">
                            <label class="form-label">Tên thỏa thuận</label>
                            <input type="text" class="form-control" id="dealTitle">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá trị (VND)</label>
                            <input type="number" class="form-control" id="dealValue" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giai đoạn</label>
                            <select class="form-select" id="dealStage">
                                <option value="prospect">Tiềm năng</option>
                                <option value="qualification">Xác minh</option>
                                <option value="proposal">Đề xuất</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-2"></i>Chuyển đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nhập khách hàng tiềm năng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn file Excel</label>
                        <input type="file" class="form-control" name="file" accept=".xls,.xlsx" required>
                        <div class="form-text">File phải có cột: full_name, email, phone, company_name, job_title,
                            source, priority</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Người phụ trách</label>
                        <select class="form-select" name="assigned_to" id="importAssignedTo">
                            <option value="">Tôi</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-link" onclick="downloadSampleExcel()">Tải file mẫu</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Nhập</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$inlineJS = '
let currentPage = 1;
let leadModal, convertModal;

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    leadModal = new bootstrap.Modal(document.getElementById("leadModal"));
    convertModal = new bootstrap.Modal(document.getElementById("convertModal"));
    
    loadLeads();
    loadStats();
    loadFilterOptions();
    
    // Search input
    document.getElementById("searchInput").addEventListener("input", debounce(function() {
        currentPage = 1;
        loadLeads();
    }, 300));
    
    // Filters
    ["statusFilter", "priorityFilter", "sourceFilter", "assignedFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", function() {
            currentPage = 1;
            loadLeads();
        });
    });
    
    // Forms
    document.getElementById("leadForm").addEventListener("submit", saveLead);
    document.getElementById("convertForm").addEventListener("submit", convertLead);
    document.getElementById("importForm").addEventListener("submit", importLeads);
    
    // Create deal checkbox
    document.getElementById("createDeal").addEventListener("change", function() {
        document.getElementById("dealFields").style.display = this.checked ? "block" : "none";
    });
    
    // Check URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("action") === "add") {
        openModal();
    }
});

function loadStats() {
    fetch(`${API_BASE_URL}/dashboard.php?action=leads-summary`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById("totalLeads").textContent = stats.total || 0;
                
                // Count new leads this month
                const newCount = (stats.by_status || []).find(s => s.status === "new")?.count || 0;
                document.getElementById("newLeads").textContent = newCount;
            }
        });
}

function loadLeads() {
    const params = new URLSearchParams({
        page: currentPage,
        per_page: 20
    });
    
    const search = document.getElementById("searchInput")?.value;
    const status = document.getElementById("statusFilter")?.value;
    const priority = document.getElementById("priorityFilter")?.value;
    const source = document.getElementById("sourceFilter")?.value;
    const assigned = document.getElementById("assignedFilter")?.value;
    
    if (search) params.append("search", search);
    if (status) params.append("status", status);
    if (priority) params.append("priority", priority);
    if (source) params.append("source", source);
    if (assigned) params.append("assigned_to", assigned);
    
    fetch(`${API_BASE_URL}/leads.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTable(data.data.data);
                renderPagination(data.data.pagination);
            }
        });
}

function renderTable(leads) {
    const tbody = document.getElementById("leadsTable");
    
    if (leads.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">Không có KH tiềm năng nào</td></tr>`;
        return;
    }
    
    tbody.innerHTML = leads.map(l => `
        <tr>
            <td>
                <div class="fw-bold">${l.full_name}</div>
                <small class="text-muted">${l.lead_code}</small>
            </td>
            <td>
                <div>${l.email || "-"}</div>
                <small class="text-muted">${l.phone || ""}</small>
            </td>
            <td>${l.company_name || "-"}</td>
            <td>${formatStatus(l.source) || "-"}</td>
            <td><span class="badge badge-${l.status}">${formatStatus(l.status)}</span></td>
            <td>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: ${l.score || 0}%"></div>
                </div>
                <small class="text-muted">${l.score || 0}/100</small>
            </td>
            <td><span class="badge badge-${l.priority}">${formatStatus(l.priority)}</span></td>
            <td>${l.assigned_to_name || "-"}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-success" onclick="openConvertModal(${l.id})" data-bs-toggle="tooltip" title="Chuyển đổi">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <button class="btn btn-outline-secondary" onclick="editLead(${l.id})" data-bs-toggle="tooltip" title="Sửa">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteLead(${l.id})" data-bs-toggle="tooltip" title="Xóa">
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
        loadLeads();
    });
}

function loadFilterOptions() {
    // Load users
    fetch(`${API_BASE_URL}/users.php?action=dropdown`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selects = ["assignedTo", "assignedFilter"];
                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        data.data.data.forEach(user => {
                            select.innerHTML += `<option value="${user.id}">${user.full_name}</option>`;
                        });
                    }
                });
            }
        });
}

function openModal() {
    document.getElementById("leadForm").reset();
    document.getElementById("leadId").value = "";
    document.getElementById("modalTitle").textContent = "Thêm Lead";
    leadModal.show();
}

function editLead(id) {
    fetch(`${API_BASE_URL}/leads.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const l = data.data;
                document.getElementById("leadId").value = l.id;
                document.getElementById("fullName").value = l.full_name;
                document.getElementById("jobTitle").value = l.job_title || "";
                document.getElementById("email").value = l.email || "";
                document.getElementById("phone").value = l.phone || "";
                document.getElementById("dob").value = l.dob || "";
                document.getElementById("companyName").value = l.company_name || "";
                document.getElementById("source").value = l.source || "";
                document.getElementById("address").value = l.address || "";
                document.getElementById("city").value = l.city || "";
                document.getElementById("website").value = l.website || "";
                document.getElementById("status").value = l.status;
                document.getElementById("priority").value = l.priority;
                document.getElementById("score").value = l.score || 0;
                document.getElementById("assignedTo").value = l.assigned_to || "";
                document.getElementById("notes").value = l.notes || "";
                
                document.getElementById("modalTitle").textContent = "Sửa Lead";
                leadModal.show();
            }
        });
}

function saveLead(e) {
    e.preventDefault();
    
    const id = document.getElementById("leadId").value;
    const data = {
        full_name: document.getElementById("fullName").value,
        job_title: document.getElementById("jobTitle").value,
        email: document.getElementById("email").value,
        phone: document.getElementById("phone").value,
        dob: document.getElementById("dob").value,
        company_name: document.getElementById("companyName").value,
        source: document.getElementById("source").value,
        address: document.getElementById("address").value,
        city: document.getElementById("city").value,
        website: document.getElementById("website").value,
        status: document.getElementById("status").value,
        priority: document.getElementById("priority").value,
        score: document.getElementById("score").value,
        assigned_to: document.getElementById("assignedTo").value,
        notes: document.getElementById("notes").value
    };
    
    if (id) data.id = id;
    
    fetch(`${API_BASE_URL}/leads.php`, {
        method: id ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert(id ? "Cập nhật thành công!" : "Thêm lead thành công!", "success");
            leadModal.hide();
            loadLeads();
            loadStats();
        } else {
            showAlert(result.message, "danger");
        }
    });
}

function openConvertModal(id) {
    document.getElementById("convertLeadId").value = id;
    document.getElementById("convertForm").reset();
    document.getElementById("dealFields").style.display = "block";
    convertModal.show();
}

function convertLead(e) {
    e.preventDefault();
    
    const leadId = document.getElementById("convertLeadId").value;
    const createDeal = document.getElementById("createDeal").checked;
    
    const data = {
        lead_id: leadId
    };
    
    if (createDeal) {
        data.create_deal = true;
        data.deal_title = document.getElementById("dealTitle").value;
        data.deal_value = document.getElementById("dealValue").value;
        data.deal_stage = document.getElementById("dealStage").value;
    }
    
    fetch(`${API_BASE_URL}/convert.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert("Chuyển đổi lead thành công!", "success");
            convertModal.hide();
            loadLeads();
            loadStats();
        } else {
            showAlert(result.message, "danger");
        }
    });
}

function deleteLead(id) {
    confirmDialog("Bạn có chắc chắn muốn xóa lead này?", function() {
        fetch(`${API_BASE_URL}/leads.php?id=${id}`, {
            method: "DELETE"
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert("Đã xóa lead!", "success");
                loadLeads();
                loadStats();
            } else {
                showAlert(result.message, "danger");
            }
        });
    });
}

function resetFilters() {
    document.getElementById("searchInput").value = "";
    document.getElementById("statusFilter").value = "";
    document.getElementById("priorityFilter").value = "";
    document.getElementById("sourceFilter").value = "";
    document.getElementById("assignedFilter").value = "";
    currentPage = 1;
    loadLeads();
}

function exportLeads() {
    window.location.href = API_BASE_URL + "/leads-export.php";
}

function importLeads(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append("type", "leads");
    
    fetch(API_BASE_URL + "/leads-import.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert("Import thành công: " + result.data.imported + " khách hàng tiềm năng", "success");
            bootstrap.Modal.getInstance(document.getElementById("importModal")).hide();
            loadLeads();
            loadStats();
        } else {
            showAlert(result.message, "danger");
        }
    })
    .catch(error => {
        console.error("Import error:", error);
        showAlert("Lỗi khi import: " + error.message, "danger");
    });
}

function downloadSampleExcel() {
    const csv = "full_name,email,phone,company_name,job_title,source,priority\nNguyen Van A,nguyenvana@email.com,0901234567,Cong ty A,Sales Manager,Website,high\nTran Thi B,tranthib@email.com,0912345678,Cong ty B,Marketing Director,Facebook,medium";
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "leads_sample.csv";
    a.click();
}
';

include 'components/footer.php';
?>