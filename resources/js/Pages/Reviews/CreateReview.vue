<script>
export default {
  props: {
    // Accept both String and Number types, then convert internally
    employeeId: [String, Number],
    categories: Array,
    employeeName: { type: String},
    reviewMonth: [String, Number],
    reviewYear: [String, Number],
    cancelRoute: {
    type: String,
    default: 'reviews.team',
  },
   submitRouteName: {
    type: String,
    default: 'employee.review.store', 
  },
  },
  data() {
    const now = new Date();
    return {
      form: {
        scores: {},
        comments: {}, // keep if you want to save comments, otherwise remove
      },
      submitting: false,
      // Selected year and month for filter, default to current month/year
      selectedYear: now.getFullYear(),
      selectedMonth: now.getMonth() + 1,
    };
  },
  computed: {
    employeeIdNumber() {
      return Number(this.employeeId);
    },
    reviewMonthNumber() {
      // Use selectedMonth, fallback to prop if needed
      return this.selectedMonth || Number(this.reviewMonth);
    },
    reviewYearNumber() {
      // Use selectedYear, fallback to prop if needed
      return this.selectedYear || Number(this.reviewYear);
    },
    // Optional periodText for display if needed
    periodText() {
      if (!this.reviewMonthNumber || !this.reviewYearNumber) return '';
      return `${this.monthName(this.reviewMonthNumber)} ${this.reviewYearNumber}`;
    },
    canSubmit() {
      // Prevent submission if selected year/month is in future
      const now = new Date();
      const currentYear = now.getFullYear();
      const currentMonth = now.getMonth() + 1;
      
      if (this.selectedYear > currentYear) return false;
      if (this.selectedYear === currentYear && this.selectedMonth > currentMonth) return false;
      return true;
    },
  },
  methods: {
    monthName(monthNum) {
      return new Date(0, monthNum - 1).toLocaleString('default', { month: 'long' });
    },
    async onSubmit() {
    if (!this.canSubmit) {
      alert('Cannot submit a review for a future month.');
      return;
    }
    this.submitting = true;
    try {
      await this.$inertia.post(
        route(this.submitRouteName, {
          employeeId: this.employeeIdNumber,
          month: this.reviewMonthNumber,
          year: this.reviewYearNumber,
        }),
        this.form
      );
      this.$emit('submitted', this.cancelRoute);
    } catch (error) {
      console.error(error);
    } finally {
      this.submitting = false;
    }
  },


   onCancel() {
    this.$inertia.visit(route(this.cancelRoute));
  },
  },
};
</script>

<template>
  <div class="review-bg min-h-screen flex items-center justify-center py-8">
    <div class="review-card w-full max-w-3xl mx-auto p-8 rounded-2xl shadow-lg">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-main font-serif">
          Manager Review for <span class="text-accent font-bold">{{ employeeName }}</span>
        </h2>

        <!-- Filter: Year and Month selectors -->
        <div class="flex items-center space-x-4">
          <div>
            <label for="filter-year" class="block text-sm font-medium text-gray-700">Year</label>
            <select
              id="filter-year"
              v-model="selectedYear"
              class="mt-1 block pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-accent focus:border-accent rounded-md"
            >
              <!-- Generate range of years from 2000 to current year -->
              <option
                v-for="year in Array.from({ length: new Date().getFullYear() - 1999 }, (_, i) => 2000 + i)"
                :key="year"
                :value="year"
              >
                {{ year }}
              </option>
            </select>
          </div>
          <div>
            <label for="filter-month" class="block text-sm font-medium text-gray-700">Month</label>
            <select
              id="filter-month"
              v-model="selectedMonth"
              class="mt-1 block pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-accent focus:border-accent rounded-md"
            >
              <option v-for="m in 12" :key="m" :value="m">{{ monthName(m) }}</option>
            </select>
          </div>
        </div>
      </div>

      <div v-for="category in categories" :key="category.id" class="mb-8 review-section">
        <div class="review-section-header text-xl font-extrabold flex items-center mb-6">
          <span class="text-main">{{ category.name }}</span>
          <span
            class="ml-4 text-xs font-semibold bg-accent-light px-2 py-1 rounded-full text-accent border border-accent"
          >
            {{ category.weight }}% Weight
          </span>
        </div>

        <div
          v-for="criterion in category.criteria"
          :key="criterion.id"
          class="review-criterion-box flex items-center gap-4 bg-white shadow mb-5 px-4 py-3 rounded-lg"
        >
          <div class="flex-1">
            <div class="font-semibold text-main">{{ criterion.name }}</div>
            <!-- 
            <div class="text-xs text-gray-500">{{ criterion.description || '' }}</div> 
            -->
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
        <button
          type="button"
          @click="onCancel"
          class="px-4 py-2 rounded border border-gray-400 bg-white hover:bg-gray-100 font-semibold text-gray-700"
        >
          Cancel
        </button>
        <button
          type="button"
          @click="onSubmit"
          :disabled="submitting || !canSubmit"
          class="px-6 py-2 rounded bg-black text-white font-bold text-lg hover:bg-accent transition disabled:opacity-50"
        >
          Submit Review
        </button>
      </div>
    </div>
  </div>
</template>

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
input[type='number']::-webkit-inner-spin-button,
input[type='number']::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type='number'] {
  appearance: textfield;
}
</style>
