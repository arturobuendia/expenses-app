<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Vault } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const { formatCurrency } = useFormatter();

defineProps<{
    vaults: Vault[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Bóvedas', href: '/vaults' },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    balance: '',
    annual_yield_rate: '',
    yield_cap: '',
});

const openModal = (vault?: Vault) => {
    if (vault) {
        isEditing.value = true;
        editingId.value = vault.id;
        form.name = vault.name;
        form.balance = vault.balance.toString();
        form.annual_yield_rate = vault.annual_yield_rate ? vault.annual_yield_rate.toString() : '';
        form.yield_cap = vault.yield_cap ? vault.yield_cap.toString() : '';
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
    if (isEditing.value && editingId.value) {
        form.put(`/vaults/${editingId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/vaults', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteVault = (id: number) => {
    if (confirm('¿Estás seguro de eliminar esta bóveda? Esto no eliminará tu historial de gastos/ingresos, solo la cuenta de capital.')) {
        router.delete(`/vaults/${id}`);
    }
};
</script>

<template>
    <Head title="Tus Bóvedas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 text-zinc-100 p-8 font-sans">
            
            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">Tus Bóvedas</h1>
                    <p class="text-zinc-400 mt-1">Gestiona tu capital, cuentas y topes de inversión.</p>
                </div>
                <Button @click="openModal()" class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                    + Nueva Bóveda
                </Button>
            </header>

            <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-zinc-900/50 hover:bg-zinc-900/50 border-zinc-800">
                                <TableRow class="border-zinc-800 hover:bg-transparent">
                                    <TableHead class="text-zinc-400 font-medium">Nombre</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Rendimiento Anual</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Tope de Inversión</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Balance Actual</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="vault in vaults" :key="vault.id" class="border-zinc-800 hover:bg-zinc-800/50 transition-colors">
                                    <TableCell class="font-medium text-zinc-200">{{ vault.name }}</TableCell>
                                    <TableCell class="text-right text-zinc-400">
                                        {{ vault.annual_yield_rate ? `${vault.annual_yield_rate}%` : '-' }}
                                    </TableCell>
                                    <TableCell class="text-right text-zinc-400">
                                        {{ vault.yield_cap ? formatCurrency(vault.yield_cap) : 'Sin tope' }}
                                    </TableCell>
                                    <TableCell class="text-right font-bold text-zinc-100">
                                        {{ formatCurrency(vault.balance) }}
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button variant="ghost" size="sm" @click="openModal(vault)" class="text-zinc-400 hover:text-zinc-200">
                                            Editar
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="deleteVault(vault.id)" class="text-red-500 hover:text-red-400 hover:bg-red-500/10">
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="vaults.length === 0" class="border-zinc-800 hover:bg-transparent">
                                    <TableCell colspan="5" class="py-8 text-center text-zinc-500">
                                        No tienes bóvedas registradas.
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
                        <DialogTitle>{{ isEditing ? 'Editar Bóveda' : 'Nueva Bóveda' }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Ajusta el saldo o las tasas de tu cuenta de capital.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-zinc-400">Nombre de la cuenta</Label>
                            <Input id="name" v-model="form.name" required
                                class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500" 
                                placeholder="Ej. Didi, Nu, Efectivo" />
                            <span v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="balance" class="text-zinc-400">Balance Actual</Label>
                            <Input id="balance" v-model="form.balance" type="number" step="0.01" min="0" required
                                class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500 font-medium" 
                                placeholder="0.00" />
                            <span v-if="form.errors.balance" class="text-red-500 text-xs">{{ form.errors.balance }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-zinc-800 mt-4">
                            <div class="space-y-2">
                                <Label for="annual_yield_rate" class="text-zinc-400">Rendimiento Anual (%) <span class="text-xs text-zinc-600">(Opcional)</span></Label>
                                <Input id="annual_yield_rate" v-model="form.annual_yield_rate" type="number" step="0.01" min="0" max="100"
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500" 
                                    placeholder="Ej. 13.00" />
                                <span v-if="form.errors.annual_yield_rate" class="text-red-500 text-xs">{{ form.errors.annual_yield_rate }}</span>
                            </div>
                            <div class="space-y-2">
                                <Label for="yield_cap" class="text-zinc-400">Tope Inversión ($) <span class="text-xs text-zinc-600">(Opcional)</span></Label>
                                <Input id="yield_cap" v-model="form.yield_cap" type="number" step="0.01" min="0"
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500" 
                                    placeholder="Ej. 10000.00" />
                                <span v-if="form.errors.yield_cap" class="text-red-500 text-xs">{{ form.errors.yield_cap }}</span>
                            </div>
                        </div>

                        <DialogFooter class="pt-4 mt-2">
                            <Button type="button" variant="ghost" @click="closeModal" class="text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800">
                                Cancelar
                            </Button>
                            <Button type="submit" :disabled="form.processing" class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                                {{ form.processing ? 'Guardando...' : 'Guardar' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

        </div>
    </AppLayout>
</template>