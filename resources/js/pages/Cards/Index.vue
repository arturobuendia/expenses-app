<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Card } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import {
    Card as CardPanel,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';

const { formatCurrency, formatDate } = useFormatter();

defineProps<{
    cards: Card[];
    totals: {
        cards: number;
        credit_limit: number;
        debt: number;
        available_credit: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Tarjetas', href: '/cards' },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    credit_limit: '',
    cut_off_day: '',
    payment_day: '',
    is_active: true,
});

const openModal = (card?: Card) => {
    if (card) {
        isEditing.value = true;
        editingId.value = card.id;
        form.name = card.name;
        form.credit_limit = card.credit_limit.toString();
        form.cut_off_day = card.cut_off_day.toString();
        form.payment_day = card.payment_day.toString();
        form.is_active = card.is_active;
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.is_active = true;
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
    if (isEditing.value && editingId.value) {
        form.put(`/cards/${editingId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/cards', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCard = (id: number) => {
    if (
        confirm(
            '¿Eliminar esta tarjeta? Sus gastos se conservan, pero pierden la referencia a la tarjeta.',
        )
    ) {
        router.delete(`/cards/${id}`);
    }
};

const nextPending = (card: Card) =>
    card.statement_summaries?.find((statement) => !statement.is_settled) ??
    null;
</script>

<template>
    <Head title="Tarjetas de Crédito" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header class="mb-8 flex items-end justify-between">
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Tarjetas de Crédito
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Cortes, fechas límite de pago y cupo disponible.
                    </p>
                </div>
                <Button
                    @click="openModal()"
                    class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200"
                >
                    + Nueva Tarjeta
                </Button>
            </header>

            <div
                class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4"
            >
                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Tarjetas</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ totals.cards }}
                        </p>
                    </CardContent>
                </CardPanel>

                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Límite Total</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ formatCurrency(totals.credit_limit) }}
                        </p>
                    </CardContent>
                </CardPanel>

                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Deuda Total</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-red-400 tabular-nums"
                        >
                            {{ formatCurrency(totals.debt) }}
                        </p>
                    </CardContent>
                </CardPanel>

                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Cupo Disponible</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-400 tabular-nums"
                        >
                            {{ formatCurrency(totals.available_credit) }}
                        </p>
                    </CardContent>
                </CardPanel>
            </div>

            <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader
                                class="border-zinc-800 bg-zinc-900/50 hover:bg-zinc-900/50"
                            >
                                <TableRow
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Tarjeta</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Límite</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Corte / Pago</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Deuda</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Disponible</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Próximo Pago</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Acciones</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="card in cards"
                                    :key="card.id"
                                    class="border-zinc-800 transition-colors hover:bg-zinc-800/50"
                                >
                                    <TableCell
                                        class="font-medium text-zinc-200"
                                    >
                                        {{ card.name }}
                                        <span
                                            v-if="!card.is_active"
                                            class="ml-2 text-xs text-zinc-500"
                                            >(inactiva)</span
                                        >
                                        <div
                                            v-if="card.cycle"
                                            class="text-xs text-zinc-500"
                                        >
                                            Ciclo:
                                            {{
                                                formatDate(
                                                    card.cycle.period_start,
                                                )
                                            }}
                                            —
                                            {{
                                                formatDate(
                                                    card.cycle.period_end,
                                                )
                                            }}
                                        </div>
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono text-zinc-300 tabular-nums"
                                    >
                                        {{ formatCurrency(card.credit_limit) }}
                                    </TableCell>
                                    <TableCell class="text-right text-zinc-300">
                                        <div>
                                            {{ card.cut_off_day }} /
                                            {{ card.payment_day }}
                                        </div>
                                        <div class="text-xs text-zinc-500">
                                            día de corte / pago
                                        </div>
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-bold tabular-nums"
                                        :class="
                                            (card.debt ?? 0) > 0
                                                ? 'text-red-400'
                                                : 'text-zinc-300'
                                        "
                                    >
                                        {{ formatCurrency(card.debt ?? 0) }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium tabular-nums"
                                        :class="
                                            (card.available_credit ?? 0) < 0
                                                ? 'text-red-500'
                                                : 'text-emerald-400'
                                        "
                                    >
                                        {{
                                            formatCurrency(
                                                card.available_credit ?? 0,
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell class="text-right text-zinc-300">
                                        <template v-if="nextPending(card)">
                                            <div>
                                                {{
                                                    formatDate(
                                                        nextPending(card)!
                                                            .due_date,
                                                    )
                                                }}
                                            </div>
                                            <div
                                                class="font-mono text-xs text-amber-400 tabular-nums"
                                            >
                                                {{
                                                    formatCurrency(
                                                        nextPending(card)!
                                                            .pending_amount ??
                                                            0,
                                                    )
                                                }}
                                                pendiente
                                            </div>
                                        </template>
                                        <span
                                            v-else
                                            class="text-xs text-zinc-600"
                                            >Sin adeudo</span
                                        >
                                    </TableCell>
                                    <TableCell class="space-x-2 text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openModal(card)"
                                            class="text-zinc-400 hover:text-zinc-200"
                                        >
                                            Editar
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteCard(card.id)"
                                            class="text-red-500 hover:bg-red-500/10 hover:text-red-400"
                                        >
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="cards.length === 0"
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="7"
                                        class="py-8 text-center text-zinc-500"
                                    >
                                        No tienes tarjetas registradas.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </CardPanel>

            <Dialog v-model:open="isModalOpen">
                <DialogContent
                    class="border-zinc-800 bg-zinc-950 text-zinc-100 sm:max-w-106.25"
                >
                    <DialogHeader>
                        <DialogTitle>{{
                            isEditing ? 'Editar Tarjeta' : 'Nueva Tarjeta'
                        }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Define el límite de crédito y los días de corte y
                            pago.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-zinc-400"
                                >Nombre de la tarjeta</Label
                            >
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. BBVA Azul, Nu Crédito"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-xs text-red-500"
                                >{{ form.errors.name }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label for="credit_limit" class="text-zinc-400"
                                >Límite de crédito</Label
                            >
                            <Input
                                id="credit_limit"
                                v-model="form.credit_limit"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="border-zinc-800 bg-zinc-900 font-medium text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="0.00"
                            />
                            <span
                                v-if="form.errors.credit_limit"
                                class="text-xs text-red-500"
                                >{{ form.errors.credit_limit }}</span
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="cut_off_day" class="text-zinc-400"
                                    >Día de corte</Label
                                >
                                <Input
                                    id="cut_off_day"
                                    v-model="form.cut_off_day"
                                    type="number"
                                    min="1"
                                    max="31"
                                    required
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="Ej. 10"
                                />
                                <span
                                    v-if="form.errors.cut_off_day"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.cut_off_day }}</span
                                >
                            </div>
                            <div class="space-y-2">
                                <Label for="payment_day" class="text-zinc-400"
                                    >Día límite de pago</Label
                                >
                                <Input
                                    id="payment_day"
                                    v-model="form.payment_day"
                                    type="number"
                                    min="1"
                                    max="31"
                                    required
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="Ej. 25"
                                />
                                <span
                                    v-if="form.errors.payment_day"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.payment_day }}</span
                                >
                            </div>
                        </div>

                        <p class="text-xs text-zinc-500">
                            La fecha límite es el siguiente día de pago después
                            del corte. Con corte 10 y pago 25, el corte del 10
                            vence el 25 del mismo mes.
                        </p>

                        <div
                            class="mt-2 flex items-center gap-3 border-t border-zinc-800 pt-2"
                        >
                            <Checkbox
                                id="is_active"
                                v-model="form.is_active"
                                class="border-zinc-600"
                            />
                            <Label
                                for="is_active"
                                class="cursor-pointer text-sm font-medium text-zinc-200"
                            >
                                Tarjeta activa
                            </Label>
                        </div>

                        <DialogFooter class="mt-2 pt-4">
                            <Button
                                type="button"
                                variant="ghost"
                                @click="closeModal"
                                class="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200"
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200"
                            >
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
