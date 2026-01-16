# 🗄️ DB Sync - Database Comparison Tool

A full-fledged database comparison and synchronization tool with a clean web interface. Compare MySQL databases, identify differences, and synchronize data with ease.

## ✨ Features

### Database Comparison
- **Table Comparison**: Identify missing tables between databases
- **Column Comparison**: Compare column names, types, and nullability
- **Record Comparison**: Find missing rows and different values
- **Primary Key Detection**: Automatically detects primary keys for comparison

### Data Synchronization
- **One-Click Insert**: Insert missing rows from DB1 to DB2
- **Error Handling**: 
  - Duplicate primary key detection
  - Foreign key constraint violations
  - Data type mismatches
  - NULL constraint violations
- **Detailed Reports**: Success/failure counts with error messages

### Web Dashboard
- **Modern UI**: Clean, responsive interface with color coding
- **Summary View**: Quick overview of all differences
- **Detailed Tables**: Side-by-side comparison of different rows
- **Export Options**: JSON export of comparison reports

### API Endpoints
- REST API for programmatic access
- Full CRUD operations for comparisons
- JSON responses for easy integration

## 📁 Project Structure

```
dbsync/
├── config.php         # Database configuration
├── functions.php      # Core comparison functions
├── index.php          # Web dashboard
├── api.php            # REST API endpoints
├── demo_setup.php     # Demo database setup script
├── README.md          # This file
└── logs/              # Sync action logs (auto-created)
```

## 🚀 Quick Start

### 1. Configuration

Edit `config.php` and update your database credentials:

```php
// Database 1 (Source)
define('DB1_HOST', 'localhost');
define('DB1_PORT', '3306');
define('DB1_NAME', 'your_database1');
define('DB1_USER', 'your_username');
define('DB1_PASS', 'your_password');

// Database 2 (Target)
define('DB2_HOST', 'localhost');
define('DB2_PORT', '3306');
define('DB2_NAME', 'your_database2');
define('DB2_USER', 'your_username');
define('DB2_PASS', 'your_password');
```

### 2. Set Up Demo Databases (Optional)

For testing, you can create demo databases with sample data:

```bash
php demo_setup.php setup
```

This creates:
- `database1` - Main database with users, products, categories, orders
- `database2` - Database with intentional differences for testing

### 3. Run the Web Dashboard

Start a PHP development server:

```bash
php -S localhost:8000
```

Then open your browser to: `http://localhost:8000/index.php`

## 📖 Usage Guide

### Web Dashboard

1. **Connection Status**: Check if both databases are connected
2. **Comparison Settings**: Set record limit per table (default: 1000)
3. **Start Comparison**: Click to analyze databases
4. **View Results**:
   - Summary cards showing totals
   - Missing tables highlighted in red
   - Different rows shown with side-by-side comparison
   - Color-coded badges (red=missing, yellow=different, green=identical)

### Inserting Missing Rows

1. After comparison, scroll to a table with missing rows
2. Click "Insert X Missing Rows" button
3. Confirm the action in the dialog
4. View results with success/error details

### Exporting Reports

Click "Export JSON" to download a full comparison report.

## 🔧 API Reference

### Base URL
```
http://localhost:8000/api.php
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/status` | Check connection status |
| GET | `/api/tables` | List all tables in both databases |
| POST | `/api/compare/tables` | Compare tables between databases |
| GET | `/api/compare/columns/{table}` | Compare columns for a table |
| GET | `/api/compare/records/{table}` | Compare records for a table |
| POST | `/api/full-report` | Get complete comparison report |
| POST | `/api/insert` | Insert missing rows |
| POST | `/api/export/json` | Export report to JSON |

### Example API Usage

```bash
# Check connection status
curl http://localhost:8000/api.php/status

# Get comparison report
curl -X POST http://localhost:8000/api.php/full-report \
  -H "Content-Type: application/json" \
  -d '{"record_limit": 1000}'

# Insert missing rows
curl -X POST http://localhost:8000/api.php/insert \
  -H "Content-Type: application/json" \
  -d '{
    "table_name": "users",
    "rows": [{"id": 6, "username": "new_user", "email": "new@example.com"}]
  }'
```

## 🎨 Color Coding

| Color | Meaning |
|-------|---------|
| 🔴 Red | Missing tables, missing rows |
| 🟡 Yellow | Different values, type mismatches |
| 🟢 Green | Identical data, successful operations |
| 🔵 Blue | Information, actions |

## ⚠️ Error Handling

The tool handles common synchronization errors:

### 1. Duplicate Primary Key
```
Error: Duplicate entry '1' for key 'PRIMARY'
Cause: Row already exists in target database
Solution: Update existing row or check sync direction
```

### 2. Foreign Key Constraint
```
Error: Foreign key constraint fails
Cause: Referenced record doesn't exist
Solution: Insert parent records first
```

### 3. Data Type Mismatch
```
Error: Data too long for column
Cause: Value exceeds column size limit
Solution: Check column types and truncate if needed
```

### 4. NULL Constraint
```
Error: Column cannot be NULL
Cause: Inserting NULL into NOT NULL column
Solution: Provide default value or update schema

## 📊 Sample Output

### Summary View
```
Total Tables in DB1: 4
Total Tables in DB2: 2
Missing Tables in DB2: 2 (categories, orders)
Total Missing Rows: 5
Total Different Rows: 3
Total Identical Rows: 12
```

### Table Comparison
```
Table: users
├── Columns: All match
├── DB1 Rows: 5
├── DB2 Rows: 4
├── Missing in DB2: 1
├── Different: 1
└── Identical: 3
```

## 🔒 Security Considerations

1. **Never commit config.php** with real credentials
2. **Use environment variables** for production
3. **Enable HTTPS** in production
4. **Restrict API access** with authentication
5. **Validate all inputs** before database operations

## 📝 Logging

Sync actions are logged to `logs/sync_actions.log`:

```json
{"timestamp":"2024-01-15 10:30:00","action":"insert","table":"users","success":true,"details":{...}}
```

## 🛠️ Customization

### Adding New Comparison Types

Add new functions to `functions.php`:

```php
function compareTableIndexes(PDO $db1, PDO $db2, $tableName) {
    // Your comparison logic
}
```

### Custom Error Handlers

Modify the `categorizeError()` function to handle additional error types.

### Styling

Edit the `<style>` section in `index.php` to customize colors and layout.

## 📦 Dependencies

- PHP 7.4+
- PDO MySQL Extension
- MySQL 5.7+ / MariaDB 10.2+

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

MIT License - Feel free to use and modify for your projects.

---

## 🆘 Troubleshooting

### Connection Failed
- Verify MySQL server is running
- Check credentials in config.php
- Ensure databases exist

### No Tables Found
- Check user has SELECT privilege
- Verify database names are correct

### Insert Errors
- Check for foreign key constraints
- Verify primary key values
- Ensure target table structure matches

---

**Made with ❤️ for developers who need reliable database comparison**

