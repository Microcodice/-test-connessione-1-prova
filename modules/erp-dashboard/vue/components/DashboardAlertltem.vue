<template>
    <div class="erp-alert-item">
      <div class="erp-alert-icon" :class="typeClass">
        <i class="material-icons">{{ typeIcon }}</i>
      </div>
      <div class="erp-alert-content">
        <div class="erp-alert-title">{{ title }}</div>
        <div class="erp-alert-desc">{{ description }}</div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'DashboardAlertItem',
    props: {
      title: {
        type: String,
        required: true
      },
      description: {
        type: String,
        default: ''
      },
      type: {
        type: String,
        default: 'info',
        validator: (val) => ['info', 'warning', 'danger', 'success'].includes(val)
      }
    },
    computed: {
      typeIcon() {
        const icons = {
          info: 'info',
          warning: 'warning_amber',
          danger: 'error',
          success: 'check_circle'
        };
        return icons[this.type] || 'info';
      },
      typeClass() {
        return `alert-type-${this.type}`;
      }
    }
  }
  </script>
  
  <style scoped>
  .erp-alert-item {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
  }
  
  .erp-alert-item:last-child {
    border-bottom: none;
  }
  
  .erp-alert-icon {
    margin-right: 15px;
    display: flex;
    align-items: flex-start;
    padding-top: 2px;
  }
  
  .alert-type-info {
    color: #3f8cff;
  }
  
  .alert-type-warning {
    color: #ff9f43;
  }
  
  .alert-type-danger {
    color: #ea5455;
  }
  
  .alert-type-success {
    color: #28c76f;
  }
  
  .erp-alert-content {
    flex: 1;
  }
  
  .erp-alert-title {
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
  }
  
  .erp-alert-desc {
    font-size: 13px;
    color: #6e6b7b;
  }
  </style>