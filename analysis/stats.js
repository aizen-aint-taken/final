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
            const response = await fetch('../admin/analytics.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const allData = await response.json();
            console.log('All data:', allData); // Debug log

            // Return the appropriate data based on the endpoint requested
            switch(endpoint) {
                case 'fetch_top_borrowed.php':
                    return allData.topBorrowed || [];
                case 'fetch_book_stock.php':
                    return allData.bookStock || [];
                case 'fetch_reservation_status.php':
                    return allData.statusDistribution || [];
                case 'fetch_monthly_trends.php':
                    return allData.monthlyTrends || [];
                case 'fetch_borrow_stats.php':
                    return allData.borrowStats || {};
                default:
                    return [];
            }
        } catch (error) {
            console.error(`Error fetching data from ${endpoint}:`, error);
            return endpoint.includes('borrow_stats.php') ? {} : [];
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
                        labels: topBorrowedData.map(item => item.Title),
                        datasets: [{
                            label: 'Times Borrowed',
                            data: topBorrowedData.map(item => item.borrow_count),
                            backgroundColor: 'rgba(255, 107, 107, 0.8)',
                            borderColor: 'rgba(255, 107, 107, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        ...commonOptions,
                        indexAxis: 'y',  // This makes the chart horizontal
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            y: {
                                ticks: {
                                    font: {
                                        size: 12
                                    },
                                    callback: function(value) {
                                        // Truncate long titles if needed
                                        const title = this.getLabelForValue(value);
                                        return title.length > 30 ? title.substr(0, 27) + '...' : title;
                                    }
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
                        labels: bookStockData.map(item => item.Subject),
                        datasets: [{
                            data: bookStockData.map(item => item.total_stock),
                            backgroundColor: [
                                'rgba(78, 205, 196, 0.8)',
                                'rgba(255, 107, 107, 0.8)',
                                'rgba(69, 183, 209, 0.8)',
                                'rgba(150, 206, 180, 0.8)',
                                'rgba(108, 92, 231, 0.8)'
                            ]
                        }]
                    },
                    options: commonOptions
                });
            }

            // Reservation Status Chart
            const statusData = await fetchChartData('fetch_reservation_status.php');
            const statusCtx = document.getElementById('reservationStatusChart')?.getContext('2d');
            if (statusCtx && statusData.length > 0) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(item => item.STATUS),
                        datasets: [{
                            data: statusData.map(item => item.total),
                            backgroundColor: [
                                'rgba(69, 183, 209, 0.8)',
                                'rgba(255, 107, 107, 0.8)',
                                'rgba(78, 205, 196, 0.8)'
                            ]
                        }]
                    },
                    options: commonOptions
                });
            }

            // Monthly Trends Chart
            const trendsData = await fetchChartData('fetch_monthly_trends.php');
            const trendsCtx = document.getElementById('monthlyTrendsChart')?.getContext('2d');
            if (trendsCtx && trendsData.length > 0) {
                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: trendsData.map(item => item.month),
                        datasets: [{
                            label: 'Borrowed Books',
                            data: trendsData.map(item => item.reservation_count),
                            borderColor: 'rgba(150, 206, 180, 1)',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(150, 206, 180, 0.2)'
                        }]
                    },
                    options: commonOptions
                });
            }

            // Borrow Stats Chart
            const borrowStatsData = await fetchChartData('fetch_borrow_stats.php');
            const borrowStatsCtx = document.getElementById('borrowStatsChart')?.getContext('2d');
            if (borrowStatsCtx && borrowStatsData) {
                new Chart(borrowStatsCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Returned', 'Currently Borrowed'],
                        datasets: [{
                            label: 'Number of Books',
                            data: [
                                borrowStatsData.returned_count,
                                borrowStatsData.borrowed_count
                            ],
                            backgroundColor: [
                                'rgba(108, 92, 231, 0.8)',
                                'rgba(255, 107, 107, 0.8)'
                            ]
                        }]
                    },
                    options: commonOptions
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
   