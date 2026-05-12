<?php
/**
 * Navbar Component
 * Top navigation bar for CRM
 */
?>

<!-- Navbar -->
<div class="navbar-crm">
    <!-- Left side - Toggle button for mobile -->
    <div class="d-flex align-items-center">
        <button class="btn btn-link d-md-none me-3" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>

    </div>

    <!-- Right side -->
    <div class="d-flex align-items-center gap-3">
        <!-- Thông báo -->
        <div class="dropdown">
            <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5 text-secondary"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    id="notificationCount" style="display: none;">
                    0
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                    <h6 class="mb-0">Thông báo</h6>
                    <button class="btn btn-sm btn-link" id="markAllRead">Đánh dấu đã đọc</button>
                </div>
                <div id="notificationList">
                    <div class="text-center py-3 text-muted">Không có thông báo</div>
                </div>
            </div>
        </div>

        <!-- Quick Add -->
        <div class="dropdown d-none d-md-block">
            <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-plus-lg"></i> Tạo mới
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="customers.php?action=add">
                    <i class="bi bi-person-plus me-2"></i>Khách hàng
                </a>
                <a class="dropdown-item" href="leads.php?action=add">
                    <i class="bi bi-bullseye me-2"></i>KH tiềm năng
                </a>
                <a class="dropdown-item" href="deals.php?action=add">
                    <i class="bi bi-briefcase me-2"></i>Thỏa thuận
                </a>
                <a class="dropdown-item" href="tasks.php?action=add">
                    <i class="bi bi-check2-square me-2"></i>Công việc
                </a>
            </div>
        </div>

        <!-- Hồ sơ người dùng -->
        <div class="dropdown">
            <button class="btn btn-link d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <img src="" alt="User" class="rounded-circle user-avatar" width="35" height="35">
                <div class="text-start d-none d-md-block">
                    <div class="fw-bold user-name" style="font-size: 0.875rem;">Người dùng</div>
                    <div class="text-muted user-role" style="font-size: 0.75rem;">Vai trò</div>
                </div>
                <i class="bi bi-chevron-down ms-1"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <div class="px-3 py-2 border-bottom">
                    <div class="fw-bold user-name">Người dùng</div>
                    <div class="text-muted small user-email">email@example.com</div>
                </div>
                <a class="dropdown-item" href="profile.php">
                    <i class="bi bi-person me-2"></i>Hồ sơ
                </a>
                <a class="dropdown-item admin-only" href="settings.php" style="display: none;">
                    <i class="bi bi-gear me-2"></i>Cài đặt
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="#" id="logoutBtn">
                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Load notifications
function loadNotifications() {
    fetch('/customer_management/backend/api/notifications.php?count=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.count;
                const badge = document.getElementById('notificationCount');
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
}

// Load recent notifications
function loadRecentNotifications() {
    fetch('/customer_management/backend/api/notifications.php?recent=1&limit=5')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('notificationList');
            if (data.success && data.data.length > 0) {
                list.innerHTML = data.data.map(notif => `
                    <div class="notification-item ${notif.is_read ? '' : 'unread'}" data-id="${notif.id}">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-${getNotifIcon(notif.type)} text-${notif.type} me-2"></i>
                            <div>
                                <div class="fw-bold small">${notif.title}</div>
                                <div class="small text-muted">${notif.message}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">${timeAgo(notif.created_at)}</div>
                            </div>
                        </div>
                    </div>
                `).join('');

                // Add click handlers
                list.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const id = this.dataset.id;
                        markAsRead(id);
                    });
                });
            } else {
                list.innerHTML = '<div class="text-center py-3 text-muted">Không có thông báo</div>';
            }
        });
}

function getNotifIcon(type) {
    const icons = {
        'info': 'info-circle',
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'error': 'x-circle'
    };
    return icons[type] || 'bell';
}

function markAsRead(id) {
    fetch('/customer_management/backend/api/notifications.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id
        })
    }).then(() => {
        loadNotifications();
        loadRecentNotifications();
    });
}

function markAllAsRead() {
    fetch('/customer_management/backend/api/notifications.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'mark_all_read'
        })
    }).then(() => {
        loadNotifications();
        loadRecentNotifications();
    });
}


function getTypeIcon(type) {
    const icons = {
        'customer': 'person',
        'lead': 'bullseye',
        'deal': 'briefcase',
        'task': 'check2-square'
    };
    return icons[type] || 'circle';
}


// Mark all read button
document.getElementById('markAllRead')?.addEventListener('click', markAllAsRead);

// Load notifications on page load
loadNotifications();
loadRecentNotifications();

// Refresh notifications every minute
setInterval(loadNotifications, 60000);

// Show admin-only items for admin users
fetch('/customer_management/backend/api/auth.php?action=me')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.role === 'admin') {
            document.querySelectorAll('.dropdown-item.admin-only').forEach(el => {
                el.style.display = 'block';
            });
        }
    });
</script>