# Aramex API Testing Guide

This guide provides comprehensive instructions for testing the Aramex Shipping API using Postman to diagnose connectivity issues with the MO Aramex Shipping Integration plugin.

## 📋 Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Setup Instructions](#setup-instructions)
4. [Testing Scenarios](#testing-scenarios)
5. [Troubleshooting](#troubleshooting)
6. [Expected Results](#expected-results)
7. [Common Issues](#common-issues)

## 🎯 Overview

The Postman collection includes tests for:
- **Test Environment**: Development/sandbox API endpoints
- **Live Environment**: Production API endpoints
- **SOAP Protocol**: Traditional web service calls
- **REST/JSON Protocol**: Modern API calls

## 📋 Prerequisites

### Required Software
- [Postman](https://www.postman.com/downloads/) (Latest version recommended)
- Valid Aramex API credentials (Test and/or Live)

### Required Information
- **Account Number**: Your Aramex account number
- **Account Pin**: Your Aramex account PIN
- **Username**: Your Aramex API username
- **Password**: Your Aramex API password
- **Account Entity**: Usually "RUH" for Saudi Arabia
- **Account Country Code**: Usually "SA" for Saudi Arabia

## 🚀 Setup Instructions

### Step 1: Import the Collection

1. Open Postman
2. Click **Import** button
3. Select `ARAMEX_API_Test_Collection.postman_collection.json`
4. Click **Import**

### Step 2: Configure Environment Variables

1. Click the **Environment** dropdown (top right)
2. Click **Manage Environments**
3. Click **Add** to create a new environment
4. Name it "Aramex Test Environment"
5. Add the following variables:

#### Test Environment Variables
```
Variable Name                | Initial Value                    | Current Value
----------------------------|----------------------------------|----------------------------
test_base_url              | https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json
test_soap_url              | https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc
test_account_number        | YOUR_TEST_ACCOUNT_NUMBER
test_account_pin           | YOUR_TEST_ACCOUNT_PIN
test_username              | YOUR_TEST_USERNAME
test_password              | YOUR_TEST_PASSWORD
test_reference             | TEST-{{$timestamp}}
test_shipper_address       | Your Test Shipper Address
test_shipper_city          | Riyadh
test_shipper_postcode      | 12345
test_shipper_country       | SA
test_shipper_name          | Test Shipper
test_shipper_company       | Test Company
test_shipper_phone         | +966501234567
test_shipper_email         | test@example.com
test_consignee_address     | Test Consignee Address
test_consignee_city        | Jeddah
test_consignee_postcode    | 23456
test_consignee_country     | SA
test_consignee_name        | Test Consignee
test_consignee_company     | Test Consignee Company
test_consignee_phone       | +966509876543
test_consignee_email       | consignee@example.com
```

#### Live Environment Variables (Optional)
```
Variable Name                | Initial Value                    | Current Value
----------------------------|----------------------------------|----------------------------
live_base_url              | https://ws.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json
live_soap_url              | https://ws.aramex.net/shippingapi.v2/shipping/service_1_0.svc
live_account_number        | YOUR_LIVE_ACCOUNT_NUMBER
live_account_pin           | YOUR_LIVE_ACCOUNT_PIN
live_username              | YOUR_LIVE_USERNAME
live_password              | YOUR_LIVE_PASSWORD
live_reference             | LIVE-{{$timestamp}}
live_shipper_address       | Your Live Shipper Address
live_shipper_city          | Riyadh
live_shipper_postcode      | 12345
live_shipper_country       | SA
live_shipper_name          | Live Shipper
live_shipper_company       | Live Company
live_shipper_phone         | +966501234567
live_shipper_email         | live@example.com
live_consignee_address     | Live Consignee Address
live_consignee_city        | Jeddah
live_consignee_postcode    | 23456
live_consignee_country     | SA
live_consignee_name        | Live Consignee
live_consignee_company     | Live Consignee Company
live_consignee_phone       | +966509876543
live_consignee_email       | liveconsignee@example.com
```

### Step 3: Update Your Credentials

1. Replace all `YOUR_*_*` placeholders with your actual credentials
2. Update addresses, names, and contact information as needed
3. Save the environment

### Step 4: Select Environment

1. Click the **Environment** dropdown (top right)
2. Select "Aramex Test Environment" (or your custom environment name)

## 🧪 Testing Scenarios

### Scenario 1: Test REST/JSON API

1. Navigate to **Test Environment** → **CreateShipment - Test**
2. Click **Send**
3. Check the response:
   - **Success**: HTTP 200 with shipment details
   - **Failure**: HTTP 4xx/5xx with error details

### Scenario 2: Test SOAP API

1. Navigate to **SOAP Endpoints** → **CreateShipment - SOAP Test**
2. Click **Send**
3. Check the response:
   - **Success**: HTTP 200 with SOAP XML response
   - **Failure**: HTTP 4xx/5xx with error details

### Scenario 3: Test Live Environment (Use with Caution)

1. Navigate to **Live Environment** → **CreateShipment - Live**
2. **⚠️ WARNING**: This creates real shipments with real costs
3. Click **Send** only if you're sure
4. Check the response

### Scenario 4: Connectivity Test

1. Try each endpoint in sequence
2. Document which ones work and which fail
3. Note any error messages or response codes

## 🔍 Troubleshooting

### Common HTTP Status Codes

| Code | Meaning | Possible Causes |
|------|---------|-----------------|
| 200 | Success | API call successful |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Invalid credentials |
| 403 | Forbidden | Account restrictions |
| 404 | Not Found | Endpoint doesn't exist |
| 500 | Server Error | Aramex server issue |
| 503 | Service Unavailable | Aramex service down |

### Network Connectivity Issues

If you get connection errors:

1. **Check DNS Resolution**
   ```bash
   nslookup ws.dev.aramex.net
   nslookup ws.aramex.net
   ```

2. **Test Basic Connectivity**
   ```bash
   ping ws.dev.aramex.net
   ping ws.aramex.net
   ```

3. **Test HTTPS Connectivity**
   ```bash
   curl -I https://ws.dev.aramex.net/
   curl -I https://ws.aramex.net/
   ```

4. **Check Firewall/Proxy Settings**
   - Ensure outbound HTTPS (port 443) is allowed
   - Check if corporate firewall blocks external APIs
   - Verify proxy settings if applicable

### Credential Issues

If you get authentication errors:

1. **Verify Account Details**
   - Double-check account number and PIN
   - Ensure username and password are correct
   - Confirm account entity and country code

2. **Check Account Status**
   - Contact Aramex to verify account is active
   - Ensure API access is enabled for your account
   - Check if there are any account restrictions

### API Endpoint Issues

If endpoints return 404 or connection errors:

1. **Verify URLs**
   - Test URLs should use `ws.dev.aramex.net`
   - Live URLs should use `ws.aramex.net`
   - Ensure `.v2` is included in the path

2. **Check Protocol Support**
   - REST/JSON: Append `/json` to base URL
   - SOAP: Use base URL without `/json`
   - XML: Append `/xml` to base URL

## ✅ Expected Results

### Successful Response (REST/JSON)
```json
{
    "Transaction": {
        "Reference1": "TEST-1234567890",
        "Reference2": "",
        "Reference3": "",
        "Reference4": "",
        "Reference5": ""
    },
    "Shipments": [
        {
            "Reference1": "TEST-1234567890",
            "Reference2": "",
            "Reference3": "",
            "Shipper": { ... },
            "Consignee": { ... },
            "ThirdParty": { ... },
            "Details": { ... }
        }
    ],
    "HasErrors": false,
    "Notifications": {
        "Notification": []
    }
}
```

### Successful Response (SOAP)
```xml
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <CreateShipmentsResponse xmlns="http://ws.aramex.net/ShippingAPI/v1/Service_1_0">
            <Transaction>
                <Reference1>TEST-1234567890</Reference1>
                ...
            </Transaction>
            <Shipments>
                <ProcessedShipment>
                    <ID>123456789</ID>
                    ...
                </ProcessedShipment>
            </Shipments>
            <HasErrors>false</HasErrors>
            <Notifications>
                <Notification />
            </Notifications>
        </CreateShipmentsResponse>
    </soap:Body>
</soap:Envelope>
```

## ❌ Common Issues

### Issue 1: "Could not connect to host"
**Cause**: Network connectivity problems
**Solutions**:
- Check internet connection
- Verify DNS resolution
- Check firewall settings
- Test from different network

### Issue 2: "401 Unauthorized"
**Cause**: Invalid credentials
**Solutions**:
- Verify account number and PIN
- Check username and password
- Contact Aramex for credential verification

### Issue 3: "404 Not Found"
**Cause**: Wrong endpoint URL
**Solutions**:
- Verify API endpoint URLs
- Check if `.v2` is included in path
- Ensure correct protocol (JSON/SOAP/XML)

### Issue 4: "400 Bad Request"
**Cause**: Invalid request data
**Solutions**:
- Check request payload format
- Verify required fields are present
- Validate data types and formats

### Issue 5: "500 Server Error"
**Cause**: Aramex server issues
**Solutions**:
- Try again later
- Contact Aramex support
- Check Aramex service status

## 📞 Support

### Aramex Support
- **Email**: Contact your Aramex account manager
- **Phone**: Check your Aramex account documentation
- **Documentation**: [Aramex Developer Portal](https://www.aramex.com/solutions-services/developers-solutions-center)

### Plugin Support
- **Email**: maki3omar@gmail.com
- **GitHub**: [MO Aramex Plugin Repository](https://github.com/MakiOmar/Aramex-Woocommerce-api-integration)

## 📝 Test Results Template

Use this template to document your test results:

```
Test Date: ___________
Tester: _____________
Environment: Test / Live / Both

REST/JSON Test Environment:
□ Success (200)
□ Failed (Error: __________)

SOAP Test Environment:
□ Success (200)
□ Failed (Error: __________)

REST/JSON Live Environment:
□ Success (200)
□ Failed (Error: __________)

Network Connectivity:
□ DNS Resolution: Working / Failed
□ HTTPS Connectivity: Working / Failed
□ Firewall/Proxy: None / Corporate / Unknown

Credentials:
□ Test Credentials: Valid / Invalid
□ Live Credentials: Valid / Invalid

Notes:
_________________________________
_________________________________
_________________________________
```

## 🔧 Next Steps

After completing the tests:

1. **Document Results**: Use the test results template above
2. **Identify Issues**: Note which endpoints work and which fail
3. **Contact Support**: Reach out to Aramex if API issues are found
4. **Update Plugin**: If API endpoints have changed, update the plugin accordingly
5. **Retest**: After fixes, run tests again to verify resolution

---

**Last Updated**: October 2025  
**Version**: 1.0  
**Compatible with**: Aramex API v2
