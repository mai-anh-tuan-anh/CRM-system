<?php
/**
 * Cài đặt
 * Trang cấu hình hệ thống
 */
$pageTitle = 'Cài đặt - Hệ thống CRM';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Cài đặt</h1>
            <p class="text-muted mb-0">Cấu hình hệ thống và thông tin công ty</p>
        </div>
        <button class="btn btn-primary" onclick="saveSettings()">
            <i class="bi bi-save me-2"></i>Lưu thay đổi
        </button>
    </div>

    <div class="row">
        <!-- Company Info -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Thông tin công ty</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tên công ty</label>
                        <input type="text" class="form-control" id="companyName" placeholder="Tên công ty">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email công ty</label>
                        <input type="email" class="form-control" id="companyEmail" placeholder="email@company.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Điện thoại</label>
                        <input type="text" class="form-control" id="companyPhone" placeholder="+84 xxx xxx xxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea class="form-control" id="companyAddress" rows="3" placeholder="Địa chỉ công ty"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branding -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-palette me-2"></i>Thương hiệu</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">URL Logo</label>
                        <input type="url" class="form-control" id="logoUrl" placeholder="https://example.com/logo.png">
                        <small class="text-muted">Nhập URL hình ảnh logo</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Favicon</label>
                        <input type="url" class="form-control" id="faviconUrl" placeholder="https://example.com/favicon.ico">
                        <small class="text-muted">Nhập URL favicon (.ico, .png)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xem trước Logo</label>
                        <div class="border rounded p-3 text-center bg-light">
                            <img id="logoPreview" src="" alt="Logo" style="max-height: 60px; display: none;">
                            <span id="noLogoText" class="text-muted">Chưa có logo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- CRM Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Cài đặt CRM</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Ngôn ngữ mặc định</label>
                        <select class="form-select" id="defaultLanguage">
                            <option value="vi">Tiếng Việt</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đơn vị tiền tệ</label>
                        <select class="form-select" id="defaultCurrency">
                            <option value="VND">VND (₫)</option>
                            <option value="USD">USD ($)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Múi giờ</label>
                        <select class="form-select" id="timezone">
                            <option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh (GMT+7)</option>
                            <option value="Asia/Bangkok">Asia/Bangkok (GMT+7)</option>
                            <option value="Asia/Singapore">Asia/Singapore (GMT+8)</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Định dạng ngày</label>
                        <select class="form-select" id="dateFormat">
                            <option value="d/m/Y">DD/MM/YYYY (31/12/2024)</option>
                            <option value="Y-m-d">YYYY-MM-DD (2024-12-31)</option>
                            <option value="m/d/Y">MM/DD/YYYY (12/31/2024)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>Cài đặt Email</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtpHost" placeholder="smtp.gmail.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="smtpPort" placeholder="587">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" id="smtpUsername" placeholder="email@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" id="smtpPassword" placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="smtpSecure">
                            <label class="form-check-label" for="smtpSecure">Sử dụng TLS/SSL</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Phân quyền mặc định</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Chức năng</th>
                            <th class="text-center">Admin</th>
                            <th class="text-center">Manager</th>
                            <th class="text-center">Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Xem tất cả khách hàng</td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Chỉnh sửa tất cả khách hàng</td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i> <small>(chỉ của mình)</small></td>
                        </tr>
                        <tr>
                            <td>Xóa khách hàng</td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
                        </tr>
                        <tr>
                            <td>Xem báo cáo</td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
                        </tr>
                        <tr>
                            <td>Quản lý người dùng</td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i> <small>(xem)</small></td>
                            <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
                        </tr>
                        <tr>
                            <td>Cài đặt hệ thống</td>
                            <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                            <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
                            <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small"><i class="bi bi-info-circle me-1"></i>Lưu ý: Bảng phân quyền này chỉ mang tính tham khảo. Để thay đổi quyền chi tiết, vui lòng liên hệ developer.</p>
        </div>
    </div>
</div>

<?php
$inlineJS = '
// Load settings on page load
document.addEventListener("DOMContentLoaded", function() {
    loadSettings();
});

function loadSettings() {
    // Load company info
    fetch(`${API_BASE_URL}/settings.php?group=company`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.data;
                document.getElementById("companyName").value = s.company_name || "";
                document.getElementById("companyEmail").value = s.company_email || "";
                document.getElementById("companyPhone").value = s.company_phone || "";
                document.getElementById("companyAddress").value = s.company_address || "";
            }
        });
    
    // Load CRM settings
    fetch(`${API_BASE_URL}/settings.php?group=crm`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.data;
                document.getElementById("defaultLanguage").value = s.default_language || "vi";
                document.getElementById("defaultCurrency").value = s.default_currency || "VND";
                document.getElementById("timezone").value = s.timezone || "Asia/Ho_Chi_Minh";
                document.getElementById("dateFormat").value = s.date_format || "d/m/Y";
                document.getElementById("logoUrl").value = s.logo_url || "";
                document.getElementById("faviconUrl").value = s.favicon_url || "";
                
                // Update logo preview
                if (s.logo_url) {
                    document.getElementById("logoPreview").src = s.logo_url;
                    document.getElementById("logoPreview").style.display = "block";
                    document.getElementById("noLogoText").style.display = "none";
                }
            }
        });
    
    // Load email settings
    fetch(`${API_BASE_URL}/settings.php?group=email`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.data;
                document.getElementById("smtpHost").value = s.smtp_host || "";
                document.getElementById("smtpPort").value = s.smtp_port || "";
                document.getElementById("smtpUsername").value = s.smtp_username || "";
                document.getElementById("smtpPassword").value = s.smtp_password || "";
                document.getElementById("smtpSecure").checked = s.smtp_secure === "1" || s.smtp_secure === true;
            }
        });
}

// Preview logo when URL changes
document.getElementById("logoUrl")?.addEventListener("input", function() {
    const url = this.value;
    const preview = document.getElementById("logoPreview");
    const noText = document.getElementById("noLogoText");
    
    if (url) {
        preview.src = url;
        preview.style.display = "block";
        preview.onload = function() { noText.style.display = "none"; };
        preview.onerror = function() {
            preview.style.display = "none";
            noText.style.display = "block";
            noText.textContent = "Không thể tải logo";
        };
    } else {
        preview.style.display = "none";
        noText.style.display = "block";
        noText.textContent = "Chưa có logo";
    }
});

function saveSettings() {
    const settings = {
        // Company
        company_name: document.getElementById("companyName").value,
        company_email: document.getElementById("companyEmail").value,
        company_phone: document.getElementById("companyPhone").value,
        company_address: document.getElementById("companyAddress").value,
        
        // Branding
        logo_url: document.getElementById("logoUrl").value,
        favicon_url: document.getElementById("faviconUrl").value,
        
        // CRM
        default_language: document.getElementById("defaultLanguage").value,
        default_currency: document.getElementById("defaultCurrency").value,
        timezone: document.getElementById("timezone").value,
        date_format: document.getElementById("dateFormat").value,
        
        // Email
        smtp_host: document.getElementById("smtpHost").value,
        smtp_port: document.getElementById("smtpPort").value,
        smtp_username: document.getElementById("smtpUsername").value,
        smtp_password: document.getElementById("smtpPassword").value,
        smtp_secure: document.getElementById("smtpSecure").checked ? "1" : "0"
    };
    
    fetch(`${API_BASE_URL}/settings.php`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ settings: settings })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert("Đã lưu cài đặt thành công!");
        } else {
            alert("Lỗi: " + (data.message || "Không thể lưu cài đặt"));
        }
    })
    .catch(err => {
        alert("Lỗi kết nối: " + err.message);
    });
}
';

include 'components/footer.php';
?>
