# AWB Manager - Manual AWB Number Management

## Overview

The AWB Manager feature allows administrators to manually set, update, or delete AWB (Air Waybill) numbers for WooCommerce orders. This is useful when:

- AWB numbers are received from Aramex through external channels
- Manual shipment creation is needed outside the automated system
- Correcting or updating AWB numbers for existing orders
- Handling special cases where automatic AWB creation fails

## Features

✅ **Manual AWB Entry** - Set AWB numbers directly from the order edit page  
✅ **Update Existing AWB** - Modify AWB numbers that were previously set  
✅ **Delete AWB** - Remove AWB numbers when needed  
✅ **Order Notes** - Automatic order notes for all AWB changes  
✅ **Activity Logging** - All AWB management actions are logged  
✅ **Security** - Nonce verification and capability checks  
✅ **HPOS Compatible** - Works with both traditional and High-Performance Order Storage  

## How to Use

### Setting or Updating an AWB Number

1. Navigate to **WooCommerce > Orders**
2. Open the order you want to manage
3. In the right sidebar, find the **"Aramex Shipment Information"** meta box
4. Scroll to the **"Manual AWB Management"** section
5. Enter the AWB number in the input field
6. Click **"Save AWB"** button
7. The page will reload with the updated AWB information

### Deleting an AWB Number

1. Open the order with an existing AWB number
2. In the **"Manual AWB Management"** section
3. Click the **"Delete AWB"** button
4. Confirm the deletion when prompted
5. The AWB number will be removed and an order note will be added

## Technical Details

### Files Added/Modified

**New Files:**
- `includes/class-mo-aramex-awb-manager.php` - AWB management class
- `assets/js/awb-manager.js` - JavaScript for AJAX handling

**Modified Files:**
- `includes/class-mo-aramex-order-meta-box.php` - Added AWB editor to meta box
- `mo-aramex-shipping-integration.php` - Loaded AWB manager class

### Database Storage

AWB numbers are stored in the WordPress `postmeta` table:
- **Meta Key:** `aramex_awb_no`
- **Meta Value:** The AWB number (numeric string)

### AJAX Actions

The feature uses two AJAX actions:

1. **`mo_aramex_save_awb`** - Saves or updates AWB number
   - Validates order ID and AWB number
   - Updates post meta
   - Adds order note
   - Logs the action

2. **`mo_aramex_delete_awb`** - Deletes AWB number
   - Validates order ID
   - Removes post meta
   - Adds order note
   - Logs the action

### Permissions

Only users with `edit_shop_orders` capability can manage AWB numbers.

### Validation

- AWB numbers must contain only digits
- AWB numbers cannot be empty when saving
- Order must exist and be valid

### Order Notes

The system automatically adds order notes for:
- New AWB number set
- AWB number updated (shows old and new values)
- AWB number deleted (shows deleted value)

### Activity Logging

All AWB management actions are logged using the plugin's logging system, including:
- Order ID
- User who made the change
- AWB number (old and new if updated)
- Timestamp

## User Interface

The AWB manager interface appears in the **Aramex Shipment Information** meta box with:

- **Input Field** - For entering/editing the AWB number
- **Help Text** - Explains what to enter
- **Save Button** - Saves the AWB number
- **Delete Button** - Appears when an AWB exists, allows deletion
- **Success/Error Messages** - Visual feedback for all actions

## Integration with Existing Features

The AWB manager integrates seamlessly with:

- **Order Meta Box** - Displays AWB in the main shipment info section
- **Tracking Links** - AWB numbers link to Aramex tracking page
- **Shipment Creation** - Manually set AWB works the same as automatically created ones
- **Order Status** - Compatible with all order statuses

## Security Features

1. **Nonce Verification** - All AJAX requests are verified
2. **Capability Checks** - Only authorized users can manage AWB
3. **Input Sanitization** - All input is sanitized and validated
4. **XSS Prevention** - Output is properly escaped

## Browser Compatibility

The feature works with all modern browsers:
- Chrome
- Firefox
- Safari
- Edge

## Troubleshooting

### AWB not saving
- Check if you have permission to edit orders
- Ensure AWB contains only numbers
- Check browser console for JavaScript errors

### Delete button not working
- Ensure you confirm the deletion prompt
- Check browser console for errors

### Page not reloading after save
- Check your internet connection
- Ensure JavaScript is enabled
- Check browser console for errors

## Version

This feature was added in version **1.0.63** of the MO Aramex Shipping Integration plugin.

## Support

For issues or questions:
- Email: maki3omar@gmail.com
- GitHub: https://github.com/maki3omar

