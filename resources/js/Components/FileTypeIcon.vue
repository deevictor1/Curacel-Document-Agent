<template>
    <div :class="iconColor">
        <svg v-if="type === 'document'" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M16,11V18H8V11H16M10,13V16H14V13H10Z" />
        </svg>

        <svg v-else-if="type === 'spreadsheet'" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,13H8V11H10V13M14,13H12V11H14V13M18,13H16V11H18V13M10,17H8V15H10V17M14,17H12V15H14V17M18,17H16V15H18V17Z" />
        </svg>

        <svg v-else-if="type === 'presentation'" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19,16H5V8H19M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3M13,19H11V17H13V19M15,11H9V9H15V11Z" />
        </svg>

        <svg v-else-if="type === 'pdf'" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14,2L20,8V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V4A2,2 0 0,1 6,2H14M18,20V9H13V4H6V20H18M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,12.16 12.03,12.16C12.42,13.65 14.05,14.72 14.05,14.72C14.55,14.57 17.4,14.24 17,15.72C16.57,17.2 13.5,15.81 13.5,15.81C11.55,15.95 10.09,16.47 10.09,16.47C8.96,18.58 7.64,19.5 7.1,18.61C6.43,17.5 9.23,16.07 9.23,16.07C10.68,13.72 10.92,12.31 10.92,12.31M11.57,13.15C11.17,14.45 10.37,15.84 10.37,15.84C11.22,15.5 13.08,15.11 13.08,15.11C11.94,14.11 11.57,13.15 11.57,13.15M13.81,15.1C14.27,15.35 15.54,15.77 15.82,15.07C16.06,14.44 14.79,14.24 14.79,14.24L13.81,15.1M10.86,10.63C10.41,10.63 10.86,12.14 10.86,12.14C10.86,12.14 11.31,10.63 10.86,10.63M9.77,16.89C9.77,16.89 8.81,17.5 9.06,17.84C9.28,18.15 10.25,17.14 9.77,16.89Z" />
        </svg>

        <svg v-else-if="type === 'csv'" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,19H8V18H10V19M10,16H8V15H10V16M10,13H8V12H10V13M14,19H12V18H14V19M14,16H12V15H14V16M14,13H12V12H14V13M17,19H15V18H17V19M17,16H15V15H17V16M17,13H15V12H17V13Z" />
        </svg>

        <svg v-else-if="type === 'folder'" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" />
        </svg>

        <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        required: true
    }
})

const iconColor = computed(() => {
    const colors = {
        'document': 'text-blue-600',
        'spreadsheet': 'text-green-600',
        'presentation': 'text-yellow-600',
        'pdf': 'text-red-600',
        'csv': 'text-purple-600',
        'folder': 'text-gray-600'
    }
    return colors[props.type] || 'text-gray-400'
})
</script> 