<template>
  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto pb-12 px-6">
      <!-- Header -->
      <div class="pt-10 pb-4 flex flex-col gap-1">
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 mb-1">Team Performance</h1>
        <p class="text-gray-500 mb-4">
          Monitor and manage your team's review progress
        </p>
      </div>

      <!-- Stats cards row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white shadow-sm rounded-xl py-6 px-4 text-center border">
          <div class="text-2xl font-extrabold text-gray-900">{{ stats.teamSize }}</div>
          <div class="text-sm text-gray-500 mt-2">Team Size</div>
          <div class="text-xs text-gray-400">Active members</div>
        </div>
        <div class="bg-white shadow-sm rounded-xl py-6 px-4 text-center border">
          <div class="text-2xl font-extrabold text-green-700">{{ stats.avgPerformance.toFixed(1) }}/10</div>
          <div class="text-sm text-gray-500 mt-2">Avg Performance</div>
          <div class="text-xs text-gray-400">Current quarter</div>
        </div>
        <div class="bg-white shadow-sm rounded-xl py-6 px-4 text-center border">
          <div class="text-2xl font-extrabold text-red-600">{{ stats.reviewsPending }}</div>
          <div class="text-sm text-gray-500 mt-2">Reviews Pending</div>
          <div class="text-xs text-red-600">Need attention</div>
        </div>
        <div class="bg-white shadow-sm rounded-xl py-6 px-4 text-center border">
          <div class="text-2xl font-extrabold text-blue-700">{{ stats.topPerformers }}</div>
          <div class="text-sm text-gray-500 mt-2">Top Performers</div>
          <div class="text-xs text-gray-400">Above 8.5 rating</div>
        </div>
      </div>

      <!-- Filter/Search -->
      <div class="flex flex-col sm:flex-row sm:items-center mb-6 gap-3">
        <input
          v-model="search"
          placeholder="Search team members..."
          class="border px-4 py-2 rounded w-full sm:w-96"
        />
      </div>

      <!-- Member Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-7">
        <div
          v-for="member in filteredMembers"
          :key="member.id"
          class="bg-white border shadow-sm rounded-xl px-7 py-6 flex flex-col gap-2"
        >
          <div class="flex items-center gap-4 mb-2">
            <div class="bg-gray-100 rounded-full h-12 w-12 flex items-center justify-center text-xl font-bold text-gray-700 border border-gray-200">
              {{ initials(member.name) }}
            </div>
            <div>
              <div class="font-semibold text-lg text-gray-900">{{ member.name }}</div>
              <div class="text-gray-500 text-sm">{{ member.role }}</div>
            </div>
          </div>
          <div class="font-medium text-sm text-gray-600 mb-1">Overall Progress</div>
          <div class="relative h-2 bg-gray-200 rounded mb-2">
            <div
              :style="{ width: Math.min(Math.max(member.progress || 0, 0), 100) + '%' }"
              :class="['absolute top-0 left-0 h-2 rounded', progressBarClass(member.status)]"
            ></div>
          </div>
          <div class="flex items-center justify-between text-sm mb-2">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              Last Review:
              <span class="font-semibold text-gray-800 ml-1">{{ member.lastReview ? member.lastReview : 'N/A' }}</span>
            </div>
            <span
              :class="[
                'px-3 py-1 rounded-full text-xs font-semibold',
                member.status === 'Completed'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-gray-100 text-gray-600'
              ]"
            >
              {{ member.status }}
            </span>
          </div>
          <div class="text-2xl font-bold mb-1" :class="{'text-green-600': member.performance > 0, 'text-gray-400': !member.performance}">{{ member.performance }}/10</div>
          <div class="text-gray-600 text-sm mb-1">Performance</div>

          <div class="flex flex-wrap gap-2 mb-1">
            <span
              v-for="ach in member.achievements"
              :key="ach"
              class="bg-gray-100 border border-gray-200 rounded px-3 py-1 text-xs font-medium text-gray-700"
              >{{ ach }}</span
            >
          </div>
          <div class="text-gray-500 text-sm mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 inline-block text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
            </svg>
            {{ member.email }}
          </div>
          <div class="flex gap-3">
            <button
              @click="goToHistory(member.id)"
              class="bg-white text-gray-900 px-4 py-2 rounded border border-gray-300 flex items-center gap-2 font-semibold hover:bg-gray-50 transition"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v9a2 2 0 002 2h14a2 2 0 002-2V7"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.5V7"></path>
              </svg>
              View History
            </button>
            <button
              @click="goToCreateReview(member.id, member.latestReviewMonth, member.latestReviewYear)"
              class="bg-gray-900 text-white px-4 py-2 rounded flex items-center gap-2 font-semibold hover:bg-gray-700 transition"
            >
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
              </svg>
              Add Review
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

export default {
  components: {
    AuthenticatedLayout,
  },
  props: {
    members: { type: Array, required: true },
    stats: { type: Object, required: false },
  },
  data() {
    return {
      search: '',
      loadingReview: false,
    };
  },
  computed: {
    filteredMembers() {
      if (!this.search.trim()) return this.members;
      const term = this.search.trim().toLowerCase();
      return this.members.filter(
        (m) =>
          m.name.toLowerCase().includes(term) ||
          (m.email ? m.email.toLowerCase().includes(term) : false)
      );
    },
  },
  methods: {
    initials(name) {
      if (!name) return '';
      return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
    },
    progressBarClass(status) {
      return status === 'Completed' ? 'bg-green-500' : 'bg-gray-400';
    },
    goToReview(employeeId, month, year) {
      this.$inertia.visit(route('employee.reviewPage', { employeeId, month, year }));
    },
    goToCreateReview(employeeId, month, year) {
      const now = new Date();
      const reviewMonth = month || now.getMonth() + 1;
      const reviewYear = year || now.getFullYear();
      this.$inertia.visit(route('employee.review.create', { employeeId, month: reviewMonth, year: reviewYear }));
    },
    goToHistory(employeeId) {
      // Define this to go to review history, if you have a route
      this.$inertia.visit(route('employee.reviewHistory', { employeeId }));
    }
  },
};
</script>
