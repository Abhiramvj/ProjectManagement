<script setup>
// NO FullCalendar CSS imports are needed. The JS plugins handle style injection.

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { formatDistanceToNowStrict, format } from 'date-fns';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import axios from 'axios';

ChartJS.register(ArcElement, Tooltip, Legend);

// Props are unchanged
const props = defineProps({
    user: { type: Object, required: true },
    attendance: { type: Object, required: true },
    calendarEvents: { type: Array, default: () => [] },
    greeting: { type: Object, required: true },
    projects: { type: Array, default: () => [] },
    myTasks: { type: Array, default: () => [] },
    taskStats: { type: Object, required: true },
    timeStats: { type: Object, required: true },
    leaveStats: { type: Object, required: true },
    announcements: { type: Array, default: () => [] },
});


// Logic for Performance Score and AI Summary is unchanged
const performanceScore = computed(() => {
    if (!props.taskStats || !props.timeStats || !props.leaveStats || !props.leaveStats.balance) {
        return NaN;
    }
    const taskScore = props.taskStats.completion_rate;
    const timeScore = Math.min(100, (props.timeStats.current_month / 160) * 100);
    const leaveScore = Math.max(0, 100 - (props.leaveStats.current_year / props.leaveStats.balance) * 100);
    return Math.round((taskScore + timeScore + leaveScore) / 3);
});

const isDataReadyForSummary = computed(() => {
    return props.taskStats && props.timeStats && props.leaveStats &&
           typeof performanceScore.value === 'number' &&
           !isNaN(performanceScore.value);
});

const showSummaryBox = ref(false);
const generatedSummary = ref('');
const isLoadingSummary = ref(false);
const summaryError = ref('');

const fetchAiSummary = async () => {
    showSummaryBox.value = true;
    isLoadingSummary.value = true;
    summaryError.value = '';
    generatedSummary.value = '';
    try {
        const response = await axios.post(route('my-performance.generateSummary'), {
            taskStats: props.taskStats,
            timeStats: props.timeStats,
            leaveStats: props.leaveStats,
            performanceScore: performanceScore.value
        });
        generatedSummary.value = response.data.summary;
    } catch (error) {
        console.error("Error generating AI summary:", error);
        summaryError.value = error.response?.data?.error || 'An unexpected error occurred.';
    } finally {
        isLoadingSummary.value = false;
    }
};

const closeSummaryBox = () => { showSummaryBox.value = false; };


// Role-based visibility & helpers are unchanged
const page = usePage();
const authUser = computed(() => page.props.auth.user);
const hasPermission = (permission) => {
    if (!authUser.value || !Array.isArray(authUser.value.permissions)) { return false; }
    return authUser.value.permissions.includes(permission);
};
const canManageAnnouncements = computed(() => hasPermission('manage announcements'));
const canViewAttendanceStats = computed(() => hasPermission('manage employees'));

// Announcement management logic is unchanged
const isAnnouncementModalOpen = ref(false);
const announcementModalMode = ref('create');
const editingAnnouncementId = ref(null);
const announcementForm = useForm({ title: '', content: '' });
const isViewAnnouncementModalOpen = ref(false);
const viewingAnnouncement = ref(null);
function openCreateAnnouncementModal() { announcementModalMode.value = 'create'; announcementForm.reset(); editingAnnouncementId.value = null; isAnnouncementModalOpen.value = true; }
function openEditAnnouncementModal(announcement) { announcementModalMode.value = 'edit'; editingAnnouncementId.value = announcement.id; announcementForm.title = announcement.title; announcementForm.content = announcement.content; isAnnouncementModalOpen.value = true; }
function closeAnnouncementModal() { isAnnouncementModalOpen.value = false; announcementForm.reset(); }
function saveAnnouncement() { const onFinish = () => { closeAnnouncementModal(); router.reload({ only: ['announcements'] }); }; if (announcementModalMode.value === 'create') { announcementForm.post(route('announcements.store'), { preserveScroll: true, onSuccess: onFinish, }); } else { announcementForm.put(route('announcements.update', editingAnnouncementId.value), { preserveScroll: true, onSuccess: onFinish, }); } }
function deleteAnnouncement() { if (confirm('Are you sure?')) { router.delete(route('announcements.destroy', editingAnnouncementId.value), { preserveScroll: true, onSuccess: () => { closeAnnouncementModal(); router.reload({ only: ['announcements'] }); }, }); } }
function openViewAnnouncementModal(announcement) { viewingAnnouncement.value = announcement; isViewAnnouncementModalOpen.value = true; }
function closeViewAnnouncementModal() { isViewAnnouncementModalOpen.value = false; viewingAnnouncement.value = null; }

// Other script setup logic is unchanged
const updateTaskStatus = (task, newStatus) => { router.patch(route('tasks.updateStatus', task.id), { status: newStatus }, { preserveScroll: true }); };
const getTaskStatusColor = (status) => { if (status === 'completed' || status === 'done') return 'bg-green-50 border-green-200'; if (status === 'in_progress') return 'bg-blue-50 border-blue-200'; return 'bg-gray-50 border-gray-200'; };
const getStatusBadgeColor = (status) => { if (status === 'completed' || status === 'done') return 'bg-green-100 text-green-800'; if (status === 'in_progress') return 'bg-blue-100 text-blue-800'; return 'bg-gray-100 text-gray-800'; };
const getStatusDisplayName = (status) => (status || 'todo').replace(/_/g, ' ');
const canStartTask = (status) => status === 'todo' || status === 'pending';
const canCompleteTask = (status) => status === 'in_progress';
const companyExperience = computed(() => { if (!props.user.hire_date) return 'N/A'; return formatDistanceToNowStrict(new Date(props.user.hire_date)); });
const isNoteModalVisible = ref(false);
const modalMode = ref('create');
const editingNoteId = ref(null);
const noteForm = useForm({ note: '', date: '' });
const now = ref(new Date());
let timeUpdater = null;
const liveTime = computed(() => now.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));
onMounted(() => { timeUpdater = setInterval(() => { now.value = new Date(); }, 1000); });
onUnmounted(() => { clearInterval(timeUpdater); });
const calendar = ref(null);
watch(() => props.calendarEvents, (newEvents) => { if (calendar.value) { const calendarApi = calendar.value.getApi(); calendarApi.removeAllEvents(); calendarApi.addEventSource(newEvents); } }, { deep: true });
const currentCalendarView = ref('dayGridMonth');
function handleDateClick(arg) { modalMode.value = 'create'; editingNoteId.value = null; noteForm.date = arg.dateStr; isNoteModalVisible.value = true; }
function handleEventClick(arg) { if (arg.event.extendedProps.type === 'note') { modalMode.value = 'edit'; editingNoteId.value = arg.event.extendedProps.note_id; noteForm.note = arg.event.title; noteForm.date = arg.event.startStr; isNoteModalVisible.value = true; } }
const calendarOptions = ref({ plugins: [dayGridPlugin, interactionPlugin], initialView: 'dayGridMonth', headerToolbar: false, events: props.calendarEvents, height: 'auto', selectable: true, dateClick: handleDateClick, eventClick: handleEventClick, dayHeaderClassNames: 'text-xs font-semibold text-gray-500 uppercase tracking-wider', dayCellClassNames: 'border-gray-200', eventDisplay: 'block', eventClassNames: 'p-1 rounded-md font-medium cursor-pointer border-none text-xs', });
function changeCalendarView(view) { if(calendar.value) { calendar.value.getApi().changeView(view); currentCalendarView.value = view; } }
function saveNote() { const action = modalMode.value === 'create' ? route('calendar-notes.store') : route('calendar-notes.update', editingNoteId.value); const method = modalMode.value === 'create' ? 'post' : 'put'; noteForm[method](action, { preserveScroll: true, onSuccess: () => { closeModal(); router.reload({ only: ['calendarEvents'] }); }, }); }
function deleteNote() { if (confirm('Are you sure?')) { router.delete(route('calendar-notes.destroy', editingNoteId.value), { preserveScroll: true, onSuccess: () => { closeModal(); router.reload({ only: ['calendarEvents'] }); }, }); } }
function closeModal() { isNoteModalVisible.value = false; noteForm.reset(); editingNoteId.value = null; }
const chartData = computed(() => ({ labels: ['Present', 'Absent'], datasets: [{ backgroundColor: ['#2563EB', '#D1D5DB'], data: [props.attendance.present, props.attendance.absent], borderWidth: 0, }], }));
const chartOptions = { responsive: true, maintainAspectRatio: false, cutout: '80%', plugins: { legend: { display: false }, tooltip: { enabled: true } }, };

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <!-- [-] REMOVED AI Performance Summary Modal from here -->

        <!-- Announcement and Calendar Modals (Unchanged) -->
        <Modal :show="isViewAnnouncementModalOpen" @close="closeViewAnnouncementModal">
            <div v-if="viewingAnnouncement" class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-900">{{ viewingAnnouncement.title }}</h3>
                        <div class="flex items-center space-x-2 mt-2 text-sm text-slate-500">
                            <img class="h-6 w-6 rounded-full" :src="viewingAnnouncement.author.avatar_url || `https://ui-avatars.com/api/?name=${viewingAnnouncement.author.name.replace(' ', '+')}&background=random`" alt="">
                            <span>{{ viewingAnnouncement.author.name }}</span>
                            <span>•</span>
                            <span>{{ viewingAnnouncement.created_at_formatted }}</span>
                        </div>
                    </div>
                    <button @click="closeViewAnnouncementModal" class="p-1 rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition">×</button>
                </div>
                <div class="mt-4 prose prose-sm max-w-none text-slate-600" v-html="viewingAnnouncement.content"></div>
                <div class="mt-6 text-right">
                    <button type="button" @click="closeViewAnnouncementModal" class="px-4 py-2 text-sm font-semibold bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Close</button>
                </div>
            </div>
        </Modal>
        <Modal :show="isAnnouncementModalOpen" @close="closeAnnouncementModal">
             <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900">{{ announcementModalMode === 'create' ? 'New Announcement' : 'Edit Announcement' }}</h3>
                <form @submit.prevent="saveAnnouncement" class="mt-4">
                    <div class="space-y-4">
                        <div>
                            <label for="announcement-title" class="block text-sm font-medium text-slate-700">Title</label>
                            <input v-model="announcementForm.title" id="announcement-title" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g., Upcoming Holiday">
                            <InputError class="mt-1 text-xs" :message="announcementForm.errors.title" />
                        </div>
                         <div>
                            <label for="announcement-content" class="block text-sm font-medium text-slate-700">Content</label>
                            <textarea v-model="announcementForm.content" id="announcement-content" rows="6" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Enter the details of the announcement..."></textarea>
                            <InputError class="mt-1 text-xs" :message="announcementForm.errors.content" />
                        </div>
                        <div class="flex justify-between items-center pt-2">
                             <div>
                                <button v-if="announcementModalMode === 'edit'" type="button" @click="deleteAnnouncement" class="text-sm font-semibold text-red-600 hover:text-red-800">Delete</button>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="closeAnnouncementModal" class="px-4 py-2 text-sm font-semibold bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                                <button type="submit" :disabled="announcementForm.processing" class="px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 disabled:opacity-50">{{ announcementForm.processing ? 'Saving...' : 'Save Announcement' }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
        <Modal :show="isNoteModalVisible" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900">{{ modalMode === 'create' ? `Add a Note for ${noteForm.date}` : 'Edit Note' }}</h3>
                    <button @click="closeModal" class="p-1 rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition">×</button>
                </div>
                <form @submit.prevent="saveNote">
                    <div class="space-y-4">
                        <div>
                            <label for="note" class="block text-sm font-medium text-slate-700">Note</label>
                            <textarea v-model="noteForm.note" id="note" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Team meeting at 10 AM..."></textarea>
                            <InputError class="mt-1 text-xs" :message="noteForm.errors.note" />
                        </div>
                        <div class="flex justify-between items-center">
                            <div><button v-if="modalMode === 'edit'" type="button" @click="deleteNote" class="text-sm font-semibold text-red-600 hover:text-red-800">Delete Note</button></div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-semibold bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                                <button type="submit" :disabled="noteForm.processing" class="px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 disabled:opacity-50">{{ noteForm.processing ? 'Saving...' : 'Save' }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

<div class="flex-1 bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Dashboard Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <div class="flex items-center space-x-3">
                <!-- MODIFIED: Calls the new trigger function -->
                <button
                    @click="fetchAiSummary"
                    :disabled="isLoadingSummary || !isDataReadyForSummary"
                    class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-all shadow-sm"
                    :title="!isDataReadyForSummary ? 'Performance data not yet available.' : 'Get AI insights on your performance'"
                >
                    <!-- MODIFIED: Better loading text -->
                    <svg v-if="isLoadingSummary" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>{{ isLoadingSummary ? 'Generating...' : 'Get Performance Insights' }}</span>
                </button>
                <Link :href="route('leave.index')" class="px-4 py-2 text-sm font-semibold bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors shadow-sm">Create Leave Request</Link>
            </div>
        </div>

        <!-- NEW & IMPROVED: AI Performance Insights Box with Loading State -->
        <div v-if="showSummaryBox" class="relative bg-white p-6 rounded-lg shadow-sm border border-gray-200 transition-all">
            <button @click="closeSummaryBox" class="absolute top-2 right-2 p-1 text-gray-400 rounded-full hover:bg-gray-200 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>

            <h3 class="text-lg font-bold text-gray-900 flex items-center mb-4">
                <svg class="w-6 h-6 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 01-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 013.09-3.09L12 5.25l.813 2.846a4.5 4.5 0 013.09 3.09L18.75 12l-2.846.813a4.5 4.5 0 01-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.898 20.562L16.25 22.5l-.648-1.938a3.375 3.375 0 00-2.456-2.456L11.25 18l1.938-.648a3.375 3.375 0 002.456-2.456L16.25 13.5l.648 1.938a3.375 3.375 0 002.456 2.456L21 18l-1.938.648a3.375 3.375 0 00-2.456 2.456z" /></svg>
                AI Performance Insights
            </h3>

            <!-- Skeleton Loader -->
            <div v-if="isLoadingSummary" class="animate-pulse space-y-3">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-full"></div>
                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                <p class="text-sm text-gray-500 pt-2">Analyzing your data... this might take a moment.</p>
            </div>

            <!-- Error Message -->
            <div v-else-if="summaryError" class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <p><strong class="font-semibold">Generation Failed:</strong> {{ summaryError }}</p>
            </div>

            <!-- Generated Content -->
            <div v-else-if="generatedSummary" class="prose prose-sm max-w-none text-gray-700" v-html="generatedSummary"></div>

        </div> <!--- THIS IS THE MISSING DIV THAT HAS BEEN ADDED --->

        <!-- Grid for Top Row Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- User Info Card -->
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-600 text-2xl">{{ user.name.charAt(0) }}</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">{{ user.name }} <span class="text-sm font-medium text-gray-400 ml-2">{{ user.employee_id }}</span></h3>
                            <p class="text-sm text-gray-500">{{ user.email }}</p>
                        </div>
                    </div>
                    <Link :href="route('profile.edit')" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center">Edit</Link>
                </div>
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-6 border-t border-gray-100 pt-6">
                    <div><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Designation</p><p class="text-sm font-semibold text-gray-900">{{ user.designation }}</p></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reporting to</p><p class="text-sm font-semibold text-gray-900">{{ user.parent ? user.parent.name : 'N/A' }}</p></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Experience</p><p class="text-sm font-semibold text-gray-900">{{ user.total_experience }} Years</p></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Company Experience</p><p class="text-sm font-semibold text-gray-900">{{ companyExperience }}</p></div>
                </div>
            </div>

            <!-- Greeting Card -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col justify-between">
                <h3 class="font-semibold text-gray-800">Good {{ greeting.message }}</h3>
                <div class="flex items-center my-auto"><span class="text-4xl mr-4">{{ greeting.icon }}</span><span class="text-3xl font-bold text-gray-900">{{ liveTime }}</span></div>
                <div class="text-sm text-gray-500 text-right border-t pt-2 border-gray-100">Today, {{ greeting.date }}</div>
            </div>
        </div>

        <!-- Announcement Panel -->
        <div v-if="announcements.length > 0 || canManageAnnouncements" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Announcements</h3>
                <button v-if="canManageAnnouncements" @click="openCreateAnnouncementModal" class="px-3 py-1.5 text-sm font-semibold bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">New Announcement</button>
            </div>
            <div v-if="announcements.length > 0" class="space-y-4">
                <div v-for="announcement in announcements" :key="announcement.id" class="p-4 bg-slate-50 rounded-lg border border-slate-200 flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="font-semibold text-slate-800">{{ announcement.title }}</p>
                        <div class="text-xs text-slate-500 mt-1 flex items-center space-x-2">
                            <span>By {{ announcement.author.name }}</span>
                            <span>•</span>
                            <span>{{ announcement.created_at_formatted }}</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <button @click="openViewAnnouncementModal(announcement)" class="text-sm font-medium text-blue-600 hover:text-blue-800">Read More</button>
                        <template v-if="canManageAnnouncements">
                            <span class="text-slate-300">|</span>
                            <button @click="openEditAnnouncementModal(announcement)" class="text-sm font-medium text-slate-600 hover:text-slate-800">Edit</button>
                        </template>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8">
                <p class="text-slate-500">No announcements at the moment.</p>
            </div>
        </div>

        <!-- The rest of your template -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-if="projects && projects.length > 0" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold text-gray-900">Active Projects</h3>
                <ul class="mt-4 space-y-3">
                    <li v-for="project in projects" :key="project.id" class="p-4 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-center">
                        <div><p class="font-semibold text-gray-800">{{ project.name }}</p><span class="text-sm text-gray-600 block capitalize">Status: {{ project.status }}</span></div>
                        <div><Link :href="route('projects.show', project.id)" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm"><span v-if="hasPermission('assign tasks')">View / Assign Tasks</span><span v-else>View Progress</span></Link></div>
                    </li>
                </ul>
            </div>
            <div v-if="myTasks && myTasks.length > 0" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center mb-4"><h3 class="text-lg font-semibold text-gray-900 flex items-center">My Assigned Tasks</h3><span class="text-sm text-gray-500">{{ myTasks.length }} total tasks</span></div>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div v-for="task in myTasks" :key="task.id" class="p-3 rounded-lg border transition-all duration-200 hover:shadow-sm" :class="getTaskStatusColor(task.status)">
                        <div class="flex justify-between items-center">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2"><h4 class="text-sm font-medium text-gray-800">{{ task.name }}</h4><span class="px-1.5 py-0.5 text-xs font-medium rounded capitalize" :class="getStatusBadgeColor(task.status)">{{ getStatusDisplayName(task.status) }}</span></div>
                                <p class="text-xs text-gray-500 mt-1">{{ task.project?.name || 'No Project' }}</p>
                            </div>
                            <div class="flex gap-1 ml-3">
                                <button v-if="canStartTask(task.status)" @click="updateTaskStatus(task, 'in_progress')" class="px-2 py-1 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600 transition-colors">Start</button>
                                <button v-if="canCompleteTask(task.status)" @click="updateTaskStatus(task, 'completed')" class="px-2 py-1 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600 transition-colors">Done</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200" :class="canViewAttendanceStats ? 'lg:col-span-2' : 'lg:col-span-3'">
                <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-slate-900">My Calendar</h3><div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-lg"><button @click="changeCalendarView('dayGridMonth')" :class="[currentCalendarView === 'dayGridMonth' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Month</button><button @click="changeCalendarView('dayGridWeek')" :class="[currentCalendarView === 'dayGridWeek' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Week</button><button @click="changeCalendarView('dayGridDay')" :class="[currentCalendarView === 'dayGridDay' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Day</button></div></div>
                <FullCalendar :options="calendarOptions" ref="calendar" />
            </div>
            <div v-if="canViewAttendanceStats" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Team Attendance</h3>
                <div class="relative h-48 mb-4"><Doughnut :data="chartData" :options="chartOptions" /><div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"><span class="text-4xl font-bold text-slate-900">{{ attendance.total }}</span></div></div>
                <div class="flex items-center justify-center space-x-6 text-sm mb-6"><div class="flex items-center"><span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>Present</div><div class="flex items-center"><span class="w-3 h-3 rounded-full bg-slate-800 mr-2"></span>Absent</div></div>
                <div class="space-y-4 border-t border-slate-100 pt-4"><h4 class="font-semibold text-slate-800">Absent Today</h4><div v-if="attendance.absent_list.length > 0" class="space-y-3"><div v-for="absentee in attendance.absent_list" :key="absentee.id" class="flex items-center justify-between"><div class="flex items-center space-x-3"><img class="h-9 w-9 rounded-full" :src="absentee.avatar_url || `https://ui-avatars.com/api/?name=${absentee.name.replace(' ', '+')}&background=random`" :alt="absentee.name" /><div><p class="text-sm font-medium text-slate-800">{{ absentee.name }}</p><p class="text-xs text-slate-500">{{ absentee.designation }}</p></div></div><span class="text-xs font-medium text-red-600 bg-red-100 px-2 py-1 rounded-full">Fullday</span></div></div><div v-else class="text-center text-sm text-slate-500 py-4">Everyone is present today! 🎉</div></div>
            </div>
        </div>
    </div>
</div>
    </AuthenticatedLayout>
</template>
<style>
/* Global styles for FullCalendar - now using gray instead of slate for consistency */
.fc .fc-daygrid-day-number {
  color: #374151 !important; /* text-gray-700 */
  font-weight: 600 !important;
  padding: 0.25rem !important;
  user-select: none;
}
.fc-theme-standard .fc-scrollgrid {
  border-color: #e5e7eb !important; /* border-gray-200 */
}
.fc .fc-event-main {
    white-space: normal !important;
}
</style>
76.6s

