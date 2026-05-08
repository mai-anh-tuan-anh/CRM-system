-- CRM Database Schema
-- Created for XAMPP environment
-- Port: 8082

-- Drop database if exists and create new
DROP DATABASE IF EXISTS customer_management;
CREATE DATABASE customer_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE customer_management;

-- 1. Users Table (Admin, Sales, Manager accounts)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    role ENUM('admin', 'sales', 'manager') DEFAULT 'sales',
    avatar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    company_name VARCHAR(100),
    address TEXT,
    city VARCHAR(50),
    country VARCHAR(50) DEFAULT 'Vietnam',
    industry VARCHAR(50),
    website VARCHAR(100),
    source VARCHAR(50), -- How they found us
    status ENUM('active', 'inactive') DEFAULT 'active',
    assigned_to INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Leads Table
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_code VARCHAR(20) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    company_name VARCHAR(100),
    job_title VARCHAR(50),
    address TEXT,
    city VARCHAR(50),
    website VARCHAR(100),
    source VARCHAR(50), -- Website, Referral, Social Media, etc.
    status ENUM('new', 'contacted', 'qualified', 'converted', 'lost') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    score INT DEFAULT 0, -- Lead scoring 0-100
    assigned_to INT,
    notes TEXT,
    converted_to_customer_id INT,
    converted_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (converted_to_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Deals/Opportunities Table
CREATE TABLE deals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_code VARCHAR(20) UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    customer_id INT NOT NULL,
    lead_id INT,
    value DECIMAL(15, 2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'VND',
    stage ENUM('prospect', 'qualification', 'proposal', 'negotiation', 'won', 'lost') DEFAULT 'prospect',
    probability INT DEFAULT 0, -- Win probability 0-100
    expected_close_date DATE,
    actual_close_date DATE,
    assigned_to INT,
    source VARCHAR(50),
    competitor VARCHAR(100),
    loss_reason VARCHAR(200), -- If lost
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Deal Stages History (Track deal movements)
CREATE TABLE deal_stages_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_id INT NOT NULL,
    from_stage VARCHAR(50),
    to_stage VARCHAR(50) NOT NULL,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tasks & Activities Table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('call', 'meeting', 'email', 'follow_up', 'demo', 'task') DEFAULT 'task',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    related_to_type ENUM('customer', 'lead', 'deal') NOT NULL,
    related_to_id INT NOT NULL,
    assigned_to INT NOT NULL,
    due_date DATETIME,
    completed_at DATETIME,
    reminder_at DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Activities/Interactions Log
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_type ENUM('call', 'email', 'meeting', 'note', 'status_change', 'file_upload', 'deal_created', 'lead_converted') NOT NULL,
    description TEXT,
    related_to_type ENUM('customer', 'lead', 'deal', 'task', 'user') NOT NULL,
    related_to_id INT NOT NULL,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metadata JSON, -- Additional data in JSON format
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Files/Attachments Table
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    related_to_type ENUM('customer', 'lead', 'deal', 'user') NOT NULL,
    related_to_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Email Templates Table
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    variables TEXT, -- JSON of available variables
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Emails Log Table
CREATE TABLE emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT,
    from_email VARCHAR(100) NOT NULL,
    to_email VARCHAR(100) NOT NULL,
    cc_email VARCHAR(255),
    bcc_email VARCHAR(255),
    subject VARCHAR(200) NOT NULL,
    body TEXT,
    attachments TEXT, -- JSON array of file IDs
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    related_to_type ENUM('customer', 'lead', 'deal'),
    related_to_id INT,
    sent_by INT NOT NULL,
    sent_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Settings Table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'array') DEFAULT 'string',
    description VARCHAR(255),
    is_editable TINYINT(1) DEFAULT 1,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Notifications Table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    related_to_type ENUM('customer', 'lead', 'deal', 'task', 'system'),
    related_to_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Product/Services Catalog (for deals)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(20) UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(15, 2) DEFAULT 0,
    cost DECIMAL(15, 2) DEFAULT 0,
    category VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Deal Products (Many-to-Many)
CREATE TABLE deal_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(15, 2),
    discount DECIMAL(5, 2) DEFAULT 0,
    total_price DECIMAL(15, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- INDEXES FOR BETTER PERFORMANCE
-- ==========================================

CREATE INDEX idx_customers_assigned ON customers(assigned_to);
CREATE INDEX idx_customers_status ON customers(status);
CREATE INDEX idx_customers_created ON customers(created_at);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_assigned ON leads(assigned_to);
CREATE INDEX idx_leads_source ON leads(source);
CREATE INDEX idx_deals_stage ON deals(stage);
CREATE INDEX idx_deals_assigned ON deals(assigned_to);
CREATE INDEX idx_deals_customer ON deals(customer_id);
CREATE INDEX idx_deals_expected_close ON deals(expected_close_date);
CREATE INDEX idx_tasks_assigned ON tasks(assigned_to);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due ON tasks(due_date);
CREATE INDEX idx_activities_related ON activities(related_to_type, related_to_id);
CREATE INDEX idx_activities_performed ON activities(performed_by, performed_at);
CREATE INDEX idx_files_related ON files(related_to_type, related_to_id);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);

-- ==========================================
-- INSERT DEFAULT DATA
-- ==========================================

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, phone, role, is_active) VALUES
('admin', 'admin@crm.local', '$2y$10$718WxgSfEe1OCCm57Po7LuzbxxDG4bBmP2JjMmNHG3OT3rmJXmeF2', 'System Administrator', '0123456789', 'admin', 1);

-- Insert demo sales user (password: sales123)
INSERT INTO users (username, email, password, full_name, phone, role, is_active, created_by) VALUES
('sales01', 'sales@crm.local', '$2y$10$Nybj99JyDfV/jAF/u.URfOgs31YUq2lwgVipa2NxQ.3Sxiy4aRaZa', 'Sales Representative', '0987654321', 'sales', 1, 1);

-- Insert demo manager user (password: manager123)
INSERT INTO users (username, email, password, full_name, phone, role, is_active, created_by) VALUES
('manager01', 'manager@crm.local', '$2y$10$z5y8tCF2aQkmnNGMFRXH2.Mwo.JSbkPsGkfQYr8HSEhfxmTL3X8F2', 'Sales Manager', '0912345678', 'manager', 1, 1);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'My Company CRM', 'string', 'Company name displayed in the system'),
('company_email', 'contact@mycompany.com', 'string', 'Default company email address'),
('company_phone', '+84 123 456 789', 'string', 'Company contact phone'),
('currency', 'VND', 'string', 'Default currency code'),
('items_per_page', '20', 'integer', 'Number of items per page in lists'),
('lead_auto_assign', 'false', 'boolean', 'Automatically assign new leads'),
('deal_stages', '["prospect","qualification","proposal","negotiation","won","lost"]', 'json', 'Deal pipeline stages'),
('lead_sources', '["Website","Social Media","Referral","Email","Phone","Event","Other"]', 'json', 'Lead source options'),
('email_smtp_host', 'smtp.gmail.com', 'string', 'SMTP server host'),
('email_smtp_port', '587', 'integer', 'SMTP server port'),
('email_smtp_encryption', 'tls', 'string', 'SMTP encryption type'),
('email_from_address', 'noreply@crm.local', 'string', 'Default from email address'),
('email_from_name', 'CRM System', 'string', 'Default from name');

-- Insert default email templates
INSERT INTO email_templates (name, subject, body, variables, created_by) VALUES
('Welcome Email', 'Welcome to {{company_name}}!', 
'<h2>Hello {{customer_name}},</h2>
<p>Welcome to {{company_name}}! We\'re excited to have you on board.</p>
<p>If you have any questions, feel free to reach out to us.</p>
<p>Best regards,<br>{{sender_name}}</p>',
'["company_name","customer_name","sender_name"]', 1);

INSERT INTO email_templates (name, subject, body, variables, created_by) VALUES
('Follow-up Email', 'Following up on our conversation',
'<h2>Hi {{customer_name}},</h2>
<p>I hope this email finds you well. I wanted to follow up on our recent conversation about {{topic}}.</p>
<p>Please let me know if you have any questions or if there\'s anything else I can help you with.</p>
<p>Best regards,<br>{{sender_name}}<br>{{company_name}}</p>',
'["customer_name","topic","sender_name","company_name"]', 1);

INSERT INTO email_templates (name, subject, body, variables, created_by) VALUES
('Deal Proposal', 'Proposal for {{deal_title}}',
'<h2>Dear {{customer_name}},</h2>
<p>Thank you for your interest in our services. Please find attached our proposal for {{deal_title}}.</p>
<p>The total value of this proposal is {{deal_value}}.</p>
<p>We look forward to hearing from you.</p>
<p>Best regards,<br>{{sender_name}}<br>{{company_name}}</p>',
'["customer_name","deal_title","deal_value","sender_name","company_name"]', 1);

-- Insert sample products
INSERT INTO products (product_code, name, description, price, cost, category, is_active, created_by) VALUES
('PROD-001', 'Basic Service Package', 'Entry-level service package for small businesses', 5000000, 3000000, 'Services', 1, 1),
('PROD-002', 'Standard Service Package', 'Standard service package with additional features', 10000000, 6000000, 'Services', 1, 1),
('PROD-003', 'Premium Service Package', 'Full-featured premium service package', 20000000, 12000000, 'Services', 1, 1),
('PROD-004', 'Consulting Service', 'Professional consulting service per hour', 1500000, 500000, 'Consulting', 1, 1),
('PROD-005', 'Training Program', 'Comprehensive training program for teams', 15000000, 8000000, 'Training', 1, 1);

-- Insert sample customers
INSERT INTO customers (customer_code, full_name, email, phone, company_name, address, city, industry, source, status, assigned_to, notes, created_by) VALUES
('CUS-2024-001', 'Nguyễn Văn A', 'nguyenvana@email.com', '0901234567', 'Công ty TNHH A', '123 Lê Lợi, Q1', 'TP.HCM', 'Technology', 'Website', 'active', 2, 'Key customer from website', 1),
('CUS-2024-002', 'Trần Thị B', 'tranthib@email.com', '0912345678', 'Công ty CP B', '456 Nguyễn Huệ, Q1', 'TP.HCM', 'Finance', 'Referral', 'active', 2, 'Referred by existing customer', 1),
('CUS-2024-003', 'Lê Văn C', 'levanc@email.com', '0923456789', 'Công ty TNHH C', '789 Đồng Khởi, Q1', 'TP.HCM', 'Manufacturing', 'Social Media', 'active', 2, 'Found us on Facebook', 1),
('CUS-2024-004', 'Phạm Thị D', 'phamthid@email.com', '0934567890', 'Công ty CP D', '321 Hai Bà Trưng, Q3', 'TP.HCM', 'Retail', 'Email', 'active', 3, 'Responded to email campaign', 2),
('CUS-2024-005', 'Hoàng Văn E', 'hoangvane@email.com', '0945678901', 'Công ty TNHH E', '654 Cách Mạng Tháng 8, Q3', 'TP.HCM', 'Healthcare', 'Phone', 'active', 2, 'Called for inquiry', 1);

-- Insert sample leads
INSERT INTO leads (lead_code, full_name, email, phone, company_name, job_title, address, city, website, source, status, priority, score, assigned_to, notes, created_by) VALUES
('LEAD-2024-001', 'Vũ Thị F', 'vuthif@email.com', '0956789012', 'Công ty F', 'Marketing Manager', NULL, NULL, NULL, 'Website', 'new', 'high', 75, 2, 'Downloaded whitepaper', 1),
('LEAD-2024-002', 'Đặng Văn G', 'dangvang@email.com', '0967890123', 'Công ty G', 'IT Director', NULL, 'TP.HCM', 'congtyg.com', 'Referral', 'contacted', 'high', 85, 2, 'Referred by Nguyễn Văn A', 1),
('LEAD-2024-003', 'Bùi Thị H', 'buithih@email.com', '0978901234', 'Công ty H', 'CEO', '123 Lê Lợi, Q1', 'TP.HCM', 'congtyh.vn', 'Event', 'qualified', 'medium', 90, 3, 'Met at Tech Conference 2024', 2),
('LEAD-2024-004', 'Lý Văn I', 'lyvani@email.com', '0989012345', 'Công ty I', 'Sales Manager', NULL, 'Hà Nội', NULL, 'Social Media', 'contacted', 'low', 60, 2, 'LinkedIn connection', 1),
('LEAD-2024-005', 'Ngô Thị K', 'ngothik@email.com', '0990123456', 'Công ty K', 'Operations Manager', '456 Nguyễn Huệ, Q1', 'TP.HCM', NULL, 'Email', 'new', 'medium', 50, 2, 'Email subscriber', 1);

--- Insert sample deals
INSERT INTO deals (deal_code, title, description, customer_id, value, currency, stage, probability, expected_close_date, actual_close_date, assigned_to, source, notes, created_by) VALUES
('DEAL-2024-001', 'Giấy phép phần mềm doanh nghiệp', 'Gói phần mềm doanh nghiệp đầy đủ', 1, 500000000, 'VND', 'negotiation', 80, '2025-12-31', NULL, 2, 'Website', 'Thỏa thuận giá trị cao đang thương lượng', 1),
('DEAL-2024-002', 'Hợp đồng bảo trì hàng năm', 'Bảo trì và hỗ trợ hàng năm', 2, 100000000, 'VND', 'proposal', 60, '2025-11-30', NULL, 2, 'Referral', 'Khách hàng cũ gia hạn', 1),
('DEAL-2024-003', 'Dự án tư vấn', 'Tư vấn 3 tháng', 3, 75000000, 'VND', 'qualification', 40, '2025-12-15', NULL, 3, 'Social Media', 'Cần đánh giá kỹ thuật', 2),
('DEAL-2024-004', 'Gói đào tạo', 'Đào tạo đội ngũ 20 người', 4, 300000000, 'VND', 'won', 100, '2025-10-15', '2026-05-02', 2, 'Email', 'Đã đóng thành công', 2),
('DEAL-2024-005', 'Triển khai sản phẩm', 'Triển khai và cài đặt', 5, 150000000, 'VND', 'prospect', 20, '2025-06-30', NULL, 2, 'Phone', 'Thảo luận ban đầu', 1),
('DEAL-2024-006', 'Gói nâng cấp', 'Nâng cấp và chuyển đổi hệ thống', 1, 200000000, 'VND', 'negotiation', 70, '2025-12-20', NULL, 2, 'Website', 'Bán thêm cho khách hàng cũ', 1);

-- Insert sample tasks
INSERT INTO tasks (title, description, type, status, priority, related_to_type, related_to_id, assigned_to, due_date, created_by) VALUES
('Theo dõi với Nguyễn Văn A', 'Gọi điện thảo luận điều khoản gia hạn', 'call', 'pending', 'high', 'customer', 1, 2, '2024-11-25 10:00:00', 1),
('Gửi đề xuất cho Đặng Văn G', 'Gửi email đề xuất chi tiết', 'email', 'in_progress', 'high', 'lead', 2, 2, '2024-11-24 16:00:00', 1),
('Họp với Bùi Thị H', 'Thảo luận yêu cầu chi tiết', 'meeting', 'pending', 'urgent', 'lead', 3, 3, '2024-11-26 14:00:00', 2),
('Chuẩn bị demo cho DEAL-2024-005', 'Tạo môi trường demo tùy chỉnh', 'demo', 'pending', 'medium', 'deal', 5, 2, '2024-11-28 09:00:00', 1),
('Cập nhật dữ liệu CRM', 'Dọn dẹp và cập nhật hồ sơ khách hàng', 'task', 'completed', 'low', 'customer', 2, 2, '2024-11-20 17:00:00', 2);

-- Insert sample activities
INSERT INTO activities (activity_type, description, related_to_type, related_to_id, performed_by, metadata) VALUES
('call', 'Gọi điện tìm hiểu ban đầu với khách hàng', 'customer', 1, 2, '{"duration": 30, "outcome": "positive"}'),
('email', 'Gửi email chào mừng KH tiềm năng mới', 'lead', 1, 2, '{"template": "Email chào mừng"}'),
('meeting', 'Trình bày demo sản phẩm', 'deal', 1, 2, '{"location": "Zoom", "duration": 60}'),
('note', 'Khách hàng đề cập lo ngại về ngân sách', 'deal', 2, 2, '{}'),
('status_change', 'Trạng thái lead chuyển từ Mới sang Đã liên hệ', 'lead', 2, 2, '{"from": "new", "to": "contacted"}');

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type, related_to_type, related_to_id) VALUES
(2, 'KH tiềm năng mới được giao', 'KH tiềm năng Vũ Thị F đã được giao cho bạn', 'info', 'lead', 1),
(2, 'Thỏa thuận sắp đến hạn', 'DEAL-2024-001 dự kiến đóng trong 7 ngày', 'warning', 'deal', 1),
(2, 'Công việc quá hạn', 'Theo dõi với Nguyễn Văn A đã quá hạn', 'error', 'task', 1),
(3, 'KH tiềm năng ưu tiên cao', 'KH tiềm năng Bùi Thị H cần xử lý ngay', 'warning', 'lead', 3);

-- Insert sample deal stages history
INSERT INTO deal_stages_history (deal_id, from_stage, to_stage, changed_by, notes) VALUES
(1, 'prospect', 'qualification', 2, 'Hoàn thành xác minh ban đầu'),
(1, 'qualification', 'proposal', 2, 'Đã gửi đề xuất cho khách hàng'),
(1, 'proposal', 'negotiation', 2, 'Khách hàng yêu cầu thay đổi'),
(2, 'prospect', 'qualification', 2, 'Đã đánh giá nhu cầu'),
(2, 'qualification', 'proposal', 2, 'Đã thảo luận giá'),
(4, 'prospect', 'qualification', 2, 'Đã thu thập yêu cầu'),
(4, 'qualification', 'proposal', 2, 'Đề xuất được chấp nhận'),
(4, 'proposal', 'negotiation', 2, 'Đã thương lượng điều khoản'),
(4, 'negotiation', 'won', 2, 'Đóng thỏa thuận thành công');
