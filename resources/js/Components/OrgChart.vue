<script setup>
import { onMounted, ref } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';

const props = defineProps({
    nodes: {
        type: Array,
        required: true,
    },
});

const chartContainer = ref(null);

onMounted(() => {
    if (chartContainer.value && props.nodes.length) {
        // Define custom templates and styles
        OrgChart.templates.rony.field_1 =
            '<text class="field_1" style="font-size: 14px;" fill="#a3a3a3" x="125" y="100" text-anchor="middle">{val}</text>';

        const chart = new OrgChart(chartContainer.value, {
            nodes: props.nodes,
            // Use a modern, built-in template
            template: 'rony',
            // Enable interactive features
            mouseScrool: OrgChart.action.zoom,
            enableDragDrop: true,
            // Map your data fields to the node elements
            nodeBinding: {
                field_0: 'name',
                field_1: 'title',
                img_0: 'image',
            },
            // Define custom styles for different tags
            tags: {
                'ceo-style': {
                    template: 'olivia', // A special template for the top leader
                },
                'manager-style': {
                    // A blue theme for managers
                    nodeBGColor: '#039BE5',
                },
                'tech-style': {
                    // A green theme for tech team
                    nodeBGColor: '#4CAF50',
                },
                'hr-team': {
                    // A deep purple for HR
                    nodeBGColor: '#673AB7',
                },
            },
        });
    }
});
</script>

<template>
    <!-- The chart needs a container with a defined height to render correctly -->
    <div ref="chartContainer" class="h-[75vh] w-full"></div>
</template>
