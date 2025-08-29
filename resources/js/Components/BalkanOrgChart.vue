// In BalkanOrgChart.vue

<script setup>
import { onMounted, ref, watch, defineProps } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';

// --- FIX 1: Simplified props. We only need 'nodes'. ---
const props = defineProps({
    nodes: {
        type: Array,
        required: true,
    },
});

const selectedEmployee = ref(null);
const performanceData = ref(null);
const chartContainer = ref(null);
let chartInstance = null;

// Node templates remain the same...
OrgChart.templates.templateGlass = Object.assign({}, OrgChart.templates.base);
OrgChart.templates.templateGlass.size = [250, 120];
OrgChart.templates.templateGlass.node =
    '<rect x="0" y="0" width="250" height="120" rx="12" ry="12"></rect>';
OrgChart.templates.templateGlass.img_0 =
    '<circle cx="45" cy="60" r="32" fill="none" stroke="{binding.color}" stroke-width="2.5"></circle>' +
    '<clipPath id="clip-glass"><circle cx="45" cy="60" r="30"></circle></clipPath>' +
    '<image preserveAspectRatio="xMidYMid slice" clip-path="url(#clip-glass)" xlink:href="{val}" x="15" y="30" width="60" height="60"></image>';
OrgChart.templates.templateGlass.field_0 =
    '<foreignObject x="95" y="35" width="145" height="40">' +
    '<div xmlns="http://www.w3.org/1999/xhtml" style="font-size: 16px; font-weight: 600; color: #ffffff; white-space: normal; word-wrap: break-word; line-height: 1.25;">' +
    '{val}' +
    '</div>' +
    '</foreignObject>';
OrgChart.templates.templateGlass.field_1 =
    '<foreignObject x="95" y="75" width="145" height="35">' +
    '<div xmlns="http://www.w3.org/1999/xhtml" style="font-size: 12px; font-weight: 400; color: #e5e7eb; white-space: normal; word-wrap: break-word; line-height: 1.25;">' +
    '{val}' +
    '</div>' +
    '</foreignObject>';

const initializeChart = () => {
    if (chartContainer.value && props.nodes.length) {
        if (chartInstance) chartInstance.destroy();

        chartInstance = new OrgChart(chartContainer.value, {
            // All your chart settings
            nodes: props.nodes,
            template: 'templateGlass',
            nodeMouseClick: OrgChart.action.none,
            mouseScrool: OrgChart.action.zoom,
            layout: OrgChart.mixed,
            nodeSeparation: 60,
            levelSeparation: 80,
            subtreeSeparation: 70,
            connector: { type: 'curved', color: 'rgba(255, 255, 255, 0.4)' },
            navigator: {
                enabled: true,
                width: 200,
                height: 120,
                position: 'bottom-right',
                header: 'Navigator',
            },
            nodeMenu: {
                performance: {
                    text: 'Performance',
                    icon: OrgChart.icon.details(18, 18, '#fff'),
                    onClick: (nodeId) => {
                        const nodeData = chartInstance.get(nodeId);
                        selectedEmployee.value = nodeData;
                        performanceData.value =
                            nodeData.performance_summary || null;
                    },
                },
            },
            tags: {
                manager: { layout: OrgChart.tree },
                all: { 'node-class': 'glass-node' },
            },
            nodeBinding: {
                field_0: 'name',
                field_1: 'title',
                img_0: 'image',
                color: 'color',
                id: 'id',
                email: 'email',
                hire_date: 'hire_date',
                total_experience: 'total_experience',
                canViewPerformance: 'canViewPerformance',
                performance_summary: 'performance_summary',
            },

            // The onInit function to change the placeholder text
            onInit: function (sender) {
                const searchInput =
                    sender.element.querySelector('.boc-search input');
                if (searchInput) {
                    searchInput.placeholder = 'Search';
                }
            },
        }); // --- FIX 2: Corrected the closing brackets here. The extra '}),' has been removed.

        chartInstance.on('field', function (sender, args) {
            if (args.name == 'menu') {
                if (!args.data.canViewPerformance) {
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

        <div
            v-if="selectedEmployee"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
            @click="closeModal"
        >
            <div
                class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                @click.stop
            >
                <button
                    @click="closeModal"
                    class="absolute right-3 top-2 text-3xl font-light text-gray-500 hover:text-gray-800"
                >
                    &times;
                </button>

                <div class="mb-4 flex items-center border-b pb-4">
                    <img
                        :src="selectedEmployee.image"
                        alt="Photo"
                        class="mr-4 h-20 w-20 rounded-full border-2 border-gray-200"
                    />
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            {{ selectedEmployee.name }}
                        </h2>
                        <p class="text-md text-gray-600">
                            {{ selectedEmployee.title }}
                        </p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <p v-if="selectedEmployee.canViewPerformance">
                        <strong
                            class="inline-block w-32 font-semibold text-gray-700"
                            >Employee ID:</strong
                        >
                        <span class="text-gray-800">{{
                            selectedEmployee.id || 'N/A'
                        }}</span>
                    </p>
                    <p v-if="selectedEmployee.canViewPerformance">
                        <strong
                            class="inline-block w-32 font-semibold text-gray-700"
                            >Hire Date:</strong
                        >
                        <span class="text-gray-800">{{
                            selectedEmployee.hire_date
                                ? new Date(
                                      selectedEmployee.hire_date,
                                  ).toLocaleDateString()
                                : 'N/A'
                        }}</span>
                    </p>

                    <p>
                        <strong
                            class="inline-block w-32 font-semibold text-gray-700"
                            >Email:</strong
                        >
                        <span class="text-gray-800">{{
                            selectedEmployee.email || 'N/A'
                        }}</span>
                    </p>
                    <p>
                        <strong
                            class="inline-block w-32 font-semibold text-gray-700"
                            >Experience:</strong
                        >
                        <span class="text-gray-800">{{
                            selectedEmployee.total_experience
                                ? `${selectedEmployee.total_experience} years`
                                : 'N/A'
                        }}</span>
                    </p>
                </div>

                <div v-if="performanceData" class="mt-6 border-t pt-4">
                    <h3 class="mb-3 text-lg font-bold text-gray-800">
                        Performance Snapshot
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span class="font-semibold text-gray-700"
                                    >Task Completion</span
                                >
                                <span class="text-gray-600"
                                    >{{ performanceData.tasks_completed }} /
                                    {{ performanceData.tasks_total }}</span
                                >
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-gray-200">
                                <div
                                    class="h-2.5 rounded-full bg-indigo-600"
                                    :style="{
                                        width:
                                            getTaskProgress(performanceData) +
                                            '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 text-center">
                        <a
                            :href="`/performance/${selectedEmployee.id}`"
                            class="inline-block rounded-lg bg-indigo-600 px-6 py-2 text-white transition-colors hover:bg-indigo-700"
                        >
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
    height: 100%; /* CHANGE THIS FROM 85vh to 100% */
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
