# Security Findings — Second Audit Pass

| | |
|---|---|
| **Application** | ERP (PHP / custom MVC framework) |
| **Audit date** | 2026-06-30 |
| **Scope** | Authentication & session, password/token management, authorization & IDOR, file-upload storage, command execution, dynamic code execution, public customer portal |
| **Context** | Follow-up pass after phase-2 remediation (commit `b876233`). Full cumulative history is in [SECURITY_AUDIT.md](SECURITY_AUDIT.md). |
| **Confidence threshold** | Exploitable findings reported at ≥ 8/10 |

---

## 1. Summary

**No new HIGH or MEDIUM exploitable vulnerabilities were found.** The subsystems
reviewed in this pass are soundly implemented. Three **LOW-severity / hardening**
items were identified (L7–L9); none are independently exploitable today, but
L7 is a latent remote-code-execution foot-gun worth fixing proactively.

| # | Title | Severity | Category | Exploitable now? |
|---|-------|----------|----------|------------------|
| L7 | `Upload` library has unsafe defaults | LOW | `insecure_file_upload` | No (only caller overrides defaults) |
| L8 | Spaces photo upload validates by extension only | LOW | `insecure_file_upload` | No (randomized names; not served as executable) |
| L9 | Always-on diagnostic logging of request bodies | LOW | `data_exposure` | No (non-PII, permission-gated) |

---

## 2. Low-Severity / Hardening Findings

### L7 — `Upload` library: deny-nothing defaults

| Field | Detail |
|---|---|
| **Severity** | LOW (latent RCE if misused) |
| **Category** | `insecure_file_upload` |
| **Location** | [`application/libraries/Upload.php`](application/libraries/Upload.php) (defaults at lines 20–26; validation at 56–64) |

**Description**

The library's default configuration is permissive and its only validation is an
extension check (no server-side MIME inspection):

```php
$defaults = [
    'upload_path'   => './uploads/',
    'allowed_types' => '*',     // accepts ANY extension
    'max_size'      => 0,       // no size limit
    'encrypt_name'  => FALSE,   // keeps attacker-supplied filename
    'overwrite'     => FALSE
];
```

If any future caller invokes `do_upload()` with defaults (or an `allowed_types`
that includes executable extensions) and an `upload_path` inside the web root, an
attacker could upload `shell.php` and execute it — full RCE.

**Why not exploitable today**

The sole caller, [`Spaces::uploadPhotos`](application/controllers/Spaces.php:751),
overrides the defaults safely:

```php
$config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
$config['encrypt_name']  = TRUE;
```

So no current code path reaches the dangerous defaults.

**Recommendation**

Harden the library itself so misuse is not possible:

1. Default `allowed_types` to a safe allowlist (or fail if unset) — never `'*'`.
2. Force `encrypt_name = TRUE` (or always generate a random server-side name).
3. Add server-side MIME detection inside `do_upload()` (reuse the `finfo` logic
   from `validateFileUpload()`), and verify the extension matches the detected
   MIME — do not trust the client-supplied extension alone.
4. Reject double extensions (`.php.jpg`) and known executable extensions
   regardless of allowlist.

---

### L8 — Spaces photo upload: extension-only validation

| Field | Detail |
|---|---|
| **Severity** | LOW |
| **Category** | `insecure_file_upload` |
| **Location** | [`application/controllers/Spaces.php:751`](application/controllers/Spaces.php) (`uploadPhotos`) |

**Description**

Space photos are validated by file **extension only** (`gif|jpg|png|jpeg|webp`)
with no inspection of actual file content. A file such as `evil.gif` containing
PHP/script bytes (a polyglot) would pass the extension check and be stored.

**Why not exploitable today**

`encrypt_name = TRUE` means the stored file gets a random `md5().gif` name, and
it is served with an image extension — so it is not directly executable as PHP.
Impact is limited to storing a crafted-but-inert image.

**Recommendation**

Route space photo uploads through `validateFileUpload()` (server-side MIME ↔
extension verification), matching the avatar and company-logo upload paths. This
makes content-based validation consistent across all upload features.

---

### L9 — Always-on diagnostic logging of request data

| Field | Detail |
|---|---|
| **Severity** | LOW |
| **Category** | `data_exposure` |
| **Location** | [`Stock_movements::receive`](application/controllers/Stock_movements.php:33-39); similar debug writes in `Profile` / `Payment` |

**Description**

`Stock_movements::receive()` unconditionally writes the full `$_POST` payload to
`logs/receive_stock_debug.log` on every POST:

```php
$logFile = ROOTPATH . 'logs/receive_stock_debug.log';
@file_put_contents($logFile, "[$ts] POST data: " . json_encode($_POST) . "\n", FILE_APPEND);
```

**Why impact is low**

The data logged is inventory fields (item, quantity, cost) — non-PII and not
secrets — and the endpoint is gated by `requirePermission('inventory','create')`.
This is excluded from higher severity per standard log-exposure precedents
(non-PII logging is not a vulnerability), but it is unnecessary attack surface
and risks future leakage of more sensitive payloads.

**Recommendation**

1. Gate diagnostic logging behind an explicit debug flag, as
   [`payment_debug_log()`](application/helpers/security_helper.php:363) already
   does (env-controlled), rather than always-on.
2. Remove leftover diagnostic logging before production.
3. Ensure the `logs/` directory is **outside the web root** or blocked by web
   server config, so log files cannot be downloaded directly.

---

## 3. Verified Secure (No Action Required)

These subsystems were examined this pass and found correctly implemented:

| Area | Evidence |
|------|----------|
| Login / session fixation | CSRF-guarded login + `session_regenerate_id(true)` on login and remember-me auto-login ([Auth.php:87,333](application/controllers/Auth.php)) |
| Brute force | Rate limiting (`rate_limit_allows`, 5/15min) + DB account lockout ([Auth.php:69](application/controllers/Auth.php), [User_model.php:75](application/models/User_model.php)) |
| Password storage | `password_hash(PASSWORD_BCRYPT)` + `password_verify` ([User_model.php:52,205](application/models/User_model.php)) |
| Remember-me tokens | Random 256-bit token; **SHA-256 hash only** in DB; HttpOnly/Secure/SameSite cookie ([User_model.php:108](application/models/User_model.php)) |
| Password reset | `random_bytes(32)`, 1-hour expiry, single-use, strength-validated, no enumeration ([Auth.php:195](application/controllers/Auth.php)) |
| Authorization / IDOR | Customer-email ownership check on every id-taking portal endpoint ([Customer_portal.php:473](application/controllers/Customer_portal.php)) |
| Command execution | Only `escapeshellarg`-wrapped `mysqldump`/`mysql`; all other `exec` are `PDO::exec()` SQL |
| Dynamic code / includes | No `eval`/`assert`/`create_function`; no request-driven `include`/`require`/file ops |
| File download | `basename()` constrains `Backup::download()` to the backups directory |
| Payment webhook | Raw-body signature verification, fail-closed (401), + server-to-server re-verification ([Payment.php:337](application/controllers/Payment.php)) |
| Avatar / logo upload | `validateFileUpload()` (server-side MIME ↔ extension) before save ([Profile.php:148](application/controllers/Profile.php)) |

---

## 4. Methodology

Traced data flow from request inputs (`$_GET`/`$_POST`/`$_FILES`/`$_COOKIE`/
`$_SERVER`) to sensitive sinks across authentication, session handling,
authorization, file I/O, process execution, and the public customer portal.
Reviewed: every controller method guard pattern, the `Base_Controller`
permission/role/auth helpers, `User_model` cryptographic routines, the `Upload`
library and all `move_uploaded_file` callers, all `exec`-family calls, dynamic
include/eval usage, and the payment webhook signature path.

**Exclusions (per policy):** DoS / resource exhaustion, rate limiting, secrets at
rest, dependency CVEs, log-spoofing, non-PII logging, theoretical race conditions.

---

*Findings below the 8/10 confidence threshold were excluded to minimize false
positives. This document records the 2026-06-30 audit pass and is not a guarantee
of the absence of other vulnerabilities.*
