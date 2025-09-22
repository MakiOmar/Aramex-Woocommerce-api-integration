<?php
/**
 * Script to create GitHub release for MO Aramex Shipping Integration
 * Run this script to create a proper GitHub release
 */

// GitHub repository information
$repo_owner = 'MakiOmar';
$repo_name = 'Aramex-Woocommerce-api-integration';
$tag_name = 'v1.0.1';
$release_name = 'MO Aramex Shipping Integration v1.0.1';
$release_body = '## Version 1.0.1 - Fixed Update Checker and Enhanced Functionality

### Bug Fixes:
- Fixed duplicate update checker URLs
- Resolved 404 errors in update checker
- Fixed AJAX 400 errors in bulk shipment
- Fixed PHP Fatal error with setHttpFilter method
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
- SOAP extension';

// GitHub API endpoint
$api_url = "https://api.github.com/repos/{$repo_owner}/{$repo_name}/releases";

// Release data
$release_data = array(
    'tag_name' => $tag_name,
    'target_commitish' => 'master',
    'name' => $release_name,
    'body' => $release_body,
    'draft' => false,
    'prerelease' => false
);

// Convert to JSON
$json_data = json_encode($release_data);

// cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'User-Agent: MO-Aramex-Plugin/1.0.1',
    'Accept: application/vnd.github.v3+json'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $response . "\n";

if ($http_code === 201) {
    echo "✅ Release created successfully!\n";
} else {
    echo "❌ Failed to create release. You may need to create it manually via GitHub web interface.\n";
    echo "Go to: https://github.com/{$repo_owner}/{$repo_name}/releases/new\n";
}
?>
