<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TreeNode from '@/Components/TreeNode.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  hierarchyNodes: Array
})

const normalize = node => ({
  id: node.id,
  name: node.name,
  email: node.email,
  children: (node.children_recursive || []).map(normalize),
})
const reportingTree = computed(() => (props.hierarchyNodes || []).map(normalize))
</script>

<template>
  <Head title="Company Hierarchy" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company Hierarchy</h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-lg shadow-sm">
          <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-200 pb-2">
            Team Structure
          </h3>
          <div v-if="reportingTree.length">
            <div class="flex flex-col items-center">
              <TreeNode v-for="n in reportingTree" :key="n.id" :node="n" />
            </div>
          </div>
          <div v-else class="text-gray-500">
            No reporting structure to display.
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
