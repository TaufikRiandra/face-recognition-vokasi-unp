# IMPLEMENTASI KOLOM KETERANGAN ATTENDANCE

## 📋 Ringkasan Fitur
Tambahan database schema untuk melacak status attendance dengan automatic categorization:
- **Terlambat**: Waktu masuk melampaui jam standar + toleransi
- **Lembur**: Waktu pulang melampaui jam standar pulang
- **Tepat Waktu**: Dalam jam yang ditentukan

---

## 🗄️ File yang Dibuat/Diupdate

### 1. Backend Helper Functions
**File:** `backend/helpers_attendance.php` (NEW)
- `hitungKeterangan()` - Compute keterangan based on time
- `getKeteranganBadge()` - Get styling for each keterangan type
- `getKeteranganHTML()` - Generate HTML badge with colors
- `getStatisticKeterangan()` - Get attendance statistics by keterangan

### 2. Attendance Processing
**File:** `backend/process_attendance.php` (UPDATED)
- Include `helpers_attendance.php`
- Auto-compute keterangan when inserting attendance
- Both `save_embedding` and `submit_attendance` actions now calculate keterangan

### 3. Migration Execution
**File:** `backend/execute_migration.php` (NEW)
- Execute `database/migration_add_keterangan.sql`
- Requires admin authentication
- Returns JSON with status and errors

### 4. Dashboard Display
**File:** `pages/dashboard.php` (UPDATED)
- Include `helpers_attendance.php`
- Query SELECT now includes `keterangan` column
- New column in table: "Keterangan" with colored badges
- Display format: ✓ Tepat Waktu (green), ⏰ Terlambat (red), ⏰ Lembur (orange)

### 5. History/Report Display
**File:** `pages/history.php` (UPDATED)
- Include `helpers_attendance.php`
- AJAX query includes `keterangan` in SELECT
- New column in table: "Keterangan" with same styling as dashboard
- Support for filtering/searching by keterangan

### 6. PDF & Excel Export
**File:** `backend/api_export_history.php` (UPDATED)
- Include `helpers_attendance.php`
- Query SELECT includes `keterangan`
- PDF table has new "Keterangan" column with color coding
- CSV/Excel export includes "Keterangan" column

### 7. Migration Admin Page
**File:** `admin/migration.php` (NEW)
- Beautiful UI to execute migration
- Shows migration status (done/pending)
- Migration checklist with current status
- Real-time log output during execution
- Auto-refresh on successful migration

---

## 🔧 Database Changes (migration_add_keterangan.sql)

### Schema Changes
```sql
-- Add keterangan column to attendance_logs
ALTER TABLE attendance_logs 
ADD COLUMN keterangan VARCHAR(50) DEFAULT 'normal' AFTER stored_user_nama;

-- Add time standards to labor table
ALTER TABLE labor
ADD COLUMN jam_masuk_standar TIME DEFAULT '08:00:00',
ADD COLUMN jam_pulang_standar TIME DEFAULT '16:00:00',
ADD COLUMN toleransi_terlambat INT DEFAULT 15;
```

### Trigger (Automatic Calculation)
```sql
CREATE TRIGGER tr_attendance_keterangan_insert 
BEFORE INSERT ON attendance_logs 
FOR EACH ROW
BEGIN
    DECLARE v_jam_standar TIME;
    DECLARE v_jam_pulang_standar TIME;
    DECLARE v_toleransi INT;
    DECLARE v_jam_att TIME;
    
    -- Get labor settings
    SELECT jam_masuk_standar, jam_pulang_standar, toleransi_terlambat
    INTO v_jam_standar, v_jam_pulang_standar, v_toleransi
    FROM labor WHERE id = NEW.labor_id;
    
    -- Default values if not found
    IF v_jam_standar IS NULL THEN SET v_jam_standar = '08:00:00'; END IF;
    IF v_jam_pulang_standar IS NULL THEN SET v_jam_pulang_standar = '16:00:00'; END IF;
    IF v_toleransi IS NULL THEN SET v_toleransi = 15; END IF;
    
    SET v_jam_att = TIME(NEW.created_at);
    
    -- Determine keterangan
    IF NEW.status = 'IN' AND v_jam_att > ADDTIME(v_jam_standar, SEC_TO_TIME(v_toleransi * 60)) THEN
        SET NEW.keterangan = 'terlambat';
    ELSEIF NEW.status = 'OUT' AND v_jam_att > v_jam_pulang_standar THEN
        SET NEW.keterangan = 'lembur';
    ELSE
        SET NEW.keterangan = 'tepat waktu';
    END IF;
END;
```

---

## 📊 Keterangan Values

| Value | Condition | Color | Icon |
|-------|-----------|-------|------|
| **tepat waktu** | IN before threshold / OUT before limit | 🟢 Green | ✓ |
| **terlambat** | IN after (jam_masuk + toleransi) | 🔴 Red | ⏰ |
| **lembur** | OUT after jam_pulang_standar | 🟠 Orange | ⏰ |
| **normal** | Default/fallback | ⚫ Gray | ◆ |

---

## 🚀 Setup Instructions

### Step 1: Apply Database Migration
1. Go to: `http://yoursite/admin/migration.php`
2. Login dengan admin account
3. Click **"Jalankan Migration"** button
4. Wait for confirmation ✓ Migration Successful

### Step 2: Verify in Dashboard
1. Go to: `http://yoursite/pages/dashboard.php`
2. New "Keterangan" column should show up in attendance table
3. Colors and icons should display correctly

### Step 3: Configure Labor Times (Optional)
Currently defaults:
- **jam_masuk_standar**: 08:00:00
- **jam_pulang_standar**: 16:00:00
- **toleransi_terlambat**: 15 minutes

To change per labor, execute SQL:
```sql
UPDATE labor 
SET jam_masuk_standar = '07:30:00', 
    jam_pulang_standar = '16:30:00',
    toleransi_terlambat = 10
WHERE id = 3;  -- Replace with labor ID
```

---

## 💡 Features & Benefits

### Auto-Calculation
- No manual status entry needed
- Reduces data entry errors
- Consistent across all records

### Color-Coded Display
- Quick visual recognition of attendance status
- Different colors for each category
- Icons for better UX

### Export Support
- PDF reports include keterangan column
- Excel/CSV exports include keterangan
- Printable with full formatting

### Admin Control
- Configurable time thresholds per labor
- Toleransi adjustable for different rules
- Default values prevent errors

---

## 📋 Query Examples

### Get Statistics
```php
$stats = getStatisticKeterangan($labor_id, '2024-01-01', '2024-01-31', $conn);
// Output: ['tepat waktu' => ['total' => 15, 'percentage' => 75.0], ...]
```

### Generate HTML Badge
```php
$html = getKeteranganHTML('terlambat');
// Output: HTML span with red style and icon
```

### Calculate Keterangan
```php
$keterangan = hitungKeterangan('IN', '2024-01-15 08:20:00', 3, $conn);
// Output: 'terlambat' (if tolerance is 15 min)
```

---

## ⚙️ Configuration

### Default Values (In migration_add_keterangan.sql)
```
Labor table defaults:
- jam_masuk_standar: 08:00:00
- jam_pulang_standar: 16:00:00
- toleransi_terlambat: 15 (minutes)

Attendance logs:
- keterangan default: 'normal'
```

### Badge Colors (helpers_attendance.php)
```php
'tepat waktu' => ['color' => '#10b981', 'label' => '✓ Tepat Waktu'],
'terlambat' => ['color' => '#ef4444', 'label' => '⏰ Terlambat'],
'lembur' => ['color' => '#f59e0b', 'label' => '⏰ Lembur'],
'normal' => ['color' => '#64748b', 'label' => '◆ Normal']
```

---

## 🔍 Troubleshooting

### Keterangan column not showing?
1. Check if migration executed: `admin/migration.php`
2. Verify in database: `DESCRIBE attendance_logs;`
3. Clear browser cache

### Keterangan always showing "tepat waktu"?
1. Check labor table has time settings
2. Verify trigger is created: 
   ```sql
   SHOW TRIGGERS LIKE 'attendance_logs';
   ```
3. Check attendance.created_at timestamp is correct

### Export not showing keterangan?
1. Verify `api_export_history.php` is updated
2. Check helpers_attendance.php is included
3. Test with simple query first

---

## 📝 Files to Remember

| File | Purpose | Status |
|------|---------|--------|
| `backend/helpers_attendance.php` | Core functions | ✅ NEW |
| `backend/process_attendance.php` | Auto-calculate on insert | ✅ UPDATED |
| `backend/execute_migration.php` | Run migration | ✅ NEW |
| `pages/dashboard.php` | Show keterangan badge | ✅ UPDATED |
| `pages/history.php` | Show keterangan in history | ✅ UPDATED |
| `backend/api_export_history.php` | Include in exports | ✅ UPDATED |
| `admin/migration.php` | Migration UI | ✅ NEW |
| `database/migration_add_keterangan.sql` | SQL schema | ✅ READY |

---

## ✅ Next Steps

1. **Execute Migration** → `admin/migration.php`
2. **Test Dashboard** → Verify keterangan shows correctly
3. **Test History** → Check filtering and display
4. **Test Export** → Download PDF/Excel and verify
5. **Configure Times** → Adjust per labor if needed (optional)

---

## 📞 Support

For issues:
1. Check `admin/migration.php` for execution errors
2. Review database trigger: `SHOW TRIGGERS;`
3. Verify helpers_attendance.php is included correctly
4. Check console logs for JavaScript errors

---

**Last Updated:** 2024
**Version:** 1.0.0
**Status:** Ready for Production
