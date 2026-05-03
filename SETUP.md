# Hướng dẫn cài đặt CRM System

## Yêu cầu hệ thống

- **XAMPP** với PHP 8.0+ (cổng 8082)
- **MySQL** 5.7+ hoặc MariaDB 10.3+
- Trình duyệt web hiện đại

## Cài đặt nhanh (5 phút)

### Bước 1: Khởi động XAMPP

1. Mở **XAMPP Control Panel**
2. Start **Apache** và **MySQL**
3. Đảm bảo Apache chạy trên port **8082**

### Bước 2: Import Database

1. Mở trình duyệt: `http://localhost:8082/phpmyadmin`
2. Click tab **Import**
3. Chọn file: `C:\xampp\htdocs\customer_management\database\crm_database.sql`
4. Click **Go**

### Bước 3: Truy cập ứng dụng

Mở trình duyệt và truy cập:

```
http://localhost:8082/customer_management/frontend/login.php
```

## Tài khoản đăng nhập

| Vai trò | Username | Password |
|---------|----------|----------|
| **Admin** | admin | admin123 |
| **Sales** | sales01 | sales123 |
| **Manager** | manager01 | manager123 |

## Cấu trúc thư mục

```
customer_management/
├── backend/          # API & Logic
│   ├── api/          # REST API endpoints
│   ├── models/       # Database models
│   ├── config/       # Configuration
│   └── utils/        # Helper functions
├── frontend/         # Giao diện người dùng
│   ├── components/   # Sidebar, Navbar
│   ├── assets/       # CSS, JS
│   └── *.php        # Các trang
└── database/         # SQL files
```

## Chức năng chính

### Dashboard
- Tổng quan số liệu
- Biểu đồ doanh thu
- Hoạt động gần đây
- Công việc sắp tới

### Khách hàng
- Danh sách khách hàng
- Thêm/Sửa/Xóa
- Import/Export CSV
- Tìm kiếm và lọc

### Leads
- Quản lý leads
- Chấm điểm leads
- Chuyển đổi Lead → Customer

### Deals
- Quản lý cơ hội
- Pipeline view (Kanban)
- Theo dõi giá trị

### Tasks
- Quản lý công việc
- Gán công việc
- Theo dõi deadline

## Xử lý lỗi thường gặp

### Lỗi kết nối database
```
Kiểm tra:
1. MySQL đã start trong XAMPP
2. File backend/config/database.php
3. DB_NAME = 'customer_management'
```

### Lỗi 404
```
Kiểm tra:
1. Đường dẫn đúng: /customer_management/frontend/
2. File .htaccess (nếu có)
```

### Lỗi session
```
Kiểm tra php.ini:
session.save_path = "C:\xampp\tmp"
```

## API Documentation

### Authentication
```
POST /backend/api/auth.php?action=login
Body: { "username": "...", "password": "..." }
```

### Customers
```
GET  /backend/api/customers.php          # List
GET  /backend/api/customers.php?id=1    # Detail
POST /backend/api/customers.php          # Create
PUT  /backend/api/customers.php          # Update
DELETE /backend/api/customers.php?id=1  # Delete
```

### Leads
```
GET    /backend/api/leads.php
POST   /backend/api/leads.php
PUT    /backend/api/leads.php
DELETE /backend/api/leads.php?id=1
```

### Deals
```
GET /backend/api/deals.php               # List
GET /backend/api/deals.php?pipeline=1    # Pipeline
```

### Dashboard
```
GET /backend/api/dashboard.php?action=overview
GET /backend/api/dashboard.php?action=stats
```

## Bảo mật

- ✅ Prepared Statements (chống SQL Injection)
- ✅ Password Hashing (bcrypt)
- ✅ XSS Protection
- ✅ Session Security
- ✅ Input Validation

## Backup Database

```bash
# Export
cd C:\xampp\mysql\bin
mysqldump -u root customer_management > backup.sql

# Import
mysql -u root customer_management < backup.sql
```

## Liên hệ hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra file log: `C:\xampp\apache\logs\error.log`
2. Đảm bảo tất cả file đã được tạo đầy đủ
3. Kiểm tra quyền thư mục uploads/

---

**Lưu ý**: Đây là hệ thống demo. Đổi mật khẩu sau khi đăng nhập lần đầu!
