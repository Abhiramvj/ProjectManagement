<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link as InertiaLink } from '@inertiajs/vue3';
import HrToggleBar from '@/Components/HrToggleBar.vue';

export default {
  components: { AuthenticatedLayout, InertiaLink,
    HrToggleBar
   },
  props: {
    stats: {
      type: Object,
      required: true,
      default: () => ({
        total: 0,
        bronze: 0,
        silver: 0,
        gold: 0,
        this_month: 0,
      }),
    },
    users: {
      type: Array,
      required: true,
      default: () => [],
    },
    recentActivity: {
      type: Array,
      required: true,
      default: () => [],
    },
  },
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
    }
  },



  data() {
    return {
        SearchTerm: '',
    };
  },
   computed: {
    filteredUsers() {
      if (!this.SearchTerm) return this.users;
      const term = this.SearchTerm.toLowerCase();
      return this.users.filter((user) =>
        user.name.toLowerCase().includes(term)
      );
    },
  },
};
</script>


<template>
  <AuthenticatedLayout>
    <HrToggleBar />
    <div class="max-w-7xl mx-auto py-8 px-4">
      <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
          <img src="/badges/badge-icon.png" alt="Badge Management" class="w-8 h-8 mr-2" />
          <h1 class="text-2xl font-bold">Badge Management</h1>
        </div>

      </div>

      <div class="grid grid-cols-5 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow-md flex items-center justify-between">
          <div>
            <div class="flex items-center text-purple-600 mb-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-3xl font-bold">{{ stats.total }}</span>
            </div>
            <p class="text-gray-600">Total Badges</p>
          </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md flex items-center justify-between">
          <div>
            <div class="flex items-center text-yellow-600 mb-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.329 1.176l1.519 4.674c.3.921-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.519-4.674a1 1 0 00-.329-1.176l-3.976-2.888c-.783-.57-.381-1.81.588-1.81h4.915a1 1 0 00.95-.69l1.519-4.674z" />
              </svg>
              <span class="text-3xl font-bold">{{ stats.bronze }}</span>
            </div>
            <p class="text-gray-600">Bronze Badges</p>
          </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md flex items-center justify-between">
          <div>
            <div class="flex items-center text-gray-400 mb-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.329 1.176l1.519 4.674c.3.921-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.519-4.674a1 1 0 00-.329-1.176l-3.976-2.888c-.783-.57-.381-1.81.588-1.81h4.915a1 1 0 00.95-.69l1.519-4.674z" />
              </svg>
              <span class="text-3xl font-bold">{{ stats.silver }}</span>
            </div>
            <p class="text-gray-600">Silver Badges</p>
          </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md flex items-center justify-between">
          <div>
            <div class="flex items-center text-yellow-500 mb-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.329 1.176l1.519 4.674c.3.921-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.519-4.674a1 1 0 00-.329-1.176l-3.976-2.888c-.783-.57-.381-1.81.588-1.81h4.915a1 1 0 00.95-.69l1.519-4.674z" />
              </svg>
              <span class="text-3xl font-bold">{{ stats.gold }}</span>
            </div>
            <p class="text-gray-600">Gold Badges</p>
          </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md flex items-center justify-between">
          <div>
            <div class="flex items-center text-green-500 mb-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              <span class="text-3xl font-bold">{{ stats.this_month }}</span>
            </div>
            <p class="text-gray-600">This Month</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div class="col-span-2 bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-xl font-semibold mb-4">Employee Badge Overview</h2>
          <div class="flex items-center space-x-4 mb-4">
            <div class="relative flex-grow">
              <input v-model="SearchTerm" type="text" placeholder="Search employees..." class="w-full pl-10 pr-4 py-2 border rounded-lg" />
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <div v-for="user in filteredUsers" :key="user.id" class="flex items-center bg-gray-50 p-4 rounded-lg mb-3">
            <img v-if="user.profile_picture" :src="user.profile_picture" alt="" class="w-12 h-12 rounded-full mr-4" />
            <div v-else class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center text-lg font-semibold text-gray-600 mr-4">
              {{ user.name.charAt(0) }}
            </div>
            <div class="flex-grow">
              <p class="font-semibold text-lg">{{ user.name }}</p>
              <p class="text-gray-600">{{ user.designation || 'N/A' }}</p>
              <div class="flex space-x-3 text-sm text-gray-500 mt-1">
                <span>{{ user.badges_count }} badges</span>
                <span>{{ user.sessions_count }} sessions</span>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <div v-for="badge in user.badges" :key="badge.id" :title="badge.name">
                <img v-if="badge.icon" :src="`/storage/${badge.icon}`" :alt="badge.name" class="w-8 h-8" />
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-1 bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-xl font-semibold mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Recent Badges
          </h2>
          <div v-for="(activity, index) in recentActivity" :key="index" class="flex items-start mb-4">
            <img v-if="activity.badge_icon" :src="`/storage/${activity.badge_icon}`" :alt="activity.badge_name" class="w-8 h-8 rounded-full mr-3 mt-1" />
            <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600 mr-3 mt-1">
              {{ activity.badge_name.charAt(0) }}
            </div>
            <div>
              <p class="font-semibold">{{ activity.user_name }}</p>
              <p class="text-gray-700">Earned <span :class="{'text-yellow-600': activity.badge_name === 'Gold', 'text-gray-400': activity.badge_name === 'Silver', 'text-yellow-700': activity.badge_name === 'Bronze'}">{{ activity.badge_name }} Speaker</span></p>
              <p class="text-sm text-gray-500">Topic: {{ activity.topic || 'N/A' }}</p>
              <span :class="{'bg-yellow-200 text-yellow-800': activity.badge_name === 'Gold', 'bg-gray-200 text-gray-800': activity.badge_name === 'Silver', 'bg-yellow-100 text-yellow-700': activity.badge_name === 'Bronze'}" class="px-2 py-0.5 rounded-full text-xs font-medium mr-2">
                {{ activity.badge_name.toLowerCase() }}
              </span>
              <span class="text-sm text-gray-500">{{ formatDate(activity.unlocked_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>



