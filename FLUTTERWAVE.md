# Flutterwave Payment Integration

This ERP integrates Flutterwave using the same flow as Paystack: redirect checkout, server-side verification, and signed webhooks.

## Required configuration

### Environment variables (optional overrides)

When set, these override values stored in **Settings → Payment Gateways**:

| Variable | Description |
|----------|-------------|
| `FLUTTERWAVE_PUBLIC_KEY` | Flutterwave public key (`FLWPUBK_TEST-…` or live) |
| `FLUTTERWAVE_SECRET_KEY` | Secret key for API calls (`FLWSECK_TEST-…` or live) |
| `FLUTTERWAVE_ENCRYPTION_KEY` | Encryption key for inline/encrypted payloads (optional) |
| `FLUTTERWAVE_WEBHOOK_SECRET_HASH` | Secret hash from Flutterwave dashboard webhooks settings |
| `FLUTTERWAVE_ENABLE_SUBACCOUNTS` | Optional `1`/`true` to enable split payments (overrides DB toggle) |
| `FLUTTERWAVE_LOG_SPLIT` | Optional `1`/`true` to log split payload on `payment_transactions` |
| `PAYMENT_PROVIDER` | Optional default gateway: `flutterwave` |

Example (Apache/nginx / server env or `.env` loaded by your host):

```bash
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK_TEST-xxxx
FLUTTERWAVE_SECRET_KEY=FLWSECK_TEST-xxxx
FLUTTERWAVE_WEBHOOK_SECRET_HASH=your-secret-hash
PAYMENT_PROVIDER=flutterwave
```

### Admin UI

1. Go to **Settings → Payment Gateways → Flutterwave → Configure**.
2. Enter **Public Key** and **Private Key / Secret Key** (API secret).
3. Enter **Webhook Secret Hash** (from Flutterwave dashboard).
4. Set **Callback URL** to your site’s `payment/callback` (gateway is appended automatically).
5. Enable **Test Mode** for sandbox, disable for production.
6. Activate the gateway and optionally set as default.

## Webhook URL

Register one of these URLs in the [Flutterwave dashboard](https://dashboard.flutterwave.com) → **Settings → Webhooks**:

- Dedicated: `https://your-domain.com/webhooks/flutterwave`
- Generic: `https://your-domain.com/payment/webhook?gateway=flutterwave`

### Verification

Flutterwave sends a `verif-hash` header that must match your **Webhook Secret Hash**. The application compares hashes with constant-time `hash_equals` **before** parsing the JSON body.

Handled events:

- `charge.completed` — verifies and fulfills the order
- `transfer.completed` — acknowledged and verified when applicable
- `refund.completed` / `refund.failed` — logged on the payment transaction

## Sandbox vs live

| Mode | Keys | API base |
|------|------|----------|
| Sandbox | `FLWPUBK_TEST-…`, `FLWSECK_TEST-…` | `https://api.flutterwave.com/v3` |
| Live | `FLWPUBK-…`, `FLWSECK-…` | Same |

Toggle **Test Mode** in gateway settings or use test keys only on staging.

## Payment flow

Uses [Flutterwave Standard](https://developer.flutterwave.com/v3.0.0/docs/flutterwave-standard-1) — same redirect pattern as Paystack:

1. **Initialize** — Server `POST https://api.flutterwave.com/v3/payments` with secret key → receives `data.link` (e.g. `https://checkout.flutterwave.com/v3/hosted/pay/flwlnk-…`).
2. **Redirect** — Browser is sent to that hosted payment page (not the Flutterwave merchant dashboard).
3. **Callback** — After payment, Flutterwave redirects to `payment/callback?gateway=flutterwave&tx_ref=…&transaction_id=…&status=…`.
4. **Verify** — Server calls `GET /v3/transactions/{transaction_id}/verify` (or verify-by-reference using `tx_ref`).
5. **Fulfill** — Amount and currency must match the DB record before booking/invoice fulfillment runs.
6. **Webhook** — Duplicate `charge.completed` events are ignored when the transaction is already `success`.

## Split payments (subaccounts, optional)

Flutterwave controls **how much** is split; the ERP only sends your **subaccount code** at checkout.

1. **Subaccounts** → **Activate code** → paste `RS_…` from Flutterwave.
2. **Gateway settings** → enable **split payments (subaccounts)**.
3. Booking payments then include `subaccounts: [{ "id": "RS_…" }]` — no split % in the ERP.

Optional: **Advanced → per property/space** split rules if different bookings need different codes.  
Optional: **log split details** on payment transactions (audit only).

Customer still pays the **full booking amount** in ERP; fulfillment is unchanged.

AutoMigration creates `flutterwave_subaccounts`, `flutterwave_split_rules`, and optional `payment_transactions.split_applied` / `split_payload` columns on existing installs.

## Replay failed webhooks

1. Open **Flutterwave Dashboard → Webhooks → Logs**.
2. Find the failed delivery for `charge.completed`.
3. Fix the cause (wrong secret hash, 401 signature, 400 processing error in server logs).
4. Use **Resend** / **Retry** in the dashboard, or send a manual `POST` with the original payload and valid `verif-hash` header.

Check application logs (`error_log`) — full card data and secrets are never logged.

## Running tests

```bash
php tests/payment/FlutterwaveWebhookTest.php
php tests/payment/FlutterwaveVerifyTest.php
FLUTTERWAVE_SECRET_KEY=FLWSECK_TEST-xxx php tests/payment/FlutterwaveIntegrationTest.php
php tests/payment/FlutterwaveSplitTest.php
```

No extra Composer dependencies are required for unit tests.
