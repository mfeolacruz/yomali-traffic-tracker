/**
 * Yomali Traffic Tracker - Dashboard JavaScript
 * Handles data fetching, filtering, sorting, and pagination
 */

class AnalyticsDashboard {
    constructor() {
        this.apiBase = '/api/v1';
        this.currentData = [];
        this.currentPage = 1;
        this.recordsPerPage = 20;
        this.totalRecords = 0;
        this.totalPages = 0;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.filters = {
            domain: '',
            startDate: '',
            endDate: ''
        };
        this.autoRefreshInterval = null;
        this.isLoading = false;
        
        this.init();
    }
    
    /**
     * Initialize the dashboard
     */
    init() {
        this.bindEvents();
        this.loadFromURL();
        this.loadData();
        this.startAutoRefresh();
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Records per page selector
        document.getElementById('recordsPerPage').addEventListener('change', (e) => {
            this.recordsPerPage = parseInt(e.target.value);
            this.currentPage = 1; // Reset to first page
            this.loadData();
            this.updateURL();
        });
        
        // Filter controls
        document.getElementById('applyFilters').addEventListener('click', () => {
            this.applyFilters();
        });
        
        document.getElementById('clearFilters').addEventListener('click', () => {
            this.clearFilters();
        });
        
        // Enter key on filter inputs
        ['domainFilter', 'startDate', 'endDate'].forEach(id => {
            document.getElementById(id).addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.applyFilters();
                }
            });
        });
        
        // Manual refresh
        document.getElementById('refreshIcon').addEventListener('click', () => {
            this.loadData(true);
        });
        
        // Table sorting
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', () => {
                this.handleSort(header.dataset.sort);
            });
        });
    }
    
    /**
     * Load filters and pagination from URL parameters
     */
    loadFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Load filters
        this.filters.domain = urlParams.get('domain') || '';
        this.filters.startDate = urlParams.get('start_date') || '';
        this.filters.endDate = urlParams.get('end_date') || '';
        
        // Load pagination
        this.currentPage = parseInt(urlParams.get('page')) || 1;
        this.recordsPerPage = parseInt(urlParams.get('limit')) || 20;
        
        // Load sorting
        this.sortColumn = urlParams.get('sort') || null;
        this.sortDirection = urlParams.get('order') || 'asc';
        
        // Update form inputs
        document.getElementById('domainFilter').value = this.filters.domain;
        document.getElementById('startDate').value = this.filters.startDate;
        document.getElementById('endDate').value = this.filters.endDate;
        document.getElementById('recordsPerPage').value = this.recordsPerPage;
    }
    
    /**
     * Update URL with current state
     */
    updateURL() {
        const params = new URLSearchParams();
        
        // Add filters
        if (this.filters.domain) params.set('domain', this.filters.domain);
        if (this.filters.startDate) params.set('start_date', this.filters.startDate);
        if (this.filters.endDate) params.set('end_date', this.filters.endDate);
        
        // Add pagination
        if (this.currentPage > 1) params.set('page', this.currentPage);
        if (this.recordsPerPage !== 20) params.set('limit', this.recordsPerPage);
        
        // Add sorting
        if (this.sortColumn) {
            params.set('sort', this.sortColumn);
            params.set('order', this.sortDirection);
        }
        
        const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState(null, '', newURL);
    }
    
    /**
     * Apply current filters
     */
    applyFilters() {
        this.filters.domain = document.getElementById('domainFilter').value.trim();
        this.filters.startDate = document.getElementById('startDate').value;
        this.filters.endDate = document.getElementById('endDate').value;
        
        // Validate date range
        if (this.filters.startDate && this.filters.endDate) {
            const startDate = new Date(this.filters.startDate);
            const endDate = new Date(this.filters.endDate);
            
            if (startDate > endDate) {
                this.showDateError('Start date cannot be after end date');
                return;
            }
        }
        
        this.currentPage = 1; // Reset to first page when filtering
        this.loadData();
        this.updateURL();
    }
    
    /**
     * Clear all filters
     */
    clearFilters() {
        this.filters = { domain: '', startDate: '', endDate: '' };
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        
        // Clear form inputs
        document.getElementById('domainFilter').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        
        // Clear sort headers
        document.querySelectorAll('.sortable').forEach(header => {
            header.classList.remove('sorted-asc', 'sorted-desc');
        });
        
        this.loadData();
        this.updateURL();
    }
    
    /**
     * Handle table column sorting
     */
    handleSort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        
        this.sortData();
        this.renderTable();
        this.updateSortHeaders();
        this.updateURL();
    }
    
    /**
     * Sort current data
     */
    sortData() {
        if (!this.sortColumn) return;
        
        this.currentData.sort((a, b) => {
            let aVal = a[this.sortColumn];
            let bVal = b[this.sortColumn];
            
            // Handle different data types
            if (this.sortColumn.includes('visits')) {
                aVal = parseInt(aVal);
                bVal = parseInt(bVal);
            } else {
                aVal = aVal.toString().toLowerCase();
                bVal = bVal.toString().toLowerCase();
            }
            
            if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
    }
    
    /**
     * Update sort header indicators
     */
    updateSortHeaders() {
        document.querySelectorAll('.sortable').forEach(header => {
            header.classList.remove('sorted-asc', 'sorted-desc');
            if (header.dataset.sort === this.sortColumn) {
                header.classList.add(`sorted-${this.sortDirection}`);
            }
        });
    }
    
    /**
     * Load data from API
     */
    async loadData(forceRefresh = false) {
        if (this.isLoading && !forceRefresh) return;
        
        this.isLoading = true;
        this.showLoading();
        
        if (forceRefresh) {
            this.startRefreshAnimation();
        }
        
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.recordsPerPage
            });
            
            // Add filters with correct parameter names
            if (this.filters.domain) params.set('domain', this.filters.domain);
            if (this.filters.startDate) params.set('start_date', this.filters.startDate);
            if (this.filters.endDate) params.set('end_date', this.filters.endDate);
            
            const response = await fetch(`${this.apiBase}/analytics?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            this.currentData = data.data || [];
            this.totalRecords = data.pagination?.total || 0;
            this.totalPages = data.pagination?.total_pages || 0;
            this.currentPage = data.pagination?.page || 1;
            
            // Apply client-side sorting if needed
            if (this.sortColumn) {
                this.sortData();
            }
            
            this.renderTable();
            this.renderPagination();
            this.updateLastUpdated();
            this.hideLoading();
            
        } catch (error) {
            console.error('Error loading data:', error);
            this.showError(`Failed to load data: ${error.message}`);
        } finally {
            this.isLoading = false;
            if (forceRefresh) {
                this.stopRefreshAnimation();
            }
        }
    }
    
    /**
     * Render the data table
     */
    renderTable() {
        const tbody = document.getElementById('tableBody');
        
        if (this.currentData.length === 0) {
            this.showEmpty();
            return;
        }
        
        tbody.innerHTML = this.currentData.map(item => `
            <tr>
                <td class="domain-column">
                    <span class="fw-medium">${this.escapeHtml(item.domain)}</span>
                </td>
                <td class="path-column">
                    <span class="font-monospace text-muted">${this.escapeHtml(item.path)}</span>
                </td>
                <td class="text-center numeric">
                    <span class="badge bg-info rounded-pill">${item.unique_visits}</span>
                </td>
                <td class="text-center numeric">
                    <span class="badge bg-primary rounded-pill">${item.total_visits}</span>
                </td>
            </tr>
        `).join('');
        
        this.showTable();
    }
    
    /**
     * Render pagination controls
     */
    renderPagination() {
        const paginationInfo = document.getElementById('paginationInfo');
        const paginationControls = document.getElementById('paginationControls');
        
        // Update info
        const start = this.totalRecords === 0 ? 0 : ((this.currentPage - 1) * this.recordsPerPage) + 1;
        const end = Math.min(this.currentPage * this.recordsPerPage, this.totalRecords);
        paginationInfo.textContent = `Showing ${start} - ${end} of ${this.totalRecords} results`;
        
        // Generate pagination controls
        const pages = this.generatePaginationPages();
        paginationControls.innerHTML = pages.map(page => {
            if (page === '...') {
                return '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            
            if (page === 'prev') {
                return `<li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${this.currentPage - 1}">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                </li>`;
            }
            
            if (page === 'next') {
                return `<li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${this.currentPage + 1}">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </li>`;
            }
            
            return `<li class="page-item ${page === this.currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${page}">${page}</a>
            </li>`;
        }).join('');
        
        // Bind pagination events
        paginationControls.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page || e.target.closest('a').dataset.page);
                if (page && page !== this.currentPage && page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                    this.loadData();
                    this.updateURL();
                }
            });
        });
    }
    
    /**
     * Generate pagination page numbers
     */
    generatePaginationPages() {
        const pages = ['prev'];
        const maxVisible = 7;
        
        if (this.totalPages <= maxVisible) {
            for (let i = 1; i <= this.totalPages; i++) {
                pages.push(i);
            }
        } else {
            pages.push(1);
            
            if (this.currentPage > 4) {
                pages.push('...');
            }
            
            const start = Math.max(2, this.currentPage - 1);
            const end = Math.min(this.totalPages - 1, this.currentPage + 1);
            
            for (let i = start; i <= end; i++) {
                if (i !== 1 && i !== this.totalPages) {
                    pages.push(i);
                }
            }
            
            if (this.currentPage < this.totalPages - 3) {
                pages.push('...');
            }
            
            pages.push(this.totalPages);
        }
        
        pages.push('next');
        return pages;
    }
    
    /**
     * Start auto-refresh
     */
    startAutoRefresh() {
        this.autoRefreshInterval = setInterval(() => {
            if (!document.hidden && !this.isLoading) {
                this.loadData();
            }
        }, 30000); // 30 seconds
    }
    
    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }
    }
    
    /**
     * Show/hide different states
     */
    showLoading() {
        document.getElementById('loadingState').style.display = 'block';
        document.getElementById('errorState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('dataTable').style.display = 'none';
    }
    
    showError(message) {
        document.getElementById('errorMessage').textContent = message;
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorState').style.display = 'block';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('dataTable').style.display = 'none';
    }
    
    showDateError(message) {
        // Create a toast or alert for date validation errors
        alert(message);
    }
    
    showEmpty() {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('dataTable').style.display = 'none';
    }
    
    showTable() {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('dataTable').style.display = 'block';
    }
    
    hideLoading() {
        document.getElementById('loadingState').style.display = 'none';
    }
    
    /**
     * Animation helpers
     */
    startRefreshAnimation() {
        document.getElementById('refreshIcon').classList.add('spinning');
    }
    
    stopRefreshAnimation() {
        document.getElementById('refreshIcon').classList.remove('spinning');
    }
    
    /**
     * Update last updated timestamp
     */
    updateLastUpdated() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString();
        document.getElementById('lastUpdated').textContent = `Last updated: ${timeStr}`;
    }
    
    /**
     * Utility functions
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatDateTime(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new AnalyticsDashboard();
});

// Handle page visibility changes for auto-refresh
document.addEventListener('visibilitychange', () => {
    if (window.dashboard) {
        if (document.hidden) {
            window.dashboard.stopAutoRefresh();
        } else {
            window.dashboard.startAutoRefresh();
        }
    }
});