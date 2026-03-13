-- Single Cooperative Database Schema untuk KSP Lam Gabe Jaya
-- Author: KSP Lam Gabe Jaya Development Team
-- Version: 2.0 (Single Cooperative)
-- Date: 2026-03-12

-- Create main database
CREATE DATABASE IF NOT EXISTS ksp_lamgabejaya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ksp_lamgabejaya;

-- =============================================
-- COOPERATIVE MANAGEMENT
-- =============================================

-- Cooperative information
CREATE TABLE cooperatives (
    id INT PRIMARY KEY DEFAULT 1,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT 'ksp-lamgabejaya-uuid',
    name VARCHAR(255) NOT NULL DEFAULT 'KSP Lam Gabe Jaya',
    code VARCHAR(50) NOT NULL DEFAULT 'LAMGABE',
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    website VARCHAR(255),
    npwp VARCHAR(25),
    siup_number VARCHAR(50),
    establishment_date DATE NOT NULL,
    registration_number VARCHAR(50),
    operational_license VARCHAR(50),
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(255),
    max_daily_loan_amount DECIMAL(15,2) DEFAULT 5000000,
    max_monthly_loan_amount DECIMAL(15,2) DEFAULT 50000000,
    interest_rate_monthly DECIMAL(5,4) DEFAULT 0.0200,
    late_fee_rate DECIMAL(5,4) DEFAULT 0.0010,
    admin_fee_rate DECIMAL(5,4) DEFAULT 0.0100,
    settings JSON DEFAULT (JSON_OBJECT()),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Units/Cabang untuk single cooperative
CREATE TABLE units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    cooperative_id INT DEFAULT 1,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    type ENUM('main', 'branch', 'sub_branch') DEFAULT 'branch',
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    head_name VARCHAR(255),
    head_phone VARCHAR(20),
    operational_area TEXT,
    coverage_radius_km DECIMAL(8,3) DEFAULT 5.000,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    is_active BOOLEAN DEFAULT TRUE,
    max_members INT DEFAULT 1000,
    max_loan_amount DECIMAL(15,2) DEFAULT 10000000,
    settings JSON DEFAULT (JSON_OBJECT()),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cooperative_id) REFERENCES cooperatives(id),
    INDEX idx_units_cooperative (cooperative_id),
    INDEX idx_units_code (code),
    INDEX idx_units_active (is_active)
);

-- =============================================
-- USER MANAGEMENT (Single Cooperative)
-- =============================================

-- Users table (single cooperative)
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_users_uuid (uuid),
    INDEX idx_users_email (email),
    INDEX idx_users_phone (phone),
    INDEX idx_users_active (is_active)
);

-- User roles untuk single cooperative
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    permissions JSON DEFAULT (JSON_OBJECT()),
    is_system_role BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User assignments
CREATE TABLE user_assignments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    unit_id INT NOT NULL,
    role_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES user_roles(id),
    UNIQUE KEY unique_user_unit_role (user_id, unit_id, role_id),
    INDEX idx_user_assignments_user (user_id),
    INDEX idx_user_assignments_unit (unit_id),
    INDEX idx_user_assignments_role (role_id)
);

-- User sessions
CREATE TABLE user_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    device_info JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_token (token_hash),
    INDEX idx_sessions_expires (expires_at)
);

-- =============================================
-- MEMBER MANAGEMENT
-- =============================================

-- Members table
CREATE TABLE members (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    user_id BIGINT NOT NULL,
    member_number VARCHAR(20) UNIQUE NOT NULL,
    nik VARCHAR(16) UNIQUE NOT NULL,
    kk_number VARCHAR(16),
    birth_place VARCHAR(100),
    birth_date DATE,
    gender ENUM('male', 'female') NOT NULL,
    address TEXT NOT NULL,
    rt VARCHAR(3),
    rw VARCHAR(3),
    village VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(255),
    occupation VARCHAR(100),
    company_name VARCHAR(255),
    company_address TEXT,
    monthly_income DECIMAL(12,2),
    marital_status ENUM('single', 'married', 'divorced', 'widowed') NOT NULL,
    spouse_name VARCHAR(255),
    spouse_nik VARCHAR(16),
    spouse_phone VARCHAR(20),
    mother_name VARCHAR(255),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relation VARCHAR(50),
    photo_ktp VARCHAR(255),
    photo_selfie VARCHAR(255),
    photo_signature VARCHAR(255),
    registration_unit_id INT NOT NULL,
    registration_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'suspended', 'blacklisted') DEFAULT 'active',
    credit_score DECIMAL(5,2) DEFAULT 0.00,
    membership_level ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    total_savings DECIMAL(15,2) DEFAULT 0.00,
    total_loans DECIMAL(15,2) DEFAULT 0.00,
    outstanding_loans DECIMAL(15,2) DEFAULT 0.00,
    late_payment_count INT DEFAULT 0,
    blacklist_reason TEXT,
    blacklist_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (registration_unit_id) REFERENCES units(id),
    INDEX idx_members_uuid (uuid),
    INDEX idx_members_number (member_number),
    INDEX idx_members_nik (nik),
    INDEX idx_members_status (status),
    INDEX idx_members_unit (registration_unit_id),
    INDEX idx_members_credit_score (credit_score)
);

-- =============================================
-- SAVINGS MANAGEMENT
-- =============================================

-- Savings products
CREATE TABLE savings_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    type ENUM('regular', 'mandatory', 'voluntary', 'time_deposit', 'education', 'retirement') NOT NULL,
    description TEXT,
    minimum_balance DECIMAL(15,2) DEFAULT 0.00,
    minimum_deposit DECIMAL(15,2) DEFAULT 10000.00,
    maximum_deposit DECIMAL(15,2) DEFAULT 999999999.99,
    interest_rate_monthly DECIMAL(5,4) DEFAULT 0.0000,
    interest_rate_yearly DECIMAL(5,4) DEFAULT 0.0000,
    tax_rate DECIMAL(5,4) DEFAULT 0.2000,
    admin_fee_monthly DECIMAL(15,2) DEFAULT 0.00,
    withdrawal_fee DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_savings_products_code (code),
    INDEX idx_savings_products_type (type),
    INDEX idx_savings_products_active (is_active)
);

-- Member savings accounts
CREATE TABLE member_savings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    member_id BIGINT NOT NULL,
    savings_product_id INT NOT NULL,
    account_number VARCHAR(30) UNIQUE NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    frozen_amount DECIMAL(15,2) DEFAULT 0.00,
    last_deposit_date DATE,
    last_withdrawal_date DATE,
    status ENUM('active', 'inactive', 'frozen', 'closed') DEFAULT 'active',
    opened_date DATE NOT NULL,
    closed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (savings_product_id) REFERENCES savings_products(id),
    INDEX idx_member_savings_member (member_id),
    INDEX idx_member_savings_product (savings_product_id),
    INDEX idx_member_savings_account (account_number),
    INDEX idx_member_savings_status (status)
);

-- Savings transactions
CREATE TABLE savings_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    member_savings_id BIGINT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'interest', 'tax', 'fee', 'transfer_in', 'transfer_out') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    description TEXT,
    reference_number VARCHAR(50),
    transaction_unit_id INT NOT NULL,
    transaction_user_id BIGINT NOT NULL,
    transaction_location_lat DECIMAL(10,8),
    transaction_location_lng DECIMAL(11,8),
    transaction_location_address TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_savings_id) REFERENCES member_savings(id),
    FOREIGN KEY (transaction_unit_id) REFERENCES units(id),
    FOREIGN KEY (transaction_user_id) REFERENCES users(id),
    INDEX idx_savings_transactions_savings (member_savings_id),
    INDEX idx_savings_transactions_type (transaction_type),
    INDEX idx_savings_transactions_date (transaction_date),
    INDEX idx_savings_transactions_reference (reference_number)
);

-- =============================================
-- LOAN MANAGEMENT
-- =============================================

-- Loan products
CREATE TABLE loan_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    type ENUM('productive', 'consumptive', 'emergency', 'education', 'renovation', 'vehicle', 'working_capital') NOT NULL,
    description TEXT,
    minimum_amount DECIMAL(15,2) DEFAULT 500000.00,
    maximum_amount DECIMAL(15,2) DEFAULT 50000000.00,
    minimum_term_months INT DEFAULT 1,
    maximum_term_months INT DEFAULT 60,
    interest_rate_monthly DECIMAL(5,4) NOT NULL,
    late_fee_rate DECIMAL(5,4) DEFAULT 0.0010,
    admin_fee_rate DECIMAL(5,4) DEFAULT 0.0100,
    provision_fee_rate DECIMAL(5,4) DEFAULT 0.0050,
    insurance_fee_rate DECIMAL(5,4) DEFAULT 0.0020,
    collateral_required BOOLEAN DEFAULT TRUE,
    credit_score_min DECIMAL(5,2) DEFAULT 50.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_loan_products_code (code),
    INDEX idx_loan_products_type (type),
    INDEX idx_loan_products_active (is_active)
);

-- Loan applications
CREATE TABLE loan_applications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    member_id BIGINT NOT NULL,
    loan_product_id INT NOT NULL,
    application_number VARCHAR(30) UNIQUE NOT NULL,
    amount_requested DECIMAL(15,2) NOT NULL,
    term_months INT NOT NULL,
    purpose TEXT NOT NULL,
    collateral_type ENUM('none', 'property', 'vehicle', 'deposit', 'guarantor', 'other') NOT NULL,
    collateral_description TEXT,
    collateral_value DECIMAL(15,2),
    guarantor_name VARCHAR(255),
    guarantor_nik VARCHAR(16),
    guarantor_phone VARCHAR(20),
    guarantor_address TEXT,
    monthly_income DECIMAL(12,2),
    monthly_expenses DECIMAL(12,2),
    other_loans DECIMAL(15,2) DEFAULT 0.00,
    credit_score_before DECIMAL(5,2),
    credit_score_after DECIMAL(5,2),
    application_unit_id INT NOT NULL,
    application_user_id BIGINT NOT NULL,
    application_location_lat DECIMAL(10,8),
    application_location_lng DECIMAL(11,8),
    application_location_address TEXT,
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'disbursed', 'closed') DEFAULT 'draft',
    rejection_reason TEXT,
    approved_amount DECIMAL(15,2),
    approved_term_months INT,
    approved_interest_rate DECIMAL(5,4),
    approved_by BIGINT,
    approved_at TIMESTAMP NULL,
    disbursed_amount DECIMAL(15,2),
    disbursed_by BIGINT,
    disbursed_at TIMESTAMP NULL,
    notes TEXT,
    submitted_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (loan_product_id) REFERENCES loan_products(id),
    FOREIGN KEY (application_unit_id) REFERENCES units(id),
    FOREIGN KEY (application_user_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (disbursed_by) REFERENCES users(id),
    INDEX idx_loan_applications_member (member_id),
    INDEX idx_loan_applications_product (loan_product_id),
    INDEX idx_loan_applications_number (application_number),
    INDEX idx_loan_applications_status (status),
    INDEX idx_loan_applications_date (created_at)
);

-- Loans
CREATE TABLE loans (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    loan_application_id BIGINT NOT NULL,
    member_id BIGINT NOT NULL,
    loan_product_id INT NOT NULL,
    loan_number VARCHAR(30) UNIQUE NOT NULL,
    principal_amount DECIMAL(15,2) NOT NULL,
    interest_rate_monthly DECIMAL(5,4) NOT NULL,
    term_months INT NOT NULL,
    monthly_installment DECIMAL(15,2) NOT NULL,
    total_interest DECIMAL(15,2) NOT NULL,
    total_payment DECIMAL(15,2) NOT NULL,
    disbursed_amount DECIMAL(15,2) NOT NULL,
    outstanding_principal DECIMAL(15,2) NOT NULL,
    outstanding_interest DECIMAL(15,2) NOT NULL,
    outstanding_total DECIMAL(15,2) NOT NULL,
    next_payment_date DATE,
    next_payment_amount DECIMAL(15,2),
    late_days_count INT DEFAULT 0,
    late_fee_amount DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active', 'late', 'default', 'restructured', 'paid_off', 'written_off') DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    paid_off_date DATE,
    disbursed_unit_id INT NOT NULL,
    disbursed_user_id BIGINT NOT NULL,
    disbursed_location_lat DECIMAL(10,8),
    disbursed_location_lng DECIMAL(11,8),
    disbursed_location_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_application_id) REFERENCES loan_applications(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (loan_product_id) REFERENCES loan_products(id),
    FOREIGN KEY (disbursed_unit_id) REFERENCES units(id),
    FOREIGN KEY (disbursed_user_id) REFERENCES users(id),
    INDEX idx_loans_member (member_id),
    INDEX idx_loans_product (loan_product_id),
    INDEX idx_loans_number (loan_number),
    INDEX idx_loans_status (status),
    INDEX idx_loans_next_payment (next_payment_date)
);

-- Loan payments
CREATE TABLE loan_payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    loan_id BIGINT NOT NULL,
    payment_number INT NOT NULL,
    payment_type ENUM('installment', 'principal', 'interest', 'late_fee', 'prepayment', 'settlement') NOT NULL,
    amount_paid DECIMAL(15,2) NOT NULL,
    principal_amount DECIMAL(15,2) NOT NULL,
    interest_amount DECIMAL(15,2) NOT NULL,
    late_fee_amount DECIMAL(15,2) DEFAULT 0.00,
    principal_before DECIMAL(15,2) NOT NULL,
    principal_after DECIMAL(15,2) NOT NULL,
    interest_before DECIMAL(15,2) NOT NULL,
    interest_after DECIMAL(15,2) NOT NULL,
    late_fee_before DECIMAL(15,2) DEFAULT 0.00,
    late_fee_after DECIMAL(15,2) DEFAULT 0.00,
    payment_method ENUM('cash', 'transfer', 'qris', 'digital_wallet', 'bank_transfer', 'other') NOT NULL,
    payment_reference VARCHAR(100),
    receipt_number VARCHAR(50),
    payment_unit_id INT NOT NULL,
    payment_user_id BIGINT NOT NULL,
    payment_location_lat DECIMAL(10,8),
    payment_location_lng DECIMAL(11,8),
    payment_location_address TEXT,
    photo_evidence VARCHAR(255),
    is_late BOOLEAN DEFAULT FALSE,
    days_late INT DEFAULT 0,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES loans(id),
    FOREIGN KEY (payment_unit_id) REFERENCES units(id),
    FOREIGN KEY (payment_user_id) REFERENCES users(id),
    INDEX idx_loan_payments_loan (loan_id),
    INDEX idx_loan_payments_type (payment_type),
    INDEX idx_loan_payments_date (payment_date),
    INDEX idx_loan_payments_receipt (receipt_number)
);

-- =============================================
-- GPS & LOCATION TRACKING
-- =============================================

-- Location tracking
CREATE TABLE location_tracking (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    unit_id INT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    accuracy DECIMAL(8,2),
    altitude DECIMAL(8,2),
    speed DECIMAL(8,2),
    bearing DECIMAL(8,2),
    location_type ENUM('check_in', 'check_out', 'transaction', 'visit', 'route') NOT NULL,
    reference_type ENUM('loan', 'payment', 'member_visit', 'other') NULL,
    reference_id BIGINT NULL,
    address TEXT,
    device_info JSON,
    is_fake_gps BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    INDEX idx_location_tracking_user (user_id),
    INDEX idx_location_tracking_date (created_at),
    INDEX idx_location_tracking_type (location_type)
);

-- Geofencing zones
CREATE TABLE geofence_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    unit_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('branch_coverage', 'member_area', 'restricted_area', 'allowed_area') NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    radius_meters INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (unit_id) REFERENCES units(id),
    INDEX idx_geofence_unit (unit_id),
    INDEX idx_geofence_active (is_active)
);

-- =============================================
-- FRAUD PREVENTION & SECURITY
-- =============================================

-- Fraud detection logs
CREATE TABLE fraud_detection_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    user_id BIGINT NOT NULL,
    transaction_type ENUM('loan', 'payment', 'member_registration', 'user_login', 'other') NOT NULL,
    reference_id BIGINT,
    risk_score DECIMAL(5,4) NOT NULL,
    risk_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    detection_method ENUM('ml_model', 'rule_based', 'behavioral', 'location_anomaly', 'other') NOT NULL,
    risk_factors JSON,
    ml_predictions JSON,
    is_blocked BOOLEAN DEFAULT FALSE,
    requires_review BOOLEAN DEFAULT FALSE,
    reviewed_by BIGINT,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_fraud_detection_user (user_id),
    INDEX idx_fraud_detection_type (transaction_type),
    INDEX idx_fraud_detection_risk (risk_level),
    INDEX idx_fraud_detection_date (created_at)
);

-- Security audit logs
CREATE TABLE security_audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    user_id BIGINT,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_info JSON,
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_security_audit_user (user_id),
    INDEX idx_security_audit_action (action),
    INDEX idx_security_audit_date (created_at)
);

-- =============================================
-- NOTIFICATIONS & COMMUNICATIONS
-- =============================================

-- Notification templates
CREATE TABLE notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    type ENUM('sms', 'email', 'push', 'whatsapp') NOT NULL,
    category ENUM('payment_reminder', 'loan_due', 'welcome', 'promotion', 'alert', 'other') NOT NULL,
    subject VARCHAR(255),
    content TEXT NOT NULL,
    variables JSON DEFAULT (JSON_OBJECT()),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_notification_templates_type (type),
    INDEX idx_notification_templates_category (category),
    INDEX idx_notification_templates_active (is_active)
);

-- Notifications
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    user_id BIGINT NOT NULL,
    template_id INT,
    type ENUM('sms', 'email', 'push', 'whatsapp', 'in_app') NOT NULL,
    category ENUM('payment_reminder', 'loan_due', 'welcome', 'promotion', 'alert', 'system', 'other') NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed', 'read') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    reference_type VARCHAR(50),
    reference_id BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (template_id) REFERENCES notification_templates(id),
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_type (type),
    INDEX idx_notifications_status (status),
    INDEX idx_notifications_date (created_at)
);

-- =============================================
-- REPORTING & ANALYTICS
-- =============================================

-- Daily reports
CREATE TABLE daily_reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    unit_id INT NOT NULL,
    report_date DATE NOT NULL,
    total_members INT DEFAULT 0,
    new_members INT DEFAULT 0,
    active_members INT DEFAULT 0,
    total_savings DECIMAL(15,2) DEFAULT 0.00,
    total_deposits DECIMAL(15,2) DEFAULT 0.00,
    total_withdrawals DECIMAL(15,2) DEFAULT 0.00,
    total_loans_disbursed DECIMAL(15,2) DEFAULT 0.00,
    total_loan_payments DECIMAL(15,2) DEFAULT 0.00,
    outstanding_loans DECIMAL(15,2) DEFAULT 0.00,
    late_loans_count INT DEFAULT 0,
    late_loans_amount DECIMAL(15,2) DEFAULT 0.00,
    default_loans_count INT DEFAULT 0,
    default_loans_amount DECIMAL(15,2) DEFAULT 0.00,
    total_revenue DECIMAL(15,2) DEFAULT 0.00,
    total_expenses DECIMAL(15,2) DEFAULT 0.00,
    net_profit DECIMAL(15,2) DEFAULT 0.00,
    fraud_alerts_count INT DEFAULT 0,
    transactions_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (unit_id) REFERENCES units(id),
    UNIQUE KEY unique_unit_date_report (unit_id, report_date),
    INDEX idx_daily_reports_unit (unit_id),
    INDEX idx_daily_reports_date (report_date)
);

-- =============================================
-- SETTINGS & CONFIGURATION
-- =============================================

-- System settings
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX idx_system_settings_key (key_name),
    INDEX idx_system_settings_public (is_public)
);

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Insert default cooperative
INSERT INTO cooperatives (uuid, name, code, address, phone, email, establishment_date, registration_number, operational_license, bank_name, bank_account_number, bank_account_name) 
VALUES (UUID(), 'KSP Lam Gabe Jaya', 'LAMGABE', 'Jl. Raya Lam Gabe No. 123, Kec. Lam Gabe, Kab. Someplace', '08123456789', 'info@lamgabejaya.coop', '2020-01-15', '1234567890', 'SK-123/2020', 'Bank BCA', '1234567890', 'KSP Lam Gabe Jaya');

-- Insert default user roles
INSERT INTO user_roles (name, display_name, description, permissions, is_system_role) VALUES
('super_admin', 'Super Administrator', 'Full system access', JSON_OBJECT('all', true), TRUE),
('admin', 'Administrator', 'Administrative access', JSON_OBJECT('users', JSON_ARRAY('read', 'write', 'delete'), 'members', JSON_ARRAY('read', 'write', 'delete'), 'loans', JSON_ARRAY('read', 'write', 'delete'), 'savings', JSON_ARRAY('read', 'write', 'delete'), 'reports', JSON_ARRAY('read', 'write')), TRUE),
('mantri', 'Petugas Lapangan', 'Field officer access', JSON_OBJECT('members', JSON_ARRAY('read', 'write'), 'loans', JSON_ARRAY('read', 'write'), 'savings', JSON_ARRAY('read', 'write'), 'payments', JSON_ARRAY('read', 'write')), TRUE),
('member', 'Anggota', 'Member access', JSON_OBJECT('profile', JSON_ARRAY('read', 'write'), 'savings', JSON_ARRAY('read'), 'loans', JSON_ARRAY('read', 'write'), 'payments', JSON_ARRAY('read', 'write')), TRUE);

-- Insert default main unit
INSERT INTO units (uuid, name, code, type, address, phone, email, head_name, head_phone, latitude, longitude, coverage_radius_km) 
VALUES (UUID(), 'Kantor Pusat', 'MAIN', 'main', 'Jl. Raya Lam Gabe No. 123, Kec. Lam Gabe, Kab. Someplace', '08123456789', 'info@lamgabejaya.coop', 'Bpk. Ahmad', '08123456789', -6.2088, 106.8456, 10.000);

-- Insert default savings products
INSERT INTO savings_products (uuid, name, code, type, description, minimum_balance, minimum_deposit, interest_rate_monthly, is_active) VALUES
(UUID(), 'Simpanan Wajib', 'WAJIB', 'mandatory', 'Simpanan wajib bulanan anggota', 10000.00, 10000.00, 0.0000, TRUE),
(UUID(), 'Simpanan Sukarela', 'SUKARELA', 'voluntary', 'Simpanan sukarela dengan bunga', 0.00, 10000.00, 0.0025, TRUE),
(UUID(), 'Simpanan Pendidikan', 'PENDIDIKAN', 'education', 'Tabungan pendidikan anak', 100000.00, 50000.00, 0.0030, TRUE);

-- Insert default loan products
INSERT INTO loan_products (uuid, name, code, type, description, minimum_amount, maximum_amount, minimum_term_months, maximum_term_months, interest_rate_monthly, collateral_required, credit_score_min, is_active) VALUES
(UUID(), 'Pinjaman Produktif', 'PRODUKTIF', 'productive', 'Pinjaman untuk modal usaha', 1000000.00, 50000000.00, 3, 36, 0.0150, TRUE, 60.00, TRUE),
(UUID(), 'Pinjaman Konsumtif', 'KONSUMTIF', 'consumptive', 'Pinjaman untuk kebutuhan konsumtif', 500000.00, 20000000.00, 1, 24, 0.0200, TRUE, 50.00, TRUE),
(UUID(), 'Pinjaman Darurat', 'DARURAT', 'emergency', 'Pinjaman darurat cepat cair', 500000.00, 10000000.00, 1, 12, 0.0250, FALSE, 40.00, TRUE);

-- Insert default system settings
INSERT INTO system_settings (key_name, value, description, type, is_public) VALUES
('app_name', 'KSP Lam Gabe Jaya', 'Application name', 'string', TRUE),
('app_version', '2.0.0', 'Application version', 'string', TRUE),
('gps_tolerance_meters', '50', 'GPS tolerance in meters', 'number', FALSE),
('fraud_detection_enabled', 'true', 'Enable fraud detection', 'boolean', FALSE),
('max_login_attempts', '5', 'Maximum login attempts', 'number', FALSE),
('session_timeout_minutes', '480', 'Session timeout in minutes', 'number', FALSE),
('backup_enabled', 'true', 'Enable automatic backup', 'boolean', FALSE),
('backup_retention_days', '30', 'Backup retention in days', 'number', FALSE);

-- =============================================
-- CREATE INDEXES FOR PERFORMANCE
-- =============================================

-- Composite indexes for frequently queried combinations
CREATE INDEX idx_members_status_unit ON members(status, registration_unit_id);
CREATE INDEX idx_loans_member_status ON loans(member_id, status);
CREATE INDEX idx_loan_payments_loan_date ON loan_payments(loan_id, payment_date);
CREATE INDEX idx_savings_transactions_savings_date ON savings_transactions(member_savings_id, transaction_date);
CREATE INDEX idx_fraud_detection_user_date ON fraud_detection_logs(user_id, created_at);
CREATE INDEX idx_location_tracking_user_date ON location_tracking(user_id, created_at);

-- Full-text indexes for search functionality
CREATE FULLTEXT INDEX idx_members_search ON members(name, address, occupation);
CREATE FULLTEXT INDEX idx_loans_search ON loans(loan_number, notes);
CREATE FULLTEXT INDEX idx_members_search_advanced ON members(name, address, occupation, company_name);

-- =============================================
-- CREATE VIEWS FOR REPORTING
-- =============================================

-- Member summary view
CREATE VIEW member_summary AS
SELECT 
    m.id,
    m.uuid,
    m.member_number,
    m.name,
    m.phone,
    m.status,
    m.credit_score,
    m.membership_level,
    m.total_savings,
    m.total_loans,
    m.outstanding_loans,
    m.late_payment_count,
    u.name as unit_name,
    m.created_at
FROM members m
JOIN units u ON m.registration_unit_id = u.id;

-- Loan summary view
CREATE VIEW loan_summary AS
SELECT 
    l.id,
    l.uuid,
    l.loan_number,
    l.principal_amount,
    l.outstanding_total,
    l.status,
    l.next_payment_date,
    l.late_days_count,
    l.start_date,
    l.end_date,
    m.name as member_name,
    m.member_number,
    lp.name as product_name,
    u.name as unit_name,
    l.created_at
FROM loans l
JOIN members m ON l.member_id = m.id
JOIN loan_products lp ON l.loan_product_id = lp.id
JOIN units u ON l.disbursed_unit_id = u.id;

-- Daily transaction summary view
CREATE VIEW daily_transaction_summary AS
SELECT 
    DATE(created_at) as transaction_date,
    unit_id,
    COUNT(*) as transaction_count,
    SUM(CASE WHEN transaction_type IN ('deposit', 'transfer_in') THEN amount ELSE 0 END) as total_deposits,
    SUM(CASE WHEN transaction_type IN ('withdrawal', 'transfer_out') THEN amount ELSE 0 END) as total_withdrawals,
    SUM(CASE WHEN transaction_type = 'interest' THEN amount ELSE 0 END) as total_interest,
    SUM(CASE WHEN transaction_type = 'fee' THEN amount ELSE 0 END) as total_fees
FROM savings_transactions
GROUP BY DATE(created_at), unit_id;

-- =============================================
-- CREATE STORED PROCEDURES
-- =============================================

DELIMITER //

-- Procedure to calculate member credit score
CREATE PROCEDURE CalculateMemberCreditScore(IN member_id BIGINT)
BEGIN
    DECLARE credit_score DECIMAL(5,2) DEFAULT 100.00;
    DECLARE late_payments INT DEFAULT 0;
    DECLARE total_loans_count INT DEFAULT 0;
    DECLARE total_savings_amount DECIMAL(15,2) DEFAULT 0.00;
    DECLARE outstanding_loans_amount DECIMAL(15,2) DEFAULT 0.00;
    
    -- Get member statistics
    SELECT COUNT(*) INTO late_payments
    FROM loan_payments lp
    JOIN loans l ON lp.loan_id = l.id
    WHERE l.member_id = member_id AND lp.is_late = TRUE;
    
    SELECT COUNT(*) INTO total_loans_count
    FROM loans
    WHERE member_id = member_id AND status != 'written_off';
    
    SELECT COALESCE(SUM(balance), 0) INTO total_savings_amount
    FROM member_savings
    WHERE member_id = member_id AND status = 'active';
    
    SELECT COALESCE(SUM(outstanding_total), 0) INTO outstanding_loans_amount
    FROM loans
    WHERE member_id = member_id AND status IN ('active', 'late');
    
    -- Calculate credit score
    SET credit_score = credit_score - (late_payments * 5);
    SET credit_score = credit_score + (total_savings_amount / 1000000);
    SET credit_score = credit_score - (outstanding_loans_amount / 1000000);
    
    -- Ensure score is within bounds
    IF credit_score > 100 THEN SET credit_score = 100; END IF;
    IF credit_score < 0 THEN SET credit_score = 0; END IF;
    
    -- Update member credit score
    UPDATE members 
    SET credit_score = credit_score 
    WHERE id = member_id;
END //

-- Procedure to generate daily report
CREATE PROCEDURE GenerateDailyReport(IN report_date DATE, IN unit_id INT)
BEGIN
    DECLARE total_members INT DEFAULT 0;
    DECLARE new_members INT DEFAULT 0;
    DECLARE active_members INT DEFAULT 0;
    DECLARE total_savings_amount DECIMAL(15,2) DEFAULT 0.00;
    DECLARE total_deposits_amount DECIMAL(15,2) DEFAULT 0.00;
    DECLARE total_withdrawals_amount DECIMAL(15,2) DEFAULT 0.00;
    DECLARE total_loans_disbursed_amount DECIMAL(15,2) DEFAULT 0.00;
    DECLARE total_loan_payments_amount DECIMAL(15,2) DEFAULT 0.00;
    DECLARE outstanding_loans_amount DECIMAL(15,2) DEFAULT 0.00;
    DECLARE late_loans_count INT DEFAULT 0;
    DECLARE late_loans_amount DECIMAL(15,2) DEFAULT 0.00;
    
    -- Calculate statistics
    SELECT COUNT(*) INTO total_members
    FROM members 
    WHERE registration_unit_id = unit_id AND status = 'active';
    
    SELECT COUNT(*) INTO new_members
    FROM members 
    WHERE registration_unit_id = unit_id AND DATE(registration_date) = report_date;
    
    SELECT COUNT(*) INTO active_members
    FROM members 
    WHERE registration_unit_id = unit_id AND status = 'active' 
    AND (DATE(updated_at) = report_date OR EXISTS (
        SELECT 1 FROM loans WHERE member_id = members.id AND status = 'active'
    ));
    
    SELECT COALESCE(SUM(balance), 0) INTO total_savings_amount
    FROM member_savings ms
    JOIN members m ON ms.member_id = m.id
    WHERE m.registration_unit_id = unit_id AND ms.status = 'active';
    
    SELECT COALESCE(SUM(amount), 0) INTO total_deposits_amount
    FROM savings_transactions st
    WHERE st.transaction_unit_id = unit_id AND DATE(st.transaction_date) = report_date 
    AND st.transaction_type IN ('deposit', 'transfer_in');
    
    SELECT COALESCE(SUM(amount), 0) INTO total_withdrawals_amount
    FROM savings_transactions st
    WHERE st.transaction_unit_id = unit_id AND DATE(st.transaction_date) = report_date 
    AND st.transaction_type IN ('withdrawal', 'transfer_out');
    
    SELECT COALESCE(SUM(disbursed_amount), 0) INTO total_loans_disbursed_amount
    FROM loans
    WHERE disbursed_unit_id = unit_id AND DATE(disbursed_at) = report_date;
    
    SELECT COALESCE(SUM(amount_paid), 0) INTO total_loan_payments_amount
    FROM loan_payments lp
    JOIN loans l ON lp.loan_id = l.id
    WHERE l.disbursed_unit_id = unit_id AND DATE(lp.payment_date) = report_date;
    
    SELECT COALESCE(SUM(outstanding_total), 0) INTO outstanding_loans_amount
    FROM loans
    WHERE disbursed_unit_id = unit_id AND status IN ('active', 'late');
    
    SELECT COUNT(*) INTO late_loans_count
    FROM loans
    WHERE disbursed_unit_id = unit_id AND status = 'late';
    
    SELECT COALESCE(SUM(outstanding_total), 0) INTO late_loans_amount
    FROM loans
    WHERE disbursed_unit_id = unit_id AND status = 'late';
    
    -- Insert or update daily report
    INSERT INTO daily_reports (
        unit_id, report_date, total_members, new_members, active_members,
        total_savings, total_deposits, total_withdrawals,
        total_loans_disbursed, total_loan_payments, outstanding_loans,
        late_loans_count, late_loans_amount
    ) VALUES (
        unit_id, report_date, total_members, new_members, active_members,
        total_savings_amount, total_deposits_amount, total_withdrawals_amount,
        total_loans_disbursed_amount, total_loan_payments_amount, outstanding_loans_amount,
        late_loans_count, late_loans_amount
    )
    ON DUPLICATE KEY UPDATE
        total_members = VALUES(total_members),
        new_members = VALUES(new_members),
        active_members = VALUES(active_members),
        total_savings = VALUES(total_savings),
        total_deposits = VALUES(total_deposits),
        total_withdrawals = VALUES(total_withdrawals),
        total_loans_disbursed = VALUES(total_loans_disbursed),
        total_loan_payments = VALUES(total_loan_payments),
        outstanding_loans = VALUES(outstanding_loans),
        late_loans_count = VALUES(late_loans_count),
        late_loans_amount = VALUES(late_loans_amount);
END //

DELIMITER ;

-- =============================================
-- CREATE TRIGGERS
-- =============================================

-- Trigger to update member statistics on savings transaction
DELIMITER //
CREATE TRIGGER update_member_savings_stats
AFTER INSERT ON savings_transactions
FOR EACH ROW
BEGIN
    UPDATE members m
    SET m.total_savings = (
        SELECT COALESCE(SUM(balance), 0)
        FROM member_savings ms
        WHERE ms.member_id = m.id AND ms.status = 'active'
    )
    WHERE m.id = (
        SELECT member_id FROM member_savings WHERE id = NEW.member_savings_id
    );
END //
DELIMITER ;

-- Trigger to update member statistics on loan payment
DELIMITER //
CREATE TRIGGER update_member_loan_stats
AFTER INSERT ON loan_payments
FOR EACH ROW
BEGIN
    UPDATE members m
    SET m.outstanding_loans = (
        SELECT COALESCE(SUM(outstanding_total), 0)
        FROM loans
        WHERE member_id = m.id AND status IN ('active', 'late')
    ),
    m.late_payment_count = (
        SELECT COUNT(*)
        FROM loan_payments lp
        JOIN loans l ON lp.loan_id = l.id
        WHERE l.member_id = m.id AND lp.is_late = TRUE
    )
    WHERE m.id = (
        SELECT member_id FROM loans WHERE id = NEW.loan_id
    );
END //
DELIMITER ;

-- =============================================
-- FINAL SETUP
-- =============================================

-- Create admin user (default password: admin123)
INSERT INTO users (uuid, name, email, password_hash, is_active) 
VALUES (UUID(), 'Administrator', 'admin@lamgabejaya.coop', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

-- Assign admin role to admin user in main unit
INSERT INTO user_assignments (user_id, unit_id, role_id, assigned_by)
VALUES (LAST_INSERT_ID(), 1, 1, LAST_INSERT_ID());

-- Create default notification templates
INSERT INTO notification_templates (name, type, category, subject, content, variables) VALUES
('payment_reminder', 'sms', 'payment_reminder', 'Pengingat Pembayaran', 'Yth. {member_name}, pembayaran pinjaman Anda sebesar {amount} jatuh tempo pada {due_date}. Segera lakukan pembayaran.', JSON_OBJECT('member_name', 'string', 'amount', 'currency', 'due_date', 'date')),
('welcome_member', 'email', 'welcome', 'Selamat Datang di KSP Lam Gabe Jaya', 'Selamat bergabung {member_name} sebagai anggota KSP Lam Gabe Jaya. Nomor anggota Anda: {member_number}.', JSON_OBJECT('member_name', 'string', 'member_number', 'string')),
('loan_approved', 'sms', 'alert', 'Pinjaman Disetujui', 'Pinjaman Anda sebesar {amount} telah disetujui. Silakan datang ke kantor untuk pencairan.', JSON_OBJECT('amount', 'currency'));

COMMIT;
