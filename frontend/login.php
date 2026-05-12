<?php
/**
 * Đăng nhập
 * Xác thực người dùng
 */
$pageTitle = 'Đăng nhập - Hệ thống CRM';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Nunito', sans-serif;
    }

    .login-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 40px;
        width: 100%;
        max-width: 450px;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 30px;
    }

    .login-logo i {
        font-size: 60px;
        color: #4e73df;
    }

    .login-logo h2 {
        color: #3a3b45;
        font-weight: 700;
        margin-top: 15px;
    }

    .login-logo p {
        color: #858796;
        margin-top: 5px;
    }

    .form-floating .form-control {
        border-radius: 10px;
        border: 2px solid #e3e6f0;
    }

    .form-floating .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    .btn-login {
        background: #4e73df;
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s;
    }

    .btn-login:hover {
        background: #2e59d9;
        transform: translateY(-2px);
    }

    .alert {
        border-radius: 10px;
    }

    .demo-accounts {
        background: #f8f9fc;
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
    }

    .demo-accounts h6 {
        color: #3a3b45;
        margin-bottom: 10px;
    }

    .demo-accounts table {
        font-size: 0.85rem;
    }

    .demo-accounts td {
        padding: 3px 8px;
    }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Logo -->
            <div class="login-logo">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <h2>Hệ thống CRM</h2>
                <p>Quản lý khách hàng chuyên nghiệp</p>
            </div>

            <!-- Alert Container -->
            <div id="alertContainer"></div>

            <!-- Form đăng nhập -->
            <form id="loginForm">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" placeholder="Tên đăng nhập" required>
                    <label for="username"><i class="bi bi-person me-2"></i>Tên đăng nhập</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" placeholder="Mật khẩu" required>
                    <label for="password"><i class="bi bi-lock me-2"></i>Mật khẩu</label>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                    <a href="#" class="text-decoration-none small">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
                </button>
            </form>

            <!-- Demo Accounts -->
            <div class="demo-accounts">
                <h6><i class="bi bi-info-circle me-2"></i>Tài khoản demo:</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td><strong>Admin:</strong></td>
                        <td>admin / admin123</td>
                    </tr>
                    <tr>
                        <td><strong>Sales:</strong></td>
                        <td>sales01 / sales123</td>
                    </tr>
                    <tr>
                        <td><strong>Manager:</strong></td>
                        <td>manager01 / manager123</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    const API_BASE_URL = '/customer_management/backend/api';

    // Check if already logged in
    fetch(`${API_BASE_URL}/auth.php?action=check`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.authenticated) {
                window.location.href = 'dashboard.php';
            }
        });

    // Xử lý form đăng nhập
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const remember = document.getElementById('remember').checked;
        const loginBtn = document.getElementById('loginBtn');

        // Show loading
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang đăng nhập...';

        fetch(`${API_BASE_URL}/auth.php?action=login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username,
                    password,
                    remember
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to dashboard
                    window.location.href = 'dashboard.php';
                } else {
                    // Show error
                    showAlert(data.message || 'Đăng nhập thất bại', 'danger');
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập';
                }
            })
            .catch(error => {
                console.error('Lỗi đăng nhập:', error);
                showAlert('Có lỗi xảy ra, vui lòng thử lại', 'danger');
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập';
            });
    });

    function showAlert(message, type) {
        const container = document.getElementById('alertContainer');
        container.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
    }
    </script>
</body>

</html>