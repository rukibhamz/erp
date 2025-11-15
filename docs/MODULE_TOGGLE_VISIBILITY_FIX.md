# Module Toggle Visibility Fix
**Date:** Current  
**Status:** ✅ **FIXED**

---

## Summary

Fixed the error when trying to re-enable modules after deactivating them in the module customization page.

---

## Problem

When a module was deactivated and then the user tried to re-enable it, the operation would fail with an error. This happened because:

1. **UPDATE Query Issue**: The `toggleVisibility()` method was checking if a PDOStatement object exists, but a PDOStatement is always truthy even when no rows are affected. This meant the method would return `true` even when the UPDATE didn't actually change anything.

2. **Missing Module Entry**: If a module didn't exist in the `module_labels` table, the UPDATE query would affect 0 rows and fail silently.

3. **No Row Count Check**: The method wasn't checking `rowCount()` to verify that the UPDATE actually affected a row.

---

## Solution

### 1. Enhanced `toggleVisibility()` Method ✅

**Changes:**
- **Check if module exists first**: Query the database to see if the module exists before attempting to update
- **Verify row count**: Check `$stmt->rowCount()` to ensure the UPDATE actually affected a row
- **Handle already-set values**: If the module already has the desired `is_active` value, return success
- **Create missing entries**: If the module doesn't exist in the table, create it with default values instead of failing

**Logic Flow:**
```
1. Check if module exists in module_labels table
2. If exists:
   - Update is_active value
   - Check rowCount() to verify update succeeded
   - If already has desired value, return success
3. If doesn't exist:
   - Create new entry with default label and icon
   - Set is_active to desired value
   - Return success
```

### 2. Made `getDefaultLabels()` Protected ✅

Changed visibility from `private` to `protected` so it can be accessed by other methods in the class (though it was already accessible since it's in the same class, this makes the intent clearer).

---

## Code Changes

### Before:
```php
public function toggleVisibility($moduleCode, $isActive, $userId) {
    try {
        $sql = "UPDATE `{$this->prefix}module_labels`
                SET is_active = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE module_code = ?";
        
        $stmt = $this->db->query($sql, [$isActive ? 1 : 0, $userId, $moduleCode]);
        return $stmt ? true : false; // ❌ Always returns true if query executes
    } catch (Exception $e) {
        error_log("Error toggling module visibility: " . $e->getMessage());
        return false;
    }
}
```

### After:
```php
public function toggleVisibility($moduleCode, $isActive, $userId) {
    try {
        // Check if module exists
        $existing = $this->db->fetchOne(
            "SELECT id, is_active FROM `{$this->prefix}module_labels` WHERE module_code = ?",
            [$moduleCode]
        );
        
        if ($existing) {
            // Update existing module
            $sql = "UPDATE `{$this->prefix}module_labels`
                    SET is_active = ?,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE module_code = ?";
            
            $stmt = $this->db->query($sql, [$isActive ? 1 : 0, $userId, $moduleCode]);
            
            // ✅ Check rowCount() to verify update succeeded
            if ($stmt && $stmt->rowCount() > 0) {
                return true;
            }
            
            // ✅ Handle case where value is already set
            if (intval($existing['is_active']) == ($isActive ? 1 : 0)) {
                return true;
            }
            
            return false;
        } else {
            // ✅ Create module entry if it doesn't exist
            $defaultLabel = ucfirst(str_replace('_', ' ', $moduleCode));
            $defaultIcon = 'bi-circle';
            
            $defaults = $this->getDefaultLabels();
            if (isset($defaults[$moduleCode])) {
                $defaultLabel = $defaults[$moduleCode]['display_label'] ?? $defaultLabel;
                $defaultIcon = $defaults[$moduleCode]['icon_class'] ?? $defaultIcon;
            }
            
            $sql = "INSERT INTO `{$this->prefix}module_labels`
                    (module_code, default_label, custom_label, icon_class, display_order, is_active, updated_by, created_at, updated_at)
                    VALUES (?, ?, NULL, ?, 999, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->query($sql, [
                $moduleCode,
                $defaultLabel,
                $defaultIcon,
                $isActive ? 1 : 0,
                $userId
            ]);
            
            return $stmt ? true : false;
        }
    } catch (Exception $e) {
        error_log("Error toggling module visibility: " . $e->getMessage());
        return false;
    }
}
```

---

## Testing

### Test Case 1: Deactivate Module
1. Go to Module Customization page
2. Toggle a module OFF (deactivate)
3. ✅ Verify success message appears
4. ✅ Verify module still appears in list (with toggle OFF)

### Test Case 2: Re-enable Module
1. With a module deactivated (toggle OFF)
2. Toggle the module ON (re-enable)
3. ✅ Verify success message appears
4. ✅ Verify module is now active (toggle ON)
5. ✅ Verify no errors occur

### Test Case 3: Toggle Already-Set Value
1. Module is already active
2. Try to activate it again
3. ✅ Should return success (no error, even though value didn't change)

### Test Case 4: Toggle Non-Existent Module
1. Try to toggle a module that doesn't exist in `module_labels` table
2. ✅ Should create the entry and set the visibility
3. ✅ Module should appear in the list

---

## Files Modified

- `application/models/Module_label_model.php`
  - Enhanced `toggleVisibility()` method
  - Changed `getDefaultLabels()` visibility from `private` to `protected`

---

## Benefits

1. **Reliable Toggle**: Modules can now be reliably toggled on and off without errors
2. **Auto-Creation**: Missing module entries are automatically created when toggled
3. **Better Error Handling**: Proper row count checking ensures updates actually succeed
4. **Idempotent**: Toggling to the same value doesn't cause errors

---

**Status:** ✅ Fixed - Module visibility toggle now works correctly for both activation and deactivation

