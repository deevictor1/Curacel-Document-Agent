<template>
    <div class="relative">
        <!-- Notification Bell -->
        <button @click="togglePanel" class="relative p-2">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <!-- Bell icon SVG -->
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="pendingRequests.length" 
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                {{ pendingRequests.length }}
            </span>
        </button>

        <!-- Requests Panel -->
        <div v-if="showPanel" 
            class="absolute right-0 mt-2 w-96 bg-white rounded-md shadow-lg overflow-hidden z-50">
            <div class="p-4 bg-indigo-600">
                <h3 class="text-white font-medium">Access Requests</h3>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <div v-if="loading" class="p-4 text-center text-gray-500">
                    Loading requests...
                </div>
                
                <div v-else-if="pendingRequests.length === 0" class="p-4 text-center text-gray-500">
                    No pending requests
                </div>
                
                <div v-else class="divide-y divide-gray-200">
                    <div v-for="request in pendingRequests" :key="request.id" class="p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ request.requester_email }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Requested access to: {{ request.file_name }}
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <button 
                                    @click="handleRequest(request.id, request.file_id, 'approve')"
                                    class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700"
                                >
                                    Approve
                                </button>
                                <button 
                                    @click="handleRequest(request.id, request.file_id, 'reject')"
                                    class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const showPanel = ref(false)
const loading = ref(false)
const pendingRequests = ref([])

const togglePanel = () => {
    showPanel.value = !showPanel.value
    if (showPanel.value) {
        fetchRequests()
    }
}

const fetchRequests = async () => {
    loading.value = true
    try {
        const response = await fetch('/api/documents/pending-requests')
        if (response.ok) {
            pendingRequests.value = await response.json()
        }
    } catch (error) {
        console.error('Failed to fetch requests:', error)
    } finally {
        loading.value = false
    }
}

const handleRequest = async (requestId, fileId, action) => {
    try {
        const response = await fetch(`/api/documents/handle-request/${requestId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie
                    .split('; ')
                    .find(row => row.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1] || '')
            },
            body: JSON.stringify({ action, file_id: fileId })
        })
        
        if (response.ok) {
            pendingRequests.value = pendingRequests.value.filter(req => req.id !== requestId)
        }
    } catch (error) {
        console.error('Failed to handle request:', error)
    }
}

// Poll for new requests every minute
let pollInterval
onMounted(() => {
    fetchRequests()
    pollInterval = setInterval(fetchRequests, 60000)
})

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval)
})
</script> 