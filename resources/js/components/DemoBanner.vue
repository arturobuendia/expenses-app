<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FlaskConical } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const isDemo = computed(() => page.props.demo === true);
</script>

<template>
    <div
        v-if="isDemo"
        class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-500/30 bg-amber-500/10 px-4 py-2 text-sm text-amber-900 dark:text-amber-100"
        data-test="demo-banner"
    >
        <p class="flex items-center gap-2">
            <FlaskConical class="size-4 shrink-0" aria-hidden="true" />
            <span>
                Estás en la demo con datos de ejemplo. Puedes escribir lo que
                quieras: se reinicia cada semana.
            </span>
        </p>
        <Link
            href="/demo/exit"
            class="rounded-sm border border-amber-600/40 px-3 py-1 font-medium hover:bg-amber-500/20"
        >
            Salir de la demo
        </Link>
    </div>
</template>
