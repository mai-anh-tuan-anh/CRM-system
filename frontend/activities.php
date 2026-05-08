<?php
/**
 * Hoạt động
 * Trang xem danh sách hoạt động
 */
$pageTitle = 'Hoạt động - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Hoạt động</h1>
            <p class="text-muted mb-0">Lịch sử hoạt động và tương tác</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm mô tả...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="typeFilter">
                        <option value="">Tất cả loại</option>
                        <option value="call">Gọi điện</option>
                        <option value="email">Email</option>
                        <option value="meeting">Họp</option>
                        <option value="note">Ghi chú</option>
                        <option value="status_change">Thay đổi trạng thái</option>
                        <option value="file_upload">Tải file</option>
                        <option value="deal_created">Tạo thỏa thuận</option>
                        <option value="lead_converted">Chuyển đổi lead</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="dateFromFilter" placeholder="Từ ngày">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="dateToFilter" placeholder="Đến ngày">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="performedByFilter">
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

    <!-- Activities Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-crm mb-0">
                    <thead>
                        <tr>
                            <th>Hoạt động</th>
                            <th>Loại</th>
                            <th>Liên quan đến</th>
                            <th>Người thực hiện</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesTable">
                        <tr>
                            <td colspan="5" class="text-center py-4">
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

<?php
$inlineJS = '
let currentPage = 1;

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
    loadActivities();
    loadUsers();

    // Search with debounce
    document.getElementById("searchInput").addEventListener("input", debounce(function() {
        currentPage = 1;
        loadActivities();
    }, 300));

    // Filters
    ["typeFilter", "dateFromFilter", "dateToFilter", "performedByFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", function() {
            currentPage = 1;
            loadActivities();
        });
    });
});

function loadActivities() {
    const params = new URLSearchParams({
        page: currentPage,
        per_page: 20
    });

    const search = document.getElementById("searchInput")?.value;
    const type = document.getElementById("typeFilter")?.value;
    const dateFrom = document.getElementById("dateFromFilter")?.value;
    const dateTo = document.getElementById("dateToFilter")?.value;
    const performedBy = document.getElementById("performedByFilter")?.value;

    if (search) params.append("search", search);
    if (type) params.append("activity_type", type);
    if (dateFrom) params.append("date_from", dateFrom);
    if (dateTo) params.append("date_to", dateTo);
    if (performedBy) params.append("performed_by", performedBy);

    fetch(`${API_BASE_URL}/activities.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTable(data.data.data);
                renderPagination(data.data.pagination);
            }
        });
}

function renderTable(activities) {
    const tbody = document.getElementById("activitiesTable");

    if (activities.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Không có hoạt động nào</td></tr>`;
        return;
    }

    const typeLabels = {
        call: "Gọi điện",
        email: "Email",
        meeting: "Họp",
        note: "Ghi chú",
        status_change: "Thay đổi trạng thái",
        file_upload: "Tải file",
        deal_created: "Tạo thỏa thuận",
        lead_converted: "Chuyển đổi lead"
    };

    const typeIcons = {
        call: "telephone",
        email: "envelope",
        meeting: "people",
        note: "sticky",
        status_change: "arrow-repeat",
        file_upload: "upload",
        deal_created: "briefcase",
        lead_converted: "arrow-right-circle"
    };

    const relatedLabels = {
        customer: "Khách hàng",
        lead: "KH tiềm năng",
        deal: "Thỏa thuận",
        task: "Công việc",
        user: "Người dùng"
    };

    tbody.innerHTML = activities.map(a => `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="activity-icon me-3 bg-light rounded-circle p-2">
                        <i class="bi bi-${typeIcons[a.activity_type] || "circle"} text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold">${a.description}</div>
                        ${a.metadata ? `<small class="text-muted">${formatMetadata(a.metadata)}</small>` : ""}
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-light text-dark">${typeLabels[a.activity_type] || a.activity_type}</span>
            </td>
            <td>
                ${relatedLabels[a.related_to_type] || a.related_to_type} #${a.related_to_id}
            </td>
            <td>
                <div class="d-flex align-items-center">
                    ${renderAvatar(a.performed_by_avatar, a.performed_by_name, \'md\', \'me-2\')}
                    <span>${a.performed_by_name || "-"}</span>
                </div>
            </td>
            <td>
                <div class="text-muted">${formatDate(a.performed_at, "datetime")}</div>
                <small class="text-muted">${timeAgo(a.performed_at)}</small>
            </td>
        </tr>
    `).join("");
}

function formatMetadata(metadata) {
    if (!metadata) return "";
    try {
        const data = typeof metadata === "string" ? JSON.parse(metadata) : metadata;
        const parts = [];
        if (data.duration) parts.push(`${data.duration} phút`);
        if (data.outcome) parts.push(data.outcome);
        if (data.location) parts.push(`📍 ${data.location}`);
        if (data.from && data.to) parts.push(`${data.from} → ${data.to}`);
        return parts.join(" • ");
    } catch (e) {
        return "";
    }
}

function renderPagination(pagination) {
    const container = document.getElementById("pagination");
    container.innerHTML = createPagination(pagination.last_page, pagination.current_page, function(page) {
        currentPage = page;
        loadActivities();
    });
}

function loadUsers() {
    fetch(`${API_BASE_URL}/users.php?action=dropdown`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById("performedByFilter");
                data.data.data.forEach(u => {
                    select.innerHTML += `<option value="${u.id}">${u.full_name}</option>`;
                });
            }
        });
}

function resetFilters() {
    document.getElementById("searchInput").value = "";
    document.getElementById("typeFilter").value = "";
    document.getElementById("dateFromFilter").value = "";
    document.getElementById("dateToFilter").value = "";
    document.getElementById("performedByFilter").value = "";
    currentPage = 1;
    loadActivities();
}
';

include 'components/footer.php';
?>