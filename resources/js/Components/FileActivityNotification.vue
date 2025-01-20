<template>
    <div class="fixed bottom-4 right-4 w-80">
        <div v-for="activity in activities" :key="activity.id" 
             class="bg-white shadow-lg rounded-lg p-4 mb-2">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm">
                        <span class="font-medium">{{ activity.actor_email }}</span> 
                        {{ getActionText(activity.action_type) }}
                        <span class="font-medium">{{ activity.file_name }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ formatDate(activity.created_at) }}
                    </p>
                </div>
                <button @click="markAsRead(activity.id)" 
                        class="text-gray-400 hover:text-gray-600">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const activities = ref([])

const getActionText = (actionType) => {
    switch (actionType) {
        case 'edit': return 'edited';
        case 'delete': return 'deleted';
        case 'restore': return 'restored';
        case 'rename': return 'renamed';
        default: return 'modified';
    }
}

const formatDate = (date) => {
    return new Date(date).toLocaleString()
}

const fetchActivities = async () => {
    try {
        const response = await fetch('/api/file-activities')
        if (response.ok) {
            activities.value = await response.json()
        }
    } catch (error) {
        console.error('Failed to fetch activities:', error)
    }
}

const markAsRead = async (activityId) => {
    try {
        await fetch(`/api/file-activities/${activityId}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        activities.value = activities.value.filter(a => a.id !== activityId)
    } catch (error) {
        console.error('Failed to mark as read:', error)
    }
}

onMounted(() => {
    fetchActivities()
    // Poll for new activities every minute
    setInterval(fetchActivities, 60000)
})
</script> 