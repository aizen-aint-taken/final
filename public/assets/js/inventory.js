function loadImportHistory() {
    // Load import history summary
    $.post('', {
        action: 'get_import_history'
    }, function(response) {
        if (response.success) {
            let html = '';
            let totalImported = 0;
            let totalStock = 0;
            let chartData = {
                dates: [],
                booksAdded: [],
                stockAdded: []
            };

            response.data.forEach(function(import_record) {
                totalImported += parseInt(import_record.books_imported);
                totalStock += parseInt(import_record.total_stock_added);

                // Prepare data for chart
                chartData.dates.push(new Date(import_record.import_date).toLocaleDateString());
                chartData.booksAdded.push(parseInt(import_record.books_imported));
                chartData.stockAdded.push(parseInt(import_record.total_stock_added));

                const timeRange = import_record.first_import_time === import_record.last_import_time ?
                    'Single import' :
                    `${new Date(import_record.first_import_time).toLocaleTimeString()} - ${new Date(import_record.last_import_time).toLocaleTimeString()}`;

                html += `
                <tr>
                    <td>${new Date(import_record.import_date).toLocaleDateString()}</td>
                    <td><span class="badge badge-success">${import_record.books_imported}</span></td>
                    <td><span class="badge badge-info">${import_record.total_stock_added}</span></td>
                    <td><span class="badge badge-secondary">${import_record.sources}</span></td>
                    <td><small>${timeRange}</small></td>
                </tr>
            `;
            });

            $('#importHistoryTable tbody').html(html);

            // Create import history chart
            createImportHistoryChart(chartData);

            // Update import statistics
            $('#importStatsContainer').html(`
                <div class="row">
                    <div class="col-6">
                        <h3 class="text-success">${totalImported}</h3>
                        <p>Total Books Imported</p>
                    </div>
                    <div class="col-6">
                        <h3 class="text-info">${totalStock}</h3>
                        <p>Total Stock Added</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h4 class="text-primary">${response.data.length}</h4>
                        <p>Import Sessions</p>
                    </div>
                </div>
            `);
        }
    }, 'json');

    // Load books with import details
    $.post('', {
        action: 'get_books_with_import_details'
    }, function(response) {
        if (response.success) {
            let html = '';
            response.data.forEach(function(book) {
                const importDate = book.import_date ? new Date(book.import_date).toLocaleDateString() : 'N/A';
                const entryMethodClass = book.entry_method === 'Imported' ? 'badge-success' : 'badge-secondary';

                html += `
                <tr>
                    <td><span class="badge badge-light">${book.BookID}</span></td>
                    <td><strong>${book.Title}</strong></td>
                    <td>${book.Author}</td>
                    <td><span class="badge badge-info">${book.source}</span></td>
                    <td>${importDate}</td>
                    <td><span class="badge ${entryMethodClass}">${book.entry_method}</span></td>
                    <td><span class="badge badge-primary">${book.times_borrowed}x</span></td>
                    <td><span class="badge badge-warning">${book.Stock}</span></td>
                </tr>
            `;
            });

            $('#booksImportTable tbody').html(html);
        }
    }, 'json');
}

function createImportHistoryChart(chartData) {
    const ctx = document.getElementById('importHistoryChart');
    if (!ctx) return;

    // Destroy existing chart if it exists
    if (window.importHistoryChart) {
        window.importHistoryChart.destroy();
    }

    // Create new chart
    window.importHistoryChart = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: chartData.dates,
            datasets: [{
                label: 'Books Added',
                data: chartData.booksAdded,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }, {
                label: 'Stock Added',
                data: chartData.stockAdded,
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Import History Trend'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Function to create Most Borrowed Books chart
function createMostBorrowedBooksChart(data) {
    const ctx = document.getElementById('mostBorrowedChart');
    if (!ctx) return;

    // Destroy existing chart if it exists
    if (window.mostBorrowedChart) {
        window.mostBorrowedChart.destroy();
    }

    // Prepare data for chart
    const labels = [];
    const chartData = [];
    const backgroundColors = [];
    
    data.forEach(function(book, index) {
        labels.push(book.Title.length > 20 ? book.Title.substring(0, 20) + '...' : book.Title);
        chartData.push(book.borrow_count);
        
        // Different colors for each bar
        const colors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)'
        ];
        backgroundColors.push(colors[index % colors.length]);
    });

    // Create new chart
    window.mostBorrowedChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Times Borrowed',
                data: chartData,
                backgroundColor: backgroundColors,
                borderColor: backgroundColors.map(color => color.replace('0.7', '1')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Top 5 Most Borrowed Books'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

// Function to create Book Imports chart
function createBookImportsChart(data) {
    const ctx = document.getElementById('bookImportsChart');
    if (!ctx) return;

    // Destroy existing chart if it exists
    if (window.bookImportsChart) {
        window.bookImportsChart.destroy();
    }

    // Prepare data for chart
    const labels = [];
    const chartData = [];
    
    // Take only the first 5 imports for clarity
    const importsToShow = data.slice(0, 5);
    importsToShow.forEach(function(stamp) {
        const date = new Date(stamp.date).toLocaleDateString();
        labels.push(date);
        chartData.push(stamp.count);
    });

    // Create new chart
    window.bookImportsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Books Imported',
                data: chartData,
                fill: true,
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: 'rgba(40, 167, 69, 1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Recent Book Imports'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

// Function to create Library Deliveries chart
function createLibraryDeliveriesChart(data) {
    const ctx = document.getElementById('libraryDeliveriesChart');
    if (!ctx) return;

    // Destroy existing chart if it exists
    if (window.libraryDeliveriesChart) {
        window.libraryDeliveriesChart.destroy();
    }

    // Prepare data for chart
    const labels = [];
    const chartData = [];
    
    // Take only the first 5 deliveries for clarity
    const deliveriesToShow = data.slice(0, 5);
    deliveriesToShow.forEach(function(stamp) {
        const date = new Date(stamp.date).toLocaleDateString();
        labels.push(date);
        chartData.push(stamp.count);
    });

    // Create new chart
    window.libraryDeliveriesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Books Delivered',
                data: chartData,
                fill: true,
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderColor: 'rgba(0, 123, 255, 1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Recent Library Deliveries'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}