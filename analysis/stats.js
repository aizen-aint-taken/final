document.addEventListener('DOMContentLoaded', function() {

    const loading = document.getElementById('loading');
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Poppins',
                        size: 12
                    }
                }
            }
        }
    };

    // Fetch data for all charts with error handling
    async function fetchChartData(endpoint) {
        try {
            const response = await fetch(endpoint);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            return data || []; // Return empty array if no data
        } catch (error) {
            console.error(`Error fetching data from ${endpoint}:`, error);
            return []; // Return empty array on error
        }
    }

    // Initialize all charts with proper error handling
    async function initializeCharts() {
        try {
            // Top Borrowed Books Chart
            const topBorrowedData = await fetchChartData('fetch_top_borrowed.php');
            const topBorrowedCtx = document.getElementById('topBorrowedChart')?.getContext('2d');
            if (topBorrowedCtx && topBorrowedData.length > 0) {
                new Chart(topBorrowedCtx, {
                    type: 'bar',
                    data: {
                        labels: topBorrowedData.map(item => item.title || 'Unknown'),
                        datasets: [{
                            label: 'Times Borrowed',
                            data: topBorrowedData.map(item => item.count || 0),
                            backgroundColor: 'rgba(255, 107, 107, 0.8)',
                            borderColor: 'rgba(255, 107, 107, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Book Stock Chart
            const bookStockData = await fetchChartData('fetch_book_stock.php');
            const bookStockCtx = document.getElementById('bookStockChart')?.getContext('2d');
            if (bookStockCtx && bookStockData.length > 0) {
                new Chart(bookStockCtx, {
                    type: 'pie',
                    data: {
                        labels: bookStockData.map(item => item.subject || 'Unknown'),
                        datasets: [{
                            data: bookStockData.map(item => item.count || 0),
                            backgroundColor: [
                                'rgba(78, 205, 196, 0.8)',
                                'rgba(255, 107, 107, 0.8)',
                                'rgba(69, 183, 209, 0.8)',
                                'rgba(150, 206, 180, 0.8)',
                                'rgba(108, 92, 231, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            // Reservation Status Chart
            const statusData = await fetchChartData('fetch_reservation_status.php');
            const statusCtx = document.getElementById('reservationStatusChart')?.getContext('2d');
            if (statusCtx && statusData.length > 0) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(item => item.status || 'Unknown'),
                        datasets: [{
                            data: statusData.map(item => item.count || 0),
                            backgroundColor: [
                                'rgba(69, 183, 209, 0.8)',
                                'rgba(255, 107, 107, 0.8)',
                                'rgba(78, 205, 196, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            // Monthly Trends Chart
            const trendsData = await fetchChartData('fetch_monthly_trends.php');
            const trendsCtx = document.getElementById('monthlyTrendsChart')?.getContext('2d');
            if (trendsCtx && trendsData.length > 0) {
                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: trendsData.map(item => item.month || 'Unknown'),
                        datasets: [{
                            label: 'Borrowed Books',
                            data: trendsData.map(item => item.count || 0),
                            borderColor: 'rgba(150, 206, 180, 1)',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(150, 206, 180, 0.2)'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Borrow Stats Chart
            const borrowStatsData = await fetchChartData('fetch_borrow_stats.php');
            const borrowStatsCtx = document.getElementById('borrowStatsChart')?.getContext('2d');
            if (borrowStatsCtx && borrowStatsData.length > 0) {
                new Chart(borrowStatsCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Returned', 'Currently Borrowed'],
                        datasets: [{
                            label: 'Number of Books',
                            data: [
                                borrowStatsData.returned || 0,
                                borrowStatsData.borrowed || 0
                            ],
                            backgroundColor: [
                                'rgba(108, 92, 231, 0.8)',
                                'rgba(255, 107, 107, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            loading.style.display = 'none';
        } catch (error) {
            console.error('Error initializing charts:', error);
            loading.style.display = 'none';
        }
    }

    // Initialize charts when DOM is loaded
    initializeCharts();
});
   