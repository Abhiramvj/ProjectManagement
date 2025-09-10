<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ companyInfo.name }}</h1>
                    <p class="text-lg text-blue-600 font-medium">{{ companyInfo.tagline }}</p>
                </div>
                <!-- <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Total Employees</p>
                        <p class="text-2xl font-bold text-blue-600">{{ totalEmployees }}</p>
                    </div>
                    <button
                        @click="activeMainTab = activeMainTab === 'overview' ? 'hierarchy' : 'overview'"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        {{ activeMainTab === 'overview' ? 'View Hierarchy' : 'View Overview' }}
                    </button>
                </div> -->
            </div>
        </template>

        <div class="p-6">
            <!-- Main Tab Navigation -->
            <div class="mb-6">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <button
                        @click="activeMainTab = 'overview'"
                        :class="[
                            activeMainTab === 'overview'
                                ? 'border-indigo-500 text-indigo-600 bg-indigo-50'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        ]"
                        class="whitespace-nowrap border-b-2 py-2 px-6 text-sm font-medium rounded-t-lg transition-all"
                    >
                        Company Overview
                    </button>
                    <button
                        @click="activeMainTab = 'hierarchy'"
                        :class="[
                            activeMainTab === 'hierarchy'
                                ? 'border-indigo-500 text-indigo-600 bg-indigo-50'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        ]"
                        class="whitespace-nowrap border-b-2 py-2 px-6 text-sm font-medium rounded-t-lg transition-all"
                    >
                        Organization Structure
                    </button>
                </nav>
            </div>

            <!-- Company Overview Section -->
            <div v-show="activeMainTab === 'overview'" class="space-y-8">
                <!-- Hero Section with Mission & Vision -->
                <section class="bg-gradient-to-r from-blue-600 to-purple-700 text-white p-8 rounded-2xl shadow-xl">
                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <h2 class="text-2xl font-bold mb-4">Our Mission</h2>
                            <p class="text-blue-100 leading-relaxed">{{ companyInfo.mission }}</p>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold mb-4">Our Vision</h2>
                            <p class="text-blue-100 leading-relaxed">{{ companyInfo.vision }}</p>
                        </div>
                    </div>
                    <div class="mt-6">
                        <p class="text-blue-100 leading-relaxed">{{ companyInfo.description }}</p>
                    </div>
                </section>

                <!-- Quick Stats -->
                <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Employees</p>
                                <p class="text-2xl font-bold text-gray-900">{{ totalEmployees }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Active Projects</p>
                                <p class="text-2xl font-bold text-gray-900">{{ projects.length }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-purple-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Teams</p>
                                <p class="text-2xl font-bold text-gray-900">{{ teams.length }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-orange-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Services</p>
                                <p class="text-2xl font-bold text-gray-900">{{ Object.keys(companyInfo.services).length }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Core Values -->
                <section class="bg-white p-8 rounded-2xl shadow-lg">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Our Core Values</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                        <div v-for="(description, value) in companyInfo.core_values" :key="value" 
                             class="text-center p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 hover:shadow-lg transition-shadow">
                            <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-2xl">{{ getValueIcon(value) }}</span>
                            </div>
                            <h3 class="font-bold text-gray-800 mb-2">{{ value }}</h3>
                            <p class="text-sm text-gray-600 leading-tight">{{ description }}</p>
                        </div>
                    </div>
                </section>

                <!-- Services -->
                <section class="bg-white p-8 rounded-2xl shadow-lg">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8">Our Services</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div v-for="(description, service) in companyInfo.services" :key="service"
                             class="p-6 rounded-xl bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 hover:shadow-lg transition-all">
                            <div class="flex items-start">
                                <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-xl text-gray-800 mb-2">{{ service }}</h3>
                                    <p class="text-gray-600 leading-relaxed">{{ description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Active Projects -->
                <section class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-800">Active Projects</h2>
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            {{ projects.length }} Active
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="project in projects" :key="project.id"
                             class="group p-6 border border-gray-200 rounded-xl hover:shadow-xl hover:border-blue-300 cursor-pointer transition-all"
                             @click="toggleProjectDescription(project.id)">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="font-bold text-xl text-gray-800 group-hover:text-blue-600 transition-colors">
                                    {{ project.name }}
                                </h3>
                                <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="flex items-center text-sm text-gray-600 mb-4">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span class="font-medium">Team: {{ project.team?.name ?? 'Unassigned' }}</span>
                            </div>

                            <div v-if="project.team?.team_lead" class="flex items-center text-sm text-gray-600 mb-4">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Lead: {{ project.team.team_lead.name }}</span>
                            </div>

                            <div v-if="expandedProjectId === project.id" class="mt-4 p-4 bg-gray-50 border rounded-lg">
                                <p class="text-gray-700 mb-4">{{ project.description ?? 'No description provided.' }}</p>

                                <div v-if="isAdminOrHR" class="mt-4">
                                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                            @click.stop="openEditModal(project)">
                                        Edit Description
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Teams Overview -->
                <section v-if="teams.length" class="bg-white p-8 rounded-2xl shadow-lg">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8">Teams & Leadership</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="team in teams" :key="team.id"
                             class="p-6 border border-gray-200 rounded-xl hover:shadow-lg transition-shadow bg-gradient-to-br from-gray-50 to-blue-50">
                            <div class="flex items-center mb-4">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <h3 class="font-bold text-xl text-gray-800">{{ team.name }}</h3>
                            </div>
                            
                            <div class="space-y-2">
                                <p class="text-gray-600">
                                    <span class="font-medium">Team Lead:</span> 
                                    {{ team.team_lead?.name ?? 'Not Assigned' }}
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-medium">Members:</span> 
                                    {{ team.members?.length ?? 0 }} people
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Company Policies Section -->
                <section class="bg-white p-8 rounded-2xl shadow-lg">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8">Company Policies & Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <!-- Work Hours -->
                        <div class="p-6 border border-gray-200 rounded-xl hover:shadow-lg transition-shadow cursor-pointer"
                             @click="openWorkHoursModal">
                            <div class="flex items-center mb-4">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800">Work Hours</h3>
                                    <p class="text-blue-600 hover:underline">View Schedule Options</p>
                                </div>
                            </div>
                        </div>

                        <!-- Code of Conduct -->
                        <div class="p-6 border border-gray-200 rounded-xl hover:shadow-lg transition-shadow cursor-pointer"
                             @click="openCodeOfConductModal">
                            <div class="flex items-center mb-4">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800">Code of Conduct</h3>
                                    <p class="text-green-600 hover:underline">View Guidelines</p>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Policy -->
                        <div class="p-6 border border-gray-200 rounded-xl hover:shadow-lg transition-shadow cursor-pointer"
                             @click="openLeavePolicyModal">
                            <div class="flex items-center mb-4">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h8m-8 0H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-2"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800">Leave Policy</h3>
                                    <p class="text-purple-600 hover:underline">View Leave Types</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Policies Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="(description, policy) in companyInfo.policies" :key="policy"
                             class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                            <h4 class="font-semibold text-gray-800 mb-2">{{ policy }}</h4>
                            <p class="text-gray-600 text-sm">{{ description }}</p>
                        </div>
                    </div>

                    <div v-if="isAdminOrHR" class="mt-8 text-center">
                        <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                @click="openCompanyInfoModal">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Company Information
                        </button>
                    </div>
                </section>

                <!-- Employee Benefits -->
                <section class="bg-gradient-to-r from-green-50 to-blue-50 p-8 rounded-2xl border border-green-200">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8">Employee Benefits</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(description, benefit) in companyInfo.benefits" :key="benefit"
                             class="p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex items-start">
                                <div class="p-2 rounded-lg bg-green-100 text-green-600 mr-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 mb-2">{{ benefit }}</h3>
                                    <p class="text-gray-600 text-sm leading-relaxed">{{ description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact Information -->
                <!-- <section class="bg-white p-8 rounded-2xl shadow-lg">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8">Contact Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">Get in Touch</h3>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"/>
                                    </svg>
                                    <a :href="companyInfo.contact_info.website" target="_blank" class="text-blue-600 hover:underline">
                                        {{ companyInfo.contact_info.website }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">Office Locations</h3>
                            <div class="space-y-3">
                                <div v-for="(description, location) in companyInfo.office_locations" :key="location"
                                     class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                                    <h4 class="font-semibold text-gray-800">{{ location }}</h4>
                                    <p class="text-sm text-gray-600">{{ description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section> -->
            </div>

            <!-- Company Hierarchy Section -->
            <div v-show="activeMainTab === 'hierarchy'" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <!-- Hierarchy Controls -->
                    <div class="border-b border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-bold text-gray-800">Organization Structure</h2>
                            <button
                                v-if="!isHierarchyFullscreen"
                                @click="toggleHierarchyFullscreen"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                            >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                Enter Fullscreen
                            </button>
                        </div>
                        
                        <!-- Hierarchy Tabs -->
                        <nav class="flex space-x-8" aria-label="Hierarchy Tabs">
                            <button
                                @click="activeHierarchyTab = 'reporting'"
                                :class="[
                                    activeHierarchyTab === 'reporting'
                                        ? 'border-indigo-500 text-indigo-600 bg-indigo-50'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                ]"
                                class="whitespace-nowrap border-b-2 py-2 px-4 text-sm font-medium rounded-t-lg transition-all"
                            >
                                Reporting Structure
                            </button>
                            <button
                                @click="activeHierarchyTab = 'designation'"
                                :class="[
                                    activeHierarchyTab === 'designation'
                                        ? 'border-indigo-500 text-indigo-600 bg-indigo-50'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                ]"
                                class="whitespace-nowrap border-b-2 py-2 px-4 text-sm font-medium rounded-t-lg transition-all"
                            >
                                Designation Structure
                            </button>
                        </nav>
                    </div>

                    <!-- Hierarchy Content -->
                    <div class="p-6">
                        <div v-show="activeHierarchyTab === 'reporting'">
                            <div v-if="reportingNodes && reportingNodes.length > 0">
                                <BalkanOrgChart :nodes="reportingNodes" :config="chartConfig" />
                            </div>
                            <div v-else class="py-20 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-lg">No reporting structure to display</p>
                                <p class="text-sm mt-2">The organizational chart will appear here once reporting relationships are established.</p>
                            </div>
                        </div>

                        <div v-show="activeHierarchyTab === 'designation'">
                            <div v-if="designationBasedNodes && designationBasedNodes.length > 0">
                                <BalkanOrgChart :nodes="designationBasedNodes" :config="chartConfig" />
                            </div>
                            <div v-else class="py-20 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <p class="text-lg">No designation hierarchy to display</p>
                                <p class="text-sm mt-2">The designation-based structure will appear here once employee roles are defined.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fullscreen Hierarchy Mode -->
            <div v-if="isHierarchyFullscreen" class="fixed inset-0 z-50 bg-gradient-to-br from-blue-100 to-blue-300 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Organization Structure</h2>
                    <button
                        @click="toggleHierarchyFullscreen"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Exit Fullscreen
                    </button>
                </div>

                <!-- Fullscreen Hierarchy Tabs -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="flex space-x-8" aria-label="Hierarchy Tabs">
                        <button
                            @click="activeHierarchyTab = 'reporting'"
                            :class="[
                                activeHierarchyTab === 'reporting'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            ]"
                            class="whitespace-nowrap border-b-2 py-2 px-4 text-sm font-medium transition-colors"
                        >
                            Reporting Structure
                        </button>
                        <button
                            @click="activeHierarchyTab = 'designation'"
                            :class="[
                                activeHierarchyTab === 'designation'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            ]"
                            class="whitespace-nowrap border-b-2 py-2 px-4 text-sm font-medium transition-colors"
                        >
                            Organization Chart
                        </button>
                    </nav>
                </div>

                <!-- Fullscreen Hierarchy Content -->
                <div class="bg-white rounded-lg shadow-lg p-6 h-full overflow-hidden">
                    <div v-show="activeHierarchyTab === 'reporting'">
                        <div v-if="reportingNodes && reportingNodes.length > 0">
                            <BalkanOrgChart :nodes="reportingNodes" :config="chartConfig" />
                        </div>
                        <div v-else class="py-20 text-center text-gray-500">
                            <p class="text-lg">No reporting structure to display</p>
                        </div>
                    </div>

                    <div v-show="activeHierarchyTab === 'designation'">
                        <div v-if="designationBasedNodes && designationBasedNodes.length > 0">
                            <BalkanOrgChart :nodes="designationBasedNodes" :config="chartConfig" />
                        </div>
                        <div v-else class="py-20 text-center text-gray-500">
                            <p class="text-lg">No designation hierarchy to display</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Modals from Company Overview -->
            <!-- Company Info Modal -->
            <div v-if="isCompanyInfoModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Edit Company Information</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block mb-2 font-semibold text-gray-700">Work Hours (one per line)</label>
                            <textarea v-model="modalWorkHours" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block mb-2 font-semibold text-gray-700">Code of Conduct</label>
                            <textarea v-model="modalCodeOfConduct" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block mb-2 font-semibold text-gray-700">Additional Policies (format: key|description per line)</label>
                            <textarea v-model="modalPolicies" rows="6" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 mt-8">
                        <button class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                                @click="closeCompanyInfoModal">
                            Cancel
                        </button>
                        <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                @click="saveCompanyInfo">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Project Description Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Edit Project Description</h2>
                    <textarea v-model="modalEditingDescription" rows="6" 
                              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>

                    <div class="flex justify-end space-x-4 mt-6">
                        <button class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                                @click="closeModal">
                            Cancel
                        </button>
                        <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                @click="saveDescription(modalProject.id)">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Work Hours Modal -->
            <div v-if="isWorkHoursModalVisible" @click.self="closeWorkHoursModal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg w-full">
                    <div class="flex items-center mb-6">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Work Hours Schedule</h3>
                    </div>
                    <div class="space-y-3">
                        <div v-for="(shift, index) in workHoursArray" :key="index" 
                             class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-gray-700 font-medium">{{ shift }}</p>
                        </div>
                    </div>
                    <div class="mt-8 text-right">
                        <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" 
                                @click="closeWorkHoursModal">Close</button>
                    </div>
                </div>
            </div>

            <!-- Code of Conduct Modal -->
            <div v-if="isCodeOfConductModalVisible" @click.self="closeCodeOfConductModal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full">
                    <div class="flex items-center mb-6">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Code of Conduct</h3>
                    </div>
                    <div class="p-6 bg-green-50 rounded-lg border border-green-200">
                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ companyInfo.code_of_conduct }}</p>
                    </div>
                    <div class="mt-8 text-right">
                        <button class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" 
                                @click="closeCodeOfConductModal">Close</button>
                    </div>
                </div>
            </div>

            <!-- Leave Policy Modal -->
            <div v-if="isLeavePolicyModalVisible" @click.self="closeLeavePolicyModal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="flex w-full max-w-4xl flex-col rounded-2xl bg-white shadow-2xl" style="height: 90vh; max-height: 800px">
                    <header class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 p-6">
                        <div class="flex items-center gap-4">
                            <div class="rounded-full bg-purple-100 p-3">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h8m-8 0H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-2"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">Leave Policy Guide</h3>
                        </div>
                        <button @click="closeLeavePolicyModal" 
                                class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </header>
                    <div class="flex-grow space-y-6 overflow-y-auto p-6">
                        <div v-for="(policy, type) in leaveTypeDescriptions" :key="type" 
                             class="flex gap-4 rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="text-4xl flex-shrink-0">{{ leaveTypeIcons[type] || leaveTypeIcons.default }}</div>
                            <div class="flex-grow">
                                <h4 class="mb-2 text-lg font-bold text-gray-800">{{ policy.title }}</h4>
                                <p class="mb-4 text-gray-600">{{ policy.summary }}</p>
                                <ul class="list-disc space-y-2 pl-5 text-sm text-gray-600">
                                    <li v-for="(detail, index) in policy.details" :key="index">{{ detail }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <footer class="flex flex-shrink-0 justify-end border-t border-gray-200 bg-gray-50 p-6">
                        <button @click="closeLeavePolicyModal" 
                                class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            Close
                        </button>
                    </footer>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BalkanOrgChart from '@/Components/BalkanOrgChart.vue';

const props = defineProps({
    companyInfo: Object,
    projects: Array,
    teams: Array,
    roleSummary: Array,
    totalEmployees: Number,
    userRole: String,
    reportingNodes: Array,
    designationBasedNodes: Array,
});

// Main navigation state
const activeMainTab = ref('overview');

// Company overview state
const isAdminOrHR = computed(() => ['admin', 'hr'].includes(props.userRole));
const expandedProjectId = ref(null);
const isModalOpen = ref(false);
const modalEditingDescription = ref('');
const modalProject = ref(null);

const isCompanyInfoModalOpen = ref(false);
const modalWorkHours = ref('');
const modalCodeOfConduct = ref('');
const modalPolicies = ref('');

const isLeavePolicyModalVisible = ref(false);
const isWorkHoursModalVisible = ref(false);
const isCodeOfConductModalVisible = ref(false);

// Hierarchy state
const activeHierarchyTab = ref('reporting');
const isHierarchyFullscreen = ref(false);

// Chart configuration for hierarchy
const chartConfig = {
    searchInput: {
        text: 'Search',
    },
};

const teams = computed(() => Array.isArray(props.teams) ? props.teams : []);

// Computed array of work hours
const workHoursArray = computed(() => {
    if (Array.isArray(props.companyInfo.work_hours)) {
        return props.companyInfo.work_hours;
    }
    if (typeof props.companyInfo.work_hours === 'string') {
        return props.companyInfo.work_hours.split('\n').filter(line => line.trim());
    }
    return [];
});

const leaveTypeDescriptions = {
    annual: { 
        title: 'Annual Leave', 
        summary: 'Planned time off with advance notice', 
        details: [
            'For vacations, personal time, or other planned absences',
            'Should be requested at least 7 days in advance',
            'Requires manager approval',
            'Balance accrued based on tenure (typically 15-25 days/year)'
        ]
    },
    personal: { 
        title: 'Personal Leave', 
        summary: 'Leave for personal matters that require time off', 
        details: [
            'Used for personal errands, family commitments, or important events',
            'Typically requires 3 days advance notice',
            'Needs manager approval',
            'May be limited in total number of days per year'
        ]
    },
    sick: { 
        title: 'Sick Leave', 
        summary: 'Leave granted for health-related issues', 
        details: [
            'For medical appointments, illness, or recovery',
            'Usually requires a medical certificate for extended absences',
            'Accrued based on company policy and local law',
            'May be paid or unpaid as per policy'
        ]
    },
    emergency: { 
        title: 'Emergency Leave', 
        summary: 'Leave for urgent, unforeseen personal emergencies', 
        details: [
            'For unexpected situations like medical emergencies, accidents, or urgent family matters',
            'Typically granted on short notice with flexible approval process',
            'Usually paid leave, separate from annual or personal leave balances',
            'Helps employees manage critical situations without penalty'
        ]
    },
    maternity: { 
        title: 'Maternity Leave', 
        summary: 'Leave for childbirth and related recovery', 
        details: [
            'Granted to employees during pregnancy and after birth',
            'Duration varies by jurisdiction (typically 12-26 weeks)',
            'May require medical documentation',
            'Protected under employment law'
        ]
    },
    paternity: { 
        title: 'Paternity Leave', 
        summary: 'Leave for fathers around the time of childbirth', 
        details: [
            'Allows bonding with the newborn and support for the family',
            'Usually shorter duration than maternity leave (e.g., 1-2 weeks)',
            'Requires notice as per company policy',
            'Protected under employment law'
        ]
    },
    wfh: { 
        title: 'Work From Home', 
        summary: 'Remote work arrangements without reducing leave balance', 
        details: [
            'Allows employees to work remotely for full or partial days',
            'Usually does not deduct from leave balance',
            'Requires manager\'s approval',
            'Supports flexible work-life balance'
        ]
    },
    compensatory: { 
        title: 'Compensatory Leave', 
        summary: 'Leave earned by working extra hours or on holidays', 
        details: [
            'Credited when working during official holidays',
            'Used as paid time off in lieu of extra hours worked',
            'Requires manager approval to grant and to use',
            'Usually tracked separately to enforce usage policies'
        ]
    }
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

// Helper function for core values icons
function getValueIcon(value) {
    const icons = {
        'Innovation': '💡',
        'Excellence': '⭐',
        'Collaboration': '🤝',
        'Integrity': '🛡️',
        'Client-Centric': '🎯'
    };
    return icons[value] || '✨';
}

// Hierarchy Functions
function toggleHierarchyFullscreen() {
    isHierarchyFullscreen.value = !isHierarchyFullscreen.value;
}

// Company Info Modal Functions
function openCompanyInfoModal() {
    modalWorkHours.value = Array.isArray(props.companyInfo.work_hours)
        ? props.companyInfo.work_hours.join('\n')
        : props.companyInfo.work_hours;
    modalCodeOfConduct.value = props.companyInfo.code_of_conduct;
    modalPolicies.value = Object.entries(props.companyInfo.policies)
        .map(([key, desc]) => `${key}|${desc}`)
        .join('\n');
    isCompanyInfoModalOpen.value = true;
}

function closeCompanyInfoModal() {
    isCompanyInfoModalOpen.value = false;
}

function saveCompanyInfo() {
    props.companyInfo.work_hours = modalWorkHours.value.split('\n').filter(l => l.trim());
    props.companyInfo.code_of_conduct = modalCodeOfConduct.value;
    props.companyInfo.policies = {};
    modalPolicies.value.split('\n').forEach(line => {
        const [key, desc] = line.split('|');
        if (key && desc) props.companyInfo.policies[key.trim()] = desc.trim();
    });
    isCompanyInfoModalOpen.value = false;
    alert('Company information updated successfully!');
}

// Project Modal Functions
function toggleProjectDescription(projectId) {
    expandedProjectId.value = expandedProjectId.value === projectId ? null : projectId;
}

function openEditModal(project) {
    modalProject.value = project;
    modalEditingDescription.value = project.description ?? '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    modalProject.value = null;
    modalEditingDescription.value = '';
}

function saveDescription(projectId) {
    const project = props.projects.find(p => p.id === projectId);
    if (project) {
        project.description = modalEditingDescription.value;
        isModalOpen.value = false;
        alert('Project description updated successfully!');
    }
}

// Modal Functions for Policy Viewing
function openWorkHoursModal() { 
    isWorkHoursModalVisible.value = true; 
}

function closeWorkHoursModal() { 
    isWorkHoursModalVisible.value = false; 
}

function openCodeOfConductModal() { 
    isCodeOfConductModalVisible.value = true; 
}

function closeCodeOfConductModal() { 
    isCodeOfConductModalVisible.value = false; 
}

function openLeavePolicyModal() { 
    isLeavePolicyModalVisible.value = true; 
}

function closeLeavePolicyModal() { 
    isLeavePolicyModalVisible.value = false; 
}
</script>

<style scoped>
textarea {
    resize: vertical;
}

.transition-all {
    transition: all 0.3s ease;
}

.transition-colors {
    transition: color 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
}

.transition-shadow {
    transition: box-shadow 0.3s ease;
}

.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.hover\:shadow-lg:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Hierarchy Chart Styles */
.boc-input > label {
    font-size: 0 !important;
    color: transparent;
}

.boc-input > label::after {
    content: 'Search';
    font-size: 16px;
    color: #a0aec0;
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}

.chart-container-glass {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #ffffff 0%, #62a5dc 100%);
    border-radius: 0.5rem;
}

.glass-node > rect {
    background-color: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    fill: rgba(255, 255, 255, 0.2);
    stroke-width: 1px;
    stroke: rgba(255, 255, 255, 0.3);
}
</style>