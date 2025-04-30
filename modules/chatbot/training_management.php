<?php
require_once '../../gibbon.php';

// Check user has access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/training_management.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('ChatBot'))
        ->add(__('Training Management'));

    // Check if user is admin
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/ChatBot/training_management.php');

    if (!$isAdmin) {
        $page->addError(__('You do not have permission to access this page.'));
    } else {
        ?>
        <div class="message">
            <div class="training-management-container">
                <div class="training-sidebar">
                    <div class="training-stats">
                        <h3>Training Stats</h3>
                        <div class="stat-item">
                            <span class="stat-label">Total Items:</span>
                            <span id="totalItems">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Last Upload:</span>
                            <span id="lastUpload">Never</span>
                        </div>
                    </div>
                    <div class="training-actions">
                        <h3>Actions</h3>
                        <button class="upload-btn">
                            <i class="fas fa-upload"></i> Upload CSV
                        </button>
                        <button class="refresh-btn">
                            <i class="fas fa-sync"></i> Refresh Data
                        </button>
                        <button class="export-btn">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </div>
                    <div class="training-filters">
                        <h3>Filters</h3>
                        <div class="filter-group">
                            <label for="searchTraining">Search:</label>
                            <input type="text" id="searchTraining" placeholder="Search questions or answers...">
                        </div>
                        <div class="filter-group">
                            <label for="approved">Status:</label>
                            <select id="approved">
                                <option value="all">All</option>
                                <option value="1">Approved</option>
                                <option value="0">Pending</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="dateRange">Date Range:</label>
                            <select id="dateRange">
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="training-data-container">
                    <div class="training-data-header">
                        <h2>Training Data</h2>
                    </div>
                    <div class="training-data-content">
                        <table class="training-data-table">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Answer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Training data will be loaded here -->
                            </tbody>
                        </table>
                        <div class="pagination">
                            <!-- Pagination controls will be added here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .training-management-container {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }

            .training-sidebar {
                width: 300px;
                background: #f5f5f5;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .training-data-container {
                flex: 1;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .training-stats, .training-actions, .training-filters {
                margin-bottom: 20px;
            }

            .stat-item {
                display: flex;
                justify-content: space-between;
                margin: 10px 0;
                padding: 8px;
                background: #fff;
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }

            .training-actions button {
                width: 100%;
                margin: 5px 0;
                padding: 8px;
                border: none;
                border-radius: 4px;
                background: #00a651;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: background-color 0.2s;
            }

            .training-actions button:hover {
                background: #008741;
            }

            .filter-group {
                margin: 10px 0;
            }

            .filter-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }

            .filter-group input,
            .filter-group select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fff;
            }

            .training-data-header {
                padding: 20px;
                border-bottom: 1px solid #eee;
                background: #f9f9f9;
                border-radius: 8px 8px 0 0;
            }

            .training-data-content {
                padding: 20px;
            }

            .training-data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }

            .training-data-table th,
            .training-data-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }

            .training-data-table th {
                background: #f9f9f9;
                font-weight: 600;
                color: #333;
            }

            .training-data-table tr:hover {
                background: #f5f5f5;
            }

            .action-btn {
                padding: 6px 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin: 0 4px;
                transition: background-color 0.2s;
            }

            .edit-btn {
                background: #4a90e2;
                color: white;
            }

            .edit-btn:hover {
                background: #357abd;
            }

            .delete-btn {
                background: #e74c3c;
                color: white;
            }

            .delete-btn:hover {
                background: #c0392b;
            }

            .pagination {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-top: 20px;
            }

            .pagination button {
                padding: 8px 12px;
                border: 1px solid #ddd;
                background: white;
                cursor: pointer;
                border-radius: 4px;
                transition: all 0.2s;
            }

            .pagination button.active {
                background: #00a651;
                color: white;
                border-color: #00a651;
            }

            .pagination button:disabled {
                background: #f5f5f5;
                cursor: not-allowed;
            }

            .pagination button:hover:not(:disabled) {
                background: #f5f5f5;
            }

            .pagination button.active:hover {
                background: #008741;
            }
        </style>

        <script>
            // Initialize training manager when the page loads
            document.addEventListener('DOMContentLoaded', () => {
                const trainingManager = new TrainingManager('/modules/ChatBot/api');
            });
        </script>
        <?php
    }
}
?> 