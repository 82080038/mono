import Database from 'better-sqlite3'

const db = new Database('./ksp.db')

db.exec(`
CREATE TABLE IF NOT EXISTS members (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  ktp_no TEXT UNIQUE,
  address TEXT,
  phone TEXT,
  occupation TEXT,
  join_date TEXT,
  status TEXT DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS loans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  member_id INTEGER,
  amount REAL,
  purpose TEXT,
  term_months INTEGER,
  interest_rate REAL,
  start_date TEXT,
  status TEXT DEFAULT 'pending',
  FOREIGN KEY (member_id) REFERENCES members (id)
);

CREATE TABLE IF NOT EXISTS loan_payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  loan_id INTEGER,
  amount REAL,
  date TEXT,
  type TEXT,
  FOREIGN KEY (loan_id) REFERENCES loans (id)
);

CREATE TABLE IF NOT EXISTS job_applications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  birth_place_date TEXT,
  address TEXT,
  phone TEXT,
  education TEXT,
  position TEXT,
  submitted_date TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agreements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  loan_id INTEGER,
  representative_name TEXT,
  borrower_name TEXT,
  borrower_ktp TEXT,
  borrower_address TEXT,
  borrower_phone TEXT,
  loan_amount REAL,
  term_months INTEGER,
  interest_rate REAL,
  collateral TEXT,
  FOREIGN KEY (loan_id) REFERENCES loans (id)
);
`)

export default db
