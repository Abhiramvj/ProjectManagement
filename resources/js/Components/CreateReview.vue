<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

// Props passed down with pre-fetched data to avoid axios lag
defineProps({
  users: Array,      // employees list
  criterias: Array,  // review criteria list
  categories: Array, // review categories list, if needed
})

const months = ref([
  { name: 'January', value: 'January' },
  { name: 'February', value: 'February' },
  { name: 'March', value: 'March' },
  { name: 'April', value: 'April' },
  { name: 'May', value: 'May' },
  { name: 'June', value: 'June' },
  { name: 'July', value: 'July' },
  { name: 'August', value: 'August' },
  { name: 'September', value: 'September' },
  { name: 'October', value: 'October' },
  { name: 'November', value: 'November' },
  { name: 'December', value: 'December' },
])

const form = useForm({
  user_id: '',
  criteria_id: '',
  month: '',
  year: new Date().getFullYear(),
  score: 0,
  rating: '',
})

const submit = () => {
  form.post(route('reviews.store'), {
    onSuccess: () => {
      form.reset('criteria_id', 'month', 'score', 'rating')
      form.user_id = ''
      form.year = new Date().getFullYear()
    },
  })
}
</script>

<style scoped></style>

<template>
  <div class="p-6 max-w-md mx-auto">
    <h2 class="text-xl font-bold mb-4">Add Review</h2>

    <div class="mb-6 flex space-x-4">
<button
  @click="$emit('openCategoryModal')"
  class="px-4 py-2 rounded-xl bg-blue-600 text-white font-medium shadow hover:bg-blue-700 transition"
>
  Go to Review Categories
</button>

<button
  @click="$emit('openCriteriaModal')"
  class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-medium shadow hover:bg-indigo-700 transition"
>
  Go to Review Criteria
</button>

    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block mb-1 font-semibold">Employee (User)</label>
        <select v-model="form.user_id" class="w-full border rounded p-2" required>
          <option disabled value="">Select employee</option>
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Criteria</label>
        <select v-model="form.criteria_id" class="w-full border rounded p-2" required>
          <option disabled value="">Select criteria</option>
          <option v-for="crit in criterias" :key="crit.id" :value="crit.id">
            {{ crit.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Month</label>
        <select v-model="form.month" class="w-full border rounded p-2" required>
          <option disabled value="">Select month</option>
          <option v-for="month in months" :key="month.value" :value="month.value">
            {{ month.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Year</label>
        <input
          type="number"
          v-model.number="form.year"
          min="2000"
          max="2100"
          class="w-full border rounded p-2"
          required
        />
      </div>

      <div>
        <label class="block mb-1 font-semibold">Score</label>
        <input
          type="number"
          v-model.number="form.score"
          min="0"
          max="100"
          class="w-full border rounded p-2"
          required
        />
      </div>

      <div>
        <label class="block mb-1 font-semibold">Rating (optional)</label>
        <input
          type="text"
          v-model="form.rating"
          placeholder="e.g. Exceeds Expectations"
          class="w-full border rounded p-2"
        />
      </div>

      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        Submit Review
      </button>
    </form>
  </div>
</template>
