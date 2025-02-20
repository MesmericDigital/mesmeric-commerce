<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Performance Monitoring</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- Page Load Times -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Average Page Load Time</h2>
        <div class="text-3xl font-bold text-blue-600">
          {{ averagePageLoadTime.toFixed(2) }}s
        </div>
      </div>

      <!-- Slow Queries -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Slow Queries (24h)</h2>
        <div class="text-3xl font-bold text-amber-600">
          {{ slowQueriesCount }}
        </div>
      </div>
    </div>

    <!-- Performance Graph -->
    <div class="mt-8 bg-white p-4 rounded-lg shadow">
      <h2 class="text-lg font-semibold mb-4">Page Load Times (Last 24 Hours)</h2>
      <canvas ref="chartCanvas"></canvas>
    </div>

    <!-- Slow Queries Table -->
    <div class="mt-8 bg-white p-4 rounded-lg shadow">
      <h2 class="text-lg font-semibold mb-4">Recent Slow Queries</h2>
      <table class="min-w-full">
        <thead>
          <tr>
            <th class="text-left">Timestamp</th>
            <th class="text-left">Query</th>
            <th class="text-left">Duration</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="query in slowQueries" :key="query.timestamp">
            <td>{{ formatDate(query.timestamp) }}</td>
            <td class="font-mono text-sm">{{ query.value.query }}</td>
            <td>{{ query.value.duration.toFixed(2) }}s</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Chart } from 'chart.js/auto'
import { useToast } from '../composables/MC_useToast'

const toast = useToast()
const chartCanvas = ref(null)
const metrics = ref([])

const averagePageLoadTime = computed(() => {
  const pageLoads = metrics.value
    .filter(m => m.metric === 'page_load_time')
    .map(m => m.value)

  return pageLoads.reduce((a, b) => a + b, 0) / pageLoads.length || 0
})

const slowQueriesCount = computed(() => {
  return metrics.value.filter(m => m.metric === 'slow_query').length
})

const slowQueries = computed(() => {
  return metrics.value
    .filter(m => m.metric === 'slow_query')
    .slice(0, 10) // Show only last 10 slow queries
})

const formatDate = (timestamp) => {
  return new Date(timestamp * 1000).toLocaleString()
}

const fetchMetrics = async () => {
  try {
    const response = await fetch('/wp-json/mesmeric-commerce/v1/performance/metrics')
    metrics.value = await response.json()
  } catch (error) {
    toast.error('Failed to load performance metrics')
  }
}

onMounted(async () => {
  await fetchMetrics()

  // Initialize chart
  const ctx = chartCanvas.value.getContext('2d')
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: metrics.value
        .filter(m => m.metric === 'page_load_time')
        .map(m => formatDate(m.timestamp)),
      datasets: [{
        label: 'Page Load Time (seconds)',
        data: metrics.value
          .filter(m => m.metric === 'page_load_time')
          .map(m => m.value),
        borderColor: 'rgb(59, 130, 246)',
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  })
})
</script>
