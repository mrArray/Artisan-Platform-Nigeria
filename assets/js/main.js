/**
 * Artisan Platform - Main JavaScript
 * 
 * Client-side functionality for forms, validation, and interactions
 */

// ==================== DOM Ready ==================== 

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeFormValidation();
});

// ==================== Event Listeners ==================== 

function initializeEventListeners() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navItems = document.querySelector('.nav-items');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navItems.classList.toggle('active');
        });
    }

    // Dropdown menu toggle on mobile
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const dropdownMenu = this.nextElementSibling;
                dropdownMenu.classList.toggle('active');
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-item-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });

    // Dismiss alerts
    document.querySelectorAll('.alert .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });
}

// ==================== Form Validation ==================== 

function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // Real-time validation
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });

        input.addEventListener('change', function() {
            validateField(this);
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';

    // Check if field is required and empty
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    // Email validation
    else if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    // Phone validation
    else if (field.type === 'tel' && value) {
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!phoneRegex.test(value) || value.replace(/\D/g, '').length < 10) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number';
        }
    }
    // Number validation
    else if (field.type === 'number') {
        if (field.hasAttribute('min')) {
            const min = parseFloat(field.getAttribute('min'));
            if (value && parseFloat(value) < min) {
                isValid = false;
                errorMessage = `Value must be at least ${min}`;
            }
        }
        if (field.hasAttribute('max')) {
            const max = parseFloat(field.getAttribute('max'));
            if (value && parseFloat(value) > max) {
                isValid = false;
                errorMessage = `Value must not exceed ${max}`;
            }
        }
    }
    // Password validation
    else if (field.type === 'password' && field.hasAttribute('minlength')) {
        const minLength = parseInt(field.getAttribute('minlength'));
        if (value && value.length < minLength) {
            isValid = false;
            errorMessage = `Password must be at least ${minLength} characters`;
        }
    }
    // Confirm password validation
    else if (field.name === 'confirm_password') {
        const passwordField = field.form.querySelector('input[name="password"]');
        if (passwordField && value !== passwordField.value) {
            isValid = false;
            errorMessage = 'Passwords do not match';
        }
    }

    // Display error message
    displayFieldError(field, isValid, errorMessage);

    return isValid;
}

function displayFieldError(field, isValid, errorMessage) {
    // Remove existing error message
    const existingError = field.parentElement.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    // Add error styling and message
    if (!isValid) {
        field.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = errorMessage;
        field.parentElement.appendChild(errorDiv);
    } else {
        field.classList.remove('error');
    }
}

// ==================== Utility Functions ==================== 

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-NG', options);
}

/**
 * Show loading spinner
 */
function showLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    if (element) {
        element.innerHTML = '<div class="spinner"></div>';
    }
}

/**
 * Hide loading spinner
 */
function hideLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    if (element) {
        const spinner = element.querySelector('.spinner');
        if (spinner) {
            spinner.remove();
        }
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <p>${message}</p>
        <button class="close" type="button">&times;</button>
    `;

    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);

        // Manual dismiss
        alertDiv.querySelector('.close').addEventListener('click', function() {
            alertDiv.remove();
        });
    }
}

/**
 * Confirm action
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Debounce function for search
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Search with debounce
 */
const debouncedSearch = debounce(function(query) {
    // Search functionality would go here
    console.log('Searching for:', query);
}, 300);

// ==================== Table Utilities ==================== 

/**
 * Sort table by column
 */
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Try to parse as numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }

        // String comparison
        return aValue.localeCompare(bValue);
    });

    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Filter table rows
 */
function filterTable(tableId, searchValue) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchValue.toLowerCase();

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchLower) ? '' : 'none';
    });
}

// ==================== File Upload ==================== 

/**
 * Validate file upload
 */
function validateFileUpload(input, maxSizeMB = 5, allowedTypes = []) {
    const file = input.files[0];
    if (!file) return true;

    // Check file size
    const maxSizeBytes = maxSizeMB * 1024 * 1024;
    if (file.size > maxSizeBytes) {
        showNotification(`File size must not exceed ${maxSizeMB}MB`, 'error');
        input.value = '';
        return false;
    }

    // Check file type
    if (allowedTypes.length > 0) {
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(fileExtension)) {
            showNotification(`File type not allowed. Allowed types: ${allowedTypes.join(', ')}`, 'error');
            input.value = '';
            return false;
        }
    }

    return true;
}

/**
 * Preview image before upload
 */
function previewImage(input, previewElementId) {
    const file = input.files[0];
    const preview = document.getElementById(previewElementId);

    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// ==================== API Utilities ==================== 

/**
 * Fetch with error handling
 */
async function fetchWithErrorHandling(url, options = {}) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        throw error;
    }
}

// ==================== Modal Functions ==================== 

function showConfirmModal(message, title = 'Confirm Action') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirm');
        const cancelBtn = document.getElementById('modalCancel');

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        modal.classList.add('active');

        const handleConfirm = () => {
            modal.classList.remove('active');
            cleanup();
            resolve(true);
        };

        const handleCancel = () => {
            modal.classList.remove('active');
            cleanup();
            resolve(false);
        };

        const handleBackdrop = (e) => {
            if (e.target === modal) {
                handleCancel();
            }
        };

        const cleanup = () => {
            confirmBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', handleCancel);
            modal.removeEventListener('click', handleBackdrop);
        };

        confirmBtn.addEventListener('click', handleConfirm);
        cancelBtn.addEventListener('click', handleCancel);
        modal.addEventListener('click', handleBackdrop);
    });
}

// Initialize confirm modals for all confirm links
function initializeConfirmModals() {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', async function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm');
            const title = this.getAttribute('data-confirm-title') || 'Confirm Action';
            
            const confirmed = await showConfirmModal(message, title);
            
            if (confirmed) {
                if (this.tagName === 'A') {
                    window.location.href = this.href;
                } else if (this.tagName === 'BUTTON' || this.tagName === 'INPUT') {
                    // Submit the form
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            }
        });
    });
}

// Call on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initializeConfirmModals();
});

// ==================== Export Functions ==================== 

window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.showNotification = showNotification;
window.confirmAction = confirmAction;
window.sortTable = sortTable;
window.filterTable = filterTable;
window.validateFileUpload = validateFileUpload;
window.previewImage = previewImage;
window.fetchWithErrorHandling = fetchWithErrorHandling;
window.showConfirmModal = showConfirmModal;
