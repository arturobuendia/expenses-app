<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
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

const { formatCurrency, formatDate } = useFormatter();

defineProps<{
    loans: Loan[];
    totals: {
        pending_amount: number;
        pending_count: number;
        recovered_amount: number;
        recovered_count: number;
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
    debtor_name: '',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const openModal = (loan?: Loan) => {
    if (loan) {
        isEditing.value = true;
        editingId.value = loan.id;
        form.debtor_name = loan.debtor_name;
        form.amount = loan.amount.toString();
        form.description = loan.description || '';
        form.date = loan.date;
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
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
                        Cuentas por cobrar y dinero prestado.
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
                            {{ formatCurrency(totals.pending_amount) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Préstamos Activos</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ totals.pending_count }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Dinero Recuperado</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-400/80 tabular-nums"
                        >
                            {{ formatCurrency(totals.recovered_amount) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Deudas Saldadas</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-500 tabular-nums"
                        >
                            {{ totals.recovered_count }}
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
                                        >Deudor</TableHead
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
                                        >{{ loan.debtor_name }}</TableCell
                                    >
                                    <TableCell class="text-zinc-400"
                                        >{{
                                            loan.description || 'Sin concepto'
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium text-amber-400 tabular-nums"
                                    >
                                        {{ formatCurrency(loan.amount) }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <span
                                            v-if="loan.is_paid"
                                            class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/20 ring-inset"
                                        >
                                            Pagado
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-1 text-xs font-medium text-amber-400 ring-1 ring-amber-500/20 ring-inset"
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
                                        No hay préstamos registrados. ¡Dile a la
                                        IA por WhatsApp cuando prestes dinero!
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
                            Registra manualmente un dinero que te deben.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="debtor_name" class="text-zinc-400"
                                    >¿A quién le prestaste?</Label
                                >
                                <Input
                                    id="debtor_name"
                                    v-model="form.debtor_name"
                                    required
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="Ej. Juan Pérez"
                                />
                                <span
                                    v-if="form.errors.debtor_name"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.debtor_name }}</span
                                >
                            </div>
                            <div class="space-y-2">
                                <Label for="amount" class="text-zinc-400"
                                    >Monto</Label
                                >
                                <Input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="0.00"
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
