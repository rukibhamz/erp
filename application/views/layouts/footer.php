    </div>
    
    <?php if (isset($current_user)): ?>
    </div> <!-- Close main-content-wrapper -->
    <?php endif; ?>
    
    <footer>
        <div class="container-fluid">
            <div class="text-center text-muted" style="font-size: 0.875rem;">
                &copy; <?= date('Y') ?> Business ERP
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/main.js') ?>"></script>
    <?php if (isset($current_user)): ?>
    <script src="<?= base_url('assets/js/sidebar.js') ?>"></script>
    <?php endif; ?>
</body>
</html>

