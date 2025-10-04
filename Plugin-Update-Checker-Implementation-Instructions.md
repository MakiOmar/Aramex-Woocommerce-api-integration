# Plugin Update Checker Implementation Instructions

## Overview
This document provides step-by-step instructions for implementing a plugin update checker using the Plugin Update Checker library by YahnisElsts. **Updated based on successful implementation for WooCommerce Address Sync plugin with real-world solutions to common issues.**

## Prerequisites
- WordPress plugin with proper structure
- Plugin Update Checker library (v5.6)
- GitHub repository for hosting updates
- Basic understanding of PHP and WordPress hooks

## Implementation Methods

### Method 1: Custom Update Server (✅ RECOMMENDED - Proven Solution)
This method uses a custom `update-info.json` file to avoid GitHub API rate limiting. **This is the most reliable method based on actual production experience.**

#### Step 1: Create update-info.json File
Create a file named `update-info.json` in your plugin root directory:

```json
{
    "name": "Your Plugin Name",
    "slug": "your-plugin-slug",
    "version": "1.0.0",
    "tested": "6.7",
    "requires": "5.0",
    "requires_php": "7.4",
    "last_updated": "2025-10-01",
    "homepage": "https://github.com/yourusername/your-repo",
    "author": "Your Name",
    "author_profile": "https://github.com/yourusername",
    "download_url": "https://github.com/yourusername/your-repo/archive/refs/heads/main.zip",
    "sections": {
        "description": "Your plugin description here.",
        "installation": "<h4>Installation</h4><ol><li>Upload the plugin files to /wp-content/plugins/your-plugin/ directory</li><li>Activate the plugin through the 'Plugins' menu in WordPress</li></ol>",
        "changelog": "<h4>Version 1.0.0</h4><ul><li>Initial release</li></ul>",
        "faq": "<h4>How does the plugin work?</h4><p>Plugin description and FAQ answer.</p>"
    },
    "banners": {
        "low": "",
        "high": ""
    }
}
```

#### Step 2: Include Plugin Update Checker Library
Add this to your main plugin file **after the plugin header but before any classes**:

```php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the Plugin Update Checker library
require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
```

#### Step 3: Initialize Update Checker (✅ TESTED & WORKING)
Add this code to your main plugin file:

```php
// Initialize update checker
$your_plugin_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/yourusername/your-repo/raw/main/update-info.json',
    __FILE__,
    'your-plugin-slug'
);

// Add custom headers to avoid rate limiting
if (method_exists($your_plugin_update_checker, 'addHttpRequestArgFilter')) {
    $your_plugin_update_checker->addHttpRequestArgFilter(function($options) {
        if (!isset($options['headers'])) {
            $options['headers'] = array();
        }
        
        $options['headers']['User-Agent'] = 'Your-Plugin-Name/1.0.0';
        $options['headers']['Accept'] = 'application/vnd.github.v3+json';
        $options['headers']['X-Plugin-Name'] = 'Your Plugin Name';
        $options['headers']['X-Plugin-Version'] = '1.0.0';
        $options['headers']['Cache-Control'] = 'no-cache';
        
        return $options;
    });
}

// Enable debug mode if WP_DEBUG is on
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_filter('puc_manual_final_check-your-plugin-slug', '__return_true');
}
```

**⚠️ IMPORTANT NOTES:**
- Use a **unique global variable name** (not just `$update_checker`) to avoid conflicts
- Note: Branch is `main` not `master` for newer GitHub repos
- The `Cache-Control: no-cache` header helps prevent caching issues

## Common Issues and Solutions (Based on Real Experience)

### Issue 1: ❌ PHP Fatal Error - Call to undefined method setCheckPeriod()
**Problem**: Using `setCheckPeriod()` which doesn't exist in Plugin Update Checker v5.6

**Our Experience**: This caused a fatal error that prevented the entire plugin from activating.

**Solution**: DO NOT use `setCheckPeriod()` - it doesn't exist in v5.6

```php
// ❌ WRONG - This will cause FATAL ERROR in v5.6
$update_checker = PucFactory::buildUpdateChecker(...);
$update_checker->setCheckPeriod(12); // FATAL ERROR!

// ✅ CORRECT - The library handles check period internally
$update_checker = PucFactory::buildUpdateChecker(...);
// Don't call setCheckPeriod() at all
```

### Issue 2: ❌ PHP Fatal Error - Call to undefined method setBranch()
**Problem**: Using `setBranch()` with custom update server

**Solution**: `setBranch()` only works with VCS-based checkers, NOT custom update servers

```php
// ❌ WRONG - Don't use setBranch() with custom update server
$update_checker = PucFactory::buildUpdateChecker('update-info.json', ...);
$update_checker->setBranch('main'); // FATAL ERROR!

// ✅ CORRECT - No setBranch() needed for custom update server
$update_checker = PucFactory::buildUpdateChecker('update-info.json', ...);
// That's it - no setBranch() needed
```

### Issue 3: 🔴 Updates Not Showing ("Always Up to Date" Problem)
**Problem**: WordPress caches update checks, so new versions don't appear even when available on GitHub

**Our Experience**: Had v1.0.0 installed, v1.0.1 on GitHub, but WordPress kept saying "up to date"

**Root Cause**: WordPress caches update information for 12+ hours in transients

**Solution**: Implement a debug page with cache clearing capability

## ✅ ESSENTIAL: Update Checker Debug Page (HIGHLY RECOMMENDED)

Based on our experience, **you MUST have a way to clear update cache for testing**. Here's the complete working implementation:

```php
/**
 * Add update checker debug page to admin menu
 */
public function add_admin_menu() {
    // Add your main plugin menu
    // ...
    
    // Add update checker debug page
    add_submenu_page(
        'tools.php', // or your plugin's menu slug
        __('Plugin Update Debug', 'your-textdomain'),
        __('Update Checker', 'your-textdomain'),
        'manage_options',
        'your-plugin-updates',
        array($this, 'updates_debug_page')
    );
}

/**
 * Updates debug page - CRITICAL FOR TESTING
 */
public function updates_debug_page() {
    global $your_plugin_update_checker;
    
    // Handle clear cache action
    if (isset($_POST['clear_update_cache']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_update_cache')) {
        delete_site_transient('update_plugins');
        delete_transient('puc_request_info-your-plugin-slug');
        
        if ($your_plugin_update_checker) {
            $your_plugin_update_checker->resetUpdateState();
        }
        
        echo '<div class="notice notice-success"><p>Update cache cleared! Click "Force Check" below.</p></div>';
    }
    
    // Handle force check action
    if (isset($_POST['force_check']) && wp_verify_nonce($_POST['_wpnonce'], 'force_check_updates')) {
        if ($your_plugin_update_checker) {
            $your_plugin_update_checker->checkForUpdates();
        }
        echo '<div class="notice notice-success"><p>Forced update check completed!</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1><?php _e('Plugin Update Checker Debug', 'your-textdomain'); ?></h1>
        
        <div class="card">
            <h2>Current Status</h2>
            <?php
            $plugin_file = WP_PLUGIN_DIR . '/your-plugin/your-plugin.php';
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $plugin_data = get_plugin_data($plugin_file);
            $current_version = $plugin_data['Version'];
            ?>
            <p><strong>Installed Version:</strong> <?php echo esc_html($current_version); ?></p>
            <p><strong>Update Checker Status:</strong> <?php echo $your_plugin_update_checker ? 'Initialized ✓' : 'Not Initialized ✗'; ?></p>
            <p><strong>Update Info URL:</strong> <a href="https://github.com/yourusername/your-repo/raw/main/update-info.json" target="_blank">View update-info.json</a></p>
            
            <?php if ($your_plugin_update_checker): ?>
                <?php
                $update = $your_plugin_update_checker->getUpdate();
                ?>
                <p><strong>Update Available:</strong> 
                    <?php if ($update): ?>
                        <span style="color: green;">Yes - Version <?php echo esc_html($update->version); ?></span>
                    <?php else: ?>
                        <span>No (up to date)</span>
                    <?php endif; ?>
                </p>
                
                <?php if ($update): ?>
                    <div style="background: #e7f7e7; padding: 15px; border-left: 4px solid green; margin: 15px 0;">
                        <h3 style="margin-top: 0;">New Version Available: <?php echo esc_html($update->version); ?></h3>
                        <p><strong>Download URL:</strong> <?php echo esc_html($update->download_url); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Actions</h2>
            <form method="post" style="display: inline-block; margin-right: 10px;">
                <?php wp_nonce_field('clear_update_cache'); ?>
                <button type="submit" name="clear_update_cache" class="button button-primary">Clear Update Cache</button>
                <p class="description">Clears WordPress update cache and plugin update checker cache</p>
            </form>
            
            <form method="post" style="display: inline-block;">
                <?php wp_nonce_field('force_check_updates'); ?>
                <button type="submit" name="force_check" class="button">Force Check for Updates</button>
                <p class="description">Immediately checks for updates from GitHub</p>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Troubleshooting Steps</h2>
            <ol>
                <li>Click "Clear Update Cache" to remove cached update data</li>
                <li>Click "Force Check for Updates" to check GitHub immediately</li>
                <li>Go to Dashboard → Updates to see if update appears</li>
                <li>Click the update-info.json link above to verify it's accessible</li>
            </ol>
            
            <h3>Cache Transients Status</h3>
            <ul>
                <li>WordPress update_plugins: <?php echo get_site_transient('update_plugins') ? '✓ Cached' : '✗ Not cached'; ?></li>
                <li>PUC request info: <?php echo get_transient('puc_request_info-your-plugin-slug') ? '✓ Cached' : '✗ Not cached'; ?></li>
            </ul>
        </div>
    </div>
    <?php
}
```

## Real Working Example (WooCommerce Address Sync)

Here's the exact implementation that works in production:

```php
<?php
/**
 * Plugin Name: WooCommerce Address Sync
 * Version: 1.0.3
 * ...
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the Plugin Update Checker library
require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize update checker with unique variable name
$wc_address_sync_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/MakiOmar/WooCommerce-Address-Sync/raw/main/update-info.json',
    __FILE__,
    'woocommerce-address-sync'
);

// Add custom headers
if (method_exists($wc_address_sync_update_checker, 'addHttpRequestArgFilter')) {
    $wc_address_sync_update_checker->addHttpRequestArgFilter(function($options) {
        if (!isset($options['headers'])) {
            $options['headers'] = array();
        }
        
        $options['headers']['User-Agent'] = 'WooCommerce-Address-Sync/1.0.3';
        $options['headers']['Accept'] = 'application/vnd.github.v3+json';
        $options['headers']['X-Plugin-Name'] = 'WooCommerce Address Sync';
        $options['headers']['X-Plugin-Version'] = '1.0.3';
        $options['headers']['Cache-Control'] = 'no-cache';
        
        return $options;
    });
}

// Enable debug mode if WP_DEBUG is on
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_filter('puc_manual_final_check-woocommerce-address-sync', '__return_true');
}

// Rest of your plugin code...
```

## Version Release Process (Step-by-Step)

When releasing a new version:

### 1. Update Version Numbers
Update in **BOTH** places:

**In main plugin file:**
```php
/**
 * Version: 1.0.1
 */
```

**In update-info.json:**
```json
{
    "version": "1.0.1",
    "last_updated": "2025-10-01",
    "changelog": "<h4>Version 1.0.1</h4><ul><li>New feature</li></ul>"
}
```

### 2. Update HTTP Headers
```php
$options['headers']['User-Agent'] = 'Your-Plugin/1.0.1'; // Update version
$options['headers']['X-Plugin-Version'] = '1.0.1'; // Update version
```

### 3. Commit and Push to GitHub
```bash
git add your-plugin.php update-info.json
git commit -m "Release version 1.0.1"
git push origin main
```

### 4. Test Update Detection

**On production site with old version:**
1. Go to your plugin's Update Checker debug page
2. Click "Clear Update Cache"
3. Click "Force Check for Updates"
4. Go to Dashboard → Updates
5. You should see the update notification

## Best Practices (Learned from Experience)

### 1. ✅ Always Use Custom Update Server
- Avoids GitHub API rate limiting (403 errors)
- More reliable and faster
- Easier to maintain
- No need for GitHub releases/tags

### 2. ✅ Use Unique Global Variable Name
```php
// ❌ BAD - Generic name may conflict
$update_checker = PucFactory::buildUpdateChecker(...);

// ✅ GOOD - Unique plugin-specific name
$your_plugin_update_checker = PucFactory::buildUpdateChecker(...);
```

### 3. ✅ Include Cache-Control Header
```php
$options['headers']['Cache-Control'] = 'no-cache';
```
This helps prevent GitHub from caching the JSON file.

### 4. ✅ Always Have a Debug Page
You NEED to be able to:
- See current version
- See available updates
- Clear cache manually
- Force update check
- View cache status

### 5. ✅ Version Management Checklist
- [ ] Update version in plugin header
- [ ] Update version in update-info.json
- [ ] Update version in HTTP headers
- [ ] Update changelog in update-info.json
- [ ] Commit and push to GitHub
- [ ] Test update detection on production

### 6. ❌ DON'T Use These Methods (They Don't Exist in v5.6)
```php
// ❌ FATAL ERRORS - Don't use these:
$update_checker->setCheckPeriod(12);     // Doesn't exist in v5.6!
$update_checker->setBranch('main');       // Only for VCS checkers!
$vcs_api->setHttpFilter(function...);    // Wrong method name!
```

## Troubleshooting Checklist (Updated)

- [ ] Plugin Update Checker library included correctly
- [ ] Using `addHttpRequestArgFilter()` not `setHttpFilter()`
- [ ] NOT using `setBranch()` with custom update server
- [ ] NOT using `setCheckPeriod()` (doesn't exist in v5.6)
- [ ] `update-info.json` file exists and is publicly accessible
- [ ] Version numbers match in plugin header AND update-info.json
- [ ] Version numbers updated in HTTP headers
- [ ] GitHub repository is public
- [ ] Custom headers properly set including Cache-Control
- [ ] Debug page implemented with cache clearing
- [ ] Tested cache clearing and force update check

## Common Error Messages and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| `Call to undefined method setCheckPeriod()` | Using non-existent method | Remove `setCheckPeriod()` call |
| `Call to undefined method setBranch()` | Using VCS method with custom server | Remove `setBranch()` call |
| "Always up to date" | WordPress cache | Clear cache using debug page |
| 403 Forbidden | GitHub rate limiting | Use custom update server method |
| 404 Not Found | update-info.json not accessible | Check URL and file permissions |
| Fatal error on activation | Syntax error in update checker code | Check all method names and syntax |

## Testing Workflow

### Initial Setup Testing
1. Install plugin on test site
2. Verify update checker initializes without errors
3. Check debug page displays correctly
4. Verify current version shows correctly

### Update Testing
1. Increment version in main plugin and update-info.json
2. Push to GitHub
3. On test site: Clear cache
4. Force check for updates
5. Verify update notification appears
6. Click "Update Now"
7. Verify new version installed successfully
8. Check all plugin features still work

## File Structure
```
your-plugin/
├── your-plugin.php                    # Main plugin file with update checker
├── update-info.json                   # Update information (REQUIRED)
├── plugin-update-checker/             # Update checker library (v5.6)
│   ├── plugin-update-checker.php
│   └── Puc/
├── includes/
│   └── class-plugin-admin.php         # Include debug page here
└── README.md
```

## Support Resources

- [Plugin Update Checker Documentation](https://github.com/YahnisElsts/plugin-update-checker)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [GitHub API Documentation](https://docs.github.com/en/rest)
- [WooCommerce Address Sync - Real Example](https://github.com/MakiOmar/WooCommerce-Address-Sync)

---

**Note**: This implementation is based on the **WooCommerce Address Sync plugin** which uses Plugin Update Checker v5.6 in production. All issues and solutions documented here are from real implementation experience. The debug page with cache clearing is **essential** for reliable update detection.

**Last Updated**: October 2025 - Based on Plugin Update Checker v5.6
