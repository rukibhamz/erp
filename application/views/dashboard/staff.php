<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">My Dashboard</h1>
    <p class="text-muted mb-0">Your tasks and daily schedule</p>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-list-check"></i> Assigned Work Orders</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Your assigned tasks will appear here.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-calendar3"></i> Daily Schedule</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Your daily schedule will appear here.</p>
            </div>
        </div>
    </div>
</div>



