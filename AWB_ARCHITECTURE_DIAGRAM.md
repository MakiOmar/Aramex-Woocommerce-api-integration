# AWB Manager - Architecture Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         WORDPRESS ADMIN                              │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │              WooCommerce Order Edit Page                    │    │
│  │                                                              │    │
│  │  ┌────────────────────────────────────────────────────┐    │    │
│  │  │   Aramex Shipment Information (Meta Box)           │    │    │
│  │  │                                                     │    │    │
│  │  │   ┌─────────────────────────────────────────┐     │    │    │
│  │  │   │ Current AWB Display                     │     │    │    │
│  │  │   │ • AWB Number: 1234567890      [Track]  │     │    │    │
│  │  │   │ • Shipment Type: Domestic              │     │    │    │
│  │  │   │ • [Download Label PDF]                 │     │    │    │
│  │  │   └─────────────────────────────────────────┘     │    │    │
│  │  │                                                     │    │    │
│  │  │   ────────────────────────────────────────────     │    │    │
│  │  │                                                     │    │    │
│  │  │   ┌─────────────────────────────────────────┐     │    │    │
│  │  │   │ Manual AWB Management                   │     │    │    │
│  │  │   │                                         │     │    │    │
│  │  │   │ ┌───────────────────────────────────┐  │     │    │    │
│  │  │   │ │ [Enter AWB Number            ]    │  │     │    │    │
│  │  │   │ └───────────────────────────────────┘  │     │    │    │
│  │  │   │                                         │     │    │    │
│  │  │   │ Enter Aramex Air Waybill number...     │     │    │    │
│  │  │   │                                         │     │    │    │
│  │  │   │ [✓ Save AWB]  [🗑 Delete AWB]          │     │    │    │
│  │  │   └─────────────────────────────────────────┘     │    │    │
│  │  └────────────────────────────────────────────────────┘    │    │
│  └────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌──────────────┐
│     User     │
│   (Admin)    │
└──────┬───────┘
       │
       │ 1. Enters AWB Number
       ▼
┌──────────────────────────────┐
│   JavaScript (awb-manager.js) │
│   • Validate input            │
│   • Prepare AJAX request      │
└──────────┬───────────────────┘
           │
           │ 2. AJAX POST Request
           │    (with nonce)
           ▼
┌─────────────────────────────────────────┐
│  PHP Backend (class-mo-aramex-awb-      │
│              manager.php)                │
│                                          │
│  ┌────────────────────────────────┐    │
│  │ 3. Security Check              │    │
│  │    • Verify nonce              │    │
│  │    • Check permissions         │    │
│  └────────────────────────────────┘    │
│              │                          │
│              ▼                          │
│  ┌────────────────────────────────┐    │
│  │ 4. Validate Data               │    │
│  │    • Check order exists        │    │
│  │    • Validate AWB format       │    │
│  └────────────────────────────────┘    │
│              │                          │
│              ▼                          │
│  ┌────────────────────────────────┐    │
│  │ 5. Update Database             │    │
│  │    • Save to postmeta          │    │
│  │    • Add order note            │    │
│  │    • Log activity              │    │
│  └────────────────────────────────┘    │
└──────────┬──────────────────────────────┘
           │
           │ 6. JSON Response
           ▼
┌──────────────────────────────┐
│   JavaScript                 │
│   • Show success message     │
│   • Reload page              │
└──────────┬───────────────────┘
           │
           │ 7. Refresh Display
           ▼
┌──────────────────────────────┐
│   Updated Order Page         │
│   • AWB displayed            │
│   • Track button active      │
│   • Order note added         │
└──────────────────────────────┘
```

## File Structure

```
mo-aramex-shipping-integration/
│
├── includes/
│   ├── class-mo-aramex-awb-manager.php      [NEW] ⭐
│   │   ├── ajax_save_awb()
│   │   ├── ajax_delete_awb()
│   │   ├── enqueue_scripts()
│   │   └── render_awb_editor()
│   │
│   └── class-mo-aramex-order-meta-box.php   [MODIFIED] ✏️
│       └── render_meta_box()  (added AWB editor)
│
├── assets/
│   └── js/
│       └── awb-manager.js                    [NEW] ⭐
│           ├── Form submit handler
│           ├── Delete button handler
│           └── Message display
│
├── mo-aramex-shipping-integration.php        [MODIFIED] ✏️
│   └── Added: require AWB manager class
│
└── Documentation/
    ├── AWB_MANAGER_README.md                 [NEW] 📚
    ├── AWB_IMPLEMENTATION_SUMMARY.md         [NEW] 📚
    ├── AWB_QUICK_START.md                    [NEW] 📚
    ├── AWB_FEATURE_COMPLETE.md               [NEW] 📚
    └── AWB_ARCHITECTURE_DIAGRAM.md           [NEW] 📚
```

## Component Interaction

```
┌─────────────────────────────────────────────────────────────┐
│                    Main Plugin File                          │
│        mo-aramex-shipping-integration.php                    │
│                                                               │
│  require_once 'class-mo-aramex-order-meta-box.php'          │
│  require_once 'class-mo-aramex-awb-manager.php'  ⭐         │
└───────────────────────┬─────────────────────────────────────┘
                        │
        ┌───────────────┴───────────────┐
        │                               │
        ▼                               ▼
┌──────────────────┐          ┌─────────────────────┐
│  Order Meta Box  │          │   AWB Manager       │
│                  │          │                     │
│  • Displays AWB  │◄─────────┤  • AJAX Handlers   │
│  • Renders UI    │          │  • Validation      │
│  • Calls AWB     │          │  • Database Ops    │
│    editor        │          │  • Logging         │
└──────────────────┘          └──────┬──────────────┘
                                     │
                                     ▼
                            ┌─────────────────┐
                            │  JavaScript     │
                            │  awb-manager.js │
                            │                 │
                            │  • UI Logic     │
                            │  • AJAX Calls   │
                            │  • Validation   │
                            └─────────────────┘
```

## Database Schema

```
┌────────────────────────────────────────────────┐
│           wp_postmeta Table                    │
├────────────────────────────────────────────────┤
│                                                │
│  meta_id  │  post_id  │  meta_key      │ meta_value    │
│  ─────────┼───────────┼────────────────┼──────────────  │
│  12345    │  567      │ aramex_awb_no  │ 1234567890    │
│                                                │
│  ▲                                             │
│  │                                             │
│  └─── Created/Updated by AWB Manager          │
│                                                │
└────────────────────────────────────────────────┘

Associated Data:
• Order Notes (wp_comments)
• Activity Logs (custom log files)
```

## Security Architecture

```
┌────────────────────────────────────────────────┐
│              Security Layers                   │
├────────────────────────────────────────────────┤
│                                                │
│  ┌──────────────────────────────────────┐    │
│  │ Layer 1: Nonce Verification          │    │
│  │ • wp_verify_nonce()                  │    │
│  │ • Prevents CSRF attacks              │    │
│  └──────────────────────────────────────┘    │
│                 │                             │
│                 ▼                             │
│  ┌──────────────────────────────────────┐    │
│  │ Layer 2: Capability Check            │    │
│  │ • current_user_can('edit_shop_orders')│   │
│  │ • Role-based access control          │    │
│  └──────────────────────────────────────┘    │
│                 │                             │
│                 ▼                             │
│  ┌──────────────────────────────────────┐    │
│  │ Layer 3: Input Sanitization          │    │
│  │ • sanitize_text_field()              │    │
│  │ • intval()                           │    │
│  └──────────────────────────────────────┘    │
│                 │                             │
│                 ▼                             │
│  ┌──────────────────────────────────────┐    │
│  │ Layer 4: Validation                  │    │
│  │ • Numeric AWB check                  │    │
│  │ • Order exists check                 │    │
│  └──────────────────────────────────────┘    │
│                 │                             │
│                 ▼                             │
│  ┌──────────────────────────────────────┐    │
│  │ Layer 5: Output Escaping             │    │
│  │ • esc_html()                         │    │
│  │ • esc_attr()                         │    │
│  │ • esc_url()                          │    │
│  └──────────────────────────────────────┘    │
│                                                │
└────────────────────────────────────────────────┘
```

## AJAX Request Flow

```
┌──────────────┐
│  User Clicks │
│  Save AWB    │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────┐
│  JavaScript Validation              │
│  • Check if AWB is not empty        │
│  • Check if AWB is numeric          │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  jQuery AJAX POST                   │
│  URL: admin-ajax.php                │
│  Action: mo_aramex_save_awb         │
│  Data:                              │
│    • nonce                          │
│    • order_id                       │
│    • awb_number                     │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  PHP Handler                        │
│  ajax_save_awb()                    │
│  1. Verify nonce                    │
│  2. Check permissions               │
│  3. Validate data                   │
│  4. Update database                 │
│  5. Add order note                  │
│  6. Log activity                    │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  JSON Response                      │
│  Success: {                         │
│    success: true,                   │
│    data: {                          │
│      message: "AWB saved!",         │
│      awb_number: "1234567890",      │
│      track_url: "https://..."       │
│    }                                │
│  }                                  │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  JavaScript Success Handler         │
│  • Show success message             │
│  • Update UI elements               │
│  • Reload page after 1.5 seconds    │
└─────────────────────────────────────┘
```

## State Management

```
┌──────────────────────────────────────────┐
│           AWB State Flow                 │
├──────────────────────────────────────────┤
│                                          │
│  [No AWB]                                │
│     │                                    │
│     │ User enters AWB                    │
│     ▼                                    │
│  [AWB Set] ──────────────────┐          │
│     │                         │          │
│     │ User updates AWB        │          │
│     ▼                         │          │
│  [AWB Updated]                │          │
│     │                         │          │
│     │                         │ Delete   │
│     └─────────────────────────┘          │
│                               │          │
│                               ▼          │
│                          [No AWB]        │
│                                          │
└──────────────────────────────────────────┘

Each state change:
• Triggers order note
• Creates log entry
• Updates database
• Refreshes display
```

## Logging Flow

```
┌────────────────────────────┐
│   AWB Action Triggered     │
└────────────┬───────────────┘
             │
             ▼
┌────────────────────────────┐
│  custom_plugin_log()       │
│  called with:              │
│  • Action type             │
│  • Order ID                │
│  • User                    │
│  • AWB number              │
│  • Timestamp               │
└────────────┬───────────────┘
             │
             ▼
┌────────────────────────────┐
│  Log File Created/Updated  │
│  Location:                 │
│  /uploads/aramex-shipping- │
│   plugin-logs/YYYY-MM-DD.log│
└────────────────────────────┘
```

## Summary

This architecture provides:
- ✅ Clean separation of concerns
- ✅ Secure data handling
- ✅ Comprehensive logging
- ✅ User-friendly interface
- ✅ WordPress standards compliance
- ✅ Extensible design

---

**Key Technologies:**
- PHP 7.4+ (Backend)
- jQuery (Frontend)
- WordPress API (Framework)
- WooCommerce API (E-commerce)
- AJAX (Communication)

