    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script nonce="<?= function_exists('csp_nonce') ? csp_nonce() : '' ?>">
        window.BASE_URL = '<?= function_exists('base_url') ? base_url() : '/' ?>';
    </script>
    <script src="<?= function_exists('base_url') ? base_url('assets/js/main.js') : '/assets/js/main.js' ?>"></script>
</body>
</html>

