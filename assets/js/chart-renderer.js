/**
 * Frontend Chart Renderer for AI Content Engine.
 * Initializes Chart.js charts based on data from WordPress.
 *
 * @package AI_Content_Engine
 */

(function () {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        // Check if chart data exists
        if (typeof aceChartData === 'undefined' || !Array.isArray(aceChartData) || aceChartData.length === 0) {
            return;
        }

        // Initialize each chart
        aceChartData.forEach(function (chartConfig) {
            initializeChart(chartConfig);
        });
    });

    /**
     * Initialize a single chart.
     *
     * @param {Object} chartConfig Chart configuration object.
     */
    function initializeChart(chartConfig) {
        // Find the canvas element
        const canvas = document.querySelector('[data-chart-id="' + chartConfig.id + '"]');

        if (!canvas) {
            console.warn('Chart canvas not found for ID: ' + chartConfig.id);
            return;
        }

        // Get 2D context
        const ctx = canvas.getContext('2d');

        // Prepare datasets with colors if not provided
        const datasets = chartConfig.datasets.map(function (dataset, index) {
            const colors = getChartColors();

            return {
                label: dataset.label || 'Dataset ' + (index + 1),
                data: dataset.data,
                backgroundColor: dataset.backgroundColor || colors[index % colors.length].background,
                borderColor: dataset.borderColor || colors[index % colors.length].border,
                borderWidth: 2,
                fill: chartConfig.type === 'line' ? false : true
            };
        });

        // Chart configuration
        const config = {
            type: chartConfig.type,
            data: {
                labels: chartConfig.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: getScalesConfig(chartConfig.type)
            }
        };

        // Create the chart
        try {
            new Chart(ctx, config);
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    }

    /**
     * Get scale configuration based on chart type.
     *
     * @param {string} chartType Type of chart (bar, line, pie, etc.).
     * @return {Object|undefined} Scales configuration or undefined for pie/doughnut.
     */
    function getScalesConfig(chartType) {
        // Pie and doughnut charts don't use scales
        if (chartType === 'pie' || chartType === 'doughnut' || chartType === 'polarArea') {
            return undefined;
        }

        return {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 0
                }
            }
        };
    }

    /**
     * Get predefined chart colors.
     *
     * @return {Array} Array of color objects with background and border.
     */
    function getChartColors() {
        return [
            {
                background: 'rgba(59, 130, 246, 0.6)',   // Blue
                border: 'rgba(59, 130, 246, 1)'
            },
            {
                background: 'rgba(16, 185, 129, 0.6)',   // Green
                border: 'rgba(16, 185, 129, 1)'
            },
            {
                background: 'rgba(245, 158, 11, 0.6)',   // Amber
                border: 'rgba(245, 158, 11, 1)'
            },
            {
                background: 'rgba(239, 68, 68, 0.6)',    // Red
                border: 'rgba(239, 68, 68, 1)'
            },
            {
                background: 'rgba(168, 85, 247, 0.6)',   // Purple
                border: 'rgba(168, 85, 247, 1)'
            },
            {
                background: 'rgba(236, 72, 153, 0.6)',   // Pink
                border: 'rgba(236, 72, 153, 1)'
            },
            {
                background: 'rgba(20, 184, 166, 0.6)',   // Teal
                border: 'rgba(20, 184, 166, 1)'
            },
            {
                background: 'rgba(251, 146, 60, 0.6)',   // Orange
                border: 'rgba(251, 146, 60, 1)'
            }
        ];
    }

})();
