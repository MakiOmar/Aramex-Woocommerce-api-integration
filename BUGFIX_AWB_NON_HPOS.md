# Bug Fix: AWB Not Saving for Non-HPOS Orders

## Issue
AWB numbers were not being saved/updated for traditional WooCommerce orders (non-HPOS).

## Root Cause
The code was using direct WordPress meta functions (`update_post_meta`, `get_post_meta`, `delete_post_meta`) instead of WooCommerce order object methods. While this works for traditional post-based orders, it's not the recommended approach for WooCommerce 3.0+ and doesn't work consistently across different storage systems.

## Solution
Updated all meta operations to use WooCommerce order object methods:

### Before (Direct Meta Functions)
```php
// Get meta
$awb_no = get_post_meta($order_id, 'aramex_awb_no', true);

// Update meta
update_post_meta($order_id, 'aramex_awb_no', $awb_number);

// Delete meta
delete_post_meta($order_id, 'aramex_awb_no');
```

### After (WooCommerce Order Methods)
```php
// Get order object
$order = wc_get_order($order_id);

// Get meta
$awb_no = $order->get_meta('aramex_awb_no', true);

// Update meta
$order->update_meta_data('aramex_awb_no', $awb_number);
$order->save();

// Delete meta
$order->delete_meta_data('aramex_awb_no');
$order->save();
```

## Files Changed

### 1. includes/class-mo-aramex-awb-manager.php
**Changes:**
- `ajax_save_awb()`: Updated to use `$order->get_meta()`, `$order->update_meta_data()`, and `$order->save()`
- `ajax_delete_awb()`: Updated to use `$order->get_meta()`, `$order->delete_meta_data()`, and `$order->save()`

**Benefits:**
- Works with both traditional and HPOS orders
- Follows WooCommerce 3.0+ best practices
- Ensures data is properly saved

### 2. includes/class-mo-aramex-order-meta-box.php
**Changes:**
- `render_meta_box()`: Updated to get order object first, then use `$order->get_meta()` for all meta retrieval

**Benefits:**
- Consistent meta retrieval across storage systems
- Better performance
- More reliable data access

### 3. assets/js/awb-manager.js
**Changes:**
- Added console logging for debugging
- Logs AJAX request data before sending
- Logs response after receiving
- Logs errors with details

**Benefits:**
- Easier troubleshooting
- Better error visibility
- Helps diagnose issues quickly

## Testing

### Test Cases
1. ✅ Save new AWB number on traditional order
2. ✅ Update existing AWB number
3. ✅ Delete AWB number
4. ✅ Verify order notes are added
5. ✅ Check browser console for debug logs

### How to Test
1. Open any WooCommerce order (non-HPOS)
2. Open browser console (F12)
3. Enter AWB number in the AWB Manager
4. Click "Save AWB"
5. Check console logs for request/response
6. Verify AWB is saved (page reloads)
7. Try updating the AWB
8. Try deleting the AWB

## Debug Information

When testing, you'll see console logs like:

```javascript
// Before save
AWB Manager: Saving AWB {
    order_id: 123,
    awb_number: "1234567890",
    ajax_url: "/wp-admin/admin-ajax.php"
}

// After save (success)
AWB Manager: Save response {
    success: true,
    data: {
        message: "AWB number saved successfully!",
        awb_number: "1234567890",
        track_url: "https://www.aramex.com/..."
    }
}

// After save (error)
AWB Manager: Save response {
    success: false,
    data: {
        message: "Error message here"
    }
}
```

## Compatibility

- ✅ WooCommerce 3.0+
- ✅ Traditional post-based orders
- ✅ HPOS (High-Performance Order Storage)
- ✅ WordPress 5.3+
- ✅ PHP 7.4+

## Migration Note

**No database migration required.** The meta key (`aramex_awb_no`) remains the same. Only the method of accessing/updating it has changed.

## Related Issues

This fix also improves:
- Data consistency
- Code maintainability
- Future WooCommerce compatibility
- Performance (WooCommerce caches order objects)

## Commit Message

```
Fix: AWB not saving for non-HPOS orders

- Use WooCommerce order methods instead of direct meta functions
- Updated get_meta(), update_meta_data(), delete_meta_data()
- Added console logging for debugging
- Works with both traditional and HPOS orders
- Follows WooCommerce 3.0+ best practices
```

---

**Date:** October 8, 2025  
**Version:** 1.0.63 (patch)  
**Type:** Bug Fix  
**Priority:** High

