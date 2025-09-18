<script>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SessionCreate from './SessionCreate.vue';
import Pagination from '@/Components/Pagination.vue';

export default {
  components: {
    SessionCreate,
    AuthenticatedLayout,
    Pagination
  },
  props: {
    badges: {
      type: Array,
      default: () => [],
    },
    milestones: {
      type: Array,
      default: () => [],
    },
    sessionsCount: {
      type: Number,
      default: 0,
    },
    user: Object,
  upcomingSessions: {
    type: Array,
    default: () => [],
  },
    leaderboard: {
    type: Object,
    default: () => ({ data: [], next_page_url: null, prev_page_url: null }),
    },
  },
  data() {
    return {
        isVisible:true
    }
  },
  setup() {
    const showModal = ref(false);
    const route = (name) => {
        const routes = {
            'sessions.store': '/sessions',
            'sessions.create': '/sessions/create'
        };
        return routes[name] || '#';
    };

    return {
      showModal,
      route
    };
  },
 methods: {
    formatDate(dateString) {
      if (!dateString) return 'Not set';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    },
    closeCard() {
      this.isVisible = false;
    }
  }

};
</script>

<template>
    <AuthenticatedLayout>
  <div class="p-6 bg-white min-h-screen">
    <div class="flex justify-between items-center mb-6">

    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <!-- Request New Session Card -->
      <div
        class="bg-gray-50 border border-gray-200 rounded-lg p-6 flex flex-col items-center justify-center text-center shadow-sm"
      >
        <div
          class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded-full mb-3 text-gray-600"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"
            />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">Request New Session</h3>
        <p class="text-sm text-gray-500 mb-4">Share your knowledge with the team</p>
        <button
          @click="showModal = true"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200"
        >
          Request Session
        </button>
      </div>

      <!-- User Profile Card -->
      <div
        class="bg-blue-50 border border-blue-200 rounded-lg p-6 flex flex-col items-center text-center shadow-sm"
      >
        <div
          class="w-20 h-20 flex items-center justify-center bg-blue-200 rounded-full mb-4 text-blue-600"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-10 w-10"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-blue-800">{{ user.name }}</h3>
        <p class="text-sm text-blue-600">{{ user.designation }}</p>
        <p class="text-xs text-blue-400 mb-4">{{ user.email }}</p>

        <div class="flex justify-around w-full">
          <div class="text-center">
            <div class="font-bold text-lg text-blue-800">{{ sessionsCount }}</div>
            <div class="text-sm text-blue-600">Sessions</div>
          </div>
          <div class="text-center">
            <div class="font-bold text-lg text-blue-800">{{ badges.length }}</div>
            <div class="text-sm text-blue-600">Badges</div>
          </div>
        </div>
      </div>

      <!-- Notifications Card -->
<div style="background: white; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 1.5rem; max-width: 380px; font-family: 'Inter', sans-serif; border: 1px solid #e0e0e0;">
  <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <h2 style="font-weight: 700; font-size: 1.35rem; color: #333; margin: 0;">
      Upcoming Sessions <span style="background-color: #e0f2f7; color: #007bff; padding: 0.25rem 0.6rem; border-radius: 0.5rem; font-size: 1rem; margin-left: 0.5rem;">({{ upcomingSessions.length }})</span>
    </h2>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #999;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  </div>

  <div v-if="upcomingSessions.length > 0" style="display: flex; flex-direction: column; gap: 1.25rem;">
    <div v-for="session in upcomingSessions" :key="session.id" style="border: 1px solid #e0e0e0; border-radius: 0.625rem; padding: 1rem; background-color: #f9f9f9; transition: all 0.2s ease-in-out; cursor: pointer;">
      <div style="font-weight: 600; margin-bottom: 0.35rem; color: #444; font-size: 1rem;">{{ session.topic }}</div>
      <div style="color: #777; font-size: 0.875rem; margin-bottom: 0.6rem; line-height: 1.4;">
        {{ session.description || 'No description available' }}
      </div>
      <div style="font-size: 0.75rem; color: #999; display: flex; align-items: center;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1rem; height: 1rem; margin-right: 0.3rem;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12v-.008zM12 18h.008v.008H12v-.008z" />
        </svg>
        Scheduled Date: {{ formatDate(session.date) }}
      </div>
    </div>
  </div>

  <div v-else style="text-align: center; color: #888; font-style: italic; padding: 1rem 0;">
    No upcoming sessions.
  </div>
</div>


    </div>



<div class="mb-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
  <!-- Earned Badges Section -->
  <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Earned Badges ({{ badges.length }})</h2>
    <div class="flex flex-wrap gap-4 justify-center">
      <div
        v-for="badge in badges"
        :key="badge.id"
        class="flex flex-col items-center text-center w-28"
      >
        <!-- badge content -->
        <div
          class="w-14 h-14 flex items-center justify-center rounded-full mb-2"
          :class="{
            'bg-yellow-200': badge.name === 'Gold',
            'bg-gray-300': badge.name === 'Silver',
            'bg-yellow-700': badge.name === 'Bronze',
            'bg-purple-100': !['Gold', 'Silver', 'Bronze'].includes(badge.name)
          }"
        >
          <img
            v-if="['Gold', 'Silver', 'Bronze'].includes(badge.name)"
            :src="`/badges/${badge.name.toLowerCase()}.png`"
            :alt="badge.name"
            class="w-8 h-8"
          />
          <!-- fallback SVG if needed -->
        </div>
        <div class="font-bold text-gray-800 text-sm">{{ badge.name }}</div>
        <div class="text-xs text-gray-600">{{ badge.description }}</div>
        <div class="text-xs text-gray-500 mt-1">
          {{ new Date(badge.pivot.unlocked_at).toLocaleDateString() }}
        </div>
      </div>
    </div>
  </section>

  <!-- Milestone Progress Section -->
  <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-pink-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
      </svg>
      Milestone Progress
    </h2>

    <div class="flex flex-row gap-6 overflow-x-auto pb-2">
      <div
        v-for="milestone in milestones"
        :key="milestone.id"
        class="flex flex-col items-center"
        style="min-width:150px"
      >
        <img :src="milestone.icon" class="w-14 h-14 rounded-full mb-2" alt="" />
        <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
          <div
            class="bg-purple-500 h-2 rounded-full"
            :style="{ width: (Math.min(milestone.progress / milestone.target * 100, 100)) + '%' }"
          ></div>
        </div>
        <div class="text-sm font-bold mt-2">{{ milestone.progress }}/{{ milestone.target }}</div>
        <div class="text-xs text-gray-600">{{ milestone.name }}</div>
      </div>
    </div>
  </section>
</div>


<section class="mb-6">
  <div class="flex items-center gap-2 mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-gray-600">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625C9.75 8.004 10.254 7.5 10.875 7.5h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 019.75 19.875v-11.25zM16.5 4.125C16.5 3.504 17.004 3 17.625 3h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 0116.5 19.875v-15.75z" />
    </svg>
    <h2 class="text-xl font-bold text-gray-800">Complete Rankings</h2>
  </div>
  <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
    <div
      v-for="(user, index) in leaderboard.data"
      :key="user.id"
      class="flex items-center p-4"
      :class="{
        'bg-yellow-50': index === 0,
        'bg-gray-50': index === 1,
        'bg-orange-50': index === 2,
        'border-b border-gray-200': index < leaderboard.data.length - 1,
      }"
    >
      <div class="w-8 text-center font-semibold" :class="{
        'text-yellow-600': index === 0,
        'text-gray-600': index === 1,
        'text-orange-600': index === 2,
        'text-gray-500': index > 2,
      }">
        <span v-if="index === 0">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.292-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
          </svg>
        </span>
        <span v-else-if="index === 1">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
          </svg>
        </span>
        <span v-else-if="index === 2">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm.53 5.75a.75.75 0 00-1.5 0v3.75h-1.385c-.414 0-.628.474-.379.824l2.25 3.375a.75.75 0 001.299-.54l-.375-2.25h1.385c.414 0 .628-.474.379-.824L12.53 8.03z" clip-rule="evenodd" />
          </svg>
        </span>
        <span v-else>{{ index + 1 }}</span>
      </div>
      <img :src="user.profile_picture || 'defaults/default-avatar.jpg'" alt="Profile" class="w-10 h-10 rounded-full mr-4" />
      <div class="flex-grow">
        <div class="font-semibold text-gray-800">{{ user.name }}</div>
        <div class="text-sm text-gray-500">{{ user.designation }}</div>
      </div>
      <div class="flex items-center space-x-4 text-sm">
        <div class="text-center">
          <div class="font-bold">{{ user.badges_count }}</div>
          <div class="text-gray-500">Badges</div>
        </div>
        <div class="text-center">
          <div class="font-bold">{{ user.milestones_count }}</div>
          <div class="text-gray-500">Milestones</div>
        </div>
        <div class="text-center">
          <div class="font-bold">{{ user.project_sessions_count }}</div>
          <div class="text-gray-500">Sessions</div>
        </div>
        <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 rounded-full text-yellow-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
              <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.292-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
            </svg>
        </div>
      </div>
    </div>

    <div class="mt-4 flex justify-between text-xs text-blue-600 p-4">
       <Pagination :links="leaderboard.links" />
    </div>
  </div>
</section>

    <SessionCreate :is-open="showModal" @close="showModal = false" />
  </div>
  </AuthenticatedLayout>
</template>



<style scoped>

</style>
