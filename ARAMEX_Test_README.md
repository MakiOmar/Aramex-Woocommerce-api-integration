# Aramex Shipping API - Test Environment (JSON Only)

## Overview
Use the JSON REST API only. SOAP is not used.

**Base URL (JSON)**: `https://ws.sbx.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json`

---

## Service Endpoints (JSON)

- CreateShipments: `POST https://ws.sbx.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreateShipments`
- CreatePickup: `POST https://ws.sbx.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreatePickup`
- PrintLabel: `POST https://ws.sbx.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/PrintLabel`

---

## Minimal CreateShipments JSON Sample

```json
{
  "Shipments": [
    {
      "Shipper": {
        "Reference1": "10001",
        "AccountNumber": "{TEST_ACCOUNT_NUMBER}",
        "PartyAddress": {
          "Line1": "Address line",
          "City": "Riyadh",
          "PostCode": "00000",
          "CountryCode": "SA"
        },
        "Contact": {
          "PersonName": "Sender Name",
          "CompanyName": "Sender Co",
          "PhoneNumber1": "+966500000000",
          "EmailAddress": "sender@example.com"
        }
      },
      "Consignee": {
        "Reference1": "10001",
        "PartyAddress": {
          "Line1": "Customer address",
          "City": "Jeddah",
          "PostCode": "23456",
          "CountryCode": "SA"
        },
        "Contact": {
          "PersonName": "Customer Name",
          "CompanyName": "",
          "PhoneNumber1": "+966511111111",
          "EmailAddress": "customer@example.com"
        }
      },
      "Reference1": "10001",
      "TransportType": 0,
      "ShippingDateTime": 1737018820,
      "DueDate": 1737623620,
      "PickupLocation": "Reception",
      "Comments": "Order 10001",
      "Details": {
        "ActualWeight": { "Value": 0.5, "Unit": "KG" },
        "ProductGroup": "EXP",
        "ProductType": "EPX",
        "PaymentType": "P",
        "Services": "",
        "NumberOfPieces": 1,
        "DescriptionOfGoods": "Order items",
        "GoodsOriginCountry": "SA"
      }
    }
  ],
  "ClientInfo": {
    "UserName": "{TEST_USERNAME}",
    "Password": "{TEST_PASSWORD}",
    "Version": "1.0",
    "AccountNumber": "{TEST_ACCOUNT_NUMBER}",
    "AccountPin": "{TEST_ACCOUNT_PIN}",
    "AccountEntity": "{TEST_ACCOUNT_ENTITY}",
    "AccountCountryCode": "{TEST_ACCOUNT_COUNTRY}"
  },
  "LabelInfo": { "ReportID": 9729, "ReportType": "URL" },
  "Transaction": { "Reference1": "10001" }
}
```

Notes:
- Omit unsupported/unused fields to reduce errors.
- Do not include ClientInfo.Source unless explicitly required by Aramex.

---

## CreatePickup JSON Sample (with ExistingShipments)

Endpoint: `POST /CreatePickup`

Use the sample provided in the project `ARAMEX_CreatePickup_Collection.postman_collection.json`. Timestamp fields use the `/Date(…+TZ)/` format.

---

## Test Credentials

Configure in WordPress > WooCommerce > Settings > Shipping > MO Aramex Shipping:

- Test Email, Test Password
- Test Account Number, Test Account Pin
- Test Account Entity, Test Account Country Code
- Enable "Test Mode"

When Test Mode is enabled, requests are sent to `ws.sbx` using the Test credentials.

---

### 2. PrintLabel
Generate and print shipping labels for created shipments.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 3. CreatePickup
Schedule a pickup request for shipments.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 4. CancelPickup
Cancel a previously scheduled pickup request.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 5. ReserveShipmentNumberRange
Reserve a range of shipment numbers for future use.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 6. GetLastShipmentsNumbersRange
Retrieve the last range of shipment numbers that were reserved.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 7. ScheduleDelivery
Schedule a delivery for shipments.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.sbx.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

## Protocol Information

This environment uses JSON only. SOAP and REST/XML are not used.

---

## Environment
**Current Environment**: Sandbox (ws.sbx)

For production use, see the Live README.

---

## Getting Started

1. **Authentication**: Contact Aramex to obtain API credentials
2. **Enable Test Mode** in plugin settings and fill Test credentials
3. **Use JSON** endpoints documented above
4. **CreateShipments** then **PrintLabel**, optionally **CreatePickup**

---

## Common Workflow

1. **CreateShipments** - Create shipment
2. **PrintLabel** - Generate shipping labels
3. **CreatePickup** - Schedule pickup for the shipment (optional)

---

## Support

For technical support, API documentation, and credential requests, contact Aramex International support team.

---

## Notes

- All endpoints are available in three protocol formats
- Development environment should be used for testing only
- Ensure proper error handling for all API calls
- Keep credentials secure and never commit them to version control

---

**Document Version**: 1.0  
**Last Updated**: October 2025  
**API Version**: v2 (service_1_0)