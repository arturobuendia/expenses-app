<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type {
    Expense,
    FinanceEvent,
    EventCategorySummary,
    EventDaySummary,
    EventPaymentMethodSummary,
} from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    event: FinanceEvent & { expenses: Expense[] };
    by_category: EventCategorySummary[];
    by_day: EventDaySummary[];
    by_payment_method: EventPaymentMethodSummary;
}>();

const { formatCurrency, formatDate } = useFormatter();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Eventos', href: '/events' },
    { title: props.event.name, href: `/events/${props.event.id}` },
];

const expenses = computed(() => props.event.expenses ?? []);
const total = computed(() => props.event.spent ?? 0);
const budget = computed(() => props.event.budget);
const remaining = computed<number | null>(
    () => props.event.remaining_budget ?? null,
);

const maxCategory = computed(() =>
    Math.max(1, ...props.by_category.map((item) => item.total)),
);
const maxDay = computed(() =>
    Math.max(1, ...props.by_day.map((item) => item.total)),
);

const paymentTotal = computed(
    () =>
        props.by_payment_method.card +
        props.by_payment_method.vault +
        props.by_payment_method.none,
);

const pct = (value: number): number => {
    if (paymentTotal.value <= 0) {
        return 0;
    }
    return Math.round((value / paymentTotal.value) * 100);
};

const barWidth = (value: number, max: number): string =>
    `${Math.max(2, Math.round((value / max) * 100))}%`;

const formatPeriod = (): string => {
    if (!props.event.starts_at && !props.event.ends_at) {
        return 'Sin fechas definidas';
    }
    if (props.event.starts_at && props.event.ends_at) {
        return `${formatDate(props.event.starts_at)} – ${formatDate(
            props.event.ends_at,
        )}`;
    }
    return formatDate((props.event.starts_at ?? props.event.ends_at) as string);
};

const paymentMethods = computed(() => [
    {
        label: 'Tarjeta de crédito',
        amount: props.by_payment_method.card,
        color: 'bg-violet-500',
    },
    {
        label: 'Bóveda / efectivo',
        amount: props.by_payment_method.vault,
        color: 'bg-emerald-500',
    },
    {
        label: 'Sin especificar',
        amount: props.by_payment_method.none,
        color: 'bg-zinc-500',
    },
]);
</script>

<template>
    <Head :title="event.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header
                class="mb-8 flex flex-col items-start justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <h1
                            class="font-display text-3xl font-bold tracking-tight text-white"
                        >
                            {{ event.name }}
                        </h1>
                        <span
                            v-if="event.archived_at"
                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-zinc-700 ring-inset"
                        >
                            Finalizado
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/20 ring-inset"
                        >
                            Activo
                        </span>
                    </div>
                    <p class="mt-1 text-zinc-400">{{ formatPeriod() }}</p>
                    <p
                        v-if="event.description"
                        class="mt-1 text-sm text-zinc-500"
                    >
                        {{ event.description }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        as-child
                        class="border-zinc-800 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                    >
                        <Link href="/events">Volver</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="`/expenses?new=1&event_id=${event.id}`">
                            + Registrar gasto
                        </Link>
                    </Button>
                </div>
            </header>

            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Total gastado</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-red-500 tabular-nums"
                        >
                            -{{ formatCurrency(total) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-600">
                            {{ expenses.length }} movimiento(s) en el evento.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Presupuesto</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{
                                budget == null
                                    ? 'Sin límite'
                                    : formatCurrency(budget)
                            }}
                        </p>
                        <p
                            v-if="budget != null"
                            class="mt-1 text-xs text-zinc-600"
                        >
                            Límite que definiste para este evento.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Disponible</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold tabular-nums"
                            :class="
                                remaining != null && remaining < 0
                                    ? 'text-red-500'
                                    : 'text-emerald-400'
                            "
                        >
                            {{
                                remaining == null
                                    ? '—'
                                    : formatCurrency(remaining)
                            }}
                        </p>
                        <p
                            v-if="remaining != null && remaining < 0"
                            class="mt-1 text-xs text-red-400"
                        >
                            Te excediste del presupuesto.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Distribución por categoría</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="item in by_category"
                            :key="item.category"
                            class="space-y-1"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-zinc-300">{{
                                    item.category
                                }}</span>
                                <span
                                    class="font-mono text-zinc-400 tabular-nums"
                                    >{{ formatCurrency(item.total) }}</span
                                >
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-zinc-800"
                            >
                                <div
                                    class="h-full rounded-full bg-sky-500"
                                    :style="{
                                        width: barWidth(
                                            item.total,
                                            maxCategory,
                                        ),
                                    }"
                                />
                            </div>
                        </div>
                        <p
                            v-if="by_category.length === 0"
                            class="py-4 text-center text-sm text-zinc-500"
                        >
                            Aún no hay gastos asociados.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Gastos por día</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="item in by_day"
                            :key="item.date"
                            class="space-y-1"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-zinc-300">{{
                                    formatDate(item.date)
                                }}</span>
                                <span
                                    class="font-mono text-zinc-400 tabular-nums"
                                    >{{ formatCurrency(item.total) }}</span
                                >
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-zinc-800"
                            >
                                <div
                                    class="h-full rounded-full bg-amber-500"
                                    :style="{
                                        width: barWidth(item.total, maxDay),
                                    }"
                                />
                            </div>
                        </div>
                        <p
                            v-if="by_day.length === 0"
                            class="py-4 text-center text-sm text-zinc-500"
                        >
                            Aún no hay gastos asociados.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card class="mb-8 border-zinc-800 bg-zinc-900 text-zinc-100">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-zinc-400"
                        >Métodos de pago</CardTitle
                    >
                </CardHeader>
                <CardContent class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div
                        v-for="method in paymentMethods"
                        :key="method.label"
                        class="rounded-lg border border-zinc-800 bg-zinc-950/50 p-4"
                    >
                        <div
                            class="flex items-center justify-between text-sm text-zinc-400"
                        >
                            <span>{{ method.label }}</span>
                            <span class="font-mono tabular-nums"
                                >{{ pct(method.amount) }}%</span
                            >
                        </div>
                        <p
                            class="mt-2 font-mono text-xl font-semibold text-zinc-100 tabular-nums"
                        >
                            {{ formatCurrency(method.amount) }}
                        </p>
                        <div
                            class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-800"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="method.color"
                                :style="{ width: `${pct(method.amount)}%` }"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-zinc-400"
                        >Movimientos del evento</CardTitle
                    >
                </CardHeader>
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
                                        >Fecha</TableHead
                                    >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Descripción</TableHead
                                    >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Categoría</TableHead
                                    >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Método</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Monto</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="expense in expenses"
                                    :key="expense.id"
                                    class="border-zinc-800 transition-colors hover:bg-zinc-800/50"
                                >
                                    <TableCell
                                        class="whitespace-nowrap text-zinc-400"
                                        >{{
                                            formatDate(expense.date)
                                        }}</TableCell
                                    >
                                    <TableCell
                                        class="font-medium text-zinc-200"
                                        >{{ expense.description }}</TableCell
                                    >
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-zinc-700 ring-inset"
                                        >
                                            {{ expense.category?.name ?? '—' }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-zinc-400">
                                        {{
                                            expense.card
                                                ? 'Tarjeta'
                                                : expense.vault
                                                  ? 'Bóveda'
                                                  : '—'
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium text-red-400 tabular-nums"
                                    >
                                        -{{ formatCurrency(expense.amount) }}
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="expenses.length === 0"
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="5"
                                        class="py-8 text-center text-zinc-500"
                                    >
                                        Este evento todavía no tiene gastos. Usa
                                        "Registrar gasto" para asociar el
                                        primero.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
