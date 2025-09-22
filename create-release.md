# Creating a GitHub Release

To fix the update checker, you need to create a GitHub release. Here's how:

## Method 1: Using GitHub Web Interface

1. Go to: https://github.com/MakiOmar/Aramex-Woocommerce-api-integration
2. Click on "Releases" (on the right side)
3. Click "Create a new release"
4. Set the tag version to: `v1.0.0`
5. Set the release title to: `MO Aramex Shipping Integration v1.0.0`
6. Add release notes:
   ```
   ## Initial Release - MO Aramex Shipping Integration v1.0.0
   
   ### Features:
   - Professional Aramex shipping integration for WooCommerce
   - Bulk shipment creation and management
   - Label printing functionality
   - Rate calculation
   - Order tracking
   - White-labeled for MO
   
   ### Requirements:
   - WordPress 5.3+
   - WooCommerce 3.0+
   - PHP 7.4+
   - SOAP extension
   ```
7. Click "Publish release"

## Method 2: Using GitHub CLI (if installed)

```bash
gh release create v1.0.0 --title "MO Aramex Shipping Integration v1.0.0" --notes "Initial release with full Aramex integration features"
```

## Method 3: Using cURL

```bash
curl -X POST \
  -H "Authorization: token YOUR_GITHUB_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/repos/MakiOmar/Aramex-Woocommerce-api-integration/releases \
  -d '{
    "tag_name": "v1.0.0",
    "target_commitish": "master",
    "name": "MO Aramex Shipping Integration v1.0.0",
    "body": "Initial release with full Aramex integration features",
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
