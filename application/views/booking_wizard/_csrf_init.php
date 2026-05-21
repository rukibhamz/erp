<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (function_exists('csrf_token')): ?>
<script nonce="<?= csp_nonce() ?>">
window.WIZARD_CSRF = <?= json_encode(csrf_token()) ?>;
function wizardCsrfQuery() {
    return 'csrf_token=' + encodeURIComponent(window.WIZARD_CSRF || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
}
function wizardCsrfPrefix(body) {
    const token = wizardCsrfQuery();
    return body ? token + '&' + body : token;
}
/** Safe alert in a container (avoids innerHTML with server/user text). */
function wizardSetAlert(container, alertClass, message) {
    if (!container) return;
    container.replaceChildren();
    const div = document.createElement('div');
    div.className = 'alert ' + alertClass;
    div.textContent = message == null ? '' : String(message);
    container.appendChild(div);
}
/** Alert inside Bootstrap col-12 grid cell. */
function wizardSetGridAlert(container, alertClass, message) {
    if (!container) return;
    container.replaceChildren();
    const col = document.createElement('div');
    col.className = 'col-12';
    const div = document.createElement('div');
    div.className = 'alert ' + alertClass;
    div.textContent = message == null ? '' : String(message);
    col.appendChild(div);
    container.appendChild(col);
}
</script>
<?php endif; ?>
