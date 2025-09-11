<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import CreateReview from '@/Pages/Reviews/CreateReview.vue';  // Import CreateReview page component as modal

export default {
  components: {
    Pagination,
    AuthenticatedLayout,
    CreateReview,
  },

  props: {
  employeeId: [String, Number],
  categories: Array,
  reviewMonth: [String, Number],
  reviewYear: [String, Number],
  cancelRoute: { type: String, default: 'myReviews' },
    reviews: Array,
    categories: Array,
    meta: Object,
    links: Array,
  },

data() {
  const now = new Date();
  return {
    showModal: false,
    submitting: false,
    modalEmployeeId: this.$page.props.auth.user.id, // gets current user id from Inertia page props
    selectedReviewMonth: now.getMonth() + 1,
    selectedReviewYear: now.getFullYear(),
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

  openCreateReviewModal() {
  const now = new Date();
  this.selectedReviewMonth = now.getMonth() + 1;
  this.selectedReviewYear = now.getFullYear();
  this.modalEmployeeId = this.$page.props.auth.user.id;
  this.showModal = true;
},

    handleModalClose() {
      this.showModal = false;
    },
async onSubmit() {
  try {
    await this.$inertia.post(
      route(this.submitRouteName),
      {
        employee_id: this.employeeIdNumber,
        month: this.reviewMonthNumber,
        year: this.reviewYearNumber,
        scores: this.form.scores,
        comments: this.form.comments,
      },
      {
        onSuccess: () => {
          this.$emit('submitted');
        },
      }
    );
  } catch (error) {
    console.error("Error submitting review:", error);
  }
},

handleModalSubmitted() {

  this.showModal = false;   
  this.modalEmployeeId = null;

  this.$inertia.reload({ only: ['reviews'] });
},




    onPageChange(url) {
      this.$inertia.get(url, {}, { preserveState: true, preserveScroll: true });
    },
  },
};
</script>

<template>
  <AuthenticatedLayout>
    <div class="p-6 main-bg text-black">
      <h1 class="text-2xl font-semibold mb-6 flex items-center justify-between main-heading">
        My Reviews
        <button
          v-if="reviews && reviews.length"
          @click="openCreateReviewModal"
          class="px-4 py-2 bg-gray-800 text-white font-semibold text-lg rounded hover:bg-gray-900 shadow transition duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400"
        >
          New Review
        </button>
      </h1>

      <div v-if="reviews && reviews.length" class="space-y-6">
       <div
  v-for="review in reviews"
  :key="review.id"
  class="border p-2 rounded-lg shadow bg-white hover:shadow-md transition review-block"
>
          <div class="flex justify-between items-center mb-1 review-header">
            <div class="text-lg font-bold text-gray-800 review-date">
              {{ monthName(review.review_month) }} {{ review.review_year }}
            </div>
            <div class="w-48">
  <div class="flex justify-between mb-1">
    <span class="font-semibold text-blue-700">Overall Score</span>
    <span class="font-bold text-blue-800">{{ calculateOverallScore(review).toFixed(1) }}/10</span>
  </div>
  <div class="w-full bg-gray-200 rounded-full h-3">
    <div
      class="bg-blue-600 h-3 rounded-full transition-all duration-500"
      :style="{ width: (calculateOverallScore(review) * 10) + '%' }"
    ></div>
  </div>
</div>

          </div>
           <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 mb-2 review-row">
           <div
      v-for="category in categories"
      :key="category.id"
      class="review-card bg-gray-50 rounded p-1 flex flex-col items-center border hover:bg-blue-50 transition"
    >
               <div class="review-category-name text-gray-700 text-sm font-medium mb-0.5">{{ category.name }}</div>
      <div class="review-score text-lg font-bold text-blue-800">{{ categoryScore(review, category).toFixed(1) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center py-20 text-center no-reviews-interactive">
        <img
          src="/storage/defaults/no-reviews.png"
          alt="No reviews found illustration"
          class="w-64 h-64 mb-8 opacity-90"
        />
        <h2 class="text-3xl font-bold mb-4 text-gray-800 interactive-heading">Time to Reflect & Grow!</h2>
        <p class="text-lg text-gray-600 mb-8 interactive-text max-w-xl mx-auto">
          Looks like you haven't submitted any reviews yet. Start your first one now to progress and provide valuable feedback.
        </p>
        <button
          @click="openCreateReviewModal"
          class="px-6 py-3 bg-gray-700 text-white rounded-lg text-lg font-semibold hover:bg-gray-900 shadow transition duration-300 focus:outline-none focus:ring-2 focus:ring-gray-400"
        >
          New Review
        </button>
      </div>

      <div class="mt-8">
        <Pagination :meta="meta" :links="links" @page-changed="onPageChange" />
      </div>

      <!-- CreateReview used as modal -->
   <transition name="fade">
  <div
    v-if="showModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="handleModalClose"
  >
    <div
      class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-auto relative modal-content shadow-xl border"
      @click.stop
    >
      <button
        class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-2xl font-bold focus:outline-none"
        @click="handleModalClose"
        aria-label="Close">
        &times;
      </button>
      <CreateReview
  :employee-id="modalEmployeeId"
  :categories="categories"
  :review-month="selectedReviewMonth"
  :review-year="selectedReviewYear"
  cancelRoute="myReviews"
  submitRouteName="selfReview.store"
  @cancel="handleModalClose"
  @submitted="handleModalSubmitted"
/>

    </div>
  </div>
</transition>

    </div>
  </AuthenticatedLayout>
</template>
