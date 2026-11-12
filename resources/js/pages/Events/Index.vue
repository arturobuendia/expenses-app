<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { FinanceEvent } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    active_events: FinanceEvent[];
    archived_events: FinanceEvent[];
}>();

const { formatCurrency, formatDate, toDateInput } = useFormatter();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Eventos', href: '/events' },
];

const sections = computed(() => [
    { title: 'Eventos activos', events: props.active_events, archived: false },
    { title: 'Historial', events: props.archived_events, archived: true },
]);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
    starts_at: '',
    ends_at: '',
    budget: '',
});

const openModal = (event?: FinanceEvent) => {
    if (event) {
        isEditing.value = true;
        editingId.value = event.id;
        form.name = event.name;
        form.description = event.description ?? '';
        form.starts_at = toDateInput(event.starts_at ?? '');
        form.ends_at = toDateInput(event.ends_at ?? '');
        form.budget = event.budget != null ? String(event.budget) : '';
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        form.reset();
        form.clearErrors();
    }, 200);
};

const submitForm = () => {
    const payload = {
        name: form.name,
        description: form.description || null,
        starts_at: form.starts_at || null,
        ends_at: form.ends_at || null,
        budget: form.budget === '' ? null : form.budget,
    };

    if (isEditing.value && editingId.value) {
        router.put(`/events/${editingId.value}`, payload, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post('/events', payload, {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleArchive = (event: FinanceEvent) => {
    router.patch(`/events/${event.id}/archive`, {}, { preserveScroll: true });
};

const deleteEvent = (event: FinanceEvent) => {
    if (
        confirm(
            `¿Eliminar el evento "${event.name}"? Los gastos se conservan, solo se desvinculan.`,
        )
    ) {
        router.delete(`/events/${event.id}`);
    }
};

const formatPeriod = (event: FinanceEvent): string => {
    if (!event.starts_at && !event.ends_at) {
        return 'Sin fechas';
    }
    if (event.starts_at && event.ends_at) {
        return `${formatDate(event.starts_at)} – ${formatDate(event.ends_at)}`;
    }
    return formatDate((event.starts_at ?? event.ends_at) as string);
};

const formatRemaining = (event: FinanceEvent): string => {
    if (event.remaining_budget == null) {
        return '—';
    }
    return formatCurrency(event.remaining_budget);
};
</script>

<template>
    <Head title="Eventos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header class="mb-8 flex items-end justify-between">
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Eventos
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Agrupa gastos de unas vacaciones, un viaje o cualquier
                        plan.
                    </p>
                </div>
                <Button @click="openModal()"> + Nuevo Evento </Button>
            </header>

            <div class="space-y-10">
                <section v-for="section in sections" :key="section.title">
                    <h2 class="mb-3 text-lg font-semibold text-zinc-200">
                        {{ section.title }}
                    </h2>

                    <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                        <CardContent class="p-0">
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader
                                        class="border-zinc-800 bg-zinc-900/50 hover:bg-zinc-900/50"
                                    >
                                        <TableRow
                                            class="border-zinc-800 hover:bg-transparent"
                                        >
                                            <TableHead
                                                class="font-medium text-zinc-400"
                                                >Evento</TableHead
                                            >
                                            <TableHead
                                                class="font-medium text-zinc-400"
                                                >Periodo</TableHead
                                            >
                                            <TableHead
                                                class="text-center font-medium text-zinc-400"
                                                >Movs.</TableHead
                                            >
                                            <TableHead
                                                class="text-right font-medium text-zinc-400"
                                                >Gastado</TableHead
                                            >
                                            <TableHead
                                                class="text-right font-medium text-zinc-400"
                                                >Presupuesto</TableHead
                                            >
                                            <TableHead
                                                class="text-right font-medium text-zinc-400"
                                                >Disponible</TableHead
                                            >
                                            <TableHead
                                                class="text-right font-medium text-zinc-400"
                                                >Acciones</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="event in section.events"
                                            :key="event.id"
                                            class="border-zinc-800 transition-colors hover:bg-zinc-800/50"
                                        >
                                            <TableCell>
                                                <Link
                                                    :href="`/events/${event.id}`"
                                                    class="font-medium text-zinc-200 hover:text-white"
                                                >
                                                    {{ event.name }}
                                                </Link>
                                                <p
                                                    v-if="event.description"
                                                    class="mt-1 max-w-xs truncate text-xs text-zinc-500"
                                                >
                                                    {{ event.description }}
                                                </p>
                                            </TableCell>
                                            <TableCell
                                                class="whitespace-nowrap text-zinc-400"
                                                >{{
                                                    formatPeriod(event)
                                                }}</TableCell
                                            >
                                            <TableCell
                                                class="text-center text-zinc-400"
                                                >{{
                                                    event.expenses_count ?? 0
                                                }}</TableCell
                                            >
                                            <TableCell
                                                class="text-right font-mono font-medium text-red-400 tabular-nums"
                                            >
                                                -{{
                                                    formatCurrency(
                                                        event.spent ?? 0,
                                                    )
                                                }}
                                            </TableCell>
                                            <TableCell
                                                class="text-right font-mono text-zinc-300 tabular-nums"
                                            >
                                                {{
                                                    event.budget == null
                                                        ? 'Sin límite'
                                                        : formatCurrency(
                                                              event.budget,
                                                          )
                                                }}
                                            </TableCell>
                                            <TableCell
                                                class="text-right font-mono tabular-nums"
                                                :class="
                                                    event.remaining_budget !=
                                                        null &&
                                                    event.remaining_budget < 0
                                                        ? 'text-red-400'
                                                        : 'text-emerald-400'
                                                "
                                            >
                                                {{ formatRemaining(event) }}
                                            </TableCell>
                                            <TableCell
                                                class="space-x-2 text-right whitespace-nowrap"
                                            >
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    as-child
                                                    class="text-zinc-400 hover:text-zinc-200"
                                                >
                                                    <Link
                                                        :href="`/events/${event.id}`"
                                                        >Ver</Link
                                                    >
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="openModal(event)"
                                                    class="text-zinc-400 hover:text-zinc-200"
                                                >
                                                    Editar
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        toggleArchive(event)
                                                    "
                                                    class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                                                >
                                                    {{
                                                        section.archived
                                                            ? 'Reactivar'
                                                            : 'Finalizar'
                                                    }}
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="deleteEvent(event)"
                                                    class="text-red-500 hover:bg-red-500/10 hover:text-red-400"
                                                >
                                                    Borrar
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow
                                            v-if="section.events.length === 0"
                                            class="border-zinc-800 hover:bg-transparent"
                                        >
                                            <TableCell
                                                colspan="7"
                                                class="py-8 text-center text-zinc-500"
                                            >
                                                {{
                                                    section.archived
                                                        ? 'Todavía no hay eventos finalizados.'
                                                        : 'No hay eventos activos.'
                                                }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </section>
            </div>

            <Dialog v-model:open="isModalOpen">
                <DialogContent
                    class="border-zinc-800 bg-zinc-950 text-zinc-100 sm:max-w-106.25"
                >
                    <DialogHeader>
                        <DialogTitle>{{
                            isEditing ? 'Editar Evento' : 'Nuevo Evento'
                        }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Las fechas y el presupuesto son opcionales.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-zinc-400"
                                >Nombre</Label
                            >
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Vacaciones en la playa"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-xs text-red-500"
                                >{{ form.errors.name }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-zinc-400"
                                >Descripción
                                <span class="text-xs text-zinc-600"
                                    >(Opcional)</span
                                ></Label
                            >
                            <Input
                                id="description"
                                v-model="form.description"
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Viaje con la familia"
                            />
                            <span
                                v-if="form.errors.description"
                                class="text-xs text-red-500"
                                >{{ form.errors.description }}</span
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="starts_at" class="text-zinc-400"
                                    >Inicio
                                    <span class="text-xs text-zinc-600"
                                        >(Opcional)</span
                                    ></Label
                                >
                                <Input
                                    id="starts_at"
                                    v-model="form.starts_at"
                                    type="date"
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 scheme-dark focus-visible:ring-zinc-500"
                                />
                                <span
                                    v-if="form.errors.starts_at"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.starts_at }}</span
                                >
                            </div>
                            <div class="space-y-2">
                                <Label for="ends_at" class="text-zinc-400"
                                    >Fin
                                    <span class="text-xs text-zinc-600"
                                        >(Opcional)</span
                                    ></Label
                                >
                                <Input
                                    id="ends_at"
                                    v-model="form.ends_at"
                                    type="date"
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 scheme-dark focus-visible:ring-zinc-500"
                                />
                                <span
                                    v-if="form.errors.ends_at"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.ends_at }}</span
                                >
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="budget" class="text-zinc-400"
                                >Presupuesto
                                <span class="text-xs text-zinc-600"
                                    >(Opcional)</span
                                ></Label
                            >
                            <Input
                                id="budget"
                                v-model="form.budget"
                                type="number"
                                step="0.01"
                                min="0"
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Sin límite"
                            />
                            <span
                                v-if="form.errors.budget"
                                class="text-xs text-red-500"
                                >{{ form.errors.budget }}</span
                            >
                        </div>

                        <DialogFooter
                            class="mt-2 border-t border-zinc-800 pt-4"
                        >
                            <Button
                                type="button"
                                variant="ghost"
                                @click="closeModal"
                                class="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200"
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                {{
                                    form.processing ? 'Guardando...' : 'Guardar'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
