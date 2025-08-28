<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    node: Object,
    // Add a 'layout' prop to control the orientation of children.
    // It defaults to 'horizontal' to maintain the original behavior.
    layout: {
        type: String,
        default: 'horizontal',
    },
});

// This state controls if the current node's children are visible
const isOpen = ref(true);

// A computed property to check if the node has children
const hasChildren = computed(() => props.node?.children?.length > 0);
</script>

<template>
    <!-- The root element now gets a dynamic class to switch between layouts -->
    <div class="org-tree-node" :class="`layout-${layout}`">
        <div class="node-block-wrapper">
            <div
                class="node-block"
                @click="hasChildren && (isOpen = !isOpen)"
                :class="{ clickable: hasChildren }"
            >
                <span v-if="hasChildren" class="toggle-icon">{{
                    isOpen ? '▼' : '►'
                }}</span>
                <img
                    :src="
                        node.image
                            ? `/storage/${node.image}`
                            : '/storage/defaults/default-avatar.jpg'
                    "
                    alt="Profile"
                    class="node-image"
                />
                <span class="node-title">{{ node.name }}</span>
            </div>
        </div>

        <!-- The children container is only rendered if the node is open and has children -->
        <div v-if="isOpen && hasChildren" class="children-container">
            <div class="parent-connector"></div>
            <div class="children">
                <!-- Recursively render child nodes, passing the layout prop down -->
                <TreeNode
                    v-for="child in node.children"
                    :key="child.id"
                    :node="child"
                    :layout="layout"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* --- BASE STYLES --- */
.org-tree-node {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    padding: 0 1rem;
}

.node-block-wrapper {
    position: relative;
    display: inline-block;
}

.node-block {
    display: flex;
    align-items: center;
    background: #e0e7ff;
    border: 1.6px solid #6366f1;
    border-radius: 8px;
    box-shadow: 0 2px 12px #6366f13c;
    padding: 8px 20px;
    font-weight: 600;
    color: #2e1065;
    text-align: center;
    cursor: default;
    position: relative;
    min-width: 150px;
    justify-content: center;
}
.node-block.clickable {
    cursor: pointer;
    transition: box-shadow 0.2s;
}
.node-block.clickable:hover {
    box-shadow: 0 4px 16px #818cf87c;
}
.toggle-icon {
    margin-right: 8px;
    color: #6366f1;
}
.node-title {
    color: #2d3a8c;
}
.node-image {
    width: 32px;
    height: 32px;
    border-radius: 9999px;
    object-fit: cover;
    margin-right: 8px;
    display: inline-block;
    vertical-align: middle;
    image-rendering: smooth;
    -ms-interpolation-mode: nearest-neighbor;
}

.children-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    margin-top: 20px;
}

/* --- STYLES FOR HORIZONTAL LAYOUT (Original) --- */
.layout-horizontal .parent-connector {
    width: 2px;
    height: 20px;
    background-color: #6366f1;
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
}

.layout-horizontal .children {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 2rem;
    padding-top: 20px;
    position: relative;
}
.layout-horizontal .children::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 2rem);
    height: 2px;
    background-color: #6366f1;
}
/* Hide the top horizontal bar if there's only one child */
.layout-horizontal .children .org-tree-node:only-child ~ ::before {
    display: none;
}
.layout-horizontal .children > .org-tree-node::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: 20px;
    background-color: #6366f1;
}

/*
 * STYLES FOR VERTICAL LAYOUT (New)
 */
.layout-vertical {
    /* For vertical layout, nodes should align to the start */
    align-items: flex-start;
    padding: 1rem 0;
}
.layout-vertical .children {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0; /* No gap needed for vertical stacking */
    padding-top: 0;
    padding-left: 30px; /* Space for the main vertical connector */
    position: relative;
}
/* This is the main vertical line connecting all children */
.layout-vertical .children::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 2px;
    height: 100%;
    background-color: #6366f1;
    transform: none;
}
/* This is the line coming DOWN from the parent node */
.layout-vertical .parent-connector {
    width: 50%;
    height: 2px;
    background-color: #6366f1;
    position: absolute;
    top: -20px;
    left: 0;
    transform: none;
}
/* This adds the vertical segment to the parent connector line */
.layout-vertical .parent-connector::after {
    content: '';
    position: absolute;
    left: 100%;
    top: 0;
    width: 2px;
    height: 20px;
    background-color: #6366f1;
}
/* This is the horizontal line coming from each child node to the main vertical line */
.layout-vertical .children > .org-tree-node::before {
    content: '';
    position: absolute;
    top: 50%; /* Center vertically */
    left: -30px; /* Start from outside the node's padding */
    width: 30px;
    height: 2px;
    background-color: #6366f1;
    transform: translateY(-50%);
}
</style>
