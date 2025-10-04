# Aramex Shipping API Documentation

## Overview
This document provides a comprehensive reference for the Aramex International Shipping API endpoints. The API supports multiple protocols including SOAP, REST/JSON, and REST/XML.

**Base URL**: `https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc`

---

## Service Endpoints

### 1. CreateShipments
Create new shipment orders in the Aramex system.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 2. PrintLabel
Generate and print shipping labels for created shipments.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 3. CreatePickup
Schedule a pickup request for shipments.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 4. CancelPickup
Cancel a previously scheduled pickup request.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 5. ReserveShipmentNumberRange
Reserve a range of shipment numbers for future use.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 6. GetLastShipmentsNumbersRange
Retrieve the last range of shipment numbers that were reserved.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

### 7. ScheduleDelivery
Schedule a delivery for shipments.

**Available Protocols:**
- **SOAP**: [/service_1_0.svc](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc)
- **REST/JSON**: [/service_1_0.svc/json](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/json)
- **REST/XML**: [/service_1_0.svc/xml](https://ws.dev.aramex.net/shippingapi.v2/shipping/service_1_0.svc/xml)

---

## Protocol Information

### SOAP
Use SOAP protocol for traditional web service integrations. Endpoints use the base service URL.

### REST/JSON
Use REST/JSON for modern API integrations with JSON request/response format. Append `/json` to the base URL.

### REST/XML
Use REST/XML for REST-based integrations with XML request/response format. Append `/xml` to the base URL.

---

## Environment
**Current Environment**: Development (dev)

For production use, replace `ws.dev.aramex.net` with the production URL provided by Aramex.

---

## Getting Started

1. **Authentication**: Contact Aramex to obtain API credentials
2. **Choose Protocol**: Select SOAP, REST/JSON, or REST/XML based on your integration needs
3. **Test Endpoints**: Use the development environment for testing
4. **Integration**: Implement the required methods for your shipping workflow

---

## Common Workflow

1. **ReserveShipmentNumberRange** - Reserve shipment numbers
2. **CreateShipments** - Create shipment with reserved numbers
3. **PrintLabel** - Generate shipping labels
4. **CreatePickup** - Schedule pickup for the shipment
5. **ScheduleDelivery** - Schedule delivery (if applicable)
6. **CancelPickup** - Cancel pickup if needed

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