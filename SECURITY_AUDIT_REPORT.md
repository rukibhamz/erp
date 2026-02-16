# Security Audit Report - Second Round Review

This report details the findings of a comprehensive security audit conducted on the PHP Business Management System. The review covered advanced security vulnerabilities, business logic flaws, code quality, and architectural issues.

---

## 1. Privilege Escalation in User Management

**Category**: Authentication & Authorization
**Severity**: Critical
**Issue Title**: Privilege Escalation via IDOR in User Edit Functionality
**File & Location**: `application/controllers/Users.php`, line 185 (in `edit` method)
**Vulnerability Description**: The `edit` function in the `Users` controller only checks if the logged-in user has the `update` permission for the `users` module. It fails to verify that the user has the right to edit the *specific* user account being requested. This allows a user with a lower-privileged role (e.g., 'manager') who has the 'update users' permission to edit the accounts of higher-privileged users, including administrators.
**Attack Scenario**:
1. A manager logs into the system.
2. The manager navigates to the user list and clicks to edit their own profile, observing the URL (e.g., `https://example.com/users/edit/10`).
3. The manager identifies the user ID of an administrator (e.g., by observing other URLs or guessing common IDs like 1).
4. The manager manually changes the URL in their browser to `https://example.com/users/edit/1`.
5. The system loads the edit page for the administrator account.
6. The manager can now change the administrator's email address, password, or role, effectively taking over the account or escalating their own privileges.
**Proof of Concept**:
```http
GET /users/edit/1 HTTP/1.1
Host: your-erp-domain.com
Cookie: session_id=...; user_id=...; role=manager
```
**Impact**: An attacker with a mid-level role like 'manager' can gain full administrative control over the system by modifying an admin account. This could lead to a complete compromise of the application, data theft, and unauthorized actions.
**Current Code**:
```php
public function edit($id) {
    $this->requirePermission('users', 'update'); // Only checks general permission

    $user = $this->userModel->getById($id);

    if (!$user) {
        $this->setFlashMessage('danger', 'User not found.');
        redirect('users');
    }
    // ... (rest of the function)
}
```
**Recommended Fix**: Implement a check to ensure that the logged-in user has a higher privilege level than the user they are attempting to edit. Additionally, prevent users from editing their own roles.
```php
public function edit($id) {
    $this->requirePermission('users', 'update');

    // Prevent editing own account in this interface
    if ($id == $this->session['user_id']) {
        redirect('profile'); // Redirect to dedicated profile page
    }

    $userToEdit = $this->userModel->getById($id);
    if (!$userToEdit) {
        $this->setFlashMessage('danger', 'User not found.');
        redirect('users');
    }

    $currentUserRole = $this->session['role'];
    $targetUserRole = $userToEdit['role'];

    // Define role hierarchy
    $roleHierarchy = ['user' => 1, 'manager' => 2, 'admin' => 3, 'super_admin' => 4];

    // Enforce hierarchy check
    if ($roleHierarchy[$currentUserRole] <= $roleHierarchy[$targetUserRole]) {
        $this->setFlashMessage('danger', 'You do not have permission to edit this user.');
        redirect('users');
    }

    // ... (rest of the function)
}
```
**Prevention**: Always implement fine-grained access control checks that verify not only *what* a user can do, but also *which specific resources* they can do it to.

---

## 2. SQL Injection in Base Model

**Category**: Injection Attacks
**Severity**: High
**Issue Title**: SQL Injection via Unsanitized `orderBy` and `where` Parameters
**File & Location**: `application/core/Base_Model.php`, lines 18 and 54 (in `getAll` and `count` methods)
**Vulnerability Description**: The `getAll` method in the `Base_Model` directly concatenates the `$orderBy` parameter into the SQL query without any sanitization or validation. This allows an attacker to inject arbitrary SQL into the `ORDER BY` clause. The `count` method has a similar vulnerability with the `$where` parameter.
**Attack Scenario**:
An attacker can manipulate a URL parameter that is passed to the `getAll` method's `$orderBy` argument. For example, if a user list is sortable, the attacker could inject a malicious SQL query into the sort parameter.
**Proof of Concept**:
```
// Malicious orderBy parameter
$orderBy = "id; --, SLEEP(10)";
```
This would result in the following SQL query, which would cause a 10-second delay in the database response, confirming the vulnerability:
```sql
SELECT * FROM `erp_users` ORDER BY id; --, SLEEP(10)
```
**Impact**: An attacker could use this vulnerability to extract sensitive data from the database, modify data, or cause a denial of service. The exact impact depends on the database user's privileges.
**Current Code**:
```php
public function getAll($limit = null, $offset = 0, $orderBy = null) {
    try {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}"; // Vulnerable concatenation
        }

        // ... (rest of the function)
    } // ...
}

public function count($where = '1=1', $params = []) {
    $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` WHERE {$where}"; // Vulnerable concatenation
    $result = $this->db->fetchOne($sql, $params);
    return $result['count'] ?? 0;
}
```
**Recommended Fix**: Implement a whitelisting approach for the `$orderBy` parameter to ensure that only valid column names and sort directions are allowed. For the `count` method, avoid passing raw SQL in the `$where` parameter. Instead, construct the `WHERE` clause using an array of conditions that can be safely converted into a parameterized query.
```php
public function getAll($limit = null, $offset = 0, $orderBy = null) {
    try {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";

        if ($orderBy) {
            // Whitelist validation
            $allowedColumns = ['id', 'username', 'email', 'created_at', 'last_login'];
            $sortOrder = 'ASC';
            if (is_array($orderBy)) {
                $column = $orderBy['column'];
                $order = strtoupper($orderBy['order']);
                if (in_array($order, ['ASC', 'DESC'])) {
                    $sortOrder = $order;
                }
            } else {
                $column = $orderBy;
            }

            if (in_array($column, $allowedColumns)) {
                $sql .= " ORDER BY `{$column}` {$sortOrder}";
            }
        }

        // ... (rest of the function)
    } // ...
}

// Example of a safer count method
public function countBy(array $conditions) {
    $whereClauses = [];
    $params = [];
    foreach ($conditions as $field => $value) {
        $whereClauses[] = "`{$field}` = ?";
        $params[] = $value;
    }
    $whereSql = implode(' AND ', $whereClauses);

    $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` WHERE {$whereSql}";
    $result = $this->db->fetchOne($sql, $params);
    return $result['count'] ?? 0;
}
```
**Prevention**: Never trust user input. Always sanitize, validate, and use parameterized queries to prevent SQL injection vulnerabilities.

---

## 3. Denial of Service in File Upload

**Category**: File Operations
**Severity**: Medium
**Issue Title**: Mismatched MIME Type Whitelist Prevents Legitimate File Uploads
**File & Location**: `application/helpers/security_helper.php`, line 150 (in `validateFileUpload` function)
**Vulnerability Description**: The `validateFileUpload` function has a default `$allowedTypes` array that is used to validate the MIME type of uploaded files. However, this array is missing the MIME types for Microsoft Office documents (`.doc`, `.docx`, `.xls`, `.xlsx`), even though the internal `$allowedMappings` array includes them. This means that if the function is called without a custom `$allowedTypes` array, it will reject all Microsoft Office documents, preventing users from uploading legitimate files.
**Attack Scenario**: A user attempts to upload a `.docx` or `.xlsx` file through a form that uses the `validateFileUpload` function with its default parameters. The function will incorrectly identify the file as "not allowed" and reject the upload. This is not a malicious attack, but a functional bug that results in a denial of service for legitimate users.
**Proof of Concept**:
1. Create a form that allows file uploads and uses `validateFileUpload` without a custom `$allowedTypes` parameter.
2. Attempt to upload a `.docx` file.
3. The upload will fail with the error "File type not allowed".
**Impact**: This vulnerability prevents users from uploading common and expected file types, leading to a frustrating user experience and a denial of service for legitimate functionality.
**Current Code**:
```php
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']) {
    // ...

    // MIME type to extension mapping for validation
    $allowedMappings = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx']
    ];

    // Check if detected MIME type is allowed
    if (!in_array($detectedMimeType, $allowedTypes)) { // This check uses the incomplete default array
        return ['valid' => false, 'error' => 'File type not allowed - detected MIME type: ' . $detectedMimeType];
    }

    // ...
}
```
**Recommended Fix**: Update the default `$allowedTypes` array to include all the MIME types that are present in the `$allowedMappings` array.
```php
function validateFileUpload($file, $allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]) {
    // ...
}
```
**Prevention**: Ensure that all whitelists and validation rules are consistent and up-to-date. When multiple related checks are performed, they should all be derived from a single, authoritative source of truth.

---

## 4. Time-of-Check to Time-of-Use (TOCTOU) in Booking Wizard

**Category**: Business Logic Vulnerabilities
**Severity**: High
**Issue Title**: Price Manipulation via Session Data Tampering
**File & Location**: `application/controllers/Booking_wizard.php`, lines 301 and 421 (in `step5` and `finalize` methods)
**Vulnerability Description**: The booking wizard calculates the total price, including discounts and addons, in the `step5` method and stores these values in the session. The `finalize` method then retrieves these values from the session to create the final booking in the database. This creates a race condition where an attacker can manipulate the session data between the time it's calculated (`step5`) and the time it's used (`finalize`). An attacker could, for example, apply a promo code in `step5`, then remove the promo code from their session data but keep the discounted price, effectively getting a discount they are not entitled to.
**Attack Scenario**:
1. A user proceeds through the booking wizard to `step5`.
2. The user applies a valid promo code, and the discounted price is calculated and stored in the session.
3. The user uses browser developer tools or a proxy to intercept and modify their session data, removing the promo code but leaving the discounted total.
4. The user proceeds to `finalize` the booking.
5. The `finalize` method reads the manipulated session data, creating a booking with a discounted price but without a valid promo code attached.
**Impact**: This vulnerability can lead to financial loss for the business, as users can obtain services for a lower price than they should. It also undermines the integrity of the booking and reporting system.
**Current Code**:
```php
public function step5() {
    // ...
    // Price calculation
    $total = $subtotal - $discountAmount;

    // Store calculated amounts in session
    $_SESSION['booking_data']['total_amount'] = $total;
    // ...
}

public function finalize() {
    // ...
    $bookingData = $_SESSION['booking_data'] ?? [];

    // Create booking
    $bookingRecord = [
        // ...
        'total_amount' => $bookingData['total_amount'] ?? 0, // Uses session data directly
        'promo_code' => $bookingData['promo_code'] ?? null,
        // ...
    ];

    $bookingId = $this->bookingModel->create($bookingRecord);
    // ...
}
```
**Recommended Fix**: Recalculate the total price in the `finalize` method, using the raw inputs (resource ID, addons, promo code) from the session. Do not trust the calculated total stored in the session.
```php
public function finalize() {
    // ...
    $bookingData = $_SESSION['booking_data'] ?? [];

    // Recalculate price
    $resource = $this->facilityModel->getById($bookingData['resource_id']);
    $baseAmount = $this->facilityModel->calculatePrice(/* ... */);
    $addonsTotal = // ... recalculate addons total
    $discountAmount = 0;
    if (!empty($bookingData['promo_code'])) {
        $promoValidation = $this->promoCodeModel->validateCode(/* ... */);
        if ($promoValidation['valid']) {
            $discountAmount = $promoValidation['discount_amount'];
        }
    }
    $finalTotal = $baseAmount + $addonsTotal - $discountAmount + floatval($resource['security_deposit'] ?? 0);

    // Create booking
    $bookingRecord = [
        // ...
        'total_amount' => $finalTotal, // Use recalculated total
        'promo_code' => $bookingData['promo_code'] ?? null,
        // ...
    ];

    $bookingId = $this->bookingModel->create($bookingRecord);
    // ...
}
```
**Prevention**: Never trust data that can be manipulated by the client, including session data. Always re-validate and recalculate critical values on the server immediately before they are used to perform a sensitive action.

---

## 5. Code Duplication in User Permission Assignment

**Category**: Code Quality & Maintainability
**Severity**: Low
**Issue Title**: Duplicated Permission Assignment Logic in `create` and `edit` Methods
**File & Location**: `application/controllers/Users.php`, lines 112 and 225
**Vulnerability Description**: The `create` and `edit` methods in the `Users` controller both contain nearly identical blocks of code for assigning permissions to users based on their role. This code duplication makes the controller harder to read, maintain, and reason about. It also increases the risk that a bug fixed in one location will not be fixed in the other.
**Impact**: This is not a direct security vulnerability, but it is a code quality issue that can lead to security vulnerabilities in the future. Duplicated code is a common source of bugs and inconsistencies.
**Current Code**:
```php
// In create() method
if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
    // ...
} elseif ($data['role'] === 'admin') {
    $allPermissions = $this->permissionModel->getAllPermissions();
    $permissionIds = array_column($allPermissions, 'id');
    $this->userPermissionModel->assignPermissions($userId, $permissionIds);
} elseif ($data['role'] === 'manager') {
    $managerPermissions = $this->getManagerPermissions();
    $permissionIds = array_column($managerPermissions, 'id');
    $this->userPermissionModel->assignPermissions($userId, $permissionIds);
}

// In edit() method
if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
    // ...
} elseif ($data['role'] === 'admin') {
    $allPermissions = $this->permissionModel->getAllPermissions();
    $permissionIds = array_column($allPermissions, 'id');
    $this->userPermissionModel->assignPermissions($id, $permissionIds);
} elseif ($data['role'] === 'manager') {
    $managerPermissions = $this->getManagerPermissions();
    $permissionIds = array_column($managerPermissions, 'id');
    $this->userPermissionModel->assignPermissions($id, $permissionIds);
}
```
**Recommended Fix**: Create a private helper method within the `Users` controller to handle the permission assignment logic. This will remove the code duplication and make the `create` and `edit` methods easier to read and maintain.
```php
private function assignPermissionsByRole($userId, $role, $postedPermissions) {
    if (isset($postedPermissions) && is_array($postedPermissions)) {
        $permissionIds = array_map('intval', $postedPermissions);
        $this->userPermissionModel->assignPermissions($userId, $permissionIds);
    } elseif ($role === 'admin') {
        $allPermissions = $this->permissionModel->getAllPermissions();
        $permissionIds = array_column($allPermissions, 'id');
        $this->userPermissionModel->assignPermissions($userId, $permissionIds);
    } elseif ($role === 'manager') {
        $managerPermissions = $this->getManagerPermissions();
        $permissionIds = array_column($managerPermissions, 'id');
        $this->userPermissionModel->assignPermissions($userId, $permissionIds);
    }
}

// In create() method
$this->assignPermissionsByRole($userId, $data['role'], $_POST['permissions']);

// In edit() method
$this->assignPermissionsByRole($id, $data['role'], $_POST['permissions']);
```
**Prevention**: Follow the Don't Repeat Yourself (DRY) principle. When you find yourself writing the same code in multiple places, refactor it into a separate function or method.
