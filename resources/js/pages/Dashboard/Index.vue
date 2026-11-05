<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { useFormatter } from '@/composables/useFormatter';
import type { Vault, Subscription, Expense } from '@/types/finances';

// Expandimos la interfaz de Totales aquí mismo o asegúrate de actualizarla en tu types/finances.ts
interface DashboardTotals {
    expenses: number;
    incomes: number;
    net_flow: number;
    capital: number;
    monthly_yield: number;
    pending_loans: number;
}

const { formatCurrency, formatDate, getMonthName } = useFormatter();

defineProps<{
    totals: DashboardTotals;
    vaults: Vault[];
    upcomingSubscriptions: Subscription[];
    latestExpenses: Expense[];
}>();

const currentMonthName = getMonthName();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];
</script>

<template>
    <Head :title="`Dashboard - ${currentMonthName}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header
                class="mb-8 flex flex-col items-start justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Resumen de {{ currentMonthName }}
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Tu patrimonio, rentabilidades y flujo mensual.
                    </p>
                </div>
            </header>

            <div
                class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4"
            >
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Ingresos del Mes</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-500 tabular-nums"
                        >
                            {{ formatCurrency(totals.incomes) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Gastos del Mes</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-red-500 tabular-nums"
                        >
                            {{ formatCurrency(totals.expenses) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Flujo Neto</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            :class="[
                                'font-mono text-3xl font-bold tabular-nums',
                                totals.net_flow >= 0
                                    ? 'text-emerald-400'
                                    : 'text-red-500',
                            ]"
                        >
                            {{ totals.net_flow > 0 ? '+' : ''
                            }}{{ formatCurrency(totals.net_flow) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            Capacidad de ahorro este mes
                        </p>
                    </CardContent>
                </Card>

                <Card
                    class="relative overflow-hidden border-zinc-800 bg-zinc-900 text-zinc-100"
                >
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Patrimonio Total</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ formatCurrency(totals.capital) }}
                        </p>
                        <div class="mt-1 flex gap-3 text-xs">
                            <span
                                v-if="totals.monthly_yield > 0"
                                class="font-mono font-medium text-emerald-400/80 tabular-nums"
                            >
                                +{{ formatCurrency(totals.monthly_yield) }}/mes
                            </span>
                            <span
                                v-if="totals.pending_loans > 0"
                                class="font-mono font-medium text-amber-400/80 tabular-nums"
                                title="Préstamos por cobrar"
                            >
                                {{ formatCurrency(totals.pending_loans) }} en la
                                calle
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="space-y-8 lg:col-span-1">
                    <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                        <CardHeader
                            class="border-b border-zinc-800 bg-zinc-900/50 pb-4"
                        >
                            <CardTitle class="text-base font-semibold"
                                >Tus Bóvedas</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-4 pt-4">
                            <div
                                v-for="vault in vaults"
                                :key="vault.id"
                                class="flex items-center justify-between"
                            >
                                <div>
                                    <p class="font-medium text-zinc-200">
                                        {{ vault.name }}
                                    </p>
                                    <p
                                        v-if="vault.annual_yield_rate"
                                        class="text-xs text-zinc-500"
                                    >
                                        Rendimiento:
                                        {{ vault.annual_yield_rate }}%
                                    </p>
                                </div>
                                <span
                                    class="font-mono font-semibold tabular-nums"
                                    >{{ formatCurrency(vault.balance) }}</span
                                >
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                        <CardHeader
                            class="border-b border-zinc-800 bg-zinc-900/50 pb-4"
                        >
                            <CardTitle class="text-base font-semibold"
                                >Próximos Pagos Fijos</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-4 pt-4">
                            <div
                                v-for="sub in upcomingSubscriptions"
                                :key="sub.id"
                                class="flex items-center justify-between"
                            >
                                <div>
                                    <p class="font-medium text-zinc-200">
                                        {{ sub.name }}
                                    </p>
                                    <p class="text-xs text-zinc-500">
                                        Cobro:
                                        {{ formatDate(sub.next_billing_date) }}
                                    </p>
                                </div>
                                <span
                                    class="font-mono font-medium text-zinc-300 tabular-nums"
                                    >-{{ formatCurrency(sub.amount) }}</span
                                >
                            </div>
                            <div
                                v-if="upcomingSubscriptions.length === 0"
                                class="py-2 text-center text-sm text-zinc-500"
                            >
                                No hay pagos fijos próximos.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="lg:col-span-2">
                    <Card
                        class="flex h-full flex-col border-zinc-800 bg-zinc-900 text-zinc-100"
                    >
                        <CardHeader
                            class="flex flex-row items-center justify-between space-y-0 border-b border-zinc-800 bg-zinc-900/50 pb-4"
                        >
                            <CardTitle class="text-base font-semibold"
                                >Últimos Gastos</CardTitle
                            >
                            <span class="text-xs font-normal text-zinc-400"
                                >Auditoría rápida</span
                            >
                        </CardHeader>
                        <CardContent class="grow p-0">
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
                                                >Fecha</TableHead
                                            >
                                            <TableHead
                                                class="font-medium text-zinc-400"
                                                >Descripción</TableHead
                                            >
                                            <TableHead
                                                class="font-medium text-zinc-400"
                                                >Categoría</TableHead
                                            >
                                            <TableHead
                                                class="text-right font-medium text-zinc-400"
                                                >Monto</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="expense in latestExpenses"
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
                                                >{{ expense.description }}
                                            </TableCell>
                                            <TableCell>
                                                <span
                                                    class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-zinc-700 ring-inset"
                                                >
                                                    {{ expense.category.name }}
                                                </span>
                                            </TableCell>
                                            <TableCell
                                                class="text-right font-mono font-medium text-red-400 tabular-nums"
                                            >
                                                -{{
                                                    formatCurrency(
                                                        expense.amount,
                                                    )
                                                }}
                                            </TableCell>
                                        </TableRow>
                                        <TableRow
                                            v-if="latestExpenses.length === 0"
                                            class="border-zinc-800 hover:bg-transparent"
                                        >
                                            <TableCell
                                                colspan="4"
                                                class="py-8 text-center text-zinc-500"
                                            >
                                                No hay gastos registrados aún.
                                                Manda un WhatsApp para empezar.
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
