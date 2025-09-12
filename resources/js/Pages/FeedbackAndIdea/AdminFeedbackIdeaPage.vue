<template>
  <AuthenticatedLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
      <div class="max-w-7xl mx-auto p-6">
        <!-- Header Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
          <!-- Internal Nav Bar -->
          <div class="flex border-b border-gray-100 bg-gray-50/50">
            <Link
              :class="[
                'px-6 py-4 text-sm font-medium transition-all duration-300 relative',
                currentType === 'feedback' 
                  ? 'text-blue-600 bg-white border-b-2 border-blue-500 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-800 hover:bg-white/50'
              ]"
              :href="route('admin.feedback.index')"
            >
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                Feedback
              </span>
            </Link>
            <Link
              :class="[
                'px-6 py-4 text-sm font-medium transition-all duration-300 relative',
                currentType === 'idea' 
                  ? 'text-blue-600 bg-white border-b-2 border-blue-500 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-800 hover:bg-white/50'
              ]"
              :href="route('admin.idea.index')"
            >
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Ideas
              </span>
            </Link>
          </div>

          <div class="p-8">
            <div class="flex items-center gap-4 mb-6">
              <div class="p-3 bg-blue-100 rounded-xl">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ pageTitle }}</h1>
                <p class="text-gray-600 mt-1">Manage and review employee {{ currentType }} submissions</p>
              </div>
            </div>

            <!-- Enhanced Filters -->
            <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
              <h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Filters</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-600 uppercase tracking-wide">Employee</label>
                  <select 
                    v-model="filters.employee_id" 
                    class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  >
                    <option value="">All Employees</option>
                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>

                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-600 uppercase tracking-wide">Start Date</label>
                  <input 
                    type="date" 
                    v-model="filters.start_date" 
                    class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-600 uppercase tracking-wide">End Date</label>
                  <input 
                    type="date" 
                    v-model="filters.end_date" 
                    class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-xs font-medium text-gray-600 uppercase tracking-wide">Actions</label>
                  <button 
                    @click="resetFilters" 
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
                  >
                    Reset Filters
                  </button>
                </div>
              </div>

              <!-- Quick Date Filter Buttons -->
              <div class="flex flex-wrap gap-2">
                <button 
                  @click="quickFilter('today')" 
                  :class="[
                    'px-4 py-2 rounded-lg border transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5',
                    filters.date_filter === 'today' 
                      ? 'bg-blue-500 text-white border-blue-500' 
                      : 'bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-600 border-gray-200 hover:border-blue-300'
                  ]"
                >
                  Today
                </button>
                <button 
                  @click="quickFilter('week')" 
                  :class="[
                    'px-4 py-2 rounded-lg border transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5',
                    filters.date_filter === 'week' 
                      ? 'bg-blue-500 text-white border-blue-500' 
                      : 'bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-600 border-gray-200 hover:border-blue-300'
                  ]"
                >
                  This Week
                </button>
                <button 
                  @click="quickFilter('month')" 
                  :class="[
                    'px-4 py-2 rounded-lg border transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5',
                    filters.date_filter === 'month' 
                      ? 'bg-blue-500 text-white border-blue-500' 
                      : 'bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-600 border-gray-200 hover:border-blue-300'
                  ]"
                >
                  This Month
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Results Section -->
        <div class="space-y-4">
          <div v-if="filteredItems.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No {{ currentType }} found</h3>
            <p class="text-gray-600">
              <span v-if="hasActiveFilters">Try adjusting your filters or check back later.</span>
              <span v-else>No {{ currentType }} submissions available.</span>
            </p>
          </div>

          <div v-for="item in filteredItems" :key="item.id" class="group">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300 overflow-hidden group-hover:-translate-y-1">
              <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        {{ (item.user?.name || 'U')[0].toUpperCase() }}
                      </div>
                      <div>
                        <h4 class="font-semibold text-gray-900">{{ item.user?.name || 'Unknown User' }}</h4>
                        <p class="text-xs text-gray-500">{{ formattedDate(item.created_at) }}</p>
                      </div>
                    </div>
                    
                    <p class="text-gray-700 leading-relaxed mb-4">
                      {{ truncate(item.description, 50) }}
                    </p>
                    
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ currentTypeCapitalized }}
                      </span>
                    </div>
                  </div>
                  
                  <button 
                    @click="openModal(item)" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5 flex items-center gap-2 text-sm opacity-0 group-hover:opacity-100"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View Details
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Modal -->
        <Transition name="modal">
          <div 
            v-if="showModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click="closeModal"
          >
            <div 
              class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform transition-all duration-300"
              @click.stop
            >
              <!-- Modal Header -->
              <div class="sticky top-0 bg-white border-b border-gray-100 px-8 py-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                      {{ (modalData.user?.name || 'U')[0].toUpperCase() }}
                    </div>
                    <div>
                      <h3 class="text-xl font-bold text-gray-900">
                        {{ modalData.user?.name || 'Unknown User' }}'s {{ currentTypeCapitalized }}
                      </h3>
                      <p class="text-sm text-gray-500">{{ formattedDate(modalData.created_at) }}</p>
                    </div>
                  </div>
                  <button 
                    @click="closeModal"
                    class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200"
                  >
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Modal Body -->
              <div class="px-8 py-6">
                <div class="mb-4">
                  <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Content</label>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                    <p class="text-gray-800 leading-relaxed whitespace-pre-wrap">{{ modalData.description }}</p>
                  </div>
                </div>
                
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Submitted {{ formattedDate(modalData.created_at) }}
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ currentTypeCapitalized }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="border-t border-gray-100 px-8 py-4 bg-gray-50 rounded-b-2xl">
                <div class="flex justify-end">
                  <button 
                    @click="closeModal" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  items: Array,
  type: String,
  employees: Array,
});

const currentType = props.type;
const currentTypeCapitalized = currentType.charAt(0).toUpperCase() + currentType.slice(1);
const pageTitle = `${currentTypeCapitalized} Submissions`;

const filters = ref({
  employee_id: '',
  start_date: '',
  end_date: '',
  date_filter: '',
});

const showModal = ref(false);
const modalData = ref({});

// Check if any filters are active
const hasActiveFilters = computed(() => {
  return !!(filters.value.employee_id || filters.value.start_date || filters.value.end_date || filters.value.date_filter);
});

// Client-side filtered items
const filteredItems = computed(() => {
  if (!props.items || !Array.isArray(props.items)) {
    return [];
  }

  let filtered = [...props.items];
  
  // Filter by employee
  if (filters.value.employee_id) {
    filtered = filtered.filter(item => item.user_id == filters.value.employee_id);
  }
  
  // Filter by date range
  if (filters.value.start_date || filters.value.end_date || filters.value.date_filter) {
    const now = new Date();
    let startDate, endDate;
    
    if (filters.value.date_filter) {
      switch (filters.value.date_filter) {
        case 'today':
          startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
          endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
          break;
        case 'week':
          const startOfWeek = new Date(now);
          startOfWeek.setDate(now.getDate() - now.getDay());
          startOfWeek.setHours(0, 0, 0, 0);
          startDate = startOfWeek;
          endDate = new Date(startOfWeek.getTime() + 7 * 24 * 60 * 60 * 1000);
          break;
        case 'month':
          startDate = new Date(now.getFullYear(), now.getMonth(), 1);
          endDate = new Date(now.getFullYear(), now.getMonth() + 1, 1);
          break;
      }
    } else {
      if (filters.value.start_date) startDate = new Date(filters.value.start_date);
      if (filters.value.end_date) {
        endDate = new Date(filters.value.end_date);
        endDate.setHours(23, 59, 59, 999);
      }
    }
    
    filtered = filtered.filter(item => {
      const itemDate = new Date(item.created_at);
      const afterStart = !startDate || itemDate >= startDate;
      const beforeEnd = !endDate || itemDate <= endDate;
      return afterStart && beforeEnd;
    });
  }
  
  return filtered;
});

function quickFilter(option) {
  filters.value.date_filter = option;
  filters.value.start_date = '';
  filters.value.end_date = '';
}

function resetFilters() {
  filters.value.employee_id = '';
  filters.value.start_date = '';
  filters.value.end_date = '';
  filters.value.date_filter = '';
}

function openModal(item) {
  modalData.value = item;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

function formattedDate(dateString) {
  return new Date(dateString).toLocaleString();
}

function truncate(text, length = 100) {
  return text.length > length ? text.substring(0, length) + '...' : text;
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.95) translateY(20px);
}
</style>