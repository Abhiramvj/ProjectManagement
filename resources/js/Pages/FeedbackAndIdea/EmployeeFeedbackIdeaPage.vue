<template>
  <AuthenticatedLayout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
      <div class="max-w-6xl mx-auto p-6">
        <!-- Header Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
          <!-- Internal Nav Bar -->
          <div class="flex border-b border-gray-100 bg-gray-50/50">
            <Link
              :class="[
                'px-6 py-4 text-sm font-medium transition-all duration-300 relative',
                currentType === 'feedback' 
                  ? 'text-indigo-600 bg-white border-b-2 border-indigo-500 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-800 hover:bg-white/50'
              ]"
              :href="route('feedback.index')"
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
                  ? 'text-indigo-600 bg-white border-b-2 border-indigo-500 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-800 hover:bg-white/50'
              ]"
              :href="route('idea.index')"
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
              <div class="p-3 bg-indigo-100 rounded-xl">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ pageTitle }}</h1>
                <p class="text-gray-600 mt-1">Share your thoughts and help us improve</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Submission Form -->
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
              <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-8 py-6">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                  </div>
                  <h3 class="text-xl font-bold text-white">Submit New {{ currentTypeCapitalized }}</h3>
                </div>
                <p class="text-indigo-100 mt-2 text-sm">Your {{ currentType }} matters to us. Let us know what's on your mind.</p>
              </div>

              <form @submit.prevent="submit" class="p-8">
                <div class="space-y-6">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                      What would you like to share?
                    </label>
                    <div class="relative">
                      <textarea
                        v-model="form.description"
                        placeholder="Write your feedback or idea here. Be as detailed as you'd like..."
                        class="w-full p-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 resize-none bg-gray-50 focus:bg-white"
                        rows="6"
                        required
                      ></textarea>
                      <div class="absolute bottom-3 right-3 text-xs text-gray-400">
                        {{ form.description.length }} characters
                      </div>
                    </div>
                  </div>

                  <button 
                    type="submit" 
                    :disabled="form.processing || !form.description.trim()"
                    class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white px-6 py-4 rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center gap-2"
                  >
                    <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    {{ form.processing ? 'Submitting...' : `Submit ${currentTypeCapitalized}` }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                  </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Your Contributions</h3>
              </div>
              <div class="text-center">
                <div class="text-3xl font-bold text-indigo-600 mb-1">{{ items.length }}</div>
                <div class="text-sm text-gray-600">{{ currentType }} submitted</div>
              </div>
            </div>

            <!-- Tips -->
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl border border-amber-100 p-6">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Tips for Great {{ currentTypeCapitalized }}</h3>
              </div>
              <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-start gap-2">
                  <div class="w-1.5 h-1.5 bg-amber-400 rounded-full mt-2 flex-shrink-0"></div>
                  <span>Be specific and provide context</span>
                </li>
                <li class="flex items-start gap-2">
                  <div class="w-1.5 h-1.5 bg-amber-400 rounded-full mt-2 flex-shrink-0"></div>
                  <span>Explain the impact or benefit</span>
                </li>
                <li class="flex items-start gap-2">
                  <div class="w-1.5 h-1.5 bg-amber-400 rounded-full mt-2 flex-shrink-0"></div>
                  <span>Keep it constructive and actionable</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Filters and Submissions -->
        <div class="mt-12">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Filters Header -->
            <div class="border-b border-gray-100 bg-gray-50/50 px-8 py-6">
              <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-gray-900">My Submissions</h3>
                  <p class="text-sm text-gray-600">Filter and view your previous submissions</p>
                </div>
                
                <div class="flex flex-wrap gap-2">
                  <button 
                    @click="applyQuickFilter('today')" 
                    class="bg-white hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 px-4 py-2 rounded-lg border border-gray-200 hover:border-indigo-300 transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
                  >
                    Today
                  </button>
                  <button 
                    @click="applyQuickFilter('week')" 
                    class="bg-white hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 px-4 py-2 rounded-lg border border-gray-200 hover:border-indigo-300 transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
                  >
                    This Week
                  </button>
                  <button 
                    @click="applyQuickFilter('month')" 
                    class="bg-white hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 px-4 py-2 rounded-lg border border-gray-200 hover:border-indigo-300 transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
                  >
                    This Month
                  </button>
                </div>
              </div>

              <!-- Date Range Filters -->
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-2">Start Date</label>
                  <input 
                    type="date" 
                    v-model="filters.start_date" 
                    class="w-full p-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 text-sm"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-2">End Date</label>
                  <input 
                    type="date" 
                    v-model="filters.end_date" 
                    class="w-full p-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 text-sm"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-2">Apply Range</label>
                  <button 
                    @click="applyDateRangeFilter" 
                    class="w-full bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2.5 rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5 text-sm"
                  >
                    Apply Filter
                  </button>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-2">Clear</label>
                  <button 
                    @click="resetFilters" 
                    class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2.5 rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5 text-sm"
                  >
                    Reset Filters
                  </button>
                </div>
              </div>
            </div>

            <!-- Submissions List -->
            <div class="p-8">
              <div v-if="items.length === 0" class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No submissions yet</h3>
                <p class="text-gray-600">Start by submitting your first {{ currentType }} above!</p>
              </div>

              <div v-else class="space-y-4">
                <div v-for="item in items" :key="item.id" class="group">
                  <div class="bg-gray-50 hover:bg-white border border-gray-100 hover:border-gray-200 hover:shadow-md rounded-xl p-6 transition-all duration-300 group-hover:-translate-y-1">
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-3">
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ currentTypeCapitalized }}
                          </span>
                          <span class="text-xs text-gray-500">{{ formattedDate(item.created_at) }}</span>
                        </div>
                        
                        <p class="text-gray-700 leading-relaxed mb-3">
                          {{ truncate(item.description, 120) }}
                        </p>
                      </div>
                      
                      <button 
                        @click="openModal(item)" 
                        class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5 flex items-center gap-2 text-sm opacity-0 group-hover:opacity-100"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View Full
                      </button>
                    </div>
                  </div>
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
              <div class="sticky top-0 bg-gradient-to-r from-indigo-500 to-purple-600 px-8 py-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-xl font-bold text-white">
                        Your {{ currentTypeCapitalized }}
                      </h3>
                      <p class="text-indigo-100 text-sm">{{ formattedDate(modalData.created_at) }}</p>
                    </div>
                  </div>
                  <button 
                    @click="closeModal"
                    class="p-2 hover:bg-white/20 rounded-full transition-colors duration-200"
                  >
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Modal Body -->
              <div class="p-8">
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
                  <p class="text-gray-800 leading-relaxed whitespace-pre-wrap">{{ modalData.description }}</p>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="border-t border-gray-100 px-8 py-6 bg-gray-50 rounded-b-2xl">
                <div class="flex justify-end">
                  <button 
                    @click="closeModal" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2.5 rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
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
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  items: Array,
  type: String,
});

const currentType = props.type;
const currentTypeCapitalized = currentType.charAt(0).toUpperCase() + currentType.slice(1);
const pageTitle = `${currentTypeCapitalized} Submissions`;

const form = useForm({
  description: '',
});

const filters = ref({
  start_date: '',
  end_date: '',
  date_filter: '',
});

const showModal = ref(false);
const modalData = ref({});

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

function submit() {
  form.post(route(currentType === 'feedback' ? 'feedback.store' : 'idea.store'), {
    onSuccess: () => form.reset(),
  });
}

function applyDateRangeFilter() {
  const query = {};
  if (filters.value.start_date) query.start_date = filters.value.start_date;
  if (filters.value.end_date) query.end_date = filters.value.end_date;

  window.location.href = route(
    currentType === 'feedback' ? 'feedback.index' : 'idea.index',
    query
  );
}

function applyQuickFilter(option) {
  window.location.href = route(
    currentType === 'feedback' ? 'feedback.index' : 'idea.index',
    { date_filter: option }
  );
}

function resetFilters() {
  filters.value.start_date = '';
  filters.value.end_date = '';
  filters.value.date_filter = '';
  window.location.href = route(
    currentType === 'feedback' ? 'feedback.index' : 'idea.index',
    {}
  );
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