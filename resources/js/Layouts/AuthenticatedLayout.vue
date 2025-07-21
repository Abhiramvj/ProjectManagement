<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="min-h-screen flex bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md hidden md:block">
      <div class="h-16 flex items-center justify-center border-b">
        <Link :href="route('dashboard')">
          <ApplicationLogo class="h-8 w-auto text-gray-800" />
        </Link>
      </div>

      <nav class="mt-4 px-4 space-y-1">
        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage roles')" :href="route('roles.index')" :active="route().current('roles.index')">
          Manage Roles
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('assign projects')" :href="route('projects.create')" :active="route().current('projects.create')">
          New Project
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('apply for leave')" :href="route('leave.index')" :active="route().current('leave.index')">
          Apply for Leave
        </NavLink>

        <NavLink :href="route('hours.index')" :active="route().current('hours.index')">Working Hours</NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage employees')" :href="route('users.index')" :active="route().current('users.index')">
          Manage Employees
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage employees')" :href="route('teams.index')" :active="route().current('teams.index')">
          Manage Teams
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage employees')" :href="route('company.hierarchy')" :active="route().current('company.hierarchy')">
          Company Hierarchy
        </NavLink>
      </nav>
    </aside>

    <!-- Main Section -->
    <div class="flex-1 flex flex-col">
      <!-- Top Navbar with Page Heading on left, Profile on right -->
      <header class="bg-white shadow h-16 flex items-center justify-between px-6">
        <div class="text-lg font-semibold text-gray-700">
          <slot name="header" />
        </div>

        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:text-gray-900 focus:outline-none">
                {{ $page.props.auth.user.name }}
                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l5 5 5-5" />
                </svg>
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header \

      <!-- Page Content -->
      <main class="flex-1 p-4">
        <slot />
      </main>
    </div>
  </div>
</template>