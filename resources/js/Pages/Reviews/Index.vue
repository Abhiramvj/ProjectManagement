<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

export default {
     components: {
    Pagination,
    AuthenticatedLayout,
  },

  props: {
    reviews: Array,
    categories: Array,
    meta: Object,
    links: Array,
  },
  data() {
    return {
      showModal: false,
      submitting: false,
      form: {
        review_month: new Date().getMonth() + 1,
        review_year: new Date().getFullYear(),
        scores: {},
      },
    };
  },
  methods: {
    monthName(monthNum) {
      return new Date(0, monthNum - 1).toLocaleString('default', { month: 'long' });
    },
    calculateOverallScore(review) {
      if (!review.scores || !Array.isArray(review.scores) || review.scores.length === 0 || !this.categories.length) return 0;

      let totalWeight = 0;
      let weightedSum = 0;

      this.categories.forEach((cat) => {
        const avg = this.categoryScore(review, cat);
        weightedSum += avg * cat.weight;
        totalWeight += cat.weight;
      });

      return totalWeight ? weightedSum / totalWeight : 0;
    },
    categoryScore(review, category) {
      if (!review.scores || !Array.isArray(review.scores)) return 0;

      const criterionIds = category.criteria.map((c) => c.id);
      const scores = review.scores.filter((score) => criterionIds.includes(score.criteria_id));
      if (scores.length === 0) return 0;

      const sum = scores.reduce((acc, curr) => acc + curr.score, 0);
      return sum / scores.length;
    },
    async submitReview() {
      this.submitting = true;
      try {
         await this.$inertia.post(route('selfReview.store', { month: this.form.review_month, year: this.form.review_year }), {
      scores: this.form.scores,
    });
      } catch (error) {
        console.error(error);
      } finally {
        this.submitting = false;
      }
    },
    onPageChange(url) {
      this.$inertia.get(url, {}, { preserveState: true, preserveScroll: true });
    },
  },
};
</script>

<template>
  <AuthenticatedLayout>
    <div class="p-6 main-bg">
      <h1 class="text-2xl font-semibold mb-6 flex items-center justify-between main-heading">
        My Reviews
        <button
          @click="showModal = true"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          New Review
        </button>
      </h1>

      <!-- Reviews List -->
      <div v-if="reviews && reviews.length" class="space-y-6">
        <div v-for="review in reviews" :key="review.id" class="border p-4 rounded shadow-sm review-block">
          <div class="flex justify-between items-center mb-2 review-header">
            <div class="text-lg font-bold review-date">
              {{ monthName(review.review_month) }} {{ review.review_year }}
            </div>
            <div class="font-semibold review-overall">
              Overall Score: {{ calculateOverallScore(review).toFixed(1) }}/10
            </div>
          </div>
          <div class="grid grid-cols-4 gap-4 mb-3 review-row">
            <div
              v-for="category in categories"
              :key="category.id"
              class="review-card"
            >
              <div class="review-category-name">
                {{ category.name }}
              </div>
              <div class="review-score">
                {{ categoryScore(review, category).toFixed(1) }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="no-reviews-text">No reviews found.</div>

       <Pagination
        :meta="meta"
        :links="links"
        @page-changed="onPageChange"
      />

      <!-- New Review Modal -->
      <transition name="fade">
        <div
          v-if="showModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded p-6 w-full max-w-2xl max-h-[90vh] overflow-auto relative modal-content">
            <h2 class="text-xl font-semibold mb-4 modal-text">New Review</h2>

            <form @submit.prevent="submitReview">
              <div class="mb-4 flex gap-4">
                <div>
                  <label for="review_month" class="block font-medium mb-1 modal-text">Month</label>
                  <select id="review_month" v-model="form.review_month" required class="border rounded p-2 w-full">
                    <option v-for="m in 12" :key="m" :value="m">{{ monthName(m) }}</option>
                  </select>
                </div>

                <div>
                  <label for="review_year" class="block font-medium mb-1 modal-text">Year</label>
                  <input
                    id="review_year"
                    type="number"
                    v-model.number="form.review_year"
                    min="2000"
                    max="2100"
                    required
                    class="border rounded p-2 w-24"
                  />
                </div>
              </div>

              <div v-for="category in categories" :key="category.id" class="mb-6 border p-4 rounded">
                <h3 class="font-semibold mb-3 modal-text">{{ category.name }} (Weight: {{ category.weight }}%)</h3>

                <div v-for="criterion in category.criteria" :key="criterion.id" class="mb-3">
                  <label :for="'criterion-' + criterion.id" class="block mb-1 modal-text">{{ criterion.name }}</label>
                  <input
                    type="number"
                    :id="'criterion-' + criterion.id"
                    min="1"
                    max="10"
                    v-model.number="form.scores[criterion.id]"
                    class="w-full border rounded p-2"
                  />
                </div>
              </div>

              <div class="flex justify-end gap-4">
                <button type="button" @click="showModal = false" class="px-4 py-2 border rounded">Cancel</button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                  :disabled="submitting"
                >
                  Submit Review
                </button>
              </div>
            </form>

            <button
              @click="showModal = false"
              class="absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-xl font-bold"
              aria-label="Close"
            >
              &times;
            </button>
          </div>
        </div>
      </transition>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.main-bg {
  background: #fff !important;
  min-height: 100vh;
}
.main-heading {
  color: #222 !important;
}
.review-block {
  background: #fafaff;
}
.review-header {
  background: #fff;
  color: #222 !important;
  font-weight: 600;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 0.5rem;
}
.review-date {
  color: #23509e !important;
  font-weight: 700;
  font-size: 1.1rem;
}
.review-overall {
  color: #16a34a !important;
  font-size: 1.1rem;
  font-weight: 700;
}
.review-row {
  background: none;
}
.review-card {
  background: #f3f6fa !important;
  color: #202124 !important;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.2rem 0.5rem;
  text-align: center;
  width: 100%;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.review-category-name {
  color: #23509e !important;
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
}
.review-score {
  color: #222 !important;
  font-size: 1.5rem;
  font-weight: 700;
}
.no-reviews-text {
  color: #555 !important;
  margin-top: 2rem;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Modal specific styles */
.modal-content {
  color: black !important; /* Change all text within the modal to black */
}

.modal-text {
  color: black !important;
}
</style>
