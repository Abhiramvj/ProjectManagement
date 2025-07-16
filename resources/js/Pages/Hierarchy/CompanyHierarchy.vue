<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TreeNode from '@/Components/TreeNode.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  hierarchyNodes: Array,
  leaveFlowNodes: Array,
  directManager: Object, // The user's direct manager, passed from the controller
});

// Recursive function to normalize node data for the TreeNode component.
const normalize = (node) => ({
  id: node.id,
  name: node.name,
  email: node.email,
  children: (node.children_recursive || []).map(normalize),
});

// Processes the standard reporting hierarchy data.
const reportingTree = computed(() =>
  props.hierarchyNodes.map(normalize)
);

// Processes the leave approval flowchart data.
const leaveFlowTree = computed(() =>
  props.leaveFlowNodes.map(normalize)
);
</script>

<template>
  <Head title="Company & Leave Hierarchy" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company & Leave Hierarchy</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

          <!-- ========== Column 1: Standard Reporting Structure ========== -->
          <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-200 pb-2">
              Team Structure
            </h3>

            <!-- UPDATED: Display team lead/manager info using the new prop -->
            <div v-if="directManager" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md text-sm">
              <strong>Your Team Lead:</strong>
              <span class="font-medium text-indigo-600 ml-2">{{ directManager.name }}</span>
            </div>

            <!-- Render the reporting hierarchy tree (this now shows the full team) -->
            <div v-if="reportingTree.length">
              <TreeNode
                v-for="node in reportingTree"
                :key="node.id"
                :node="node"
              />
            </div>
            <div v-else class="text-gray-500">
              No reporting structure to display.
            </div>
          </div>

          <!-- ========== Column 2: Leave Approval Flowchart (No changes here) ========== -->
          <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-200 pb-2">
              Leave Approval Flow
            </h3>

            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm">
              <p>For all leave applications, employees report directly to HR.</p>
            </div>

            <div v-if="leaveFlowTree.length">
              <TreeNode
                v-for="node in leaveFlowTree"
                :key="node.id"
                :node="node"
              />
            </div>
            <div v-else class="text-gray-500">
              No leave approval flow to display.
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
