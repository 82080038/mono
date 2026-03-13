-- Single Cooperative Database Schema for KSP Lam Gabe Jaya
-- Simplified schema without complex indexes and views for initial setup

SET FOREIGN_KEY_CHECKS = 0;

-- Core Tables
CREATE TABLE cooperatives (
    id INT PRIMARY KEY DEFAULT 1,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL DEFAULT 'KSP Lam Gabe Jaya',
    code VARCHAR(50) NOT NULL DEFAULT 'LAMGABE',
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    establishment_date DATE NOT NULL,
    settings JSON DEFAULT (JSON_OBJECT()),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    cooperative_id INT DEFAULT 1,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    type ENUM('main', 'branch', 'sub_branch') DEFAULT 'main',
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cooperative_id) REFERENCES cooperatives(id)
);

CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(32),
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

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

CREATE TABLE user_assignments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    unit_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by BIGINT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (role_id) REFERENCES user_roles(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

CREATE TABLE members (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    member_number VARCHAR(50) NOT NULL UNIQUE,
    user_id BIGINT UNIQUE,
    nik VARCHAR(16) UNIQUE,
    name VARCHAR(255) NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    birth_place VARCHAR(100),
    birth_date DATE,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    occupation VARCHAR(100),
    company_name VARCHAR(255),
    company_address TEXT,
    monthly_income DECIMAL(15,2),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    spouse_name VARCHAR(255),
    spouse_phone VARCHAR(20),
    spouse_occupation VARCHAR(100),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relation VARCHAR(50),
    ktp_photo VARCHAR(255),
    kk_photo VARCHAR(255),
    signature_photo VARCHAR(255),
    credit_score DECIMAL(5,2) DEFAULT 50.00,
    is_active BOOLEAN DEFAULT TRUE,
    is_blacklisted BOOLEAN DEFAULT FALSE,
    blacklist_reason TEXT,
    registration_date DATE NOT NULL,
    unit_id INT NOT NULL,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE savings_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('mandatory', 'voluntary', 'education', 'retirement') NOT NULL,
    description TEXT,
    minimum_balance DECIMAL(15,2) DEFAULT 0.00,
    minimum_deposit DECIMAL(15,2) DEFAULT 0.00,
    maximum_deposit DECIMAL(15,2) DEFAULT 999999999.99,
    interest_rate_monthly DECIMAL(5,4) DEFAULT 0.0000,
    tax_rate DECIMAL(5,4) DEFAULT 0.0000,
    withdrawal_fee DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE savings_accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    member_id BIGINT NOT NULL,
    product_id INT NOT NULL,
    account_number VARCHAR(50) NOT NULL UNIQUE,
    balance DECIMAL(15,2) DEFAULT 0.00,
    frozen_amount DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    is_frozen BOOLEAN DEFAULT FALSE,
    freeze_reason TEXT,
    opened_date DATE NOT NULL,
    closed_date DATE,
    unit_id INT NOT NULL,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (product_id) REFERENCES savings_products(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE loan_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('productive', 'consumptive', 'emergency', 'education') NOT NULL,
    description TEXT,
    minimum_amount DECIMAL(15,2) NOT NULL,
    maximum_amount DECIMAL(15,2) NOT NULL,
    minimum_term_months INT NOT NULL,
    maximum_term_months INT NOT NULL,
    interest_rate_monthly DECIMAL(5,4) NOT NULL,
    late_fee_rate DECIMAL(5,4) DEFAULT 0.0000,
    admin_fee_rate DECIMAL(5,4) DEFAULT 0.0000,
    admin_fee_amount DECIMAL(15,2) DEFAULT 0.00,
    collateral_required BOOLEAN DEFAULT FALSE,
    collateral_types JSON,
    credit_score_min DECIMAL(5,2) DEFAULT 0.00,
    grace_period_days INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE loans (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL DEFAULT (UUID()),
    loan_number VARCHAR(50) NOT NULL UNIQUE,
    member_id BIGINT NOT NULL,
    product_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,4) NOT NULL,
    term_months INT NOT NULL,
    admin_fee DECIMAL(15,2) DEFAULT 0.00,
    disbursement_amount DECIMAL(15,2) NOT NULL,
    purpose TEXT,
    collateral_description TEXT,
    status ENUM('pending', 'approved', 'disbursed', 'active', 'late', 'default', 'completed', 'rejected') DEFAULT 'pending',
    application_date DATE NOT NULL,
    approval_date DATE,
    disbursement_date DATE,
    due_date DATE,
    completed_date DATE,
    rejected_reason TEXT,
    approved_by BIGINT,
    disbursed_by BIGINT,
    unit_id INT NOT NULL,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (product_id) REFERENCES loan_products(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (disbursed_by) REFERENCES users(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert Default Data
INSERT INTO cooperatives (name, code, address, phone, email, establishment_date) VALUES
('KSP Lam Gabe Jaya', 'LAMGABE', 'Jl. Raya Lam Gabe No. 123, Kec. Lam Gabe, Kab. Someplace', '08123456789', 'info@lamgabejaya.coop', '2020-01-15');

INSERT INTO units (uuid, name, code, type, address, phone, email) VALUES
(UUID(), 'Kantor Pusat', 'MAIN', 'main', 'Jl. Raya Lam Gabe No. 123, Kec. Lam Gabe, Kab. Someplace', '08123456789', 'info@lamgabejaya.coop');

INSERT INTO user_roles (name, display_name, description, permissions, is_system_role) VALUES
('super_admin', 'Super Administrator', 'Full system access', JSON_OBJECT('all', true), TRUE),
('admin', 'Administrator', 'Administrative access', JSON_OBJECT('users', JSON_ARRAY('read', 'write', 'delete'), 'members', JSON_ARRAY('read', 'write', 'delete'), 'loans', JSON_ARRAY('read', 'write', 'delete'), 'savings', JSON_ARRAY('read', 'write', 'delete'), 'reports', JSON_ARRAY('read', 'write')), TRUE),
('mantri', 'Petugas Lapangan', 'Field officer access', JSON_OBJECT('members', JSON_ARRAY('read', 'write'), 'loans', JSON_ARRAY('read', 'write'), 'savings', JSON_ARRAY('read', 'write'), 'payments', JSON_ARRAY('read', 'write')), TRUE),
('member', 'Anggota', 'Member access', JSON_OBJECT('profile', JSON_ARRAY('read', 'write'), 'savings', JSON_ARRAY('read'), 'loans', JSON_ARRAY('read', 'write'), 'payments', JSON_ARRAY('read', 'write')), TRUE);

INSERT INTO users (uuid, name, email, password_hash, is_active) VALUES
(UUID(), 'Administrator', 'admin@lamgabejaya.coop', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

INSERT INTO user_assignments (user_id, unit_id, role_id, assigned_by) VALUES
(1, 1, 1, 1);

INSERT INTO savings_products (uuid, name, code, type, description, minimum_balance, minimum_deposit, interest_rate_monthly, is_active) VALUES
(UUID(), 'Simpanan Wajib', 'WAJIB', 'mandatory', 'Simpanan wajib bulanan anggota', 10000.00, 10000.00, 0.0000, TRUE),
(UUID(), 'Simpanan Sukarela', 'SUKARELA', 'voluntary', 'Simpanan sukarela dengan bunga', 0.00, 10000.00, 0.0025, TRUE),
(UUID(), 'Simpanan Pendidikan', 'PENDIDIKAN', 'education', 'Tabungan pendidikan anak', 100000.00, 50000.00, 0.0030, TRUE);

INSERT INTO loan_products (uuid, name, code, type, description, minimum_amount, maximum_amount, minimum_term_months, maximum_term_months, interest_rate_monthly, collateral_required, credit_score_min, is_active) VALUES
(UUID(), 'Pinjaman Produktif', 'PRODUKTIF', 'productive', 'Pinjaman untuk modal usaha', 1000000.00, 50000000.00, 3, 36, 0.0150, TRUE, 60.00, TRUE),
(UUID(), 'Pinjaman Konsumtif', 'KONSUMTIF', 'consumptive', 'Pinjaman untuk kebutuhan konsumtif', 500000.00, 20000000.00, 1, 24, 0.0200, TRUE, 50.00, TRUE),
(UUID(), 'Pinjaman Darurat', 'DARURAT', 'emergency', 'Pinjaman darurat cepat cair', 500000.00, 10000000.00, 1, 12, 0.0250, FALSE, 40.00, TRUE);

SET FOREIGN_KEY_CHECKS = 1;
