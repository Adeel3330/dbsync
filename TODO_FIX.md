# TODO: Fix Auto-Increment Primary Key Issue

## Problem
When syncing records, the auto-increment primary key column is included in INSERT statements, causing:
- Duplicate key errors if the ID already exists in target
- MySQL cannot auto-generate new values when PK is explicitly provided

## Solution
Modify `insertRow()` function in `functions.php` to:
1. Detect auto-increment columns
2. Exclude auto-increment columns from INSERT statement

## Steps
1. ✅ Create this todo list
2. ✅ Modify `insertRow()` function to exclude auto-increment columns
3. 🔄 Test the fix

## Implementation Details
The `insertRow()` function needs to:
- Get table structure to identify auto-increment columns
- Filter out auto-increment columns from the INSERT
- Only include columns that should be inserted

## Files Modified
- `functions.php` - `insertRow()` function (ADDED)
- `functions.php` - `getAutoIncrementColumns()` function (NEW)

## Changes Made
1. Modified `insertRow()` to call `getAutoIncrementColumns()` and filter out auto-increment columns
2. Added new `getAutoIncrementColumns()` helper function that queries table structure
3. Returns `insert_id` from `lastInsertId()` for debugging

## Testing
Run sync.php and verify that:
- Records are inserted with new auto-generated IDs
- No duplicate key errors occur
- All non-PK columns are properly synced

