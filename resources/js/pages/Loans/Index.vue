<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Loan } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const { formatCurrency, formatDate } = useFormatter();

defineProps<{
    loans: Loan[];
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
        <div class="min-h-screen bg-zinc-950 text-zinc-100 p-8 font-sans">

            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">Préstamos</h1>
                    <p class="text-zinc-400 mt-1">Cuentas por cobrar y dinero prestado.</p>
                </div>
                <Button @click="openModal()" class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                    + Nuevo Préstamo
                </Button>
            </header>

            <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-zinc-900/50 hover:bg-zinc-900/50 border-zinc-800">
                                <TableRow class="border-zinc-800 hover:bg-transparent">
                                    <TableHead class="text-zinc-400 font-medium">Fecha</TableHead>
                                    <TableHead class="text-zinc-400 font-medium">Deudor</TableHead>
                                    <TableHead class="text-zinc-400 font-medium">Concepto</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Monto</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-center">Estado</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="loan in loans" :key="loan.id"
                                    :class="['border-zinc-800 transition-colors hover:bg-zinc-800/50', loan.is_paid ? 'opacity-60' : '']">
                                    <TableCell class="whitespace-nowrap text-zinc-400">{{ formatDate(loan.date) }}
                                    </TableCell>
                                    <TableCell class="font-medium text-zinc-200">{{ loan.debtor_name }}</TableCell>
                                    <TableCell class="text-zinc-400">{{ loan.description || 'Sin concepto' }}
                                    </TableCell>
                                    <TableCell class="text-right text-amber-400 font-medium">
                                        {{ formatCurrency(loan.amount) }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <span v-if="loan.is_paid"
                                            class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                                            Pagado
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-1 text-xs font-medium text-amber-400 ring-1 ring-inset ring-amber-500/20">
                                            Pendiente
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button v-if="!loan.is_paid" variant="outline" size="sm"
                                            @click="markAsPaid(loan.id)"
                                            class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white">
                                            Marcar Pagado
                                        </Button>

                                        <Button variant="ghost" size="sm" @click="openModal(loan)"
                                            class="text-zinc-400 hover:text-zinc-200">
                                            Editar
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="deleteLoan(loan.id)"
                                            class="text-red-500 hover:text-red-400 hover:bg-red-500/10">
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="loans.length === 0" class="border-zinc-800 hover:bg-transparent">
                                    <TableCell colspan="6" class="py-8 text-center text-zinc-500">
                                        No hay préstamos registrados. ¡Dile a la IA por WhatsApp cuando prestes dinero!
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <Dialog v-model:open="isModalOpen">
                <DialogContent class="sm:max-w-106.25 bg-zinc-950 border-zinc-800 text-zinc-100">
                    <DialogHeader>
                        <DialogTitle>{{ isEditing ? 'Editar Préstamo' : 'Nuevo Préstamo' }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Registra manualmente un dinero que te deben.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="debtor_name" class="text-zinc-400">¿A quién le prestaste?</Label>
                                <Input id="debtor_name" v-model="form.debtor_name" required
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="Ej. Juan Pérez" />
                                <span v-if="form.errors.debtor_name" class="text-red-500 text-xs">{{
                                    form.errors.debtor_name }}</span>
                            </div>
                            <div class="space-y-2">
                                <Label for="amount" class="text-zinc-400">Monto</Label>
                                <Input id="amount" v-model="form.amount" type="number" step="0.01" min="0.01" required
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="0.00" />
                                <span v-if="form.errors.amount" class="text-red-500 text-xs">{{ form.errors.amount
                                    }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-zinc-400">Concepto (Opcional)</Label>
                            <Input id="description" v-model="form.description"
                                class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Para completar la cuenta de la cena" />
                            <span v-if="form.errors.description" class="text-red-500 text-xs">{{ form.errors.description
                                }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="date" class="text-zinc-400">Fecha del préstamo</Label>
                            <Input id="date" v-model="form.date" type="date" required
                                class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500 scheme-dark" />
                            <span v-if="form.errors.date" class="text-red-500 text-xs">{{ form.errors.date }}</span>
                        </div>

                        <DialogFooter class="pt-4 mt-2 border-t border-zinc-800">
                            <Button type="button" variant="ghost" @click="closeModal"
                                class="text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800">
                                Cancelar
                            </Button>
                            <Button type="submit" :disabled="form.processing"
                                class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                                {{ form.processing ? 'Guardando...' : 'Guardar' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

        </div>
    </AppLayout>
</template>