# Sync Implementation Plan

## Goal
Implement deduplication sync that compares records by full row data EXCEPT primary key. Only INSERTs new unique records, skips duplicates.

## Changes Required

### Step 1: Update functions.php ✅ COMPLETED
- Added new function `compareRecordsByNonPK()` 
- Compares records by non-PK columns only
- Returns `missingInB_rows` (new records) and `alreadyExist_rows` (duplicates)

### Step 2: Update api/sync_records.php ✅ COMPLETED
- Modified `getSyncPreview()` to use `compareRecordsByNonPK()`
- Modified `executeSync()` to only INSERT new records (no UPDATE)
- Added `skip` tracking for duplicate records
- Updated summary counts for CREATE-only operations

### Step 3: Update pages/sync.php (optional)
- UI shows CREATE count (new records) and UPDATE count (always 0)
- Can add "Skipped" display if needed

## Implementation Details

### functions.php changes:
- New function `compareRecordsByNonPK($dbKeyA, $dbKeyB, $tableName, $primaryKeys)`:
  - Builds hash using only non-PK columns
  - Returns: `missingInB` (to CREATE), `alreadyExist` (to skip)
  - Falls back to `compareRecords()` if no non-PK columns

### api/sync_records.php changes:
- `getSyncPreview()`: 
  - Uses `compareRecordsByNonPK()` for comparison
  - create_count = records with new non-PK data
  - update_count = 0 (no updates in deduplication mode)
  - skip_count = duplicate records
- `executeSync()`:
  - Only INSERT records that don't have matching non-PK data
  - Track skipped duplicates in `skip` section

## Expected Behavior
1. First sync: Source 100 rows → Target 0 rows = CREATE 100
2. After sync: Target has 100 rows with new PKs
3. Second preview: Source 100 rows → Target 100 rows = CREATE 0 (deduplicated)

