// ===== SKILLSWAP PLATFORM - MAIN JAVASCRIPT =====

class SkillSwapApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupAnimations();
    }

    setupEventListeners() {
        // Form submissions
        document.addEventListener('submit', (e) => this.handleFormSubmit(e));
        
        // Button clicks
        document.addEventListener('click', (e) => this.handleButtonClick(e));
        
        // Input focus effects
        document.addEventListener('focusin', (e) => this.handleInputFocus(e));
        document.addEventListener('focusout', (e) => this.handleInputBlur(e));
        
        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e));
        }
    }

    initializeComponents() {
        this.initializeTooltips();
        this.initializeModals();
        this.initializeNotifications();
        this.initializeRatingSystem();
    }

    setupAnimations() {
        // Add fade-in animation to cards
        const cards = document.querySelectorAll('.card, .user-card, .swap-request');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in');
        });

        // Add slide-in animation to forms
        const forms = document.querySelectorAll('.form-container');
        forms.forEach(form => {
            form.classList.add('slide-in');
        });
    }

    // ===== FORM HANDLING =====
    handleFormSubmit(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            this.showLoadingState(submitBtn);
        }

        // Add form validation
        if (!this.validateForm(form)) {
            e.preventDefault();
            this.hideLoadingState(submitBtn);
            return false;
        }
    }

    validateForm(form) {
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(input);
            }

            // Email validation
            if (input.type === 'email' && input.value) {
                if (!this.isValidEmail(input.value)) {
                    this.showFieldError(input, 'Please enter a valid email address');
                    isValid = false;
                }
            }

            // Password validation
            if (input.type === 'password' && input.value) {
                if (input.value.length < 6) {
                    this.showFieldError(input, 'Password must be at least 6 characters');
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    showFieldError(input, message) {
        const errorDiv = input.parentNode.querySelector('.field-error') || 
                        this.createErrorElement(input);
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        input.classList.add('error');
    }

    clearFieldError(input) {
        const errorDiv = input.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
        input.classList.remove('error');
    }

    createErrorElement(input) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = 'var(--danger-color)';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        input.parentNode.appendChild(errorDiv);
        return errorDiv;
    }

    // ===== BUTTON HANDLING =====
    handleButtonClick(e) {
        const button = e.target.closest('button');
        if (!button) return;

        // Handle different button types
        if (button.classList.contains('btn-accept')) {
            this.handleAcceptSwap(button);
        } else if (button.classList.contains('btn-reject')) {
            this.handleRejectSwap(button);
        } else if (button.classList.contains('btn-cancel')) {
            this.handleCancelSwap(button);
        } else if (button.classList.contains('btn-delete')) {
            this.handleDeleteItem(button);
        }
    }

    async handleAcceptSwap(button) {
        const swapId = button.dataset.swapId;
        if (!swapId) return;

        try {
            this.showLoadingState(button);
            const response = await this.makeApiCall('/api/swaps.php?action=respond', {
                swap_id: swapId,
                response: 'accept'
            });

            if (response.success) {
                this.showNotification('Swap request accepted!', 'success');
                this.updateSwapStatus(swapId, 'accepted');
            } else {
                this.showNotification(response.message || 'Failed to accept swap', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        } finally {
            this.hideLoadingState(button);
        }
    }

    async handleRejectSwap(button) {
        const swapId = button.dataset.swapId;
        if (!swapId) return;

        if (!confirm('Are you sure you want to reject this swap request?')) {
            return;
        }

        try {
            this.showLoadingState(button);
            const response = await this.makeApiCall('/api/swaps.php?action=respond', {
                swap_id: swapId,
                response: 'reject'
            });

            if (response.success) {
                this.showNotification('Swap request rejected', 'success');
                this.updateSwapStatus(swapId, 'rejected');
            } else {
                this.showNotification(response.message || 'Failed to reject swap', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        } finally {
            this.hideLoadingState(button);
        }
    }

    async handleCancelSwap(button) {
        const swapId = button.dataset.swapId;
        if (!swapId) return;

        if (!confirm('Are you sure you want to cancel this swap request?')) {
            return;
        }

        try {
            this.showLoadingState(button);
            const response = await this.makeApiCall('/api/swaps.php?action=cancel', {
                swap_id: swapId
            });

            if (response.success) {
                this.showNotification('Swap request cancelled', 'success');
                this.removeSwapRequest(swapId);
            } else {
                this.showNotification(response.message || 'Failed to cancel swap', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        } finally {
            this.hideLoadingState(button);
        }
    }

    // ===== API CALLS =====
    async makeApiCall(url, data = null) {
        const options = {
            method: data ? 'POST' : 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        return await response.json();
    }

    // ===== UI UPDATES =====
    updateSwapStatus(swapId, status) {
        const swapElement = document.querySelector(`[data-swap-id="${swapId}"]`);
        if (!swapElement) return;

        const statusElement = swapElement.querySelector('.swap-status');
        if (statusElement) {
            statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusElement.className = `swap-status ${status}`;
        }

        // Update buttons
        const actionButtons = swapElement.querySelector('.swap-actions');
        if (actionButtons) {
            if (status === 'accepted' || status === 'rejected') {
                actionButtons.innerHTML = '<span class="text-muted">Request ' + status + '</span>';
            }
        }
    }

    removeSwapRequest(swapId) {
        const swapElement = document.querySelector(`[data-swap-id="${swapId}"]`);
        if (swapElement) {
            swapElement.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                swapElement.remove();
            }, 300);
        }
    }

    // ===== LOADING STATES =====
    showLoadingState(button) {
        if (!button) return;
        
        const originalText = button.textContent;
        button.dataset.originalText = originalText;
        button.innerHTML = '<span class="loading"></span> Loading...';
        button.disabled = true;
    }

    hideLoadingState(button) {
        if (!button) return;
        
        const originalText = button.dataset.originalText;
        if (originalText) {
            button.textContent = originalText;
        }
        button.disabled = false;
    }

    // ===== NOTIFICATIONS =====
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification-toast`;
        notification.textContent = message;
        
        // Add styles for toast notification
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.style.animation = 'slideInRight 0.3s ease-out';
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }

    initializeNotifications() {
        // Check for unread notifications count
        this.updateNotificationCount();
    }

    async updateNotificationCount() {
        try {
            const response = await this.makeApiCall('/api/notifications.php?action=unread_count');
            if (response.success) {
                const count = response.count;
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'block' : 'none';
                }
            }
        } catch (error) {
            console.error('Failed to update notification count:', error);
        }
    }

    // ===== RATING SYSTEM =====
    initializeRatingSystem() {
        const ratingContainers = document.querySelectorAll('.rating-input');
        ratingContainers.forEach(container => {
            const stars = container.querySelectorAll('.star');
            const input = container.querySelector('input[type="hidden"]');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    this.setRating(container, index + 1);
                });
                
                star.addEventListener('mouseenter', () => {
                    this.highlightStars(container, index + 1);
                });
            });
            
            container.addEventListener('mouseleave', () => {
                this.highlightStars(container, parseInt(input.value) || 0);
            });
        });
    }

    setRating(container, rating) {
        const input = container.querySelector('input[type="hidden"]');
        const stars = container.querySelectorAll('.star');
        
        input.value = rating;
        this.highlightStars(container, rating);
    }

    highlightStars(container, rating) {
        const stars = container.querySelectorAll('.star');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    // ===== SEARCH FUNCTIONALITY =====
    handleSearch(e) {
        const query = e.target.value;
        const searchResults = document.querySelector('.search-results');
        
        if (query.length < 2) {
            if (searchResults) {
                searchResults.style.display = 'none';
            }
            return;
        }

        // Debounce search
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, 300);
    }

    async performSearch(query) {
        try {
            const response = await this.makeApiCall(`/api/users.php?action=search&q=${encodeURIComponent(query)}`);
            this.displaySearchResults(response.users || []);
        } catch (error) {
            console.error('Search failed:', error);
        }
    }

    displaySearchResults(users) {
        const searchResults = document.querySelector('.search-results');
        if (!searchResults) return;

        if (users.length === 0) {
            searchResults.innerHTML = '<p class="text-center text-muted">No users found</p>';
        } else {
            const userCards = users.map(user => this.createUserCard(user)).join('');
            searchResults.innerHTML = userCards;
        }
        
        searchResults.style.display = 'block';
    }

    createUserCard(user) {
        return `
            <div class="user-card">
                <div class="user-card-header">
                    <img src="${user.profile_photo || 'assets/images/default-avatar.png'}" alt="${user.name}" class="user-avatar">
                    <div class="user-name">${user.name}</div>
                    <div class="user-location">${user.location || 'Location not specified'}</div>
                </div>
                <div class="user-card-body">
                    <div class="skills-section">
                        <div class="skills-title">Skills Offered:</div>
                        <div class="skills-list">
                            ${this.formatSkills(user.skills_offered)}
                        </div>
                    </div>
                    <div class="skills-section">
                        <div class="skills-title">Skills Wanted:</div>
                        <div class="skills-list">
                            ${this.formatSkills(user.skills_wanted)}
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="profile.php?id=${user.id}" class="btn btn-primary btn-sm">View Profile</a>
                        <button class="btn btn-outline btn-sm" onclick="app.sendSwapRequest(${user.id})">Send Request</button>
                    </div>
                </div>
            </div>
        `;
    }

    formatSkills(skills) {
        if (!skills) return '<span class="text-muted">No skills listed</span>';
        
        const skillArray = skills.split(',').map(skill => skill.trim());
        return skillArray.map(skill => 
            `<span class="skill-tag">${skill}</span>`
        ).join('');
    }

    // ===== MODAL SYSTEM =====
    initializeModals() {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                this.openModal(modalId);
            });
        });

        // Close modal on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target);
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    // ===== TOOLTIPS =====
    initializeTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });
            
            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(element) {
        const tooltipText = element.dataset.tooltip;
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = tooltipText;
        
        // Position tooltip
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = rect.top - 30 + 'px';
        tooltip.style.left = rect.left + (rect.width / 2) - 50 + 'px';
        tooltip.style.zIndex = '1000';
        
        document.body.appendChild(tooltip);
    }

    hideTooltip() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // ===== INPUT FOCUS EFFECTS =====
    handleInputFocus(e) {
        if (e.target.classList.contains('form-control')) {
            e.target.parentNode.classList.add('focused');
        }
    }

    handleInputBlur(e) {
        if (e.target.classList.contains('form-control')) {
            e.target.parentNode.classList.remove('focused');
        }
    }

    // ===== UTILITY METHODS =====
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
        if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + 'd ago';
        
        return this.formatDate(dateString);
    }

    // ===== PUBLIC METHODS =====
    sendSwapRequest(userId) {
        // This would typically open a modal or redirect to a form
        window.location.href = `send_request.php?user_id=${userId}`;
    }

    refreshPage() {
        window.location.reload();
    }

    scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    window.app = new SkillSwapApp();
});

// ===== ADDITIONAL CSS ANIMATIONS =====
const additionalStyles = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: scale(1);
        }
        to {
            opacity: 0;
            transform: scale(0.95);
        }
    }

    .form-group.focused .form-label {
        color: var(--primary-color);
    }

    .form-control.error {
        border-color: var(--danger-color);
        box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
    }

    .star.active {
        color: #ffd700;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .tooltip {
        background: var(--dark-gray);
        color: var(--white);
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-sm);
        font-size: 0.875rem;
        white-space: nowrap;
        pointer-events: none;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--danger-color);
        color: var(--white);
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet); 