<?php
require 'config.php';
$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS members (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name TEXT NOT NULL,
    ktp_no VARCHAR(255) UNIQUE,
    address TEXT,
    phone VARCHAR(255),
    occupation TEXT,
    join_date DATE,
    status VARCHAR(255) DEFAULT 'active'
);");
$db->exec("CREATE TABLE IF NOT EXISTS loans (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    member_id INTEGER,
    amount DECIMAL(10,2),
    purpose TEXT,
    term_months INTEGER,
    interest_rate DECIMAL(5,2),
    start_date DATE,
    status VARCHAR(255) DEFAULT 'pending',
    FOREIGN KEY (member_id) REFERENCES members (id)
);");
$db->exec("CREATE TABLE IF NOT EXISTS loan_payments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    loan_id INTEGER,
    amount DECIMAL(10,2),
    date DATE,
    type VARCHAR(255),
    FOREIGN KEY (loan_id) REFERENCES loans (id)
);");
$db->exec("CREATE TABLE IF NOT EXISTS job_applications (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name TEXT,
    birth_place_date TEXT,
    address TEXT,
    phone VARCHAR(255),
    education TEXT,
    position TEXT,
    submitted_date DATETIME DEFAULT CURRENT_TIMESTAMP
);");
$db->exec("CREATE TABLE IF NOT EXISTS agreements (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    loan_id INTEGER,
    representative_name TEXT,
    borrower_name TEXT,
    borrower_ktp VARCHAR(255),
    borrower_address TEXT,
    borrower_phone VARCHAR(255),
    loan_amount DECIMAL(10,2),
    term_months INTEGER,
    interest_rate DECIMAL(5,2),
    collateral TEXT,
    FOREIGN KEY (loan_id) REFERENCES loans (id)
);");
?>
