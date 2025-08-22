<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BalkanOrgChart from '@/Components/BalkanOrgChart.vue';
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  reportingNodes: Array,
  designationBasedNodes: Array
});

const activeTab = ref('reporting')
const isFullscreen = ref(false)

function toggleFullscreen() {
  isFullscreen.value = !isFullscreen.value
}
</script>

<template>
  <Head title="Company Hierarchy" />

  <div>
    <!-- Normal mode with AuthenticatedLayout and sticky header -->
    <AuthenticatedLayout>
      <template #header>
        <div class="authenticated-layout-header">
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company Hierarchy</h2>
        </div>
      </template>

      <div class="hierarchy-container">
        <div class="relative bg-white overflow-hidden shadow-sm sm:rounded-lg">

          <button
  v-if="!isFullscreen"
  @click="toggleFullscreen"
  class="fullscreen-toggle-button px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
>
  Enter Fullscreen
</button>

          <div class="border-b border-gray-200">
            <nav class="-mb-px flex" aria-label="Tabs">
              <button @click="activeTab = 'reporting'" :class="[activeTab === 'reporting' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200">
                Reporting Structure
              </button>
              <button @click="activeTab = 'designation'" :class="[activeTab === 'designation' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200">
                Designation Hierarchy
              </button>
            </nav>
          </div>

          <div class="p-6">
            <div v-show="activeTab === 'reporting'">
              <div v-if="props.reportingNodes && props.reportingNodes.length > 0">
                <BalkanOrgChart :nodes="props.reportingNodes" />
              </div>
              <div v-else class="text-center text-gray-500 py-10">No reporting structure to display.</div>
            </div>

            <div v-show="activeTab === 'designation'">
              <div v-if="props.designationBasedNodes && props.designationBasedNodes.length > 0">
                <BalkanOrgChart :nodes="props.designationBasedNodes" />
              </div>
              <div v-else class="text-center text-gray-500 py-10">No designation hierarchy to display.</div>
            </div>
          </div>

        </div>
      </div>

    </AuthenticatedLayout>

    <!-- Fullscreen mode: full viewport, no AuthenticatedLayout -->
    <div v-if="isFullscreen" class="fixed inset-0 z-50 bg-gradient-to-br from-blue-100 to-blue-300 overflow-auto p-6">
      <button
  v-if="isFullscreen"
  @click="toggleFullscreen"
  class="absolute top-4 right-4 z-50 px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
>
  Exit Fullscreen
</button>

      <h2 class="font-semibold text-xl text-gray-800 mb-4">Company Hierarchy (Fullscreen)</h2>

      <div class="border-b border-gray-200 mb-4">
        <nav class="-mb-px flex" aria-label="Tabs">
          <button @click="activeTab = 'reporting'" :class="[activeTab === 'reporting' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200">
            Reporting Structure
          </button>
          <button @click="activeTab = 'designation'" :class="[activeTab === 'designation' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200">
            Designation Hierarchy
          </button>
        </nav>
      </div>

      <div>
        <div v-show="activeTab === 'reporting'">
          <div v-if="props.reportingNodes && props.reportingNodes.length > 0">
            <BalkanOrgChart :nodes="props.reportingNodes" />
          </div>
          <div v-else class="text-center text-gray-500 py-10">No reporting structure to display.</div>
        </div>

        <div v-show="activeTab === 'designation'">
          <div v-if="props.designationBasedNodes && props.designationBasedNodes.length > 0">
            <BalkanOrgChart :nodes="props.designationBasedNodes" />
          </div>
          <div v-else class="text-center text-gray-500 py-10">No designation hierarchy to display.</div>
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

.fullscreen-toggle-button {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 60;
}
</style>
