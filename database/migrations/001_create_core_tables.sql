-- Multi-Tenant Database Schema untuk KSP SaaS Koperasi Harian
-- Author: KSP Lam Gabe Jaya Development Team
-- Version: 1.0
-- Date: 2026-03-11

-- Create main database
CREATE DATABASE IF NOT EXISTS ksp_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ksp_saas;

-- Enable PostGIS extension (untuk GPS/spatial data)
-- CREATE EXTENSION IF NOT EXISTS postgis;

-- =============================================
-- TENANT MANAGEMENT SCHEMA
-- =============================================

-- Tenants table untuk multi-tenant architecture
CREATE TABLE tenants (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    domain VARCHAR(255) UNIQUE,
    database_name VARCHAR(255) UNIQUE NOT NULL,
    database_host VARCHAR(255) DEFAULT '127.0.0.1',
    database_port INT DEFAULT 3306,
    database_username VARCHAR(255) NOT NULL,
    database_password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'suspended', 'trial') DEFAULT 'trial',
    subscription_plan ENUM('basic', 'professional', 'enterprise') DEFAULT 'basic',
    max_users INT DEFAULT 10,
    max_storage_mb INT DEFAULT 1024,
    trial_expires_at TIMESTAMP NULL,
    subscription_expires_at TIMESTAMP NULL,
    settings JSON DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_tenants_uuid (uuid),
    INDEX idx_tenants_status (status),
    INDEX idx_tenants_subscription (subscription_plan),
    INDEX idx_tenants_trial_expires (trial_expires_at)
);

-- Tenant users untuk cross-tenant access
CREATE TABLE tenant_users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role ENUM('super_admin', 'admin', 'mantri', 'member') NOT NULL,
    permissions JSON DEFAULT '{}',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_users_tenant (tenant_id),
    INDEX idx_tenant_users_user (user_id),
    INDEX idx_tenant_users_role (role)
);

-- =============================================
-- CORE USER MANAGEMENT
-- =============================================

-- Users table (global users)
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

-- User sessions untuk authentication
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
-- TENANT-SPECIFIC TABLES (Template)
-- =============================================

-- Members table (template untuk tenant databases)
CREATE TABLE members (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    nik VARCHAR(16) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) UNIQUE,
    email VARCHAR(255),
    address TEXT,
    gps_lat DECIMAL(10,8),
    gps_lng DECIMAL(11,8),
    location POINT, -- PostGIS point untuk spatial queries
    photo_ktp_url VARCHAR(255),
    photo_selfie_url VARCHAR(255),
    birth_date DATE,
    gender ENUM('male', 'female'),
    occupation VARCHAR(255),
    monthly_income DECIMAL(12,2),
    family_members INT DEFAULT 1,
    education_level ENUM('sd', 'smp', 'sma', 'd1', 'd2', 'd3', 's1', 's2', 's3'),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    credit_score INT DEFAULT 0,
    credit_limit DECIMAL(12,2) DEFAULT 0,
    status ENUM('active', 'inactive', 'blacklisted', 'deceased') DEFAULT 'active',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_members_uuid (uuid),
    INDEX idx_members_nik (nik),
    INDEX idx_members_phone (phone),
    INDEX idx_members_status (status),
    INDEX idx_members_credit_score (credit_score),
    INDEX idx_members_location (location), -- Spatial index
    INDEX idx_members_joined (joined_at)
);

-- Mantri (Field Officers)
CREATE TABLE mantris (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    user_id BIGINT NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255),
    area_id BIGINT,
    daily_target INT DEFAULT 20,
    max_cash_limit DECIMAL(12,2) DEFAULT 5000000,
    current_cash_on_hand DECIMAL(12,2) DEFAULT 0,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    last_location POINT,
    last_location_update TIMESTAMP,
    device_info JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mantris_uuid (uuid),
    INDEX idx_mantris_code (code),
    INDEX idx_mantris_phone (phone),
    INDEX idx_mantris_status (status),
    INDEX idx_mantris_area (area_id),
    INDEX idx_mantris_location (last_location)
);

-- Areas/Regions untuk mantri assignment
CREATE TABLE areas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    boundary POLYGON, -- PostGIS polygon untuk area boundaries
    assigned_mantri_id BIGINT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_mantri_id) REFERENCES mantris(id),
    INDEX idx_areas_uuid (uuid),
    INDEX idx_areas_status (status),
    INDEX idx_areas_boundary (boundary)
);

-- =============================================
-- LOAN MANAGEMENT
-- =============================================

-- Loan applications
CREATE TABLE loan_applications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    member_id BIGINT NOT NULL,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    loan_amount DECIMAL(12,2) NOT NULL,
    loan_purpose TEXT,
    loan_term_days INT NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    monthly_payment DECIMAL(12,2) NOT NULL,
    collateral_description TEXT,
    collateral_value DECIMAL(12,2),
    guarantor_name VARCHAR(255),
    guarantor_phone VARCHAR(20),
    guarantor_relationship VARCHAR(100),
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'disbursed', 'completed', 'defaulted') DEFAULT 'draft',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by BIGINT NULL,
    approved_at TIMESTAMP NULL,
    approved_by BIGINT NULL,
    disbursed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_loan_applications_uuid (uuid),
    INDEX idx_loan_applications_number (application_number),
    INDEX idx_loan_applications_member (member_id),
    INDEX idx_loan_applications_status (status),
    INDEX idx_loan_applications_applied (applied_at)
);

-- Loan disbursements
CREATE TABLE loan_disbursements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    loan_application_id BIGINT NOT NULL,
    disbursement_amount DECIMAL(12,2) NOT NULL,
    disbursement_method ENUM('cash', 'bank_transfer', 'digital_wallet') NOT NULL,
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(255),
    bank_name VARCHAR(255),
    digital_wallet_type VARCHAR(50),
    digital_wallet_number VARCHAR(50),
    reference_number VARCHAR(100),
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    processed_at TIMESTAMP NULL,
    processed_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    INDEX idx_loan_disbursements_uuid (uuid),
    INDEX idx_loan_disbursements_loan (loan_application_id),
    INDEX idx_loan_disbursements_status (status)
);

-- Loan repayment schedules
CREATE TABLE loan_repayment_schedules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    loan_application_id BIGINT NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(12,2) NOT NULL,
    interest_amount DECIMAL(12,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('pending', 'partial_paid', 'paid', 'overdue', 'defaulted') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    INDEX idx_repayment_schedules_uuid (uuid),
    INDEX idx_repayment_schedules_loan (loan_application_id),
    INDEX idx_repayment_schedules_due (due_date),
    INDEX idx_repayment_schedules_status (status)
);

-- =============================================
-- TRANSACTIONS (IMMUTABLE LOGS)
-- =============================================

-- Immutable transaction logs
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    tenant_id BIGINT NOT NULL,
    transaction_type ENUM('loan_disbursement', 'loan_payment', 'deposit', 'withdrawal', 'fee', 'penalty', 'adjustment', 'correction') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'IDR',
    member_id BIGINT,
    mantri_id BIGINT,
    loan_application_id BIGINT,
    repayment_schedule_id BIGINT,
    reference_number VARCHAR(100),
    description TEXT,
    location POINT,
    transaction_hash VARCHAR(64) NOT NULL, -- SHA-256 hash
    previous_hash VARCHAR(64), -- Previous transaction hash
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (mantri_id) REFERENCES mantris(id) ON DELETE SET NULL,
    FOREIGN KEY (loan_application_id) REFERENCES loan_applications(id) ON DELETE SET NULL,
    FOREIGN KEY (repayment_schedule_id) REFERENCES loan_repayment_schedules(id) ON DELETE SET NULL,
    INDEX idx_transactions_uuid (uuid),
    INDEX idx_transactions_tenant (tenant_id),
    INDEX idx_transactions_type (transaction_type),
    INDEX idx_transactions_member (member_id),
    INDEX idx_transactions_mantri (mantri_id),
    INDEX idx_transactions_hash (transaction_hash),
    INDEX idx_transactions_created (created_at)
);

-- Transaction corrections (immutable)
CREATE TABLE transaction_corrections (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    original_transaction_id BIGINT NOT NULL,
    correction_amount DECIMAL(12,2) NOT NULL,
    correction_reason TEXT NOT NULL,
    approved_by BIGINT NOT NULL,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (original_transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_corrections_uuid (uuid),
    INDEX idx_corrections_original (original_transaction_id),
    INDEX idx_corrections_approved (approved_by)
);

-- =============================================
-- SAVINGS & DEPOSITS
-- =============================================

-- Savings accounts
CREATE TABLE savings_accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    member_id BIGINT NOT NULL,
    account_number VARCHAR(50) UNIQUE NOT NULL,
    account_type ENUM('regular', 'mandatory', 'voluntary', 'time_deposit') NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    interest_rate DECIMAL(5,2) DEFAULT 0,
    maturity_date DATE,
    auto_debit BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'frozen', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_savings_uuid (uuid),
    INDEX idx_savings_number (account_number),
    INDEX idx_savings_member (member_id),
    INDEX idx_savings_type (account_type),
    INDEX idx_savings_status (status)
);

-- =============================================
-- GPS & LOCATION TRACKING
-- =============================================

-- Location tracking untuk mantri
CREATE TABLE location_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    mantri_id BIGINT NOT NULL,
    location POINT NOT NULL,
    accuracy DECIMAL(5,2),
    altitude DECIMAL(8,2),
    speed DECIMAL(5,2),
    heading DECIMAL(5,2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    device_info JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mantri_id) REFERENCES mantris(id) ON DELETE CASCADE,
    INDEX idx_location_logs_uuid (uuid),
    INDEX idx_location_logs_mantri (mantri_id),
    INDEX idx_location_logs_timestamp (timestamp),
    INDEX idx_location_logs_location (location)
);

-- Geofence violations
CREATE TABLE geofence_violations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    mantri_id BIGINT NOT NULL,
    member_id BIGINT,
    transaction_id BIGINT,
    violation_type ENUM('out_of_range', 'fake_gps', 'location_disabled') NOT NULL,
    expected_location POINT,
    actual_location POINT,
    distance_meters DECIMAL(8,2),
    description TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolved_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mantri_id) REFERENCES mantris(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_geofence_violations_uuid (uuid),
    INDEX idx_geofence_violations_mantri (mantri_id),
    INDEX idx_geofence_violations_severity (severity),
    INDEX idx_geofence_violations_resolved (resolved)
);

-- =============================================
-- AUDIT LOGS
-- =============================================

-- Comprehensive audit logging
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100) NOT NULL,
    resource_id BIGINT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_audit_logs_uuid (uuid),
    INDEX idx_audit_logs_tenant (tenant_id),
    INDEX idx_audit_logs_user (user_id),
    INDEX idx_audit_logs_action (action),
    INDEX idx_audit_logs_resource (resource_type),
    INDEX idx_audit_logs_created (created_at)
);

-- =============================================
-- SYSTEM CONFIGURATION
-- =============================================

-- System settings per tenant
CREATE TABLE system_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_tenant_setting (tenant_id, setting_key),
    INDEX idx_system_settings_tenant (tenant_id),
    INDEX idx_system_settings_key (setting_key),
    INDEX idx_system_settings_public (is_public)
);

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- Spatial indexes untuk location queries
-- CREATE INDEX idx_members_location_spatial ON members USING GIST (location);
-- CREATE INDEX idx_mantris_location_spatial ON mantris USING GIST (last_location);
-- CREATE INDEX idx_areas_boundary_spatial ON areas USING GIST (boundary);

-- Composite indexes untuk common queries
CREATE INDEX idx_loan_applications_member_status ON loan_applications(member_id, status);
CREATE INDEX idx_transactions_type_member ON transactions(transaction_type, member_id);
CREATE INDEX idx_repayment_schedules_loan_due ON loan_repayment_schedules(loan_application_id, due_date);
CREATE INDEX idx_savings_member_type ON savings_accounts(member_id, account_type);

-- =============================================
-- TRIGGERS FOR DATA INTEGRITY
-- =============================================

DELIMITER //

-- Trigger untuk update transaction hash
CREATE TRIGGER before_transaction_insert 
BEFORE INSERT ON transactions
FOR EACH ROW
BEGIN
    -- Generate transaction hash
    SET NEW.transaction_hash = SHA2(
        CONCAT(
            NEW.tenant_id,
            NEW.transaction_type,
            NEW.amount,
            NEW.member_id,
            NEW.mantri_id,
            NEW.created_by,
            NEW.created_at,
            COALESCE(NEW.previous_hash, '')
        ),
        256
    );
END//

-- Trigger untuk update timestamps
CREATE TRIGGER update_timestamps 
BEFORE UPDATE ON members
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

DELIMITER ;

-- =============================================
-- SAMPLE DATA FOR TESTING
-- =============================================

-- Insert sample tenant
INSERT INTO tenants (uuid, name, slug, database_name, database_username, database_password, status) VALUES
('550e8400-e29b-41d4-a716-446655440000', 'KSP Lam Gabe Jaya', 'ksp-lam-gabe-jaya', 'ksp_lam_gabe_jaya', 'ksp_user', 'secure_password', 'active');

-- Insert sample user
INSERT INTO users (uuid, name, email, phone, password_hash) VALUES
('550e8400-e29b-41d4-a716-446655440001', 'Admin User', 'admin@ksp-lamgabejaya.id', '08123456789', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- =============================================
-- MIGRATION NOTES
-- =============================================

/*
Migration Steps:
1. Run this SQL on main database
2. Create tenant-specific databases
3. Copy tenant-specific tables to each tenant database
4. Setup Row-Level Security (RLS) policies
5. Configure spatial indexes for PostGIS
6. Setup triggers for data integrity
7. Create backup procedures
8. Test with sample data

Performance Considerations:
- Use partitioning untuk large tables (transactions, audit_logs)
- Implement proper indexing strategy
- Use connection pooling
- Configure read replicas untuk reporting
- Implement caching strategies

Security Considerations:
- Enable Row-Level Security (RLS)
- Implement proper user permissions
- Use encrypted connections
- Regular security audits
- Data encryption at rest and in transit
*/
