<template>
    <div class="erp-stat-card">
      <div class="erp-stat-icon" :class="color">
        <i class="material-icons">{{ icon }}</i>
      </div>
      <div class="erp-stat-info">
        <div class="erp-stat-value" v-if="!loading">{{ value }}</div>
        <div class="erp-stat-value loading-pulse" v-else>---</div>
        <div class="erp-stat-label">{{ title }}</div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'DashboardStatCard',
    props: {
      title: {
        type: String,
        required: true
      },
      value: {
        type: [String, Number],
        default: '0'
      },
      icon: {
        type: String,
        default: 'insights'
      },
      color: {
        type: String,
        default: 'blue',
        validator: (val) => ['blue', 'green', 'purple', 'orange', 'red'].includes(val)
      },
      loading: {
        type: Boolean,
        default: false
      }
    }
  }
  </script>
  
  <style scoped>
  .loading-pulse {
    animation: pulse 1.5s infinite ease-in-out;
    opacity: 0.7;
  }
  
  @keyframes pulse {
    0% { opacity: 0.5; }
    50% { opacity: 1; }
    100% { opacity: 0.5; }
  }
  </style>