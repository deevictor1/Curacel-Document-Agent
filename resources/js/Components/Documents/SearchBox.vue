<template>
    <div class="w-full">
        <div class="relative">
            <input 
                type="text" 
                v-model="query"
                @keyup.enter="search"
                placeholder="Search documents... (e.g., 'find budget spreadsheet from last month')"
                class="w-full px-4 py-2 border rounded-lg pr-10"
            >
            <button 
                @click="search"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
            >
                Search
            </button>
        </div>
        
        <div v-if="loading" class="mt-4 text-center">
            Searching documents...
        </div>

        <div v-if="error" class="mt-4 text-red-600">
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const query = ref('')
const loading = ref(false)
const error = ref(null)

const search = async () => {
    if (!query.value.trim()) return

    loading.value = true
    error.value = null

    try {
        const response = await fetch(`/api/documents/search?q=${encodeURIComponent(query.value)}`)
        if (!response.ok) throw new Error('Search failed')
        
        const data = await response.json()
        emit('results', data)
    } catch (e) {
        error.value = 'Failed to search documents. Please try again.'
    } finally {
        loading.value = false
    }
}

defineEmits(['results'])
</script> 