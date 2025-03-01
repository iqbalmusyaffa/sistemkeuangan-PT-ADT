<template>
    <CCol class="mb-3">
      <div :class="color" class="p-3 text-white text-center">
        <slot></slot>
      </div>
    </CCol>
  </template>

  <script setup>
  defineProps({
    color: {
      type: String,
      required: true
    }
  })
  </script>

  <style scoped>
  /* Add any additional styles here if needed */
  </style>
