<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yomali Traffic Tracker - Analytics Dashboard</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-chart-line me-2"></i>
                Yomali Traffic Tracker
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i>
                    <span id="lastUpdated" class="ms-2 text-light opacity-75">Loading...</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Filters Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>
                            Filters
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Domain Filter -->
                            <div class="col-md-4">
                                <label for="domainFilter" class="form-label">Domain</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                    <input type="text" class="form-control" id="domainFilter" 
                                           placeholder="example.com">
                                </div>
                            </div>
                            
                            <!-- Date Range Filters -->
                            <div class="col-md-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate">
                            </div>
                            
                            <!-- Filter Actions -->
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-danger" id="clearFilters">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                    <button type="button" class="btn btn-primary" id="applyFilters">
                                        <i class="fas fa-search"></i> Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table Section -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>
                            Page Analytics
                        </h5>
                        <div class="d-flex align-items-center">
                            <label for="recordsPerPage" class="form-label me-2 mb-0">Records per page:</label>
                            <select class="form-select form-select-sm" id="recordsPerPage" style="width: auto;">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Loading State -->
                        <div id="loadingState" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2 text-muted">Loading analytics data...</div>
                        </div>

                        <!-- Error State -->
                        <div id="errorState" class="alert alert-danger m-3" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="errorMessage">Failed to load data</span>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="text-center py-5" style="display: none;">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Data Available</h5>
                            <p class="text-muted">No page visits found for the selected filters.</p>
                        </div>

                        <!-- Data Table -->
                        <div id="dataTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="sortable" data-sort="domain">
                                                Domain <i class="fas fa-sort text-muted"></i>
                                            </th>
                                            <th scope="col" class="sortable" data-sort="path">
                                                Path <i class="fas fa-sort text-muted"></i>
                                            </th>
                                            <th scope="col" class="sortable text-center" data-sort="unique_visits">
                                                Unique Visits <i class="fas fa-sort text-muted"></i>
                                            </th>
                                            <th scope="col" class="sortable text-center" data-sort="total_visits">
                                                Total Visits <i class="fas fa-sort text-muted"></i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <!-- Data will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                <span id="paginationInfo">Showing 0 - 0 of 0 results</span>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                    <!-- Pagination will be populated by JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>