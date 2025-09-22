# Create GitHub Release to Fix Update Checker 404 Errors

The update checker is getting 404 errors because it needs a proper GitHub release, not just tags.

## Method 1: Using GitHub Web Interface (Recommended)

1. Go to: https://github.com/MakiOmar/Aramex-Woocommerce-api-integration
2. Click on "Releases" (on the right side)
3. Click "Create a new release"
4. Set the tag version to: `v1.0.1`
5. Set the release title to: `MO Aramex Shipping Integration v1.0.1`
6. Add release notes:
   ```
   ## Version 1.0.1 - Fixed Update Checker and Enhanced Functionality
   
   ### Bug Fixes:
   - Fixed duplicate update checker URLs
   - Resolved 404 errors in update checker
   - Fixed AJAX 400 errors in bulk shipment
   - Improved error handling and logging
   
   ### Enhancements:
   - Enhanced debug page functionality
   - Added comprehensive debugging tools
   - Improved bulk shipment error handling
   - Set default values for bulk forms
   - Auto-submit functionality for forms
   
   ### Technical Changes:
   - Removed conflicting updater class
   - Proper GitHub repository integration
   - Enhanced error handling with try-catch blocks
   - Added custom logging for debugging
   
   ### Requirements:
   - WordPress 5.3+
   - WooCommerce 3.0+
   - PHP 7.4+
   - SOAP extension
   ```
7. Click "Publish release"

## Method 2: Using GitHub CLI (if installed)

```bash
gh release create v1.0.1 --title "MO Aramex Shipping Integration v1.0.1" --notes "Version 1.0.1 - Fixed Update Checker and Enhanced Functionality"
```

## Method 3: Using cURL (if you have a GitHub token)

```bash
curl -X POST \
  -H "Authorization: token YOUR_GITHUB_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/repos/MakiOmar/Aramex-Woocommerce-api-integration/releases \
  -d '{
    "tag_name": "v1.0.1",
    "target_commitish": "master",
    "name": "MO Aramex Shipping Integration v1.0.1",
    "body": "Version 1.0.1 - Fixed Update Checker and Enhanced Functionality",
    "draft": false,
    "prerelease": false
  }'
```

## After Creating the Release

Once you create the release, the update checker should work properly. The plugin will be able to:
- Check for new versions
- Display update notifications in WordPress admin
- Allow users to update directly from WordPress

## Testing the Update Checker

1. Go to WordPress Admin → Plugins
2. Look for "MO Aramex Shipping Integration"
3. You should see update information if available
4. Check the "View version details" link to see release information
5. Use Tools → MO Aramex Update Debug to see detailed status
