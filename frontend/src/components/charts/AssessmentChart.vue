<template>
  <div class="assessment-chart">
    <Bar
      :chart-options="chartOptions"
      :chart-data="chartData"
      :chart-id="chartId"
      :dataset-id-key="datasetIdKey"
      :plugins="plugins"
      :css-classes="cssClasses"
      :styles="styles"
      :width="width"
      :height="height"
    />
  </div>
</template>

<script>
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
  name: 'AssessmentChart',
  components: { Bar },
  props: {
    chartId: {
      type: String,
      default: 'bar-chart'
    },
    datasetIdKey: {
      type: String,
      default: 'label'
    },
    width: {
      type: Number,
      default: 400
    },
    height: {
      type: Number,
      default: 300
    },
    cssClasses: {
      default: '',
      type: String
    },
    styles: {
      type: Object,
      default: () => {}
    },
    plugins: {
      type: Object,
      default: () => {}
    },
    assessments: {
      type: Array,
      required: true
    }
  },
  computed: {
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          title: {
            display: true,
            text: 'Assessment Distribution'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed.y !== null) {
                  label += context.parsed.y;
                }
                return label;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Count'
            }
          },
          x: {
            title: {
              display: true,
              text: 'Assessment Type'
            }
          }
        }
      }
    },
    chartData() {
      // Count assessments by type
      const assessmentTypes = ['quiz', 'assignment', 'midterm', 'final_exam', 'other'];
      const counts = assessmentTypes.map(type => 
        this.assessments.filter(a => a.type === type).length
      );
      
      return {
        labels: assessmentTypes.map(type => type.replace('_', ' ').toUpperCase()),
        datasets: [
          {
            label: 'Count',
            backgroundColor: [
              'rgba(54, 162, 235, 0.7)',
              'rgba(75, 192, 192, 0.7)',
              'rgba(255, 206, 86, 0.7)',
              'rgba(255, 99, 132, 0.7)',
              'rgba(153, 102, 255, 0.7)'
            ],
            data: counts
          }
        ]
      }
    }
  }
}
</script>
