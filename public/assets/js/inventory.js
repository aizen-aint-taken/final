// Inventory Dashboard JavaScript
$(document).ready(function() {
    // Initialize all dashboard components that don't exist in custom-inventory.js
    loadStockDistributionChart();
    loadBorrowingStatusChart();
    loadAlertsAndNotifications();
    loadActivityFeed();
    loadRealtimeIndicators();
    
    // Set up auto-refresh for components not handled by custom-inventory.js
    setInterval(function() {
        loadStockDistributionChart();
        loadBorrowingStatusChart();
        loadAlertsAndNotifications();
        loadActivityFeed();
        loadRealtimeIndicators();
    }, 300000); // Refresh every 5 minutes
});

// Show notification message
function showNotification(message, type) {
    let alertClass = type === 'success' ? 'alert-success' :
        type === 'error' ? 'alert-danger' :
        type === 'warning' ? 'alert-warning' : 'alert-info';

    let alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ${message}
        </div>
    `;

    $('.container-fluid').prepend(alertHtml);

    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Stock Distribution Chart
function loadStockDistributionChart() {
    $.post('inventory_operations.php', {
        action: 'get_stock_distribution'
    }, function(response) {
        if (response.success) {
            renderStockChart(response.data);
        } else {
            $('#stockChart').html('<p class="text-muted text-center">Unable to load stock distribution data</p>');
        }
    }, 'json').fail(function() {
        $('#stockChart').html('<p class="text-muted text-center">Error loading stock distribution data</p>');
    });
}

// Render Stock Distribution Chart
function renderStockChart(data) {
    const ctx = document.getElementById('stockChart').getContext('2d');
    
    // Process data for chart
    const subjects = [];
    const stockCounts = [];
    const backgroundColors = [];
    
    // Generate distinct colors for each subject
    const colorPalette = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
        '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56'
    ];
    
    data.forEach((item, index) => {
        subjects.push(item.Subject || 'Unknown');
        stockCounts.push(item.total_stock);
        backgroundColors.push(colorPalette[index % colorPalette.length]);
    });
    
    // Destroy existing chart if it exists
    if (window.stockChartInstance) {
        window.stockChartInstance.destroy();
    }
    
    // Create new chart
    window.stockChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: subjects,
            datasets: [{
                label: 'Total Books by Subject',
                data: stockCounts,
                backgroundColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y} books`;
                        }
                    }
                }
            }
        }
    });
}

// Borrowing Status Chart
function loadBorrowingStatusChart() {
    $.post('inventory_operations.php', {
        action: 'get_borrowing_status'
    }, function(response) {
        if (response.success) {
            renderReservationChart(response.data);
        } else {
            $('#reservationChart').html('<p class="text-muted text-center">Unable to load borrowing status data</p>');
        }
    }, 'json').fail(function() {
        $('#reservationChart').html('<p class="text-muted text-center">Error loading borrowing status data</p>');
    });
}

// Render Borrowing Status Chart
function renderReservationChart(data) {
    const ctx = document.getElementById('reservationChart').getContext('2d');
    
    // Process data for chart
    const statusLabels = [];
    const statusCounts = [];
    const backgroundColors = ['#36A2EB', '#4BC0C0', '#FF6384', '#FFCE56', '#9966FF'];
    
    data.forEach((item, index) => {
        statusLabels.push(item.STATUS || 'Unknown');
        statusCounts.push(item.total);
    });
    
    // Destroy existing chart if it exists
    if (window.reservationChartInstance) {
        window.reservationChartInstance.destroy();
    }
    
    // Create new chart
    window.reservationChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: backgroundColors.slice(0, statusCounts.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.parsed * 100) / total);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Alerts & Notifications
function loadAlertsAndNotifications() {
    $.post('inventory_operations.php', {
        action: 'get_alerts_notifications'
    }, function(response) {
        if (response.success) {
            let html = '';
            
            // Low stock alerts
            if (response.data.low_stock && response.data.low_stock.length > 0) {
                response.data.low_stock.forEach(function(book) {
                    html += `
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>⚠️ Low Stock Alert:</strong> "${book.Title}" is running low (${book.Stock} copies remaining)
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `;
                });
            }
            
            // Overdue books alerts
            if (response.data.overdue && response.data.overdue.length > 0) {
                response.data.overdue.forEach(function(reservation) {
                    html += `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>🚨 Overdue Book:</strong> "${reservation.Title}" borrowed by ${reservation.student_name} is ${reservation.days_overdue} days overdue
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `;
                });
            }
            
            // Recently added books
            if (response.data.recent_additions && response.data.recent_additions.length > 0) {
                response.data.recent_additions.forEach(function(book) {
                    html += `
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong>🆕 New Addition:</strong> "${book.Title}" was recently added to inventory (${book.Stock} copies)
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `;
                });
            }
            
            if (html === '') {
                html = '<p class="text-muted text-center">No alerts or notifications at this time</p>';
            }
            
            $('#alertsContainer').html(html);
        } else {
            $('#alertsContainer').html('<p class="text-muted text-center">Error loading alerts</p>');
        }
    }, 'json').fail(function() {
        $('#alertsContainer').html('<p class="text-muted text-center">Failed to load alerts</p>');
    });
}

// Recent Activity Feed
function loadActivityFeed() {
    $.post('inventory_operations.php', {
        action: 'get_activity_feed'
    }, function(response) {
        if (response.success) {
            let html = '';
            
            if (response.data && response.data.length > 0) {
                // Sort by activity time descending
                response.data.sort((a, b) => new Date(b.activity_time) - new Date(a.activity_time));
                
                response.data.forEach(function(activity) {
                    let icon = activity.type === 'reservation' ? '📚' : '✅';
                    let badgeClass = activity.type === 'reservation' ? 'badge-primary' : 'badge-success';
                    
                    const activityTime = new Date(activity.activity_time);
                    const formattedTime = activityTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const formattedDate = activityTime.toLocaleDateString();
                    
                    html += `
                        <div class="activity-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <span class="badge ${badgeClass}">${icon}</span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-1">${activity.activity}</p>
                                    <small class="text-muted">${formattedDate} at ${formattedTime}</small>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                    `;
                });
            } else {
                html = '<p class="text-muted text-center">No recent activity</p>';
            }
            
            $('#activityFeed').html(html);
        } else {
            $('#activityFeed').html('<p class="text-muted text-center">Error loading activity feed</p>');
        }
    }, 'json').fail(function() {
        $('#activityFeed').html('<p class="text-muted text-center">Failed to load activity feed</p>');
    });
}

// Real-time Indicators
function loadRealtimeIndicators() {
    $.post('inventory_operations.php', {
        action: 'get_realtime_stats'
    }, function(response) {
        if (response.success) {
            const stats = response.data;
            
            let html = `
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-primary text-white p-3 rounded">
                            <h4>${stats.active_reservations}</h4>
                            <p class="mb-0">Active Reservations</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-warning text-white p-3 rounded">
                            <h4>${stats.pending_returns}</h4>
                            <p class="mb-0">Pending Returns</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-success text-white p-3 rounded">
                            <h4>${stats.today_activities}</h4>
                            <p class="mb-0">Today's Activities</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-info text-white p-3 rounded">
                            <h4>${stats.available_books}</h4>
                            <p class="mb-0">Available Books</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <small class="text-muted">Last updated: ${stats.last_updated}</small>
                    </div>
                </div>
            `;
            
            $('#realtimeIndicators').html(html);
        } else {
            $('#realtimeIndicators').html('<p class="text-muted text-center">Error loading real-time indicators</p>');
        }
    }, 'json').fail(function() {
        $('#realtimeIndicators').html('<p class="text-muted text-center">Failed to load real-time indicators</p>');
    });
}

// Manual refresh functions
function refreshAlerts() {
    loadAlertsAndNotifications();
    showNotification('Alerts refreshed', 'info');
}

// Function to fetch and display stock distribution and borrowing stats (existing function)
function displayInventoryStats() {
    // Fetch data from server
    fetch('admin/fetch_inventory_stats.php' )
        .then(response => response.json())
        .then(data => {
            // Display stock distribution
            const stockDistribution = document.getElementById('stock-distribution');
            if (stockDistribution) {
                const totalBooks = data.total_books;
                const availableBooks = data.available_books;
                const borrowedBooks = data.borrowed_books;
                
                // Calculate percentages
                const availablePercent = (availableBooks / totalBooks) * 100;
                const borrowedPercent = (borrowedBooks / totalBooks) * 100;
                
                // Update stock distribution chart
                stockDistribution.innerHTML = `
                    <div class="card">
                        <div class="card-header">
                            <h5>Stock Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: ${availablePercent}%" aria-valuenow="${availablePercent}" aria-valuemin="0" aria-valuemax="100">
                                            Available: ${availableBooks}/${totalBooks}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: ${borrowedPercent}%" aria-valuenow="${borrowedPercent}" aria-valuemin="0" aria-valuemax="100">
                                            Borrowed: ${borrowedBooks}/${totalBooks}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Display borrowing stats
            const borrowingStats = document.getElementById('borrowing-stats');
            if (borrowingStats) {
                const mostBorrowed = data.most_borrowed_books;
                const recentReturns = data.recent_returns;
                
                borrowingStats.innerHTML = `
                    <div class="card">
                        <div class="card-header">
                            <h5>Borrowing Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Most Borrowed Books</h6>
                                    <ul class="list-group">
                                        ${mostBorrowed.map(book => `<li class="list-group-item">${book.title} (${book.borrow_count} times)</li>`).join('')}
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Recent Returns</h6>
                                    <ul class="list-group">
                                        ${recentReturns.map(returnItem => `<li class="list-group-item">${returnItem.title} - ${returnItem.user_name}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching inventory stats:', error);
        });
}

// Initialize stats when page loads (existing function)
document.addEventListener('DOMContentLoaded', function() {
    // Check if the elements exist before trying to populate them
    if (document.getElementById('stock-distribution') || document.getElementById('borrowing-stats')) {
        displayInventoryStats();
    }
});
