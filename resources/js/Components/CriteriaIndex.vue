<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Manage Review Criteria</h2>

    <form @submit.prevent="submit">
      <div class="mb-2">
        <label class="block text-sm">Category</label>
        <select v-model="form.category_id" class="border rounded px-2 py-1 w-full" required>
          <option disabled value="">Please select a category</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
      </div>

      <div class="mb-2">
        <label class="block text-sm">Criteria Name</label>
        <input v-model="form.name" class="border rounded px-2 py-1 w-full" required />
      </div>

      <div class="mb-2">
        <label class="block text-sm">Max Points</label>
        <input type="number" v-model.number="form.max_points" class="border rounded px-2 py-1 w-full" min="1" required />
      </div>

      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
        Add Criteria
      </button>
    </form>

    <hr class="my-6" />

    <div v-for="cat in categories" :key="cat.id" class="mb-4">
      <h3 class="font-semibold">{{ cat.name }} (Weight: {{ cat.weight || 0 }}%)</h3>
      <ul class="list-disc ml-6">
        <li v-for="crit in cat.criteria || []" :key="crit.id">
          {{ crit.name }} - Max: {{ crit.max_points }}
          <button @click="destroy(crit.id)" class="text-red-600 ml-2">Delete</button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'

defineProps({ categories: { type: Array, default: () => [] } })

const form = useForm({
  category_id: "",
  name: "",
  max_points: 10,
})

const submit = () => {
  // Validate client-side if needed
  if (!form.category_id || !form.name || form.max_points < 1) {
    alert("Please fill in all fields properly.");
    return;
  }
  form.post(route('criteria.store'), {
    onSuccess: () => {
      form.reset("category_id", "name", "max_points")
      form.max_points = 10; // reset with default max points
    },
  })
}

const destroy = (id) => {
  if (confirm('Delete this criteria?')) {
    router.delete(route('criteria.destroy', id))
  }
}
</script>

<style scoped></style>
