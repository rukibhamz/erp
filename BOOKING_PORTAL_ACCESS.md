# Booking Portal Access Guide

## Public Booking Access

The booking system is accessible to customers through a modern, multi-step booking wizard.

### Access URL

**Main Booking Portal:**
```
http://localhost/erp/booking-wizard
```

This is the enhanced multi-step booking wizard that replaces the legacy single-page booking portal.

### Booking Flow

1. **Step 1: Select Resource**
   - Browse available resources (halls, meeting rooms, equipment, vehicles)
   - Filter by type and category
   - View photos, amenities, and pricing
   - URL: `http://localhost/erp/booking-wizard` or `http://localhost/erp/booking-wizard/step1`

2. **Step 2: Date & Time**
   - Select booking date
   - Choose available time slot
   - URL: `http://localhost/erp/booking-wizard/step2/{resource_id}`

3. **Step 3: Add Extras**
   - Select add-ons (catering, equipment, services)
   - URL: `http://localhost/erp/booking-wizard/step3/{resource_id}`

4. **Step 4: Customer Information**
   - Enter customer details
   - Special requests
   - URL: `http://localhost/erp/booking-wizard/step4`

5. **Step 5: Review & Payment**
   - Review booking summary
   - Select payment plan (full, deposit, installment, pay later)
   - Apply promo codes
   - Choose payment method (cash, bank transfer, online payment gateways)
   - URL: `http://localhost/erp/booking-wizard/step5`

6. **Confirmation**
   - Booking confirmation page
   - Booking number
   - Payment schedule (if applicable)
   - URL: `http://localhost/erp/booking-wizard/confirmation/{booking_id}`

### Payment Gateway Integration

The system supports multiple payment gateways:
- Paystack
- Flutterwave
- Monnify
- Stripe
- PayPal

Payment gateways are configured in: **Settings → Payment Gateways**

### Customer Portal (Future Enhancement)

A customer portal with login/registration is planned for:
- View booking history
- Manage bookings
- Make payments
- Download invoices
- Modify/cancel bookings

### Legacy Portal (Deprecated)

The legacy single-page booking portal has been replaced by the enhanced multi-step wizard. All legacy routes now redirect to the wizard.

**Old URLs (redirect to wizard):**
- `http://localhost/erp/booking-portal` → Redirects to `booking-wizard`
- `http://localhost/erp/booking-portal/facility/{id}` → Redirects to `booking-wizard/step2/{id}`

### Technical Details

**Controller:** `Booking_wizard`
**Views:** `application/views/booking_wizard/`
**Public Access:** Yes (no authentication required)
**Payment Processing:** `Payment` controller handles gateway callbacks

### Features

✅ Multi-step booking process
✅ Real-time availability checking
✅ Dynamic pricing calculation
✅ Add-ons and extras
✅ Promo code support
✅ Multiple payment plans
✅ Payment gateway integration
✅ Online payment processing
✅ Booking confirmation emails
✅ Payment schedule tracking

### Support

For technical issues or questions, contact the system administrator.
