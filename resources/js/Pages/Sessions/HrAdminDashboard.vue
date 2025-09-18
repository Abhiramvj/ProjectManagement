<script>
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HrToggleBar from '@/Components/HrToggleBar.vue';

export default {
  name: 'HrAdminDashboard',
  components: {
    Head,
    AuthenticatedLayout,
    HrToggleBar

  },
  props: {
    sessions: {
      type: Array,
      default: () => [],
    },
    pendingCount: {
      type: Number,
      default: 0,
    },
    approvedCount: {
      type: Number,
      default: 0,
    },
    rejectedCount: {
      type: Number,
      default: 0,
    },
    completedCount: {
      type: Number,
      default: 0,
    },
  },
  data() {
    return {
      processing: false,
      editingDate: null,
      newDate: '',
      showToast: false,
      toastMessage: '',
      toastType: 'success',
      today: new Date().toISOString().split('T')[0],
    };
  },
  mounted() {
    if (this.$page.props.flash?.success) {
      this.showToastMessage(this.$page.props.flash.success, 'success');
    }
    if (this.$page.props.flash?.error) {
      this.showToastMessage(this.$page.props.flash.error, 'error');
    }
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
     goToBadges() {
    router.visit(route('hr.badges'));
  },

    toggleDateEditor(sessionId) {
      if (this.editingDate === sessionId) {
        this.cancelDateEdit();
      } else {
        this.editingDate = sessionId;
        const session = this.sessions.find((s) => s.id === sessionId);
        this.newDate = session?.date
          ? new Date(session.date).toISOString().split('T')[0]
          : this.today;
      }
    },

    cancelDateEdit() {
      this.editingDate = null;
      this.newDate = '';
    },

    async approveSession(sessionId) {
      if (this.processing) return;
      this.processing = true;
      try {
        await router.post(
          `/hr/sessions/${sessionId}/approve`,
          {},
          {
            preserveState: false,
            onSuccess: () => {
              this.showToastMessage('Session approved successfully!', 'success');
              router.reload(); // reload to update dashboard data
            },
            onError: () => {
              this.showToastMessage('Failed to approve session', 'error');
            },
          }
        );
      } catch (error) {
        this.showToastMessage('An error occurred while approving the session', 'error');
      } finally {
        this.processing = false;
      }
    },

    async rejectSession(sessionId) {
      if (this.processing) return;
      if (!confirm('Are you sure you want to reject this session request?')) return;
      this.processing = true;
      try {
        await router.post(
          `/hr/sessions/${sessionId}/reject`,
          {},
          {
            preserveState: false,
            onSuccess: () => {
              this.showToastMessage('Session rejected successfully!', 'success');
              router.reload();
            },
            onError: () => {
              this.showToastMessage('Failed to reject session', 'error');
            },
          }
        );
      } catch (error) {
        this.showToastMessage('An error occurred while rejecting the session', 'error');
      } finally {
        this.processing = false;
      }
    },

    async updateSessionDate(sessionId) {
      if (this.processing || !this.newDate) return;
      this.processing = true;
      try {
        await router.put(
          `/hr/sessions/${sessionId}/update-date`,
          { date: this.newDate },
          {
            preserveState: false,
            onSuccess: () => {
              this.showToastMessage('Session date updated successfully!', 'success');
              this.cancelDateEdit();
              router.reload(); // reload to update session data
            },
            onError: () => {
              this.showToastMessage('Failed to update session date', 'error');
            },
          }
        );
      } catch (error) {
        this.showToastMessage('An error occurred while updating the date', 'error');
      } finally {
        this.processing = false;
      }
    },

    async markAsComplete(sessionId) {
      if (this.processing) return;
      this.processing = true;
      try {
        await router.post(
          `/hr/sessions/${sessionId}/complete`,
          {},
          {
            preserveState: false,
            onSuccess: () => {
              this.showToastMessage('Session marked as complete!', 'success');
              router.reload(); // reload to update completed status and counts
            },
            onError: () => {
              this.showToastMessage('Failed to mark as complete', 'error');
            },
          }
        );
      } catch (error) {
        this.showToastMessage('An error occurred while marking complete', 'error');
      } finally {
        this.processing = false;
      }
    },

    showToastMessage(message, type = 'success') {
      this.toastMessage = message;
      this.toastType = type;
      this.showToast = true;
      setTimeout(() => {
        this.showToast = false;
      }, 4000);
    },
  },
};
</script>

<template>
  <AuthenticatedLayout>
    <HrToggleBar/>
    <div class="min-h-screen bg-gray-50">
      <!-- Header -->
      <div class="bg-white shadow-sm border-b">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center py-6">
            <div class="flex items-center space-x-2 mb-6">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="text-xl font-semibold text-gray-900">Session Requests ({{ sessions.length }})</h2>
          </div>

            <!-- Stats Cards -->
            <div class="flex space-x-4">
              <div class="bg-orange-50 px-4 py-2 rounded-lg border border-orange-200">
                <div class="text-2xl font-bold text-orange-600">{{ pendingCount }}</div>
                <div class="text-sm text-orange-700">Pending</div>
              </div>
              <div class="bg-green-50 px-4 py-2 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">{{ approvedCount }}</div>
                <div class="text-sm text-green-700">Approved</div>
              </div>
              <div class="bg-teal-50 px-4 py-2 rounded-lg border border-teal-200">
                <div class="text-2xl font-bold text-teal-600">{{ completedCount }}</div>
                <div class="text-sm text-teal-700">Completed</div>
              </div>
              <div class="bg-red-50 px-4 py-2 rounded-lg border border-red-200">
                <div class="text-2xl font-bold text-red-600">{{ rejectedCount }}</div>
                <div class="text-sm text-red-700">Rejected</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sessions List -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">


          <div class="space-y-4">
            <div
              v-for="session in sessions"
              :key="session.id"
              class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200"
            >
              <div class="p-6">
                <div class="flex items-start justify-between">
                  <!-- Left side: Info -->
                  <div class="flex-1">
                    <div class="flex items-start space-x-4">
                      <div
                        :class="{
                          'bg-orange-400': session.status === 'pending',
                          'bg-green-400': session.status === 'approved',
                          'bg-red-400': session.status === 'rejected',
                          'bg-teal-400': session.status === 'completed',
                        }"
                        class="w-1 h-16 rounded-full flex-shrink-0"
                      ></div>

                      <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                          <h3 class="text-lg font-medium text-gray-900">{{ session.title || session.topic }}</h3>
                          <span
                            v-if="session.status === 'pending'"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200"
                          >
                            Pending
                          </span>
                          <span
                            v-else-if="session.status === 'approved'"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200"
                          >
                            Approved
                          </span>
                          <span
                            v-else-if="session.status === 'completed'"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 border border-teal-200"
                          >
                            Completed
                          </span>
                          <span
                            v-else-if="session.status === 'rejected'"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200"
                          >
                            Rejected
                          </span>
                        </div>

                        <p class="text-sm text-gray-600 mb-2">by {{ session.requester?.name || 'Unknown' }}</p>

                        <p class="text-sm text-gray-700 mb-4">{{ session.description || 'No description available' }}</p>

                        <div class="flex items-center space-x-6 text-sm text-gray-500">
                          <div class="flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Date Of Session: {{ formatDate(session.date) }}</span>
                          </div>
                          <div class="flex items-center space-x-1">
                            <span>Submitted: {{ formatDate(session.created_at) }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Right side: Actions -->
                  <div class="flex items-center space-x-2 ml-4">
                    <template v-if="session.status === 'pending'">
                      <button
                        @click="approveSession(session.id)"
                        :disabled="processing"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg transition-colors duration-200 space-x-2"
                        title="Approve Session"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Approve</span>
                      </button>

                      <button
                        @click="rejectSession(session.id)"
                        :disabled="processing"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white text-sm font-medium rounded-lg transition-colors duration-200 space-x-2"
                        title="Reject Session"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Reject</span>
                      </button>

                      <button
                        @click="toggleDateEditor(session.id)"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                        title="Edit Date"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                      </button>
                    </template>

                    <template v-else-if="session.status === 'approved'">
                      <button
                        @click="markAsComplete(session.id)"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
                      >
                        Mark as Complete
                      </button>
                    </template>

                    <template v-else-if="session.status === 'completed'">
                      <span class="px-3 py-1 text-sm font-semibold text-green-800 bg-green-100 rounded-full">
                        Completed
                      </span>
                    </template>

                    <template v-else-if="session.status === 'rejected'">
                      <span class="px-3 py-1 text-sm font-semibold text-red-800 bg-red-100 rounded-full">
                        Rejected
                      </span>
                    </template>
                  </div>
                </div>

                <!-- Date Editor -->
                <div v-if="editingDate === session.id" class="mt-6 pt-6 border-t border-gray-200">
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Update Session Date</h4>
                    <form @submit.prevent="updateSessionDate(session.id)" class="flex items-end space-x-4">
                      <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1">New Date</label>
                        <input
                          v-model="newDate"
                          type="date"
                          required
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                          :min="today"
                        />
                      </div>
                      <div class="flex space-x-2">
                        <button
                          type="submit"
                          :disabled="processing || !newDate"
                          class="px-4 py-2 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                        >
                          Update
                        </button>
                        <button
                          type="button"
                          @click="cancelDateEdit"
                          class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200"
                        >
                          Cancel
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="sessions.length === 0" class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                </path>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No session requests</h3>
              <p class="mt-1 text-sm text-gray-500">No sessions found.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading Overlay -->
      <div v-if="processing" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
          <svg class="animate-spin h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          <span class="text-gray-900">Processing...</span>
        </div>
      </div>

      <!-- Toast Notifications -->
      <div v-if="showToast" class="fixed bottom-4 right-4 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 z-50">
        <div class="p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg v-if="toastType === 'success'" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <svg v-else class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-3 w-0 flex-1">
              <p class="text-sm font-medium text-gray-900">{{ toastMessage }}</p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
              <button @click="showToast = false"
                class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
<style scoped>
/* Custom animations for smooth transitions */
.transition-shadow {
  transition-property: box-shadow;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom focus styles */
input:focus {
  outline: none;
}

/* Loading animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
