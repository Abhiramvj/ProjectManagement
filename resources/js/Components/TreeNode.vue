<script setup>
import { ref, computed } from 'vue'
const props = defineProps({ node: Object })
const isOpen = ref(true)
const hasChildren = computed(() => props.node?.children?.length > 0)
</script>

<template>
  <div class="org-tree-node">
    <!-- Node block -->
    <div class="node-block" @click="hasChildren && (isOpen = !isOpen)" :class="{ clickable: hasChildren }">
      <span v-if="hasChildren" class="toggle-icon">{{ isOpen ? '▼' : '►' }}</span>
      <span class="node-title">{{ node.name }}</span>
      <span class="node-email" v-if="node.email">({{ node.email }})</span>
    </div>
    <!-- Connector lines -->
    <div v-if="isOpen && hasChildren" class="children-wrap">
      <div class="connectors">
        <div class="vline"></div>
        <div class="hline" :style="{width: `${node.children.length * 120}px`}"></div>
      </div>
      <div class="children">
        <TreeNode
          v-for="child in node.children"
          :key="child.id"
          :node="child"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.org-tree-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  margin-bottom: 1.6rem;
}
.node-block {
  background: #e0e7ff;
  border: 1.6px solid #6366f1;
  border-radius: 8px;
  box-shadow: 0 2px 12px #6366f13c;
  padding: 6px 20px;
  font-weight: 600;
  color: #2e1065;
  text-align: center;
  cursor: default;
  position: relative;
}
.node-block.clickable { cursor: pointer; transition: box-shadow 0.2s;}
.node-block.clickable:hover { box-shadow: 0 4px 16px #818cf87c; }
.toggle-icon { margin-right: 4px; color: #6366f1; }
.node-title { color: #2d3a8c; }
.node-email { color: #334155; font-size: 0.92em; margin-left:6px; }
.children-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}
.connectors {
  height: 20px;
  width: 100%;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}
.vline {
  width: 0;
  border-left: 2px solid #6366f1;
  height: 12px;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  top: 0;
}
.hline {
  height: 0;
  border-top: 2px solid #6366f1;
  position: absolute;
  top: 12px;
  left: 50%;
  transform: translateX(-50%);
}
.children {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  width: max-content;
  gap: 32px;
  margin-top: 8px;
}
</style>
