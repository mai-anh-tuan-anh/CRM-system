# Customer Management CRM

Hệ thống CRM quản lý khách hàng hoàn chỉnh sử dụng PHP thuần và MySQL.

## Yêu cầu hệ thống

- XAMPP/WAMP với PHP 8.0+
- MySQL 5.7+ hoặc MariaDB 10.3+
- Trình duyệt web hiện đại (Chrome, Firefox, Edge, Safari)

## Cấu trúc dự án

```
customer_management/
├── backend/
│   ├── api/              # API endpoints
│   │   ├── auth.php      # Authentication API
│   │   ├── users.php     # Users API
│   │   ├── customers.php # Customers API
│   │   ├── leads.php     # Leads API
│   │   ├── deals.php     # Deals API
│   │   ├── tasks.php     # Tasks API
│   │   ├── dashboard.php # Dashboard API
│   │   ├── files.php     # File upload API
│   │   ├── search.php    # Search API
│   │   ├── convert.php   # Lead to customer conversion
│   │   ├── export.php    # Export data API
│   │   ├── import.php    # Import data API
│   │   ├── notifications.php # Notifications API
│   │   └── settings.php  # Settings API
│   ├── config/
│   │   └── database.php  # Database configuration
│   ├── controllers/      # Business logic controllers
│   ├── middleware/
│   │   └── auth.php      # Authentication middleware
│   ├── models/
│   │   ├── User.php
│   │   ├── Customer.php
│   │   ├── Lead.php
│   │   ├── Deal.php
│   │   ├── Task.php
│   │   ├── Activity.php
│   │   ├── Dashboard.php
│   │   ├── Notification.php
│   │   ├── File.php
│   │   ├── Setting.php
│   │   ├── Product.php
│   │   └── EmailTemplate.php
│   └── utils/
│       └── helpers.php   # Helper functions
├── frontend/
│   ├── components/
│   │   ├── header.php    # Common header
│   │   ├── footer.php    # Common footer
│   │   ├── sidebar.php   # Navigation sidebar
│   │   └── navbar.php    # Top navbar
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css # Custom styles
│   │   └── js/
│   │       └── main.js   # Main JavaScript
│   ├── login.php         # Login page
│   ├── dashboard.php     # Dashboard
│   ├── customers.php     # Customers list
│   ├── leads.php         # Leads list
│   ├── deals.php         # Deals list
│   ├── pipeline.php      # Pipeline view
│   ├── tasks.php         # Tasks list
│   ├── users.php         # User management (admin)
│   └── settings.php      # Settings
├── database/
│   └── crm_database.sql  # Database schema
└── README.md
```

## Cài đặt

### Bước 1: Cài đặt XAMPP

1. Tải và cài đặt XAMPP từ [https://www.apachefriends.org](https://www.apachefriends.org)
2. Khởi động Apache và MySQL từ XAMPP Control Panel

### Bước 2: Cài đặt dự án

1. Copy toàn bộ thư mục `customer_management` vào `C:\xampp\htdocs\`
2. Đảm bảo cấu trúc thư mục giống như mô tả ở trên

### Bước 3: Tạo database

1. Mở trình duyệt và truy cập: `http://localhost:8082/phpmyadmin`
2. Click **Import** tab
3. Chọn file `C:\xampp\htdocs\customer_management\database\crm_database.sql`
4. Click **Go** để import

Database sẽ được tạo tự động với tên `customer_management` và đầy đủ dữ liệu mẫu.

### Bước 4: Cấu hình (nếu cần)

Mở file `backend/config/database.php` và điều chỉnh nếu cần:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'customer_management');
define('DB_USER', 'root');
define('DB_PASS', ''); // Mật khẩu MySQL (thường để trống cho XAMPP)
```

### Bước 5: Chạy ứng dụng

Mở trình duyệt và truy cập:

```
http://localhost:8082/customer_management/frontend/login.php
```

## Tài khoản đăng nhập mặc định

| Vai trò | Username | Password |
|---------|----------|----------|
| Admin | admin | admin123 |
| Sales | sales01 | sales123 |
| Manager | manager01 | manager123 |

## Chức năng chính

### 1. Authentication & Authorization
- Đăng nhập, đăng xuất, phân quyền (Admin, Sales, Manager)
- Session-based authentication

### 2. Quản lý khách hàng (Customers)
- CRUD khách hàng đầy đủ
- Thông tin: tên, email, phone, công ty, ngành nghề, nguồn...
- Tìm kiếm, lọc, phân trang
- Import/Export CSV

### 3. Leads Management
- Tạo và quản lý leads
- Trạng thái: New, Contacted, Qualified, Lost
- Chấm điểm leads (Lead scoring)
- Chuyển đổi Lead → Customer

### 4. Deals/Opportunities
- Pipeline theo giai đoạn: Prospect, Qualification, Proposal, Negotiation, Won, Lost
- Kanban view
- Giá trị deal, xác suất thành công
- Theo dõi lịch sử thay đổi stage

### 5. Tasks & Activities
- Tạo task (call, meeting, email, demo)
- Gắn với customer, lead hoặc deal
- Deadline và nhắc nhở
- Trạng thái: Pending, In Progress, Completed

### 6. Dashboard
- Thống kê: số lượng khách hàng, leads, deals, doanh thu
- Biểu đồ doanh thu (Chart.js)
- Biểu đồ pipeline
- Hoạt động gần đây
- Công việc sắp tới

### 7. Search & Filter
- Tìm kiếm real-time bằng AJAX
- Tìm kiếm toàn cục

### 8. File Upload
- Upload file cho khách hàng/deal
- Quản lý attachments

### 9. Notifications
- Thông báo khi có task mới, lead được assign
- Đánh dấu đã đọc

### 10. Settings
- Quản lý users (Admin)
- Cấu hình hệ thống

## API Endpoints

### Authentication
- `POST /api/auth.php?action=login` - Đăng nhập
- `POST /api/auth.php?action=logout` - Đăng xuất
- `GET /api/auth.php?action=me` - Thông tin user hiện tại

### Customers
- `GET /api/customers.php` - Danh sách khách hàng
- `GET /api/customers.php?id={id}` - Chi tiết khách hàng
- `POST /api/customers.php` - Tạo khách hàng
- `PUT /api/customers.php` - Cập nhật khách hàng
- `DELETE /api/customers.php?id={id}` - Xóa khách hàng

### Leads
- `GET /api/leads.php` - Danh sách leads
- `POST /api/leads.php` - Tạo lead
- `PUT /api/leads.php` - Cập nhật lead
- `DELETE /api/leads.php?id={id}` - Xóa lead

### Deals
- `GET /api/deals.php` - Danh sách deals
- `GET /api/deals.php?pipeline=1` - Pipeline data
- `POST /api/deals.php` - Tạo deal
- `PUT /api/deals.php` - Cập nhật deal
- `DELETE /api/deals.php?id={id}` - Xóa deal

### Tasks
- `GET /api/tasks.php` - Danh sách tasks
- `POST /api/tasks.php` - Tạo task
- `PUT /api/tasks.php` - Cập nhật task
- `DELETE /api/tasks.php?id={id}` - Xóa task

### Dashboard
- `GET /api/dashboard.php?action=overview` - Tổng quan dashboard
- `GET /api/dashboard.php?action=stats` - Thống kê
- `GET /api/dashboard.php?action=pipeline` - Pipeline data

## Bảo mật

- Sử dụng Prepared Statements để chống SQL Injection
- Password hashing với bcrypt
- XSS protection với htmlspecialchars
- CSRF protection qua session
- Input validation và sanitization

## Ghi chú

- Đảm bảo thư mục `uploads/` có quyền ghi (writable)
- Cấu hình email trong Settings để gửi thông báo
- Backup database định kỳ

## Hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra XAMPP đang chạy (Apache và MySQL)
2. Kiểm tra database đã được import
3. Kiểm tra file log: `C:\xampp\apache\logs\error.log`

## License

MIT License - Free to use for personal and commercial projects.
