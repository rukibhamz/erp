# UI Consistency Guide

This document outlines the standardized UI patterns and components to ensure consistency across the entire ERP system.

## Table of Contents
1. [Page Headers](#page-headers)
2. [Buttons](#buttons)
3. [Cards](#cards)
4. [Tables](#tables)
5. [Forms](#forms)
6. [Alerts](#alerts)
7. [Badges](#badges)
8. [Navigation](#navigation)
9. [Empty States](#empty-states)
10. [KPI Cards](#kpi-cards)

---

## Page Headers

### Standard Pattern
All pages must use the standardized page header structure:

```php
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Page Title</h1>
        <div class="d-flex gap-2">
            <!-- Action buttons here -->
            <a href="<?= base_url('path') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create
            </a>
            <a href="<?= base_url('back') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>
```

### Rules
- Always use `page-header` class
- Title must use `page-title` class
- Action buttons should be in a flex container with `gap-2`
- Back button should use `btn-outline-secondary`
- Primary actions use `btn-primary`

---

## Buttons

### Button Variants
- **Primary**: `btn btn-primary` - Main actions (Create, Save, Submit)
- **Secondary**: `btn btn-outline-secondary` - Secondary actions (Back, Cancel)
- **Success**: `btn btn-success` - Positive actions (Approve, Confirm)
- **Danger**: `btn btn-danger` - Destructive actions (Delete, Remove)
- **Info**: `btn btn-info` - Informational actions
- **Warning**: `btn btn-warning` - Warning actions

### Button Sizes
- **Small**: `btn btn-sm` - For compact spaces
- **Default**: `btn` - Standard size
- **Large**: `btn btn-lg` - For prominent actions

### Button Groups
```php
<div class="btn-group btn-group-sm">
    <a href="..." class="btn btn-primary" title="View">
        <i class="bi bi-eye"></i>
    </a>
    <a href="..." class="btn btn-primary" title="Edit">
        <i class="bi bi-pencil"></i>
    </a>
    <button type="submit" class="btn btn-danger" title="Delete">
        <i class="bi bi-trash"></i>
    </button>
</div>
```

### Rules
- Always include icons with buttons
- Use `title` attribute for icon-only buttons
- Group related actions in `btn-group`
- Use consistent icon classes (Bootstrap Icons)

---

## Cards

### Standard Card Structure
```php
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0">Card Title</h5>
    </div>
    <div class="card-body">
        <!-- Card content -->
    </div>
    <div class="card-footer">
        <!-- Footer content -->
    </div>
</div>
```

### Card Variants
- **Standard**: `card` - Default card
- **Shadow**: `card shadow-sm` - Subtle shadow
- **No Border**: `card border-0` - No border
- **Colored Header**: `card-header bg-primary text-white` - Primary header

### Rules
- Always use `shadow-sm` for cards
- Card headers should have `card-title` with `mb-0`
- Use `card-body` for main content
- Footer is optional

---

## Tables

### Standard Table Structure
```php
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Column 1</th>
                <th class="text-end">Column 2</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Data 1</td>
                <td class="text-end">Data 2</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <!-- Action buttons -->
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### Table Variants
- **Hover**: `table table-hover` - Highlight rows on hover
- **Bordered**: `table table-bordered` - All borders
- **Striped**: `table table-striped` - Alternating row colors

### Rules
- Always wrap tables in `table-responsive`
- Use `text-end` for numeric columns
- Action column should be last
- Use `btn-group btn-group-sm` for action buttons

---

## Forms

### Standard Form Structure
```php
<form method="POST" action="<?= base_url('path') ?>">
    <?php echo csrf_field(); ?>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="field" class="form-label">
                Field Label <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="field" name="field" required>
        </div>
        <div class="col-md-6">
            <!-- Another field -->
        </div>
    </div>
    
    <div class="d-flex justify-content-between">
        <a href="<?= base_url('back') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Save
        </button>
    </div>
</form>
```

### Form Rules
- Use `row mb-3` for form rows
- Use `col-md-6` or `col-md-4` for field columns
- Required fields: Add `<span class="text-danger">*</span>`
- Always include CSRF token
- Use consistent button layout at bottom

---

## Alerts

### Standard Alert Structure
```php
<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
```

### Alert Types
- `alert-success` - Success messages
- `alert-danger` - Error messages
- `alert-warning` - Warning messages
- `alert-info` - Informational messages

### Rules
- Always use `alert-dismissible fade show`
- Include close button
- Position after page header, before content

---

## Badges

### Standard Badge Usage
```php
<span class="badge bg-success">Active</span>
<span class="badge bg-danger">Inactive</span>
<span class="badge bg-warning">Pending</span>
<span class="badge bg-info">Processing</span>
```

### Badge Colors
- `bg-success` - Active, Approved, Completed
- `bg-danger` - Inactive, Rejected, Failed
- `bg-warning` - Pending, Draft, Warning
- `bg-info` - Processing, Info
- `bg-secondary` - Neutral, Default

---

## Navigation

### Module Navigation
```php
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= $is_active ? 'active' : '' ?>" href="...">
            <i class="bi bi-icon"></i> Label
        </a>
    </nav>
</div>
```

### Rules
- Use module-specific nav class (e.g., `accounting-nav`, `staff-management-nav`)
- Active state uses `active` class
- Always include icons
- Use `mb-4` for spacing

---

## Empty States

### Standard Empty State
```php
<?php if (empty($items)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-icon" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No items found.</p>
            <a href="<?= base_url('create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create First Item
            </a>
        </div>
    </div>
<?php endif; ?>
```

### Rules
- Use large icon (3rem) with muted color
- Include helpful message
- Provide action button to create first item
- Center align content

---

## KPI Cards

### Standard KPI Card
```php
<div class="col-md-3">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Label</h6>
                    <h3 class="card-title mb-0 fw-bold"><?= format_large_currency($value) ?></h3>
                </div>
                <div class="text-primary">
                    <i class="bi bi-icon" style="font-size: 2rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Rules
- Use `shadow-sm border-0` for modern look
- Label: `text-muted small text-uppercase`
- Value: `fw-bold` with appropriate formatting
- Icon: 2rem size, 0.3 opacity, colored
- Use `format_large_currency()` for monetary values

---

## Color Scheme

### Primary Colors
- **Primary**: #000000 (Black)
- **Success**: #28a745 (Green)
- **Danger**: #dc3545 (Red)
- **Warning**: #ffc107 (Yellow)
- **Info**: #17a2b8 (Cyan)

### Text Colors
- **Primary**: #212529
- **Secondary**: #6c757d
- **Muted**: #adb5bd

---

## Spacing

### Standard Spacing Scale
- `mb-0` - No margin bottom
- `mb-1` - 0.25rem (4px)
- `mb-2` - 0.5rem (8px)
- `mb-3` - 1rem (16px)
- `mb-4` - 1.5rem (24px)

### Gap Utilities
- `gap-1` - 0.25rem
- `gap-2` - 0.5rem
- `gap-3` - 1rem
- `gap-4` - 1.5rem

---

## Typography

### Headings
- `h1` / `.page-title` - Page titles (1.5rem, bold)
- `h2` - Section titles (1.25rem, semibold)
- `h3` - Subsection titles (1.125rem, semibold)
- `h5` / `.card-title` - Card titles (1.125rem, semibold)
- `h6` - Small labels (0.875rem, medium)

### Font Weights
- Normal: 400
- Medium: 500
- Semibold: 600
- Bold: 700

---

## Icons

### Standard Icon Usage
- Always use Bootstrap Icons (`bi bi-icon-name`)
- Icon size: 1rem (default), 0.875rem (small), 2rem (large)
- Include icons with buttons and navigation items
- Use `title` attribute for icon-only buttons

---

## Responsive Design

### Breakpoints
- Mobile: < 768px
- Tablet: 768px - 991px
- Desktop: ≥ 992px

### Mobile Considerations
- Stack buttons vertically on mobile
- Reduce padding on cards
- Simplify table layouts
- Use full-width buttons

---

## Best Practices

1. **Consistency First**: Always use standardized classes and patterns
2. **Accessibility**: Include proper labels, ARIA attributes, and keyboard navigation
3. **Responsive**: Test on all screen sizes
4. **Performance**: Minimize custom CSS, use utility classes
5. **Maintainability**: Follow the patterns in this guide

---

## Quick Reference

### Common Patterns

**Page Header with Actions:**
```php
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Title</h1>
        <div class="d-flex gap-2">
            <a href="..." class="btn btn-primary"><i class="bi bi-plus"></i> Create</a>
        </div>
    </div>
</div>
```

**Action Buttons:**
```php
<div class="btn-group btn-group-sm">
    <a href="..." class="btn btn-primary" title="View"><i class="bi bi-eye"></i></a>
    <a href="..." class="btn btn-primary" title="Edit"><i class="bi bi-pencil"></i></a>
    <button type="submit" class="btn btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
</div>
```

**Status Badge:**
```php
<span class="badge bg-<?= $status === 'active' ? 'success' : 'secondary' ?>">
    <?= ucfirst($status) ?>
</span>
```

