<template>
    <div class="chart-container">
        <div v-if="loading" class="flex h-64 items-center justify-center">
            <div
                class="h-8 w-8 animate-spin rounded-full border-b-2 border-primary"
            ></div>
        </div>
        <component v-else :is="chartComponent" v-bind="$attrs" />
    </div>
</template>

<script setup>
import { ref, onMounted, defineAsyncComponent } from 'vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) =>
            ['doughnut', 'bar', 'line', 'pie'].includes(value),
    },
});

const loading = ref(true);
const chartComponent = ref(null);

onMounted(async () => {
    try {
        // Lazy load chart components based on type
        switch (props.type) {
            case 'doughnut':
                chartComponent.value = defineAsyncComponent(() =>
                    import('vue-chartjs').then((module) => ({
                        default: module.Doughnut,
                    })),
                );
                break;
            case 'bar':
                chartComponent.value = defineAsyncComponent(() =>
                    import('vue-chartjs').then((module) => ({
                        default: module.Bar,
                    })),
                );
                break;
            case 'line':
                chartComponent.value = defineAsyncComponent(() =>
                    import('vue-chartjs').then((module) => ({
                        default: module.Line,
                    })),
                );
                break;
            case 'pie':
                chartComponent.value = defineAsyncComponent(() =>
                    import('vue-chartjs').then((module) => ({
                        default: module.Pie,
                    })),
                );
                break;
        }
    } catch (error) {
        console.error('Error loading chart component:', error);
    } finally {
        loading.value = false;
    }
});
</script>
