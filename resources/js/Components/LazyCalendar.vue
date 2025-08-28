<template>
    <div class="calendar-container">
        <div v-if="loading" class="flex h-64 items-center justify-center">
            <div
                class="h-8 w-8 animate-spin rounded-full border-b-2 border-primary"
            ></div>
        </div>
        <component v-else :is="calendarComponent" v-bind="$attrs" />
    </div>
</template>

<script setup>
import { ref, onMounted, defineAsyncComponent } from 'vue';

const loading = ref(true);
const calendarComponent = ref(null);

onMounted(async () => {
    try {
        // Lazy load FullCalendar component
        calendarComponent.value = defineAsyncComponent(() =>
            import('@fullcalendar/vue3').then((module) => ({
                default: module.default,
            })),
        );
    } catch (error) {
        console.error('Error loading calendar component:', error);
    } finally {
        loading.value = false;
    }
});
</script>
