<script setup>
import { onMounted, ref, watch } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';

// This component expects a 'nodes' prop, which will be provided by your controller.
const props = defineProps({
  nodes: {
    type: Array,
    required: true,
  }
});

const selectedEmployee = ref(null);
const chartContainer = ref(null);
let chartInstance = null;

// --- NEW DESIGN: Employee Node Template ("userNodeV2") ---
// This template features a colored sidebar, better text alignment, and a more prominent image.
OrgChart.templates.userNodeV2 = Object.assign({}, OrgChart.templates.base);
OrgChart.templates.userNodeV2.size = [250, 80]; // Wider and shorter
OrgChart.templates.userNodeV2.node =
    '<rect x="0" y="0" width="250" height="80" fill="#ffffff" rx="6" ry="6"></rect>' +
    // This is the colored vertical sidebar. Its fill is bound to the 'color' data field.
    '<rect x="0" y="0" width="7" height="80" fill="{binding.color}" rx="6" ry="0"></rect>';

// Name (field_0): Larger font, left-aligned next to the sidebar.
OrgChart.templates.userNodeV2.field_0 = '<text style="font-size: 16px; font-weight: 600;" fill="#1f2937" x="20" y="35" text-anchor="start">{val}</text>';
// Title/Designation (field_1): Smaller font, below the name.
OrgChart.templates.userNodeV2.field_1 = '<text style="font-size: 12px;" fill="#6b7280" x="20" y="55" text-anchor="start">{val}</text>';

// Image (img_0): Positioned on the right side, inside a circular clip path.
OrgChart.templates.userNodeV2.img_0 =
    '<clipPath id="clip-circle-v2"><circle cx="210" cy="40" r="28"></circle></clipPath>' +
    '<image preserveAspectRatio="xMidYMid slice" clip-path="url(#clip-circle-v2)" xlink:href="{val}" x="182" y="12" width="56" height="56"></image>';

OrgChart.templates.userNodeV2.nodeBinding = {
    field_0: "name",
    field_1: "title",
    img_0: "image",
    color: "color"
};


// --- NEW DESIGN: Group/Designation Node Template ("designationNodeV2") ---
// A matching compact design for the group boxes.
OrgChart.templates.designationNodeV2 = Object.assign({}, OrgChart.templates.base);
OrgChart.templates.designationNodeV2.size = [250, 50];
OrgChart.templates.designationNodeV2.node =
    '<rect x="0" y="0" width="250" height="50" fill="#ffffff" rx="6" ry="6"></rect>' +
    // Consistent colored vertical sidebar.
    '<rect x="0" y="0" width="7" height="50" fill="{binding.color}" rx="6" ry="0"></rect>' +
    // An icon (SVG path for a "users" group) for a professional look.
    '<path fill="#9ca3af" transform="translate(20,15) scale(1.2)" d="M12,12A4,4 0 0,0 8,16A4,4 0 0,0 12,20A4,4 0 0,0 16,16A4,4 0 0,0 12,12M12,14A2,2 0 0,1 14,16A2,2 0 0,1 12,18A2,2 0 0,1 10,16A2,2 0 0,1 12,14M20,12C22.21,12 24,13.79 24,16V17H22V16C22,14.9 21.1,14 20,14M18,12C16.5,12 15.25,12.59 14.37,13.5C14.74,14.22 15,15.06 15,16V17H4V16C4,13.79 5.79,12 8,12M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,6A2,2 0 0,0 10,8A2,2 0 0,0 12,10A2,2 0 0,0 14,8A2,2 0 0,0 12,6Z"></path>';

// Designation Name (field_0): Positioned to the right of the icon.
OrgChart.templates.designationNodeV2.field_0 = '<text style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;" fill="#374151" x="55" y="30" text-anchor="start">{val}</text>';

OrgChart.templates.designationNodeV2.nodeBinding = {
    field_0: "name",
    color: "color"
};


// --- Chart Initialization and Logic ---
const initializeChart = () => {
  if (chartContainer.value && props.nodes.length) {
    if (chartInstance) {
      chartInstance.destroy();
    }

    chartInstance = new OrgChart(chartContainer.value, {
      nodes: props.nodes,
      // Use our new template as the default for employee nodes.
      template: "userNodeV2",
      // Add a CSS class to all nodes for styling (like shadows).
      nodeMenu: {},
      nodeMouseClick: OrgChart.action.none, // We handle click via event listener
      enableSearch: false,
      mouseScrool: OrgChart.action.zoom,
      connector: {
          type: "curved",
          color: "#E5E7EB" // A lighter gray for connectors
      },
      levelSeparation: 100, // More vertical space
      siblingSeparation: 40, // More horizontal space

      tags: {
        // Tag to apply the new group node template.
        "department": {
          template: "designationNodeV2",
          // Add a custom CSS class to group nodes if needed.
          "node-class": "department-node-shadow"
        },
        // A generic class for all user nodes
        "user": {
          "node-class": "user-node-shadow"
        }
      },
      nodeBinding: {
        field_0: "name",
        field_1: "title",
        img_0: "image",
        color: "color"
      }
    });

    // Event listener for clicks to open the modal.
    chartInstance.on('click', (sender, args) => {
      const nodeData = args.node.data;
      if (nodeData.image) { // Only open for nodes with an image (employees)
        selectedEmployee.value = nodeData;
      }
    });
  }
};

const closeModal = () => {
  selectedEmployee.value = null;
};

onMounted(() => {
  initializeChart();
});

watch(() => props.nodes, () => {
  initializeChart();
}, { deep: true });

</script>

<template>
  <div class="relative">
    <div ref="chartContainer" class="w-full h-[85vh] chart-container bg-gray-50"></div>

    <!-- The Modal (no changes needed here, it will work as is) -->
    <div v-if="selectedEmployee" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <!-- Modal content is identical to the previous version -->
        <button @click="closeModal" class="modal-close-button">×</button>
        <div class="modal-header">
          <img :src="selectedEmployee.image" alt="Employee photo" class="modal-image">
          <div>
            <h2 class="modal-name">{{ selectedEmployee.name }}</h2>
            <p class="modal-title">{{ selectedEmployee.title }}</p>
          </div>
        </div>
        <div class="modal-body">
          <h3 class="details-heading">Employee Details</h3>
          <p><strong>Employee ID:</strong> {{ selectedEmployee.employee_id || 'N/A' }}</p>
          <p><strong>Reports to (Dept):</strong> {{ selectedEmployee.department || 'N/A' }}</p>
          <p><strong>Email:</strong> {{ selectedEmployee.email || 'N/A' }}</p>
          <p><strong>Hire Date:</strong> {{ selectedEmployee.hire_date ? new Date(selectedEmployee.hire_date).toLocaleDateString() : 'N/A' }}</p>
          <p><strong>Total Experience:</strong> {{ selectedEmployee.total_experience_years ? `${selectedEmployee.total_experience_years} years` : 'N/A' }}</p>
        </div>
        <div class="modal-footer">
          <a :href="`/performance/${selectedEmployee.id}`" class="performance-link">
            View Performance Page
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* --- NEW Styles for the Chart Nodes --- */
.user-node-shadow > rect, .department-node-shadow > rect {
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));
}
.boc-node:hover > rect {
    filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));
}

/* --- Modal Styles (unchanged but included for completeness) --- */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  transition: opacity 0.3s ease;
}
.modal-content {
  background: white;
  padding: 24px;
  border-radius: 8px;
  width: 90%;
  max-width: 450px;
  position: relative;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  transform: scale(0.95);
  opacity: 0;
  animation: fadeInScale 0.3s forwards;
}
@keyframes fadeInScale {
  to {
    transform: scale(1);
    opacity: 1;
  }
}
.modal-close-button {
  position: absolute;
  top: 10px;
  right: 12px;
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #9ca3af;
  line-height: 1;
}
.modal-close-button:hover {
    color: #374151;
}
.modal-header {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 16px;
  margin-bottom: 16px;
}
.modal-image {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  margin-right: 16px;
  object-fit: cover;
  border: 3px solid #e5e7eb;
}
.modal-name {
  font-size: 22px;
  font-weight: 600;
  color: #111827;
}
.modal-title {
  font-size: 14px;
  color: #6b7280;
}
.details-heading {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #374151;
}
.modal-body p {
  margin-bottom: 10px;
  color: #4b5563;
  font-size: 14px;
}
.modal-body p strong {
    color: #374151;
    width: 140px;
    display: inline-block;
}
.modal-footer {
  margin-top: 24px;
  text-align: center;
}
.performance-link {
  display: inline-block;
  padding: 10px 24px;
  background-color: #4f46e5;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 500;
  transition: background-color 0.2s ease;
}
.performance-link:hover {
  background-color: #4338ca;
}
</style>
