/**
 * UELS (Universal Entity Limit System) Frontend Integration
 * Handles modal display, data fetching, and user interactions for entity limits
 */

class UELSManager {
    constructor() {
        this.baseUrl = window.location.origin;
        this.currentEntityType = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    /**
     * Open UELS modal for specific entity type
     */
    async openModal(entityType) {
        this.currentEntityType = entityType;
        
        const modal = document.getElementById('uelsModal');
        if (!modal) {
            console.error('UELS Modal not found');
            return;
        }

        // Show modal
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
        }, 20);

        // Update title
        const title = document.getElementById('uelsModalTitle');
        if (title) {
            const entityName = this.getEntityDisplayName(entityType);
            title.innerHTML = `<i class="fas fa-shield-alt mr-2 text-purple-500"></i>${window.UELSTranslations.modal.title} - ${entityName}`;
        }

        // Show loading state
        this.showLoading();
        
        // Load data
        try {
            await this.loadEntityData(entityType);
        } catch (error) {
            console.error('Failed to load UELS data:', error);
            this.showError();
        }
    }

    /**
     * Close UELS modal
     */
    closeModal() {
        const modal = document.getElementById('uelsModal');
        if (modal) {
            setTimeout(() => {
                modal.classList.remove('opacity-100');
            }, 20);
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 500);
        }
        this.currentEntityType = null;
    }

    /**
     * Show loading state
     */
    showLoading() {
        document.getElementById('uelsModalLoading')?.classList.remove('hidden');
        document.getElementById('uelsModalContent')?.classList.add('hidden');
        document.getElementById('uelsModalError')?.classList.add('hidden');
    }

    /**
     * Show content state
     */
    showContent() {
        document.getElementById('uelsModalLoading')?.classList.add('hidden');
        document.getElementById('uelsModalContent')?.classList.remove('hidden');
        document.getElementById('uelsModalError')?.classList.add('hidden');
    }

    /**
     * Show error state
     */
    showError() {
        document.getElementById('uelsModalLoading')?.classList.add('hidden');
        document.getElementById('uelsModalContent')?.classList.add('hidden');
        document.getElementById('uelsModalError')?.classList.remove('hidden');
    }

    /**
     * Load entity data from API
     */
    async loadEntityData(entityType) {
        try {
            const response = await fetch(`${this.baseUrl}/api/uels/${entityType}/data`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            // Transform data for modal display
            const modalData = {
                overview: {
                    currentUsage: data.current_usage || 0,
                    limit: data.limit || -1,
                    status: data.status || 'unknown',
                    bestStatsPeriod: data.best_stats_period || null
                },
                currentUsage: data.currentUsage || {
                    today: 0,
                    thisWeek: 0,
                    thisMonth: 0,
                    total: 0
                },
                recentActivity: data.recentActivity || []
            };
            
            this.renderEntityData(modalData);
            this.showContent();

        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    /**
     * Render entity data in modal
     */
    renderEntityData(data) {
        // Render overview
        this.renderOverview(data.overview);
        
        // Render current usage
        this.renderCurrentUsage(data.currentUsage);
        
        // Render recent activity
        this.renderRecentActivity(data.recentActivity);
    }

    /**
     * Render entity overview
     */
    renderOverview(overview) {
        const container = document.getElementById('uelsEntityOverview');
        if (!container) return;

        const isOverLimit = overview.currentUsage >= overview.limit && overview.limit > 0;
        const percentage = overview.limit > 0 ? Math.min(100, (overview.currentUsage / overview.limit) * 100) : 0;
        
        const progressClass = isOverLimit ? 'bg-red-500' : 
                            percentage >= 80 ? 'bg-yellow-500' : 'bg-green-500';

        container.innerHTML = `
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-300">${window.UELSTranslations.modal.current_usage}</div>
                <div class="text-xl font-semibold ${isOverLimit ? 'text-red-600' : 'text-gray-900 dark:text-white'}">
                    ${overview.currentUsage}
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-300">${window.UELSTranslations.modal.limit}</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white">
                    ${overview.limit === -1 ? window.UELSTranslations.widget.unlimited : overview.limit} ${overview.bestStatsPeriod !== null ? '/ ' + overview.bestStatsPeriod : ''}
                </div>
            </div>
            ${overview.limit > 0 ? `
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 md:col-span-2">
                <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">${window.UELSTranslations.modal.usage_percentage}</div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="${progressClass} h-3 rounded-full transition-all duration-300" style="width: ${Math.min(100, percentage)}%"></div>
                </div>
                <div class="text-right mt-1">
                    <span class="text-sm font-medium ${isOverLimit ? 'text-red-600' : 'text-gray-700 dark:text-gray-300'}">${percentage.toFixed(1)}%</span>
                </div>
            </div>` : ''}
        `;
    }

    /**
     * Render current usage details
     */
    renderCurrentUsage(usage) {
        const container = document.getElementById('uelsCurrentUsage');
        if (!container) return;

        container.innerHTML = `
            <div class="p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-blue-600">${usage.today}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">${window.UELSTranslations.modal.today}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600">${usage.thisWeek}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">${window.UELSTranslations.modal.this_week}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-purple-600">${usage.thisMonth}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">${window.UELSTranslations.modal.this_month}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-600">${usage.total}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">${window.UELSTranslations.modal.total}</div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render recent activity
     */
    renderRecentActivity(activities) {
        const container = document.getElementById('uelsRecentActivity');
        if (!container) return;

        if (!activities || activities.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p>${window.UELSTranslations.modal.no_activity}</p>
                </div>
            `;
            return;
        }

        const activityHtml = activities.map(activity => `
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-plus text-purple-600 dark:text-purple-400 text-xs"></i>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            ${window.UELSTranslations.modal.entity_created}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            ${this.formatDate(activity.created_at)}
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    +1
                </div>
            </div>
        `).join('');

        container.innerHTML = activityHtml;
    }

    /**
     * Refresh UELS data
     */
    async refreshData() {
        if (!this.currentEntityType) return;
        
        this.showLoading();
        try {
            await this.loadEntityData(this.currentEntityType);
        } catch (error) {
            console.error('Failed to refresh UELS data:', error);
            this.showError();
        }
    }

    /**
     * Retry loading data
     */
    async retryModal() {
        if (!this.currentEntityType) return;
        await this.refreshData();
    }

    /**
     * Get display name for entity type
     */
    getEntityDisplayName(entityType) {
        const names = {
            'client': window.UELSTranslations?.entities?.clients || 'Clients',
            'supplier': window.UELSTranslations?.entities?.suppliers || 'Suppliers', 
            'product': window.UELSTranslations?.entities?.products || 'Products',
            'invoice': window.UELSTranslations?.entities?.invoices || 'Invoices'
        };
        return names[entityType] || entityType;
    }

    /**
     * Format date for display
     */
    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } catch (error) {
            return dateString;
        }
    }    /**
     * Load widget data and update display
     */
    async loadWidgetData(entityType) {
        try {
            const response = await fetch(`${this.baseUrl}/api/uels/${entityType}/data`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.updateWidget(entityType, data);

        } catch (error) {
            console.error('Failed to load widget data:', error);
        }
    }

    /**
     * Load data for all UELS widgets on the current page
     */
    async loadAllWidgets() {
        const widgets = document.querySelectorAll('[data-uels-widget]');
        const promises = [];

        widgets.forEach(widget => {
            const entityType = widget.getAttribute('data-entity-type');
            if (entityType) {
                promises.push(this.loadWidgetData(entityType));
            }
        });

        // Wait for all widget updates to complete
        try {
            await Promise.all(promises);
            console.log(`Loaded data for ${promises.length} UELS widgets`);
        } catch (error) {
            console.error('Error loading widget data:', error);
        }
    }

    /**
     * Update widget display with fresh data
     */
    updateWidget(entityType, data) {
        // Find the specific widget for this entity type
        const widget = document.querySelector(`[data-uels-widget="${entityType}"]`);
        if (!widget) {
            console.warn(`UELS widget for entity type "${entityType}" not found`);
            return;
        }

        // Find widget elements within the specific widget
        const usageElement = widget.querySelector('[data-uels-usage]');
        const progressElement = widget.querySelector('[data-uels-progress]');
        const progressContainer = widget.querySelector('[data-uels-progress-container]');
        const percentageElement = widget.querySelector('[data-uels-percentage]');
        const alertsContainer = widget.querySelector('[data-uels-alerts]');
        const iconElement = widget.querySelector('[data-uels-icon]');
        const periodElement = widget.querySelector('[data-uels-period]');
        const titleElement = widget.querySelector('[data-uels-title]');

        if (usageElement && data) {
            const current = data.current_usage || 0;
            const limit = data.limit;
            const percentage = data.percentage_used || 0;
            const isOverLimit = data.is_at_limit || false;
            const period = data.best_stats_period || null;
            const isNearLimit = percentage >= 80 && limit !== null && limit > 0;
            const status = data.status || 'unknown';

            // Update title
            if (titleElement) {
                const entityName = this.getEntityDisplayName(entityType);
                titleElement.innerHTML = `${window.UELSTranslations.widget.title} - ${String(entityType[0]).toUpperCase()}${String(entityType).slice(1)} / ${String(period[0]).toUpperCase()}${String(period).slice(1)}`;
            }
            
            // Update usage text and remove loading spinner
            if (limit !== null && limit > 0) {
                usageElement.innerHTML = `${current} / ${limit} ${period ? `${String(period[0]).toUpperCase()}${String(period).slice(1)}` : ''}`;
            } else if (limit === -1 || status === 'unlimited') {
                usageElement.innerHTML = `${current} / ${window.UELSTranslations.widget.unlimited || 'Unlimited'}`;
            } else if (status === 'no_permission') {
                usageElement.innerHTML = `${window.UELSTranslations.status.no_limit || 'No permission'}`;
            } else {
                usageElement.innerHTML = `${current} / ${window.UELSTranslations.widget.unlimited || 'Unlimited'}`;
            }

            // Update progress bar
            if (progressElement && limit !== null && limit > 0) {
                progressElement.style.width = `${Math.min(100, percentage)}%`;
                
                // Update progress bar color
                progressElement.className = progressElement.className.replace(/bg-(green|yellow|red)-\d+/, '');
                if (isOverLimit) {
                    progressElement.classList.add('bg-red-500');
                } else if (isNearLimit) {
                    progressElement.classList.add('bg-yellow-500');
                } else {
                    progressElement.classList.add('bg-green-500');
                }
            }

            // Update percentage display
            if (percentageElement && limit !== null && limit > 0) {
                percentageElement.textContent = `${percentage.toFixed(1)}%`;
                
                // Update percentage color
                percentageElement.className = percentageElement.className.replace(/text-(green|yellow|red)-\d+/, '');
                if (isOverLimit) {
                    percentageElement.classList.add('text-red-600');
                } else if (isNearLimit) {
                    percentageElement.classList.add('text-yellow-600');
                } else {
                    percentageElement.classList.add('text-green-600');
                }
            }

            // Update usage color classes based on status
            usageElement.className = usageElement.className.replace(/text-(green|yellow|red)-\d+/, '');
            if (status === 'no_permission') {
                usageElement.classList.add('text-gray-500', 'dark:text-gray-400');
            } else if (isOverLimit) {
                usageElement.classList.add('text-red-600', 'dark:text-red-400');
            } else if (isNearLimit) {
                usageElement.classList.add('text-yellow-600', 'dark:text-yellow-400');
            } else {
                usageElement.classList.add('text-green-600', 'dark:text-green-400');
            }

            // Update icon
            if (iconElement) {
                iconElement.className = iconElement.className.replace(/fa-(check-circle|exclamation-circle|exclamation-triangle|lock) text-(green|yellow|red|gray)-\d+/, '');
                if (status === 'no_permission') {
                    iconElement.classList.add('fa-lock', 'text-gray-500');
                } else if (isOverLimit) {
                    iconElement.classList.add('fa-exclamation-triangle', 'text-red-500');
                } else if (isNearLimit) {
                    iconElement.classList.add('fa-exclamation-circle', 'text-yellow-500');
                } else {
                    iconElement.classList.add('fa-check-circle', 'text-green-500');
                }
            }

            // Update alerts
            if (alertsContainer) {
                if (status === 'no_permission') {
                    alertsContainer.innerHTML = `
                        <div class="bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800 rounded-md p-2 mt-2">
                            <p class="text-xs text-gray-700 dark:text-gray-300">
                                <i class="fas fa-lock mr-1"></i>
                                ${window.UELSTranslations.status?.no_limit || 'No permission to create this entity'}
                            </p>
                        </div>
                    `;
                } else if (isOverLimit) {
                    alertsContainer.innerHTML = `
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-2 mt-2">
                            <p class="text-xs text-red-700 dark:text-red-300">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                ${window.UELSTranslations.widget?.limit_exceeded || 'Limit exceeded'}
                            </p>
                        </div>
                    `;
                } else if (isNearLimit) {
                    alertsContainer.innerHTML = `
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-2 mt-2">
                            <p class="text-xs text-yellow-700 dark:text-yellow-300">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                ${window.UELSTranslations.widget?.approaching_limit || 'Approaching limit'}
                            </p>
                        </div>
                    `;
                } else {
                    alertsContainer.innerHTML = ''; // Clear alerts when all is OK
                }
            }

            // Hide/show progress container based on limit and permission
            if (progressContainer) {
                if (limit !== null && limit > 0 && status !== 'no_permission') {
                    progressContainer.style.display = 'block';
                    progressContainer.parentElement.style.display = 'block';
                } else {
                    progressContainer.style.display = 'none';
                    // Also hide the percentage element if no limit or no permission
                    const percentageContainer = widget.querySelector('[data-uels-percentage]')?.parentElement;
                    if (percentageContainer) {
                        percentageContainer.style.display = 'none';
                    }
                }
            }
        }
    }
}

// Make UELSManager available globally
window.UELSManager = UELSManager;

// Initialize UELS Manager
window.uelsManager = new UELSManager();

// Global functions for template use
window.openUelsModal = function(entityType) {
    window.uelsManager.openModal(entityType);
};

window.closeUelsModal = function() {
    window.uelsManager.closeModal();
};

window.refreshUelsData = function() {
    window.uelsManager.refreshData();
};

window.retryUelsModal = function() {
    window.uelsManager.retryModal();
};

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('uelsModal');
    if (event.target === modal) {
        window.closeUelsModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        window.closeUelsModal();
    }
});
