<?php
/**
 * Sidebar Component
 * Thanh điều hướng cho CRM
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Helper function to check if menu item is active
function isActive($pages, $current) {
    if (is_array($pages)) {
        return in_array($current, $pages) ? 'active' : '';
    }
    return $current === $pages ? 'active' : '';
}
?>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <h4>Hệ thống CRM</h4>
    </div>
    
    <div class="nav-menu">
        <!-- Dashboard -->
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?= isActive('dashboard', $currentPage) ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Tổng quan</span>
            </a>
        </div>
        
        <!-- Customers -->
        <div class="nav-item">
            <a href="customers.php" class="nav-link <?= isActive('customers', $currentPage) ?>">
                <i class="bi bi-people"></i>
                <span>Khách hàng</span>
            </a>
        </div>
        
        <!-- Leads -->
        <div class="nav-item">
            <a href="leads.php" class="nav-link <?= isActive('leads', $currentPage) ?>">
                <i class="bi bi-bullseye"></i>
                <span>Leads</span>
            </a>
        </div>
        
        <!-- Deals -->
        <div class="nav-item">
            <a href="deals.php" class="nav-link <?= isActive(['deals', 'pipeline'], $currentPage) ?>">
                <i class="bi bi-briefcase"></i>
                <span>Thỏa thuận</span>
            </a>
        </div>
        
        <!-- Pipeline -->
        <div class="nav-item">
            <a href="pipeline.php" class="nav-link <?= isActive('pipeline', $currentPage) ?>">
                <i class="bi bi-kanban"></i>
                <span>Quy trình</span>
            </a>
        </div>
        
        <!-- Tasks -->
        <div class="nav-item">
            <a href="tasks.php" class="nav-link <?= isActive('tasks', $currentPage) ?>">
                <i class="bi bi-check2-square"></i>
                <span>Công việc</span>
            </a>
        </div>
        
        <!-- Divider -->
        <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
        
        <!-- Báo cáo -->
        <div class="nav-item">
            <a href="reports.php" class="nav-link <?= isActive('reports', $currentPage) ?>">
                <i class="bi bi-graph-up"></i>
                <span>Báo cáo</span>
            </a>
        </div>
        
        <!-- Settings (Admin only) -->
        <div class="nav-item admin-only" style="display: none;">
            <a href="settings.php" class="nav-link <?= isActive('settings', $currentPage) ?>">
                <i class="bi bi-gear"></i>
                <span>Cài đặt</span>
            </a>
        </div>
        
        <div class="nav-item admin-only" style="display: none;">
            <a href="users.php" class="nav-link <?= isActive('users', $currentPage) ?>">
                <i class="bi bi-person-gear"></i>
                <span>Quản lý người dùng</span>
            </a>
        </div>
    </div>
</nav>

<script>
// Show admin menu items for admin users
fetch('/customer_management/backend/api/auth.php?action=me')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.role === 'admin') {
            document.querySelectorAll('.admin-only').forEach(el => {
                el.style.display = 'block';
            });
        }
    });
</script>
