<?php
/**
 * Quản lý người dùng
 * Trang quản lý tài khoản người dùng
 */
$pageTitle = 'Quản lý người dùng - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Quản lý người dùng</h1>
            <p class="text-muted mb-0">Quản lý tài khoản và phân quyền</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="bi bi-person-plus me-2"></i>Thêm người dùng
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên, email...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="roleFilter">
                        <option value="">Tất cả vai trò</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="sales">Sales</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Vô hiệu hóa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="bi bi-x-lg me-1"></i>Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-crm mb-0">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <tr>
                            <td colspan="6" class="text-center py-4">
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

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Thêm người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="userId">
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" required>
                </div>
                <div class="mb-3" id="passwordField">
                    <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password">
                    <small class="text-muted" id="passwordHint">Để trống nếu không đổi mật khẩu</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="fullName" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Điện thoại</label>
                    <input type="text" class="form-control" id="phone">
                </div>
                <div class="mb-3">
                    <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" required>
                        <option value="sales">Sales</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="isActive" checked>
                        <label class="form-check-label" for="isActive">Đang hoạt động</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Lưu</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đặt lại mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="resetUserId">
                <p>Bạn đang đặt lại mật khẩu cho: <strong id="resetUserName"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="newPassword" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirmPassword" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-warning" onclick="confirmResetPassword()">Đặt lại mật khẩu</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="deleteUserId">
                <p>Bạn có chắc muốn xóa người dùng <strong id="deleteUserName"></strong>?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Hành động này không thể hoàn tác!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Xóa</button>
            </div>
        </div>
    </div>
</div>

<?php
$inlineJS = '
let currentPage = 1;
let userModal, resetPasswordModal, deleteModal;

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

document.addEventListener("DOMContentLoaded", function() {
    userModal = new bootstrap.Modal(document.getElementById("userModal"));
    resetPasswordModal = new bootstrap.Modal(document.getElementById("resetPasswordModal"));
    deleteModal = new bootstrap.Modal(document.getElementById("deleteModal"));
    
    loadUsers();
    
    // Search with debounce
    document.getElementById("searchInput")?.addEventListener("input", debounce(function() {
        currentPage = 1;
        loadUsers();
    }, 300));
    
    // Filters
    ["roleFilter", "statusFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", function() {
            currentPage = 1;
            loadUsers();
        });
    });
});

function loadUsers() {
    const params = new URLSearchParams({
        page: currentPage,
        per_page: 20
    });
    
    const search = document.getElementById("searchInput")?.value;
    const role = document.getElementById("roleFilter")?.value;
    const status = document.getElementById("statusFilter")?.value;
    
    if (search) params.append("search", search);
    if (role) params.append("role", role);
    if (status) params.append("is_active", status);
    
    fetch(`${API_BASE_URL}/users.php?${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log("Users data:", data.data.data); // Debug avatar
                renderTable(data.data.data);
                renderPagination(data.data.pagination);
            }
        });
}

function renderTable(users) {
    const tbody = document.getElementById("usersTable");
    
    if (users.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Không có người dùng nào</td></tr>`;
        return;
    }
    
    const roleLabels = {
        admin: { text: "Admin", class: "bg-danger" },
        manager: { text: "Manager", class: "bg-warning text-dark" },
        sales: { text: "Sales", class: "bg-info" }
    };
    
    tbody.innerHTML = users.map(u => {
        const role = roleLabels[u.role] || { text: u.role, class: "bg-secondary" };
        const statusBadge = u.is_active 
            ? `<span class="badge bg-success">Đang hoạt động</span>`
            : `<span class="badge bg-secondary">Vô hiệu hóa</span>`;
        
        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        ${renderAvatar(u.avatar, u.full_name || u.username, \'lg\', \'me-2\')}
                        <div>
                            <div class="fw-bold">${u.full_name || u.username}</div>
                            <small class="text-muted">@${u.username}</small>
                        </div>
                    </div>
                </td>
                <td>${u.email}</td>
                <td><span class="badge ${role.class}">${role.text}</span></td>
                <td>${statusBadge}</td>
                <td>${formatDate(u.created_at)}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(${u.id})" title="Sửa">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="openResetModal(${u.id}, \'${u.full_name || u.username}\')" title="Đặt lại mật khẩu">
                            <i class="bi bi-key"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="openDeleteModal(${u.id}, \'${u.full_name || u.username}\')" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function renderPagination(pagination) {
    const container = document.getElementById("pagination");
    container.innerHTML = createPagination(pagination.last_page, pagination.current_page, function(page) {
        currentPage = page;
        loadUsers();
    });
}

function resetFilters() {
    document.getElementById("searchInput").value = "";
    document.getElementById("roleFilter").value = "";
    document.getElementById("statusFilter").value = "";
    currentPage = 1;
    loadUsers();
}

// Modal functions
function openAddModal() {
    document.getElementById("modalTitle").textContent = "Thêm người dùng";
    document.getElementById("userId").value = "";
    document.getElementById("username").value = "";
    document.getElementById("email").value = "";
    document.getElementById("fullName").value = "";
    document.getElementById("phone").value = "";
    document.getElementById("role").value = "sales";
    document.getElementById("isActive").checked = true;
    
    document.getElementById("password").required = true;
    document.getElementById("passwordHint").style.display = "none";
    
    document.getElementById("username").disabled = false;
    
    userModal.show();
}

function openEditModal(userId) {
    fetch(`${API_BASE_URL}/users.php?id=${userId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const u = data.data;
                document.getElementById("modalTitle").textContent = "Sửa người dùng";
                document.getElementById("userId").value = u.id;
                document.getElementById("username").value = u.username;
                document.getElementById("email").value = u.email;
                document.getElementById("fullName").value = u.full_name;
                document.getElementById("phone").value = u.phone || "";
                document.getElementById("role").value = u.role;
                document.getElementById("isActive").checked = u.is_active == 1;
                
                document.getElementById("password").required = false;
                document.getElementById("password").value = "";
                document.getElementById("passwordHint").style.display = "block";
                
                document.getElementById("username").disabled = true;
                
                userModal.show();
            }
        });
}

function saveUser() {
    const userId = document.getElementById("userId").value;
    const isEdit = userId !== "";
    
    const data = {
        username: document.getElementById("username").value,
        email: document.getElementById("email").value,
        full_name: document.getElementById("fullName").value,
        phone: document.getElementById("phone").value,
        role: document.getElementById("role").value,
        is_active: document.getElementById("isActive").checked ? 1 : 0
    };
    
    if (!isEdit) {
        data.password = document.getElementById("password").value;
    } else {
        data.id = userId;
        const newPass = document.getElementById("password").value;
        if (newPass) data.password = newPass;
    }
    
    // Validate
    if (!data.username || !data.email || !data.full_name) {
        alert("Vui lòng điền đầy đủ thông tin bắt buộc");
        return;
    }
    
    if (!isEdit && !data.password) {
        alert("Vui lòng nhập mật khẩu");
        return;
    }
    
    const url = `${API_BASE_URL}/users.php`;
    const options = {
        method: isEdit ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    };
    
    fetch(url, options)
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                userModal.hide();
                loadUsers();
                alert(isEdit ? "Đã cập nhật thành công!" : "Đã thêm người dùng thành công!");
            } else {
                alert("Lỗi: " + (result.message || "Không thể lưu"));
            }
        });
}

function openResetModal(userId, userName) {
    document.getElementById("resetUserId").value = userId;
    document.getElementById("resetUserName").textContent = userName;
    document.getElementById("newPassword").value = "";
    document.getElementById("confirmPassword").value = "";
    resetPasswordModal.show();
}

function confirmResetPassword() {
    const userId = document.getElementById("resetUserId").value;
    const newPass = document.getElementById("newPassword").value;
    const confirmPass = document.getElementById("confirmPassword").value;
    
    if (!newPass) {
        alert("Vui lòng nhập mật khẩu mới");
        return;
    }
    
    if (newPass !== confirmPass) {
        alert("Mật khẩu xác nhận không khớp");
        return;
    }
    
    fetch(`${API_BASE_URL}/users.php`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: userId, password: newPass })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            resetPasswordModal.hide();
            alert("Đã đặt lại mật khẩu thành công!");
        } else {
            alert("Lỗi: " + (result.message || "Không thể đặt lại mật khẩu"));
        }
    });
}

function openDeleteModal(userId, userName) {
    document.getElementById("deleteUserId").value = userId;
    document.getElementById("deleteUserName").textContent = userName;
    deleteModal.show();
}

function confirmDelete() {
    const userId = document.getElementById("deleteUserId").value;
    
    fetch(`${API_BASE_URL}/users.php?id=${userId}`, {
        method: "DELETE"
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            deleteModal.hide();
            loadUsers();
            alert("Đã xóa người dùng thành công!");
        } else {
            alert("Lỗi: " + (result.message || "Không thể xóa"));
        }
    });
}
';

include 'components/footer.php';
?>