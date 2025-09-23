# Plugin Update Checker Implementation Instructions

## Overview
This document provides step-by-step instructions for implementing a plugin update checker using the Plugin Update Checker library by YahnisElsts. Based on our successful implementation for the MO Aramex Shipping Integration plugin.

## Prerequisites
- WordPress plugin with proper structure
- Plugin Update Checker library (v5.6)
- GitHub repository for hosting updates
- Basic understanding of PHP and WordPress hooks

## Implementation Methods

### Method 1: Custom Update Server (Recommended)
This method uses a custom `update-info.json` file to avoid GitHub API rate limiting.

#### Step 1: Create update-info.json File
Create a file named `update-info.json` in your plugin root directory:

```json
{
    "name": "Your Plugin Name",
    "slug": "your-plugin-slug",
    "version": "1.0.0",
    "tested": "6.4",
    "requires": "5.3",
    "requires_php": "7.4",
    "last_updated": "2025-01-22",
    "homepage": "https://github.com/yourusername/your-repo",
    "author": "Your Name",
    "author_profile": "https://github.com/yourusername",
    "download_url": "https://github.com/yourusername/your-repo/archive/refs/heads/master.zip",
    "sections": {
        "description": "Your plugin description here.",
        "installation": "Installation instructions here.",
        "changelog": "<h4>Version 1.0.0</h4><ul><li>Initial release</li></ul>",
        "faq": "<h4>FAQ Question</h4><p>FAQ Answer</p>"
    },
    "banners": {
        "low": "https://github.com/yourusername/your-repo/raw/master/assets/banner-772x250.png",
        "high": "https://github.com/yourusername/your-repo/raw/master/assets/banner-1544x500.png"
    }
}
```

#### Step 2: Include Plugin Update Checker Library
Add this to your main plugin file:

```php
// Include the Plugin Update Checker library
require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
```

#### Step 3: Initialize Update Checker
Add this code to your main plugin file:

```php
// Initialize update checker
$update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/yourusername/your-repo/raw/master/update-info.json',
    __FILE__,
    'your-plugin-slug'
);

// Add custom headers to avoid rate limiting
if (method_exists($update_checker, 'addHttpRequestArgFilter')) {
    $update_checker->addHttpRequestArgFilter(function($options) {
        if (!isset($options['headers'])) {
            $options['headers'] = array();
        }
        
        $options['headers']['User-Agent'] = 'Your-Plugin-Name/1.0.0';
        $options['headers']['Accept'] = 'application/vnd.github.v3+json';
        $options['headers']['X-Your-Plugin'] = 'Your Plugin Name';
        $options['headers']['X-Plugin-Version'] = '1.0.0';
        
        return $options;
    });
}
```

### Method 2: GitHub VCS Integration
This method uses GitHub's VCS API directly (may have rate limiting issues).

#### Step 1: Initialize GitHub Update Checker
```php
// Include the Plugin Update Checker library
require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize update checker
$update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/yourusername/your-repo.git',
    __FILE__,
    'your-plugin-slug'
);

// Set branch (only for VCS-based checkers)
$update_checker->setBranch('master');

// Configure VCS API
$vcs_api = $update_checker->getVcsApi();
if ($vcs_api) {
    // Disable release assets checking
    $vcs_api->enableReleaseAssets(false);
    
    // Set authentication if needed (for private repos)
    // $vcs_api->setAuthentication('your-github-token');
}

// Add custom headers
if (method_exists($update_checker, 'addHttpRequestArgFilter')) {
    $update_checker->addHttpRequestArgFilter(function($options) {
        if (!isset($options['headers'])) {
            $options['headers'] = array();
        }
        
        $options['headers']['User-Agent'] = 'Your-Plugin-Name/1.0.0';
        $options['headers']['Accept'] = 'application/vnd.github.v3+json';
        
        return $options;
    });
}
```

## Common Issues and Solutions

### Issue 1: PHP Fatal Error - Call to undefined method setBranch()
**Problem**: Using `setBranch()` with custom update server
**Solution**: `setBranch()` only works with VCS-based checkers, not custom update servers

```php
// ❌ Wrong - Don't use setBranch() with custom update server
$update_checker = PucFactory::buildUpdateChecker('update-info.json', ...);
$update_checker->setBranch('master'); // This will cause fatal error

// ✅ Correct - No setBranch() needed for custom update server
$update_checker = PucFactory::buildUpdateChecker('update-info.json', ...);
// That's it - no setBranch() needed
```

### Issue 2: PHP Fatal Error - Call to undefined method setHttpFilter()
**Problem**: Using incorrect method name
**Solution**: Use the correct method name `addHttpRequestArgFilter()`

```php
// ❌ Wrong - setHttpFilter() doesn't exist
$vcs_api->setHttpFilter(function($options) { ... });

// ✅ Correct - Use addHttpRequestArgFilter()
$update_checker->addHttpRequestArgFilter(function($options) { ... });
```

### Issue 3: GitHub API 403 Rate Limit Errors
**Problem**: GitHub API rate limiting
**Solution**: Use custom update server instead of GitHub API directly

```php
// ❌ Problematic - Direct GitHub API (rate limited)
$update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/username/repo.git',
    __FILE__,
    'plugin-slug'
);

// ✅ Better - Custom update server (no rate limits)
$update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/username/repo/raw/master/update-info.json',
    __FILE__,
    'plugin-slug'
);
```

### Issue 4: GitHub API 404 Errors
**Problem**: Missing GitHub releases or tags
**Solution**: Create proper GitHub releases or use custom update server

**Option A**: Create GitHub Release
1. Go to your GitHub repository
2. Click "Releases" → "Create a new release"
3. Set tag version, release title, and description
4. Publish the release

**Option B**: Use Custom Update Server (Recommended)
- Create `update-info.json` file as shown in Method 1

## Debugging

### Debug Page Implementation
Create a debug page to troubleshoot update checker issues:

```php
class Plugin_Update_Debug {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_menu'));
    }
    
    public function add_debug_menu() {
        add_submenu_page(
            'tools.php',
            'Plugin Update Debug',
            'Plugin Update Debug',
            'manage_options',
            'plugin-update-debug',
            array($this, 'debug_page')
        );
    }
    
    public function debug_page() {
        global $update_checker;
        
        echo '<div class="wrap">';
        echo '<h1>Plugin Update Checker Debug</h1>';
        
        if ($update_checker) {
            echo '<p><strong>Status:</strong> Initialized</p>';
            
            try {
                $update_info = $update_checker->getUpdate();
                if ($update_info) {
                    echo '<p><strong>Update Available:</strong> Yes</p>';
                    echo '<p><strong>New Version:</strong> ' . $update_info->version . '</p>';
                } else {
                    echo '<p><strong>Update Available:</strong> No (up to date)</p>';
                }
            } catch (Exception $e) {
                echo '<p><strong>Error:</strong> ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p><strong>Status:</strong> Not Initialized</p>';
        }
        
        echo '</div>';
    }
}

// Initialize debug class
new Plugin_Update_Debug();
```

## Best Practices

### 1. Use Custom Update Server
- Avoids GitHub API rate limiting
- More reliable and faster
- Easier to maintain

### 2. Proper Error Handling
```php
try {
    $update_checker = PucFactory::buildUpdateChecker(...);
    // Configuration code here
} catch (Exception $e) {
    error_log('Update checker initialization failed: ' . $e->getMessage());
}
```

### 3. Version Management
- Update version in plugin header
- Update version in update-info.json
- Create git tags for releases

### 4. Security
- Use nonces for AJAX requests
- Sanitize all inputs
- Validate update sources

## Testing

### Test Update Checker
1. Create a test version with higher version number
2. Update update-info.json with new version
3. Push changes to repository
4. Check for updates in WordPress admin
5. Verify update notification appears

### Test Update Process
1. Click "Update Now" button
2. Verify plugin updates successfully
3. Check that new version is active
4. Verify all functionality works

## File Structure
```
your-plugin/
├── your-plugin.php          # Main plugin file with update checker
├── update-info.json         # Update information (Method 1)
├── plugin-update-checker/   # Update checker library
│   ├── plugin-update-checker.php
│   └── Puc/
└── includes/
    └── class-plugin-debug.php  # Debug class (optional)
```

## Troubleshooting Checklist

- [ ] Plugin Update Checker library included correctly
- [ ] Correct method names used (addHttpRequestArgFilter, not setHttpFilter)
- [ ] No setBranch() with custom update server
- [ ] update-info.json file exists and is accessible
- [ ] Version numbers match between plugin and update-info.json
- [ ] GitHub repository is public or authentication is configured
- [ ] Custom headers are properly set
- [ ] Error handling is implemented
- [ ] Debug page shows correct status

## Support Resources

- [Plugin Update Checker Documentation](https://github.com/YahnisElsts/plugin-update-checker)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [GitHub API Documentation](https://docs.github.com/en/rest)

---

**Note**: This implementation is based on the successful MO Aramex Shipping Integration plugin update checker. Always test thoroughly in a development environment before deploying to production.
