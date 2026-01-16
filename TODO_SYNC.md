# DB Sync - Record Migration Feature

## Feature: Complete Record Migration with CREATE/UPDATE Detection

### Overview
- Migrate all records from source DB to target DB
- Distinguish between CREATE (new records) and UPDATE (modified records)
- Show detailed results in dashboard and new sync page

---

## TODO List

### Phase 1: Backend API
- [x] 1.1 Create `api/sync_records.php` - Main sync API endpoint
- [x] 1.2 Add helper functions for sync to `functions.php` (updateRow function added)

### Phase 2: Frontend Page
- [x] 2.1 Create `pages/sync.php` - Sync UI page
- [x] 2.2 Add sync navigation to `templates/header.php`

### Phase 3: JavaScript Integration
- [x] 3.1 Add sync functions to `js/app.js`
- [x] 3.2 Add preview and execute functionality

### Phase 4: Dashboard Integration
- [x] 4.1 Add sync action card to `index.php` dashboard
- [ ] 4.2 Add sync stats to summary (optional enhancement)

### Phase 5: Testing
- [ ] 5.1 Test sync functionality
- [ ] 5.2 Verify CREATE/UPDATE detection works correctly

---

## ✅ IMPLEMENTATION COMPLETE

### Files Created:
1. ✅ `api/sync_records.php` - Backend API for sync operations
2. ✅ `pages/sync.php` - New Sync Records UI page

### Files Modified:
3. ✅ `templates/header.php` - Added Sync navigation link
4. ✅ `js/app.js` - Added sync JavaScript functions
5. ✅ `index.php` - Added Sync Records quick action

### Features Implemented:
- ✅ Preview sync operations (CREATE/UPDATE counts)
- ✅ Distinguish between new records (CREATE) and modified records (UPDATE)
- ✅ Dry run mode to preview without making changes
- ✅ Execute sync with progress tracking
- ✅ Side-by-side comparison for UPDATE operations
- ✅ Detailed logging of all sync operations

### Usage:
1. Navigate to **Sync** page from the sidebar
2. Select source and target databases
3. Select a table to sync
4. Click **Preview** to see CREATE/UPDATE counts
5. Configure options (CREATE missing, UPDATE existing, Dry Run)
6. Click **Execute Sync** to perform the migration

---

## Implementation Progress

### Phase 1: Backend API
- [ ] api/sync_records.php

### Phase 2: Frontend Page
- [ ] pages/sync.php
- [ ] templates/header.php (navigation)

### Phase 3: JavaScript Integration
- [ ] js/app.js (sync functions)

### Phase 4: Dashboard Integration
- [ ] index.php (quick action card)

---

## API Endpoints

### GET /api/sync_records.php?table=xxx&source=db_a&target=db_b&action=preview
- Returns sync preview: counts of CREATE and UPDATE needed
- Does NOT modify data

### POST /api/sync_records.php
```json
{
  "table": "users",
  "source_db": "db_a",
  "target_db": "db_b",
  "action": "sync",
  "options": {
    "create_missing": true,
    "update_existing": true,
    "dry_run": false
  }
}
```
- Executes sync: CREATE missing records, UPDATE existing records
- Returns detailed results

---

## File Structure

```
dbsync/
├── api/
│   └── sync_records.php      (NEW) Sync API endpoint
├── pages/
│   └── sync.php              (NEW) Sync operations page
├── js/
│   └── app.js                (MODIFIED) Add sync functions
├── templates/
│   └── header.php            (MODIFIED) Add navigation
├── index.php                 (MODIFIED) Add quick action
└── functions.php             (MODIFIED) Add sync helpers
```

