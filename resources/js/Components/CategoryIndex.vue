<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Review Categories</h2>

    <!-- Add Category Form -->
    <div class="mb-6 p-4 border rounded shadow">
      <h3 class="text-lg font-semibold mb-2">Add New Category</h3>
      <form @submit.prevent="addCategory" class="space-y-3">
        <div>
          <label class="block text-sm font-medium">Category Name</label>
          <input
            v-model="newCategory.name"
            type="text"
            placeholder="e.g. Technical Skills"
            class="w-full border p-2 rounded"
          />
        </div>
        <div>
          <label class="block text-sm font-medium">Description</label>
          <input
            v-model="newCategory.description"
            type="text"
            placeholder="Optional description"
            class="w-full border p-2 rounded"
          />
        </div>
        <div v-if="validationErrors.length" class="bg-red-100 border border-red-400 text-red-700 p-2 rounded">
          <ul>
            <li v-for="(msg, i) in validationErrors" :key="i">{{ msg }}</li>
          </ul>
        </div>
        <button
          type="submit"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          Add Category
        </button>
      </form>
    </div>

    <!-- Category List -->
    <div>
      <h3 class="text-lg font-semibold mb-2">Existing Categories</h3>
      <div
        v-if="categories.length"
        class="grid grid-cols-1 md:grid-cols-2 gap-4"
      >
        <div
          v-for="category in categories.filter(c => c.name)"
          :key="category.id"
          class="border p-4 rounded shadow"
        >
          <h4 class="font-bold text-lg">{{ category.name }}</h4>
          <p class="text-gray-600 text-sm">
            {{ category.description || "No description provided" }}
          </p>
          <div v-if="category.criteria?.length" class="mt-3">
            <h5 class="font-semibold text-sm">Criteria:</h5>
            <ul class="list-disc list-inside text-sm">
              <li v-for="crit in category.criteria" :key="crit.id">
                {{ crit.name }}
              </li>
            </ul>
          </div>
          <div v-else class="text-gray-500 text-sm mt-2">
            No criteria assigned yet.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "ReviewCategory",
  data() {
    return {
      categories: [],
      newCategory: {
        name: "",
        description: "",
      },
      validationErrors: [],
    };
  },
  mounted() {
    this.fetchCategories();
  },
  methods: {
    async fetchCategories() {
      try {
        const response = await axios.get("/review-categories");
        this.categories = Array.isArray(response.data.categories)
          ? response.data.categories
          : [];
      } catch {
        this.categories = [];
      }
    },
    async addCategory() {
      this.validationErrors = [];
      if (!this.newCategory.name.trim()) {
        this.validationErrors.push("Category name is required.");
        return;
      }
      try {
        await axios.post(
          "/review-categories",
          {
            name: this.newCategory.name,
            description: this.newCategory.description,
          },
          {
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            },
          }
        );
        this.newCategory = { name: "", description: "" };
        await this.fetchCategories();
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors;
          if (errors) {
            this.validationErrors = Object.values(errors).flat();
          } else if (error.response.data.message) {
            this.validationErrors = [error.response.data.message];
          }
        } else {
          this.validationErrors = ["An unknown error occurred."];
        }
      }
    },
  },
};
</script>

<style scoped>
</style>
