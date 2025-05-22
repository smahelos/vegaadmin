/**
 * Dashboard Statistics Module
 * Advanced statistics visualization for the dashboard
 */
class DashboardStatistics {
    constructor() {
        this.charts = {};
        this.filters = {
            timeRange: 'year',
            dateFrom: null,
            dateTo: null
        };
        this.translations = {};
        this.currency = 'CZK';
        this.initialized = false;
        this.selectedClients = [];
    }

    /**
     * Initialize the dashboard statistics
     * @param {Object} options - Configuration options
     */
    init(options = {}) {
        this.translations = options.translations || {};
        this.currency = options.currency || 'CZK';
        this.dateFormat = options.dateFormat || { year: 'numeric', month: 'short' };
        this.locale = options.locale || 'cs-CZ';

        // Initialize date filters if provided
        if (options.dateFrom) this.filters.dateFrom = options.dateFrom;
        if (options.dateTo) this.filters.dateTo = options.dateTo;

        // Initialize filter controls
        this.initFilterControls();

        // Initialize charts if containers exist
        this.initCharts();
        
        this.initialized = true;
        console.log('Dashboard statistics module initialized');
    }

    /**
     * Initialize filter controls
     */
    initFilterControls() {
        // Time range selector
        const timeRangeSelector = document.getElementById('stats-timerange');
        if (timeRangeSelector) {
            timeRangeSelector.addEventListener('change', (e) => {
                this.filters.timeRange = e.target.value;
                this.refreshAllCharts();
            });
        }

        // Date range pickers
        const dateFromPicker = document.getElementById('stats-date-from');
        const dateToPicker = document.getElementById('stats-date-to');
        
        if (dateFromPicker && dateToPicker) {
            dateFromPicker.addEventListener('change', (e) => {
                this.filters.dateFrom = e.target.value;
                this.refreshAllCharts();
            });
            
            dateToPicker.addEventListener('change', (e) => {
                this.filters.dateTo = e.target.value;
                this.refreshAllCharts();
            });
        }

        // Client filter
        const clientFilter = document.getElementById('stats-client-filter');
        if (clientFilter) {
            clientFilter.addEventListener('change', (e) => {
                const selectedOptions = Array.from(e.target.selectedOptions);
                this.selectedClients = selectedOptions.map(option => option.value);
                this.refreshAllCharts();
            });
        }
    }

    /**
     * Initialize all charts if their containers exist
     */
    initCharts() {
        // Main revenue chart (enhanced version of existing chart)
        if (document.getElementById('invoicesChart')) {
            this.initRevenueChart();
        }

        // Revenue by client chart
        if (document.getElementById('revenueByClientChart')) {
            this.initRevenueByClientChart();
        }

        // Invoice status distribution chart
        if (document.getElementById('invoiceStatusChart')) {
            this.initInvoiceStatusChart();
        }

        // Payment method distribution chart
        if (document.getElementById('paymentMethodChart')) {
            this.initPaymentMethodChart();
        }

        // Revenue vs expenses chart
        if (document.getElementById('revenueExpensesChart')) {
            this.initRevenueVsExpensesChart();
        }
    }

    /**
     * Initialize the main revenue chart with enhanced features
     */
    initRevenueChart() {
        const ctx = document.getElementById('invoicesChart').getContext('2d');
        
        // Get initial data from the data attribute if available
        const initialDataElement = document.getElementById('monthly-stats-data');
        let initialData = [];
        
        if (initialDataElement && initialDataElement.dataset.stats) {
            try {
                initialData = JSON.parse(initialDataElement.dataset.stats);
            } catch (error) {
                console.error('Error parsing initial stats data:', error);
            }
        }
        
        this.charts.revenue = this.createChart(ctx, 'bar', {
            labels: this.formatDateLabels(initialData.map(item => item.month)),
            datasets: [
                {
                    label: this.translations.billedAmount || 'Fakturovaná částka',
                    data: initialData.map(item => item.total),
                    backgroundColor: 'rgba(79, 70, 229, 0.5)',
                    borderColor: 'rgb(79, 70, 229)',
                    borderWidth: 1
                },
                {
                    label: this.translations.paidAmount || 'Zaplacená částka',
                    data: initialData.map(item => item.paid || 0),
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1
                }
            ]
        }, {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => this.formatCurrency(value)
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            return context.dataset.label + ': ' + this.formatCurrency(context.raw);
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        });
    }

    /**
     * Initialize revenue by client chart
     */
    initRevenueByClientChart() {
        const ctx = document.getElementById('revenueByClientChart').getContext('2d');
        
        // Fetch data from API
        this.fetchData('client-revenue').then(data => {
            this.charts.clientRevenue = this.createChart(ctx, 'pie', {
                labels: data.map(item => item.client_name),
                datasets: [{
                    data: data.map(item => item.total),
                    backgroundColor: this.generateColors(data.length),
                }]
            }, {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = this.formatCurrency(context.raw);
                                const percentage = context.parsed * 100 / context.dataset.data.reduce((a, b) => a + b, 0);
                                return `${label}: ${value} (${percentage.toFixed(1)}%)`;
                            }
                        }
                    },
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            });
        }).catch(error => {
            console.error('Failed to load client revenue data:', error);
        });
    }

    /**
     * Initialize invoice status distribution chart
     */
    initInvoiceStatusChart() {
        const ctx = document.getElementById('invoiceStatusChart').getContext('2d');
        
        this.fetchData('invoice-status').then(data => {
            const statusColors = {
                'paid': 'rgba(16, 185, 129, 0.7)',  // green
                'pending': 'rgba(251, 191, 36, 0.7)',  // yellow
                'overdue': 'rgba(239, 68, 68, 0.7)',   // red
                'draft': 'rgba(156, 163, 175, 0.7)'    // gray
            };
            
            this.charts.invoiceStatus = this.createChart(ctx, 'doughnut', {
                labels: data.map(item => this.translations[item.status] || item.status),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: data.map(item => statusColors[item.status] || 'rgba(79, 70, 229, 0.7)'),
                }]
            }, {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.raw;
                                return `${label}: ${value}`;
                            }
                        }
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            });
        }).catch(error => {
            console.error('Failed to load invoice status data:', error);
        });
    }

    /**
     * Initialize payment method distribution chart
     */
    initPaymentMethodChart() {
        const ctx = document.getElementById('paymentMethodChart').getContext('2d');
        
        this.fetchData('payment-methods').then(data => {
            this.charts.paymentMethods = this.createChart(ctx, 'bar', {
                labels: data.map(item => this.translations[item.method] || item.method),
                datasets: [{
                    label: this.translations.amount || 'Částka',
                    data: data.map(item => item.total),
                    backgroundColor: 'rgba(79, 70, 229, 0.5)',
                    borderColor: 'rgb(79, 70, 229)',
                    borderWidth: 1,
                }]
            }, {
                indexAxis: 'y',  // For horizontal bar chart
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => this.formatCurrency(value)
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return this.formatCurrency(context.raw);
                            }
                        }
                    }
                }
            });
        }).catch(error => {
            console.error('Failed to load payment methods data:', error);
        });
    }

    /**
     * Initialize revenue vs expenses chart
     */
    initRevenueVsExpensesChart() {
        const ctx = document.getElementById('revenueExpensesChart').getContext('2d');
        
        this.fetchData('revenue-expenses').then(data => {
            const months = [...new Set(data.map(item => item.month))].sort();
            
            const revenue = months.map(month => {
                const item = data.find(d => d.month === month && d.type === 'revenue');
                return item ? item.amount : 0;
            });
            
            const expenses = months.map(month => {
                const item = data.find(d => d.month === month && d.type === 'expense');
                return item ? item.amount : 0;
            });
            
            this.charts.revenueExpenses = this.createChart(ctx, 'line', {
                labels: this.formatDateLabels(months),
                datasets: [
                    {
                        label: this.translations.revenue || 'Příjmy',
                        data: revenue,
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: this.translations.expenses || 'Výdaje',
                        data: expenses,
                        backgroundColor: 'rgba(239, 68, 68, 0.2)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            }, {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => this.formatCurrency(value)
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return context.dataset.label + ': ' + this.formatCurrency(context.raw);
                            }
                        }
                    }
                }
            });
        }).catch(error => {
            console.error('Failed to load revenue vs expenses data:', error);
        });
    }

    /**
     * Helper method to create a chart
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {string} type - Chart type
     * @param {Object} data - Chart data
     * @param {Object} options - Chart options
     * @returns {Chart} The created chart
     */
    createChart(ctx, type, data, options = {}) {
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false
        };
        
        const mergedOptions = { ...defaultOptions, ...options };
        
        return new Chart(ctx, {
            type: type,
            data: data,
            options: mergedOptions
        });
    }

    /**
     * Fetch data from API
     * @param {string} endpoint - API endpoint
     * @returns {Promise<Array>} Promise resolving to data
     */
    async fetchData(endpoint) {
        // Prepare query parameters based on current filters
        const queryParams = new URLSearchParams();
        
        if (this.filters.timeRange) {
            queryParams.append('time_range', this.filters.timeRange);
        }
        
        if (this.filters.dateFrom) {
            queryParams.append('date_from', this.filters.dateFrom);
        }
        
        if (this.filters.dateTo) {
            queryParams.append('date_to', this.filters.dateTo);
        }
        
        if (this.selectedClients && this.selectedClients.length > 0) {
            this.selectedClients.forEach(clientId => {
                queryParams.append('clients[]', clientId);
            });
        }
        
        const url = `/api/statistics/${endpoint}?${queryParams.toString()}`;
        
        try {
            const response = await window.fetchWithSession(url);
            return response.data;
        } catch (error) {
            console.error(`Error fetching data from ${endpoint}:`, error);
            throw error;
        }
    }

    /**
     * Refresh all initialized charts
     */
    refreshAllCharts() {
        // For each chart, fetch fresh data and update
        Object.keys(this.charts).forEach(chartKey => {
            const chart = this.charts[chartKey];
            const endpoint = this.getEndpointForChart(chartKey);
            
            if (endpoint) {
                this.fetchData(endpoint).then(data => {
                    this.updateChartData(chart, chartKey, data);
                }).catch(error => {
                    console.error(`Failed to refresh ${chartKey} chart:`, error);
                });
            }
        });
    }

    /**
     * Get API endpoint for a specific chart
     * @param {string} chartKey - Chart identifier
     * @returns {string} API endpoint
     */
    getEndpointForChart(chartKey) {
        const endpoints = {
            'revenue': 'monthly-revenue',
            'clientRevenue': 'client-revenue',
            'invoiceStatus': 'invoice-status',
            'paymentMethods': 'payment-methods',
            'revenueExpenses': 'revenue-expenses'
        };
        
        return endpoints[chartKey] || null;
    }

    /**
     * Update chart data based on fresh API data
     * @param {Chart} chart - Chart.js instance
     * @param {string} chartKey - Chart identifier
     * @param {Array} data - New data from API
     */
    updateChartData(chart, chartKey, data) {
        switch (chartKey) {
            case 'revenue':
                chart.data.labels = this.formatDateLabels(data.map(item => item.month));
                chart.data.datasets[0].data = data.map(item => item.total);
                chart.data.datasets[1].data = data.map(item => item.paid || 0);
                break;
                
            case 'clientRevenue':
                chart.data.labels = data.map(item => item.client_name);
                chart.data.datasets[0].data = data.map(item => item.total);
                chart.data.datasets[0].backgroundColor = this.generateColors(data.length);
                break;
                
            case 'invoiceStatus':
                chart.data.labels = data.map(item => this.translations[item.status] || item.status);
                chart.data.datasets[0].data = data.map(item => item.count);
                break;
                
            case 'paymentMethods':
                chart.data.labels = data.map(item => this.translations[item.method] || item.method);
                chart.data.datasets[0].data = data.map(item => item.total);
                break;
                
            case 'revenueExpenses':
                const months = [...new Set(data.map(item => item.month))].sort();
                
                chart.data.labels = this.formatDateLabels(months);
                
                const revenue = months.map(month => {
                    const item = data.find(d => d.month === month && d.type === 'revenue');
                    return item ? item.amount : 0;
                });
                
                const expenses = months.map(month => {
                    const item = data.find(d => d.month === month && d.type === 'expense');
                    return item ? item.amount : 0;
                });
                
                chart.data.datasets[0].data = revenue;
                chart.data.datasets[1].data = expenses;
                break;
        }
        
        chart.update();
    }

    /**
     * Format currency values
     * @param {number} value - Value to format
     * @returns {string} Formatted currency string
     */
    formatCurrency(value) {
        return new Intl.NumberFormat(this.locale, { 
            style: 'currency', 
            currency: this.currency,
            maximumFractionDigits: 0
        }).format(value);
    }

    /**
     * Format date labels
     * @param {Array<string>} dates - Array of date strings (YYYY-MM format)
     * @returns {Array<string>} Array of formatted date strings
     */
    formatDateLabels(dates) {
        return dates.map(dateStr => {
            try {
                const [year, month] = dateStr.split('-');
                return new Date(year, month - 1).toLocaleDateString(this.locale, this.dateFormat);
            } catch (e) {
                return dateStr;
            }
        });
    }

    /**
     * Generate an array of colors
     * @param {number} count - Number of colors to generate
     * @returns {Array<string>} Array of color strings
     */
    generateColors(count) {
        const baseColors = [
            'rgba(79, 70, 229, 0.7)',   // indigo
            'rgba(16, 185, 129, 0.7)',  // green
            'rgba(239, 68, 68, 0.7)',   // red
            'rgba(245, 158, 11, 0.7)',  // amber
            'rgba(59, 130, 246, 0.7)',  // blue
            'rgba(236, 72, 153, 0.7)',  // pink
            'rgba(139, 92, 246, 0.7)',  // purple
            'rgba(20, 184, 166, 0.7)',  // teal
        ];
        
        // If we need more colors than in our base set, generate additional ones
        if (count > baseColors.length) {
            const additionalColors = [];
            for (let i = 0; i < count - baseColors.length; i++) {
                const hue = (i * 137) % 360; // Use golden ratio for good distribution
                additionalColors.push(`hsla(${hue}, 70%, 60%, 0.7)`);
            }
            return [...baseColors, ...additionalColors];
        }
        
        // Otherwise return just the number of colors we need
        return baseColors.slice(0, count);
    }
}

// Export the class
export default DashboardStatistics;
