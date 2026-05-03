<?php
/**
 * Khách hàng
 * Giao diện quản lý khách hàng
 */
$pageTitle = 'Khách hàng - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Quản lý khách hàng</h1>
            <p class="text-muted mb-0">Danh sách khách hàng và thông tin chi tiết</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-2"></i>Nhập
            </button>
            <button class="btn btn-outline-secondary" onclick="exportCustomers()">
                <i class="bi bi-download me-2"></i>Xuất
            </button>
            <a href="customers.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Thêm khách hàng
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm khách hàng...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                        <option value="prospect">Tiềm năng</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="industryFilter">
                        <option value="">Tất cả ngành</option>
                        <!-- Dynamic options -->
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sourceFilter">
                        <option value="">Tất cả nguồn</option>
                        <!-- Dynamic options -->
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="assignedFilter">
                        <option value="">Tất cả nhân viên</option>
                        <!-- Dynamic options -->
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
    
    <!-- Bảng khách hàng -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-crm mb-0">
                    <thead>
                        <tr>
                            <th>Mã KH</th>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Công ty</th>
                            <th>Nguồn</th>
                            <th>Trạng thái</th>
                            <th>Người phụ trách</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="customersTable">
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

<!-- Modal thêm/sửa khách hàng -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Thêm khách hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm">
                <div class="modal-body">
                    <input type="hidden" id="customerId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fullName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Công ty</label>
                            <input type="text" class="form-control" id="companyName">
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
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" id="address">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Thành phố</label>
                            <input type="text" class="form-control" id="city">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngành nghề</label>
                            <select class="form-select" id="industry">
                                <option value="">Chọn ngành</option>
                                <option value="Technology">Công nghệ</option>
                                <option value="Finance">Tài chính</option>
                                <option value="Manufacturing">Sản xuất</option>
                                <option value="Retail">Bán lẻ</option>
                                <option value="Healthcare">Y tế</option>
                                <option value="Education">Giáo dục</option>
                                <option value="Other">Khác</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nguồn</label>
                            <select class="form-select" id="source">
                                <option value="">Chọn nguồn</option>
                                <option value="Website">Website</option>
                                <option value="Social Media">Mạng xã hội</option>
                                <option value="Referral">Giới thiệu</option>
                                <option value="Email">Email</option>
                                <option value="Phone">Điện thoại</option>
                                <option value="Event">Sự kiện</option>
                                <option value="Other">Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" id="website">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" id="status">
                                <option value="prospect">Tiềm năng</option>
                                <option value="active">Hoạt động</option>
                                <option value="inactive">Không hoạt động</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
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

<!-- Modal nhập liệu -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nhập khách hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn file CSV</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <div class="form-text">File phải có cột: full_name, email, phone, company_name</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Người phụ trách</label>
                        <select class="form-select" name="assigned_to" id="importAssignedTo">
                            <option value="">Tôi</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trạng thái mặc định</label>
                        <select class="form-select" name="default_status">
                            <option value="prospect">Tiềm năng</option>
                            <option value="active">Hoạt động</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-link" onclick="downloadSampleCSV()">Tải file mẫu</a>
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
let customerModal = null;

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    customerModal = new bootstrap.Modal(document.getElementById("customerModal"));
    loadCustomers();
    loadFilterOptions();
    
    // Search input with debounce
    document.getElementById("searchInput").addEventListener("input", debounce(function() {
        currentPage = 1;
        loadCustomers();
    }, 300));
    
    // Filter changes
    ["statusFilter", "industryFilter", "sourceFilter", "assignedFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", function() {
            currentPage = 1;
            loadCustomers();
        });
    });
    
    // Form submit
    document.getElementById("customerForm").addEventListener("submit", saveCustomer);
    document.getElementById("importForm").addEventListener("submit", importCustomers);
    
    // Check URL params for action
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("action") === "add") {
        openModal();
    }
});

function loadCustomers() {
    const params = new URLSearchParams({
        page: currentPage,
        per_page: 20
    });
    
    const search = document.getElementById("searchInput")?.value;
    const status = document.getElementById("statusFilter")?.value;
    const industry = document.getElementById("industryFilter")?.value;
    const source = document.getElementById("sourceFilter")?.value;
    const assigned = document.getElementById("assignedFilter")?.value;
    
    if (search) params.append("search", search);
    if (status) params.append("status", status);
    if (industry) params.append("industry", industry);
    if (source) params.append("source", source);
    if (assigned) params.append("assigned_to", assigned);
    
    fetch(`${API_BASE_URL}/customers.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTable(data.data.data);
                renderPagination(data.data.pagination);
            }
        });
}

function renderTable(customers) {
    const tbody = document.getElementById("customersTable");
    
    if (customers.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">Không có khách hàng nào</td></tr>`;
        return;
    }
    
    tbody.innerHTML = customers.map(c => `
        <tr>
            <td><code>${c.customer_code}</code></td>
            <td>
                <div class="fw-bold">${c.full_name}</div>
                <small class="text-muted">${c.email || ""}</small>
            </td>
            <td>${c.phone || "-"}</td>
            <td>${c.company_name || "-"}</td>
            <td>${c.source || "-"}</td>
            <td><span class="badge badge-${c.status}">${c.status}</span></td>
            <td>${c.assigned_to_name || "-"}</td>
            <td>${formatDate(c.created_at)}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <a href="customer_detail.php?id=${c.id}" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Xem chi tiết">
                        <i class="bi bi-eye"></i>
                    </a>
                    <button class="btn btn-outline-secondary" onclick="editCustomer(${c.id})" data-bs-toggle="tooltip" title="Sửa">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteCustomer(${c.id})" data-bs-toggle="tooltip" title="Xóa">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join("");
    
    // Re-initialize tooltips
    initTooltips();
}

function renderPagination(pagination) {
    const container = document.getElementById("pagination");
    container.innerHTML = createPagination(pagination.last_page, pagination.current_page, function(page) {
        currentPage = page;
        loadCustomers();
    });
}

function loadFilterOptions() {
    // Load industries
    fetch(`${API_BASE_URL}/customers.php?action=industries`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("industryFilter");
                data.data.forEach(industry => {
                    select.innerHTML += `<option value="${industry}">${industry}</option>`;
                });
            }
        });
    
    // Load users for assignment
    fetch(`${API_BASE_URL}/users.php?role=sales`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selects = ["assignedTo", "assignedFilter", "importAssignedTo"];
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
    document.getElementById("customerForm").reset();
    document.getElementById("customerId").value = "";
    document.getElementById("modalTitle").textContent = "Thêm khách hàng";
    customerModal.show();
}

function editCustomer(id) {
    fetch(`${API_BASE_URL}/customers.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const c = data.data;
                document.getElementById("customerId").value = c.id;
                document.getElementById("fullName").value = c.full_name;
                document.getElementById("companyName").value = c.company_name || "";
                document.getElementById("email").value = c.email || "";
                document.getElementById("phone").value = c.phone || "";
                document.getElementById("address").value = c.address || "";
                document.getElementById("city").value = c.city || "";
                document.getElementById("industry").value = c.industry || "";
                document.getElementById("source").value = c.source || "";
                document.getElementById("website").value = c.website || "";
                document.getElementById("status").value = c.status;
                document.getElementById("assignedTo").value = c.assigned_to || "";
                document.getElementById("notes").value = c.notes || "";
                
                document.getElementById("modalTitle").textContent = "Sửa khách hàng";
                customerModal.show();
            }
        });
}

function saveCustomer(e) {
    e.preventDefault();
    
    const id = document.getElementById("customerId").value;
    const data = {
        full_name: document.getElementById("fullName").value,
        company_name: document.getElementById("companyName").value,
        email: document.getElementById("email").value,
        phone: document.getElementById("phone").value,
        address: document.getElementById("address").value,
        city: document.getElementById("city").value,
        industry: document.getElementById("industry").value,
        source: document.getElementById("source").value,
        website: document.getElementById("website").value,
        status: document.getElementById("status").value,
        assigned_to: document.getElementById("assignedTo").value,
        notes: document.getElementById("notes").value
    };
    
    if (id) data.id = id;
    
    fetch(`${API_BASE_URL}/customers.php`, {
        method: id ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert(id ? "Cập nhật thành công!" : "Thêm khách hàng thành công!", "success");
            customerModal.hide();
            loadCustomers();
        } else {
            showAlert(result.message, "danger");
        }
    });
}

function deleteCustomer(id) {
    confirmDialog("Bạn có chắc chắn muốn xóa khách hàng này?", function() {
        fetch(`${API_BASE_URL}/customers.php?id=${id}`, {
            method: "DELETE"
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert("Đã xóa khách hàng!", "success");
                loadCustomers();
            } else {
                showAlert(result.message, "danger");
            }
        });
    });
}

function resetFilters() {
    document.getElementById("searchInput").value = "";
    document.getElementById("statusFilter").value = "";
    document.getElementById("industryFilter").value = "";
    document.getElementById("sourceFilter").value = "";
    document.getElementById("assignedFilter").value = "";
    currentPage = 1;
    loadCustomers();
}

function exportCustomers() {
    window.location.href = `${API_BASE_URL}/export.php?type=customers`;
}

function importCustomers(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append("type", "customers");
    
    fetch(`${API_BASE_URL}/import.php`, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert(`Import thành công: ${result.data.imported} khách hàng`, "success");
            bootstrap.Modal.getInstance(document.getElementById("importModal")).hide();
            loadCustomers();
        } else {
            showAlert(result.message, "danger");
        }
    });
}

function downloadSampleCSV() {
    const csv = "full_name,email,phone,company_name,address,city,industry,source\nNguyen Van A,nguyenvana@email.com,0901234567,Cong ty A,123 Le Loi,TP.HCM,Technology,Website";
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "customers_sample.csv";
    a.click();
}
';

include 'components/footer.php';
?>
