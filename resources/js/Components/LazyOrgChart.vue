<template>
  <div class="org-chart-container">
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
    </div>
    <div v-else ref="chartContainer" class="w-full h-full"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  }
});

const loading = ref(true);
const chartContainer = ref(null);
let chart = null;

onMounted(async () => {
  try {
    // Lazy load the org chart library
    const { OrgChart } = await import('@balkangraph/orgchart.js');
    
    // Initialize the chart
    chart = new OrgChart(chartContainer.value, {
      ...props.options,
      nodes: props.data,
      nodeBinding: {
        field_0: "name",
        field_1: "title",
        img_0: "img"
      },
      nodeMenu: {
        details: { text: "Details" },
        edit: { text: "Edit" },
        add: { text: "Add" },
        remove: { text: "Remove" }
      },
      nodeContextMenu: {
        edit: { text: "Edit" },
        add: { text: "Add" },
        remove: { text: "Remove" }
      }
    });
    
    loading.value = false;
  } catch (error) {
    console.error('Error loading org chart:', error);
    loading.value = false;
  }
});

onUnmounted(() => {
  if (chart) {
    chart.destroy();
  }
});
</script> 