// Main JavaScript for Business Management System

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Form validation enhancement
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Toast notifications (if using Bootstrap toasts)
    const toastElList = document.querySelectorAll('.toast');
    const toastList = [...toastElList].map(toastEl => new bootstrap.Toast(toastEl));
    
    // Table row highlight on hover
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
});

// Utility functions
const ERP = {
    // Show loading spinner
    showLoading: function() {
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border text-primary position-fixed top-50 start-50';
        spinner.setAttribute('role', 'status');
        spinner.innerHTML = '<span class="visually-hidden">Loading...</span>';
        document.body.appendChild(spinner);
    },
    
    // Hide loading spinner
    hideLoading: function() {
        const spinner = document.querySelector('.spinner-border');
        if (spinner) {
            spinner.remove();
        }
    },
    
    // Format date
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    // Format currency
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
};

// Theme Switcher Functionality - Enhanced with smooth transitions
(function() {
    // Get current theme from localStorage or default to light
    function getTheme() {
        return localStorage.getItem('theme') || 'light';
    }
    
    // Set theme with smooth transition
    function setTheme(theme) {
        // Add transition class for smooth switching
        document.documentElement.classList.add('theme-transitioning');
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        updateThemeIcons(theme);
        
        // Remove transition class after animation
        setTimeout(() => {
            document.documentElement.classList.remove('theme-transitioning');
        }, 300);
    }
    
    // Update theme icons
    function updateThemeIcons(theme) {
        const themeIcon = document.getElementById('themeIcon');
        const themeIconMobile = document.getElementById('themeIconMobile');
        
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
            themeIcon.style.transition = 'transform 0.3s ease, color 0.3s ease';
        }
        if (themeIconMobile) {
            themeIconMobile.className = theme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
            themeIconMobile.style.transition = 'transform 0.3s ease, color 0.3s ease';
        }
    }
    
    // Toggle theme
    function toggleTheme(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const currentTheme = getTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
        
        // Add visual feedback
        const button = e ? e.currentTarget : null;
        if (button) {
            button.style.transform = 'scale(0.95)';
            setTimeout(() => {
                button.style.transform = '';
            }, 150);
        }
    }
    
    // Initialize theme immediately (before DOMContentLoaded to prevent flash)
    (function initTheme() {
        const theme = getTheme();
        document.documentElement.setAttribute('data-theme', theme);
    })();
    
    // Initialize theme on page load
    document.addEventListener('DOMContentLoaded', function() {
        const theme = getTheme();
        setTheme(theme);
        
        // Add event listeners to theme toggle buttons
        const themeToggle = document.getElementById('themeToggle');
        const themeToggleMobile = document.getElementById('themeToggleMobile');
        
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
            // Update icon on load
            updateThemeIcons(theme);
        }
        
        if (themeToggleMobile) {
            themeToggleMobile.addEventListener('click', toggleTheme);
            // Update icon on load
            updateThemeIcons(theme);
        }
    });
    
    // Listen for theme changes from other tabs/windows
    window.addEventListener('storage', function(e) {
        if (e.key === 'theme') {
            const newTheme = e.newValue || 'light';
            setTheme(newTheme);
        }
    });
})();

