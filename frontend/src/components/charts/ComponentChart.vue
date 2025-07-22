<template>
  <div>
    <canvas :ref="canvasRef" :id="chartId" :height="height"></canvas>
  </div>
</template>

<script>
import Chart from 'chart.js/auto'

export default {
  name: 'ComponentChart',
  props: {
    components: {
      type: Array,
      default: () => []
    },
    height: {
      type: Number,
      default: 300
    },
    chartId: {
      type: String,
      default: () => `component-chart-${Math.random().toString(36).substr(2, 9)}`
    }
  },
  data() {
    return {
      chart: null,
      canvasRef: 'chartCanvas'
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.createChart()
    })
  },
  watch: {
    components: {
      handler() {
        if (this.chart) {
          this.updateChart()
        }
      },
      deep: true
    }
  },
  beforeUnmount() {
    this.destroyChart()
  },
  methods: {
    destroyChart() {
      if (this.chart) {
        this.chart.destroy()
        this.chart = null
      }
    },
    createChart() {
      // Ensure canvas exists and is not already used
      if (!this.$refs.chartCanvas) {
        console.warn('Canvas ref not available for ComponentChart')
        return
      }

      // Destroy existing chart if any
      this.destroyChart()
      
      const ctx = this.$refs.chartCanvas.getContext('2d')
      
      const data = {
        labels: this.components.map(comp => comp.name),
        datasets: [{
          label: 'Component Weightage',
          data: this.components.map(comp => comp.weightage),
          backgroundColor: [
            '#28a745', // Assignment - Green
            '#17a2b8', // Quiz - Cyan
            '#ffc107', // Test - Yellow
            '#dc3545'  // Final Exam - Red
          ],
          borderColor: [
            '#1e7e34',
            '#138496',
            '#e0a800',
            '#c82333'
          ],
          borderWidth: 2
        }]
      }

      const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Standard Component Distribution',
            font: {
              size: 16,
              weight: 'bold'
            }
          },
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `${context.label}: ${context.parsed}%`
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 35,
            ticks: {
              callback: function(value) {
                return value + '%'
              }
            },
            title: {
              display: true,
              text: 'Weightage (%)'
            }
          },
          x: {
            title: {
              display: true,
              text: 'Component Types'
            }
          }
        }
      }

      this.chart = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: options
      })
    },
    updateChart() {
      if (this.chart && this.$refs.chartCanvas) {
        this.chart.data.labels = this.components.map(comp => comp.name)
        this.chart.data.datasets[0].data = this.components.map(comp => comp.weightage)
        this.chart.update()
      } else if (!this.chart && this.$refs.chartCanvas) {
        // Recreate chart if it was destroyed but canvas is available
        this.createChart()
      }
    }
  }
}
</script>

<style scoped>
canvas {
  max-height: 100%;
}
</style>
