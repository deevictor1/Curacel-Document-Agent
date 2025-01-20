<template>
    <div class="flex flex-col w-64 bg-white border-r">
        <!-- Workspace Info -->
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ workspace?.name }}
            </h2>
            <p class="text-sm text-gray-600">
                {{ workspace?.domain }}
            </p>
        </div>

        <!-- Members List -->
        <div class="flex-1 overflow-y-auto p-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                Workspace Members
            </h3>
            
            <div v-if="loading" class="flex items-center justify-center p-4">
                <div class="flex items-center space-x-3 text-sm text-gray-600">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading members...</span>
                </div>
            </div>
            
            <div v-else-if="error" class="text-sm text-red-600">
                {{ error }}
            </div>
            
            <ul v-else class="space-y-2">
                <li 
                    v-for="member in members" 
                    :key="member.email"
                    class="flex items-center justify-between text-sm"
                >
                    <button
                        @click="searchMemberDocs(member)"
                        class="flex-1 flex items-center space-x-2 px-2 py-1 rounded-md hover:bg-gray-100 text-left"
                        :class="{'bg-gray-100': selectedMember?.email === member.email}"
                    >
                        <span class="text-gray-900">{{ member.name }}</span>
                        <span 
                            class="text-gray-500 hover:text-indigo-600 cursor-pointer"
                            @click.stop="searchMemberDocs(member)"
                        >
                            ({{ member.documentCount }} files)
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    workspace: Object
})

const members = ref([])
const loading = ref(true)
const error = ref(null)
const selectedMember = ref(null)

const emit = defineEmits(['memberSelected'])

const fetchMembers = async () => {
    try {
        loading.value = true
        const response = await fetch('/api/workspace/members', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''
            },
            credentials: 'include'
        })
        
        // For debugging
        console.log('Response status:', response.status)
        console.log('Response headers:', Object.fromEntries(response.headers))
        
        const text = await response.text()
        try {
            const data = JSON.parse(text)
            if (Array.isArray(data)) {
                members.value = data
            } else {
                throw new Error('Invalid response format')
            }
        } catch (e) {
            console.error('Response text:', text)
            throw new Error('Failed to parse response')
        }
    } catch (e) {
        console.error('Error fetching members:', e)
        error.value = e.message || 'Failed to load workspace members'
    } finally {
        loading.value = false
    }
}

const searchMemberDocs = (member) => {
    selectedMember.value = member
    emit('memberSelected', member)
}

onMounted(fetchMembers)
</script> 