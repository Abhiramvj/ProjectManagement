<template>
  <nav class="border-b bg-white p-4 flex space-x-6 max-w-7xl mx-auto">
    <button
      :class="buttonClass('hr.dashboard')"
      @click="navigate('hr.dashboard')"
      aria-current="page"
    >
      Dashboard
    </button>
    <button
      :class="buttonClass('hr.badges')"
      @click="navigate('hr.badges')"
    >
      Badges
    </button>
  </nav>
</template>

<script>
import { router } from '@inertiajs/vue3';

export default {
  methods: {
    navigate(routeName) {
      router.visit(route(routeName));
    },
    buttonClass(routeName) {
      return [
        "px-3 py-2 rounded-md font-medium",
        this.isActive(routeName)
          ? "text-blue-600 border-b-2 border-blue-600"
          : "text-gray-600 hover:text-blue-600 hover:border-blue-600",
      ];
    },
    isActive(routeName) {
      return this.$page.url.startsWith(route(routeName, [], false));
    },
  },
};
</script>

<style scoped>
button {
  background: none;
  border: none;
  cursor: pointer;
  outline: none;
}
</style>
