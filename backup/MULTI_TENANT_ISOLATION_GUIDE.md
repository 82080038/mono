# Unit-Based Data Isolation Implementation Guide

## 🚨 CRITICAL SECURITY ISSUE

**Current Status:** ❌ **NOT SECURE** - Data isolation is NOT implemented
**Required Action:** Execute the fix scripts immediately before production deployment

---

## 📋 Table of Contents

1. [Problem Analysis](#problem-analysis)
2. [Solution Overview](#solution-overview) 
3. [Implementation Steps](#implementation-steps)
4. [Testing Procedures](#testing-procedures)
5. [Security Considerations](#security-considerations)
6. [Application Code Changes](#application-code-changes)
7. [Monitoring & Maintenance](#monitoring--maintenance)

---

## 🔍 Problem Analysis

### Current Issues

1. **Missing `unit_id` Columns**
   - Core tables (`users`, `members`, `loans`, etc.) lack unit isolation
   - Users can potentially access data from other units
   - No database-level enforcement of data separation

2. **Inconsistent Schema**
   - `sql/maruba.sql` (production) ❌ No unit_id columns
   - `App/schema.sql` (reference) ✅ Has unit_id columns
   - Database structure doesn't match application expectations

3. **Security Vulnerability**
   - Unit A can view/modify Unit B's data
   - No row-level security implementation
   - Audit trail doesn't track unit context

### Impact Assessment

- **Risk Level:** 🔴 **CRITICAL**
- **Data Exposure:** All unit data accessible
- **Compliance:** Violates data privacy regulations
- **Production Readiness:** ❌ **NOT READY**

---

## 🛠️ Solution Overview

### Architecture Strategy

**Approach:** Row-Level Security with `unit_id` columns

**Benefits:**
- ✅ Complete data isolation
- ✅ Scalable to unlimited units
- ✅ Maintains single database architecture
- ✅ Preserves existing application logic

### Implementation Components

1. **Database Schema Updates**
   - Add `unit_id` columns to all tables
   - Create foreign key constraints
   - Add performance indexes

2. **Data Migration**
   - Assign existing data to appropriate units
   - Create sample unit structure
   - Preserve data integrity

3. **Application Layer**
   - Implement unit context switching
   - Update all queries with unit filtering
   - Add unit validation middleware

---

## 📝 Implementation Steps

### Step 1: Database Schema Fix

**Execute:** `sql/unit_isolation_fix.sql`

```sql
-- Critical tables that need unit_id:
- users (most critical)
- members
- loans
- products
- savings_* tables
- accounting tables
- audit_logs
```

**Expected Results:**
- ✅ All tables have `unit_id` column
- ✅ Foreign key constraints to `units` table
- ✅ Proper indexing for performance

### Step 2: Data Migration

**Execute:** `sql/unit_data_migration.sql`

```sql
-- Migration tasks:
- Create sample units
- Assign existing data to Unit 1
- Set up cooperative_admins mapping
- Create navigation menus per unit
```

**Expected Results:**
- ✅ Existing data properly assigned to units
- ✅ System admin retains cross-unit access
- ✅ New units start with clean data

### Step 3: Validation Testing

**Execute:** `sql/unit_isolation_test.sql`

```sql
-- Test scenarios:
- Basic data isolation
- Cross-unit access attempts
- Security validation
- Performance impact
```

**Expected Results:**
- ✅ Units can only access their own data
- ✅ System admin can access all data
- ✅ No performance degradation

---

## 🧪 Testing Procedures

### Pre-Deployment Checklist

- [ ] Database backup created
- [ ] Schema fix script tested in staging
- [ ] Data migration validated
- [ ] All isolation tests pass
- [ ] Performance benchmarks met
- [ ] Rollback procedure tested

### Test Scenarios

#### Scenario 1: Normal User Access
```sql
-- Unit 1 user should only see Unit 1 data
SELECT * FROM members WHERE unit_id = 1;
-- Expected: 3-5 records

-- Unit 2 user should see no data initially  
SELECT * FROM members WHERE unit_id = 2;
-- Expected: 0 records
```

#### Scenario 2: System Admin Access
```sql
-- System admin should see all data
SELECT * FROM members;
-- Expected: All records across all units
```

#### Scenario 3: Cross-Unit Access Attempt
```sql
-- This should be prevented by application code
SELECT * FROM members WHERE unit_id != :current_unit_id;
-- Expected: Empty result or application error
```

### Performance Validation

```sql
-- Verify index usage
EXPLAIN SELECT * FROM members WHERE unit_id = 1;
-- Expected: Using index (idx_members_unit)

-- Compare query performance
-- Before: Full table scan
-- After: Index seek on unit_id
```

---

## 🔒 Security Considerations

### Database-Level Security

1. **Row-Level Security**
   - `unit_id` column in all tables
   - Foreign key constraints prevent orphaned data
   - Indexes ensure efficient filtering

2. **Access Control**
   - System admin: `unit_id = NULL`
   - Unit users: `unit_id = specific_unit`
   - Application enforces unit context

3. **Audit Trail**
   - `audit_logs` table includes unit context
   - All actions tracked with unit_id
   - Cross-unit access attempts logged

### Application-Level Security

1. **Middleware Implementation**
   ```php
   // UnitMiddleware sets context
   UnitMiddleware::setUnit($unit_id);
   
   // Database connection respects context
   Database::getConnection(); // Auto-filters by unit
   ```

2. **Query Filtering**
   ```php
   // All queries must include unit filtering
   $sql = "SELECT * FROM members WHERE unit_id = :unit_id AND ...";
   ```

3. **Validation Layer**
   ```php
   // Validate user access to unit data
   if (!$user->canAccessUnit($unit_id)) {
       throw new AccessDeniedException();
   }
   ```

---

## 💻 Application Code Changes

### Required Modifications

#### 1. Database Class Updates
```php
// App/src/Database.php already supports multi-unit
// Ensure UnitMiddleware is properly implemented
```

#### 2. Model/Repository Updates
```php
// Add unit filtering to all queries
class MemberRepository {
    public function findByUnit($unitId) {
        $sql = "SELECT * FROM members WHERE unit_id = ?";
        return $this->db->query($sql, [$unitId]);
    }
}
```

#### 3. Controller Updates
```php
// Ensure all controllers respect unit context
class MemberController {
    public function index() {
        $unitId = UnitMiddleware::getCurrentUnit();
        $members = $this->memberRepository->findByUnit($unitId);
        return view('members.index', compact('members'));
    }
}
```

#### 4. Middleware Implementation
```php
// App/Middleware/UnitMiddleware.php
class UnitMiddleware {
    public static function setCurrentUnit($unitId) {
        // Set unit context for database connection
        // Validate user access to unit
        // Switch database connection if needed
    }
}
```

### Query Examples

#### Before (INSECURE):
```sql
SELECT * FROM users WHERE role = 'admin';
SELECT * FROM members WHERE status = 'active';
SELECT * FROM loans WHERE amount > 1000000;
```

#### After (SECURE):
```sql
SELECT * FROM users WHERE unit_id = ? AND role = 'admin';
SELECT * FROM members WHERE unit_id = ? AND status = 'active';  
SELECT * FROM loans WHERE unit_id = ? AND amount > 1000000;
```

---

## 📊 Monitoring & Maintenance

### Key Metrics to Monitor

1. **Security Metrics**
   - Cross-unit access attempts
   - Failed unit validation
   - Audit log anomalies

2. **Performance Metrics**
   - Query execution time with unit filtering
   - Index usage statistics
   - Database connection pool efficiency

3. **Data Integrity Metrics**
   - Orphaned records (unit_id pointing to deleted unit)
   - Data consistency across related tables
   - Backup/restore validation

### Regular Maintenance Tasks

1. **Daily**
   - Monitor audit logs for security violations
   - Check query performance
   - Validate unit access patterns

2. **Weekly**
   - Review unit data growth
   - Optimize indexes if needed
   - Backup unit-specific data

3. **Monthly**
   - Security audit of unit isolation
   - Performance benchmarking
   - Data integrity validation

### Alerting Rules

```yaml
alerts:
  - name: "Cross-unit Access Attempt"
    condition: "audit_logs.action = 'unauthorized_unit_access'"
    severity: "critical"
    
  - name: "Query Performance Degradation"  
    condition: "avg_query_time > 1000ms"
    severity: "warning"
    
  - name: "Data Integrity Issue"
    condition: "orphaned_records > 0"
    severity: "high"
```

---

## 🚀 Deployment Strategy

### Pre-Deployment

1. **Environment Setup**
   ```bash
   # Create database backup
   mysqldump -u root -p maruba > backup_before_unit_fix.sql
   
   # Test in staging environment
   mysql -u root -p maruba_staging < unit_isolation_fix.sql
   ```

2. **Validation**
   ```bash
   # Run isolation tests
   mysql -u root -p maruba_staging < unit_isolation_test.sql
   
   # Verify results
   # All tests should pass
   ```

### Deployment Steps

1. **Maintenance Window**
   - Schedule downtime (estimated: 30-60 minutes)
   - Notify all users
   - Put application in maintenance mode

2. **Execute Scripts**
   ```bash
   # Step 1: Schema fix
   mysql -u root -p maruba < sql/unit_isolation_fix.sql
   
   # Step 2: Data migration  
   mysql -u root -p maruba < sql/unit_data_migration.sql
   
   # Step 3: Validation
   mysql -u root -p maruba < sql/unit_isolation_test.sql
   ```

3. **Application Deployment**
   - Deploy updated application code
   - Restart application services
   - Clear caches

4. **Post-Deployment**
   - Run smoke tests
   - Monitor system performance
   - Validate unit access

### Rollback Plan

```bash
# If issues occur, rollback using:
mysql -u root -p maruba < backup_before_unit_fix.sql

# Or use specific rollback script (create one based on unit_isolation_fix.sql)
```

---

## 📞 Support & Troubleshooting

### Common Issues

#### Issue: "Users can't see any data after migration"
**Solution:** Check if users are properly assigned to units
```sql
SELECT u.username, u.unit_id, t.name as unit_name
FROM users u
LEFT JOIN units t ON u.unit_id = t.id;
```

#### Issue: "Performance degradation after unit columns"
**Solution:** Verify indexes are properly created
```sql
SHOW INDEX FROM members WHERE Key_name LIKE '%unit%';
```

#### Issue: "Cross-unit data still visible"
**Solution:** Update application code to include unit filtering
```php
// Ensure all queries include unit_id
$unitId = UnitMiddleware::getCurrentUnit();
```

### Emergency Contacts

- **Database Administrator:** [DBA Contact]
- **Application Developer:** [Dev Contact]  
- **Security Team:** [Security Contact]

---

## ✅ Success Criteria

### Functional Requirements
- [ ] Units can only access their own data
- [ ] System admin can access all unit data
- [ ] Application enforces unit isolation
- [ ] No data leakage between units

### Non-Functional Requirements  
- [ ] No significant performance degradation
- [ ] All existing functionality preserved
- [ ] Audit trail maintains unit context
- [ ] Backup/restore procedures work correctly

### Security Requirements
- [ ] Row-level security implemented
- [ ] Cross-unit access prevented
- [ ] Audit logs track unit context
- [ ] Compliance with data regulations

---

**Status:** 🟡 **READY FOR IMPLEMENTATION**
**Priority:** 🔴 **CRITICAL - Execute immediately**
**Timeline:** Complete within 24 hours before production deployment
