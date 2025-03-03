  function fetchChartData(chartId, type, label, bgColor) {
            fetch(`../admin/analytics.php?type=${type}`)
                .then(response => response.json())
                .then(data => {
                    const labels = data.map(row => Object.values(row)[0]);
                    const values = data.map(row => Object.values(row)[1]);

                    new Chart(document.getElementById(chartId), {

                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: label,
                                data: values,
                                backgroundColor: bgColor,
                                borderColor: bgColor.replace('0.6', '1'),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    display: false
                                },
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                                tooltip: {
                                    enabled: true
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        fetchChartData('monthlyReservationsChart', 'monthly_reservations', 'Monthly Reservations', 'rgba(176, 199, 199, 0.6)');
        fetchChartData('stockLevelsChart', 'stock_levels', 'Stock Levels', 'rgba(255, 99, 132, 0.6)');
        fetchChartData('userActivityChart', 'user_activity', 'User Activity', 'rgba(153, 102, 255, 0.6)');
        fetchChartData('topBorrowedBooksChart', 'top_borrowed_books', 'Top Borrowed Books', 'rgba(255, 206, 86, 0.6)');