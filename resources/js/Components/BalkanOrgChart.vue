<script setup>
import { onMounted, ref, watch, defineProps } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';

// The entire <script setup> section remains unchanged.
// All the logic is already in place.

const props = defineProps({
  nodes: {
    type: Array,
    required: true,
  }
});

const selectedEmployee = ref(null);
const performanceData = ref(null); 
const chartContainer = ref(null);
let chartInstance = null;

OrgChart.templates.templateGlass = Object.assign({}, OrgChart.templates.base);
OrgChart.templates.templateGlass.size = [250, 90];
OrgChart.templates.templateGlass.node = '<rect x="0" y="0" width="250" height="90" rx="12" ry="12"></rect>';
OrgChart.templates.templateGlass.img_0 =
    '<circle cx="45" cy="45" r="32" fill="none" stroke="{binding.color}" stroke-width="2.5"></circle>' +
    '<clipPath id="clip-glass"><circle cx="45" cy="45" r="30"></circle></clipPath>' +
    '<image preserveAspectRatio="xMidYMid slice" clip-path="url(#clip-glass)" xlink:href="{val}" x="15" y="15" width="60" height="60"></image>';
OrgChart.templates.templateGlass.field_0 = '<text style="font-size: 16px; font-weight: 600;" fill="#ffffff" x="95" y="45" text-anchor="start">{val}</text>';
OrgChart.templates.templateGlass.field_1 = '<text style="font-size: 12px; font-weight: 400;" fill="#e5e7eb" x="95" y="65" text-anchor="start">{val}</text>';

const initializeChart = () => {
  if (chartContainer.value && props.nodes.length) {
    if (chartInstance) chartInstance.destroy();

    chartInstance = new OrgChart(chartContainer.value, {
      nodes: props.nodes,
      template: "templateGlass",
      nodeMouseClick: OrgChart.action.none,
      mouseScrool: OrgChart.action.zoom,
      layout: OrgChart.mixed,
      connector: { type: "curved", color: "rgba(255, 255, 255, 0.4)" },
      navigator: { enabled: true, width: 200, height: 120, position: "bottom-right", header: "Navigator" },
      nodeMenu: {
        performance: {
            text: "Performance",
            icon: OrgChart.icon.details(18, 18, '#fff'),
            onClick: (nodeId) => {
                const nodeData = chartInstance.get(nodeId);
                selectedEmployee.value = nodeData;
                performanceData.value = nodeData.performance_summary || null;
            }
        }
      },
      tags: {
        "manager": { layout: OrgChart.tree },
        "all": { "node-class": "glass-node" }
      },
      nodeBinding: {
        field_0: "name",
        field_1: "title",
        img_0: "image",
        color: "color",
        id: "id",
        email: "email",
        hire_date: "hire_date",
        total_experience_years: "total_experience_years",
        canViewPerformance: "canViewPerformance",
        performance_summary: "performance_summary"
      }
    });

    chartInstance.on('field', function(sender, args){
        if (args.name == 'menu'){
            if (!args.data.canViewPerformance){
               delete args.value.performance;
            }
        }
    });
  }
};

const closeModal = () => {
  selectedEmployee.value = null;
  performanceData.value = null;
};

onMounted(initializeChart);
watch(() => props.nodes, initializeChart, { deep: true });

const getTaskProgress = (summary) => {
    if (!summary || summary.tasks_total === 0) return 0;
    return (summary.tasks_completed / summary.tasks_total) * 100;
};
</script>

<template>
  <div class="relative">
    <div ref="chartContainer" class="chart-container-glass"></div>

    <div v-if="selectedEmployee" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="closeModal">
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md relative" @click.stop>
         <button @click="closeModal" class="absolute top-2 right-3 text-gray-500 hover:text-gray-800 text-3xl font-light">&times;</button>
          
          <div class="flex items-center border-b pb-4 mb-4">
              <img :src="selectedEmployee.image" alt="Photo" class="w-20 h-20 rounded-full mr-4 border-2 border-gray-200">
              <div>
                  <h2 class="text-2xl font-bold text-gray-800">{{ selectedEmployee.name }}</h2>
                  <p class="text-md text-gray-600">{{ selectedEmployee.title }}</p>
              </div>
          </div>
          
          <div class="space-y-2 text-sm">
              <p v-if="selectedEmployee.canViewPerformance">
                  <strong class="font-semibold text-gray-700 w-32 inline-block">Employee ID:</strong> 
                  {{ selectedEmployee.id || 'N/A' }}
              </p>
              <p v-if="selectedEmployee.canViewPerformance">
                  <strong class="font-semibold text-gray-700 w-32 inline-block">Hire Date:</strong> 
                  {{ selectedEmployee.hire_date ? new Date(selectedEmployee.hire_date).toLocaleDateString() : 'N/A' }}
              </p>

              <p><strong class="font-semibold text-gray-700 w-32 inline-block">Email:</strong> {{ selectedEmployee.email || 'N/A' }}</p>
              <p><strong class="font-semibold text-gray-700 w-32 inline-block">Experience:</strong> {{ selectedEmployee.total_experience_years ? `${selectedEmployee.total_experience_years} years` : 'N/A' }}</p>
          </div>

          <div v-if="performanceData" class="mt-6 pt-4 border-t">
              <h3 class="text-lg font-bold text-gray-800 mb-3">Performance Snapshot</h3>
              <div class="space-y-3 text-sm">
                  <div>
                      <div class="flex justify-between mb-1">
                          <span class="font-semibold text-gray-700">Task Completion</span>
                          <span class="text-gray-600">{{ performanceData.tasks_completed }} / {{ performanceData.tasks_total }}</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-2.5">
                          <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: getTaskProgress(performanceData) + '%' }"></div>
                      </div>
                  </div>
              </div>
              <div class="mt-6 text-center">
                  <a :href="`/performance/${selectedEmployee.id}`" class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                      View Full Performance Report
                  </a>
              </div>
          </div>
      </div>
    </div>
  </div>
</template>

<style>
/* All CSS is unchanged */
.chart-container-glass {
    width: 100%;
    height: 85vh;
    background: linear-gradient(135deg, #ffffff 0%, #62a5dc 100%);
    border-radius: 0.5rem;
}
.glass-node > rect {
    background-color: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    fill: rgba(255, 255, 255, 0.2);
    stroke-width: 1px;
    stroke: rgba(255, 255, 255, 0.3);
}
.boc-node.glass-node:hover > rect {
    fill: rgba(255, 255, 255, 0.3);
}
.boc-navigator {
    background-color: rgba(255, 255, 255, 0.15) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: 8px !important;
}
.boc-navigator .boc-header {
    color: rgb(255, 255, 255) !important;
}
</style>