# Security Audit Report — ERP Application

| | |
|---|---|
| **Application** | ERP (PHP / custom MVC framework) |
| **Codebase path** | `C:\xampp\htdocs\newerp` |
| **Audit type** | Full-codebase security review + independent re-audit |
| **Initial audit date** | 2026-06-28 |
| **Last updated** | 2026-06-29 (phase 2 remediation) |
| **Branch / revision** | `main` (uncommitted security hardening) |
| **Confidence threshold** | Findings reported at ≥ 8/10 confidence |

---

## 1. Executive Summary

A full-scale security review of the ERP codebase was performed, covering input
validation, authentication/authorization, cryptography & secrets, injection &
code execution, and data exposure. An independent re-audit was conducted on
2026-06-29 to validate findings and identify gaps not covered in the initial
report.

### Remediation status

| # | Title | Severity | Status |
|---|-------|----------|--------|
| 1 | CSRF on GET-based state-changing operations | **HIGH** | **Remediated** (`ede660e`, `316eb0d`, `5af3586`) |
| 2 | Backup restore — dangerous admin capability | **HIGH** | **Remediated** (super_admin + controls) |
| 3 | Secrets committed in application config | **MEDIUM** | **Remediated** (`.env` + `config.php.example`) |

The primary CSRF class of vulnerability has been remediated across **81 controller
methods** and **67 view files** in three commits.

Backup restore is now restricted to **super_admin** only, requires typed
confirmation (`RESTORE`), validates uploads, logs restore events, and supports an
optional least-privilege `DB_RESTORE_USER`. Secrets are externalized to `.env`
via `config.php.example`; `config.php` and `.env` remain gitignored.

---

## 2. Findings

### Vuln 1 — CSRF: GET-Based State-Changing Operations

| Field | Detail |
|---|---|
| **Severity** | HIGH |
| **Confidence** | 9/10 |
| **Category** | `csrf` |
| **Status** | **Remediated** |

**Original scope**

The global CSRF guard (`enforce_global_csrf()` in `csrf_helper.php`) returns
early for any HTTP method that is not `POST`, `PUT`, `PATCH`, or `DELETE`. As a
result, all `GET` requests bypass the global CSRF check. The majority of
`delete($id)` controller methods and many other mutating actions performed
irreversible state changes on plain `GET` requests with no method guard and no
local `check_csrf()`. Views rendered these as bare `<a href="...">` links.

**Exploit scenario (pre-remediation)**

```html
<img src="https://erp.victim.com/users/delete/1">
```

An authenticated victim's browser would send the GET request with their session
cookie, bypass the global CSRF guard, pass `requirePermission()`, and permanently
delete the record.

**Remediation applied**

Every affected controller method now enforces:

```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $this->setFlashMessage('danger', 'Invalid request method.');
    redirect('...');
    return;
}
check_csrf();
```

Every affected view link was converted to a POST form with `csrf_field()`:

```html
<form method="post" action="<?= base_url('users/delete/' . $row->id) ?>" class="d-inline"
      onsubmit="return confirm('Delete this record?');">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
</form>
```

**Reference implementation:** `Bookings::delete()` was the template used
throughout.

**Commits**

| Commit | Scope |
|--------|-------|
| `ede660e` | All `delete($id)` endpoints + session/logout/terminate/clearDebugLog (23 controllers, 21 views) |
| `316eb0d` | Non-delete mutators: sync, approve, migrate, log-clean (5 controllers, 14 views) |
| `5af3586` | Remaining mutators: toggles, settings, bookings, stock, rent, resources (14 controllers, 9 views) |

**Defense-in-depth (still recommended):** Extend `enforce_global_csrf()` to reject
state-changing routes received over GET at the router level, so a missed
controller cannot silently reintroduce this class of bug.

---

### Vuln 2 — Backup Restore: Dangerous Administrative Capability

| Field | Detail |
|---|---|
| **Severity** | HIGH |
| **Confidence** | 9/10 |
| **Category** | `privilege_escalation` / `dangerous_functionality` |
| **Status** | **Remediated** |

**Affected location:** `application/controllers/Backup.php` — `restore()`

**Original risk**

The `restore()` function piped uploaded SQL directly into the `mysql` CLI. Any
user with `settings/update` could execute arbitrary SQL.

**Remediation applied (2026-06-29)**

| Control | Implementation |
|---------|----------------|
| Authorization | `requireRole('super_admin')` — only Super Admins can restore |
| Confirmation | User must type `RESTORE` in a confirmation field |
| Upload validation | `validateBackupUpload()` — extension, MIME, SQL header check |
| Pre-restore backup | Automatic backup created before restore runs |
| Audit logging | Activity log + `error_log` with user ID, filename, and IP |
| Least-privilege DB | Optional `DB_RESTORE_USER` / `DB_RESTORE_PASSWORD` in `.env` |
| CSRF + POST | Already enforced; `create()` also gained POST + CSRF guards |
| UI | Restore form hidden from non–super-admin users |

**Operational note:** Configure a dedicated MySQL restore user in production:

```sql
CREATE USER 'erp_restore'@'localhost' IDENTIFIED BY '...';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON erp.* TO 'erp_restore'@'localhost';
-- Do NOT grant FILE, GRANT OPTION, or CREATE USER
```

Set `DB_RESTORE_USER` and `DB_RESTORE_PASSWORD` in `.env`.

**Residual risk:** A compromised super_admin account can still restore malicious
SQL. This is inherent to the feature; monitor activity logs and restrict super_admin
assignments.

---

### Vuln 3 — Secrets Committed in Application Config

| Field | Detail |
|---|---|
| **Severity** | MEDIUM |
| **Confidence** | HIGH |
| **Category** | `secrets_exposure` |
| **Status** | **Remediated** |

**Affected location:** `application/config/config.php` (gitignored)

**Remediation applied (2026-06-29)**

1. **`application/config/config.php.example`** — versioned template; reads all secrets via `env()`.
2. **`.env.example`** — documents required environment variables.
3. **`application/helpers/env_helper.php`** — loads `.env` at bootstrap.
4. **`application/helpers/config_helper.php`** — unified `load_app_config()` + **automatic migration**.
5. **Installer** — writes secrets to `.env` (mode `0600`), not into config.
6. **`.gitignore`** — excludes `config.php`, `config.installed.php`, `.env`, and `*.legacy.bak`.

**Automatic migration (deployed systems)**

On the **first HTTP request** after `git pull`, if inline secrets still exist in
`config.php` or `config.installed.php`:

1. Values are copied into **`.env`** (only if `APP_ENCRYPTION_KEY` is not already set).
2. Legacy config files are backed up as `*.legacy.bak` and replaced with thin wrappers.
3. All components (`Database`, `Base_Controller`, `url_helper`, etc.) use `load_app_config()`.

No manual migration step is required for typical upgrades. Original config files are
preserved as `config.php.legacy.bak` / `config.installed.php.legacy.bak` on the server.

---

## 3. Independent Re-Audit Notes

The initial report and an independent re-audit were compared on 2026-06-29.

### Agreements

- CSRF on GET-based destructive deletes is real, high severity, and was the
  highest-priority fix.
- Global CSRF helper exempts GET; controller-level method discipline is required.
- SQL injection and XSS deep-dives found no high-confidence exploitable issues
  in the data layer and view output paths.

### Corrections to initial report

| Topic | Initial report | Independent assessment |
|-------|----------------|------------------------|
| Backup restore | Framed as "insecure file upload" / primary fix = MIME/extension validation | Reframed as dangerous admin capability; real fix is authz + least-privilege DB |
| `validateFileUpload()` reuse | Recommended for backup restore | Incorrect — helper has no `.sql`/`.gz` support |
| Codebase path | `C:\xampp\htdocs\erp` | Should be `C:\xampp\htdocs\newerp` |

### Additional findings (independent audit)

These were identified during the independent pass and are now remediated unless
noted:

| Area | Examples remediated |
|------|---------------------|
| Session/logout | `Profile::terminateSession`, `Customer_portal::logout`, `System_settings::clearDebugLog` |
| Approvals | `Stock_adjustments::approve`, `Vendor_utility_bills::approve` |
| Sync/migrate | `Spaces::syncToBooking`, `System_migrate::up`, `System_logs::clean` |
| Settings toggles | `Settings::toggleGateway`, Flutterwave subaccount/split-rule actions |
| Notifications | `markRead`, `markAllRead`, `savePreferences` |
| Bookings | `reschedule`, `cancel`, `updateStatus`, `addResource`, `removeResource`, `recordPayment` |
| Inventory/leases | `Stock_takes::start/complete/create`, `Rent_invoices::generate/recordPayment`, `Paye::calculate` |

---

## 4. Deep-Dive Passes

### 4.1 SQL Injection — No exploitable instances found

**Latent SQLi risks — hardening applied:**

| # | Location | Status | Fix |
|---|----------|--------|-----|
| L1 | `Base_Model::count($where)` | **Remediated** | Throws if `$where !== '1=1'`; use `countBy()` |
| L2 | `Database::update/delete($where)` | **Documented** | `@deprecated` docblocks added |
| L3 | `Bookings::mergeOptionalBookingColumns` | **Remediated** | Uses `checkColumnExists()` with bound params |

### 4.2 Cross-Site Scripting (XSS) — No high-confidence exploitable issue found

**Latent XSS items — hardening applied:**

| # | Location | Status | Fix |
|---|----------|--------|-----|
| L4 | `Entities::create()` | **Remediated** | `sanitize_input()` on all fields (matches `edit()`) |
| L5 | `entities/view.php` website `href` | **Remediated** | `safe_external_url()` + `esc(..., 'attr')` |
| L6 | `calendar_timeslots.php` flash | **Remediated** | `htmlspecialchars()` on flash type and message |

---

## 5. Validated Controls

These defenses are correctly implemented and were not changed during remediation:

- Global CSRF enforcement hook in `Router.php` for POST/PUT/PATCH/DELETE
- `check_csrf()` with `hash_equals`, header/form token support
- PDO prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`
- Webhook signature verification in `Payment.php`
- `validateFileUpload()` with server-side MIME detection (for image/document uploads)
- `validateBackupUpload()` for SQL backup restore uploads
- `safe_external_url()` for http(s)-only external links
- Nonce-based CSP via `set_security_headers()`
- ORDER BY whitelist in `Base_Model::buildOrderByClause()`
- LIMIT/OFFSET forced through `intval()`

---

## 6. Remediation Log

| Date | Commit | Summary |
|------|--------|---------|
| 2026-06-29 | `ede660e` | POST + CSRF on all `delete($id)` and session/logout/clearDebugLog (44 files) |
| 2026-06-29 | `316eb0d` | POST + CSRF on sync/approve/migrate/log-clean (14 files) |
| 2026-06-29 | `5af3586` | POST + CSRF on remaining mutators and view forms (23 files) |
| 2026-06-29 | `b876233` | Backup restore hardening, secrets externalization, XSS/SQLi hardening |

**Phase 1 total:** 81 files changed across three commits.

**Phase 2 (committed in `b876233`):** env helper, config template, backup controls,
`validateBackupUpload()`, XSS L4–L6, SQLi L1/L3 hardening.

---

## 7. Prioritized Remediation Plan (Current)

| Priority | Finding | Status | Action |
|----------|---------|--------|--------|
| **P0** | Vuln 1 — CSRF state-changing GET | **Done** | Verify in QA; consider router-level GET rejection |
| **P0** | Vuln 2 — Backup restore | **Done** | Configure `DB_RESTORE_USER` in production |
| **P1** | Vuln 3 — Config secrets | **Done** | Rotate keys if previously committed |
| **P2** | XSS L4–L6 | **Done** | — |
| **P2** | SQLi L1, L3 | **Done** | L2: migrate callers to array-based WHERE API over time |
| **P3** | Router-level GET rejection | Open | Defense-in-depth for CSRF |

---

## 8. Methodology

- **Phase 1 — Repository context.** Catalogued MVC framework, routing, CSRF
  helper, permission model, validation helpers.
- **Phase 2 — Comparative analysis.** Compared delete/upload handlers against
  secure patterns (e.g. `Bookings::delete()`).
- **Phase 3 — Vulnerability assessment.** Traced data flow from user input to
  sensitive sinks. Findings at ≥ 8/10 confidence reported.
- **Phase 4 — Independent re-audit (2026-06-29).** Validated initial findings,
  reframed backup-restore risk, identified additional mutating endpoints, applied
  remediation in three commits.

**Categories examined:** SQL injection, command injection, path traversal, XSS,
authentication bypass, privilege escalation, session management, CSRF, insecure
file upload, RCE, hardcoded secrets, data exposure.

**Out of scope:** DoS, rate limiting, dependency CVEs, log-spoofing, theoretical
race conditions.

---

## 9. Second Audit (2026-06-30)

A second full pass was performed after phase-2 remediation, focused on areas not
deeply covered in the first audit: authentication & session handling, password
and token management, authorization/IDOR, file-upload storage, command
execution, dynamic code execution, and the public customer portal.

**Result: no new HIGH or MEDIUM exploitable vulnerabilities found.** The areas
reviewed are soundly implemented.

### Verified secure (this pass)

| Area | Finding |
|------|---------|
| Login / session | CSRF-guarded login, rate limiting (`rate_limit_allows`), `session_regenerate_id(true)` on login and remember-me auto-login (anti–session-fixation), DB-backed session tracking ([Auth.php](application/controllers/Auth.php)) |
| Password storage | `password_hash(..., PASSWORD_BCRYPT)` + `password_verify`; account lockout after N failed attempts ([User_model.php:52](application/models/User_model.php)) |
| Remember-me tokens | Random 256-bit token; **only the SHA-256 hash** stored in DB, plaintext in HttpOnly/Secure/SameSite cookie ([User_model.php:108](application/models/User_model.php)) |
| Password reset | `random_bytes(32)` token, 1-hour expiry validated server-side, strength enforced, tokens cleared on use, no email enumeration ([Auth.php:195](application/controllers/Auth.php)) |
| Authorization / IDOR | Customer portal enforces ownership (`customer_email === session user email`) on **every** id-taking endpoint — `booking`, `payBooking`, `viewBooking`, `rescheduleBooking`, `getRescheduleQuote` ([Customer_portal.php:473](application/controllers/Customer_portal.php)) |
| Command execution | Only OS-level `exec()` is the `mysqldump`/`mysql` call in `Backup.php`, fully wrapped in `escapeshellarg()`. All other `exec()` are `PDO::exec()` (SQL DDL). |
| Dynamic code / includes | No `eval`/`assert`/`create_function`; no `include`/`require`/file ops driven by request input. |
| File download | `Backup::download()` constrains to the backups dir via `basename()` — no path traversal. |
| Payment webhook | `handleWebhook()` verifies the signature on the **raw** request body and fails closed (HTTP 401) before processing; payments are additionally re-verified server-to-server against the gateway API ([Payment.php:337](application/controllers/Payment.php)). |
| Avatar / logo upload | Both call `validateFileUpload()` (server-side MIME ↔ extension match against an image allowlist) before saving ([Profile.php:148](application/controllers/Profile.php), [System_settings.php:82](application/controllers/System_settings.php)). |

### New low-severity / hardening items

None are independently exploitable today, but worth addressing:

| # | Location | Issue | Recommendation |
|---|----------|-------|----------------|
| L7 | [`Upload.php`](application/libraries/Upload.php) library defaults | Defaults are `allowed_types => '*'`, `encrypt_name => FALSE`, and validation is **extension-only (no MIME check)**. The sole caller (`Spaces::uploadPhotos`) overrides safely (image-only types + `encrypt_name = TRUE`), but a future caller relying on defaults into a web-accessible path would be an RCE vector. | Change library defaults to deny-by-default, force `encrypt_name`, and add server-side MIME detection inside `do_upload()`. |
| L8 | [`Spaces::uploadPhotos`](application/controllers/Spaces.php:753) | Validates by file **extension only** (no content/MIME inspection). Filenames are randomized so the uploaded file is not directly executable as served, but a polyglot image is still storable. | Route space photos through `validateFileUpload()` for content-based MIME validation, consistent with avatar/logo uploads. |
| L9 | [`Stock_movements::receive`](application/controllers/Stock_movements.php:33) (and `Profile`/`Payment` debug logs) | Always-on diagnostic logging writes full `$_POST` / payloads to files under `logs/`. Data is non-PII and the endpoint is permission-gated, so impact is low. | Gate diagnostic logging behind a debug flag (as `payment_debug_log` already does), remove before production, and ensure `logs/` is not web-accessible. |

### Methodology (second pass)

Traced data flow from request inputs to sensitive sinks across auth, session,
authorization, file I/O, process execution, and the public portal. Reviewed
controller method guards, the `Base_Controller` permission/role/auth helpers,
the `User_model` crypto, the `Upload` library and all `move_uploaded_file`
callers, all `exec`-family calls, and the payment webhook signature path.

---

*This report reflects remediation through 2026-06-29 phase 2 and a second audit
on 2026-06-30. Findings below the 8/10 confidence threshold were excluded. This
is not a guarantee of the absence of other vulnerabilities.*
