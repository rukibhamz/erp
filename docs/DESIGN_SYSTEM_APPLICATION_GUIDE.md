# Design System Application Guide

## Overview

This guide explains how to apply the design system to existing views and maintain consistency across the application.

## Design System Files

1. **`assets/css/design-system.css`** - Core design system with variables and components
2. **`assets/css/responsive.css`** - Responsive utilities
3. **`assets/css/navigation.css`** - Navigation styling

## CSS Variables Available

### Colors
```css
--color-primary
--color-primary-hover
--color-primary-active
--color-success
--color-danger
--color-warning
--color-info
--bg-primary
--bg-secondary
--bg-tertiary
--text-primary
--text-secondary
--text-muted
--border-color
```

### Spacing
```css
--space-xs  /* 4px */
--space-sm  /* 8px */
--space-md  /* 16px */
--space-lg  /* 24px */
--space-xl  /* 32px */
```

### Typography
```css
--font-size-xs through --font-size-4xl
--font-weight-normal, --font-weight-medium, --font-weight-semibold, --font-weight-bold
--line-height-tight, --line-height-normal, --line-height-relaxed
```

## Component Classes

### Buttons
```html
<!-- Primary Button -->
<button class="btn btn-primary">Save</button>

<!-- Button Sizes -->
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary btn-md">Medium</button>
<button class="btn btn-primary btn-lg">Large</button>

<!-- Button Variants -->
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-outline-primary">Outline</button>
<button class="btn btn-ghost">Ghost</button>
```

### Cards
```html
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Card Title</h2>
    </div>
    <div class="card-body">
        Card content
    </div>
    <div class="card-footer">
        Footer content
    </div>
</div>
```

### Forms
```html
<div class="form-group">
    <label class="form-label required">Field Name</label>
    <input type="text" class="form-control" placeholder="Enter value">
    <span class="form-text">Helper text</span>
</div>
```

### Tables
```html
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Column 1</th>
                <th>Column 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Data 1</td>
                <td>Data 2</td>
            </tr>
        </tbody>
    </table>
</div>
```

### Badges
```html
<span class="badge badge-primary">Primary</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-info">Info</span>
```

## Migration Checklist

### Step 1: Replace Inline Styles
**BEFORE:**
```html
<button style="background: blue; padding: 10px;">Save</button>
```

**AFTER:**
```html
<button class="btn btn-primary">Save</button>
```

### Step 2: Replace Custom Button Classes
**BEFORE:**
```html
<button class="button submit-btn">Save</button>
```

**AFTER:**
```html
<button class="btn btn-primary">Save</button>
```

### Step 3: Replace Custom Card Classes
**BEFORE:**
```html
<div class="panel">
    <div class="panel-header">Title</div>
    <div class="panel-body">Content</div>
</div>
```

**AFTER:**
```html
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Title</h2>
    </div>
    <div class="card-body">Content</div>
</div>
```

### Step 4: Replace Hardcoded Colors
**BEFORE:**
```css
<style>
.custom-element {
    background: #f8f9fa;
    color: #212529;
    border: 1px solid #dee2e6;
}
</style>
```

**AFTER:**
```css
<style>
.custom-element {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}
</style>
```

### Step 5: Use Utility Classes
**BEFORE:**
```html
<div style="margin-top: 16px; margin-bottom: 24px; padding: 12px;">
```

**AFTER:**
```html
<div class="mt-3 mb-4 p-2">
```

## Common Patterns

### Page Header
```html
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Page Title</h1>
        <div class="d-flex gap-2">
            <a href="#" class="btn btn-primary">Action</a>
        </div>
    </div>
</div>
```

### Stat Cards
```html
<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="stat-icon primary me-3">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <div class="stat-number">1,234</div>
                <div class="stat-label">Users</div>
            </div>
        </div>
    </div>
</div>
```

### Action Buttons in Tables
```html
<div class="btn-group btn-group-sm">
    <a href="#" class="btn btn-outline-primary" title="Edit">
        <i class="bi bi-pencil"></i>
    </a>
    <a href="#" class="btn btn-outline-danger" title="Delete">
        <i class="bi bi-trash"></i>
    </a>
</div>
```

## Priority Files to Update

1. **High Priority** (Most visible):
   - `application/views/dashboard/*.php`
   - `application/views/users/index.php`
   - `application/views/properties/*.php`
   - `application/views/bookings/*.php`

2. **Medium Priority**:
   - `application/views/accounting/*.php`
   - `application/views/inventory/*.php`
   - `application/views/utilities/*.php`

3. **Low Priority**:
   - Internal/admin views
   - Settings pages

## Testing

After applying design system:

1. **Visual Check**: Ensure consistent styling
2. **Responsive Check**: Test on mobile/tablet/desktop
3. **Dark Mode**: Test if dark mode is implemented
4. **Browser Check**: Test in Chrome, Firefox, Safari, Edge

## Notes

- Design system CSS is already loaded in `header.php`
- Bootstrap classes still work (Bootstrap 5.3 is loaded)
- Design system classes complement Bootstrap, don't replace it
- Use design system for new components
- Gradually migrate existing components

---

**Status**: Design System Ready for Application  
**Last Updated**: Current Session

