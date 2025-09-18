<template>
  <div v-if="isOpen" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-auto p-6">
      <!-- Modal Header -->
      <div class="flex justify-between items-center pb-3 mb-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800">Request Knowledge Session</h3>
        <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
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
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Modal Body - Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label for="sessionTopic" class="block text-sm font-medium text-gray-700 mb-1"
            >Session Topic <span class="text-red-500">*</span></label
          >
          <input
            type="text"
            id="sessionTopic"
            v-model="form.topic"
            placeholder="e.g., Advanced React Patterns"
            required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>

        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1"
            >Description <span class="text-red-500">*</span></label
          >
          <textarea
            id="description"
            v-model="form.description"
            placeholder="Describe what you'll cover in this session..."
            rows="4"
            required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm resize-none"
          ></textarea>
        </div>

        <div>
          <label for="preferredDate" class="block text-sm font-medium text-gray-700 mb-1"
            >Preferred Date <span class="text-red-500">*</span></label
          >
          <div class="relative">
            <input
              type="date"
              id="preferredDate"
              v-model="form.date"
              required
              class="mt-1 block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- Modal Footer - Buttons -->
        <div class="pt-4 flex justify-end space-x-3">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-teal-500 to-blue-600 border border-transparent rounded-md shadow-sm hover:from-teal-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Submit Request
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';

export default {
  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['close'],
  setup(props, { emit }) {
    const form = useForm({
      topic: '',
      date: '',
      description: '',
    });

    function submit() {
      form.post(route('sessions.store'), {
        onSuccess: () => {
          form.reset();
          emit('close'); // Close modal on successful submission
        },
        onError: (errors) => {
          console.error("Form submission errors:", errors);
          // You might want to display these errors in the form
        }
      });
    }

    function closeModal() {
      emit('close');
      form.reset(); // Reset form when closing
    }

    return { form, submit, closeModal };
  },
};
</script>
