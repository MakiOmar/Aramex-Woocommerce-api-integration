# MO Aramex Shipping Integration

## Overview

**MO Aramex Shipping Integration** is a professional white-labeled WordPress plugin that provides seamless integration between WooCommerce and Aramex Express shipping services. This plugin has been completely rebranded and optimized for Mohammad Omar (maki3omar@gmail.com).

## 🚀 Features

### Core Functionality
- **Complete Aramex Integration**: Full integration with Aramex Express shipping API
- **WooCommerce Compatibility**: Seamless integration with WooCommerce stores
- **Rate Calculation**: Real-time shipping rate calculation
- **Label Generation**: Automatic shipping label generation and printing
- **Bulk Operations**: Bulk shipment creation and label printing
- **Order Tracking**: Complete order tracking functionality
- **Pickup Scheduling**: Schedule pickup requests through Aramex

### White-Label Features
- **MO Branding**: Complete rebranding with Mohammad Omar's information
- **Professional Interface**: Clean, modern admin interface
- **Custom Styling**: Professional styling matching MO brand guidelines
- **Plugin Update Checker**: Automatic updates from GitHub repository
- **Enhanced Security**: Improved security with nonce verification and input sanitization

## 📋 Installation

### Requirements
- WordPress 5.3 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher
- SOAP extension enabled
- cURL extension enabled

### Installation Steps
1. Upload the plugin files to `/wp-content/plugins/mo-aramex-shipping-integration/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your Aramex credentials in WooCommerce > Settings > Shipping > MO Aramex Shipping
4. Set up your shipping zones and methods

## ⚙️ Configuration

### Aramex API Credentials
To use this plugin, you need the following Aramex API credentials:
- **Username**: Your Aramex API username
- **Password**: Your Aramex API password
- **Account PIN**: Your Aramex account PIN
- **Account Number**: Your Aramex account number
- **Account Entity**: Your Aramex account entity
- **Account Country Code**: Your Aramex account country code

### Settings Configuration
1. Go to **WooCommerce > Settings > Shipping**
2. Click on **MO Aramex Shipping**
3. Configure the following settings:
   - Enable/Disable the shipping method
   - Set method title and description
   - Enter your Aramex API credentials
   - Configure default shipment information
   - Set up domestic and international product groups

## 🔧 Technical Details

### File Structure
```
mo-aramex-shipping-integration/
├── mo-aramex-shipping-integration.php          # Main plugin file
├── includes/
│   ├── core/
│   │   └── class-mo-aramex-helper.php
│   ├── shipping/
│   │   ├── class-mo-aramex-shipping-method.php
│   │   └── data-mo-aramex-settings.php
│   └── class-mo-aramex-updater.php
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── templates/
├── wsdl/
├── plugin-update-checker/
└── README.md
```

### Key Classes
- **MO_Aramex_Plugin**: Main plugin class with singleton pattern
- **MO_Aramex_Shipping_Method**: WooCommerce shipping method implementation
- **MO_Aramex_Helper**: Helper class for API operations
- **MO_Aramex_Updater**: Plugin update checker integration

### API Integration
The plugin integrates with Aramex's SOAP-based API for:
- Rate calculation
- Shipment creation
- Label generation
- Tracking information
- Pickup scheduling

## 🔄 Updates

### Automatic Updates
The plugin includes an integrated update checker that:
- Checks for updates from the GitHub repository
- Provides one-click updates through WordPress admin
- Maintains plugin integrity during updates
- Supports license validation (optional)

### Manual Updates
1. Download the latest version from GitHub
2. Deactivate the current plugin
3. Replace the plugin files
4. Reactivate the plugin

## 🛡️ Security Features

### Enhanced Security
- **Nonce Verification**: All AJAX requests include nonce verification
- **Input Sanitization**: All user inputs are properly sanitized
- **Capability Checks**: Proper WordPress capability checks
- **SQL Injection Prevention**: All database queries use prepared statements
- **XSS Protection**: Output escaping for all user-generated content

### Data Protection
- Encrypted storage of sensitive API credentials
- Secure transmission of data to Aramex API
- No storage of customer personal information beyond what's necessary

## 🎨 Customization

### Styling
The plugin includes custom CSS that can be overridden:
- Admin interface styling
- Frontend display customization
- Responsive design for mobile devices

### Hooks and Filters
The plugin provides various WordPress hooks for customization:
- `mo_aramex_before_shipment_creation`
- `mo_aramex_after_shipment_creation`
- `mo_aramex_shipping_rates`
- `mo_aramex_tracking_info`

## 📊 Performance Optimizations

### Code Optimizations
- **Singleton Pattern**: Efficient memory usage
- **Lazy Loading**: Classes loaded only when needed
- **Caching**: API responses cached when appropriate
- **Database Optimization**: Efficient database queries
- **Asset Optimization**: Minified CSS and JavaScript

### Performance Features
- Asynchronous API calls where possible
- Efficient error handling
- Optimized database queries
- Minimal plugin footprint

## 🐛 Troubleshooting

### Common Issues

#### Plugin Not Activating
- Ensure WooCommerce is installed and active
- Check PHP version compatibility (7.4+)
- Verify SOAP extension is enabled

#### API Connection Issues
- Verify Aramex API credentials
- Check server firewall settings
- Ensure cURL extension is enabled
- Verify SSL certificate validity

#### Shipping Rates Not Showing
- Check shipping zone configuration
- Verify product weight and dimensions
- Ensure API credentials are correct
- Check WooCommerce shipping settings

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📞 Support

### Contact Information
- **Developer**: Mohammad Omar
- **Email**: maki3omar@gmail.com
- **GitHub**: https://github.com/MakiOmar/MO-Aramex-Shipping

### Support Channels
- GitHub Issues: For bug reports and feature requests
- Email Support: For direct technical support
- Documentation: Comprehensive documentation available

## 📄 License

This plugin is licensed under the GPL v2 or later license.

## 🔄 Changelog

### Version 1.0.0
- Initial white-labeled release
- Complete rebranding from original Aramex plugin
- Enhanced security features
- Plugin update checker integration
- Performance optimizations
- Professional admin interface
- Comprehensive documentation

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📚 Additional Resources

- [Aramex Developer Documentation](https://www.aramex.com/solutions-services/developers-solutions-center)
- [WooCommerce Shipping Documentation](https://woocommerce.com/document/shipping/)
- [WordPress Plugin Development Guide](https://developer.wordpress.org/plugins/)

---

**MO Aramex Shipping Integration** - Professional shipping solutions for WooCommerce stores.
