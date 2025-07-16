<script setup>
import TreeNode from './TreeNode.vue';
import { ref, computed } from 'vue';

const props = defineProps({
  node: Object,
});

const isOpen = ref(true);

const hasChildren = computed(() => {
  return props.node?.children?.length > 0;
});
</script>

<template>
  <div class="custom-tree-node">
    <div
      class="node-content"
      :class="{ clickable: hasChildren }"
      @click="hasChildren && (isOpen = !isOpen)"
    >
      <span v-if="hasChildren" class="toggle-icon">
        {{ isOpen ? '▼' : '►' }}
      </span>
      <span class="node-title">{{ node.name }}</span>
      <span class="node-email" v-if="node.email">({{ node.email }})</span>
    </div>

    <div v-if="isOpen && hasChildren" class="children-container">
      <TreeNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
      />
    </div>
  </div>
</template>

<style scoped>
.custom-tree-node {
  margin-left: 24px;
  padding-top: 6px;
}

.node-content {
  display: flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(90deg, #f8fafc, #f1f5f9);
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 7px 18px;
  margin-bottom: 6px;
  cursor: default;
  box-shadow: 0 2px 8px #0001;
  transition: all 0.2s ease;
}

.node-content.clickable {
  cursor: pointer;
}

.node-content:hover {
  background: #e2e8f0;
  box-shadow: 0 4px 14px #67e8f950;
}

.toggle-icon {
  color: #64748b;
}

.node-title {
  color: #2563eb;
  font-weight: 600;
}

.node-email {
  color: #64748b;
  font-size: 0.93em;
}

.children-container {
  border-left: 2px dashed #e0e7ef;
  margin-left: 12px;
  padding-left: 18px;
}
</style>
