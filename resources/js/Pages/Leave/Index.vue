<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import axios from 'axios';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
const showColors = ref(true);
const showLeaveText = ref(true);
const selectedMailTemplateId = ref('');

const props = defineProps({
    leaveRequests: Object,
    highlightedDates: Array,
    remainingLeaveBalance: Number,
    compOffBalance: Number,
    employees: {
        type: Array,
        default: () => [],
    },
    leaveStats: Object,
});

const selectedRequest = ref(null)
const isRequestDetailModalVisible = ref(false)
const page = usePage();
const currentUserId = page.props.auth.user.id;


function formatDateRange(start, end, startHalf = null, endHalf = null) {
    const options = { day: '2-digit', month: 'short', year: 'numeric' };

    const startDate = new Date(start).toLocaleDateString(undefined, options);
    const endDate = end ? new Date(end).toLocaleDateString(undefined, options) : null;

    // Helper to append session
    const formatWithSession = (date, session) => {
        if (!session) return date;
        return `${date} (${session === 'morning' ? 'Morning' : 'Afternoon'})`;
    };

    // Single date
    if (!end || start === end) {
        return formatWithSession(startDate, startHalf);
    }

    // Range
    return `${formatWithSession(startDate, startHalf)} - ${formatWithSession(endDate, endHalf)}`;
}


function openRequestDetail(request) {
    selectedRequest.value = request
    isRequestDetailModalVisible.value = true
}

function closeRequestDetailModal() {
    isRequestDetailModalVisible.value = false
    selectedRequest.value = null
}

const leaveColors = {
    pending: '#fbbf24',
    annual: '#3b82f6',
    sick: '#22c55e',
    personal: '#f59e0b',
    emergency: '#f97316',
    maternity: '#ec4899',
    paternity: '#6366f1',
    rejected: '#b91c1c',
    wfh: '#0ea5e9',
    compensatory: '#16a34a',
    holiday: '#f43f5e',
};

const leaveFormSection = ref(null);
const isPolicyModalVisible = ref(false);
const selectedDates = ref([null, null]);
const today = new Date();
today.setHours(0, 0, 0, 0);

const form = useForm({
    start_date: '',
    end_date: '',
    reason: '',
    leave_type: 'sick',
    day_type: 'full',
    start_half_session: '',
    end_half_session: '',
    supporting_document: null,
    user_id: null,
});

const employeeOptions = ref([]);
const isLoadingEmployees = ref(false);
// This function is called by vue-select as the user types
const searchEmployees = (search) => {
    if (search.length > 1) {
        isLoadingEmployees.value = true; 
        debounce(async () => {
            try {
                const response = await axios.get(
                    route('users.search', { query: search }),
                );
                employeeOptions.value = response.data;
            } catch (error) {
                console.error('Error searching employees:', error);
            } finally {
                isLoadingEmployees.value = false; 
            }
        }, 350);
    }
};

// Simple debounce helper function
let debounceTimer;
const debounce = (callback, delay) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(callback, delay);
};

// This is the critical watcher that reloads data when an employee is selected
watch(
    () => form.user_id,
    (newUserId) => {
        clearTimeout(debounceTimer);
        isLoadingEmployees.value = false;

        router.visit(route('leave.index'), {
            method: 'get',
            data: { user_id: newUserId },
            preserveState: true,
            preserveScroll: true,
            only: [
                'highlightedDates',
                'employees',
                'leaveStats',
                'remainingLeaveBalance',
                'compOffBalance',
            ],
            onSuccess: () => {
                calendarOptions.value.events = [
                    ...calendarEvents.value,
                    ...getSelectionBackground(),
                ];
            },
        });
    },
);

const supportingDocument = ref(null);
function onSupportingDocumentChange(event) {
    supportingDocument.value = event.target.files[0] || null;
    form.supporting_document = supportingDocument.value;
}



const openPolicyModal = () => {
    isPolicyModalVisible.value = true;
};
const closePolicyModal = () => {
    isPolicyModalVisible.value = false;
};

function toISODateOnly(date) {
    if (!date) return '';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}


const calendarEvents = computed(() => {
    const selectedUserId =
        isAdminOrHR && form.user_id ? Number(form.user_id) : currentUserId;

    return (props.highlightedDates || [])
        .filter((ev) => ev.user_id === selectedUserId || ev.user_id === null)
        .map((ev) => ({
            display: 'background',
            start: ev.start,
            end: ev.end
                ? toISODateOnly(
                      new Date(
                          new Date(ev.end + 'T00:00:00').getTime() + 86400000,
                      ),
                  )
                : ev.start,
            backgroundColor: showColors.value
                ? leaveColors[ev.color_category] || '#9ca3af'
                : 'transparent',
            borderColor: 'transparent',
            title: showLeaveText.value ? ev.title : '', 
        }));
});

function getSelectionBackground() {
    const [start, end] = selectedDates.value;
    if (start && end) {
        return [
            {
                display: 'background',
                start: toISODateOnly(start),
                end: toISODateOnly(new Date(end.getTime() + 86400000)),
                color: '#2563eb',
            },
        ];
    }
    if (start) {
        return [
            {
                display: 'background',
                start: toISODateOnly(start),
                end: toISODateOnly(new Date(start.getTime() + 86400000)),
                color: '#ea580c',
            },
        ];
    }
    return [];
}

function updateFormDates() {
    const [start, end] = selectedDates.value;
    form.start_date = start ? toISODateOnly(start) : '';
    form.end_date = end ? toISODateOnly(end) : '';
}

const handleDateClick = (info) => {
    const clicked = new Date(info.date);
    clicked.setHours(0, 0, 0, 0);

    if (!isAdminOrHR && clicked < today) {
        alert('You can only select today or a future date.');
        return;
    }

    const [start, end] = selectedDates.value;
    if (!start) {
        selectedDates.value = [clicked, null];
    } else if (!end) {
        if (clicked >= start) {
            selectedDates.value = [start, clicked];
        } else {
            selectedDates.value = [clicked, null];
        }
    } else {
        selectedDates.value = [clicked, null];
    }
    updateFormDates();
};

const userRoles = page.props.auth.user.roles || [];
const isAdminOrHR = userRoles.includes('admin') || userRoles.includes('hr');

const submitApplication = () => {
    if (form.day_type === 'half') {
        if (!form.start_half_session) {
            alert('Please select morning or afternoon session for start date.');
            return;
        }
        if (
            form.end_date &&
            form.start_date !== form.end_date &&
            !form.end_half_session
        ) {
            alert('Please select morning or afternoon session for end date.');
            return;
        }
    }

    if (!form.start_date) {
        alert('Please select at least a start date.');
        return;
    }
    if (!form.end_date) {
        form.end_date = form.start_date;
        selectedDates.value = [
            new Date(form.start_date),
            new Date(form.start_date),
        ];
        if (form.day_type === 'half') {
            form.end_half_session = form.start_half_session;
        }
    }
    if (form.day_type === 'full') {
        form.start_half_session = null;
        form.end_half_session = null;
    }

    const startDate = new Date(form.start_date);
    const timeDiff = startDate.getTime() - today.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

    if (!isAdminOrHR) {
        if (form.leave_type === 'annual' && daysDiff < 7) {
            if (
                !confirm(
                    'Warning: Annual leaves should be requested at least 7 days in advance. Do you still want to submit?',
                )
            )
                return;
        }
        if (form.leave_type === 'personal' && daysDiff < 3) {
            if (
                !confirm(
                    'Warning: Personal leaves should be requested at least 3 days in advance. Do you still want to submit?',
                )
            )
                return;
        }
    }

    const formData = new FormData();
    for (const [key, val] of Object.entries(form.data())) {
        formData.append(key, val ?? '');
    }

    if (isAdminOrHR && form.user_id) {
        formData.append('user_id', form.user_id);
    }

    if (supportingDocument.value)
        formData.append('supporting_document', supportingDocument.value);

    router.post(route('leave.store'), formData, {
        preserveScroll: true,
        headers: { 'Content-Type': 'multipart/form-data' },
        onSuccess: () => {
            form.reset();
            form.leave_type = 'sick';
            form.day_type = 'full';
            form.start_half_session = '';
            form.end_half_session = '';
            selectedDates.value = [null, null];
            supportingDocument.value = null;
            selectedMailTemplateId.value = ''; // reset mail template selection
            if (isAdminOrHR) form.user_id = null; // Reset selected employee
        },
        onError: (errors) => {
            if (errors.message) alert(errors.message);
        },
    });
};


const statusConfig = {
    approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
    rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
    pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
};

const updateStatus = (request, newStatus) => {
    router.patch(
        route('leave.update', { leave_application: request.id }),
        {
            status: newStatus,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: [
                        'leaveRequests',
                        'remainingLeaveBalance',
                        'compOffBalance',
                    ],
                });
            },
        },
    );
};

const cancelLeave = (request) => {
    if (!confirm("Are you sure you want to cancel this leave request?")) return;

    router.delete(route('leave.cancel', request.id), {
        onSuccess: () => {
            isRequestDetailModalVisible.value = false;  // ✅ Close modal
            selectedRequest.value = null;               // ✅ Reset selection
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    selectable: true,
    selectMirror: true,
    height: 'auto',
    events: [...calendarEvents.value, ...getSelectionBackground()],
    dateClick: handleDateClick,
    headerToolbar: {
        left: 'title',
        center: '',
        right: 'prev,next',
    },
});

watch(
    [calendarEvents, selectedDates, showColors],
    () => {
        calendarOptions.value.events = [
            ...calendarEvents.value,
            ...getSelectionBackground(),
        ];
    },
    { deep: true },
);

const leaveTypeDescriptions = {
    annual: {
        title: 'Annual Leave',
        summary: 'Planned time off with advance notice',
        details: [
            'For vacations, personal time, or other planned absences',
            'Should be requested at least 7 days in advance',
            'Requires manager approval',
            'Balance accrued based on tenure (typically 15-25 days/year)',
        ],
    },
    personal: {
        title: 'Personal Leave',
        summary: 'Leave for personal matters that require time off',
        details: [
            'Used for personal errands, family commitments, or important events',
            'Typically requires 3 days advance notice',
            'Needs manager approval',
            'May be limited in total number of days per year',
        ],
    },
    sick: {
        title: 'Sick Leave',
        summary: 'Leave granted for health-related issues',
        details: [
            'For medical appointments, illness, or recovery',
            'Usually requires a medical certificate for extended absences',
            'Accrued based on company policy and local law',
            'May be paid or unpaid as per policy',
        ],
    },
    emergency: {
        title: 'Emergency Leave',
        summary: 'Leave for urgent, unforeseen personal emergencies',
        details: [
            'For unexpected situations like medical emergencies, accidents, or urgent family matters',
            'Typically granted on short notice with flexible approval process',
            'Usually paid leave, separate from annual or personal leave balances',
            'Helps employees manage critical situations without penalty',
        ],
    },
    maternity: {
        title: 'Maternity Leave',
        summary: 'Leave for childbirth and related recovery',
        details: [
            'Granted to employees during pregnancy and after birth',
            'Duration varies by jurisdiction (typically 12-26 weeks)',
            'May require medical documentation',
            'Protected under employment law',
        ],
    },
    paternity: {
        title: 'Paternity Leave',
        summary: 'Leave for fathers around the time of childbirth',
        details: [
            'Allows bonding with the newborn and support for the family',
            'Usually shorter duration than maternity leave (e.g., 1-2 weeks)',
            'Requires notice as per company policy',
            'Protected under employment law',
        ],
    },
    wfh: {
        title: 'Work From Home',
        summary: 'Remote work arrangements without reducing leave balance',
        details: [
            'Allows employees to work remotely for full or partial days',
            'Usually does not deduct from leave balance',
            'Requires manager’s approval',
            'Supports flexible work-life balance',
        ],
    },
    compensatory: {
        title: 'Compensatory Leave',
        summary: 'Leave earned by working extra hours or on holidays',
        details: [
            'Credited when working during official holidays',
            'Used as paid time off in lieu of extra hours worked',
            'Requires manager approval to grant and to use',
            'Usually tracked separately to enforce usage policies',
        ],
    },
};

const leaveTypeIcons = {
    annual: '🌴',
    personal: '📝',
    sick: '🤒',
    emergency: '🚨',
    maternity: '🤰',
    paternity: '👨‍🍼',
    wfh: '💻',
    compensatory: '⏰',
    default: '📌',
};





const isUploadModalVisible = ref(false);
const uploadFile = ref(null);
const uploadErrors = ref({});
const uploadProcessing = ref(false);
const currentUploadLeaveId = ref(null);

const openUploadModal = (leaveId) => {
    currentUploadLeaveId.value = leaveId;
    uploadFile.value = null;
    uploadErrors.value = {};
    uploadProcessing.value = false;
    isUploadModalVisible.value = true;
};

const closeUploadModal = () => {
    isUploadModalVisible.value = false;
    currentUploadLeaveId.value = null;
    uploadFile.value = null;
    uploadErrors.value = {};
    uploadProcessing.value = false;
};

function onUploadFileChange(event) {
    uploadFile.value = event.target.files[0];
}

const submitUpload = () => {
    if (!uploadFile.value) {
        uploadErrors.value.supporting_document = 'Please select a file.';
        return;
    }
    uploadProcessing.value = true;
    const formData = new FormData();
    formData.append('supporting_document', uploadFile.value);

    router.post(
        route('leave.uploadDocument', {
            leave_application: currentUploadLeaveId.value,
        }),
        formData,
        {
            preserveScroll: true,
            headers: { 'Content-Type': 'multipart/form-data' },
            onSuccess: () => {
                uploadProcessing.value = false;
                closeUploadModal();
                router.reload();
            },
            onError: (errors) => {
                uploadProcessing.value = false;
                uploadErrors.value = errors;
            },
        },
    );
};



const recentRequests = computed(() => {
    return (props.leaveRequests.data || []).slice(0, 5);
});

const isEditModalVisible = ref(false);
const editingRequest = ref(null);
const editingReason = ref('');
const editProcessing = ref(false);

function openEditModal(request) {
    editingRequest.value = request;
    editingReason.value = request.reason;
    isEditModalVisible.value = true;
}
function closeEditModal() {
    isEditModalVisible.value = false;
    editingRequest.value = null;
    editingReason.value = '';
    editProcessing.value = false;
}
function submitEditReason() {
    if (!editingRequest.value) return;
    editProcessing.value = true;
    router.patch(
        route('leave.updateReason', {
            leave_application: editingRequest.value.id,
        }),
        { reason: editingReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                editProcessing.value = false;
                closeEditModal();
                router.reload();
            },
            onError: () => {
                editProcessing.value = false;
            },
        },
    );
}


const selectedLeaveBalance = computed(() => {
    // This prop is now always correct for the user being viewed.
    return props.remainingLeaveBalance;
});

const selectedCompOffBalance = computed(() => {
    // This prop is also always correct.
    return props.compOffBalance;
});

const displayedLeaveStats = computed(() => {
    // This prop now always contains the correct stats object.
    return props.leaveStats;
});

function calculatePercentage(taken, total) {
    if (!total || total === 0) return 0; 
    const percentage = (taken / total) * 100;
    return Math.min(percentage, 100); 
}

const leaveTypeDetails = [
    { key: 'annual', label: 'Annual Leave', color: 'bg-blue-500' },
    { key: 'sick', label: 'Sick Leave', color: 'bg-emerald-500' },
    { key: 'personal', label: 'Personal Leave', color: 'bg-amber-500' },
];

function statusInfo(status) {
    switch (status) {
        case 'pending':
            return { text: 'Pending', badge: 'bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded-full' };
        case 'approved':
            return { text: 'Approved', badge: 'bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full' };
        case 'rejected':
            return { text: 'Rejected', badge: 'bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full' };
        default:
            return { text: 'Unknown', badge: 'bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded-full' };
    }
}



</script>

<template>
    <Head title="Apply for Leave" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 p-4 font-sans sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <!-- Page Header -->
                <header class="mb-8">
                    <h1 class="text-4xl font-bold text-slate-900">
                        Leave Application
                    </h1>
                    <p class="mt-2 text-slate-600">
                        Select dates on the calendar to plan and submit your
                        time off.
                    </p>
                </header>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
                    <!-- Left Column Wrapper -->
                    <div class="space-y-8 lg:col-span-3">
                        <!-- Card 1: Calendar -->
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="p-6">
                                <!-- Calendar Header -->
                                <div
                                    class="mb-4 flex flex-wrap items-center justify-between gap-y-3"
                                >
                                    <div class="flex items-center space-x-3">
                                        <label
                                            for="color-code-toggle"
                                            class="select-none text-sm font-medium text-slate-700"
                                        >
                                            Color Coding
                                        </label>
<button
    id="color-code-toggle"
    type="button"
    role="switch"
    :aria-checked="showColors.toString()"
    @click="showColors = !showColors; showLeaveText = showColors"
    :class="[
        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
        showColors ? 'bg-indigo-600' : 'bg-slate-300',
    ]"
>
    <span
        :class="[
            'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
            showColors ? 'translate-x-6' : 'translate-x-1',
        ]"
    ></span>
</button>

                                    </div>
                                    <div
                                        class="rounded-full px-3 py-1.5 text-xs font-semibold"
                                        :class="
                                            form.start_date
                                                ? 'bg-blue-100 text-blue-800'
                                                : 'bg-slate-100 text-slate-600'
                                        "
                                    >
                                        <span
                                            v-if="
                                                form.start_date &&
                                                !form.end_date
                                            "
                                            >Selected:
                                            {{ form.start_date }}</span
                                        >
                                        <span
                                            v-else-if="
                                                form.start_date && form.end_date
                                            "
                                            >Selected: {{ form.start_date }} to
                                            {{ form.end_date }}</span
                                        >
                                        <span v-else>No dates selected</span>
                                    </div>
                                </div>
                                <!-- Calendar Component -->
                                <div
                                    class="overflow-hidden rounded-lg border border-slate-200"
                                >
                                    <FullCalendar :options="calendarOptions" />
                                </div>
                                <InputError
                                    :message="form.errors.start_date"
                                    class="mt-2"
                                />
                                <InputError
                                    :message="form.errors.leave_balance"
                                    class="mt-2"
                                />
                            </div>
                        </div>

                        <!-- Secondary Action Buttons -->
                        <div class="container-fluid">
                            
                            <button
                                type="button"
                                @click="openPolicyModal"
                                class="btn-secondary w-full"
                            >
                                Leave Policy
                            </button>
                        </div>

                        <!-- Left Column: My Requests Compact Card -->
<div class="space-y-8 lg:col-span-1">
    <!-- My Requests Card -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-md p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-600 uppercase tracking-wide">
            My Leave Requests
            <span class="block text-xs font-normal text-slate-400">(Click on a request for details)</span>
        </h3>

        <!-- Empty State -->
        <div v-if="!recentRequests.length" class="flex flex-col items-center justify-center text-center py-10">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="mt-3 text-sm text-slate-500">You don't have any leave requests yet.</p>
        </div>

        <!-- Requests List -->
        <div v-else class="space-y-3 max-h-[12rem] overflow-y-auto custom-scrollbar pr-1">
    <div
        v-for="request in recentRequests.slice(0,5)"
        :key="request.id"
        @click="openRequestDetail(request)"
        class="cursor-pointer rounded-lg border border-slate-200 bg-slate-50 p-3 hover:bg-slate-100 transition flex justify-between items-center"
    >
        <div>
            <p class="text-sm font-medium text-slate-800">
                {{ formatDateRange(request.start_date, request.end_date, request.start_half_session, request.end_half_session) }}
            </p>

            <p class="text-xs text-slate-500 capitalize">
                {{ request.leave_type }}
            </p>
        </div>
        <span :class="statusInfo(request.status).badge">
            {{ statusInfo(request.status).text }}
        </span>
    </div>
</div>

    </div>
</div>
                    </div>

                    <!-- Right Column: Form & Info -->
                    <div class="space-y-8 lg:col-span-2">
                        <!-- Card 3: Leave Balance & Usage -->
                        <div
                            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                        >
                            <div class="mb-6 grid grid-cols-2 gap-6">
                                <div>
                                    <h3
                                        class="mb-1 text-sm font-medium text-slate-500"
                                    >
                                        Leave Balance
                                    </h3>
                                    <div
                                        class="text-3xl font-bold text-indigo-600"
                                    >
                                        {{ selectedLeaveBalance }}
                                    </div>
                                </div>
                                <div>
                                    <h3
                                        class="mb-1 text-sm font-medium text-slate-500"
                                    >
                                        Comp-off Balance
                                    </h3>
                                    <div
                                        class="text-3xl font-bold text-teal-600"
                                    >
                                        {{ selectedCompOffBalance }}
                                    </div>
                                </div>
                            </div>
                            <h3 class="mb-4 text-sm font-medium text-slate-500">
                                Leave Usage (This Year)
                            </h3>
                            <div class="space-y-4">
                                <div
                                    v-for="leave in leaveTypeDetails"
                                    :key="leave.key"
                                >
                                    <div
                                        class="mb-1 flex items-center justify-between text-sm"
                                    >
                                        <span
                                            class="font-medium text-slate-600"
                                            >{{ leave.label }}</span
                                        ><span
                                            class="font-semibold text-slate-800"
                                            >{{
                                                displayedLeaveStats[leave.key]
                                                    ?.taken || 0
                                            }}
                                            /
                                            {{
                                                displayedLeaveStats[leave.key]
                                                    ?.total || 0
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="h-2 w-full rounded-full bg-slate-200"
                                    >
                                        <div
                                            class="h-2 rounded-full"
                                            :class="leave.color"
                                            :style="{
                                                width:
                                                    calculatePercentage(
                                                        displayedLeaveStats[
                                                            leave.key
                                                        ]?.taken,
                                                        displayedLeaveStats[
                                                            leave.key
                                                        ]?.total,
                                                    ) + '%',
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Application Form -->
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            ref="leaveFormSection"
                        >
                            <form
                                @submit.prevent="submitApplication"
                                class="space-y-6 p-6"
                                enctype="multipart/form-data"
                            >
                                <header>
                                    <h2
                                        class="text-lg font-semibold text-slate-800"
                                    >
                                        New Leave Application
                                    </h2>
                                    <p
                                        class="text-sm text-slate-500"
                                        v-if="!form.start_date"
                                    >
                                        Select dates from the calendar to start.
                                    </p>
                                </header>

                                <div v-if="isAdminOrHR">
                                    <InputLabel
                                        for="employee_select"
                                        value="Apply for Employee"
                                        class="font-semibold text-black"
                                    />
                                    <v-select
                                        id="employee_select"
                                        v-model="form.user_id"
                                        :options="employeeOptions"
                                        :filterable="false"
                                        :loading="isLoadingEmployees"
                                        @search="searchEmployees"
                                        label="name"
                                        :reduce="(employee) => employee.id"
                                        placeholder="Start typing employee name..."
                                        class="vs-style mt-1"
                                    >
                                        <template #no-options
                                            >Type to search...</template
                                        >
                                        <template #option="option"
                                            ><div>{{ option.name }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ option.email }}
                                            </div></template
                                        >
                                        <template #selected-option="option"
                                            ><div>
                                                {{ option.name }}
                                            </div></template
                                        >
                                    </v-select>
                                    <InputError
                                        :message="form.errors.user_id"
                                        class="mt-1 text-black"
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        for="leave_type"
                                        value="Leave Type"
                                        class="font-semibold"
                                    />
                                    <select
                                        id="leave_type"
                                        v-model="form.leave_type"
                                        required
                                        class="form-select mt-1"
                                    >
                                        <option value="annual">
                                            Annual Leave
                                        </option>
                                        <option value="sick">Sick Leave</option>
                                        <option value="personal">
                                            Personal Leave
                                        </option>
                                        <option value="emergency">
                                            Emergency Leave
                                        </option>
                                        <option value="maternity">
                                            Maternity Leave
                                        </option>
                                        <option value="paternity">
                                            Paternity Leave
                                        </option>
                                        <option value="wfh">
                                            Work From Home
                                        </option>
                                        <option value="compensatory">
                                            Compensatory Off
                                        </option>
                                    </select>
                                    <InputError
                                        :message="form.errors.leave_type"
                                    />
                                    <div
                                        class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3"
                                    >
                                        <h4
                                            class="mb-1 text-sm font-semibold text-slate-800"
                                        >
                                            {{
                                                leaveTypeDescriptions[
                                                    form.leave_type
                                                ]?.title
                                            }}
                                        </h4>
                                        <p class="text-xs text-slate-600">
                                            {{
                                                leaveTypeDescriptions[
                                                    form.leave_type
                                                ]?.summary
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <InputLabel
                                        value="Day Type"
                                        class="mb-2 font-semibold"
                                    />
                                    <div class="grid grid-cols-2 gap-3">
                                        <label
                                            class="flex cursor-pointer items-center rounded-lg border p-3 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50"
                                            ><input
                                                type="radio"
                                                v-model="form.day_type"
                                                value="full"
                                                class="form-radio"
                                            /><span
                                                class="ml-2 text-sm font-medium text-black"
                                                >Full Day</span
                                            ></label
                                        >
                                        <label
                                            class="flex cursor-pointer items-center rounded-lg border p-3 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50"
                                            ><input
                                                type="radio"
                                                v-model="form.day_type"
                                                value="half"
                                                class="form-radio"
                                            /><span
                                                class="ml-2 text-sm font-medium text-black"
                                                >Half Day</span
                                            ></label
                                        >
                                    </div>
                                </div>

                                <template v-if="form.day_type === 'half'">
                                    <div
                                        class="space-y-4 rounded-lg border bg-slate-50 p-4"
                                    >
                                        <div>
                                            <InputLabel
                                                value="Session for Start Date"
                                                class="mb-2 text-sm font-medium"
                                            />
                                            <div class="grid grid-cols-2 gap-3">
                                                <label
                                                    class="flex cursor-pointer items-center rounded-lg border bg-white p-3 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50"
                                                    ><input
                                                        type="radio"
                                                        v-model="
                                                            form.start_half_session
                                                        "
                                                        value="morning"
                                                        class="form-radio"
                                                    /><span
                                                        class="ml-2 text-sm text-black"
                                                        >Morning (AM)</span
                                                    ></label
                                                >
                                                <label
                                                    class="flex cursor-pointer items-center rounded-lg border bg-white p-3 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50"
                                                    ><input
                                                        type="radio"
                                                        v-model="
                                                            form.start_half_session
                                                        "
                                                        value="afternoon"
                                                        class="form-radio"
                                                    /><span
                                                        class="ml-2 text-sm text-black"
                                                        >Afternoon (PM)</span
                                                    ></label
                                                >
                                            </div>
                                            <InputError
                                                :message="
                                                    form.errors
                                                        .start_half_session
                                                "
                                                class="mt-1"
                                            />
                                        </div>
                                        <div
                                            v-if="
                                                form.end_date &&
                                                form.start_date !==
                                                    form.end_date
                                            "
                                        >
                                            <InputLabel
                                                value="Session for End Date"
                                                class="mb-2 text-sm font-medium"
                                            />
                                            <div class="grid grid-cols-2 gap-3">
                                                <label
                                                    class="flex cursor-pointer items-center rounded-lg border bg-white p-3 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50"
                                                    ><input
                                                        type="radio"
                                                        v-model="
                                                            form.end_half_session
                                                        "
                                                        value="morning"
                                                        class="form-radio"
                                                    /><span class="ml-2 text-sm"
                                                        >Morning (AM)</span
                                                    ></label
                                                >
                                                <label
                                                    class="flex cursor-pointer items-center rounded-lg border bg-white p-3 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50"
                                                    ><input
                                                        type="radio"
                                                        v-model="
                                                            form.end_half_session
                                                        "
                                                        value="afternoon"
                                                        class="form-radio"
                                                    /><span class="ml-2 text-sm"
                                                        >Afternoon (PM)</span
                                                    ></label
                                                >
                                            </div>
                                            <InputError
                                                :message="
                                                    form.errors.end_half_session
                                                "
                                                class="mt-1"
                                            />
                                        </div>
                                    </div>
                                </template>

                                <div>
                                    <InputLabel
                                        for="reason"
                                        value="Reason"
                                        class="font-semibold"
                                    />
                                    <input
                                        type="text"
                                        id="reason"
                                        v-model="form.reason"
                                        required
                                        class="form-input mt-1"
                                        placeholder="e.g., Family event or Feeling unwell"
                                    />
                                    <InputError
                                        :message="form.errors.reason"
                                        class="mt-1"
                                    />
                                </div>

                                <div v-if="form.leave_type === 'sick'">
                                    <InputLabel
                                        for="supporting_document"
                                        value="Supporting Document (Optional)"
                                        class="font-semibold"
                                    />
                                    <input
                                        id="supporting_document"
                                        type="file"
                                        @change="onSupportingDocumentChange"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="form-file mt-1"
                                    />
                                    <InputError
                                        :message="
                                            form.errors.supporting_document
                                        "
                                        class="mt-1"
                                    />
                                </div>

                                <div class="border-t border-slate-200 pt-2">
                                    <PrimaryButton
                                        :disabled="
                                            form.processing || !form.start_date
                                        "
                                        class="w-full justify-center"
                                    >
                                        {{
                                            form.processing
                                                ? 'Submitting...'
                                                : 'Submit Leave Request'
                                        }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODALS (All redesigned and included) -->
        <!-- My Recent Requests Modal -->
       <div
    v-if="isRequestDetailModalVisible"
    @click.self="closeRequestDetailModal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 font-sans"
>
    <div class="flex w-full max-w-lg flex-col rounded-2xl bg-white shadow-2xl overflow-hidden animate-fadeIn">
        <!-- Header -->
        <header class="flex justify-between items-center border-b border-slate-200 px-6 py-4 bg-slate-50">
            <h2 class="text-lg font-bold text-slate-800">Leave Request Details</h2>
            <button @click="closeRequestDetailModal" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <!-- Body -->
        <div class="px-6 py-5 space-y-3 text-sm text-slate-700">
<p>
  <strong class="text-slate-600">Date:</strong>
  {{ formatDateRange(selectedRequest.start_date, selectedRequest.end_date, selectedRequest.start_half_session, selectedRequest.end_half_session) }}
</p>
            <p><strong class="text-slate-600">Type:</strong> <span class="capitalize">{{ selectedRequest.leave_type }}</span></p>
            <p>
                <strong class="text-slate-600">Status:</strong>
                <span :class="statusInfo(selectedRequest.status).badge">
                    {{ statusInfo(selectedRequest.status).text }}
                </span>
            </p>
            <p><strong class="text-slate-600">Reason:</strong> {{ selectedRequest.reason }}</p>
            <p v-if="selectedRequest.supporting_document_path">
                <strong class="text-slate-600">Document:</strong>
                <a :href="`/storage/${selectedRequest.supporting_document_path}`" target="_blank"
                   class="text-indigo-600 hover:underline font-medium">View File</a>
            </p>
        </div>

        <!-- Footer -->
        <footer class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button
                v-if="selectedRequest.leave_type === 'sick'"
                @click="openUploadModal(selectedRequest.id); closeRequestDetailModal()"
                class="px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-300 rounded-md hover:bg-slate-100 transition"
            >
                {{ selectedRequest.supporting_document_path ? 'Replace document' : 'Upload document' }}
            </button>
            <button
                v-if="selectedRequest.status === 'pending'"
                @click="openEditModal(selectedRequest)"
                class="px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-300 rounded-md hover:bg-slate-100 transition"
            >
                Edit
            </button>
            <button
                v-if="selectedRequest.status === 'pending'"
                @click="cancelLeave(selectedRequest)"
                class="px-3 py-1.5 text-xs font-semibold text-red-600 border border-red-300 rounded-md hover:bg-red-50 transition"
            >
                Cancel
            </button>
            <button
                @click="closeRequestDetailModal"
                class="px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-300 rounded-md hover:bg-slate-100 transition"
            >
                Close
            </button>
        </footer>
    </div>
</div>

        <!-- Edit Reason Modal -->
        <div
            v-if="isEditModalVisible"
            @click.self="closeEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans"
        >
            <form
                @submit.prevent="submitEditReason"
                class="flex w-full max-w-md flex-col gap-4 rounded-xl bg-white p-6 shadow-2xl"
            >
                <div class="mb-2 flex items-center gap-3">
                    <div class="rounded-full bg-blue-100 p-2">
                        <svg
                            class="h-6 w-6 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            ></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800">
                        Edit Reason
                    </h2>
                </div>
                <textarea
                    v-model="editingReason"
                    rows="4"
                    required
                    class="form-textarea"
                    :disabled="editProcessing"
                    maxlength="500"
                ></textarea>
                <div
                    class="mt-2 flex justify-end gap-3 border-t border-slate-200 pt-4"
                >
                    <button
                        type="button"
                        @click="closeEditModal"
                        class="btn-secondary"
                    >
                        Cancel</button
                    ><PrimaryButton type="submit" :disabled="editProcessing">{{
                        editProcessing ? 'Saving...' : 'Save Changes'
                    }}</PrimaryButton>
                </div>
            </form>
        </div>

        <!-- Leave Policy Modal -->
        <div
            v-if="isPolicyModalVisible"
            @click.self="closePolicyModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans"
        >
            <div
                class="flex w-full max-w-2xl flex-col rounded-xl bg-slate-50 shadow-2xl"
                style="height: 90vh; max-height: 750px"
            >
                <header
                    class="flex flex-shrink-0 items-center justify-between border-b border-slate-200 p-5"
                >
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-indigo-100 p-2">
                            <svg
                                class="h-6 w-6 text-indigo-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800">
                            Company Leave Policy
                        </h3>
                    </div>
                    <button
                        @click="closePolicyModal"
                        class="rounded-full p-2 text-slate-400 hover:bg-slate-200 hover:text-slate-600"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            ></path>
                        </svg>
                    </button>
                </header>
                <div class="flex-grow space-y-6 overflow-y-auto p-6">
                    <div
                        v-for="(policy, type) in leaveTypeDescriptions"
                        :key="type"
                        class="flex items-start gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow"
                    >
                        <!-- Icon for the leave type -->
                        <div class="text-3xl">
                            {{ leaveTypeIcons[type] || leaveTypeIcons.default }}
                        </div>

                        <!-- Policy text -->
                        <div>
                            <h4
                                class="mb-1 text-sm font-semibold text-slate-800"
                            >
                                {{ policy.title }}
                            </h4>
                            <p class="mb-2 text-xs text-slate-600">
                                {{ policy.summary }}
                            </p>
                            <ul
                                class="list-disc space-y-1 pl-4 text-xs text-slate-500"
                            >
                                <li
                                    v-for="(detail, index) in policy.details"
                                    :key="index"
                                >
                                    {{ detail }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <footer
                    class="flex flex-shrink-0 justify-end border-t border-slate-200 bg-white/50 p-4"
                >
                    <button @click="closePolicyModal" class="btn-secondary">
                        Close
                    </button>
                </footer>
            </div>
        </div>

        <!-- Upload Document Modal -->
        <div
            v-if="isUploadModalVisible"
            @click.self="closeUploadModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans"
        >
            <form
                @submit.prevent="submitUpload"
                enctype="multipart/form-data"
                class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-2xl"
            >
                <div class="mb-2 flex items-center gap-3">
                    <div class="rounded-full bg-sky-100 p-2">
                        <svg
                            class="h-6 w-6 text-sky-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                            ></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">
                        Upload Document
                    </h3>
                </div>
                <input
                    id="upload_file"
                    type="file"
                    @change="onUploadFileChange"
                    accept=".pdf,.jpg,.jpeg,.png"
                    required
                    class="form-file"
                />
                <div
                    v-if="uploadErrors.supporting_document"
                    class="text-sm text-red-600"
                >
                    {{ uploadErrors.supporting_document }}
                </div>
                <div
                    class="mt-2 flex justify-end gap-3 border-t border-slate-200 pt-4"
                >
                    <button
                        type="button"
                        @click="closeUploadModal"
                        class="btn-secondary"
                    >
                        Cancel
                    </button>
                    <PrimaryButton type="submit" :disabled="uploadProcessing">{{
                        uploadProcessing ? 'Uploading...' : 'Upload'
                    }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* All existing styles remain the same, they are well-written */
.fc {
    @apply text-slate-800;
    font-size: 0.9rem;
}
.fc .fc-toolbar.fc-header-toolbar {
    margin-bottom: 1.25em;
}
.fc .fc-toolbar-title {
    font-size: 1.25em;
    @apply font-bold text-slate-800;
}
.fc .fc-button {
    @apply border-slate-300 bg-white text-slate-700 hover:bg-slate-100 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0;
}
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    @apply border-indigo-600 bg-indigo-600 text-white;
}
.fc .fc-daygrid-day-frame {
    @apply min-h-[3em];
}
.fc .fc-daygrid-day-number {
    @apply p-2 text-slate-600;
}
.fc .fc-daygrid-day.fc-day-today {
    @apply bg-indigo-50;
}
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    @apply font-bold text-indigo-600;
}
.fc .fc-daygrid-day-top {
    @apply flex-row;
}
.fc .fc-col-header-cell {
    @apply border-b border-slate-200 bg-slate-50;
}
.fc .fc-col-header-cell-cushion {
    @apply py-2.5 text-sm font-semibold text-slate-600;
}
.fc .fc-bg-event {
    opacity: 0.8 !important;
}

/* vue-select styles */
.vs-style .vs__dropdown-toggle {
    @apply mt-1 block w-full rounded-md border-slate-300 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500;
    padding: 2px 0px 4px;
}
.vs-style .vs__selected {
    @apply py-0 pl-2 text-sm text-slate-800;
}
.vs-style .vs__search {
    @apply py-0 text-sm;
}
.vs-style .vs__dropdown-menu {
    @apply text-sm;
}
.vs-style .vs__spinner {
    @apply h-5 w-5;
}

/* Custom Scrollbar for specific sections */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
</style>

