<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import type { BreadcrumbItem } from '@/types';
import type { Loan } from '@/types/finances';
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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const { formatCurrency, formatDate, toDateInput } = useFormatter();

defineProps<{
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

const markAsPaid = (id: number) => {
    router.patch(`/loans/${id}/pay`);
};

const deleteLoan = (id: number) => {
    if (confirm('¿Seguro que quieres borrar este registro?')) {
        router.delete(`/loans/${id}`);
    }
};
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
                                    v-for="loan in loans"
                                    :key="loan.id"
                                    :class="[
                                        'border-zinc-800 transition-colors hover:bg-zinc-800/50',
                                        loan.is_paid ? 'opacity-60' : '',
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
                                    <TableCell class="text-center">
                                        <span
                                            v-if="loan.is_paid"
                                            class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/20 ring-inset"
                                        >
                                            {{
                                                loan.direction === 'lent'
                                                    ? 'Cobrado'
                                                    : 'Pagado'
                                            }}
                                        </span>
                                        <span
                                            v-else
                                            :class="[
                                                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                                loan.direction === 'lent'
                                                    ? 'bg-amber-500/10 text-amber-400 ring-amber-500/20'
                                                    : 'bg-red-500/10 text-red-400 ring-red-500/20',
                                            ]"
                                        >
                                            Pendiente
                                        </span>
                                    </TableCell>
                                    <TableCell class="space-x-2 text-right">
                                        <Button
                                            v-if="!loan.is_paid"
                                            variant="outline"
                                            size="sm"
                                            @click="markAsPaid(loan.id)"
                                            class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                                        >
                                            Marcar Pagado
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
                                    v-if="loans.length === 0"
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="6"
                                        class="py-8 text-center text-zinc-500"
                                    >
                                        No hay préstamos registrados. Registra
                                        dinero que te deban o que tú debas.
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
        </div>
    </AppLayout>
</template>
