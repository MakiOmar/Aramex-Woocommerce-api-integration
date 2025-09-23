# MO Aramex Shipping Integration - Installation Guide

## Prerequisites

- WordPress 5.3 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher
- SOAP extension enabled

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Go to WooCommerce → Settings → Shipping → MO Aramex Shipping
5. Configure your Aramex API credentials

### Method 2: Manual Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Configure your Aramex API credentials

## Dependencies Installation (Optional)

The plugin includes PDF merging functionality for bulk label printing. If you need this feature, you can install the required dependencies:

### Using Composer (Recommended)

1. Navigate to the plugin directory:
   ```bash
   cd /wp-content/plugins/mo-aramex-shipping-integration/
   ```

2. Install dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

### Manual Installation

If you don't have Composer, you can manually download the required libraries:

1. Download the following packages:
   - [jurosh/pdf-merge](https://github.com/jurosh/pdf-merge)
   - [setasign/fpdf](https://github.com/Setasign/FPDF)
   - [setasign/fpdi](https://github.com/Setasign/FPDI)

2. Extract them to the `vendor/` directory in the plugin folder

## Configuration

1. Go to **WooCommerce → Settings → Shipping → MO Aramex Shipping**
2. Enter your Aramex API credentials:
   - Account Number
   - Username
   - Password
   - Account PIN
   - Account Entity
   - Account Country Code
3. Configure shipping zones and methods
4. Save settings

## Features

- ✅ Single shipment creation
- ✅ Bulk shipment creation
- ✅ Label printing (single and bulk)
- ✅ Rate calculation
- ✅ Shipment tracking
- ✅ Pickup scheduling
- ✅ Comprehensive logging system
- ✅ Admin interface for log management
- ✅ Plugin update checker

## Troubleshooting

### PDF Merging Not Working

If you see errors related to PDF merging:

1. Install dependencies using Composer:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. Or manually create the vendor directory and install the required libraries

### API Connection Issues

1. Verify your Aramex API credentials
2. Check the logs in **WooCommerce → MO Aramex Logs**
3. Ensure SOAP extension is enabled in PHP

### Update Issues

1. Go to **Tools → MO Aramex Update Debug** to check update checker status
2. Ensure the plugin has proper file permissions

## Support

For support and questions, contact:
- Email: maki3omar@gmail.com
- GitHub: https://github.com/MakiOmar

## Changelog

See the plugin's update information for detailed changelog.

## License

This plugin is licensed under the GPL v2 or later.
