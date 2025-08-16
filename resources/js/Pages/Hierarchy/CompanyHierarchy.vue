<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BalkanOrgChart from '@/Components/BalkanOrgChart.vue';
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  reportingNodes: Array,
  designationBasedNodes: Array
});

const activeTab = ref('reporting');
</script>

<template>
  <Head title="Company Hierarchy" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company Hierarchy</h2>
    </template>
    <div class="py-12">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

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
    </div>
  </AuthenticatedLayout>
</template>
