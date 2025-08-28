<script setup>
import { computed } from 'vue';

const props = defineProps({
    node: { type: Object, required: true },
});

const cardClasses = computed(() => {
    const style = props.node.style || {};
    return props.node.type === 'department'
        ? [
              style.bgColor || 'bg-gray-800',
              style.textColor || 'text-white',
              'font-bold',
              'py-2',
              'px-6',
          ]
        : ['bg-white'];
});

const wrapperClasses = computed(() => {
    const style = props.node.style || {};
    return [
        'inline-block p-1 rounded-lg shadow-lg',
        style.borderColor || 'border-transparent',
        props.node.type === 'department' ? 'border-2' : 'border',
    ];
});
</script>

<template>
    <div :class="wrapperClasses">
        <div
            class="flex flex-col items-center rounded-md p-3"
            :class="cardClasses"
            style="width: 180px"
        >
            <template v-if="node.type === 'person'">
                <img
                    :src="node.image || '/storage/defaults/default-avatar.jpg'"
                    alt="Profile Photo"
                    class="-mt-10 mb-2 h-16 w-16 rounded-full border-4 border-white shadow-lg"
                />
                <div class="text-center">
                    <p class="font-semibold text-gray-900">{{ node.name }}</p>
                    <p class="text-xs capitalize text-gray-500">
                        {{ node.title }}
                    </p>
                </div>
            </template>
            <template v-else-if="node.type === 'department'">
                <span class="text-sm uppercase tracking-wider">{{
                    node.name
                }}</span>
            </template>
        </div>
    </div>
</template>
