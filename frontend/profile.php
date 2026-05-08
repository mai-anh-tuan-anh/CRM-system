<?php
/**
 * Hồ sơ cá nhân
 * Trang xem và sửa thông tin cá nhân, đổi mật khẩu
 */
$pageTitle = 'Hồ sơ cá nhân - Hệ thống CRM';
$currentPage = 'profile';

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    
    <div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i>Hồ sơ cá nhân</h4>
    </div>

    <div class="row">
        <!-- Thông tin hồ sơ -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-person me-2"></i>Thông tin cá nhân
                    </h6>
                </div>
                <div class="card-body">
                    <form id="profileForm" enctype="multipart/form-data">
                        <!-- Avatar -->
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img id="avatarPreview" src="" alt="Avatar" class="rounded-circle" width="120" height="120" style="object-fit: cover; border: 3px solid #e3e6f0;">
                                <label for="avatarInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" style="cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-camera"></i>
                                </label>
                                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;">
                            </div>
                            <small class="text-muted d-block mt-2">Click để thay đổi avatar</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="fullName" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Địa chỉ</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="Nhập địa chỉ của bạn">
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Đổi mật khẩu -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-lock me-2"></i>Đổi mật khẩu
                    </h6>
                </div>
                <div class="card-body">
                    <form id="passwordForm">
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currentPassword" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Tối thiểu 6 ký tự</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-key me-2"></i>Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>

            <!-- Thông tin bảo mật -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="bi bi-shield-check me-2"></i>Bảo mật
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Đăng nhập an toàn</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Mật khẩu được mã hóa</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Phiên làm việc có hạn</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</div>

<?php include 'components/footer.php'; ?>

<script>
// Load profile data
async function loadProfile() {
    try {
        const response = await fetch(API_BASE_URL + '/auth.php?action=profile', {
            credentials: 'include'
        });
        const result = await response.json();
        
        if (result.success) {
            // Update global currentUser variable (from main.js)
            if (typeof currentUser !== 'undefined') {
                currentUser = result.data;
            }
            populateProfile(result.data);
        } else {
            showAlert(result.message || 'Không thể tải thông tin hồ sơ', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi tải hồ sơ', 'error');
    }
}

// Populate form with user data
function populateProfile(user) {
    document.getElementById('fullName').value = user.full_name || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('address').value = user.address || '';
    
    // Avatar preview
    const avatarPreview = document.getElementById('avatarPreview');
    if (user.avatar) {
        avatarPreview.src = user.avatar;
    } else {
        // Default avatar with initials
        const initials = (user.full_name || user.username || '?').charAt(0).toUpperCase();
        avatarPreview.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(initials) + '&background=4e73df&color=fff&size=120';
    }
}

// Toggle password visibility
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// Update profile
async function updateProfile(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('full_name', document.getElementById('fullName').value.trim());
    formData.append('email', document.getElementById('email').value.trim());
    formData.append('phone', document.getElementById('phone').value.trim());
    formData.append('address', document.getElementById('address').value.trim());
    
    // Add avatar file if selected
    const avatarFile = document.getElementById('avatarInput').files[0];
    if (avatarFile) {
        formData.append('avatar', avatarFile);
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php?action=update_profile`, {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Cập nhật hồ sơ thành công!', 'success');
            
            // Update global currentUser and refresh navbar
            if (typeof currentUser !== 'undefined') {
                currentUser = {
                    ...currentUser,
                    full_name: document.getElementById('fullName').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    phone: document.getElementById('phone').value.trim(),
                    address: document.getElementById('address').value.trim(),
                    avatar: result.data?.avatar || currentUser?.avatar
                };
                // Call updateUserInterface from main.js to refresh navbar
                if (typeof updateUserInterface === 'function') {
                    updateUserInterface();
                }
            }
        } else {
            showAlert(result.message || 'Cập nhật thất bại', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra', 'error');
    }
}

// Change password
async function changePassword(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        showAlert('Mật khẩu mới không khớp', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showAlert('Mật khẩu mới phải có ít nhất 6 ký tự', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php?action=change_password`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Đổi mật khẩu thành công!', 'success');
            document.getElementById('passwordForm').reset();
        } else {
            showAlert(result.message || 'Đổi mật khẩu thất bại', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra', 'error');
    }
}


// Event listeners - All inside DOMContentLoaded to ensure elements exist
document.addEventListener('DOMContentLoaded', function() {
    loadProfile();
    
    // Form submissions
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    
    if (profileForm) {
        profileForm.addEventListener('submit', updateProfile);
    }
    if (passwordForm) {
        passwordForm.addEventListener('submit', changePassword);
    }
    
    // Preview avatar when file selected
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatarPreview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

</body>
</html>
