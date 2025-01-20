<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Left side - Logo and Dashboard -->
                    <div class="flex items-center space-x-8">
                        <div class="shrink-0 flex items-center pl-4">
                            <a href="/" class="font-bold text-xl">
                                Curacel Document Agent
                            </a>
                        </div>
                        
                        <!-- Dashboard text -->
                        <div class="hidden space-x-8 sm:-my-px sm:flex">
                            <a 
                                href="/dashboard"
                                class="inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out"
                            >
                                Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Right side - User Profile -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button 
                                id="userDropdownButton"
                                @click="toggleDropdown"
                                class="flex items-center space-x-3 focus:outline-none hover:opacity-80"
                            >
                                <span class="text-gray-700">{{ workspace?.current_user?.name }}</span>
                                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-medium">
                                    {{ workspace?.current_user?.name?.charAt(0).toUpperCase() }}
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <div 
                                id="userDropdown"
                                v-show="showDropdown"
                                class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                            >
                                <div class="py-1">
                                    <form @submit.prevent="logout" method="POST" action="/logout">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token">
                                        <button 
                                            type="submit"
                                            class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center group"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 group-hover:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Sign Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex flex-1">
            <!-- Sidebar -->
            <Sidebar 
                v-if="$page.url.startsWith('/dashboard')"
                :workspace="workspace"
                @memberSelected="(member) => $emit('memberSelected', member)"
            />

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Page Heading -->
                <header v-if="$slots.header" class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    <slot />
                </main>
            </div>
        </div>

        <!-- File Activity Notifications -->
        <FileActivityNotification />

        <!-- Access Request Notifications -->
        <AccessRequestNotification />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'
// import AccessRequestNotification from '@/Components/AccessRequestNotification.vue'
import FileActivityNotification from '@/Components/FileActivityNotification.vue'

const showDropdown = ref(false)

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value
}

// Close dropdown when clicking outside
const closeDropdown = (e) => {
    const dropdown = document.getElementById('userDropdown')
    const button = document.getElementById('userDropdownButton')
    if (showDropdown.value && dropdown && !dropdown.contains(e.target) && !button.contains(e.target)) {
        showDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown)
})

const logout = async () => {
    try {
        const response = await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie
                    .split('; ')
                    .find(row => row.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1] || '')
            },
            credentials: 'include'
        })
        
        if (response.ok) {
            window.location.href = '/'
        } else {
            throw new Error('Logout failed')
        }
    } catch (error) {
        console.error('Logout failed:', error)
    }
}

const props = defineProps({
    title: String,
    workspace: Object,
    user: {
        type: Object,
        required: true
    }
})

defineEmits(['memberSelected'])
</script>

<style scoped>
/* Add any custom styles here */
</style> 