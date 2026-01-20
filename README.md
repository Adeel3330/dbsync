/Applications/ServBay/www/dbsync/README.md
</path>
<parameter name="content"># DB Sync - Database Comparison Tool

A full-featured PHP web application for comparing two MySQL/MariaDB databases with a complete multi-page UI.

## Features

### Multi-Page Structure
1. **Dashboard** - Quick overview with summary cards
2. **Table Structure Comparison** - Compare table structures, columns, and indexes
3. **Record Comparison** - Compare records between tables with primary key matching
4. **Table Details** - View table data side-by-side with pagination
5. **DBs Reports** - Generate detailed comparison reports with PDF export
6. **Logs & Issues** - View activity logs and error tracking

### Core Functionality
- **Database Connection Management** - Store and test connections for two databases
- **Structure Comparison** - Find missing tables, columns, and data type differences
- **Record Comparison** - Compare records based on primary keys
- **Bulk Operations** - Select multiple rows and insert at once
- **PDF Reports** - Generate detailed comparison reports

### Performance Optimizations
- Pagination for large tables
- Batch loading for comparisons
- Lazy loading of data
- AJAX-based operations
- Progress indicators and loading spinners

### UI/UX
- Clean Bootstrap 5 interface
- Responsive design
- Color-coded rows (RED=missing, ORANGE=different, GREEN=matched)
- Loading indicators
- Toast notifications
- Export to CSV

## Installation

1. Place the `dbsync` folder in your web server's document root
2. Ensure PHP 7.4+ and MySQL/MariaDB extensions are enabled
3. Access via `http://localhost/dbsync/`

## Configuration

1. Click "Settings" button to configure database connections
2. Enter connection details for Database A and Database B
3. Test connections before saving
4. Click "Save Configuration"

## Usage

### Dashboard
View quick summary of both databases including:
- Total tables in each database
- Missing tables count
- Mismatched tables
- Total record differences

### Structure Comparison
Compare table structures between databases:
- Missing tables in each database (highlighted in red)
- Column differences
- Data type differences
- Index differences

### Record Comparison
1. Select a table from the dropdown
2. View comparison summary (matched, missing, different rows)
3. Missing rows are highlighted in red
4. Different rows are highlighted in orange
5. Click checkbox and "Insert Selected Rows" to sync missing data

### DBs Reports
Generate comprehensive comparison reports:
1. Click "Generate Report" to analyze databases
2. Filter by status (all differences, missing tables only, etc.)
3. Click "Export PDF" to download a printable report
4. Tables with 0 differences are not shown but counted in summary

### Logs
View detailed logs of:
- Insert actions
- Errors and constraint issues
- Duplicate key problems
- Foreign key constraints

## Security

- SQL injection prevention via PDO prepared statements
- Input sanitization
- Secure credential storage (JSON config file)

## File Structure

```
dbsync/
├── api/                    # AJAX API endpoints
│   ├── test_connection.php
│   ├── get_summary.php
│   ├── compare_structures.php
│   ├── compare_records.php
│   ├── generate_report.php
│   ├── insert_row.php
│   └── ...
├── config/
│   └── config.php          # Database configuration
├── css/
│   └── style.css           # Custom styles
├── js/
│   └── app.js              # Main JavaScript
├── pages/                  # Page views
│   ├── dashboard.php
│   ├── structure.php
│   ├── records.php
│   ├── reports.php
│   └── logs.php
├── templates/
│   ├── header.php
│   └── footer.php
├── logs/
│   └── app.log
├── index.php               # Main entry
├── functions.php           # Core functions
└── README.md
```

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB PDO extension
- Bootstrap 5 (loaded via CDN)
- Font Awesome (loaded via CDN)

## License

MIT License
