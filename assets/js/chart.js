import Chart from 'chart.js/auto';
import stc from "string-to-color";

import {initOpenChart} from './openChart'

function initChart() {
    var ctx = document.querySelectorAll('.chartjs-render-field');

    ctx.forEach(function (e) {
        var background = [];
        JSON.parse(e.dataset.labels).forEach(function (c) {
            background.push(stc(c));
        })
        const myChart = new Chart(e, {
            type: 'bar',
            data: {
                labels: JSON.parse(e.dataset.labels),
                datasets: [{
                    label: e.dataset.label,
                    data: JSON.parse(e.dataset.values),
                    backgroundColor: background,
                    borderColor: background,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        text: '%',
                        ticks: {
                            callback: function (value, index, ticks) {
                                return value + '%';
                            }
                        }

                    }
                },

                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            boxWidth: 0,
                        }

                    }
                }

            }
        });
    })

    initOpenChart();
}

export {initChart}
