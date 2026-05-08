/**
 * CRM System Main JavaScript
 * Core functionality for Customer Management CRM
 */

// API_BASE_URL và FRONTEND_URL được định nghĩa trong footer.php
// Không cần khai báo lại ở đây để tránh lỗi redeclare

// Current user info
let currentUser = null;

/**
 * Initialize application
 */
document.addEventListener('DOMContentLoaded', function () {
    // Check authentication
    checkAuth();

    // Initialize tooltips
    initTooltips();

    // Initialize logout handler
    initLogout();
});

/**
 * Check authentication status
 */
function checkAuth() {
    fetch(`${API_BASE_URL}/auth.php?action=check`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.data.authenticated) {
                currentUser = data.data.user;
                updateUserInterface();

                // If on login page, redirect to dashboard
                if (window.location.pathname.includes('login.php')) {
                    window.location.href = `${FRONTEND_URL}/dashboard.php`;
                }
            } else {
                // Not authenticated - redirect to login if not already there
                if (!window.location.pathname.includes('login.php')) {
                    window.location.href = `${FRONTEND_URL}/login.php`;
                }
            }
        })
        .catch((error) => {
            console.error('Kiểm tra xác thực thất bại:', error);
        });
}

/**
 * Update UI with user info
 */
function updateUserInterface() {
    if (!currentUser) return;

    // Update user name
    const userNameElements = document.querySelectorAll('.user-name');
    userNameElements.forEach((el) => {
        el.textContent = currentUser.full_name || currentUser.username;
    });

    // Update user role
    const userRoleElements = document.querySelectorAll('.user-role');
    userRoleElements.forEach((el) => {
        el.textContent = formatRole(currentUser.role);
    });

    // Update avatar
    const avatarElements = document.querySelectorAll('.user-avatar');
    avatarElements.forEach((el) => {
        if (currentUser.avatar) {
            el.src = currentUser.avatar;
        } else {
            el.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(currentUser.full_name || currentUser.username)}&background=4e73df&color=fff`;
        }
    });

    // Update user email
    const userEmailElements = document.querySelectorAll('.user-email');
    userEmailElements.forEach((el) => {
        el.textContent = currentUser.email || 'email@example.com';
    });
}

/**
 * Format role for display
 */
function formatRole(role) {
    const roles = {
        admin: 'Administrator',
        manager: 'Manager',
        sales: 'Sales Representative'
    };
    return roles[role] || role;
}

/**
 * Render avatar HTML with fallback to initials
 * @param {string} avatar - Avatar URL or null
 * @param {string} name - Full name for fallback initials
 * @param {string} size - Size class (sm, md, lg)
 * @param {string} extraClass - Additional CSS classes
 * @returns {string} HTML string for avatar
 */
function renderAvatar(avatar, name, size = 'md', extraClass = '') {
    const initials = (name || 'U').charAt(0).toUpperCase();
    const sizeStyles = {
        sm: 'width:24px;height:24px;font-size:0.75rem;',
        md: 'width:32px;height:32px;font-size:0.875rem;',
        lg: 'width:40px;height:40px;font-size:1rem;'
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

/**
 * Initialize Bootstrap tooltips
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    );
    tooltipTriggerList.forEach((el) => new bootstrap.Tooltip(el));
}

/**
 * Initialize logout handler
 */
function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            logout();
        });
    }
}

/**
 * Logout function
 */
function logout() {
    fetch(`${API_BASE_URL}/auth.php?action=logout`, {
        method: 'POST'
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                window.location.href = `${FRONTEND_URL}/login.php`;
            }
        })
        .catch((error) => {
            console.error('Đăng xuất thất bại:', error);
        });
}

/**
 * Make API request
 */
function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    return fetch(`${API_BASE_URL}/${endpoint}`, options)
        .then((response) => response.json())
        .then((data) => {
            if (!data.success) {
                throw new Error(data.message || 'Yêu cầu thất bại');
            }
            return data;
        });
}

/**
 * Hiển thị thông báo
 */
function showAlert(message, type = 'success', container = null) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    if (container) {
        container.prepend(alertDiv);
    } else {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.prepend(alertDiv);
        }
    }

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'VND') {
    if (currency === 'VND') {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(dateString, format = 'short') {
    if (!dateString) return '-';
    const date = new Date(dateString);

    if (format === 'short') {
        return date.toLocaleDateString('vi-VN');
    } else if (format === 'long') {
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } else if (format === 'datetime') {
        return date.toLocaleString('vi-VN');
    }
    return dateString;
}

/**
 * Time ago function
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + ' năm trước';

    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + ' tháng trước';

    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + ' ngày trước';

    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + ' giờ trước';

    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + ' phút trước';

    return 'Vừa xong';
}

/**
 * Get badge class for status
 */
function getStatusBadgeClass(status, type = 'status') {
    const classes = {
        // Lead/Customer status
        new: 'badge-new',
        contacted: 'badge-contacted',
        qualified: 'badge-qualified',
        converted: 'badge-converted',
        lost: 'badge-lost',
        active: 'badge-qualified',
        inactive: 'badge-lost',

        // Deal stages
        prospect: 'badge-prospect',
        qualification: 'badge-qualification',
        proposal: 'badge-proposal',
        negotiation: 'badge-negotiation',
        won: 'badge-won',

        // Task status
        pending: 'badge-pending',
        in_progress: 'badge-prospect',
        completed: 'badge-won',
        cancelled: 'badge-lost',

        // Priority
        low: 'badge-low',
        medium: 'badge-medium',
        high: 'badge-high',
        urgent: 'badge-urgent'
    };

    return classes[status] || 'badge-secondary';
}

/**
 * Format status/stage for display (Vietnamese)
 */
function formatStatus(status, type = 'status') {
    const labels = {
        // Lead/Customer status
        new: 'Mới',
        contacted: 'Đã liên hệ',
        qualified: 'Đủ điều kiện',
        converted: 'Đã chuyển đổi',
        lost: 'Mất',
        active: 'Hoạt động',
        inactive: 'Không hoạt động',

        // Deal stages
        prospect: 'Tiềm năng',
        qualification: 'Xác minh',
        proposal: 'Đề xuất',
        negotiation: 'Thương lượng',
        won: 'Thành công',
        lost: 'Thất bại',

        // Task status
        pending: 'Chờ xử lý',
        in_progress: 'Đang thực hiện',
        completed: 'Hoàn thành',
        cancelled: 'Đã hủy',

        // Priority
        low: 'Thấp',
        medium: 'Trung bình',
        high: 'Cao',
        urgent: 'Khẩn cấp',

        // Task types
        call: 'Gọi điện',
        meeting: 'Họp',
        email: 'Email',
        follow_up: 'Theo dõi',
        demo: 'Demo',
        task: 'Công việc',

        // Source
        website: 'Website',
        referral: 'Giới thiệu',
        social_media: 'Mạng xã hội',
        cold_call: 'Gọi lạnh',
        event: 'Sự kiện',
        phone: 'Điện thoại',
        email: 'Email',
        other: 'Khác',

        // Common
        yes: 'Có',
        no: 'Không',
        and: 'và',
        or: 'hoặc',

        // Page info
        lead: 'KH tiềm năng',
        customer: 'Khách hàng',
        deal: 'Thỏa thuận'
    };

    // Convert to lowercase and handle snake_case conversion
    const key = status?.toString().toLowerCase().replace(/ /g, '_');
    return labels[key] || status;
}

/**
 * Format task type for display
 */
function formatType(type) {
    const types = {
        call: 'Gọi điện',
        meeting: 'Họp',
        email: 'Email',
        follow_up: 'Theo dõi',
        demo: 'Demo',
        task: 'Công việc'
    };

    const key = type?.toString().toLowerCase().replace(/ /g, '_');
    return types[key] || type;
}

/**
 * Truncate text
 */
function truncateText(text, maxLength = 50) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

/**
 * Pagination component
 */
function createPagination(totalPages, currentPage, onPageChange) {
    let html =
        '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous button
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">Trước</a>
        </li>
    `;

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (startPage > 2)
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1)
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    // Next button
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">Sau</a>
        </li>
    `;

    html += '</ul></nav>';

    // Add click handlers
    setTimeout(() => {
        document.querySelectorAll('.page-link').forEach((link) => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page && page !== currentPage) {
                    onPageChange(page);
                }
            });
        });
    }, 0);

    return html;
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Hộp thoại xác nhận
 */
function confirmDialog(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Hiển thị spinner đang tải
 */
function showLoading(container) {
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải...</p>
        </div>
    `;
}

/**
 * Ẩn spinner đang tải
 */
function hideLoading(container) {
    const spinner = container.querySelector('.spinner-border');
    if (spinner) {
        spinner.parentElement.remove();
    }
}

/**
 * Capitalize first letter
 */
function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard
        .writeText(text)
        .then(() => {
            showAlert('Đã sao chép vào clipboard!', 'success');
        })
        .catch((err) => {
            console.error('Sao chép thất bại:', err);
        });
}

// Export functions for use in other scripts
window.API_BASE_URL = API_BASE_URL;
window.FRONTEND_URL = FRONTEND_URL;
window.apiRequest = apiRequest;
window.showAlert = showAlert;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.timeAgo = timeAgo;
window.getStatusBadgeClass = getStatusBadgeClass;
window.formatStatus = formatStatus;
window.formatType = formatType;
window.truncateText = truncateText;
window.createPagination = createPagination;
window.debounce = debounce;
window.confirmDialog = confirmDialog;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.capitalizeFirst = capitalizeFirst;
window.copyToClipboard = copyToClipboard;
