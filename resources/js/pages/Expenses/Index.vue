<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Expense, Category } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';

const { formatCurrency, formatDate } = useFormatter();

const props = defineProps<{
    expenses: {
        data: Expense[];
        links: any[];
    };
    categories: Category[];
    totals: {
        total_amount: number;
        transaction_count: number;
        average_expense: number;
        biggest_expense: number;
    };
    filters: {
        search?: string;
        category_id?: string;
        date_filter?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Gastos', href: '/expenses' },
];

const activeFilters = ref({
    search: props.filters?.search || '',
    category_id: props.filters?.category_id || 'all',
    date_filter: props.filters?.date_filter || 'this_month',
});

let timeoutId: ReturnType<typeof setTimeout>;

// Observamos los cambios en los filtros para recargar los datos
watch(activeFilters, (newValues) => {
    clearTimeout(timeoutId);
    // Un pequeño debounce para no hacer spam al backend si escriben rápido
    timeoutId = setTimeout(() => {
        router.get('/expenses', newValues, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
             onBefore: () => {
                clearTimeout(timeoutId);
             }
        });
    }, 300);
}, { deep: true });

const refreshData = () => {
    router.reload({ only: ['expenses', 'totals'] });
};

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    category_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const openModal = (expense?: Expense) => {
    if (expense) {
        isEditing.value = true;
        editingId.value = expense.id;
        form.category_id = expense.category_id.toString();
        form.amount = expense.amount.toString();
        form.description = expense.description;
        form.date = expense.date;
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
        form.put(`/expenses/${editingId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/expenses', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteExpense = (id: number) => {
    if (confirm('¿Estás seguro de eliminar este gasto?')) {
        router.delete(`/expenses/${id}`);
    }
};
</script>

<template>

    <Head title="Historial de Gastos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 text-zinc-100 p-8 font-sans">

            <header class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">Historial de Gastos</h1>
                    <p class="text-zinc-400 mt-1">Auditoría completa de salidas de dinero e historial.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="refreshData"
                        class="border-zinc-800 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Actualizar
                    </Button>
                    <Button @click="openModal()" class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                        + Nuevo Gasto
                    </Button>
                </div>
            </header>

            <div
                class="flex flex-col md:flex-row gap-4 mb-8 p-4 border border-zinc-800 rounded-xl bg-zinc-900/50 items-center">
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-zinc-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <Input v-model="activeFilters.search" placeholder="Buscar gastos..."
                        class="pl-10 bg-zinc-950 border-zinc-800 focus-visible:ring-zinc-500 text-zinc-200" />
                </div>

                <Select v-model="activeFilters.category_id">
                    <SelectTrigger
                        class="w-full md:w-50 bg-zinc-950 border-zinc-800 focus:ring-zinc-500 text-zinc-200">
                        <SelectValue placeholder="Todas las Categorías" />
                    </SelectTrigger>
                    <SelectContent class="bg-zinc-950 border-zinc-800 text-zinc-100">
                        <SelectGroup>
                            <SelectItem value="all" class="focus:bg-zinc-800 focus:text-zinc-100">Todas las Categorías
                            </SelectItem>
                            <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id.toString()"
                                class="focus:bg-zinc-800 focus:text-zinc-100">
                                {{ cat.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <Select v-model="activeFilters.date_filter">
                    <SelectTrigger
                        class="w-full md:w-50 bg-zinc-950 border-zinc-800 focus:ring-zinc-500 text-zinc-200">
                        <SelectValue placeholder="Periodo" />
                    </SelectTrigger>
                    <SelectContent class="bg-zinc-950 border-zinc-800 text-zinc-100">
                        <SelectGroup>
                            <SelectItem value="this_month" class="focus:bg-zinc-800 focus:text-zinc-100">Este mes
                            </SelectItem>
                            <SelectItem value="last_month" class="focus:bg-zinc-800 focus:text-zinc-100">Mes pasado
                            </SelectItem>
                            <SelectItem value="this_year" class="focus:bg-zinc-800 focus:text-zinc-100">Este año
                            </SelectItem>
                            <SelectItem value="all_time" class="focus:bg-zinc-800 focus:text-zinc-100">Todo el tiempo
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Total Filtrado</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-red-500">
                            -{{ formatCurrency(totals.total_amount) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Operaciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-zinc-100">{{ totals.transaction_count }}</p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Promedio por Gasto</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-red-400/80">
                            -{{ formatCurrency(totals.average_expense) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Gasto Mayor</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-red-600">
                            -{{ formatCurrency(totals.biggest_expense) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-zinc-900/50 hover:bg-zinc-900/50 border-zinc-800">
                                <TableRow class="border-zinc-800 hover:bg-transparent">
                                    <TableHead class="text-zinc-400 font-medium">Fecha</TableHead>
                                    <TableHead class="text-zinc-400 font-medium">Descripción</TableHead>
                                    <TableHead class="text-zinc-400 font-medium">Categoría</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Monto</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="expense in expenses.data" :key="expense.id"
                                    class="border-zinc-800 hover:bg-zinc-800/50 transition-colors">
                                    <TableCell class="whitespace-nowrap text-zinc-400">{{ formatDate(expense.date) }}
                                    </TableCell>
                                    <TableCell class="font-medium text-zinc-200">{{ expense.description }}</TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-inset ring-zinc-700">
                                            {{ expense.category.name }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right text-red-400 font-medium">
                                        -{{ formatCurrency(expense.amount) }}
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button variant="ghost" size="sm" @click="openModal(expense)"
                                            class="text-zinc-400 hover:text-zinc-200">
                                            Editar
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="deleteExpense(expense.id)"
                                            class="text-red-500 hover:text-red-400 hover:bg-red-500/10">
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="expenses.data.length === 0"
                                    class="border-zinc-800 hover:bg-transparent">
                                    <TableCell colspan="5" class="py-8 text-center text-zinc-500">
                                        No se encontraron gastos con estos filtros.
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
                        <DialogTitle>{{ isEditing ? 'Editar Gasto' : 'Nuevo Gasto' }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Ingresa los detalles de tu salida de dinero.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="description" class="text-zinc-400">Descripción</Label>
                            <Input id="description" v-model="form.description" required
                                class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Tacos de la esquina" />
                            <span v-if="form.errors.description" class="text-red-500 text-xs">{{ form.errors.description
                                }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="amount" class="text-zinc-400">Monto</Label>
                                <Input id="amount" v-model="form.amount" type="number" step="0.01" min="0.01" required
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="0.00" />
                                <span v-if="form.errors.amount" class="text-red-500 text-xs">{{ form.errors.amount
                                    }}</span>
                            </div>
                            <div class="space-y-2">
                                <Label for="date" class="text-zinc-400">Fecha</Label>
                                <Input id="date" v-model="form.date" type="date" required
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500 scheme-dark" />
                                <span v-if="form.errors.date" class="text-red-500 text-xs">{{ form.errors.date }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-zinc-400">Categoría</Label>
                            <Select v-model="form.category_id" required>
                                <SelectTrigger class="bg-zinc-900 border-zinc-800 text-zinc-100 focus:ring-zinc-500">
                                    <SelectValue placeholder="Selecciona una categoría" />
                                </SelectTrigger>
                                <SelectContent class="bg-zinc-950 border-zinc-800 text-zinc-100">
                                    <SelectGroup>
                                        <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id.toString()"
                                            class="focus:bg-zinc-800 focus:text-zinc-100">
                                            {{ cat.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.category_id" class="text-red-500 text-xs">{{ form.errors.category_id
                                }}</span>
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