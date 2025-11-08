/**
 * Dark Mode Manager - Complete Implementation
 * Handles dark mode switching, persistence, and component updates
 */
class DarkModeManager {
    constructor() {
        this.darkModeKey = 'darkMode';
        this.themeKey = 'theme'; // For backward compatibility
        this.isTransitioning = false;
        this.init();
    }

    init() {
        // Check for saved preference or system preference
        const savedMode = localStorage.getItem(this.darkModeKey) || localStorage.getItem(this.themeKey);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Determine initial mode
        let shouldEnableDark = false;
        if (savedMode === 'dark') {
            shouldEnableDark = true;
        } else if (savedMode === 'light') {
            shouldEnableDark = false;
        } else if (prefersDark) {
            // No saved preference, use system preference
            shouldEnableDark = true;
        }

        // Apply initial theme (without transition for initial load)
        if (shouldEnableDark) {
            this.enableDarkMode(false);
        } else {
            this.disableDarkMode(false);
        }

        // Listen for toggle clicks
        this.attachListeners();

        // Listen for system theme changes
        this.watchSystemTheme();

        // Refresh charts and dynamic content
        this.refreshDynamicContent();
    }

    enableDarkMode(animate = true) {
        if (this.isTransitioning) return;
        
        this.isTransitioning = true;
        
        // Add dark mode classes
        document.documentElement.classList.add('dark-mode');
        document.documentElement.setAttribute('data-theme', 'dark'); // Backward compatibility
        document.body.classList.add('dark-mode');
        
        // Save preference
        localStorage.setItem(this.darkModeKey, 'dark');
        localStorage.setItem(this.themeKey, 'dark'); // Backward compatibility
        
        // Update toggle state
        this.updateToggleState(true);
        
        // Refresh dynamic content
        if (animate) {
            setTimeout(() => {
                this.refreshDynamicContent();
                this.isTransitioning = false;
            }, 300);
        } else {
            this.refreshDynamicContent();
            this.isTransitioning = false;
        }
    }

    disableDarkMode(animate = true) {
        if (this.isTransitioning) return;
        
        this.isTransitioning = true;
        
        // Remove dark mode classes
        document.documentElement.classList.remove('dark-mode');
        document.documentElement.setAttribute('data-theme', 'light'); // Backward compatibility
        document.body.classList.remove('dark-mode');
        
        // Save preference
        localStorage.setItem(this.darkModeKey, 'light');
        localStorage.setItem(this.themeKey, 'light'); // Backward compatibility
        
        // Update toggle state
        this.updateToggleState(false);
        
        // Refresh dynamic content
        if (animate) {
            setTimeout(() => {
                this.refreshDynamicContent();
                this.isTransitioning = false;
            }, 300);
        } else {
            this.refreshDynamicContent();
            this.isTransitioning = false;
        }
    }

    toggle() {
        if (this.isTransitioning) return;
        
        const isDark = document.documentElement.classList.contains('dark-mode');
        
        if (isDark) {
            this.disableDarkMode();
        } else {
            this.enableDarkMode();
        }
    }

    updateToggleState(isDark) {
        // Update all toggle buttons
        const toggles = document.querySelectorAll('[data-dark-mode-toggle]');
        toggles.forEach(toggle => {
            if (toggle.type === 'checkbox') {
                toggle.checked = isDark;
            }
            toggle.setAttribute('aria-checked', isDark);
            toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        });

        // Update icon buttons
        const iconButtons = document.querySelectorAll('.dark-mode-toggle-icon');
        iconButtons.forEach(btn => {
            const iconOff = btn.querySelector('.dark-mode-off');
            const iconOn = btn.querySelector('.dark-mode-on');
            
            if (iconOff && iconOn) {
                if (isDark) {
                    iconOff.style.display = 'none';
                    iconOn.style.display = 'inline-block';
                } else {
                    iconOff.style.display = 'inline-block';
                    iconOn.style.display = 'none';
                }
            }
        });
    }

    attachListeners() {
        // Attach to all toggle elements
        const toggles = document.querySelectorAll('[data-dark-mode-toggle]');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });
        });

        // Also listen for theme toggle buttons (backward compatibility)
        const themeToggles = document.querySelectorAll('#themeToggle, #themeToggleMobile');
        themeToggles.forEach(toggle => {
            if (!toggle.hasAttribute('data-dark-mode-toggle')) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggle();
                });
            }
        });
    }

    watchSystemTheme() {
        // Watch for system theme changes (only if no user preference is set)
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            // Only auto-switch if user hasn't set a preference
            const savedMode = localStorage.getItem(this.darkModeKey);
            if (!savedMode) {
                if (e.matches) {
                    this.enableDarkMode();
                } else {
                    this.disableDarkMode();
                }
            }
        });
    }

    refreshDynamicContent() {
        // Refresh Chart.js charts
        if (typeof Chart !== 'undefined') {
            this.refreshCharts();
        }

        // Refresh DataTables
        if (typeof $.fn.DataTable !== 'undefined') {
            this.refreshDataTables();
        }

        // Refresh Select2
        if (typeof $.fn.select2 !== 'undefined') {
            this.refreshSelect2();
        }

        // Refresh any custom components
        this.refreshCustomComponents();
    }

    refreshCharts() {
        const isDark = document.documentElement.classList.contains('dark-mode');
        const gridColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--chart-grid').trim() || (isDark ? '#404040' : '#e9ecef');
        const textColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--chart-text').trim() || (isDark ? '#adb5bd' : '#6c757d');

        Chart.helpers.each(Chart.instances, (instance) => {
            if (instance.options.scales) {
                // Update grid colors
                if (instance.options.scales.x) {
                    instance.options.scales.x.grid = instance.options.scales.x.grid || {};
                    instance.options.scales.x.grid.color = gridColor;
                    instance.options.scales.x.ticks = instance.options.scales.x.ticks || {};
                    instance.options.scales.x.ticks.color = textColor;
                }
                if (instance.options.scales.y) {
                    instance.options.scales.y.grid = instance.options.scales.y.grid || {};
                    instance.options.scales.y.grid.color = gridColor;
                    instance.options.scales.y.ticks = instance.options.scales.y.ticks || {};
                    instance.options.scales.y.ticks.color = textColor;
                }
            }
            
            // Update legend colors
            if (instance.options.plugins && instance.options.plugins.legend) {
                instance.options.plugins.legend.labels = instance.options.plugins.legend.labels || {};
                instance.options.plugins.legend.labels.color = textColor;
            }

            instance.update('none'); // Update without animation
        });
    }

    refreshDataTables() {
        $('.dataTable').each(function() {
            const table = $(this).DataTable();
            if (table) {
                // Trigger redraw to apply new styles
                table.draw(false);
            }
        });
    }

    refreshSelect2() {
        $('.select2-container').each(function() {
            const $select = $(this).siblings('select');
            if ($select.length) {
                // Reinitialize Select2 with new theme
                $select.select2('destroy');
                $select.select2();
            }
        });
    }

    refreshCustomComponents() {
        // Dispatch custom event for other components to listen
        const event = new CustomEvent('darkModeChanged', {
            detail: {
                isDark: document.documentElement.classList.contains('dark-mode')
            }
        });
        window.dispatchEvent(event);
    }

    getChartColors() {
        const isDark = document.documentElement.classList.contains('dark-mode');
        return {
            gridColor: getComputedStyle(document.documentElement)
                .getPropertyValue('--chart-grid').trim() || (isDark ? '#404040' : '#e9ecef'),
            textColor: getComputedStyle(document.documentElement)
                .getPropertyValue('--chart-text').trim() || (isDark ? '#adb5bd' : '#6c757d'),
            backgroundColor: isDark ? '#2d2d2d' : '#ffffff'
        };
    }

    isDarkMode() {
        return document.documentElement.classList.contains('dark-mode');
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.darkModeManager = new DarkModeManager();
    });
} else {
    window.darkModeManager = new DarkModeManager();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkModeManager;
}

