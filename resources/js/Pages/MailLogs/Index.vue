<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { defineProps } from 'vue';

// Props passed from backend controller
const props = defineProps({
  mailLogs: Object,
});

const templates = ref([]);

// Manage selections for each event type
const templateSelections = ref({
  leave_submitted: '',
  leave_approved: '',
  leave_rejected: '',
});

// Store loaded template content preview per event type
const templateContents = ref({
  leave_submitted: '',
  leave_approved: '',
  leave_rejected: '',
});

// Fetch all templates from backend API
const fetchTemplates = async () => {
  try {
    const response = await fetch('/mail-templates');
    templates.value = await response.json();
  } catch (error) {
    console.error('Failed to fetch mail templates:', error);
  }
};

// Computed function to filter templates by event_type
const templatesByType = (type) => {
  return templates.value.filter(template => template.event_type === type);
};

// Load full template content when selection changes for an event type
const loadTemplateContent = async (type) => {
  const id = templateSelections.value[type];
  if (!id) {
    templateContents.value[type] = '';
    return;
  }
  try {
    const response = await fetch(`/mail-templates/${id}`);
    const template = await response.json();
    templateContents.value[type] = template.body || '';
  } catch (error) {
    console.error('Failed to load template content:', error);
    templateContents.value[type] = '';
  }
};

// Persist the template mapping update backend-side when changing dropdown
const updateTemplateMapping = async (eventType, mailTemplateId) => {
  try {
    const response = await fetch('/template-mapping/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        event_type: eventType,
        mail_template_id: mailTemplateId,
      }),
    });

    if (!response.ok) {
      throw new Error('Failed to update template mapping');
    }

    // Success notification can be added here if desired

  } catch (error) {
    console.error(error);
    // Error notification can be added here
  }
};

// Handle change event on dropdown, load content and update backend mapping
const onTemplateChange = async (type) => {
  await loadTemplateContent(type);
  const selectedId = templateSelections.value[type];
  if (selectedId) {
    await updateTemplateMapping(type, selectedId);
  }
};

// On component mount, load all templates and could load current selections if needed
onMounted(() => {
  fetchTemplates();
});

// Helper to format ISO date from MongoDB into readable string
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

<template>
  <Head title="Mail Logs" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Mail Activity Logs</h2>

    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

        <!-- Templates Selectors -->
        <div class="flex flex-wrap gap-6">
          <div class="mb-6 max-w-xs">
            <label class="block text-sm font-medium text-gray-700 mb-1">Submitted Mail Template</label>
            <select
              v-model="templateSelections.leave_submitted"
              @change="onTemplateChange('leave_submitted')"

              class="block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option disabled value="">Choose a template</option>
              <option
                v-for="template in templatesByType('leave_submitted')"
                :key="template.id"
                :value="template.id"
              >
                {{ template.name }}
              </option>
            </select>
          </div>

          <div class="mb-6 max-w-xs">
            <label class="block text-sm font-medium text-gray-700 mb-1">Approved Mail Template</label>
            <select
              v-model="templateSelections.leave_approved"
              @change="onTemplateChange('leave_approved')"
              class="block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option disabled value="">Choose a template</option>
              <option
                v-for="template in templatesByType('leave_approved')"
                :key="template.id"
                :value="template.id"
              >
                {{ template.name }}
              </option>
            </select>
          </div>

          <div class="mb-12 max-w-xs">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rejected Mail Template</label>
            <select
              v-model="templateSelections.leave_rejected"
               @change="onTemplateChange('leave_rejected')"
              class="block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option disabled value="">Choose a template</option>
              <option
                v-for="template in templatesByType('leave_rejected')"
                :key="template.id"
                :value="template.id"
              >
                {{ template.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Mail Logs Table -->
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="border-b border-gray-200 bg-white p-6">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                      Recipient
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                      Subject
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                      Event
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                      Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                      Sent At
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-if="mailLogs.data.length === 0">
                    <td colspan="5" class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500">
                      No mail has been sent yet.
                    </td>
                  </tr>
                  <tr v-for="log in mailLogs.data" :key="log._id">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ log.recipient_email }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                      <Link :href="route('mail-logs.show', log.id)" class="text-blue-600 hover:underline">
                        {{ log.subject }}
                      </Link>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                      <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 hover:bg-blue-200">
                        {{
                          log.event_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                        }}
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4">
                      <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                        {{ log.status }}
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ formatDate(log.sent_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="mailLogs.links.length > 3" class="mt-6 flex items-center justify-between">
              <Pagination :links="mailLogs.links" />
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
