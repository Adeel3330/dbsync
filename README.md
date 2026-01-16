# DB Sync - Database Comparison Tool

A full-featured PHP web application for comparing two MySQL/MariaDB databases with a complete multi-page UI.

## Features

- **Multi-page architecture** with Dashboard, Structure Comparison, Record Comparison, Table Details, and Logs pages
- **Database connection management** with saveable configurations
- **Structure comparison** - Compare tables, columns, indexes, and data types
- **Record comparison** - Compare data rows based on primary keys
- **Insert functionality** - Insert missing rows from one database to another
- **Optimized for large databases** with pagination and lazy loading
- **Activity logging** - Track all insert actions and errors
- **Export to CSV** - Download comparison results
- **Responsive Bootstrap UI** - Clean, modern interface

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10+
- PDO extension enabled
- Bootstrap 5 (loaded via CDN)
- Font Awesome (loaded via CDN)

## Installation

1. Place the project in your web server directory (e.g., `/Applications/ServBay/www/dbsync`)
2. Ensure the `logs/` directory is writable
3. Ensure the `config/` directory is writable
4. Access the application via browser

## Project Structure

```
dbsync/
├── api/
│   ├── compare_records.php    # Compare table records
│   ├── compare_structures.php # Compare table structures
│   ├── get_logs.php           # Get activity logs
│   ├── get_missing_rows.php   # Get missing rows for insert
│   ├── get_summary.php        # Get dashboard summary
│   ├── get_table_data.php     # Get paginated table data
│   ├── insert_row.php         # Insert row from one DB to another
│   ├── save_config.php        # Save configuration
│   └── test_connection.php    # Test database connection
├── config/
│   └── config.php             # Database configuration class
├── css/
│   └── style.css              # Custom styles
├── js/
│   └── app.js                 # JavaScript application
├── pages/
│   ├── logs.php               # Logs & Issues page
│   ├── records.php            # Record comparison page
│   ├── structure.php          # Structure comparison page
│   └── table_detail.php       # Detailed table view page
├── templates/
│   ├── footer.php             # Page footer
│   └── header.php             # Page header
├── index.php                  # Dashboard/Home page
├── functions.php              # Core helper functions
└── .htaccess                  # Apache configuration
```

## Usage

### 1. Configure Database Connections

1. Click the "Settings" button in the top navigation
2. Enter connection details for both Database A and Database B
3. Test each connection
4. Save the configuration

### 2. Dashboard

The dashboard shows a quick summary:
- Total tables in each database
- Missing tables count
- Matched/mismatched tables

### 3. Structure Comparison

Compare table structures between databases:
- Missing tables (highlighted in red)
- Column differences
- Data type differences
- Index differences

### 4. Record Comparison

Compare actual data rows:
1. Select a table from the dropdown
2. View summary of matching/missing/different rows
3. Click "Load & Insert" to load missing rows
4. Use the Insert button to add rows from one DB to another

### 5. Table Details

View detailed table data with:
- Pagination
- Search/filter
- Side-by-side comparison
- CSV export

### 6. Logs

View activity logs including:
- Insert actions
- Errors
- Constraint issues
- Connection status

## Performance Optimization

- **Pagination** - All large data sets are paginated
- **Batch loading** - Data is loaded in batches to avoid timeouts
- **Lazy loading** - Data is only loaded when requested
- **Optimized queries** - Uses COUNT(*) and LIMIT/OFFSET efficiently

## Security

- PDO prepared statements for all queries
- Input sanitization
- SQL injection prevention
- Secure session handling

## License

MIT License

