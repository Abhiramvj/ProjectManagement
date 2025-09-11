<script>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';


export default {
    layout: AuthenticatedLayout,
  name: 'EmployeeHistory',
  props: {
    member: Object, // id, name, role, email
    reviewPeriod: String, // e.g. "March 2024"
    metrics: Array, // current metrics for summary [{ name, value }]
    overallScore: Number,
    lastReview: String,
    selfReview: Object,      // { scores: {criteria_id: score}, comments: {criteria_id: string} }
    managerReview: Object,   // { scores: {criteria_id: score}, comments: {criteria_id: string} }
    categories: Array        // [{ id, name, criteria: [...], weight }]
  },
  setup(props) {
    const tab = ref('overview');

    // Filter dropdown reactive state
    const selectedMonth = ref(new Date().getMonth() + 1);
    const selectedYear = ref(new Date().getFullYear());

    // Month options
    const months = [
      { value: 1, label: 'January' },
      { value: 2, label: 'February' },
      { value: 3, label: 'March' },
      { value: 4, label: 'April' },
      { value: 5, label: 'May' },
      { value: 6, label: 'June' },
      { value: 7, label: 'July' },
      { value: 8, label: 'August' },
      { value: 9, label: 'September' },
      { value: 10, label: 'October' },
      { value: 11, label: 'November' },
      { value: 12, label: 'December' }
    ];

    // Year options (adjust as needed)
    const years = [2024, 2025];



    const fetchPeriodReview = () => {
      router.get(
  `/employee/${props.member.id}/reviews`,
  { month: selectedMonth.value, year: selectedYear.value },
  { preserveState: true }
);
    };

    // Compute self average score
    const selfAvg = computed(() => {
      const vals = Object.values(props.selfReview?.scores || {});
      return vals.length ? (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1) : 'N/A';
    });

    // Compute manager average score
    const mgrAvg = computed(() => {
      const vals = Object.values(props.managerReview?.scores || {});
      return vals.length ? (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1) : 'N/A';
    });

    return {
      tab, selfAvg, mgrAvg,
      selectedMonth, selectedYear, months, years, fetchPeriodReview
    };
  }
};
</script>

<template>
    <AuthenticatedLayout>
  <div class="min-h-screen bg-gray-50 py-10">
    <div class="max-w-4xl mx-auto">

      <button
        @click="$inertia.visit('/reviews/team')"
        class="flex items-center mb-4 text-blue-800 hover:underline font-medium"
      >
        <span class="text-lg mr-2">&larr;</span> Back to Team
      </button>

      <!-- Employee Card -->
      <div class="bg-white rounded-xl shadow p-8 mb-8">
        <div class="flex items-center mb-5 gap-6">
          <!-- Avatar -->
          <div class="bg-gray-100 text-gray-800 font-bold text-2xl rounded-full w-16 h-16 flex items-center justify-center uppercase mr-4">
            {{ member.name.split(' ').map(n => n[0]).join('') }}
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900">{{ member.name }}</div>
            <div class="text-gray-700 text-base">{{ member.role }}</div>
            <div class="flex flex-wrap items-center text-sm mt-1 gap-2 text-gray-500">
              <span>{{ member.email }}</span>
              <span class="block">&bull;</span>
              <span>Last Review: {{ lastReview || 'N/A' }}</span>
            </div>
          </div>
          <div class="ml-auto text-right">
            <div class="text-3xl font-extrabold text-gray-900 mb-1">{{ overallScore ? overallScore.toFixed(1) : 'N/A' }}/10</div>
            <div class="text-sm font-semibold px-3 py-1 rounded bg-green-100 text-green-800 w-fit mx-auto">Completed</div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-6">
          <button
            :class="['px-4 py-2 rounded font-semibold', tab === 'overview' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200']"
            @click="tab = 'overview'"
          >Current Overview</button>
          <button
            :class="['px-4 py-2 rounded font-semibold', tab === 'history' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200']"
            @click="tab = 'history'"
          >Performance History</button>
        </div>

        <div v-if="tab === 'overview'">
          <div class="font-bold mb-4 text-gray-900">PERFORMANCE METRICS</div>
          <div v-for="metric in metrics" :key="metric.name" class="flex items-center mb-3">
            <div class="w-1/2 text-gray-900 font-semibold">{{ metric.name }}</div>
            <div class="flex-1 mx-4 h-2 bg-gray-300 rounded">
              <div :style="{ width: Math.round((metric.value / 10) * 100) + '%' }" class="h-2 rounded bg-blue-700"></div>
            </div>
            <div class="w-12 text-right text-blue-900 font-bold">{{ metric.value }}/10</div>
          </div>
        </div>

        <div v-else>
          <div class="flex flex-col sm:flex-row sm:items-center mb-6 gap-4">
            <div class="flex gap-2">
              <select v-model="selectedMonth" class="border rounded px-3 py-2">
                <option v-for="m in months" :value="m.value" :key="m.value">{{ m.label }}</option>
              </select>
              <select v-model="selectedYear" class="border rounded px-3 py-2">
                <option v-for="y in years" :value="y" :key="y">{{ y }}</option>
              </select>
            </div>
           <button
  class="ml-0 sm:ml-4 px-5 py-2 bg-gray-800 text-white font-bold rounded hover:bg-gray-600"
  @click="fetchPeriodReview"
>
  Search
</button>

          </div>

          <div class="font-bold mb-1 text-lg text-gray-900">{{ member.name }} - Performance History</div>
          <div class="text-gray-600 text-sm mb-6">Monthly performance tracking and comparison</div>
          <div class="flex flex-col md:flex-row gap-5">
            <!-- Self Review -->
            <div class="flex-1 border-[2px] border-blue-200 bg-white rounded-lg p-6">
              <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold text-lg text-blue-900">Self Review</h3>
                <span class="px-3 py-1 text-sm bg-blue-700 text-white rounded-full font-semibold">{{ selfAvg }}/10</span>
              </div>
              <div v-for="cat in categories" :key="cat.id" class="mb-3">
                <div v-for="cri in cat.criteria" :key="cri.id" class="mb-4">
                  <div class="flex justify-between items-center mb-1">
                    <div class="text-gray-900 font-semibold">{{ cri.name }}</div>
                    <div class="font-semibold text-right text-blue-800">
                      {{ selfReview.scores?.[cri.id] !== undefined ? selfReview.scores[cri.id] : 'N/A' }}/10
                    </div>
                  </div>
                  <div class="bg-gray-300 h-2 rounded relative mb-1">
                    <div
                      class="bg-blue-700 h-2 rounded absolute top-0 left-0"
                      :style="{ width: ((selfReview.scores?.[cri.id] || 0) * 10) + '%' }"
                    ></div>
                  </div>
                  <!-- Comment box -->
                  <div v-if="selfReview.comments?.[cri.id]" class="text-sm text-gray-800 px-2 py-1 rounded bg-blue-50 border border-blue-200 mt-2">
                    {{ selfReview.comments[cri.id] }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Manager Review -->
            <div class="flex-1 border-[2px] border-green-200 bg-white rounded-lg p-6">
              <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold text-lg text-green-900">Manager Review</h3>
                <span class="px-3 py-1 text-sm bg-green-700 text-white rounded-full font-semibold">{{ mgrAvg }}/10</span>
              </div>
              <div v-for="cat in categories" :key="cat.id" class="mb-3">
                <div v-for="cri in cat.criteria" :key="cri.id" class="mb-4">
                  <div class="flex justify-between items-center mb-1">
                    <div class="text-gray-900 font-semibold">{{ cri.name }}</div>
                    <div class="font-semibold text-right text-green-800">
                      {{ managerReview.scores?.[cri.id] !== undefined ? managerReview.scores[cri.id] : 'N/A' }}/10
                    </div>
                  </div>
                  <div class="bg-gray-300 h-2 rounded relative mb-1">
                    <div
                      class="bg-green-700 h-2 rounded absolute top-0 left-0"
                      :style="{ width: ((managerReview.scores?.[cri.id] || 0) * 10) + '%' }"
                    ></div>
                  </div>
                  <!-- Comment box -->
                  <div v-if="managerReview.comments?.[cri.id]" class="text-sm text-gray-800 px-2 py-1 rounded bg-green-50 border border-green-200 mt-2">
                    {{ managerReview.comments[cri.id] }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
  </AuthenticatedLayout>
</template>
