<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Category } from '@/types/finances';

// Importaciones de shadcn-vue
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineProps<{
    categories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Categorías', href: '/categories' },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    type: 'expense',
    is_active: '1',
});

const openModal = (category?: Category) => {
    if (category) {
        isEditing.value = true;
        editingId.value = category.id;
        form.name = category.name;
        form.type = category.type;
        form.is_active = category.is_active ? '1' : '0';
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.type = 'expense';
        form.is_active = '1';
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
    const payload = {
        name: form.name,
        type: form.type,
        is_active: form.is_active === '1'
    };

    if (isEditing.value && editingId.value) {
        router.put(`/categories/${editingId.value}`, payload, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post('/categories', payload, {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleActive = (category: Category) => {
    router.put(`/categories/${category.id}`, {
        name: category.name,
        type: category.type,
        is_active: !category.is_active
    });
};
</script>

<template>

    <Head title="Catálogo de Categorías" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 text-zinc-100 p-8 font-sans">

            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">Catálogo de Categorías</h1>
                    <p class="text-zinc-400 mt-1">El diccionario estricto que utiliza la IA para clasificar.</p>
                </div>
                <Button @click="openModal()" class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                    + Nueva Categoría
                </Button>
            </header>

            <Card class="border-zinc-800 bg-zinc-900 text-zinc-100 max-w-4xl">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-zinc-900/50 hover:bg-zinc-900/50 border-zinc-800">
                                <TableRow class="border-zinc-800 hover:bg-transparent">
                                    <TableHead class="text-zinc-400 font-medium">Nombre</TableHead>
                                    <TableHead class="text-zinc-400 font-medium">Tipo</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-center">Estado</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="category in categories" :key="category.id"
                                    :class="['border-zinc-800 transition-colors hover:bg-zinc-800/50', !category.is_active ? 'opacity-50' : '']">
                                    <TableCell class="font-medium text-zinc-200">{{ category.name }}</TableCell>
                                    <TableCell>
                                        <span v-if="category.type === 'expense'"
                                            class="inline-flex items-center rounded-md bg-red-500/10 px-2 py-1 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-500/20">
                                            Gasto
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                                            Ingreso
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <span v-if="category.is_active"
                                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-inset ring-zinc-700">
                                            Activa
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center rounded-md bg-zinc-950 px-2 py-1 text-xs font-medium text-zinc-500 ring-1 ring-inset ring-zinc-800">
                                            Inactiva
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button variant="outline" size="sm" @click="toggleActive(category)"
                                            class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white">
                                            {{ category.is_active ? 'Desactivar' : 'Activar' }}
                                        </Button>

                                        <Button variant="ghost" size="sm" @click="openModal(category)"
                                            class="text-zinc-400 hover:text-zinc-200">
                                            Editar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="categories.length === 0" class="border-zinc-800 hover:bg-transparent">
                                    <TableCell colspan="4" class="py-8 text-center text-zinc-500">
                                        No hay categorías configuradas.
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
                        <DialogTitle>{{ isEditing ? 'Editar Categoría' : 'Nueva Categoría' }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Añade conceptos para que la IA organice tu dinero.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-zinc-400">Nombre de la Categoría</Label>
                            <Input id="name" v-model="form.name" required
                                class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Comida, Sueldo, Gasolina" />
                            <span v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-zinc-400">Tipo de Movimiento</Label>
                            <Select v-model="form.type" required :disabled="isEditing">
                                <SelectTrigger
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus:ring-zinc-500 disabled:opacity-50">
                                    <SelectValue placeholder="Selecciona el tipo" />
                                </SelectTrigger>
                                <SelectContent class="bg-zinc-950 border-zinc-800 text-zinc-100">
                                    <SelectGroup>
                                        <SelectItem value="expense" class="focus:bg-zinc-800 focus:text-zinc-100">Gasto
                                            (Salida)</SelectItem>
                                        <SelectItem value="income" class="focus:bg-zinc-800 focus:text-zinc-100">Ingreso
                                            (Entrada)</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <span v-if="isEditing" class="text-xs text-zinc-500">El tipo no se puede cambiar una vez
                                creado para no afectar los registros históricos.</span>
                        </div>

                        <div class="space-y-2 pt-2">
                            <Label class="text-zinc-400">Estado</Label>
                            <Select v-model="form.is_active" required>
                                <SelectTrigger class="bg-zinc-900 border-zinc-800 text-zinc-100 focus:ring-zinc-500">
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent class="bg-zinc-950 border-zinc-800 text-zinc-100">
                                    <SelectGroup>
                                        <SelectItem value="1" class="focus:bg-zinc-800 focus:text-zinc-100">Activa (La
                                            IA la usará)</SelectItem>
                                        <SelectItem value="0" class="focus:bg-zinc-800 focus:text-zinc-100">Inactiva
                                            (Oculta para la IA)</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
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