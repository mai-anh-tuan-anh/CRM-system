<?php
/**
 * Công việc
 * Giao diện quản lý công việc
 */
$pageTitle = 'Công việc - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Quản lý Công việc</h1>
            <p class="text-muted mb-0">Theo dõi công việc và hoạt động</p>
        </div>
        <a href="tasks.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Thêm công việc
        </a>
    </div>
    
    <!-- Thống kê công việc -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-white border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Chờ xử lý</div>
                            <h3 class="mb-0" id="pendingCount">0</h3>
                        </div>
                        <i class="bi bi-hourglass text-warning fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Đang làm</div>
                            <h3 class="mb-0" id="inProgressCount">0</h3>
                        </div>
                        <i class="bi bi-arrow-repeat text-primary fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Hoàn thành</div>
                            <h3 class="mb-0" id="completedCount">0</h3>
                        </div>
                        <i class="bi bi-check-circle text-success fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-left-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Quá hạn</div>
                            <h3 class="mb-0" id="overdueCount">0</h3>
                        </div>
                        <i class="bi bi-exclamation-triangle text-danger fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="taskTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-filter="all">Tất cả</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-filter="my">Của tôi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-filter="today">Hôm nay</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-filter="overdue">Quá hạn</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-filter="upcoming">Sắp tới</a>
        </li>
    </ul>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm công việc...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">Chờ xử lý</option>
                        <option value="in_progress">Đang thực hiện</option>
                        <option value="completed">Hoàn thành</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="typeFilter">
                        <option value="">Tất cả loại</option>
                        <option value="call">Gọi điện</option>
                        <option value="meeting">Họp</option>
                        <option value="email">Email</option>
                        <option value="follow_up">Theo dõi</option>
                        <option value="demo">Demo</option>
                        <option value="task">Công việc</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="priorityFilter">
                        <option value="">Tất cả ưu tiên</option>
                        <option value="low">Thấp</option>
                        <option value="medium">Trung bình</option>
                        <option value="high">Cao</option>
                        <option value="urgent">Khẩn cấp</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="assignedFilter">
                        <option value="">Tất cả NV</option>
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
    
    <!-- Bảng công việc -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-crm mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Công việc</th>
                            <th>Liên quan đến</th>
                            <th>Loại</th>
                            <th>Ưu tiên</th>
                            <th>Hạn</th>
                            <th>Người phụ trách</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="tasksTable">
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

<!-- Modal thêm/sửa công việc -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Thêm công việc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="taskForm">
                <div class="modal-body">
                    <input type="hidden" id="taskId">
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
                            <label class="form-label">Loại</label>
                            <select class="form-select" id="type">
                                <option value="task">Công việc</option>
                                <option value="call">Gọi điện</option>
                                <option value="meeting">Họp</option>
                                <option value="email">Email</option>
                                <option value="follow_up">Theo dõi</option>
                                <option value="demo">Demo</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ưu tiên</label>
                            <select class="form-select" id="priority">
                                <option value="low">Thấp</option>
                                <option value="medium" selected>Trung bình</option>
                                <option value="high">Cao</option>
                                <option value="urgent">Khẩn cấp</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Liên quan đến</label>
                            <select class="form-select" id="relatedType">
                                <option value="">Chọn loại</option>
                                <option value="customer">Khách hàng</option>
                                <option value="lead">Lead</option>
                                <option value="deal">Deal</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chọn</label>
                            <select class="form-select" id="relatedId" disabled>
                                <option value="">Chọn trước loại</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hạn</label>
                            <input type="datetime-local" class="form-control" id="dueDate">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Người phụ trách <span class="text-danger">*</span></label>
                            <select class="form-select" id="assignedTo" required>
                                <option value="">Chọn nhân viên</option>
                            </select>
                        </div>
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
let currentFilter = "all";
let taskModal;

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    taskModal = new bootstrap.Modal(document.getElementById("taskModal"));
    
    loadTasks();
    loadStats();
    loadFilterOptions();
    
    // Tabs
    document.getElementById("taskTabs").addEventListener("click", function(e) {
        if (e.target.classList.contains("nav-link")) {
            e.preventDefault();
            this.querySelectorAll(".nav-link").forEach(tab => tab.classList.remove("active"));
            e.target.classList.add("active");
            currentFilter = e.target.dataset.filter;
            currentPage = 1;
            loadTasks();
        }
    });
    
    // Search and filters
    document.getElementById("searchInput").addEventListener("input", debounce(function() {
        currentPage = 1;
        loadTasks();
    }, 300));
    
    ["statusFilter", "typeFilter", "priorityFilter", "assignedFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", function() {
            currentPage = 1;
            loadTasks();
        });
    });
    
    // Related entity selector
    document.getElementById("relatedType").addEventListener("change", function() {
        loadRelatedOptions(this.value);
    });
    
    document.getElementById("taskForm").addEventListener("submit", saveTask);
    
    // URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("action") === "add") {
        openModal();
    }
});

function loadStats() {
    fetch(`${API_BASE_URL}/dashboard.php?action=tasks-summary`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById("pendingCount").textContent = stats.pending || 0;
                document.getElementById("inProgressCount").textContent = stats.in_progress || 0;
                document.getElementById("completedCount").textContent = stats.completed || 0;
                document.getElementById("overdueCount").textContent = stats.overdue || 0;
            }
        });
}

function loadTasks() {
    const params = new URLSearchParams({
        page: currentPage,
        per_page: 20
    });
    
    // Apply tab filter
    if (currentFilter === "my") {
        // Will be filtered server-side by current user
    } else if (currentFilter === "today") {
        params.append("today", "1");
    } else if (currentFilter === "overdue") {
        params.append("overdue", "1");
    } else if (currentFilter === "upcoming") {
        params.append("upcoming", "1");
    }
    
    const search = document.getElementById("searchInput")?.value;
    const status = document.getElementById("statusFilter")?.value;
    const type = document.getElementById("typeFilter")?.value;
    const priority = document.getElementById("priorityFilter")?.value;
    const assigned = document.getElementById("assignedFilter")?.value;
    
    if (search) params.append("search", search);
    if (status) params.append("status", status);
    if (type) params.append("type", type);
    if (priority) params.append("priority", priority);
    if (assigned) params.append("assigned_to", assigned);
    
    fetch(`${API_BASE_URL}/tasks.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTable(data.data.data);
                renderPagination(data.data.pagination);
            }
        });
}

function renderTable(tasks) {
    const tbody = document.getElementById("tasksTable");
    
    if (tasks.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">Không có công việc nào</td></tr>`;
        return;
    }
    
    tbody.innerHTML = tasks.map(t => {
        const isOverdue = t.status !== "completed" && new Date(t.due_date) < new Date();
        const typeIcons = {
            call: "telephone",
            meeting: "people",
            email: "envelope",
            follow_up: "arrow-repeat",
            demo: "play-circle",
            task: "check2-square"
        };
        
        return `
        <tr class="${isOverdue ? "table-danger" : ""}">
            <td>
                ${t.status !== "completed" ? `
                <input type="checkbox" class="form-check-input" onchange="completeTask(${t.id})" title="Đánh dấu hoàn thành">
                ` : `<i class="bi bi-check-circle text-success"></i>`}
            </td>
            <td>
                <div class="fw-bold">${t.title}</div>
                <small class="text-muted">${truncateText(t.description, 50)}</small>
            </td>
            <td>${t.related_to_type} #${t.related_to_id}</td>
            <td><i class="bi bi-${typeIcons[t.type] || "circle"} me-1"></i>${t.type}</td>
            <td><span class="badge badge-${t.priority}">${t.priority}</span></td>
            <td>
                ${t.due_date ? `
                <span class="${isOverdue ? "text-danger fw-bold" : ""}">
                    <i class="bi bi-clock me-1"></i>${formatDate(t.due_date, "datetime")}
                    ${isOverdue ? "(Quá hạn)" : ""}
                </span>
                ` : "-"}
            </td>
            <td>${t.assigned_to_name || "-"}</td>
            <td><span class="badge badge-${t.status}">${t.status}</span></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" onclick="editTask(${t.id})" data-bs-toggle="tooltip" title="Sửa">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteTask(${t.id})" data-bs-toggle="tooltip" title="Xóa">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `}).join("");
    
    initTooltips();
}

function renderPagination(pagination) {
    const container = document.getElementById("pagination");
    container.innerHTML = createPagination(pagination.last_page, pagination.current_page, function(page) {
        currentPage = page;
        loadTasks();
    });
}

function loadFilterOptions() {
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
}

function loadRelatedOptions(type) {
    const select = document.getElementById("relatedId");
    select.innerHTML = "<option value=\"\">Chọn...</option>";
    select.disabled = !type;
    
    if (!type) return;
    
    let endpoint = "";
    if (type === "customer") endpoint = "customers.php";
    else if (type === "lead") endpoint = "leads.php";
    else if (type === "deal") endpoint = "deals.php";
    
    fetch(`${API_BASE_URL}/${endpoint}?per_page=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.data.data.forEach(item => {
                    const name = item.full_name || item.title || item.name || `#${item.id}`;
                    select.innerHTML += `<option value="${item.id}">${name}</option>`;
                });
            }
        });
}

function openModal() {
    document.getElementById("taskForm").reset();
    document.getElementById("taskId").value = "";
    document.getElementById("modalTitle").textContent = "Thêm công việc";
    taskModal.show();
}

function editTask(id) {
    fetch(`${API_BASE_URL}/tasks.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const t = data.data;
                document.getElementById("taskId").value = t.id;
                document.getElementById("title").value = t.title;
                document.getElementById("description").value = t.description || "";
                document.getElementById("type").value = t.type;
                document.getElementById("priority").value = t.priority;
                document.getElementById("relatedType").value = t.related_to_type;
                loadRelatedOptions(t.related_to_type);
                document.getElementById("relatedId").value = t.related_to_id;
                document.getElementById("dueDate").value = t.due_date ? t.due_date.slice(0, 16) : "";
                document.getElementById("assignedTo").value = t.assigned_to;
                
                document.getElementById("modalTitle").textContent = "Sửa công việc";
                taskModal.show();
            }
        });
}

function saveTask(e) {
    e.preventDefault();
    
    const id = document.getElementById("taskId").value;
    const data = {
        title: document.getElementById("title").value,
        description: document.getElementById("description").value,
        type: document.getElementById("type").value,
        priority: document.getElementById("priority").value,
        related_to_type: document.getElementById("relatedType").value,
        related_to_id: document.getElementById("relatedId").value,
        due_date: document.getElementById("dueDate").value,
        assigned_to: document.getElementById("assignedTo").value
    };
    
    if (id) data.id = id;
    
    fetch(`${API_BASE_URL}/tasks.php`, {
        method: id ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert(id ? "Cập nhật thành công!" : "Thêm công việc thành công!", "success");
            taskModal.hide();
            loadTasks();
            loadStats();
        } else {
            showAlert(result.message, "danger");
        }
    });
}

function completeTask(id) {
    fetch(`${API_BASE_URL}/tasks.php`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id, action: "complete" })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert("Đã hoàn thành công việc!", "success");
            loadTasks();
            loadStats();
        }
    });
}

function deleteTask(id) {
    confirmDialog("Bạn có chắc chắn muốn xóa công việc này?", function() {
        fetch(`${API_BASE_URL}/tasks.php?id=${id}`, {
            method: "DELETE"
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert("Đã xóa công việc!", "success");
                loadTasks();
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
    document.getElementById("typeFilter").value = "";
    document.getElementById("priorityFilter").value = "";
    document.getElementById("assignedFilter").value = "";
    currentPage = 1;
    currentFilter = "all";
    document.querySelectorAll("#taskTabs .nav-link").forEach(tab => tab.classList.remove("active"));
    document.querySelector("[data-filter=\"all\"]").classList.add("active");
    loadTasks();
}
';

include 'components/footer.php';
?>
