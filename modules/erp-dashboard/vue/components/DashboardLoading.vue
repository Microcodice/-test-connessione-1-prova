<template>
    <div class="loading-container">
      <div class="spinner"></div>
      <p v-if="text">{{ text }}</p>
    </div>
  </template>
  
  <script>
  export default {
    name: 'DashboardLoading',
    props: {
      text: {
        type: String,
        default: 'Caricamento in corso...'
      }
    }
  }
  </script>
  
  <style scoped>
  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
  }
  
  .spinner {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid rgba(0, 0, 0, 0.1);
    border-top-color: #3f8cff;
    animation: spin 1s infinite linear;
    margin-bottom: 10px;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  
  p {
    color: #6e6b7b;
    font-size: 14px;
  }
  </style>