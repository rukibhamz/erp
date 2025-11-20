<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Get current URL to determine active tab
$current_url = $_GET['url'] ?? '';
$current_url = trim($current_url, '/');
$uri_segments = !empty($current_url) ? explode('/', $current_url) : [];

// Determine active sections
$is_dashboard = (empty($current_url) || $current_url === 'staff_management') && !isset($uri_segments[1]);
$is_employees = (isset($uri_segments[0]) && $uri_segments[0] === 'staff_management' && isset($uri_segments[1]) && $uri_segments[1] === 'employees') || 
                (isset($uri_segments[0]) && $uri_segments[0] === 'employees');
$is_payroll = (isset($uri_segments[0]) && $uri_segments[0] === 'staff_management' && isset($uri_segments[1]) && $uri_segments[1] === 'payroll') ||
              (isset($uri_segments[0]) && $uri_segments[0] === 'payroll');
?>

<!-- Staff Management Module Navigation -->
<div class="staff-management-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= $is_dashboard || $is_employees ? 'active' : '' ?>" href="<?= base_url('staff_management/employees') ?>">
            <i class="bi bi-people"></i> Employees
        </a>
        <a class="nav-link <?= $is_payroll ? 'active' : '' ?>" href="<?= base_url('staff_management/payroll') ?>">
            <i class="bi bi-cash-stack"></i> Payroll
        </a>
    </nav>
</div>

