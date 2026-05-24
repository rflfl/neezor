<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    title: String,
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head :title="title" />

        <div class="max-w-2xl mx-auto px-4 py-8">
            <slot />
        </div>
    </div>
</template>