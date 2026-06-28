# Security Audit Report — ERP Application

| | |
|---|---|
| **Application** | ERP (PHP / custom MVC framework) |
| **Codebase path** | `C:\xampp\htdocs\erp` |
| **Audit type** | Full-codebase security review |
| **Audit date** | 2026-06-28 |
| **Branch / revision** | `main` @ `2e6bf57` |
| **Confidence threshold** | Findings reported at ≥ 8/10 confidence |

---

## 1. Executive Summary

A full-scale security review of the ERP codebase was performed, covering input
validation, authentication/authorization, cryptography & secrets, injection &
code execution, and data exposure.

Two **HIGH-severity** vulnerabilities were confirmed with concrete, exploitable
attack paths:

1. **Cross-Site Request Forgery (CSRF) on GET-based destructive delete routes** —
   roughly 20 controllers permanently delete records on a plain `GET` request
   with no CSRF protection.
2. **Arbitrary SQL execution via the unvalidated backup-restore upload** — an
   uploaded file is piped verbatim into the `mysql` CLI with no validation.

Both issues are actionable and should be remediated before the next release.

### Findings at a glance

| # | Title | Severity | Category | Confidence |
|---|-------|----------|----------|------------|
| 1 | CSRF on GET-based destructive delete operations | **HIGH** | `csrf` | 9/10 |
| 2 | Arbitrary SQL execution via backup-restore upload | **HIGH** | `insecure_file_upload` / `sql_injection` | 9/10 |

---

## 2. Findings & Remediations

### Vuln 1 — CSRF: GET-Based Destructive Delete Operations

| Field | Detail |
|---|---|
| **Severity** | HIGH |
| **Confidence** | 9/10 |
| **Category** | `csrf` |
| **Status** | Open |

**Affected locations (non-exhaustive):**

- `application/controllers/Users.php:516`
- `application/controllers/Taxes.php:131`
- `application/controllers/Tenants.php:214`
- `application/controllers/Currencies.php:176`
- `application/controllers/Entities.php:166`
- `application/controllers/Locations.php:214`
- `application/controllers/Spaces.php:599`
- `application/controllers/Products.php:210`
- `application/controllers/Suppliers.php:224`
- `application/controllers/Companies.php:101`
- `application/controllers/Tax_config.php:495`
- `application/controllers/Leases.php:336`
- `application/controllers/Fixed_assets.php:220`
- `application/controllers/Meters.php:252`
- `application/controllers/Facilities.php:55`
- `application/controllers/Customer_types.php:66`
- `application/controllers/Discount_tiers.php:57`
- `application/controllers/Resource_management.php:147`
- `application/controllers/Tariffs.php:194`
- `application/controllers/Utility_providers.php:165`
- Guard logic: `application/core/Router.php`, `application/helpers/csrf_helper.php:174`

**Description**

The global CSRF guard (`enforce_global_csrf()` in `csrf_helper.php`) returns
`true` (no-op) for any HTTP method that is not `POST`, `PUT`, `PATCH`, or
`DELETE`. As a result, **all `GET` requests are completely unprotected.** The
majority of `delete($id)` controller methods perform an irreversible database
deletion on a plain `GET` request with no HTTP method check and no CSRF token
verification. Views render these actions as bare `<a href=".../delete/42">`
links, and record IDs are sequential integers (easily guessable).

**Exploit Scenario**

An attacker embeds the following in any page an authenticated ERP user visits
(forum post, email, malicious ad, attacker-controlled site):

```html
<img src="https://erp.victim.com/users/delete/1">
```

The victim's browser sends the `GET` request with their session cookie. The
global CSRF guard is bypassed (GET is exempt), `requirePermission()` passes
because the victim is authenticated, and the record is permanently deleted. The
same applies to tenants, currencies, entities, taxes, products, leases, and all
other resources with unguarded GET delete routes — enabling silent, mass
destruction of business data.

**Remediation**

1. Require `POST` and validate a CSRF token in **every** `delete()` method:

   ```php
   public function delete($id)
   {
       requirePermission('users', 'delete');

       if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
           redirect(base_url('users'));
           return;
       }
       check_csrf();

       $this->userModel->delete($id);
       // ...
   }
   ```

2. Replace every `<a href=".../delete/...">` link in the views with a POST form
   that includes the CSRF token:

   ```html
   <form method="post" action="<?= base_url('users/delete/' . $row->id) ?>"
         onsubmit="return confirm('Delete this record?');">
       <?= csrf_field() ?>
       <button type="submit" class="btn btn-danger btn-sm">Delete</button>
   </form>
   ```

3. **Reference implementation:** `Bookings::delete()` already follows the correct
   pattern — use it as the template.

4. **Defense-in-depth:** Consider extending `enforce_global_csrf()` to also reject
   state-changing routes received over GET, so a missed controller cannot silently
   reintroduce this class of bug.

---

### Vuln 2 — Arbitrary SQL Execution via Unvalidated Backup Restore Upload

| Field | Detail |
|---|---|
| **Severity** | HIGH |
| **Confidence** | 9/10 |
| **Category** | `insecure_file_upload` / `sql_injection` |
| **Status** | Open |

**Affected location:** `application/controllers/Backup.php` — `restore()`, lines ~191–230

**Description**

The `restore()` function accepts `$_FILES['backup_file']`, moves it to a temp
directory **without any validation** (no extension check, no MIME check, no
content/magic-byte inspection), and then pipes the raw file contents directly
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

`escapeshellarg` correctly prevents command injection via the filename, **but the
file's contents are executed verbatim by MySQL.** Any user with `settings/update`
permission can upload a crafted `.sql` file containing arbitrary SQL. Note that a
`validateFileUpload()` helper already exists in the codebase but is **not called
here.**

**Exploit Scenario**

1. Attacker holds (or compromises) an account with `settings/update` permission.
2. Attacker loads the backup UI page to obtain a valid CSRF token.
3. Attacker POSTs a crafted file to `/backup/restore` containing, e.g.:

   ```sql
   CREATE USER 'backdoor'@'%' IDENTIFIED BY 's3cr3t';
   GRANT ALL PRIVILEGES ON *.* TO 'backdoor'@'%';
   SELECT '<?php system($_GET["c"]); ?>'
       INTO OUTFILE '/var/www/html/shell.php';
   DROP TABLE users;
   ```

4. MySQL executes every statement: the attacker gains a persistent DB backdoor
   account, potentially a web shell (if the DB user has `FILE` privilege), and
   destroys critical tables.

**Remediation**

1. **Validate before processing** — extension, MIME (via `finfo`, not
   `$_FILES['type']`), and dump-header signature:

   ```php
   $name = $_FILES['backup_file']['name'];
   $tmp  = $_FILES['backup_file']['tmp_name'];

   // 1) Allowed extension
   $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
   if (!in_array($ext, ['sql', 'gz'], true)) {
       // reject
   }

   // 2) MIME type from actual content
   $finfo = new finfo(FILEINFO_MIME_TYPE);
   $mime  = $finfo->file($tmp);
   if (!in_array($mime, ['text/plain', 'application/gzip', 'application/x-gzip'], true)) {
       // reject
   }

   // 3) Recognizable mysqldump header (for uncompressed dumps)
   $handle = fopen($tmp, 'r');
   $firstLine = fgets($handle);
   fclose($handle);
   if (strpos($firstLine, '-- MySQL dump') === false &&
       strpos($firstLine, '-- MariaDB dump') === false) {
       // reject
   }
   ```

2. **Reuse the existing `validateFileUpload()` helper** so all upload handlers
   share one validation path.

3. **Least privilege:** Run the restore under a dedicated MySQL account that
   **lacks** `GRANT`, `FILE`, and `CREATE USER` privileges. This contains the
   blast radius even if a malicious dump slips through.

4. **Confirm restores explicitly:** Require a typed confirmation and log the
   actor/timestamp of every restore operation.

---

## 2b. Dedicated Deep-Dive Passes

Two attack classes were given a focused, full-codebase pass beyond the initial
sweep. Both came back **clean of high-confidence exploitable issues**; the
residual items below are defense-in-depth / latent hardening only and did not
meet the HIGH/MEDIUM reporting bar.

### 2b.1 SQL Injection — No exploitable instances found

The data layer ([`application/core/Database.php`](application/core/Database.php))
uses PDO prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`, and
nearly every query routes through it with bound `?` parameters.

Verified defenses:

- **All `query()`/`fetchAll()`/`fetchOne()` calls** pass user values as bound
  params (`WHERE id = ?`, `[$id]`). High-traffic controllers (`Bookings`,
  `Booking_wizard`) and the models do not interpolate request data into SQL.
- **`update()`/`delete()`/`count()` callers** use static WHERE strings with
  separate param arrays. No caller concatenates `$_GET`/`$_POST` into WHERE.
- **ORDER BY** is whitelist-validated in
  [`Base_Model::buildOrderByClause()`](application/core/Base_Model.php:59).
- **LIMIT/OFFSET** are forced through `intval()`.
- **Search / LIKE** ([`sql_append_search`](application/helpers/list_search_helper.php:99))
  binds the search term as a parameter; the column list comes from a hardcoded
  whitelist in `standard_list_search_fields()`, not user input.

**Latent SQLi risks (not attacker-reachable today — hardening only):**

| # | Location | Issue | Why not exploitable now |
|---|----------|-------|-------------------------|
| L1 | [`Base_Model::count()`](application/core/Base_Model.php:138) | Interpolates `$where` raw; already marked `@deprecated ... vulnerable to SQL injection` in-code | Every current caller invokes it with **no argument** (`->count()`). Recommend deleting in favor of `countBy()`. |
| L2 | [`Database::update/delete($where)`](application/core/Database.php:134) | Accept a raw `$where` string by design | Safe today because all callers pass literals. Consider an array-based WHERE API. |
| L3 | `SHOW COLUMNS ... LIKE '{$col}'` in [AutoMigration.php](application/core/AutoMigration.php) / `Booking_wizard.php:2493` | Column names interpolated | Names are hardcoded constants; admin/migration-side only. |

### 2b.2 Cross-Site Scripting (XSS) — No high-confidence exploitable issue found

The app applies **three layers of XSS defense**, consistently:

1. **Input-time sanitization** — most controllers wrap text fields in
   `sanitize_input()` ([common_helper.php:62](application/helpers/common_helper.php)):
   `htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8')`.
2. **Output-time escaping** — views consistently use `htmlspecialchars()` / the
   context-aware `esc()` helper. Every `nl2br()` is correctly ordered as
   `nl2br(htmlspecialchars(...))`; all checked DB-field outputs are escaped.
3. **Nonce-based CSP** —
   [`set_security_headers()`](application/helpers/security_helper.php:262) sets
   `script-src 'self' 'nonce-…'` with **no `unsafe-inline`**, neutralizing
   injected inline scripts and `javascript:` URIs.

Surfaces specifically traced: the public booking wizard (`customer_name`,
`special_requests` are sanitized on save *and* escaped on display); reflected
vectors (no view echoes `$_GET`/`$_POST`/`$_SERVER`/`PHP_SELF`/`REQUEST_URI`
directly — the search page escapes the reflected query); and `innerHTML` / JS
sinks (all client-side writes use static or numerically-computed strings).

**Latent XSS items (not independently exploitable — escaping/CSP saves each):**

| # | Location | Issue | Why not exploitable now |
|---|----------|-------|-------------------------|
| L4 | [Entities.php:57-67](application/controllers/Entities.php) | `create()` saves raw `$_POST` while `edit()` (line 121+) sanitizes — inconsistent | Entities views escape every field with `htmlspecialchars` on output |
| L5 | [entities/view.php:94](application/views/entities/view.php) | `website` rendered into `href="…"`; `htmlspecialchars` does not block a stored `javascript:` URI | Nonce-based CSP blocks `javascript:` execution; also requires a victim click |
| L6 | [bookings/calendar_timeslots.php:17](application/views/bookings/calendar_timeslots.php) | `<?= $flash['message'] ?>` rendered raw (every other view escapes flash) | Flash messages on this page are static/system-generated; no user input reaches them |

**Hardening recommendations (low priority):**

1. Standardize input handling — make `Entities::create()` (and other raw-`$_POST`
   savers) match the sanitized pattern in `edit()`.
2. Validate URL fields — enforce an `http(s)://` scheme
   (`filter_var($url, FILTER_VALIDATE_URL)` + scheme allowlist) for `website`
   before storing/rendering in an `href`.
3. Escape the lone raw flash output in `calendar_timeslots.php`.

---

## 3. Methodology

- **Phase 1 — Repository context.** Catalogued the custom MVC framework, routing
  (`application/core/Router.php`), the CSRF helper, permission model
  (`requirePermission()`), and existing validation helpers (`validateFileUpload()`).
- **Phase 2 — Comparative analysis.** Compared each controller's delete/upload
  handlers against the secure patterns already present in the codebase
  (e.g. `Bookings::delete()`).
- **Phase 3 — Vulnerability assessment.** Traced data flow from user input
  (`$_GET`, `$_POST`, `$_FILES`) to sensitive sinks (SQL, `exec`, file ops).
  Each candidate finding was independently re-verified to filter false positives;
  only findings at ≥ 8/10 confidence are reported above.

**Categories examined:** SQL injection, command injection, path traversal, XSS,
authentication bypass, privilege escalation, session management, CSRF, insecure
file upload, RCE (eval / dynamic include), hardcoded secrets, data exposure.

**Out of scope / excluded by policy:** Denial-of-Service & resource exhaustion,
rate limiting, secrets-at-rest hardening, outdated third-party dependencies,
log-spoofing, theoretical race conditions, and pure best-practice hardening with
no concrete attack path.

---

## 4. Prioritized Remediation Plan

| Priority | Finding | Effort | Action |
|---|---|---|---|
| **P0** | Vuln 2 — Backup restore | Low | Add upload validation + least-privilege DB user. Single file. |
| **P0** | Vuln 1 — CSRF deletes | Medium | Add POST+CSRF guard to all `delete()` methods; convert delete links to POST forms. Repetitive but mechanical. |
| **P1** | Defense-in-depth | Low | Make `enforce_global_csrf()` reject state-changing GET routes. |

---

## 5. Remediation Breakage Risk Assessment

Before applying fixes, the functional risk of each was assessed. Nothing here is
inherently destructive, but two fixes **will break functionality if applied
carelessly** (marked ⚠️).

| Fix | Risk | Notes |
|-----|------|-------|
| **Vuln 1 — CSRF deletes** | ⚠️ **High if half-applied** | Controller guard and view form **must** change together. Adding `if REQUEST_METHOD !== 'POST'` to a `delete()` while leaving the GET anchor links in views **breaks every delete button**. Each form must include `csrf_field()` or the global guard rejects the POST. Apply incrementally, one resource at a time, testing each. `Bookings::delete()` + bulk-delete are the working reference. Sweep for non-button `/delete/` references (notification links, emails, redirects) first. |
| **Vuln 2 — Backup restore** | ✅ Low (verified) | App backups are produced by `mysqldump` → plain `.sql` files (`backup_<timestamp>.sql`, see [`createBackup()`](application/controllers/Backup.php:75)). Extension allowlist `['sql','gz']`, `finfo` MIME `text/plain`, and a `-- MySQL/MariaDB dump` header check all accept the app's own backups. **Caveat:** verify the header line against a real file in `/backups/`; keep the header check lenient (warn, don't hard-reject) to avoid blocking valid restores. Least-privilege DB user is purely additive. |
| **XSS L4 — sanitize `Entities::create()`** | ⚠️ Double-encoding | `Entities::edit()` already calls `sanitize_input()` (`htmlspecialchars`) **and** views escape again on output → existing double-encoding (`&` → `&amp;amp;`). Making `create()` match `edit()` only makes both consistently double-encode (cosmetic). The clean fix is the opposite: pick **one** escaping layer (output) and remove input-time escaping — a broader change; don't rush a partial one. |
| **XSS L5 — `website` URL validation** | ✅ Low | `FILTER_VALIDATE_URL` + scheme allowlist only rejects malformed/`javascript:` URLs. To avoid rejecting bare-domain entries (`example.com`), auto-prepend `https://` when no scheme is present rather than rejecting. |
| **XSS L6 — escape flash in `calendar_timeslots.php`** | ✅ Safe | Wrapping in `htmlspecialchars()` is safe unless a message intentionally contains HTML (e.g. `Entities` uses `implode('<br>', $errors)`). Those flashes don't reach this page today; match the main layout's rendering for consistency. |
| **SQLi L1/L2 — remove `count()`, array WHERE** | ✅ Safe | `Base_Model::count()` has zero callers passing arguments; safe to remove/replace. Array-WHERE API is additive. |

**Recommended order:** start with the verified low-risk, high-value fix
(backup-restore validation), then do the CSRF fix incrementally per resource with
testing. Treat the XSS items as low-priority hardening, and resolve the
double-encoding direction deliberately before touching input sanitization.

---

*Generated as part of a full-scale security audit. Findings below the 8/10
confidence threshold were excluded to minimize false positives. This report
covers only the issues confirmed during this review and is not a guarantee of
the absence of other vulnerabilities.*
