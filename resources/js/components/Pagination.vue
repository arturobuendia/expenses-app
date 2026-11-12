<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types/finances';

const props = withDefaults(
    defineProps<{
        currentPage: number;
        lastPage: number;
        total: number;
        from: number | null;
        to: number | null;
        prevUrl: string | null;
        nextUrl: string | null;
        links: PaginationLink[];
        itemsLabel?: string;
    }>(),
    {
        itemsLabel: 'registros',
    },
);

// Laravel incluye "« Previous" y "Next »" como primer y último enlace; los
// botones de los extremos ya los cubren, así que solo pintamos el centro.
const pageLinks = computed((): PaginationLink[] => props.links.slice(1, -1));

const visit = (url: string | null) => {
    if (!url) {
        return;
    }

    // preserveState conserva los filtros activos y preserveScroll evita que la
    // página salte al inicio al cambiar de página.
    router.visit(url, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <nav
        v-if="total > 0"
        aria-label="Paginación"
        class="flex flex-col items-center justify-between gap-3 border-t border-zinc-800 px-4 py-3 sm:flex-row"
    >
        <p class="text-sm text-zinc-400">
            Mostrando
            <span class="font-medium text-zinc-200">{{ from ?? 0 }}</span>
            –
            <span class="font-medium text-zinc-200">{{ to ?? 0 }}</span>
            de
            <span class="font-medium text-zinc-200">{{ total }}</span>
            {{ itemsLabel }}
        </p>

        <div v-if="lastPage > 1" class="flex items-center gap-1">
            <Button
                variant="outline"
                size="icon-sm"
                aria-label="Página anterior"
                :disabled="!prevUrl"
                class="border-zinc-800 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                @click="visit(prevUrl)"
            >
                <ChevronLeft />
            </Button>

            <template
                v-for="(link, index) in pageLinks"
                :key="`${index}-${link.label}`"
            >
                <span
                    v-if="!link.url"
                    aria-hidden="true"
                    class="px-2 text-sm text-zinc-600"
                >
                    {{ link.label }}
                </span>
                <Button
                    v-else
                    :variant="link.active ? 'default' : 'outline'"
                    size="icon-sm"
                    :aria-label="`Página ${link.label}`"
                    :aria-current="link.active ? 'page' : undefined"
                    class="border-zinc-800 bg-transparent text-zinc-300 tabular-nums hover:bg-zinc-800 hover:text-white"
                    :class="{
                        'border-zinc-700 bg-zinc-100 text-zinc-900 hover:bg-white hover:text-zinc-900':
                            link.active,
                    }"
                    @click="visit(link.url)"
                >
                    {{ link.label }}
                </Button>
            </template>

            <Button
                variant="outline"
                size="icon-sm"
                aria-label="Página siguiente"
                :disabled="!nextUrl"
                class="border-zinc-800 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                @click="visit(nextUrl)"
            >
                <ChevronRight />
            </Button>
        </div>
    </nav>
</template>
