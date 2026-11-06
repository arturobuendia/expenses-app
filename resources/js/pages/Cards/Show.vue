<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Card, CardStatement } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';
import {
    Card as CardPanel,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const { formatCurrency } = useFormatter();

const props = defineProps<{
    card: Card;
    statements: CardStatement[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Tarjetas', href: '/cards' },
    { title: props.card.name, href: `/cards/${props.card.id}` },
];

// Fecha tipo "YYYY-MM-DD" o "YYYY-MM-DD HH:MM:SS" sin desfase de zona horaria
// (una fecha sin hora la parsea JS como UTC y correría un día en México).
const formatDay = (value: string): string => {
    if (!value) {
        return '';
    }

    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        timeZone: 'America/Mexico_City',
    }).format(new Date(year, month - 1, day));
};

const sum = (items: Array<{ amount: number | string }>): number =>
    items.reduce((total, item) => total + Number(item.amount || 0), 0);

const openStates = ref<Record<number, boolean>>({});
const firstPending = props.statements.find(
    (statement) => !statement.is_settled,
);

if (firstPending) {
    openStates.value[firstPending.id] = true;
}

const nextPending =
    [...props.statements]
        .filter((statement) => !statement.is_settled)
        .sort((a, b) => a.due_date.localeCompare(b.due_date))[0] ?? null;
</script>

<template>
    <Head :title="`Estados de Cuenta — ${card.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header class="mb-8">
                <Link
                    href="/cards"
                    class="text-sm text-zinc-400 transition-colors hover:text-zinc-200"
                >
                    ← Volver a Tarjetas
                </Link>
                <h1
                    class="mt-2 font-display text-3xl font-bold tracking-tight text-white"
                >
                    {{ card.name }}
                </h1>
                <p class="mt-1 text-zinc-400">
                    Corte el {{ card.cut_off_day }} · Pago el
                    {{ card.payment_day }}
                </p>
            </header>

            <div
                class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4"
            >
                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Límite</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ formatCurrency(card.credit_limit) }}
                        </p>
                    </CardContent>
                </CardPanel>

                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Deuda</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-red-400 tabular-nums"
                        >
                            {{ formatCurrency(card.debt ?? 0) }}
                        </p>
                    </CardContent>
                </CardPanel>

                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Cupo disponible</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-400 tabular-nums"
                        >
                            {{ formatCurrency(card.available_credit ?? 0) }}
                        </p>
                    </CardContent>
                </CardPanel>

                <CardPanel class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Próximo pago</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <template v-if="nextPending">
                            <p
                                class="font-mono text-3xl font-bold text-amber-400 tabular-nums"
                            >
                                {{
                                    formatCurrency(
                                        nextPending.pending_amount ?? 0,
                                    )
                                }}
                            </p>
                            <p class="mt-1 text-xs text-zinc-500">
                                vence el {{ formatDay(nextPending.due_date) }}
                            </p>
                        </template>
                        <p v-else class="text-sm text-zinc-600">Sin adeudo</p>
                    </CardContent>
                </CardPanel>
            </div>

            <div
                v-if="statements.length === 0"
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-8 text-center text-zinc-500"
            >
                Aún no hay estados de cuenta para esta tarjeta.
            </div>

            <div v-else class="space-y-4">
                <CardPanel
                    v-for="statement in statements"
                    :key="statement.id"
                    class="border-zinc-800 bg-zinc-900 text-zinc-100"
                >
                    <Collapsible v-model:open="openStates[statement.id]">
                        <CollapsibleTrigger class="w-full">
                            <div
                                class="flex items-center justify-between gap-4 p-4 text-left"
                            >
                                <div>
                                    <p class="font-medium text-zinc-200">
                                        {{ formatDay(statement.period_start) }}
                                        —
                                        {{ formatDay(statement.period_end) }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        Corte
                                        {{ formatDay(statement.cut_date) }} ·
                                        Límite de pago
                                        {{ formatDay(statement.due_date) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p
                                            class="font-mono text-lg font-bold text-zinc-100 tabular-nums"
                                        >
                                            {{
                                                formatCurrency(
                                                    statement.total ?? 0,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="text-xs"
                                            :class="
                                                statement.is_settled
                                                    ? 'text-emerald-400'
                                                    : 'text-amber-400'
                                            "
                                        >
                                            {{
                                                statement.is_settled
                                                    ? 'Liquidado'
                                                    : `Pendiente ${formatCurrency(
                                                          statement.pending_amount ??
                                                              0,
                                                      )}`
                                            }}
                                        </p>
                                    </div>
                                    <Badge
                                        :variant="
                                            statement.is_settled
                                                ? 'outline'
                                                : 'destructive'
                                        "
                                    >
                                        {{
                                            statement.is_settled
                                                ? 'Liquidado'
                                                : 'Pendiente'
                                        }}
                                    </Badge>
                                    <ChevronDown
                                        class="h-4 w-4 text-zinc-500 transition-transform"
                                        :class="{
                                            'rotate-180':
                                                openStates[statement.id],
                                        }"
                                    />
                                </div>
                            </div>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <div class="space-y-5 border-t border-zinc-800 p-4">
                                <div
                                    v-if="
                                        statement.charges &&
                                        statement.charges.length > 0
                                    "
                                >
                                    <h4
                                        class="mb-2 text-sm font-medium text-zinc-400"
                                    >
                                        Cargos del corte
                                    </h4>
                                    <div
                                        class="overflow-x-auto rounded-lg border border-zinc-800"
                                    >
                                        <Table>
                                            <TableHeader class="bg-zinc-900/50">
                                                <TableRow
                                                    class="border-zinc-800 hover:bg-transparent"
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
                                                        class="font-medium text-zinc-400"
                                                        >Fecha</TableHead
                                                    >
                                                    <TableHead
                                                        class="text-right font-medium text-zinc-400"
                                                        >Monto</TableHead
                                                    >
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow
                                                    v-for="charge in statement.charges"
                                                    :key="charge.id"
                                                    class="border-zinc-800"
                                                >
                                                    <TableCell
                                                        class="font-medium text-zinc-200"
                                                        >{{
                                                            charge.description
                                                        }}</TableCell
                                                    >
                                                    <TableCell
                                                        class="text-zinc-400"
                                                        >{{
                                                            charge.category
                                                                ?.name ?? '—'
                                                        }}</TableCell
                                                    >
                                                    <TableCell
                                                        class="text-zinc-400"
                                                        >{{
                                                            formatDay(
                                                                charge.date,
                                                            )
                                                        }}</TableCell
                                                    >
                                                    <TableCell
                                                        class="text-right font-mono text-red-400 tabular-nums"
                                                    >
                                                        -{{
                                                            formatCurrency(
                                                                charge.amount,
                                                            )
                                                        }}
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        statement.installments &&
                                        statement.installments.length > 0
                                    "
                                >
                                    <h4
                                        class="mb-2 text-sm font-medium text-zinc-400"
                                    >
                                        Parcialidades a meses
                                    </h4>
                                    <div
                                        class="overflow-x-auto rounded-lg border border-zinc-800"
                                    >
                                        <Table>
                                            <TableHeader class="bg-zinc-900/50">
                                                <TableRow
                                                    class="border-zinc-800 hover:bg-transparent"
                                                >
                                                    <TableHead
                                                        class="font-medium text-zinc-400"
                                                        >Compra</TableHead
                                                    >
                                                    <TableHead
                                                        class="font-medium text-zinc-400"
                                                        >Parcialidad</TableHead
                                                    >
                                                    <TableHead
                                                        class="text-right font-medium text-zinc-400"
                                                        >Monto</TableHead
                                                    >
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow
                                                    v-for="installment in statement.installments"
                                                    :key="installment.id"
                                                    class="border-zinc-800"
                                                >
                                                    <TableCell
                                                        class="font-medium text-zinc-200"
                                                        >{{
                                                            installment.expense
                                                                ?.description ??
                                                            '—'
                                                        }}</TableCell
                                                    >
                                                    <TableCell
                                                        class="text-zinc-400"
                                                    >
                                                        {{ installment.number }}
                                                        /
                                                        {{
                                                            installment.expense
                                                                ?.installment_count ??
                                                            installment.number
                                                        }}
                                                    </TableCell>
                                                    <TableCell
                                                        class="text-right font-mono text-red-400 tabular-nums"
                                                    >
                                                        -{{
                                                            formatCurrency(
                                                                installment.amount,
                                                            )
                                                        }}
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-2 border-t border-zinc-800 pt-4 text-sm"
                                >
                                    <div
                                        class="flex justify-between text-zinc-400"
                                    >
                                        <span>Cargos</span>
                                        <span class="font-mono tabular-nums">{{
                                            formatCurrency(
                                                sum(statement.charges ?? []),
                                            )
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between text-zinc-400"
                                    >
                                        <span>Parcialidades</span>
                                        <span class="font-mono tabular-nums">{{
                                            formatCurrency(
                                                sum(
                                                    statement.installments ??
                                                        [],
                                                ),
                                            )
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between text-zinc-200"
                                    >
                                        <span class="font-medium"
                                            >Total del corte</span
                                        >
                                        <span
                                            class="font-mono font-bold tabular-nums"
                                            >{{
                                                formatCurrency(
                                                    statement.total ?? 0,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between text-zinc-400"
                                    >
                                        <span>Pagado</span>
                                        <span
                                            class="font-mono text-emerald-400 tabular-nums"
                                            >{{
                                                formatCurrency(
                                                    statement.paid_amount ?? 0,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between text-zinc-400"
                                    >
                                        <span>Pendiente</span>
                                        <span
                                            class="font-mono tabular-nums"
                                            :class="
                                                (statement.pending_amount ??
                                                    0) > 0
                                                    ? 'text-amber-400'
                                                    : 'text-zinc-400'
                                            "
                                            >{{
                                                formatCurrency(
                                                    statement.pending_amount ??
                                                        0,
                                                )
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>
                </CardPanel>
            </div>
        </div>
    </AppLayout>
</template>
