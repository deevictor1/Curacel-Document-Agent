<template>
    <AppLayout 
        :title="title" 
        :workspace="workspace"
        :user="$page.props.auth.user"
        @memberSelected="handleMemberSelected"
    >
        <div class="min-h-screen bg-gray-50">
            <!-- Main Content Container -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Workspace Info and Search Section -->
                <div class="bg-white rounded-lg shadow mb-6 p-6">
                    <div class="flex justify-between items-start">
                        <!-- Workspace Info - Left Side -->
                        <div class="flex-shrink-0">
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">
                                {{ workspace?.name }}
                            </h2>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p>Domain: {{ workspace?.domain }}</p>
                                <!-- <p>Connected Users: {{ workspace?.connected_users_count || 0 }}</p> -->
                            </div>
                        </div>

                        <!-- Search Section - Right Side -->
                        <div class="flex-shrink-0 w-96">
                            <div class="flex items-center justify-between mb-2">
                                <label for="search" class="block text-sm font-medium text-gray-700">
                                    Search Files by Title
                                </label>
                                <span v-if="selectedMember" class="text-sm text-gray-500">
                                    Filtering by: {{ selectedMember.name }}
                                    <button 
                                        @click="clearMemberFilter" 
                                        class="ml-2 text-indigo-600 hover:text-indigo-900"
                                    >
                                        Clear
                                    </button>
                                </span>
                            </div>
                            <div class="relative rounded-md shadow-sm">
                                <input
                                    type="text"
                                    name="search"
                                    id="search"
                                    v-model="searchQuery"
                                    @input="debouncedSearch"
                                    @keyup.enter="performSearch"
                                    class="block w-full pr-10 sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Enter file name..."
                                >
                                <div class="absolute inset-y-0 right-0 pr-1 flex items-center">
                                    <button
                                        @click="performSearch"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Grid -->
                <div class="bg-white rounded-lg shadow min-h-[calc(100vh-13rem)]">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                            <span class="text-sm text-gray-500" v-if="searchResults.length">
                                Showing {{ searchResults.length }} results
                            </span>
                        </div>
                        
                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-12">
                            <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm text-indigo-600">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Searching...
                            </div>
                        </div>

                        <!-- Initial State - No Search Performed -->
                        <div v-else-if="!searched" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Start Your Search</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Search for documents using the search bar above or<br/>
                                click on a team member's name from the left menu to view their documents.
                            </p>
                        </div>

                        <!-- No Results State -->
                        <div v-else-if="searched && !searchResults.length" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No documents found</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Try adjusting your search or selecting a different team member.
                            </p>
                        </div>

                        <!-- Results Grid -->
                        <template v-else>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="file in searchResults" :key="file.id" 
                                    class="relative bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
                                    <!-- File Info -->
                                    <div class="flex items-start space-x-3">
                                        <FileTypeIcon :type="file.type" class="w-8 h-8" />
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <h3 class="text-sm font-medium text-gray-900 truncate">
                                                    {{ file.name }}
                                                </h3>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                <p>Owner: {{ file.creator.name }}</p>
                                                <p>Modified: {{ new Date(file.modifiedTime).toLocaleDateString() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons and Access Label -->
                                    <div class="mt-4 flex justify-between items-center">
                                        <!-- No Access Label -->
                                        <div v-if="!file.hasAccess" 
                                            class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">
                                            No Access
                                        </div>
                                        <div v-else class="invisible">
                                            <!-- Placeholder to maintain spacing -->
                                            No Access
                                        </div>
                                        
                                        <!-- Action Button -->
                                        <a v-if="file.hasAccess"
                                            :href="file.webViewLink"
                                            target="_blank"
                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            Open
                                        </a>
                                        <a v-else
                                            :href="file.webViewLink"
                                            target="_blank"
                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            Request Access
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Load More Button -->
                            <div v-if="hasMore" class="mt-6 text-center">
                                <button 
                                    @click="loadMore"
                                    :disabled="loadingMore"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg v-if="loadingMore" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ loadingMore ? 'Loading more...' : 'Load More Results' }}
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import FileTypeIcon from '@/Components/FileTypeIcon.vue'

const searchQuery = ref('')
const searchResults = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const searched = ref(false)
const selectedMember = ref(null)
const nextPageToken = ref(null)
const hasMore = ref(false)

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const performSearch = async (isLoadMore = false) => {
    // Don't search if query is empty and no member is selected
    if (!isLoadMore && !searchQuery.value.trim() && !selectedMember.value) {
        searchResults.value = []
        searched.value = false
        return
    }

    if (isLoadMore) {
        loadingMore.value = true
    } else {
        loading.value = true
        searchResults.value = [] // Clear results for new search
        nextPageToken.value = null // Reset pagination for new search
    }
    
    try {
        const params = new URLSearchParams()
        
        // Only add search parameters if they exist
        const trimmedQuery = searchQuery.value.trim()
        if (trimmedQuery) {
            params.append('q', trimmedQuery)
        }
        
        if (selectedMember.value) {
            params.append('owner', selectedMember.value.email)
        }
        
        if (isLoadMore && nextPageToken.value) {
            params.append('pageToken', nextPageToken.value)
        }
        
        console.log('Searching with params:', params.toString()) // Debug log
        
        const response = await fetch(`/api/documents/search?${params.toString()}`)
        if (!response.ok) throw new Error('Search failed')
        
        const data = await response.json()
        console.log('Search results:', data) // Debug log
        
        if (isLoadMore) {
            searchResults.value = [...searchResults.value, ...data.files]
        } else {
            searchResults.value = data.files
        }
        
        nextPageToken.value = data.nextPageToken
        hasMore.value = data.hasMore
        searched.value = true
    } catch (error) {
        console.error('Search failed:', error)
        if (!isLoadMore) {
            searchResults.value = []
        }
    } finally {
        loading.value = false
        loadingMore.value = false
    }
}

// Add debounced search for input
const debouncedSearch = debounce(() => {
    performSearch()
}, 500)

// Add debounce utility function
function debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

const loadMore = () => {
    performSearch(true)
}

const handleMemberSelected = (member) => {
    selectedMember.value = member
    searchQuery.value = '' // Clear search query when filtering by member
    performSearch()
}

const clearMemberFilter = () => {
    selectedMember.value = null
    performSearch()
}

defineProps({
    title: String,
    workspace: Object,
    user: Object
})
</script> 