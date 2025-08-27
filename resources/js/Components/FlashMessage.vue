<!-- resources/js/Components/FlashMessage.vue -->
<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  flashMessage: Object
});

const page = usePage();
const show = ref(false);
const type = ref('success');
const message = ref('');

const successMessage = computed(() => page.props.flash?.success);
const errorMessage = computed(() => page.props.flash?.error);

router.on('finish', () => {
  // Clear flash message after each navigation to allow showing again on next request
  if (page.props.flash) {
    page.props.flash.success = null;
    page.props.flash.error = null;
  }
});

watch(successMessage, (newMessage) => {
    if (newMessage) {
        type.value = 'success';
        message.value = newMessage;
        show.value = true;
        setTimeout(() => show.value = false, 4000); // Hide after 4 seconds
    }
});

watch(errorMessage, (newMessage) => {
    if (newMessage) {
        type.value = 'error';
        message.value = newMessage;
        show.value = true;
        setTimeout(() => show.value = false, 4000);
    }
});

watch(() => props.flashMessage, (newFlash) => {
  if(newFlash?.message) {
    type.value = newFlash.type || 'success';
    message.value = newFlash.message;
    show.value = true;
    setTimeout(() => show.value = false, 4000);
  }
});

const alertClasses = computed(() => ({
    'bg-emerald-50 border-emerald-500 text-emerald-800': type.value === 'success',
    'bg-red-50 border-red-500 text-red-800': type.value === 'error',
}));
</script>

<template>
    <transition
        enter-from-class="opacity-0 translate-y-8"
        enter-to-class="opacity-100 translate-y-0"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-8"
        class="transition-all duration-300 ease-in-out"
    >
        <div v-if="show" class="fixed top-5 right-5 z-50 max-w-sm">
            <div :class="alertClasses" class="rounded-lg border-l-4 p-4 shadow-lg" role="alert">
                <p class="font-bold">{{ type === 'success' ? 'Success' : 'Error' }}</p>
                <p>{{ message }}</p>
            </div>
        </div>
    </transition>
</template>