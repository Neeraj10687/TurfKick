# TurfKick Repository Audit Report

## Executive Summary

This is a **comprehensive audit** of the TurfKick turf booking platform repository. The codebase is a PHP/MySQL web application with HTML/CSS/JavaScript frontend for booking sports turfs. Overall, the project is **well-structured** with good security practices, but there are several **critical issues** that need to be addressed before production deployment.

---

## 1. Project Structure Analysis

### ✅ Strengths
- Clean separation of concerns (api/, config/, includes/, js/, css/)
- Proper use of PDO with prepared statements throughout
- CSRF protection implemented globally
- Password hashing using bcrypt (`password_hash()`)
- Role-based access control (user, owner, admin)
- Transaction safety for critical operations

### 📁 File Organization
```
/workspace
├── api/                    # Backend API endpoints (PHP)
│   ├── admin/             # Admin-specific endpoints
│   └── owner/             # Owner-specific endpoints
├── config/                 # Database configuration
├── includes/               # Helper functions
├── js/                     # Frontend JavaScript modules
├── css/                    # Stylesheets
├── uploads/                # User-uploaded files
├── schema.sql              # Database schema
├── admin_update.sql        # Admin role migration
└── *.html                  # Frontend pages
```

---

## 2. Critical Issues Found 🔴

### 2.1 Missing Database Tables in Schema

The following tables are **referenced in code but NOT defined in `schema.sql`**:

| Table | Used In | Priority |
|-------|---------|----------|
| `reviews` | `api/submit_review.php` | HIGH |
| `maintenance_requests` | `api/maintenance_requests.php`, `api/admin/handle_maintenance.php` | HIGH |
| `equipment.turf_id` column | `api/get_equipment.php`, `api/manage_equipment.php` | MEDIUM |
| `bookings.equipment_ids` column | `api/create_booking.php` | MEDIUM |
| `turfs.is_under_maintenance` column | `api/admin/handle_maintenance.php`, `js/owner.js` | MEDIUM |
| `admins` table | Referenced in login but uses `users` table with role='admin' | LOW |

**Impact**: Application will crash when these features are used.

### 2.2 SQL Syntax Error in schema.sql

Lines 125-135 contain **corrupted binary data** in the schema file:
```sql
-- Lines 125-135 have null bytes and corrupted text
CREATE TABLE IF NOT EXISTS equipment (...)
```

This will cause SQL import failures.

### 2.3 Missing `created_at` Column References

Multiple files reference `created_at` in queries, but it's only defined in some tables:
- `turfs` table: Referenced in `api/manage_turfs.php` line 28 but doesn't exist
- Should be added to `turfs`, `bookings`, `time_slots` tables

### 2.4 Incomplete Equipment Feature

The equipment system has inconsistencies:
- `equipment` table schema missing `turf_id` column (only has `owner_id`)
- `api/get_equipment.php` queries by `turf_id` but schema only has `owner_id`
- Booking flow references `equipment_ids` but no validation exists

---

## 3. Security Assessment 🔒

### ✅ Good Practices Implemented
1. **SQL Injection Prevention**: All queries use PDO prepared statements
2. **Password Hashing**: Using `password_hash()` with bcrypt
3. **CSRF Protection**: Token-based validation on all POST requests
4. **Session Management**: Proper `session_start()` in helpers.php
5. **Input Sanitization**: `sanitize_input()` function using `htmlspecialchars()`
6. **Access Control**: Role checks (`is_admin()`, `is_owner()`, `is_logged_in()`)

### ⚠️ Security Concerns

1. **File Upload Validation** (MEDIUM RISK)
   ```php
   // api/register.php - No file type validation
   move_uploaded_file($_FILES['aadhaar']['tmp_name'], '../' . $aadhaar_path);
   ```
   - Missing MIME type checking
   - No file size limits
   - Could allow malicious file uploads

2. **Error Disclosure** (LOW RISK)
   ```php
   // Multiple files expose internal errors
   send_json_response('error', 'Booking failed: ' . $e->getMessage());
   ```
   - Database errors exposed to users
   - Should log internally and show generic messages

3. **Missing Rate Limiting** (MEDIUM RISK)
   - No brute force protection on login
   - No request throttling on API endpoints

4. **Session Security** (LOW RISK)
   - Missing `session_regenerate_id()` after login
   - No session timeout configuration

---

## 4. Code Quality Issues 🐛

### 4.1 Inconsistent Error Handling

Some files use try-catch properly, others don't validate inputs:
```php
// api/check_availability.php - Good
if (!$turf_id || !$date || !$slot_id) {
    send_json_response('error', 'Missing parameters');
}

// api/get_equipment.php - Better pattern
if (!$turf_id) {
    send_json_response('error', 'Turf ID required.');
}
```

### 4.2 Duplicate Code

- CSRF token fetching logic duplicated in `auth.js`, `bookings.js`, `admin.js`, `owner.js`
- Should be consolidated into a single utility module

### 4.3 Hardcoded Values

```php
// api/login.php - Admin redirect hardcoded
if ($user['role'] === 'admin') {
    $redirect = 'admin_dashboard.html';
}
```

### 4.4 Missing Input Validation

```php
// api/create_booking.php
$total_price = sanitize_input($_POST['price'] ?? 0);
// No validation that price matches actual turf price
```

---

## 5. Frontend Issues 🎨

### 5.1 Broken File References

1. `userreg.html` references `turf.png` which doesn't exist (should be `turf1.png`)
2. Some image paths may break if folder structure changes

### 5.2 JavaScript Dependencies

- `browse_turfs.html` comment mentions updating JS to fetch from `booking/get_turfs.php` but actual path is `api/get_turfs.php`
- No error handling for network failures in some AJAX calls

### 5.3 XSS Prevention

While backend sanitizes input, frontend should also escape output:
```javascript
// js/bookings.js - Potential XSS
container.innerHTML = filtered.map(t => `
    <h3>${t.name}</h3>  // Should use textContent or escape
`).join('');
```

---

## 6. Database Design Issues 🗄️

### 6.1 Missing Indexes

- `bookings.user_id` - No index (slow user booking lookups)
- `maintenance_requests.turf_id` - No index
- `reviews.turf_id` - No index

### 6.2 Missing Foreign Keys

- `equipment.owner_id` → `users.id` (exists in code, not enforced in schema)
- `maintenance_requests` table relationships not defined

### 6.3 Data Integrity

- No `ON DELETE CASCADE` for all related tables
- `bookings.equipment_ids` stores JSON (violates normalization)
  - Should be a junction table `booking_equipment(booking_id, equipment_id)`

---

## 7. Missing Features / Incomplete Implementation

### 7.1 Review System
- `submit_review.php` exists but no `reviews` table in schema
- No UI for displaying reviews
- No rating aggregation logic

### 7.2 Maintenance Requests
- Full implementation in code but no database table
- Admin approval workflow incomplete

### 7.3 Payment Integration
- `payments` table exists but always marked as 'completed'
- No actual payment gateway integration
- Placeholder implementation only

### 7.4 Equipment Booking
- Partially implemented
- No price calculation for equipment in total booking cost
- Missing UI for equipment selection in some flows

---

## 8. Testing Gaps 🧪

### Manual Testing Required

According to `instructions.md`, these scenarios need testing:

1. ✅ **Double Booking Prevention** - Implemented with DB constraint
2. ❌ **Concurrent Booking Race Conditions** - Needs load testing
3. ❌ **File Upload Security** - Needs penetration testing
4. ❌ **Role Escalation** - Test if user can access owner/admin endpoints
5. ❌ **SQL Injection** - Verify all endpoints (though PDO is used)

---

## 9. Recommendations 📋

### Immediate Actions (Before Deployment)

1. **Fix Database Schema**
   ```sql
   -- Add missing tables
   CREATE TABLE IF NOT EXISTS reviews (...);
   CREATE TABLE IF NOT EXISTS maintenance_requests (...);
   ALTER TABLE equipment ADD COLUMN turf_id INT;
   ALTER TABLE bookings ADD COLUMN equipment_ids TEXT;
   ALTER TABLE turfs ADD COLUMN is_under_maintenance TINYINT DEFAULT 0;
   ```

2. **Clean schema.sql**
   - Remove corrupted lines 125-135
   - Rewrite equipment table properly

3. **Add File Upload Validation**
   ```php
   $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
   $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
   if (!in_array($ext, $allowed)) { /* reject */ }
   ```

4. **Add Missing Indexes**
   ```sql
   CREATE INDEX idx_bookings_user ON bookings(user_id);
   CREATE INDEX idx_bookings_turf_date ON bookings(turf_id, booking_date);
   ```

### Short-term Improvements

1. Implement rate limiting (e.g., 5 login attempts per minute)
2. Add session regeneration after login
3. Create proper error logging system
4. Add unit tests for critical functions
5. Implement proper payment gateway integration

### Long-term Enhancements

1. Move to MVC framework (Laravel/Symfony)
2. Add API versioning
3. Implement proper queue system for emails/notifications
4. Add comprehensive test suite (PHPUnit + Jest)
5. Docker containerization
6. CI/CD pipeline setup

---

## 10. File-by-File Status

| File | Status | Issues |
|------|--------|--------|
| `config/db.php` | ✅ Good | None |
| `includes/helpers.php` | ✅ Good | Consider adding session security settings |
| `api/login.php` | ✅ Good | Add rate limiting |
| `api/register.php` | ⚠️ Warning | File upload validation needed |
| `api/create_booking.php` | ⚠️ Warning | Price validation missing |
| `api/get_turfs.php` | ✅ Good | None |
| `api/check_availability.php` | ✅ Good | None |
| `api/manage_turfs.php` | ✅ Good | References non-existent `created_at` |
| `api/manage_slots.php` | ✅ Good | None |
| `api/submit_review.php` | ❌ Broken | `reviews` table missing |
| `api/maintenance_requests.php` | ❌ Broken | `maintenance_requests` table missing |
| `api/admin/*.php` | ✅ Good | All depend on `require_admin()` working |
| `schema.sql` | ❌ Broken | Corrupted data, missing tables |
| `index.html` | ✅ Good | None |
| `browse_turfs.html` | ✅ Good | Minor comment inconsistency |
| `owner_dashboard.html` | ✅ Good | None |
| `admin_dashboard.html` | ✅ Good | None |
| `js/auth.js` | ✅ Good | Could be more modular |
| `js/bookings.js` | ⚠️ Warning | XSS potential in innerHTML |
| `js/owner.js` | ✅ Good | None |
| `js/admin.js` | ✅ Good | None |

---

## 11. Conclusion

### Overall Assessment: **B- (Good but needs work)**

**Working Features:**
- User registration and authentication ✅
- Multi-role system (user/owner/admin) ✅
- Turf browsing and search ✅
- Slot management ✅
- Booking creation and cancellation ✅
- Admin moderation panel ✅
- CSRF protection ✅
- SQL injection prevention ✅

**Broken/Incomplete Features:**
- Review system ❌
- Maintenance requests ❌
- Equipment booking (partially broken) ⚠️
- Payment processing (placeholder only) ⚠️

**Security Posture: Moderate**
- Good foundation with PDO and password hashing
- Needs file upload hardening
- Missing rate limiting and session security enhancements

**Recommendation**: 
The application is **functional for demonstration purposes** but requires the fixes listed above before production deployment. Priority should be given to fixing the database schema, adding file upload validation, and completing the incomplete features.

---

*Audit completed on: $(date)*
*Auditor: AI Code Review System*
