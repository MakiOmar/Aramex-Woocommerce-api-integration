# AWB Manager - Quick Start Guide

## 🚀 Getting Started (5 Minutes)

### Step 1: Access an Order

1. Go to WordPress Admin Dashboard
2. Navigate to **WooCommerce → Orders**
3. Click on any order to edit it

### Step 2: Locate the AWB Manager

Look for the **"Aramex Shipment Information"** box on the right sidebar:

```
┌─────────────────────────────────────────┐
│ Aramex Shipment Information             │
├─────────────────────────────────────────┤
│                                          │
│ ▶ AWB Number: [Displays if exists]      │
│ ▶ Shipment Type: [Displays if exists]   │
│ ▶ Shipping Label: [Download button]     │
│                                          │
│ ───────────────────────────────────────  │
│                                          │
│ Manual AWB Management                    │
│ ┌─────────────────────────────────────┐ │
│ │ [Enter AWB Number            ]      │ │
│ └─────────────────────────────────────┘ │
│                                          │
│ Enter the Aramex Air Waybill (AWB)      │
│ number for this shipment.               │
│                                          │
│ [✓ Save AWB]  [🗑 Delete AWB]           │
└─────────────────────────────────────────┘
```

### Step 3: Set an AWB Number

1. In the input field, type the AWB number (numbers only)
   - Example: `1234567890`
2. Click the **"Save AWB"** button
3. Wait for the success message
4. Page will reload automatically

### Step 4: Verify the AWB

After reload, you should see:
- ✅ AWB number displayed at the top of the meta box
- ✅ A **"Track"** button to track the shipment
- ✅ A new order note in the order notes section

## 📋 Common Use Cases

### Case 1: Adding AWB for Manual Shipment

**Scenario:** You created a shipment manually on Aramex website and got an AWB number.

**Steps:**
1. Open the order in WooCommerce
2. Enter the AWB number from Aramex
3. Click "Save AWB"
4. Done! The AWB is now linked to the order

### Case 2: Correcting Wrong AWB

**Scenario:** Wrong AWB was entered or automatically assigned.

**Steps:**
1. Open the order
2. Replace the AWB number with the correct one
3. Click "Save AWB"
4. The order note will show: "AWB updated from [old] to [new]"

### Case 3: Removing AWB

**Scenario:** Need to remove an AWB number.

**Steps:**
1. Open the order
2. Click "Delete AWB" button
3. Confirm the deletion
4. AWB is removed and order note added

## ⚡ Quick Tips

✨ **Only Numbers:** AWB numbers must contain only digits (0-9)

✨ **Track Instantly:** Once AWB is saved, use the "Track" button to view shipment status

✨ **Order Notes:** All AWB changes are automatically logged in order notes

✨ **Activity Log:** All changes are logged for auditing purposes

✨ **Auto-Reload:** Page reloads after save/delete to ensure all data is current

## ⚠️ Important Notes

🔒 **Permissions Required:** You need "Edit Orders" permission to manage AWB numbers

🔒 **Security:** All actions are protected with WordPress security features

⚙️ **HPOS Compatible:** Works with both old and new WooCommerce order storage systems

## 🎯 What Happens Behind the Scenes

When you save an AWB:

1. ✅ AWB is validated (must be numeric)
2. ✅ AWB is saved to database
3. ✅ Order note is added automatically
4. ✅ Action is logged
5. ✅ Tracking link is created
6. ✅ Meta box is updated

## 📱 Visual Example

### Before Setting AWB:
```
┌────────────────────────────────┐
│ Aramex Shipment Information    │
├────────────────────────────────┤
│ No Aramex shipment created yet.│
│                                 │
│ ─────────────────────────────── │
│                                 │
│ Manual AWB Management           │
│ [                        ]      │
│ [✓ Save AWB]                    │
└────────────────────────────────┘
```

### After Setting AWB (e.g., 1234567890):
```
┌────────────────────────────────┐
│ Aramex Shipment Information    │
├────────────────────────────────┤
│ AWB Number:                     │
│ 1234567890        [Track]       │
│                                 │
│ ─────────────────────────────── │
│                                 │
│ Manual AWB Management           │
│ [1234567890              ]      │
│ [✓ Save AWB] [🗑 Delete AWB]   │
└────────────────────────────────┘
```

## 🐛 Troubleshooting

### "Error saving AWB"
- **Cause:** Invalid AWB format
- **Solution:** Make sure AWB contains only numbers

### Delete button doesn't appear
- **Cause:** No AWB set yet
- **Solution:** Delete button only shows when AWB exists

### Can't see the AWB Manager
- **Cause:** Not on order edit page
- **Solution:** Make sure you're editing a WooCommerce order

### Permission error
- **Cause:** User doesn't have edit orders permission
- **Solution:** Contact admin to grant you "Edit Shop Orders" capability

## 📞 Need Help?

- 📧 Email: maki3omar@gmail.com
- 📚 Full Documentation: See `AWB_MANAGER_README.md`
- 🔧 Technical Details: See `AWB_IMPLEMENTATION_SUMMARY.md`

## ✅ You're All Set!

The AWB Manager is now ready to use. Simply navigate to any order and start managing AWB numbers!

---

**Version:** 1.0.63  
**Last Updated:** October 2025

