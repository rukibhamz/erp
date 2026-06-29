# Security Audit Report — ERP Application

| | |
|---|---|
| **Application** | ERP (PHP / custom MVC framework) |
| **Codebase path** | `C:\xampp\htdocs\newerp` |
| **Audit type** | Full-codebase security review + independent re-audit |
| **Initial audit date** | 2026-06-28 |
| **Last updated** | 2026-06-29 |
| **Branch / revision** | `main` @ `5af3586` |
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
| 2 | Backup restore — dangerous admin capability | **HIGH** | **Open** |
| 3 | Secrets committed in application config | **MEDIUM** | **Open** |

The primary CSRF class of vulnerability — state-changing actions reachable via
plain GET with no CSRF token — has been remediated across **81 controller
methods** and **67 view files** in three commits. All `delete($id)` endpoints
and the majority of other mutating actions now enforce `POST` + `check_csrf()`.

The backup-restore issue remains open. It is reframed below as a **privilege
boundary** problem rather than a file-upload validation bypass: the endpoint's
intended behavior is to execute SQL, so MIME/extension checks alone are
insufficient.

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
| **Status** | **Open** |

**Affected location:** `application/controllers/Backup.php` — `restore()`, lines ~176–254

**Description**

The `restore()` function accepts `$_FILES['backup_file']`, moves it to a temp
directory without content validation, and pipes the raw file contents directly
into the `mysql` CLI:

```php
$command = sprintf(
    'mysql --defaults-file=%s %s < %s 2>&1',
    escapeshellarg($mysqlConfigFile),
    escapeshellarg($dbConfig['database']),
    escapeshellarg($tempFile)
);
exec($command, $output, $returnVar);
```

`escapeshellarg` correctly prevents command injection via the filename, but the
file's contents are executed verbatim by MySQL. Any user with `settings/update`
permission can upload a crafted `.sql` file containing arbitrary SQL.

**Why upload validation alone is insufficient**

The endpoint's intended behavior is to execute SQL from the uploaded file. An
attacker can trivially prepend a valid `-- MySQL dump` header to malicious SQL
and pass extension/MIME checks. The existing `validateFileUpload()` helper also
does not support `.sql`/`.gz` MIME mappings and cannot be reused without
extension.

**Real security boundary**

1. **Authorization** — restrict restore to a tighter admin role (not all
   `settings/update` holders).
2. **Least-privilege DB account** — run restore under a dedicated MySQL user that
   lacks `GRANT`, `FILE`, and `CREATE USER` privileges.
3. **Operational controls** — require typed confirmation, log actor/timestamp,
   and alert on restore operations.

**Secondary hardening (optional, not primary fix)**

- Extension allowlist (`sql`, `gz`)
- `finfo` MIME check (`text/plain`, `application/gzip`)
- Recognizable mysqldump header (`-- MySQL dump` / `-- MariaDB dump`)

These accept the app's own backups but do not stop a determined attacker with
`settings/update` access.

---

### Vuln 3 — Secrets Committed in Application Config

| Field | Detail |
|---|---|
| **Severity** | MEDIUM |
| **Confidence** | HIGH |
| **Category** | `secrets_exposure` |
| **Status** | **Open** |

**Affected location:** `application/config/config.php`

The file includes `encryption_key` and database credentials in version control.
In the current dev snapshot the DB password is empty, but committed secret
material increases blast radius if the repository is exposed or credentials are
reused across environments.

**Remediation**

1. Move secrets to environment variables or a non-versioned config file.
2. Keep only a `.example` template in VCS.
3. Rotate `encryption_key` and DB credentials wherever they have been reused.

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

## 4. Deep-Dive Passes (Unchanged Assessment)

### 4.1 SQL Injection — No exploitable instances found

The data layer ([`application/core/Database.php`](application/core/Database.php))
uses PDO prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`, and
nearly every query routes through it with bound `?` parameters.

**Latent SQLi risks (hardening only):**

| # | Location | Issue | Why not exploitable now |
|---|----------|-------|-------------------------|
| L1 | [`Base_Model::count()`](application/core/Base_Model.php:138) | Interpolates `$where` raw | Every caller uses `->count()` with no argument |
| L2 | [`Database::update/delete($where)`](application/core/Database.php:134) | Accepts raw `$where` string | All callers pass literals |
| L3 | `SHOW COLUMNS ... LIKE '{$col}'` in AutoMigration / Booking_wizard | Column names interpolated | Names are hardcoded constants |

### 4.2 Cross-Site Scripting (XSS) — No high-confidence exploitable issue found

Three layers: input-time `sanitize_input()`, output-time `htmlspecialchars()` /
`esc()`, and nonce-based CSP without `unsafe-inline`.

**Latent XSS items (hardening only):**

| # | Location | Issue |
|---|----------|-------|
| L4 | Entities.php `create()` vs `edit()` | Inconsistent input sanitization |
| L5 | entities/view.php `website` in `href` | `javascript:` URI possible; CSP mitigates |
| L6 | bookings/calendar_timeslots.php | Raw flash message output |

---

## 5. Validated Controls

These defenses are correctly implemented and were not changed during remediation:

- Global CSRF enforcement hook in `Router.php` for POST/PUT/PATCH/DELETE
- `check_csrf()` with `hash_equals`, header/form token support
- PDO prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`
- Webhook signature verification in `Payment.php`
- `validateFileUpload()` with server-side MIME detection (for image/document uploads)
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

**Total:** 81 files changed across three commits.

---

## 7. Prioritized Remediation Plan (Current)

| Priority | Finding | Status | Action |
|----------|---------|--------|--------|
| **P0** | Vuln 1 — CSRF state-changing GET | **Done** | Verify in QA; consider router-level GET rejection |
| **P0** | Vuln 2 — Backup restore | **Open** | Tighten role, least-privilege DB user, audit logging |
| **P1** | Vuln 3 — Config secrets | **Open** | Externalize secrets; rotate keys |
| **P2** | XSS L4–L6 | Open | Low-priority hardening |
| **P2** | SQLi L1–L3 | Open | Deprecate `count($where)`; array-based WHERE API |

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

*This report reflects the state of the codebase as of commit `5af3586`. Findings
below the 8/10 confidence threshold were excluded. This is not a guarantee of
the absence of other vulnerabilities.*
