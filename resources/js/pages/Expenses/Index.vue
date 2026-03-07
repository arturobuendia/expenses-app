<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Expense, Category } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

// Importaciones de shadcn-vue
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';

const { formatCurrency, formatDate } = useFormatter();

defineProps<{
    expenses: {
        data: Expense[];
        links: any[];
    };
    categories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Gastos', href: '/expenses' },
];

// Estado del Modal (Dialog)
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

// Formulario de Inertia
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
        form.category_id = expense.category_id.toString(); // shadcn Select usa strings
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
    }, 200); // Esperar a que termine la animación del Dialog
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
            
            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">Historial de Gastos</h1>
                    <p class="text-zinc-400 mt-1">Registro detallado de salidas de dinero.</p>
                </div>
                <Button @click="openModal()" class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                    + Nuevo Gasto
                </Button>
            </header>

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
                                <TableRow v-for="expense in expenses.data" :key="expense.id" class="border-zinc-800 hover:bg-zinc-800/50 transition-colors">
                                    <TableCell class="whitespace-nowrap text-zinc-400">{{ formatDate(expense.date) }}</TableCell>
                                    <TableCell class="font-medium text-zinc-200">{{ expense.description }}</TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-inset ring-zinc-700">
                                            {{ expense.category.name }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right text-red-400 font-medium">
                                        -{{ formatCurrency(expense.amount) }}
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button variant="ghost" size="sm" @click="openModal(expense)" class="text-zinc-400 hover:text-zinc-200">
                                            Editar
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="deleteExpense(expense.id)" class="text-red-500 hover:text-red-400 hover:bg-red-500/10">
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="expenses.data.length === 0" class="border-zinc-800 hover:bg-transparent">
                                    <TableCell colspan="5" class="py-8 text-center text-zinc-500">
                                        No hay gastos registrados.
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
                            <span v-if="form.errors.description" class="text-red-500 text-xs">{{ form.errors.description }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="amount" class="text-zinc-400">Monto</Label>
                                <Input id="amount" v-model="form.amount" type="number" step="0.01" min="0.01" required
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500" 
                                    placeholder="0.00" />
                                <span v-if="form.errors.amount" class="text-red-500 text-xs">{{ form.errors.amount }}</span>
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
                                        <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id.toString()" class="focus:bg-zinc-800 focus:text-zinc-100">
                                            {{ cat.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.category_id" class="text-red-500 text-xs">{{ form.errors.category_id }}</span>
                        </div>

                        <DialogFooter class="pt-4 mt-2 border-t border-zinc-800">
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