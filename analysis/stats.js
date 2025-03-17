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

    fetch('../admin/analytics.php')
        .then(response => response.json())
        .then(data => {
          
            new Chart('bookStockChart', {
                type: 'bar',
                data: {
                    labels: data.bookStock.map(item => item.Subject),
                    datasets: [{
                        label: 'Stock Count',
                        data: data.bookStock.map(item => item.total_stock),
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });

          
            new Chart('topBorrowedChart', {
                type: 'bar',
                data: {
                    labels: data.topBorrowed.map(item => item.Title),
                    datasets: [{
                        label: 'Times Borrowed',
                        data: data.topBorrowed.map(item => item.borrow_count),
                        backgroundColor: function(context) {
                            const colors = [
                                '#FF6384',
                                '#36A2EB', 
                                '#4BC0C0', 
                                '#FFCE56',
                                '#9966FF'  
                            ];
                            return colors[context.dataIndex];
                        },
                        borderColor: function(context) {
                            const colors = [
                                '#FF3868', 
                                '#2185D0', 
                                '#3AA7A7', 
                                '#FFB420', 
                                '#7C4DFF'  
                            ];
                            return colors[context.dataIndex];
                        },
                        borderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 14
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    layout: {
                        padding: {
                            left: 15,
                            right: 15,
                            top: 15,
                            bottom: 15
                        }
                    },
                    maintainAspectRatio: false,
                    responsive: true
                }
            });


            new Chart('reservationStatusChart', {
                type: 'doughnut',
                data: {
                    labels: data.statusDistribution.map(item => item.STATUS),
                    datasets: [{
                        data: data.statusDistribution.map(item => item.total),
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(243, 156, 18, 0.7)',
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(52, 152, 219, 0.7)'
                        ],
                        borderColor: [
                            'rgba(46, 204, 113, 1)',
                            'rgba(243, 156, 18, 1)',
                            'rgba(231, 76, 60, 1)',
                            'rgba(52, 152, 219, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonOptions,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    family: 'Poppins',
                                    size: 14
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            let color;
                                            switch (label) {
                                                case 'Approved':
                                                    color = 'rgba(46, 204, 113, 1)';
                                                    break;
                                                case 'Pending':
                                                    color = 'rgba(243, 156, 18, 1)';
                                                    break;
                                                case 'Rejected':
                                                    color = 'rgba(231, 76, 60, 1)';
                                                    break;
                                                case 'Returned':
                                                    color = 'rgba(52, 152, 219, 1)';
                                                    break;
                                                default:
                                                    color = data.datasets[0].backgroundColor[i];
                                            }
                                            return {
                                                text: `${label}: ${value}`,
                                                fillStyle: color,
                                                strokeStyle: color,
                                                lineWidth: 1,
                                                hidden: isNaN(value) || value === 0,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        }
                    }
                }
            });

            new Chart('monthlyTrendsChart', {
                type: 'line',
                data: {
                    labels: data.monthlyTrends.map(item => {
                        const date = new Date(item.month + '-01');
                        return date.toLocaleDateString('en-US', {
                            month: 'short',
                            year: 'numeric'
                        });
                    }),
                    datasets: [{
                        label: 'Number of Borrowed Books in Month',
                        data: data.monthlyTrends.map(item => item.reservation_count),
                        borderColor: 'rgba(155, 89, 182, 1)',
                        backgroundColor: 'rgba(155, 89, 182, 0.2)',
                        borderTop: 'rgba(155, 89, 182, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(155, 89, 182, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Poppins',
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                title: (context) => {
                                    return context[0].label;
                                },
                                label: (context) => {
                                    return `Reservations: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });

            new Chart('borrowStatsChart', {
                type: 'pie',
                data: {
                    labels: ['Returned Books', 'Currently Borrowed'],
                    datasets: [{
                        data: [
                            data.borrowStats.returned_count,
                            data.borrowStats.borrowed_count
                        ],
                        backgroundColor: [
                            '#2ecc71',  
                            '#3498db'   
                        ],
                        borderColor: [
                            '#27ae60',
                            '#2980b9'   
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    family: 'Poppins',
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            new Chart('returnedTrendsChart', {
                type: 'line',
                data: {
                    labels: data.returnedTrends.map(item => {
                        const date = new Date(item.month + '-01');
                        return date.toLocaleDateString('en-US', {
                            month: 'short',
                            year: 'numeric'
                        });
                    }),
                    datasets: [{
                        label: 'Returned Books',
                        data: data.returnedTrends.map(item => item.return_count),
                        borderColor: '#e74c3c',  // Red color
                        backgroundColor: 'rgba(231, 76, 60, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#e74c3c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Poppins',
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                title: (context) => {
                                    return context[0].label;
                                },
                                label: (context) => {
                                    return `Returned Books: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });

            loading.style.display = 'none';
        })
        .catch(error => {
            console.error('Error:', error);
            loading.style.display = 'none';
        });
});
   