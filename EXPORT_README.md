# LinkageClock Export System

## Overview
This document describes the export functionality for the LinkageClock time tracking system, which allows payroll administrators to export attendance data in CSV and XLSX formats.

## Database Structure

### Attendance Logs Table (`wp_linkage_attendance_logs`)
This table stores one record per shift/day per employee with all the fields needed for payroll exports:

| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT(20) UNSIGNED | Auto-increment primary key |
| `user_id` | BIGINT(20) | WordPress user ID (employee) |
| `work_date` | DATE | Date of the work shift |
| `time_in` | DATETIME | Clock in time |
| `time_out` | DATETIME | Clock out time |
| `lunch_start` | DATETIME | Lunch break start time |
| `lunch_end` | DATETIME | Lunch break end time |
| `total_hours` | DECIMAL(5,2) | Calculated total hours worked |
| `status` | ENUM | Shift status (active/completed/cancelled) |
| `notes` | TEXT | Additional notes |
| `created_at` | TIMESTAMP | Record creation time |
| `updated_at` | TIMESTAMP | Last update time |

### Employee ID Storage
The system uses two ID systems:
- **WordPress User ID**: Internal system identifier (stored in `user_id` field)
- **Company Employee ID**: Business employee number stored in `wp_usermeta` table with key `linkage_company_id`

**Note**: If a company employee ID is not set, the system falls back to using the WordPress user ID for exports.

**Key Features:**
- Unique constraint on `(user_id, work_date)` ensures one record per employee per day
- Indexed fields for fast queries by user, date, and status
- Automatic timestamp tracking for audit purposes

## Export Fields

The export includes exactly the fields specified in the requirements:

1. **Employee Name** - From WordPress users table
2. **Employee ID** - Company employee ID from `linkage_company_id` meta field (falls back to WordPress user ID if not set)
3. **Date** - Work date (YYYY-MM-DD format)
4. **Time In** - Clock in time (HH:MM:SS format)
5. **Lunch Start** - Lunch break start time (HH:MM:SS format)
6. **Lunch End** - Lunch break end time (HH:MM:SS format)
7. **Time Out** - Clock out time (HH:MM:SS format)
8. **Total Hours** - Calculated hours worked (decimal format)

## Export Formats

### CSV Export
- **Format**: Standard CSV with headers
- **Filename**: `attendance_export_YYYY-MM-DD_HH-MM-SS.csv`
- **Requirements**: No additional libraries needed
- **Usage**: Direct download via AJAX

### XLSX Export
- **Format**: Excel spreadsheet (.xlsx)
- **Filename**: `attendance_export_YYYY-MM-DD_HH-MM-SS.xlsx`
- **Requirements**: PhpSpreadsheet library
- **Features**: Auto-sized columns, formatted data
- **Usage**: Direct download via AJAX

## Usage

### 1. Create Database Tables
First, ensure the attendance logs table exists by visiting the debug dashboard:
```
/debug-dashboard.php
```
Click "Create All Tables (Timesheet + Attendance Logs)"

### 2. Export via Debug Dashboard
For testing, use the export form in the debug dashboard:
- Select employee (optional)
- Choose date range
- Select format (CSV or XLSX)
- Click "Export Attendance Data"

### 3. Programmatic Export
Use the provided functions in your code:

```php
// Get attendance data for export
$logs = linkage_get_attendance_logs_for_export($user_id, $start_date, $end_date);

// Export to CSV
linkage_export_attendance_csv($user_id, $start_date, $end_date);

// Export to XLSX (requires PhpSpreadsheet)
linkage_export_attendance_xlsx($user_id, $start_date, $end_date);
```

### 4. AJAX Export
Send POST request to WordPress AJAX endpoint:

```javascript
// Example AJAX call
$.post(ajaxurl, {
    action: 'linkage_export_attendance',
    nonce: 'your_nonce_here',
    user_id: 123, // optional
    start_date: '2024-01-01',
    end_date: '2024-01-31',
    format: 'csv' // or 'xlsx'
}, function(response) {
    // Handle response
});
```

## Filtering Options

### Employee Filter
- **Single Employee**: Set `user_id` parameter
- **All Employees**: Leave `user_id` empty or null

### Date Range Filter
- **Start Date**: Include records from this date onwards
- **End Date**: Include records up to this date
- **No Dates**: Export all records if both dates are null

### Format Selection
- **CSV**: Universal format, works everywhere
- **XLSX**: Excel format, requires PhpSpreadsheet library

## Security

- **Nonce Verification**: All export requests require valid nonce
- **Permission Check**: Only users with `edit_posts` capability can export
- **Input Sanitization**: All parameters are sanitized before use
- **SQL Preparation**: Queries use prepared statements to prevent injection

## Dependencies

### Required
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+

### Optional
- **PhpSpreadsheet**: For XLSX export functionality
  - Install via Composer: `composer require phpoffice/phpspreadsheet`
  - Or download from: https://github.com/PHPOffice/PhpSpreadsheet

## Troubleshooting

### Common Issues

1. **Table Doesn't Exist**
   - Visit debug dashboard and click "Create All Tables"
   - Check WordPress error logs for database errors

2. **Export Fails**
   - Verify user permissions (need `edit_posts` capability)
   - Check nonce validity
   - Ensure date formats are valid (YYYY-MM-DD)

3. **XLSX Export Not Working**
   - Verify PhpSpreadsheet library is installed
   - Check PHP memory limits (XLSX generation can be memory-intensive)
   - Ensure proper file permissions for temporary files

4. **No Data in Export**
   - Verify attendance logs table has data
   - Check date range parameters
   - Ensure user_id filter is correct

### Debug Functions

Use these functions to troubleshoot:

```php
// Check if tables exist
linkage_debug_database_tables();

// Test export functionality
$logs = linkage_get_attendance_logs_for_export();
var_dump($logs);
```

## Future Enhancements

- **Batch Export**: Handle large datasets with pagination
- **Custom Fields**: Allow selection of specific export fields
- **Scheduled Exports**: Automated export generation
- **Email Delivery**: Send exports via email
- **API Integration**: REST API endpoints for external access
- **Audit Logging**: Track all export requests and downloads

## Support

For issues or questions about the export system:
1. Check the debug dashboard for table status
2. Review WordPress error logs
3. Verify database permissions and table structure
4. Test with minimal data and parameters
