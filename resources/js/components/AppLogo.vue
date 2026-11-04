<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const name = page.props.name;
</script>

<template>
    <div
        class="flex size-10 shrink-0 items-center justify-center group-data-[collapsible=icon]:size-6"
    >
        <AppLogoIcon class="size-10 group-data-[collapsible=icon]:size-6" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            name
        }}</span>
    </div>
</template>
