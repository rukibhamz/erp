/**
 * Module Customization JavaScript
 * Handles all interactions for module customization page
 */

// Global variables
let currentEditingModule = null;

// Get base URL and CSRF token from data attributes
function getBaseUrl() {
    // Try to get from data attribute first
    const baseUrlElement = document.querySelector('[data-base-url]');
    if (baseUrlElement) {
        let url = baseUrlElement.getAttribute('data-base-url');
        // Ensure trailing slash
        if (!url.endsWith('/')) {
            url += '/';
        }
        return url;
    }
    
    // Fallback: construct from current location
    const pathParts = window.location.pathname.split('/').filter(p => p);
    // Remove the last part (module_customization)
    if (pathParts.length > 0 && pathParts[pathParts.length - 1] === 'module_customization') {
        pathParts.pop();
    }
    const basePath = pathParts.length > 0 ? '/' + pathParts.join('/') + '/' : '/';
    return window.location.origin + basePath;
}

function getCsrfToken() {
    const baseUrlElement = document.querySelector('[data-csrf-token]');
    if (baseUrlElement) {
        return baseUrlElement.getAttribute('data-csrf-token');
    }
    return '';
}

const BASE_URL = getBaseUrl();
const CSRF_TOKEN = getCsrfToken();

// Debug: Log BASE_URL for troubleshooting
console.log('Module Customization - BASE_URL:', BASE_URL);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Module Customization - Initializing...');
    initializeDragAndDrop();
    initializeEditButtons();
    initializeResetButtons();
    initializeIconPreview();
    console.log('Module Customization - Initialized');
});

/**
 * Initialize drag-and-drop functionality for reordering modules
 */
function initializeDragAndDrop() {
    const moduleList = document.getElementById('module-list');
    if (!moduleList) return;

    let draggedElement = null;

    // Add drag event listeners to all module items
    const moduleItems = moduleList.querySelectorAll('.module-item');
    moduleItems.forEach(item => {
        item.setAttribute('draggable', 'true');

        item.addEventListener('dragstart', function(e) {
            draggedElement = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            if (draggedElement) {
                saveModuleOrder();
            }
            draggedElement = null;
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(moduleList, e.clientY);
            if (afterElement == null) {
                moduleList.appendChild(draggedElement);
            } else {
                moduleList.insertBefore(draggedElement, afterElement);
            }
        });
    });
}

/**
 * Get the element that should come after the dragged element
 */
function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.module-item:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

/**
 * Save the new module order to the server
 */
function saveModuleOrder() {
    const moduleItems = document.querySelectorAll('.module-item');
    const orders = {};

    moduleItems.forEach((item, index) => {
        const moduleCode = item.dataset.moduleCode;
        orders[moduleCode] = index + 1;
    });

    fetch(BASE_URL + 'module_customization/updateOrder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({ orders: orders, csrf_token: CSRF_TOKEN })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Module order updated successfully', 'success');
        } else {
            showMessage('Failed to update module order: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while updating module order', 'danger');
    });
}

/**
 * Initialize edit buttons
 */
function initializeEditButtons() {
    const editButtons = document.querySelectorAll('.edit-module');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const moduleCode = this.dataset.moduleCode;
            const defaultLabel = this.dataset.defaultLabel;
            const customLabel = this.dataset.customLabel;
            const iconClass = this.dataset.iconClass;

            openEditModal(moduleCode, defaultLabel, customLabel, iconClass);
        });
    });
}

/**
 * Open the edit modal
 */
function openEditModal(moduleCode, defaultLabel, customLabel, iconClass) {
    currentEditingModule = moduleCode;

    const modal = document.getElementById('edit-module-modal');
    if (!modal) {
        console.error('Edit modal not found');
        return;
    }

    const codeInput = document.getElementById('edit-module-code');
    const codeDisplay = document.getElementById('edit-module-code-display');
    const defaultLabelInput = document.getElementById('edit-default-label');
    const customLabelInput = document.getElementById('edit-custom-label');
    const iconClassInput = document.getElementById('edit-icon-class');
    const iconPreview = document.getElementById('edit-icon-preview');

    if (codeInput) codeInput.value = moduleCode || '';
    if (codeDisplay) codeDisplay.value = moduleCode || '';
    if (defaultLabelInput) defaultLabelInput.value = defaultLabel || '';
    if (customLabelInput) customLabelInput.value = customLabel || defaultLabel || '';
    if (iconClassInput) iconClassInput.value = iconClass || '';
    if (iconPreview) {
        // Ensure icon has 'bi' prefix
        let icon = iconClass || 'bi-circle';
        if (icon.indexOf('bi-') === -1 && icon.indexOf('bi ') === -1) {
            icon = icon.replace('icon-', 'bi-');
        }
        iconPreview.className = 'bi ' + icon;
    }

    // Show modal - remove d-none and set display to flex
    modal.classList.remove('d-none');
    modal.style.display = 'flex';
}

/**
 * Close the edit modal
 */
function closeEditModal() {
    const modal = document.getElementById('edit-module-modal');
    if (modal) {
        modal.classList.add('d-none');
        modal.style.display = 'none';
    }
    currentEditingModule = null;
}

/**
 * Save module edits
 */
function saveModuleEdit() {
    const form = document.getElementById('edit-module-form');
    const formData = new FormData(form);

    const moduleCode = formData.get('module_code');
    const customLabel = formData.get('custom_label');
    const iconClass = formData.get('icon_class');

    if (!customLabel || customLabel.trim() === '') {
        showMessage('Custom label cannot be empty', 'danger');
        return;
    }

    // Update label
    const url = BASE_URL + 'module_customization/updateLabel';
    console.log('Update Label - URL:', url, 'Module:', moduleCode, 'Label:', customLabel);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `module_code=${encodeURIComponent(moduleCode)}&custom_label=${encodeURIComponent(customLabel)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
    })
    .then(response => {
        console.log('Update Label - Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update icon if provided (optional)
            if (iconClass && iconClass.trim() !== '') {
                return fetch(BASE_URL + 'module_customization/updateIcon', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `module_code=${encodeURIComponent(moduleCode)}&icon_class=${encodeURIComponent(iconClass.trim())}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
                })
                .then(response => response.json())
                .then(iconData => {
                    if (!iconData.success) {
                        console.warn('Icon update failed:', iconData.error);
                    }
                    return { success: true };
                })
                .catch(error => {
                    console.warn('Icon update error:', error);
                    return { success: true }; // Don't fail the whole operation if icon update fails
                });
            }
            return Promise.resolve({ success: true });
        } else {
            throw new Error(data.error || 'Failed to update module label');
        }
    })
    .then(data => {
        if (data && data.success) {
            showMessage('Module updated successfully', 'success');
            closeEditModal();
            // Reload page to show changes
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error('Failed to update module');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred: ' + error.message, 'danger');
    });
}

/**
 * Initialize reset buttons
 */
function initializeResetButtons() {
    const resetButtons = document.querySelectorAll('.reset-module');
    
    resetButtons.forEach(button => {
        button.addEventListener('click', function() {
            const moduleCode = this.dataset.moduleCode;
            
            if (confirm('Are you sure you want to reset this module label to default?')) {
                fetch(BASE_URL + 'module_customization/resetLabel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `module_code=${encodeURIComponent(moduleCode)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Module label reset to default', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showMessage('Failed to reset: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred', 'danger');
                });
            }
        });
    });
}

/**
 * Initialize icon preview
 */
function initializeIconPreview() {
    const iconInput = document.getElementById('edit-icon-class');
    if (iconInput) {
        iconInput.addEventListener('input', function() {
            const iconClass = this.value.trim();
            const preview = document.getElementById('edit-icon-preview');
            if (preview) {
                preview.className = iconClass || 'bi bi-circle';
            }
        });
    }
}

/**
 * Show message to user
 */
function showMessage(message, type) {
    const container = document.getElementById('message-container');
    if (!container) return;

    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;

    container.appendChild(messageDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

