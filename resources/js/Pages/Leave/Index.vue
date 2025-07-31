<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'

const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  remainingLeaveBalance: Number,
})

// --- Ref for active tab in the summary section ---
const activeTab = ref('upcoming');

// Ref for the leave form section to enable auto-scrolling
const leaveFormSection = ref(null);
// Refs for centered modals
const isPolicyModalVisible = ref(false);
const isHolidaysModalVisible = ref(false);
const isLeaveDetailModalVisible = ref(false);
const viewingRequest = ref(null);
const isSuccessModalVisible = ref(false);
const lastSuccessfulRequest = ref(null);

// --- NEW: Refs for the Document Upload Modal ---
const isUploadModalVisible = ref(false);
const requestForUpload = ref(null);

const statusConfig = {
  approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
  rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
  pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
}

// Holiday data for the pop-up
const upcomingHolidays = ref([
    {date: '01', month: 'Jan 2025', name: `New Year's Day`},
    {date: '21', month: 'Jan 2025', name: 'M.L.K. Jr Day'},
    {date: '18', month: 'Feb 2025', name: `President's Day`},
    {date: '27', month: 'May 2025', name: 'Memorial Day'},
    {date: '25', month: 'Apr 2025', name: 'Easter'},
    {date: '04', month: 'Jul 2025', name: 'Independence Day'}
]);

// --- Computed property to generate the current week for the timeline ---
const timelineWeek = computed(() => {
  const week = [];
  const today = new Date();
  const dayOfWeek = today.getDay(); // 0=Sun, 1=Mon, ..., 6=Sat
  const startOfWeek = new Date(today);
  const diff = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Days to subtract to get to Monday
  startOfWeek.setDate(today.getDate() - diff);

  for (let i = 0; i < 7; i++) {
    const currentDay = new Date(startOfWeek);
    currentDay.setDate(startOfWeek.getDate() + i);
    week.push({
      date: currentDay.getDate(),
      dayInitial: currentDay.toLocaleDateString('en-US', { weekday: 'narrow' }), // 'M', 'T', 'W', etc.
      isToday: currentDay.toDateString() === new Date().toDateString(),
    });
  }
  return week;
});


const leaveTypeDescriptions = {
  annual: { title: "Annual Leave", summary: "Planned time off with advance notice", details: [ "✓ For vacations, personal time, or other planned absences", "✓ Should be requested at least 7 days in advance", "✓ Requires manager approval", "✓ Balance accrued based on tenure (typically 15-25 days/year)" ] },
  sick: { title: "Sick Leave", summary: "For illness or medical appointments", details: [ "✓ Can be requested with short notice", "✓ Doctor's note required for absences longer than 3 days", "✓ Typically up to 10 paid days/year", "✓ Covers medical appointments and contagious illnesses" ] },
  personal: { title: "Personal Leave", summary: "For personal matters requiring 3+ days notice", details: [ "✓ Requires at least 3 days notice when possible", "✓ Limited to 5 paid days/year", "✓ Examples: Family emergencies, urgent personal business", "✓ Not for routine errands or non-urgent matters" ] },
  emergency: { title: "Emergency Leave", summary: "Unplanned urgent situations", details: [ "✓ No advance notice required", "✓ Typically unpaid after 3 days", "✓ Examples: Natural disasters, serious family emergencies", "✓ Documentation may be required for extended leave" ] },
  maternity: { title: "Maternity Leave", summary: "For new mothers (typically 12+ weeks)", details: [ "✓ Typically 12 weeks paid (varies by location)", "✓ Requires 30 days notice when possible", "✓ Medical documentation required", "✓ Can be combined with other leave types" ] },
  paternity: { title: "Paternity Leave", summary: "For new fathers (typically 2-4 weeks)", details: [ "✓ Typically 2-4 weeks paid", "✓ Can be taken within 6 months of birth/adoption", "✓ Requires 30 days notice when possible", "✓ Can be taken intermittently in some cases" ] }
}

const selectedDates = ref([null, null])
const today = new Date()
today.setHours(0, 0, 0, 0)

const form = useForm({
  start_date: '',
  end_date: '',
  reason: '',
  leave_type: 'sick',
  day_type: 'full',
  start_half_session: '',
  end_half_session: '',
  document: null,
})

// --- NEW: Form specifically for the document upload modal ---
const uploadForm = useForm({
    document: null,
});

// Function to smoothly scroll to the leave form
const scrollToLeaveForm = () => {
  if (leaveFormSection.value) {
    leaveFormSection.value.scrollIntoView({ behavior: 'smooth' });
  }
};

// Functions to control the centered modals
const openPolicyModal = () => { isPolicyModalVisible.value = true; };
const closePolicyModal = () => { isPolicyModalVisible.value = false; };
const openHolidaysModal = () => { isHolidaysModalVisible.value = true; };
const closeHolidaysModal = () => { isHolidaysModalVisible.value = false; };
const openLeaveDetailModal = (request) => {
    viewingRequest.value = request;
    isLeaveDetailModalVisible.value = true;
};
const closeLeaveDetailModal = () => {
    isLeaveDetailModalVisible.value = false;
    viewingRequest.value = null;
};
const closeSuccessModal = () => {
    isSuccessModalVisible.value = false;
    lastSuccessfulRequest.value = null;
};

// --- NEW: Functions to control the document upload modal ---
const openUploadModal = (request) => {
    requestForUpload.value = request;
    isUploadModalVisible.value = true;
};
const closeUploadModal = () => {
    isUploadModalVisible.value = false;
    requestForUpload.value = null;
    uploadForm.reset(); // Clear form and errors
};

const selectedDateRange = computed(() => {
  if (form.start_date && !form.end_date) {
    return `${form.start_date}${form.day_type === 'half' ? ` (${form.start_half_session || 'half'})` : ''}`
  }
  if (form.start_date && form.end_date) {
    return `${form.start_date} to ${form.end_date}`
  }
  return 'No dates selected'
})

const currentLeaveDescription = computed(() => {
  return leaveTypeDescriptions[form.leave_type] || leaveTypeDescriptions.annual
})

function toISODateOnly(date) {
  if (!date) return ''
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function formatDate(dateString) {
  if (!dateString) return '';
  let date;
  if (typeof dateString === 'string' && dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
    const [year, month, day] = dateString.split('-');
    date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
  } else {
    date = new Date(dateString);
  }
  if (isNaN(date.getTime())) {
    return 'Invalid Date';
  }
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function formatLeaveDays(days) {
  const num = Number(days)
  if (isNaN(num)) return '0'
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`
  return num % 1 === 0 ? num.toString() : num.toFixed(1)
}

function updateFormDates() {
  const [start, end] = selectedDates.value
  form.start_date = start ? toISODateOnly(start) : ''
  form.end_date = end ? toISODateOnly(end) : ''
}

// --- Custom Calendar Logic ---
const displayDate = ref(new Date());

const monthYearLabel = computed(() => {
    return displayDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const calendarGrid = computed(() => {
    const year = displayDate.value.getFullYear();
    const month = displayDate.value.getMonth();
    const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0=Sun, 1=Mon
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const [startSelection, endSelection] = selectedDates.value;
    const days = [];
    const startDay = (firstDayOfMonth === 0) ? 6 : firstDayOfMonth - 1;
    for (let i = 0; i < startDay; i++) {
        days.push({ key: `pad-start-${i}`, isPadding: true });
    }
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        date.setHours(0,0,0,0);
        const isSelected = (startSelection && date.getTime() === startSelection.getTime()) || (endSelection && date.getTime() === endSelection.getTime());
        const isInRange = startSelection && endSelection && date > startSelection && date < endSelection;
        days.push({
            key: `day-${day}`, day, date, isPadding: false,
            isToday: date.getTime() === today.getTime(),
            isPast: date < today, isSelected, isInRange,
        });
    }
    const remainingSlots = (7 - (days.length % 7)) % 7;
    for (let i = 0; i < remainingSlots; i++) {
        days.push({ key: `pad-end-${i}`, isPadding: true });
    }
    return days;
});

const getTagClass = (leaveType) => {
  const classes = {
    annual: 'bg-blue-100 text-blue-800', sick: 'bg-green-100 text-green-800',
    personal: 'bg-yellow-100 text-yellow-800', emergency: 'bg-red-100 text-red-800',
    maternity: 'bg-pink-100 text-pink-800', paternity: 'bg-purple-100 text-purple-800',
  };
  return classes[leaveType] || 'bg-gray-100 text-gray-800';
};

const handleDateClick = (day) => {
  if (day.isPast) {
    alert('Please select a date that is today or in the future.');
    return;
  }
  const clickedDate = day.date;
  const [start, end] = selectedDates.value;
  if (!start || end) {
    selectedDates.value = [clickedDate, null];
  } else {
    if (clickedDate >= start) {
      selectedDates.value = [start, clickedDate];
    } else {
      selectedDates.value = [clickedDate, null];
    }
  }
  updateFormDates();
}

const changeMonth = (offset) => {
    displayDate.value = new Date(displayDate.value.setMonth(displayDate.value.getMonth() + offset));
};

const handleFileChange = (event) => {
    form.document = event.target.files[0];
}

// --- NEW: Handle file change for the upload modal ---
const handleUploadModalFileChange = (event) => {
    uploadForm.document = event.target.files[0];
};

const validateForm = () => {
  if (!form.start_date) {
    alert('Please select at least a start date.')
    return false
  }
  if (form.leave_type === 'sick' && form.document === null) {
    if (!confirm('You haven\'t attached a medical document. Sick leave typically requires documentation. Continue without attaching?')) {
      return false
    }
  }
  if (form.day_type === 'half') {
    if (!form.start_half_session) {
      alert('Please select morning or afternoon session for start date.')
      return false
    }
    if (form.end_date && form.start_date !== form.end_date && !form.end_half_session) {
      alert('Please select morning or afternoon session for end date.')
      return false
    }
  }
  return true
}

const checkAdvanceNotice = () => {
  const startDate = new Date(form.start_date)
  const timeDiff = startDate.getTime() - today.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))
  if (form.leave_type === 'annual' && daysDiff < 7) {
    return confirm('Warning: Annual leaves should be requested at least 7 days in advance. Do you still want to submit?')
  }
  if (form.leave_type === 'personal' && daysDiff < 3) {
    return confirm('Warning: Personal leaves should be requested at least 3 days in advance. Do you still want to submit?')
  }
  return true
}

const submitApplication = () => {
    if (!validateForm()) return;
    if (!form.end_date) {
        form.end_date = form.start_date;
        const startDate = new Date(form.start_date + 'T00:00:00');
        selectedDates.value = [startDate, startDate];
        if (form.day_type === 'half') { form.end_half_session = form.start_half_session; }
    }
    if (form.day_type === 'full') {
        form.start_half_session = null;
        form.end_half_session = null;
    }
    if (!checkAdvanceNotice()) return;
    const start = new Date(form.start_date);
    const end = new Date(form.end_date);
    let calculatedDays;
    if (form.day_type === 'half') {
        const diffInMs = Math.abs(end.getTime() - start.getTime());
        const diffInDays = diffInMs / (1000 * 3600 * 24);
        if (diffInDays === 0) {
            calculatedDays = 0.5;
        } else {
            calculatedDays = diffInDays + 1;
            if (form.start_half_session === 'afternoon') calculatedDays -= 0.5;
            if (form.end_half_session === 'morning') calculatedDays -= 0.5;
        }
    } else {
        const diffInMs = Math.abs(end.getTime() - start.getTime());
        calculatedDays = diffInMs / (1000 * 3600 * 24) + 1;
    }
    const submissionData = {
        days: formatLeaveDays(calculatedDays), reason: form.reason,
        start_date: form.start_date, end_date: form.end_date,
        leave_type: form.leave_type, day_type: form.day_type,
        start_half_session: form.start_half_session, end_half_session: form.end_half_session
    };
    form.post(route('leave.store'), {
        preserveScroll: true,
        onSuccess: () => {
            lastSuccessfulRequest.value = submissionData;
            isSuccessModalVisible.value = true;
            form.reset();
            form.leave_type = 'sick';
            form.day_type = 'full';
            selectedDates.value = [null, null];
        },
        onError: (errors) => {
            console.error('Leave submission errors:', errors);
            if (errors.message) { alert(errors.message); }
        }
    });
}

// --- NEW: Function to submit the document from the modal ---
const submitDocument = () => {
    if (!requestForUpload.value || !uploadForm.document) {
        alert("Please select a file to upload.");
        return;
    }
    // Using router.post for multipart form data, which is needed for file uploads.
    router.post(route('leave.upload.document', { leave_application: requestForUpload.value.id }), {
        _method: 'PATCH', // Method spoofing for Laravel
        document: uploadForm.document
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeUploadModal();
            alert('Document uploaded successfully!');
        },
        onError: (errors) => {
            // Error handling is automatic with Inertia's form helper, but you can add more here.
            console.error("Document upload failed:", errors);
        },
        onFinish: () => {
            uploadForm.processing = false;
        }
    });
};

const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), { status: newStatus }, { preserveScroll: true })
}

const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), { preserveScroll: true })
  }
}
</script>

<template>
    <Head title="Leave" />

    <AuthenticatedLayout>
        <div class="p-6 bg-gray-50 min-h-screen">
            <!-- This title is for the entire page -->
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Leave</h1>

            <!-- Top Section Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Interactive Tabbed Section -->
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Leave Summary</h2>
                        <!-- Tab Buttons -->
                        <div class="flex items-center gap-2 mt-3 sm:mt-0 p-1 bg-gray-100 rounded-lg">
                            <button @click="activeTab = 'upcoming'" :class="{'bg-white text-blue-700 shadow-sm': activeTab === 'upcoming', 'text-gray-600 hover:text-gray-900': activeTab !== 'upcoming'}" class="px-3 py-1.5 text-sm font-medium rounded-md transition-all">Upcoming</button>
                            <button @click="activeTab = 'team'" :class="{'bg-white text-blue-700 shadow-sm': activeTab === 'team', 'text-gray-600 hover:text-gray-900': activeTab !== 'team'}" class="px-3 py-1.5 text-sm font-medium rounded-md transition-all">Team</button>
                            <button @click="activeTab = 'timeline'" :class="{'bg-white text-blue-700 shadow-sm': activeTab === 'timeline', 'text-gray-600 hover:text-gray-900': activeTab !== 'timeline'}" class="px-3 py-1.5 text-sm font-medium rounded-md transition-all">Timeline</button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="min-h-[160px]">
                        <!-- Upcoming Leave Tab -->
                        <div v-if="activeTab === 'upcoming'" class="space-y-3 animate-fade-in">
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="bg-blue-100 text-blue-600 p-3 rounded-lg mr-4"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>
                                <div class="flex-grow">
                                    <p class="font-semibold text-gray-800">Aug 15, 2025</p>
                                    <p class="text-sm text-gray-700">Annual Leave - 1 day</p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg mr-4"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                <div class="flex-grow">
                                    <p class="font-semibold text-gray-800">Sep 05, 2025 - Sep 06, 2025</p>
                                    <p class="text-sm text-gray-700">Personal Leave - 2 days</p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                            </div>
                        </div>

                        <!-- Team Schedule Tab -->
                        <div v-if="activeTab === 'team'" class="space-y-3 animate-fade-in">
                           <p class="text-sm font-medium text-gray-700">Away this week</p>
                           <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <img class="h-10 w-10 rounded-full mr-3" src="https://i.pravatar.cc/100?u=jane" alt="Jane Doe">
                                <div class="flex-grow">
                                    <p class="font-semibold text-gray-800">Jane Doe</p>
                                    <p class="text-sm text-gray-700">Sick Leave</p>
                                </div>
                                <p class="text-sm text-gray-800">All Week</p>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <img class="h-10 w-10 rounded-full mr-3" src="https://i.pravatar.cc/100?u=john" alt="John Smith">
                                <div class="flex-grow">
                                    <p class="font-semibold text-gray-800">John Smith</p>
                                    <p class="text-sm text-gray-700">Annual Leave</p>
                                </div>
                                <p class="text-sm text-gray-800">Until Wednesday</p>
                            </div>
                        </div>

                        <!-- Visual Timeline Tab -->
                        <div v-if="activeTab === 'timeline'" class="animate-fade-in space-y-3">
                           <h3 class="font-semibold text-gray-800 text-center mb-2">This Week's Timeline</h3>
                           <!-- Timeline Header (Dynamic) -->
                            <div class="grid grid-cols-7 text-center text-xs text-gray-700 font-medium">
                                <div v-for="day in timelineWeek" :key="day.date" class="space-y-1">
                                    <p :class="{'text-blue-600 font-bold': day.isToday}">{{ day.dayInitial }}</p>
                                    <p :class="{'text-blue-600 font-bold bg-blue-100 rounded-full': day.isToday}">{{ day.date }}</p>
                                </div>
                            </div>
                            <!-- Manager View -->
                            <div v-if="canManage" class="space-y-2 text-sm">
                                <!-- User: Jane Doe (Illustrative) -->
                                <div class="flex items-center">
                                    <p class="w-24 text-xs truncate pr-2 text-right text-gray-800">Jane Doe</p>
                                    <div class="grid grid-cols-7 flex-grow gap-px">
                                        <div class="h-6 bg-red-400 rounded-l-full" title="Jane Doe - Sick Leave"></div>
                                        <div class="h-6 bg-red-400" title="Jane Doe - Sick Leave"></div>
                                        <div class="h-6 bg-red-400 rounded-r-full" title="Jane Doe - Sick Leave"></div>
                                    </div>
                                </div>
                                <!-- User: John Smith (Illustrative) -->
                                <div class="flex items-center">
                                    <p class="w-24 text-xs truncate pr-2 text-right text-gray-800">John Smith</p>
                                    <div class="grid grid-cols-7 flex-grow gap-px">
                                        <div></div><div></div><div></div>
                                        <div class="h-6 bg-blue-400 rounded-l-full" title="John Smith - Annual Leave"></div>
                                        <div class="h-6 bg-blue-400 rounded-r-full" title="John Smith - Annual Leave"></div>
                                    </div>
                                </div>
                                <!-- User: You (Illustrative) -->
                                <div class="flex items-center">
                                    <p class="w-24 text-xs truncate pr-2 text-right font-bold text-gray-900">You</p>
                                    <div class="grid grid-cols-7 flex-grow gap-px">
                                        <div></div><div></div><div></div><div></div>
                                        <div class="h-6 bg-green-500 rounded-full" title="You - Approved Leave"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Employee View -->
                             <div v-else class="space-y-2 text-sm">
                                <div class="grid grid-cols-7 gap-px relative h-6">
                                    <!-- Leave markers would be placed here based on real data -->
                                    <div class="h-full bg-green-500 rounded-full col-start-5" title="Your Approved Leave"></div>
                                    <div class="h-full bg-purple-400 rounded-full col-start-7" title="Public Holiday"></div>
                                </div>
                                <div class="flex justify-center gap-4 text-xs pt-3 text-gray-800">
                                  <div class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded-full mr-1.5"></span>Your Leave</div>
                                  <div class="flex items-center"><span class="w-3 h-3 bg-purple-400 rounded-full mr-1.5"></span>Holiday</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="space-y-3 flex flex-col justify-between">
                    <button @click="scrollToLeaveForm" class="w-full text-left bg-blue-600 text-white p-4 rounded-lg shadow-sm hover:bg-blue-700 transition font-medium">Apply Leave</button>
                    <button @click="openHolidaysModal" class="w-full text-left bg-white text-gray-800 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">Upcoming Holidays</button>
                    <button @click="openPolicyModal" class="w-full text-left bg-white text-gray-800 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">View Leave Policy</button>
                </div>
            </div>

            <!-- Available Leaves Section -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Available Leaves</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <div class="bg-white p-4 rounded-lg shadow-sm text-center" v-for="leave in [{name: 'Casual Leave', color: 'text-blue-500', available: 5, consumed: 7, annual: 12, progress: 58}, {name: 'Sick Leave', color: 'text-red-500', available: 5, consumed: 2, annual: 6, progress: 33}, {name: 'Compensatory Leave', color: 'text-green-500', available: 5, consumed: 5, annual: 10, progress: 50}, {name: 'Special Holiday', color: 'text-purple-500', available: 5, consumed: 5, annual: 10, progress: 50}, {name: 'Loss of Pay', color: 'text-gray-400', available: 5, consumed: 5, annual: 10, progress: 50}]" :key="leave.name">
                    <div class="relative w-24 h-24 mx-auto mb-2">
                    <svg class="w-full h-full" viewBox="0 0 36 36"><path class="text-gray-200" stroke-width="3" fill="none" stroke="currentColor" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path><path :class="leave.color" stroke-width="3" fill="none" stroke-linecap="round" stroke="currentColor" :stroke-dasharray="`${leave.progress}, 100`" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path></svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold text-gray-800">{{leave.available}}</span>
                        <span class="text-xs text-gray-700">Days</span>
                    </div>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm">{{leave.name}}</p>
                    <div class="text-xs text-gray-600 mt-1">Consumed: {{String(leave.consumed).padStart(2,'0')}} / Annual: {{leave.annual}}</div>
                </div>
                </div>
            </div>

            <!-- New Leave Request Form Card -->
            <div ref="leaveFormSection" v-if="!canManage" class="bg-white rounded-lg shadow-sm mb-6">
                <!-- MODIFICATION START: Updated the form header to be more attractive -->
                <div class="p-6 flex items-center gap-5 border-b border-gray-200">
                    <div class="bg-blue-100 text-blue-600 p-4 rounded-xl">
                        <!-- Icon: Calendar with a plus sign -->
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v-4m-2 2h4"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Create a New Leave Request</h3>
                        <p class="mt-1 text-sm text-gray-600">Select dates and provide the necessary details for your time off.</p>
                    </div>
                </div>
                <!-- MODIFICATION END -->

                <form @submit.prevent="submitApplication" class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6">
                    <!-- Left Panel: Calendar -->
                    <div class="flex flex-col">
                        <div class="border border-gray-200 rounded-lg p-4 space-y-4 flex-grow flex flex-col">
                            <div class="flex items-center justify-between">
                               <div class="flex items-center space-x-2">
                                    <button @click.prevent="changeMonth(-1)" class="p-2 rounded-full text-gray-600 hover:bg-gray-100 hover:text-gray-900"><</button>
                                    <h3 class="text-base font-semibold text-gray-800 w-32 text-center">{{ monthYearLabel }}</h3>
                                    <button @click.prevent="changeMonth(1)" class="p-2 rounded-full text-gray-600 hover:bg-gray-100 hover:text-gray-900">></button>
                                </div>
                                <div class="text-xs font-medium px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                                    {{ selectedDateRange }}
                                </div>
                            </div>

                            <!-- Custom Calendar Grid -->
<div class="space-y-2 flex-grow flex flex-col">
    <div class="grid grid-cols-7 text-center text-xs text-gray-500 font-medium">
        <div v-for="day in ['M', 'T', 'W', 'T', 'F', 'S', 'S']" :key="day" class="pb-2">{{ day }}</div>
    </div>
    <div class="grid grid-cols-7 border-r border-b border-gray-200 flex-grow">
        <div v-for="day in calendarGrid" :key="day.key" class="relative flex items-center justify-center h-10 border-t border-l border-gray-200">
            <div v-if="day.isInRange" class="absolute h-full w-full bg-blue-100 z-0"></div>
            <button
                v-if="!day.isPadding"
                @click.prevent="handleDateClick(day)"
                :disabled="day.isPast"
                class="relative z-10 w-8 h-8 flex items-center justify-center rounded-full text-sm transition-colors duration-200"
                :class="{
                    'text-gray-400 cursor-not-allowed': day.isPast,
                    'text-gray-700 hover:bg-gray-100': !day.isPast && !day.isSelected && !day.isToday,
                    'bg-blue-600 text-white font-semibold': day.isSelected,
                    'bg-blue-100 text-blue-600': day.isToday && !day.isSelected,
                    'ring-2 ring-blue-500': day.isToday && !day.isSelected,
                    'bg-blue-600 text-white': day.isToday && day.isSelected
                }"
            >
                {{ day.day }}
            </button>
        </div>
    </div>
</div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.start_date" />
                    </div>

                    <!-- Right Panel: Form Fields -->
                    <div class="flex flex-col h-full gap-6">
                        <!-- Balance Card -->
                        <div class="p-4 rounded-lg bg-blue-50 flex items-center gap-4">
                            <div class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                            </div>
                            <div>
                               <p class="text-sm font-medium text-gray-700">Remaining Leave Balance</p>
                               <p class="text-2xl font-bold text-blue-600 mt-1">{{ remainingLeaveBalance }} <span class="text-xl font-medium">days</span></p>
                            </div>
                        </div>

                        <!-- Form Sections within a container -->
                        <div class="flex-grow flex flex-col gap-6">
                            <!-- Leave Type -->
                            <div class="p-4 rounded-lg border border-gray-200 bg-white">
                                <InputLabel for="leave_type" value="Leave Type" class="text-sm font-semibold text-gray-800 mb-2" />
                                <select id="leave_type" v-model="form.leave_type" required class="bg-white text-gray-900 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base">
                                    <option value="annual">Annual Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="personal">Personal Leave</option>
                                    <option value="emergency">Emergency Leave</option>
                                    <option value="maternity">Maternity Leave</option>
                                    <option value="paternity">Paternity Leave</option>
                                </select>
                                <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <p class="text-sm text-gray-700">{{ currentLeaveDescription.summary }}</p>
                                </div>
                                
                                <!-- Document Upload (shown only for sick leave) -->
                                <div v-if="form.leave_type === 'sick'" class="mt-4">
                                    <InputLabel for="document" value="Medical Document (Optional)" class="text-sm font-semibold text-gray-800 mb-2" />
                                    <div class="flex items-center gap-3">
                                        <input
                                            id="document"
                                            type="file"
                                            @change="handleFileChange"
                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                            class="block w-full text-sm text-gray-500
                                                file:mr-4 file:py-2 file:px-4
                                                file:rounded-md file:border-0
                                                file:text-sm file:font-semibold
                                                file:bg-blue-50 file:text-blue-700
                                                hover:file:bg-blue-100"
                                        >
                                        <span v-if="form.document" class="text-sm text-gray-700 truncate max-w-xs">{{ form.document.name }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Upload doctor's note or medical certificate (PDF, JPG, PNG, DOC)</p>
                                    <InputError class="mt-2" :message="form.errors.document" />
                                </div>
                                
                                <InputError class="mt-2" :message="form.errors.leave_type" />
                            </div>

                            <!-- Duration -->
                            <div class="p-4 rounded-lg border border-gray-200 bg-white">
                                <InputLabel value="Duration" class="text-sm font-semibold text-gray-800 mb-3" />
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="relative p-3 border rounded-lg cursor-pointer transition-all flex items-center justify-center text-sm font-medium" :class="form.day_type === 'full' ? 'bg-blue-100 border-blue-500 text-blue-800' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400'">
                                        <input type="radio" v-model="form.day_type" value="full" class="absolute opacity-0 w-0 h-0" /> Full Day
                                    </label>
                                    <label class="relative p-3 border rounded-lg cursor-pointer transition-all flex items-center justify-center text-sm font-medium" :class="form.day_type === 'half' ? 'bg-blue-100 border-blue-500 text-blue-800' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400'">
                                        <input type="radio" v-model="form.day_type" value="half" class="absolute opacity-0 w-0 h-0" /> Half Day
                                    </label>
                                </div>
                                <template v-if="form.day_type === 'half'">
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <InputLabel value="Start Session" class="text-sm font-semibold text-gray-800 mb-3"/>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="relative p-3 border rounded-lg cursor-pointer transition-all flex items-center justify-center text-sm font-medium" :class="form.start_half_session === 'morning' ? 'bg-blue-100 border-blue-500 text-blue-800' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400'">
                                                <input type="radio" v-model="form.start_half_session" value="morning" class="absolute opacity-0 w-0 h-0" /> Morning
                                            </label>
                                            <label class="relative p-3 border rounded-lg cursor-pointer transition-all flex items-center justify-center text-sm font-medium" :class="form.start_half_session === 'afternoon' ? 'bg-blue-100 border-blue-500 text-blue-800' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400'">
                                                <input type="radio" v-model="form.start_half_session" value="afternoon" class="absolute opacity-0 w-0 h-0" /> Afternoon
                                            </label>
                                        </div>
                                         <InputError class="mt-2" :message="form.errors.start_half_session"/>
                                    </div>
                                    <div v-if="form.end_date && form.start_date !== form.end_date" class="mt-4 pt-4 border-t border-gray-200">
                                        <InputLabel value="End Session" class="text-sm font-semibold text-gray-800 mb-3"/>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="relative p-3 border rounded-lg cursor-pointer transition-all flex items-center justify-center text-sm font-medium" :class="form.end_half_session === 'morning' ? 'bg-blue-100 border-blue-500 text-blue-800' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400'">
                                                <input type="radio" v-model="form.end_half_session" value="morning" class="absolute opacity-0 w-0 h-0" /> Morning
                                            </label>
                                            <label class="relative p-3 border rounded-lg cursor-pointer transition-all flex items-center justify-center text-sm font-medium" :class="form.end_half_session === 'afternoon' ? 'bg-blue-100 border-blue-500 text-blue-800' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400'">
                                                <input type="radio" v-model="form.end_half_session" value="afternoon" class="absolute opacity-0 w-0 h-0" /> Afternoon
                                            </label>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.end_half_session" />
                                    </div>
                                </template>
                            </div>

                            <!-- Reason (flex-grow will make this section fill remaining space) -->
                            <div class="p-4 rounded-lg border border-gray-200 bg-white flex flex-col flex-grow">
                                <InputLabel for="reason" value="Reason for Leave" class="text-sm font-semibold text-gray-800 mb-2"/>
                                <textarea id="reason" v-model="form.reason" rows="3" required class="bg-white text-gray-900 flex-grow w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base resize-none" placeholder="Please provide a brief explanation..."></textarea>
                                <InputError class="mt-2" :message="form.errors.reason" />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div>
                             <PrimaryButton :disabled="form.processing" class="w-full justify-center py-3 px-4 text-base font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ form.processing ? 'Submitting...' : 'Submit Leave Request' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <!-- Leave Requests History Table -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                     <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ canManage ? 'Employee Leave Requests' : 'Your Leave History' }}</h3>
                        <!-- <div class="flex items-center gap-4 mt-2 sm:mt-0">
                            <div class="relative">
                                <input type="text" placeholder="Search" class="pl-8 pr-2 py-1.5 border border-gray-300 rounded-md text-sm w-40 focus:ring-blue-500 focus:border-blue-500">
                                <svg class="w-4 h-4 text-gray-500 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <button class="p-2 border border-gray-300 rounded-md hover:bg-gray-100">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4 4m0 0l4-4m-4 4v-8"></path></svg>
                            </button>
                        </div> -->
                    </div>

                    <div v-if="leaveRequests.length === 0" class="p-12 text-center">
                        <div class="text-gray-400 text-5xl mb-3">📂</div>
                        <p class="font-medium text-gray-700">No leave requests found.</p>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block" v-if="leaveRequests.length > 0">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th v-if="canManage" scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Employee</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Dates</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Duration</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Reason</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Document</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-3 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                  <tbody class="bg-white divide-y divide-gray-200">
                                      <tr v-for="request in leaveRequests" :key="request.id" class="hover:bg-gray-50 text-gray-800">
                                          <td v-if="canManage" class="px-3 py-4 whitespace-nowrap">
                                              <div class="flex items-center">
                                                  <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                      <span class="text-base font-medium text-gray-700">{{ request.user?.name?.charAt(0)?.toUpperCase() }}</span>
                                                  </div>
                                                  <div class="ml-3 min-w-0 flex-1">
                                                      <div class="text-sm font-medium text-gray-900 truncate">{{ request.user?.name }}</div>
                                                      <div class="text-xs text-gray-600 truncate">{{ request.user?.email }}</div>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-3 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-900">
                                                  {{ formatDate(request.start_date) }}
                                                  <span v-if="request.start_date !== request.end_date"> - {{ formatDate(request.end_date) }}</span>
                                              </div>
                                          </td>
                                          <td class="px-3 py-4 whitespace-nowrap">
                                              <span :class="getTagClass(request.leave_type)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium capitalize">
                                                  {{ request.leave_type }}
                                              </span>
                                          </td>
                                          <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                              {{ formatLeaveDays(request.leave_days) }} day{{ request.leave_days !== 1 ? 's' : '' }}
                                          </td>
                                          <td class="px-3 py-4 text-sm text-gray-700">
                                              <div class="max-w-[150px] truncate" :title="request.reason">{{ request.reason }}</div>
                                          </td>
                                          <td class="px-3 py-4 whitespace-nowrap text-center space-y-1">
                                               <div>
                                                  <a v-if="request.supporting_document_path" :href="`/storage/${request.supporting_document_path}`" target="_blank" class="text-indigo-600 hover:underline inline-flex items-center justify-center gap-1 text-xs" title="View supporting document">View</a>
                                                  <span v-else class="text-gray-400 text-xs italic">—</span>
                                                </div>
                                                <div v-if="request.leave_type === 'sick' && ['pending', 'approved'].includes(request.status)">
                                                  <button @click="openUploadModal(request)" class="text-blue-600 hover:underline text-xs mt-1">
                                                    {{ request.supporting_document_path ? 'Replace' : 'Upload' }}
                                                  </button>
                                                </div>
                                          </td>
                                          <td class="px-3 py-4 whitespace-nowrap">
                                              <span :class="statusConfig[request.status].class" class="px-2 py-1 rounded-full text-xs font-medium inline-flex items-center">
                                                  <span class="mr-1.5">{{ statusConfig[request.status].icon }}</span>
                                                  <span class="capitalize">{{ request.status }}</span>
                                              </span>
                                          </td>
                                          <td class="px-3 py-4 whitespace-nowrap text-center">
                                              <div v-if="canManage && request.status === 'pending'" class="flex justify-center gap-2">
                                                  <button @click="updateStatus(request, 'approved')" class="font-semibold text-green-600 hover:text-green-800">Approve</button>
                                                  <button @click="updateStatus(request, 'rejected')" class="font-semibold text-red-600 hover:text-red-800">Reject</button>
                                              </div>
                                              <button v-else-if="!canManage && request.status === 'pending'" @click="cancelLeave(request)" class="font-semibold text-red-600 hover:text-red-800">Cancel</button>
                                              <button @click="openLeaveDetailModal(request)" class="text-gray-500 hover:text-blue-600 p-1" title="View Details">
                                                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                                              </button>
                                          </td>
                                      </tr>
                                  </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Table View (Replaced) -->
                    <div class="lg:hidden" v-if="leaveRequests.length > 0">
                      <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                          <thead class="bg-gray-50">
                              <tr>
                                  <th class="py-3 px-3 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                                  <th class="py-3 px-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                  <th class="py-3 px-3 text-left text-xs font-semibold text-gray-700 uppercase">Submitted</th>
                                  <th class="py-3 px-3 text-center text-xs font-semibold text-gray-700 uppercase">Document</th>
                                  <th class="py-3 px-3 text-right text-xs font-semibold text-gray-700 uppercase">Actions</th>
                              </tr>
                          </thead>
                          <tbody class="divide-y divide-gray-200">
                            <tr v-for="request in props.leaveRequests" :key="request.id" class="text-gray-700 hover:bg-gray-50">
                              <td class="py-3 px-3">
                                <p class="font-semibold">{{ new Date(request.start_date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                              </td>
                              <td class="py-3 px-3">
                                <span :class="getTagClass(request.leave_type)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">{{ request.leave_type }}</span>
                              </td>
                              <td class="py-3 px-3">
                                <p>{{ new Date(request.created_at).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                                <p class="text-xs text-gray-500">{{ new Date(request.created_at).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) }}</p>
                              </td>
            
                              <td class="py-3 px-3 text-center whitespace-nowrap space-y-1">
                                <div>
                                  <a v-if="request.supporting_document_path" :href="`/storage/${request.supporting_document_path}`" target="_blank"
                                     class="text-indigo-600 hover:underline inline-flex items-center justify-center gap-1" title="View supporting document">
                                    View
                                  </a>
                                  <span v-else class="text-gray-400 text-xs italic">—</span>
                                </div>
                                <div v-if="request.leave_type === 'sick' && ['pending', 'approved'].includes(request.status)">
                                  <button @click="openUploadModal(request)" class="text-blue-600 hover:underline text-xs mt-1">
                                    {{ request.supporting_document_path ? 'Replace' : 'Upload' }}
                                  </button>
                                </div>
                              </td>
            
                              <td class="py-3 px-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                  <div v-if="canManage && request.status === 'pending'" class="space-x-2">
                                    <button @click="updateStatus(request, 'approved')" class="text-green-600 hover:text-green-900 font-semibold">Approve</button>
                                    <button @click="updateStatus(request, 'rejected')" class="text-red-600 hover:text-red-900 font-semibold">Reject</button>
                                  </div>
                                  <button v-else-if="!canManage && request.status === 'pending'" @click="cancelLeave(request)" class="text-red-600 hover:text-red-900 font-semibold">Cancel</button>
                                  <button @click="openLeaveDetailModal(request)" class="text-gray-400 hover:text-gray-600">Details</button>
                                </div>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->

            <!-- NEW: Document Upload Modal -->
            <div v-if="isUploadModalVisible" @click.self="closeUploadModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
                <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full transform transition-all duration-300 scale-95" :class="{ 'scale-100': isUploadModalVisible }">
                    <form @submit.prevent="submitDocument">
                        <!-- Modal Header -->
                        <div class="p-5 border-b flex justify-between items-center">
                            <h3 class="text-xl font-semibold text-gray-800">Upload Supporting Document</h3>
                            <button @click="closeUploadModal" type="button" class="text-gray-500 hover:text-gray-800 text-3xl leading-none">×</button>
                        </div>
                        <!-- Modal Body -->
                        <div class="p-6 space-y-4">
                            <p class="text-sm text-gray-700">
                                You are uploading a document for your sick leave request on
                                <strong class="font-medium text-gray-900">{{ formatDate(requestForUpload.start_date) }}</strong>.
                            </p>
                            <div>
                                <InputLabel for="modal_document" value="Select Document" class="font-semibold" />
                                <input
                                    id="modal_document"
                                    type="file"
                                    @change="handleUploadModalFileChange"
                                    required
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    class="mt-2 block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2.5 file:px-4
                                        file:rounded-lg file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                >
                                <InputError class="mt-2" :message="uploadForm.errors.document" />
                                <p v-if="uploadForm.document" class="mt-2 text-sm text-gray-600">Selected: {{ uploadForm.document.name }}</p>
                            </div>
                        </div>
                         <!-- Modal Footer -->
                        <div class="p-4 bg-gray-50 flex justify-end gap-3 rounded-b-xl">
                            <button @click="closeUploadModal" type="button" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 font-semibold text-sm">Cancel</button>
                            <PrimaryButton :disabled="uploadForm.processing" type="submit" class="px-5 py-2 text-sm">
                                {{ uploadForm.processing ? 'Submitting...' : 'Upload Document' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Upcoming Holidays Modal -->
            <div v-if="isHolidaysModalVisible" @click.self="closeHolidaysModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col transform transition-all duration-300 scale-95" :class="{ 'scale-100': isHolidaysModalVisible }">
                    <!-- Modal Header -->
                    <div class="p-5 border-b flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-800">Upcoming Holidays 2025</h3>
                        <button @click="closeHolidaysModal" class="text-gray-500 hover:text-gray-800 text-3xl leading-none">×</button>
                    </div>
                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto">
                        <div class="space-y-4">
                            <div class="flex items-center gap-4" v-for="holiday in upcomingHolidays" :key="holiday.name">
                                <div class="bg-gray-100 p-3 rounded-lg text-center w-16 shrink-0">
                                    <p class="font-bold text-gray-800">{{ holiday.date }}</p>
                                    <p class="text-xs text-gray-700">{{ holiday.month }}</p>
                                </div>
                                <p class="font-medium text-gray-800">{{ holiday.name }}</p>
                            </div>
                        </div>
                    </div>
                     <!-- Modal Footer -->
                    <div class="p-4 bg-gray-50 text-right rounded-b-xl">
                        <button @click="closeHolidaysModal" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 font-semibold text-sm">Close</button>
                    </div>
                </div>
            </div>

            <!-- Leave Policy Modal -->
            <div v-if="isPolicyModalVisible" @click.self="closePolicyModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col transform transition-all duration-300 scale-95" :class="{ 'scale-100': isPolicyModalVisible }">
                    <!-- Modal Header -->
                    <div class="p-5 border-b flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-800">Company Leave Policy</h3>
                        <button @click="closePolicyModal" class="text-gray-500 hover:text-gray-800 text-3xl leading-none">×</button>
                    </div>
                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto">
                         <div class="space-y-6">
                            <div v-for="(policy, type) in leaveTypeDescriptions" :key="type" class="pb-4 border-b last:border-b-0">
                                <h4 class="text-lg font-semibold text-gray-800 mb-2 capitalize">{{ policy.title }}</h4>
                                <p class="text-sm text-gray-700 mb-3 italic">"{{ policy.summary }}"</p>
                                <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-800">
                                <li v-for="(detail, index) in policy.details" :key="index">
                                    {{ detail.startsWith('✓') ? detail.substring(2) : detail }}
                                </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                     <!-- Modal Footer -->
                    <div class="p-4 bg-gray-50 text-right rounded-b-xl">
                        <button @click="closePolicyModal" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 font-semibold text-sm">Close</button>
                    </div>
                </div>
            </div>

            <!-- Leave Detail View Modal -->
            <div v-if="isLeaveDetailModalVisible && viewingRequest" @click.self="closeLeaveDetailModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
                <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col transform transition-all duration-300 scale-95" :class="{ 'scale-100': isLeaveDetailModalVisible }">
                    <!-- Modal Header -->
                    <div class="p-5 border-b flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-800">Leave Request Details</h3>
                        <button @click="closeLeaveDetailModal" class="text-gray-500 hover:text-gray-800 text-3xl leading-none">×</button>
                    </div>
                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto space-y-4">
                        <div v-if="canManage" class="pb-4 border-b">
                           <p class="text-sm font-medium text-gray-600">Employee</p>
                           <p class="text-base text-gray-800 font-semibold">{{ viewingRequest.user?.name }}</p>
                           <p class="text-sm text-gray-500">{{ viewingRequest.user?.email }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Start Date</p>
                                <p class="text-base text-gray-800">{{ formatDate(viewingRequest.start_date) }}</p>
                            </div>
                             <div>
                                <p class="text-sm font-medium text-gray-600">End Date</p>
                                <p class="text-base text-gray-800">{{ formatDate(viewingRequest.end_date) }}</p>
                            </div>
                             <div>
                                <p class="text-sm font-medium text-gray-600">Leave Type</p>
                                <p class="text-base text-gray-800 capitalize">{{ viewingRequest.leave_type }}</p>
                            </div>
                             <div>
                                <p class="text-sm font-medium text-gray-600">Duration</p>
                                <p class="text-base text-gray-800">{{ formatLeaveDays(viewingRequest.leave_days) }} day(s)</p>
                            </div>
                        </div>
                         <div v-if="viewingRequest.day_type === 'half'">
                            <p class="text-sm font-medium text-gray-600">Session Details</p>
                            <p class="text-base text-gray-800 capitalize">{{ viewingRequest.start_half_session }} to {{ viewingRequest.end_half_session }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Reason</p>
                            <p class="text-base text-gray-800 bg-gray-50 p-3 rounded-md">{{ viewingRequest.reason }}</p>
                        </div>
                         <div>
                            <p class="text-sm font-medium text-gray-600">Status</p>
                             <span :class="statusConfig[viewingRequest.status].class" class="px-3 py-1 rounded-full text-sm font-medium inline-flex items-center">
                                <span class="mr-1.5">{{ statusConfig[viewingRequest.status].icon }}</span>
                                <span class="capitalize">{{ viewingRequest.status }}</span>
                            </span>
                        </div>
                        <!-- NEW: Show document if exists -->
                        <div v-if="viewingRequest.document_path">
                            <p class="text-sm font-medium text-gray-600">Attached Document</p>
                            <a :href="`/storage/${viewingRequest.document_path}`" target="_blank" class="text-blue-600 hover:text-blue-800 underline flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                View Document
                            </a>
                        </div>
                    </div>
                     <!-- Modal Footer -->
                    <div class="p-4 bg-gray-50 text-right rounded-b-xl">
                        <button @click="closeLeaveDetailModal" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 font-semibold text-sm">Close</button>
                    </div>
                </div>
            </div>

            <!-- Final Leave Applied Successfully Modal -->
            <div v-if="isSuccessModalVisible && lastSuccessfulRequest" @click.self="closeSuccessModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95" :class="{ 'scale-100': isSuccessModalVisible }">
                    <!-- Modal Header -->
                    <div class="p-5 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                        <h3 class="text-lg font-semibold text-gray-800">Leave Applied Successfully</h3>
                        <button @click="closeSuccessModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">×</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 text-center space-y-6">
                        <!-- Success Icon & Confetti -->
                        <div class="w-32 h-32 mx-auto">
                            <svg viewBox="0 0 100 100" class="w-full h-full">
                                <!-- Confetti using a single blue color for consistency -->
                                <!-- Stars (4-pointed) -->
                                <path d="M12 18 L14 23 L19 25 L14 27 L12 32 L10 27 L5 25 L10 23 Z" fill="#3B82F6"/>
                                <path d="M88 65 L90 70 L95 72 L90 74 L88 79 L86 74 L81 72 L86 70 Z" fill="#3B82F6" opacity="0.8"/>
                                <!-- Squares (rotated) -->
                                <!-- <rect x="85" y="15" width="7" height="7" rx="1.5" transform="rotate(25 88.5 18.5)" fill="#3B82F6"/>
                                <rect x="10" y="75" width="9" height="9" rx="2" transform="rotate(-10 14.5 79.5)" fill="#3B82F6"/> -->
                                <!-- Circles (dots) -->
                                <!-- <circle cx="92" cy="40" r="3.5" fill="#3B82F6"/>
                                <circle cx="50" cy="5" r="3" fill="#3B82F6" opacity="0.9"/>
                                <circle cx="20" cy="90" r="3" fill="#3B82F6" opacity="0.6"/> -->
                                <!-- Arcs and Lines -->
                                <!-- <path d="M8 50 A 20 20 0 0 1 18 60" stroke="#3B82F6" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                                <path d="M80 92 A 15 15 0 0 0 90 84" stroke="#3B82F6" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                                <line x1="70" y1="8" x2="78" y2="16" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/> -->

                                <!-- Main Central Circle -->
                                <circle cx="50" cy="50" r="35" fill="#3B82F6"/>

                                <!-- White Checkmark -->
                                <path d="M36 50 L45 59 L64 40" stroke="white" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <!-- Leave Details -->
                        <div class="space-y-4 text-left">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Leave Dates</p>
                                    <div class="bg-gray-100 p-3 rounded-lg flex items-center text-sm">
                                       <p class="font-semibold text-gray-800">
                                         {{ formatDate(lastSuccessfulRequest.start_date) }}
                                         <span v-if="lastSuccessfulRequest.start_date !== lastSuccessfulRequest.end_date"> - {{ formatDate(lastSuccessfulRequest.end_date) }}</span>
                                       </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Duration</p>
                                    <div class="bg-gray-100 p-3 rounded-lg flex items-center text-sm">
                                       <p class="font-semibold text-gray-800">{{ lastSuccessfulRequest.days }} Day{{ lastSuccessfulRequest.days !== 1 && lastSuccessfulRequest.days !== '0.5' ? 's' : '' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 mb-1">Reason Submitted</p>
                                <p class="bg-gray-100 p-3 rounded-lg text-sm text-gray-700 break-words max-h-24 overflow-y-auto">{{ lastSuccessfulRequest.reason }}</p>
                            </div>
                            <!-- NEW: Show document upload status -->
                            <div v-if="form.document">
                                <p class="text-xs font-semibold text-gray-500 mb-1">Document</p>
                                <p class="bg-gray-100 p-3 rounded-lg text-sm text-gray-700">
                                    {{ form.document.name }} (Uploaded)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 bg-gray-50 text-center rounded-b-xl">
                        <button @click="closeSuccessModal" class="w-full bg-slate-800 text-white px-5 py-2.5 rounded-lg hover:bg-slate-900 font-semibold text-sm shadow-sm transition-colors">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* --- Animation for Tab Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

/* --- Animation for Tab Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

/* --- Animation for Tab Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

/* --- Animation for Slide-in Panels --- */
@keyframes slide-in {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
.animate-slide-in {
    animation: slide-in 0.35s ease-out forwards;
}

/* --- Animation for Tab and Modal Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

/* --- NEW: Animation for Holiday Slide-in Panel --- */
@keyframes slide-in {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
.animate-slide-in {
    animation: slide-in 0.35s ease-out forwards;
}

/* --- Animation for Tab Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

/* --- Animation for Tab Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

/* --- Animation for Tab Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

.fc {
  @apply text-gray-800;
}

/* --- Header: Title and Navigation --- */
.fc .fc-toolbar.fc-header-toolbar {
  @apply mb-5 p-1 flex-col sm:flex-row; /* Add vertical stacking on small screens */
}

.fc .fc-toolbar-title {
  @apply text-xl font-bold text-gray-900 tracking-tight; /* Larger, bolder title */
}

/* Prev/Next navigation buttons */
.fc .fc-button {
  @apply bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-blue-300 focus:outline-none shadow-sm transition-all duration-200 rounded-lg;
}
.fc .fc-button .fc-icon {
    font-size: 1.4em; /* Make icons slightly larger */
}
/* Ensure consistent button size for prev/next */
.fc .fc-prev-button, .fc .fc-next-button {
    @apply w-10 h-10 flex items-center justify-center p-0;
}


/* Day of Week Headers (Mon, Tue, etc.) */
.fc .fc-col-header-cell-cushion {
  @apply text-gray-500 text-xs font-bold uppercase tracking-wider pb-3; /* Cleaner, bolder headers */
}
/* Remove the heavy border under day names */
.fc .fc-col-header {
    @apply border-b-0;
}
.fc-theme-standard .fc-scrollgrid {
    @apply border-gray-200; /* Lighter border for the main grid */
}

/* --- Individual Day Cells --- */

/* Container for each day in the grid */
.fc .fc-daygrid-day {
    @apply p-1; /* Add a little space around each day frame */
}

/* The frame inside the day cell */
.fc .fc-daygrid-day-frame {
  @apply rounded-lg transition-all duration-200 ease-in-out relative;
  min-height: 5em; /* A bit more height for better spacing */
}

/* Gentle pop-out hover effect on each day */
.fc .fc-daygrid-day:hover .fc-daygrid-day-frame {
    @apply bg-gray-50 shadow-lg transform scale-105 z-10;
}

/* The day number (e.g., 1, 2, 3) */
.fc .fc-daygrid-day-number {
  /* Position day number top-right for a more modern feel */
  @apply absolute top-1.5 right-2 h-7 w-7 flex items-center justify-center text-sm rounded-full transition-all duration-200;
}
.fc .fc-daygrid-day:hover .fc-daygrid-day-number {
    @apply font-bold;
}

/* --- Special Date Styling --- */

/* Styling for Today's Date */
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-frame {
  @apply bg-blue-50; /* A very light blue background for the whole cell */
}
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    @apply bg-blue-600 text-white font-bold; /* A distinct, solid circle */
}

/* Make dates in the past appear faded */
.fc .fc-day-past {
    @apply bg-gray-50;
}
.fc .fc-day-past .fc-daygrid-day-number {
    @apply text-gray-400;
}

/* --- Event & Selection Styling --- */

/* Background for user-selected dates */
.fc-bg-event {
    opacity: 1 !important; /* Override default opacity to use RGBA from JS */
    border-radius: 6px; /* Softly rounded corners for the selection highlight */
}

/* --- Animation for Tab Content --- */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}
/* Calendar Date Circles */
.fc .fc-daygrid-day-number {
    @apply h-8 w-8 flex items-center justify-center rounded-full text-sm;
}

/* Today's Date Circle */
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    @apply bg-blue-600 text-white font-bold;
}

/* Selected Date Circle */
.fc .fc-daygrid-day.fc-day-selected .fc-daygrid-day-number {
    @apply bg-blue-100 text-blue-800 font-bold;
}

/* Hover Effect on Dates */
.fc .fc-daygrid-day:hover .fc-daygrid-day-number {
    @apply bg-gray-100;
}

/* Past Dates */
.fc .fc-day-past .fc-daygrid-day-number {
    @apply text-gray-400;
}
</style>