<template>
    <AppLayout title="Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Workspace Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Slack Connection -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-4">Slack Integration</h3>
                        <Link 
                            v-if="!workspace?.slack_connected"
                            :href="route('slack.login')"
                            class="inline-flex items-center px-4 py-2 bg-slate-800 text-white rounded-md"
                        >
                            Connect Slack Workspace
                        </Link>
                        <div v-else class="text-green-600">
                            ✓ Slack Workspace Connected
                        </div>
                    </div>

                    <!-- Google Drive Connection -->
                    <div>
                        <h3 class="text-lg font-medium mb-4">Google Drive Integration</h3>
                        <Link 
                            v-if="!workspace?.google_connected"
                            :href="route('google.login')"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md"
                        >
                            Connect Google Drive
                        </Link>
                        <div v-else class="text-green-600">
                            ✓ Google Drive Connected
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { defineProps } from 'vue'

const props = defineProps({
    workspace: Object
})
</script> 