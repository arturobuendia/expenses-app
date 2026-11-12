<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import type { BreadcrumbItem } from '@/types';
import type { Loan, LoanPayment } from '@/types/finances';
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
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const { formatCurrency, formatDate, toDateInput } = useFormatter();

const props = defineProps<{
    loans: Loan[];
    totals: {
        receivable_pending_amount: number;
        receivable_pending_count: number;
        payable_pending_amount: number;
        payable_pending_count: number;
        receivable_settled_amount: number;
        receivable_settled_count: number;
        payable_settled_amount: number;
        payable_settled_count: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Préstamos', href: '/loans' },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    counterparty_name: '',
    direction: 'lent',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const openModal = (loan?: Loan) => {
    if (loan) {
        isEditing.value = true;
        editingId.value = loan.id;
        form.counterparty_name = loan.counterparty_name;
        form.direction = loan.direction;
        form.amount = loan.amount.toString();
        form.description = loan.description || '';
        form.date = toDateInput(loan.date);
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.direction = 'lent';
        form.date = new Date().toISOString().split('T')[0];
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
        form.put(`/loans/${editingId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/loans', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteLoan = (id: number) => {
    if (confirm('¿Seguro que quieres borrar este registro?')) {
        router.delete(`/loans/${id}`);
    }
};

// --- Abonos (pagos parciales) ---

const isPaymentOpen = ref(false);
const paymentLoanId = ref<number | null>(null);
const paymentForm = useForm({
    amount: '',
    date: new Date().toISOString().split('T')[0],
    note: '',
});

const openPayment = (loan: Loan) => {
    paymentLoanId.value = loan.id;
    paymentForm.reset();
    paymentForm.amount = (loan.remaining_amount ?? loan.amount).toString();
    paymentForm.date = new Date().toISOString().split('T')[0];
    paymentForm.note = '';
    isPaymentOpen.value = true;
};

const closePayment = () => {
    isPaymentOpen.value = false;
    setTimeout(() => {
        paymentForm.reset();
        paymentForm.clearErrors();
    }, 200);
};

const submitPayment = () => {
    if (paymentLoanId.value) {
        paymentForm.post(`/loans/${paymentLoanId.value}/payments`, {
            onSuccess: () => closePayment(),
        });
    }
};

// --- Historial de abonos ---

const historyLoanId = ref<number | null>(null);
const isHistoryOpen = ref(false);

const historyLoan = computed(
    () => props.loans.find((loan) => loan.id === historyLoanId.value) ?? null,
);

const openHistory = (loan: Loan) => {
    historyLoanId.value = loan.id;
    isHistoryOpen.value = true;
};

const deletePayment = (payment: LoanPayment) => {
    if (!historyLoan.value) return;

    if (confirm('¿Eliminar este abono?')) {
        router.delete(`/loans/${historyLoan.value.id}/payments/${payment.id}`);
    }
};

// --- Helpers de presentación ---

const statusLabel = (loan: Loan) => {
    if (loan.is_settled) {
        return loan.direction === 'lent' ? 'Cobrado' : 'Pagado';
    }
    if ((loan.paid_amount ?? 0) > 0) {
        return 'Parcial';
    }
    return 'Pendiente';
};

const remainingOf = (loan: Loan) =>
    loan.remaining_amount ?? Number(loan.amount);

// --- Filtro por persona ---

const selectedName = ref<string>('all');

const uniqueNames = computed(() => {
    const names = props.loans.map((loan) => loan.counterparty_name);

    return [...new Set(names)].sort((a, b) => a.localeCompare(b));
});

const hasPersonFilter = computed(() => selectedName.value !== 'all');

const filteredLoans = computed(() => {
    if (!hasPersonFilter.value) {
        return props.loans;
    }

    return props.loans.filter(
        (loan) => loan.counterparty_name === selectedName.value,
    );
});

/**
 * Totales por persona sobre el saldo pendiente (remaining_amount), igual que
 * las tarjetas de totales: lo liquidado no cuenta como deuda activa.
 */
const personTotals = computed(() => {
    const pending = filteredLoans.value.filter((loan) => !loan.is_settled);

    const lent = pending.filter((loan) => loan.direction === 'lent');
    const borrowed = pending.filter((loan) => loan.direction === 'borrowed');

    const sumRemaining = (loans: Loan[]) =>
        loans.reduce((total, loan) => total + remainingOf(loan), 0);

    return {
        owedToMe: sumRemaining(lent),
        owedToMeCount: lent.length,
        iOwe: sumRemaining(borrowed),
        iOweCount: borrowed.length,
    };
});
</script>

<template>
    <Head title="Préstamos y Deudas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header class="mb-8 flex items-end justify-between">
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Préstamos
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Dinero que te deben y dinero que debes.
                    </p>
                </div>
                <Button @click="openModal()"> + Nuevo Préstamo </Button>
            </header>

            <div
                class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4"
            >
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Dinero por Cobrar</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-amber-400 tabular-nums"
                        >
                            {{
                                formatCurrency(totals.receivable_pending_amount)
                            }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ totals.receivable_pending_count }} préstamos
                            activos
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Dinero por Pagar</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-red-400 tabular-nums"
                        >
                            {{ formatCurrency(totals.payable_pending_amount) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ totals.payable_pending_count }} deudas activas
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Cobrado</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-400/80 tabular-nums"
                        >
                            {{
                                formatCurrency(totals.receivable_settled_amount)
                            }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ totals.receivable_settled_count }} liquidados
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Pagado</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-500 tabular-nums"
                        >
                            {{ formatCurrency(totals.payable_settled_amount) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ totals.payable_settled_count }} liquidados
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="mb-6 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <Label class="text-zinc-400">Persona</Label>
                    <Select v-model="selectedName">
                        <SelectTrigger
                            class="w-64 border-zinc-800 bg-zinc-900 text-zinc-100 focus:ring-zinc-500"
                        >
                            <SelectValue placeholder="Todas las personas" />
                        </SelectTrigger>
                        <SelectContent
                            class="border-zinc-800 bg-zinc-950 text-zinc-100"
                        >
                            <SelectGroup>
                                <SelectItem
                                    value="all"
                                    class="focus:bg-zinc-800 focus:text-zinc-100"
                                >
                                    Todas las personas
                                </SelectItem>
                                <SelectItem
                                    v-for="name in uniqueNames"
                                    :key="name"
                                    :value="name"
                                    class="focus:bg-zinc-800 focus:text-zinc-100"
                                >
                                    {{ name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div
                v-if="hasPersonFilter"
                class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2"
            >
                <Card class="border-amber-500/20 bg-amber-500/5 text-zinc-100">
                    <CardContent class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-400">
                                {{ selectedName }} te debe en total
                            </p>
                            <p
                                class="font-mono text-2xl font-bold text-amber-400 tabular-nums"
                            >
                                {{ formatCurrency(personTotals.owedToMe) }}
                            </p>
                        </div>
                        <p class="text-xs text-zinc-500">
                            {{ personTotals.owedToMeCount }} préstamos activos
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-red-500/20 bg-red-500/5 text-zinc-100">
                    <CardContent class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-400">
                                Le debes a {{ selectedName }} en total
                            </p>
                            <p
                                class="font-mono text-2xl font-bold text-red-400 tabular-nums"
                            >
                                {{ formatCurrency(personTotals.iOwe) }}
                            </p>
                        </div>
                        <p class="text-xs text-zinc-500">
                            {{ personTotals.iOweCount }} deudas activas
                        </p>
                    </CardContent>
                </Card>
            </div>

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
                                    <TableHead class="font-medium text-zinc-400"
                                        >Fecha</TableHead
                                    >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Persona</TableHead
                                    >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Concepto</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Monto</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Restante</TableHead
                                    >
                                    <TableHead
                                        class="text-center font-medium text-zinc-400"
                                        >Estado</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Acciones</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="loan in filteredLoans"
                                    :key="loan.id"
                                    :class="[
                                        'border-zinc-800 transition-colors hover:bg-zinc-800/50',
                                        loan.is_settled ? 'opacity-60' : '',
                                    ]"
                                >
                                    <TableCell
                                        class="whitespace-nowrap text-zinc-400"
                                        >{{ formatDate(loan.date) }}
                                    </TableCell>
                                    <TableCell
                                        class="font-medium text-zinc-200"
                                    >
                                        <div class="flex flex-col gap-1">
                                            <span>{{
                                                loan.counterparty_name
                                            }}</span>
                                            <span
                                                :class="[
                                                    'w-fit rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                                    loan.direction === 'lent'
                                                        ? 'bg-amber-500/10 text-amber-400 ring-amber-500/20'
                                                        : 'bg-red-500/10 text-red-400 ring-red-500/20',
                                                ]"
                                            >
                                                {{
                                                    loan.direction === 'lent'
                                                        ? 'Te debe'
                                                        : 'Le debes'
                                                }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-zinc-400"
                                        >{{
                                            loan.description || 'Sin concepto'
                                        }}
                                    </TableCell>
                                    <TableCell
                                        :class="[
                                            'text-right font-mono font-medium tabular-nums',
                                            loan.direction === 'lent'
                                                ? 'text-amber-400'
                                                : 'text-red-400',
                                        ]"
                                    >
                                        {{ formatCurrency(loan.amount) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div
                                            class="font-mono font-medium text-zinc-100 tabular-nums"
                                        >
                                            {{
                                                formatCurrency(
                                                    remainingOf(loan),
                                                )
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                !loan.is_settled &&
                                                (loan.paid_amount ?? 0) > 0
                                            "
                                            class="text-xs text-zinc-500"
                                        >
                                            Abonado
                                            {{
                                                formatCurrency(
                                                    loan.paid_amount ?? 0,
                                                )
                                            }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                                loan.is_settled
                                                    ? 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20'
                                                    : statusLabel(loan) ===
                                                        'Parcial'
                                                      ? 'bg-sky-500/10 text-sky-400 ring-sky-500/20'
                                                      : loan.direction ===
                                                          'lent'
                                                        ? 'bg-amber-500/10 text-amber-400 ring-amber-500/20'
                                                        : 'bg-red-500/10 text-red-400 ring-red-500/20',
                                            ]"
                                        >
                                            {{ statusLabel(loan) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="space-x-2 text-right">
                                        <Button
                                            v-if="!loan.is_settled"
                                            variant="outline"
                                            size="sm"
                                            @click="openPayment(loan)"
                                            class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                                        >
                                            Abonar
                                        </Button>

                                        <Button
                                            v-if="loan.payments?.length"
                                            variant="ghost"
                                            size="sm"
                                            @click="openHistory(loan)"
                                            class="text-zinc-400 hover:text-zinc-200"
                                        >
                                            Ver abonos
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openModal(loan)"
                                            class="text-zinc-400 hover:text-zinc-200"
                                        >
                                            Editar
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteLoan(loan.id)"
                                            class="text-red-500 hover:bg-red-500/10 hover:text-red-400"
                                        >
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="filteredLoans.length === 0"
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="7"
                                        class="py-8 text-center text-zinc-500"
                                    >
                                        <template v-if="hasPersonFilter">
                                            {{ selectedName }} no tiene
                                            préstamos registrados.
                                        </template>
                                        <template v-else>
                                            No hay préstamos registrados.
                                            Registra dinero que te deban o que
                                            tú debas.
                                        </template>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <Dialog v-model:open="isModalOpen">
                <DialogContent
                    class="border-zinc-800 bg-zinc-950 text-zinc-100 sm:max-w-106.25"
                >
                    <DialogHeader>
                        <DialogTitle>{{
                            isEditing ? 'Editar Préstamo' : 'Nuevo Préstamo'
                        }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            {{
                                form.direction === 'lent'
                                    ? 'Registra un dinero que te deben.'
                                    : 'Registra un dinero que tú debes.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label class="text-zinc-400">Dirección</Label>
                            <Select v-model="form.direction" required>
                                <SelectTrigger
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus:ring-zinc-500"
                                >
                                    <SelectValue
                                        placeholder="Selecciona una dirección"
                                    />
                                </SelectTrigger>
                                <SelectContent
                                    class="border-zinc-800 bg-zinc-950 text-zinc-100"
                                >
                                    <SelectGroup>
                                        <SelectItem
                                            value="lent"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                        >
                                            Me deben (yo presté)
                                        </SelectItem>
                                        <SelectItem
                                            value="borrowed"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                        >
                                            Debo (yo pedí prestado)
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <span
                                v-if="form.errors.direction"
                                class="text-xs text-red-500"
                                >{{ form.errors.direction }}</span
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label
                                    for="counterparty_name"
                                    class="text-zinc-400"
                                    >{{
                                        form.direction === 'lent'
                                            ? '¿A quién le prestaste?'
                                            : '¿A quién le debes?'
                                    }}</Label
                                >
                                <Input
                                    id="counterparty_name"
                                    v-model="form.counterparty_name"
                                    required
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    :placeholder="
                                        form.direction === 'lent'
                                            ? 'Ej. Juan Pérez'
                                            : 'Ej. Banco o Sofía'
                                    "
                                />
                                <span
                                    v-if="form.errors.counterparty_name"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.counterparty_name }}</span
                                >
                            </div>
                            <div class="space-y-2">
                                <Label for="amount" class="text-zinc-400"
                                    >Monto</Label
                                >
                                <CurrencyInput
                                    id="amount"
                                    v-model="form.amount"
                                    required
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="$0.00"
                                />
                                <span
                                    v-if="form.errors.amount"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.amount }}</span
                                >
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-zinc-400"
                                >Concepto (Opcional)</Label
                            >
                            <Input
                                id="description"
                                v-model="form.description"
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Para completar la cuenta de la cena"
                            />
                            <span
                                v-if="form.errors.description"
                                class="text-xs text-red-500"
                                >{{ form.errors.description }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label for="date" class="text-zinc-400"
                                >Fecha del préstamo</Label
                            >
                            <Input
                                id="date"
                                v-model="form.date"
                                type="date"
                                required
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 scheme-dark focus-visible:ring-zinc-500"
                            />
                            <span
                                v-if="form.errors.date"
                                class="text-xs text-red-500"
                                >{{ form.errors.date }}</span
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

            <Dialog v-model:open="isPaymentOpen">
                <DialogContent
                    class="border-zinc-800 bg-zinc-950 text-zinc-100 sm:max-w-md"
                >
                    <DialogHeader>
                        <DialogTitle>Registrar Abono</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Registra un pago parcial sobre el préstamo.
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        @submit.prevent="submitPayment"
                        class="space-y-4 py-4"
                    >
                        <div class="space-y-2">
                            <Label for="payment_amount" class="text-zinc-400"
                                >Monto del abono</Label
                            >
                            <CurrencyInput
                                id="payment_amount"
                                v-model="paymentForm.amount"
                                required
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="$0.00"
                            />
                            <span
                                v-if="paymentForm.errors.amount"
                                class="text-xs text-red-500"
                                >{{ paymentForm.errors.amount }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label for="payment_date" class="text-zinc-400"
                                >Fecha del abono</Label
                            >
                            <Input
                                id="payment_date"
                                v-model="paymentForm.date"
                                type="date"
                                required
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 scheme-dark focus-visible:ring-zinc-500"
                            />
                            <span
                                v-if="paymentForm.errors.date"
                                class="text-xs text-red-500"
                                >{{ paymentForm.errors.date }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label for="payment_note" class="text-zinc-400"
                                >Nota (Opcional)</Label
                            >
                            <Input
                                id="payment_note"
                                v-model="paymentForm.note"
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Primera parcialidad"
                            />
                            <span
                                v-if="paymentForm.errors.note"
                                class="text-xs text-red-500"
                                >{{ paymentForm.errors.note }}</span
                            >
                        </div>

                        <DialogFooter
                            class="mt-2 border-t border-zinc-800 pt-4"
                        >
                            <Button
                                type="button"
                                variant="ghost"
                                @click="closePayment"
                                class="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200"
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                :disabled="paymentForm.processing"
                            >
                                {{
                                    paymentForm.processing
                                        ? 'Guardando...'
                                        : 'Guardar abono'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isHistoryOpen">
                <DialogContent
                    class="border-zinc-800 bg-zinc-950 text-zinc-100 sm:max-w-md"
                >
                    <DialogHeader>
                        <DialogTitle>Abonos</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Historial de abonos de
                            {{ historyLoan?.counterparty_name }}.
                        </DialogDescription>
                    </DialogHeader>

                    <ScrollArea class="h-72">
                        <div class="space-y-2 py-4 pr-3">
                            <div
                                v-if="historyLoan?.payments?.length"
                                class="space-y-2"
                            >
                                <div
                                    v-for="payment in historyLoan.payments"
                                    :key="payment.id"
                                    class="flex items-center justify-between rounded-md border border-zinc-800 bg-zinc-900/50 px-3 py-2"
                                >
                                    <div>
                                        <div
                                            class="font-mono font-medium text-zinc-100 tabular-nums"
                                        >
                                            {{ formatCurrency(payment.amount) }}
                                        </div>
                                        <div class="text-xs text-zinc-500">
                                            {{ formatDate(payment.date) }}
                                            <template v-if="payment.note">
                                                · {{ payment.note }}
                                            </template>
                                        </div>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="deletePayment(payment)"
                                        class="text-red-500 hover:bg-red-500/10 hover:text-red-400"
                                    >
                                        Eliminar
                                    </Button>
                                </div>
                            </div>
                            <p
                                v-else
                                class="py-6 text-center text-sm text-zinc-500"
                            >
                                Sin abonos registrados.
                            </p>
                        </div>
                    </ScrollArea>

                    <DialogFooter class="border-t border-zinc-800 pt-4">
                        <Button
                            variant="ghost"
                            @click="isHistoryOpen = false"
                            class="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200"
                        >
                            Cerrar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
