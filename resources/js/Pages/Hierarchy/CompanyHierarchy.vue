<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BalkanOrgChart from '@/Components/BalkanOrgChart.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reportingNodes: Array,
    designationBasedNodes: Array,
});

const activeTab = ref('reporting');
const isFullscreen = ref(false);

// CORRECT
const chartConfig = {
    searchInput: {
        text: 'Search',
    },
};
function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
}
</script>

<template>
    <Head title="Company Hierarchy" />

    <div>
        <!-- Normal mode with AuthenticatedLayout and sticky header -->
        <AuthenticatedLayout>
            <template #header>
                <!-- Header is now simplified as requested by the new layout -->
                <div class="authenticated-layout-header">
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-800"
                    >
                        Company Hierarchy
                    </h2>
                </div>
            </template>

            <div class="hierarchy-container">
                <div
                    class="relative overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex" aria-label="Tabs">
                            <button
                                @click="activeTab = 'reporting'"
                                :class="[
                                    activeTab === 'reporting'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                ]"
                                class="w-1/2 border-b-2 px-1 py-4 text-center text-sm font-medium transition-colors duration-200 focus:outline-none"
                            >
                                Reporting Structure
                            </button>
                            <button
                                @click="activeTab = 'designation'"
                                :class="[
                                    activeTab === 'designation'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                ]"
                                class="w-1/2 border-b-2 px-1 py-4 text-center text-sm font-medium transition-colors duration-200 focus:outline-none"
                            >
                                Designation Hierarchy
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Fullscreen button moved inside the content area -->
                        <button
                            v-if="!isFullscreen"
                            @click="toggleFullscreen"
                            class="mb-4 rounded bg-indigo-600 px-3 py-1 text-sm text-white hover:bg-indigo-700"
                        >
                            Enter Fullscreen
                        </button>

                        <div v-show="activeTab === 'reporting'">
                            <div
                                v-if="
                                    props.reportingNodes &&
                                    props.reportingNodes.length > 0
                                "
                            >
                                <!-- 2. Pass the configuration object to the component -->
                                <BalkanOrgChart
                                    :nodes="props.reportingNodes"
                                    :config="chartConfig"
                                />
                            </div>
                            <div v-else class="py-10 text-center text-gray-500">
                                No reporting structure to display.
                            </div>
                        </div>

                        <div v-show="activeTab === 'designation'">
                            <div
                                v-if="
                                    props.designationBasedNodes &&
                                    props.designationBasedNodes.length > 0
                                "
                            >
                                <!-- 2. Pass the configuration object to the component -->
                                <BalkanOrgChart
                                    :nodes="props.designationBasedNodes"
                                    :config="chartConfig"
                                />
                            </div>
                            <div v-else class="py-10 text-center text-gray-500">
                                No designation hierarchy to display.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>

        <!-- Fullscreen mode: full viewport, no AuthenticatedLayout -->
        <div
            v-if="isFullscreen"
            class="fixed inset-0 z-50 overflow-auto bg-gradient-to-br from-blue-100 to-blue-300 p-6"
        >
            <button
                v-if="isFullscreen"
                @click="toggleFullscreen"
                class="absolute right-4 top-4 z-50 rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
            >
                Exit Fullscreen
            </button>

            <h2 class="mb-4 text-xl font-semibold text-gray-800">
                Company Hierarchy (Fullscreen)
            </h2>

            <div class="mb-4 border-b border-gray-200">
                <nav class="-mb-px flex" aria-label="Tabs">
                    <button
                        @click="activeTab = 'reporting'"
                        :class="[
                            activeTab === 'reporting'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        ]"
                        class="w-1/2 border-b-2 px-1 py-4 text-center text-sm font-medium transition-colors duration-200 focus:outline-none"
                    >
                        Reporting Structure
                    </button>
                    <button
                        @click="activeTab = 'designation'"
                        :class="[
                            activeTab === 'designation'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        ]"
                        class="w-1/2 border-b-2 px-1 py-4 text-center text-sm font-medium transition-colors duration-200 focus:outline-none"
                    >
                        Designation Hierarchy
                    </button>
                </nav>
            </div>

            <div>
                <div v-show="activeTab === 'reporting'">
                    <div
                        v-if="
                            props.reportingNodes &&
                            props.reportingNodes.length > 0
                        "
                    >
                        <BalkanOrgChart
                            :nodes="props.reportingNodes"
                            :config="chartConfig"
                        />
                    </div>
                    <div v-else class="py-10 text-center text-gray-500">
                        No reporting structure to display.
                    </div>
                </div>

                <div v-show="activeTab === 'designation'">
                    <div
                        v-if="
                            props.designationBasedNodes &&
                            props.designationBasedNodes.length > 0
                        "
                    >
                        <BalkanOrgChart
                            :nodes="props.designationBasedNodes"
                            :config="chartConfig"
                        />
                    </div>
                    <div v-else class="py-10 text-center text-gray-500">
                        No designation hierarchy to display.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.authenticated-layout-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: white;
    box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
}

.hierarchy-container {
    padding-top: 70px; /* adjust if header height changes */
    overflow-y: auto;
    max-height: calc(100vh - 70px);
    position: relative;
}

.boc-input > label {
    font-size: 0 !important;
    color: transparent;
}

/* Creates a new pseudo-element and inserts the correct text */
.boc-input > label::after {
    content: 'Search'; /* This is your new placeholder text */
    font-size: 16px; /* Adjust this to match the original font size */
    color: #a0aec0; /* A standard placeholder gray color */
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none; /* Allows clicks to pass through to the input */
}

/* --- The rest of your existing styles --- */
.chart-container-glass {
    width: 100%;
    height: 100%;
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
</style>
