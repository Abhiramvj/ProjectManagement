<template>
  <AuthenticatedLayout>
    <div class="review-bg min-h-screen flex items-center justify-center py-8">
      <div class="review-card w-full max-w-3xl mx-auto p-8 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-8">
          <h2 class="text-3xl font-bold text-main font-serif">
            Manager Review for <span class="text-accent font-bold">{{ employeeName }}</span>
          </h2>
          <div class="text-lg text-main">
            Review Period: <span class="font-semibold underline cursor-pointer text-accent">{{ periodText }}</span>
          </div>
        </div>

        <div v-for="category in categories" :key="category.id" class="mb-8 review-section">
          <div class="review-section-header text-xl font-extrabold flex items-center mb-6">
            <span class="text-main">{{ category.name }}</span>
            <span class="ml-4 text-xs font-semibold bg-accent-light px-2 py-1 rounded-full text-accent border border-accent">
              {{ category.weight }}% Weight
            </span>
          </div>

          <div v-for="criterion in category.criteria" :key="criterion.id" class="review-criterion-box flex items-center gap-4 bg-white shadow mb-5 px-4 py-3 rounded-lg">
            <div class="flex-1">
              <div class="font-semibold text-main">{{ criterion.name }}</div>
              <!-- Description removed to simplify submission
              <div class="text-xs text-gray-500">{{ criterion.description || '' }}</div> -->
            </div>
            <div class="flex items-center gap-2">
              <input
                type="number"
                min="1"
                max="10"
                class="score-input border-2 border-accent rounded px-3 py-2 text-center text-lg font-semibold w-16 focus:outline-none focus:ring-2 focus:ring-accent"
                v-model.number="form.scores[criterion.id]"
                placeholder=""
              />
              <span class="text-gray-500 text-base font-semibold">/ 10</span>
            </div>
          </div>
        </div>

        <div class="flex justify-end mt-6 gap-4">
          <button type="button" @click="onCancel" class="px-4 py-2 rounded border border-gray-400 bg-white hover:bg-gray-100 font-semibold text-gray-700">Cancel</button>
          <button type="button" @click="onSubmit" class="px-6 py-2 rounded bg-black text-white font-bold text-lg hover:bg-accent transition">Submit Review</button>
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
    // Fix: Accept both String and Number types, then convert internally
    employeeId: [String, Number],
    categories: Array,
    employeeName: { type: String, default: 'Employee User' },
    reviewMonth: [String, Number],
    reviewYear: [String, Number],
  },
  data() {
    return {
      form: {
        scores: {},
        comments: {}, // keep if you want to save comments, otherwise remove
      },
      submitting: false,
      showModal: false,
    };
  },
  computed: {
    // Convert to numbers for internal use
    employeeIdNumber() {
      return Number(this.employeeId);
    },
    reviewMonthNumber() {
      return Number(this.reviewMonth);
    },
    reviewYearNumber() {
      return Number(this.reviewYear);
    },
    periodText() {
      if (!this.reviewMonthNumber || !this.reviewYearNumber) return '';
      return `${this.monthName(this.reviewMonthNumber)} ${this.reviewYearNumber}`;
    },
  },
  methods: {
    monthName(monthNum) {
      return new Date(0, monthNum - 1).toLocaleString('default', { month: 'long' });
    },
    async onSubmit() {
      this.submitting = true;
      try {
        await this.$inertia.post(
          route('employee.review.store', {
            employeeId: this.employeeIdNumber,  // use computed property
            month: this.reviewMonthNumber,
            year: this.reviewYearNumber,
          }),
          this.form
        );
        this.showModal = false;
      } catch (error) {
        console.error(error);
      } finally {
        this.submitting = false;
      }
    },
    onCancel() {
      this.$inertia.visit(route('reviews.team'));
    },
  },
};
</script>

<style scoped>
.review-bg {
  background: #eaf3fb;
  display: block;
  padding: 0;
}
.review-card {
  background: #f4faff;
  width: 100%;
  max-width: none;
  margin: 0;
  min-height: 100vh;
  border-radius: 0;
  box-shadow: none;
  padding: 0;
}
.review-card > div {
  padding: 2rem;
}

.text-main {
  color: #163a87;
}
.text-accent {
  color: #2851bb;
}
.bg-accent-light {
  background: #deebff;
}
.bg-accent-bg {
  background: #f4faff;
}
.review-section-header {
  border-bottom: 2px solid #d0e4ff;
  padding-bottom: 0.25rem;
}
.review-criterion-box {
  box-shadow: 0 2px 6px 0 rgba(44, 95, 166, 0.07);
}
.score-input:focus {
  outline: none;
  ring: 2px solid #2863e1;
}
.comment-input:focus {
  outline: none;
  ring: 2px solid #b5d2fc;
}
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  appearance: textfield;
}
</style>
