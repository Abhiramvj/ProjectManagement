<script setup>
import { onMounted, ref, watch } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';

const props = defineProps({
  nodes: {
    type: Array,
    required: true,
  }
});

const selectedEmployee = ref(null);
const chartContainer = ref(null);
let chartInstance = null;

// Color constants
const VIBRANT_COLORS = [
  '#ff6b6b', '#feca57', '#48dbfb', '#ff9ff3', '#54a0ff',
  '#5f27cd', '#00d2d3', '#ff9f43', '#10ac84', '#ee5253',
  '#8395a7', '#f368e0', '#3742fa', '#2f3542', '#ff3838',
  '#ff6348', '#70a1ff', '#5352ed', '#747d8c', '#a4b0be',
  '#6c5ce7', '#fd79a8', '#fdcb6e', '#6c5ce7', '#74b9ff'
];

const COLOR_COMPLEMENTS = {
  '#ff6b6b': '#6bff6b',
  '#feca57': '#57cafe',
  '#48dbfb': '#fb48db',
  '#ff9ff3': '#9fff9f',
  '#54a0ff': '#ffa054',
  '#5f27cd': '#cd5f27',
  '#00d2d3': '#d30000',
  '#ff9f43': '#43ff9f'
};

// Template creation functions
const createCommonDefs = (id, color, secondaryColor) => `
  <defs>
    <linearGradient id="cardGradient${id}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="${color}" stop-opacity="0.8" />
      <stop offset="50%" stop-color="${color}" stop-opacity="0.6" />
      <stop offset="100%" stop-color="${color}" stop-opacity="0.4" />
    </linearGradient>
    <linearGradient id="borderGradient${id}" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="${color}" />
      <stop offset="100%" stop-color="${secondaryColor}" />
    </linearGradient>
    <filter id="glow${id}">
      <feMorphology operator="dilate" radius="1"/>
      <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
      <feMerge> 
        <feMergeNode in="coloredBlur"/>
        <feMergeNode in="SourceGraphic"/> 
      </feMerge>
    </filter>
  </defs>`;

const createTextElement = (text, x, y, fontSize, fontWeight = '400', fill = '#ffffff', opacity = '1') => 
  `<text style="font-size: ${fontSize}px; font-weight: ${fontWeight}; font-family: 'Inter', sans-serif;" 
        fill="${fill}" fill-opacity="${opacity}" x="${x}" y="${y}" text-anchor="start">${text}</text>`;

// Initialize templates
const initializeTemplates = () => {
  // User node template
  OrgChart.templates.userNodeColorful = Object.assign({}, OrgChart.templates.base);
  OrgChart.templates.userNodeColorful.size = [300, 120];
  OrgChart.templates.userNodeColorful.node = 
    createCommonDefs('{id}', '{binding.color}', '{binding.secondaryColor}') +
    `<rect x="0" y="0" width="300" height="120" fill="url(#cardGradient{id})" rx="15" ry="15" stroke="url(#borderGradient{id})" stroke-width="3"></rect>
     <rect x="5" y="5" width="8" height="110" fill="{binding.color}" rx="4" ry="4" filter="url(#glow{id})"></rect>
     <circle cx="250" cy="60" r="38" fill="rgba(255,255,255,0.9)" stroke="{binding.color}" stroke-width="3"></circle>`;

  // Text fields
  OrgChart.templates.userNodeColorful.field_0 = createTextElement('{val}', 25, 45, 18, '700');
  OrgChart.templates.userNodeColorful.field_1 = createTextElement('{val}', 25, 70, 14, '500', 'rgba(255,255,255,0.9)');
  
  // Department badge
  OrgChart.templates.userNodeColorful.field_2 = 
    `<rect x="20" y="85" width="120" height="20" fill="rgba(255,255,255,0.2)" rx="10" ry="10" stroke="rgba(255,255,255,0.3)" stroke-width="1"></rect>` +
    createTextElement('{val}', 80, 97, 11, '600', 'rgba(255,255,255,0.95)').replace('text-anchor="start"', 'text-anchor="middle"').replace('font-weight: 600', 'font-weight: 600; text-transform: uppercase');

  // Image
  OrgChart.templates.userNodeColorful.img_0 =
    `<clipPath id="clipCircleColorful{id}">
       <circle cx="250" cy="60" r="35"></circle>
     </clipPath>
     <image preserveAspectRatio="xMidYMid slice" clip-path="url(#clipCircleColorful{id})" 
            xlink:href="{val}" x="215" y="25" width="70" height="70"></image>`;

  // Department node template
  OrgChart.templates.departmentNodeColorful = Object.assign({}, OrgChart.templates.base);
  OrgChart.templates.departmentNodeColorful.size = [320, 90];
  OrgChart.templates.departmentNodeColorful.node =
    `<defs>
      <linearGradient id="deptGradient{id}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="{binding.color}" />
        <stop offset="25%" stop-color="{binding.color}" />
        <stop offset="50%" stop-color="{binding.color}" />
        <stop offset="75%" stop-color="{binding.color}" />
        <stop offset="100%" stop-color="{binding.color}" />
      </linearGradient>
      <filter id="deptGlow{id}">
        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
        <feMerge> 
          <feMergeNode in="coloredBlur"/>
          <feMergeNode in="SourceGraphic"/> 
        </feMerge>
      </filter>
    </defs>
    <rect x="0" y="0" width="320" height="90" fill="url(#deptGradient{id})" rx="15" ry="15" stroke="{binding.color}" stroke-width="2" filter="url(#deptGlow{id})"></rect>`;

  OrgChart.templates.departmentNodeColorful.field_0 = 
    `<g transform="translate(25, 25)">
       <circle cx="20" cy="20" r="18" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="2"></circle>
       <path fill="#ffffff" d="M20,20A4,4 0 0,0 16,24A4,4 0 0,0 20,28A4,4 0 0,0 24,24A4,4 0 0,0 20,20M20,22A2,2 0 0,1 22,24A2,2 0 0,1 20,26A2,2 0 0,1 18,24A2,2 0 0,1 20,22M28,20C30.21,20 32,21.79 32,24V25H30V24C30,22.9 29.1,22 28,22M26,20C24.5,20 23.25,20.59 22.37,21.5C22.74,22.22 23,23.06 23,24V25H12V24C12,21.79 13.79,20 16,20M20,12A4,4 0 0,1 24,16A4,4 0 0,1 20,20A4,4 0 0,1 16,16A4,4 0 0,1 20,12M20,14A2,2 0 0,0 18,16A2,2 0 0,0 20,18A2,2 0 0,0 22,16A2,2 0 0,0 20,14Z" transform="scale(0.8) translate(5, 5)"></path>
     </g>` + 
    createTextElement('{val}', 80, 50, 18, '700').replace('font-weight: 700', 'font-weight: 700; text-transform: uppercase; letter-spacing: 1px');

  // Node bindings
  const commonBinding = {
    field_0: "name",
    field_1: "title", 
    field_2: "department",
    img_0: "image",
    color: "color",
    secondaryColor: "secondaryColor"
  };

  OrgChart.templates.userNodeColorful.nodeBinding = commonBinding;
  OrgChart.templates.departmentNodeColorful.nodeBinding = { field_0: "name", color: "color" };
};

// Utility functions
const getRandomVibrantColor = () => VIBRANT_COLORS[Math.floor(Math.random() * VIBRANT_COLORS.length)];
const getComplementaryColor = (color) => COLOR_COMPLEMENTS[color] || '#ffffff';

const enhanceNodesWithColors = (nodes) => 
  nodes.map(node => ({
    ...node,
    color: node.color || getRandomVibrantColor(),
    secondaryColor: node.secondaryColor || getComplementaryColor(node.color || getRandomVibrantColor())
  }));

const getChartConfig = (enhancedNodes) => ({
  nodes: enhancedNodes,
  template: "userNodeColorful",
  nodeMenu: {
    details: { text: "👁️ View Details" },
    edit: { text: "✏️ Edit" },
    add: { text: "➕ Add" },
    remove: { text: "🗑️ Remove" }
  },
  nodeMouseClick: OrgChart.action.none,
  enableSearch: true,
  searchFields: ["name", "title", "department"],
  mouseScrool: OrgChart.action.zoom,
  toolbar: {
    zoom: true,
    fit: true,
    expandAll: true
  },
  connector: {
    type: "curved",
    style: {
      stroke: "#ff6b6b",
      "stroke-width": 3,
      "stroke-dasharray": "5,5"
    }
  },
  levelSeparation: 140,
  siblingSeparation: 60,
  subtreeSeparation: 40,
  align: OrgChart.ORIENTATION,
  orientation: OrgChart.orientation.top,
  tags: {
    "department": {
      template: "departmentNodeColorful",
      "node-class": "department-node-colorful"
    },
    "user": {
      "node-class": "user-node-colorful"
    }
  },
  nodeBinding: {
    field_0: "name",
    field_1: "title",
    field_2: "department",
    img_0: "image",
    color: "color",
    secondaryColor: "secondaryColor"
  }
});

const initializeChart = () => {
  if (!chartContainer.value || !props.nodes.length) return;

  chartInstance?.destroy();
  
  const enhancedNodes = enhanceNodesWithColors(props.nodes);
  chartInstance = new OrgChart(chartContainer.value, getChartConfig(enhancedNodes));
  
  chartInstance.on('click', (sender, args) => {
    const nodeData = args.node.data;
    if (nodeData.image) {
      selectedEmployee.value = nodeData;
    }
  });
};

const closeModal = () => {
  selectedEmployee.value = null;
};

const formatDate = (dateString) => 
  dateString ? new Date(dateString).toLocaleDateString() : 'N/A';

const formatExperience = (years) => 
  years ? `${years} years` : 'N/A';

// Initialize templates once
initializeTemplates();

onMounted(initializeChart);
watch(() => props.nodes, initializeChart, { deep: true });
</script>

<template>
  <div class="org-chart-container-colorful">
    <div ref="chartContainer" class="org-chart-colorful"></div>

    <!-- Enhanced Colorful Modal -->
    <div v-if="selectedEmployee" class="modal-overlay-colorful" @click="closeModal">
      <div class="modal-content-colorful" @click.stop :style="{ '--accent-color': selectedEmployee.color }">
        <button @click="closeModal" class="modal-close-button-colorful">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
        
        <div class="modal-header-colorful">
          <div class="image-container-colorful">
            <img :src="selectedEmployee.image" alt="Employee photo" class="modal-image-colorful">
            <div class="status-indicator-colorful"></div>
          </div>
          <div>
            <h2 class="modal-name-colorful">{{ selectedEmployee.name }}</h2>
            <p class="modal-title-colorful">{{ selectedEmployee.title }}</p>
            <div class="badge-container-colorful">
              <span class="department-badge-colorful">
                {{ selectedEmployee.department || 'No Department' }}
              </span>
            </div>
          </div>
        </div>
        
        <div class="modal-body-colorful">
          <div class="detail-section-colorful">
            <h3 class="details-heading-colorful">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Employee Details
            </h3>
            <div class="detail-grid-colorful">
              <div class="detail-item-colorful">
                <span class="detail-label-colorful">Employee ID</span>
                <span class="detail-value-colorful">{{ selectedEmployee.employee_id || 'N/A' }}</span>
              </div>
              <div class="detail-item-colorful">
                <span class="detail-label-colorful">Email</span>
                <span class="detail-value-colorful">{{ selectedEmployee.email || 'N/A' }}</span>
              </div>
              <div class="detail-item-colorful">
                <span class="detail-label-colorful">Hire Date</span>
                <span class="detail-value-colorful">{{ formatDate(selectedEmployee.hire_date) }}</span>
              </div>
              <div class="detail-item-colorful">
                <span class="detail-label-colorful">Experience</span>
                <span class="detail-value-colorful">{{ formatExperience(selectedEmployee.total_experience_years) }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer-colorful">
          <a :href="`/performance/${selectedEmployee.id}`" class="performance-link-colorful">
            View Performance Metrics
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* Base Colorful Styles */
.org-chart-container-colorful {
  position: relative;
  width: 100%;
  height: 100%;
  background-color: #ffffff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.org-chart-colorful {
  width: 100%;
  height: 85vh;
  min-height: 600px;
  background-color: #ffffff;
  position: relative;
}

.org-chart-colorful::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.05) 0%, transparent 50%),
              radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.05) 0%, transparent 50%),
              radial-gradient(circle at 40% 40%, rgba(120, 219, 226, 0.05) 0%, transparent 50%);
  pointer-events: none;
}

/* Enhanced Node Styles */
.user-node-colorful, .department-node-colorful {
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.user-node-colorful:hover, .department-node-colorful:hover {
  transform: translateY(-5px) scale(1.05);
  filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.25));
}

/* Colorful Modal Styles */
.modal-overlay-colorful {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(10px);
  animation: fadeInColorful 0.4s ease-out;
}

@keyframes fadeInColorful {
  from { 
    opacity: 0;
    backdrop-filter: blur(0px);
  }
  to { 
    opacity: 1;
    backdrop-filter: blur(10px);
  }
}

.modal-content-colorful {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
  backdrop-filter: blur(20px);
  padding: 40px;
  border-radius: 20px;
  width: 90%;
  max-width: 550px;
  position: relative;
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  animation: slideUpColorful 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes slideUpColorful {
  from { 
    transform: translateY(30px) scale(0.9) rotateX(10deg);
    opacity: 0; 
  }
  to { 
    transform: translateY(0) scale(1) rotateX(0deg);
    opacity: 1; 
  }
}

.modal-close-button-colorful {
  position: absolute;
  top: 20px;
  right: 20px;
  background: linear-gradient(135deg, var(--accent-color, #ff6b6b), #ff9ff3);
  border: none;
  cursor: pointer;
  color: white;
  transition: all 0.3s ease;
  padding: 12px;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.modal-close-button-colorful:hover {
  transform: rotate(90deg) scale(1.1);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.modal-header-colorful {
  display: flex;
  align-items: flex-start;
  margin-bottom: 32px;
}

.image-container-colorful {
  position: relative;
  margin-right: 24px;
}

.modal-image-colorful {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid var(--accent-color, #ff6b6b);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.status-indicator-colorful {
  position: absolute;
  bottom: 5px;
  right: 5px;
  width: 20px;
  height: 20px;
  background: linear-gradient(135deg, #48dbfb, #0abde3);
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.modal-name-colorful {
  font-size: 28px;
  font-weight: 700;
  background: linear-gradient(135deg, var(--accent-color, #ff6b6b), #ff9ff3);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 8px;
  font-family: 'Inter', sans-serif;
}

.modal-title-colorful {
  font-size: 16px;
  color: #6b7280;
  margin-bottom: 16px;
  font-weight: 500;
}

.badge-container-colorful {
  display: flex;
  gap: 12px;
}

.department-badge-colorful {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 25px;
  font-size: 13px;
  font-weight: 600;
  background: linear-gradient(135deg, var(--accent-color, #ff6b6b), #ff9ff3);
  color: white;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.modal-body-colorful {
  margin-bottom: 32px;
}

.detail-section-colorful {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.details-heading-colorful {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 20px;
  color: #374151;
}

.details-heading-colorful svg {
  color: var(--accent-color, #ff6b6b);
}

.detail-grid-colorful {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px 32px;
}

.detail-item-colorful {
  display: flex;
  flex-direction: column;
  padding: 12px;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.detail-item-colorful:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.detail-label-colorful {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.detail-value-colorful {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.modal-footer-colorful {
  display: flex;
  justify-content: center;
}

.performance-link-colorful {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 16px 32px;
  background: linear-gradient(135deg, var(--accent-color, #ff6b6b), #ff9ff3);
  color: white;
  text-decoration: none;
  border-radius: 50px;
  font-weight: 600;
  font-size: 15px;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.performance-link-colorful:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

/* Enhanced Search and Toolbar Styles */
.boc-search {
  border: 2px solid rgba(255, 255, 255, 0.3) !important;
  border-radius: 25px !important;
  padding: 12px 20px !important;
  font-family: 'Inter', sans-serif !important;
  background: rgba(255, 255, 255, 0.9) !important;
  backdrop-filter: blur(10px) !important;
  color: #374151 !important;
  font-weight: 500 !important;
}

.boc-search:focus {
  border-color: #ff6b6b !important;
  box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2) !important;
}

.boc-toolbar {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7)) !important;
  backdrop-filter: blur(20px) !important;
  border-radius: 20px !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
  padding: 16px !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.boc-toolbar-button {
  border-radius: 12px !important;
  transition: all 0.3s ease !important;
  padding: 12px !important;
  font-weight: 600 !important;
}

.boc-toolbar-button:hover {
  background: linear-gradient(135deg, #ff6b6b, #ff9ff3) !important;
  color: white !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
}

/* Responsive Design */
@media (max-width: 768px) {
  .detail-grid-colorful {
    grid-template-columns: 1fr;
  }
  
  .modal-header-colorful {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  .image-container-colorful {
    margin-right: 0;
    margin-bottom: 20px;
  }
}
</style>