<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Subscription } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const { formatCurrency, formatDate } = useFormatter();

defineProps<{
    subscriptions: Subscription[];
    totals: {
        active_count: number;
        monthly_cost: number;
        annual_projection: number;
        paused_count: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Suscripciones', href: '/subscriptions' },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    amount: '',
    next_billing_date: '',
    is_active: '1',
});

const openModal = (sub?: Subscription) => {
    if (sub) {
        isEditing.value = true;
        editingId.value = sub.id;
        form.name = sub.name;
        form.amount = sub.amount.toString();
        form.next_billing_date = sub.next_billing_date;
        form.is_active = sub.is_active ? '1' : '0';
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
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
        amount: form.amount,
        next_billing_date: form.next_billing_date,
        is_active: form.is_active === '1'
    };

    if (isEditing.value && editingId.value) {
        router.put(`/subscriptions/${editingId.value}`, payload, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post('/subscriptions', payload, {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteSubscription = (id: number) => {
    if (confirm('¿Estás seguro de eliminar esta suscripción?')) {
        router.delete(`/subscriptions/${id}`);
    }
};
</script>

<template>

    <Head title="Suscripciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 text-zinc-100 p-8 font-sans">

            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">Pagos Fijos y Suscripciones</h1>
                    <p class="text-zinc-400 mt-1">Controla tus servicios mensuales y anuales.</p>
                </div>
                <Button @click="openModal()" class="bg-zinc-100 text-zinc-900 hover:bg-zinc-200">
                    + Nueva Suscripción
                </Button>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Servicios Activos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-zinc-100">{{ totals.active_count }}</p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Gasto Mensual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-red-400">
                            -{{ formatCurrency(totals.monthly_cost) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Proyección Anual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-red-400/80">
                            -{{ formatCurrency(totals.annual_projection) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400">Servicios Pausados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-zinc-500">{{ totals.paused_count }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-zinc-900/50 hover:bg-zinc-900/50 border-zinc-800">
                                <TableRow class="border-zinc-800 hover:bg-transparent">
                                    <TableHead class="text-zinc-400 font-medium">Servicio</TableHead>
                                    <TableHead class="text-zinc-400 font-medium">Próximo Cobro</TableHead>
                                    <TableHead class="text-zinc-400 font-medium">Estado</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Monto</TableHead>
                                    <TableHead class="text-zinc-400 font-medium text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="sub in subscriptions" :key="sub.id"
                                    :class="['border-zinc-800 transition-colors hover:bg-zinc-800/50', !sub.is_active ? 'opacity-50' : '']">
                                    <TableCell class="font-medium text-zinc-200">{{ sub.name }}</TableCell>
                                    <TableCell class="text-zinc-400">
                                        {{ formatDate(sub.next_billing_date) }}
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="sub.is_active"
                                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-inset ring-zinc-700">
                                            Activa
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center rounded-md bg-zinc-950 px-2 py-1 text-xs font-medium text-zinc-500 ring-1 ring-inset ring-zinc-800">
                                            Pausada
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right font-medium text-zinc-100">
                                        {{ formatCurrency(sub.amount) }}
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button variant="ghost" size="sm" @click="openModal(sub)"
                                            class="text-zinc-400 hover:text-zinc-200">
                                            Editar
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="deleteSubscription(sub.id)"
                                            class="text-red-500 hover:text-red-400 hover:bg-red-500/10">
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="subscriptions.length === 0"
                                    class="border-zinc-800 hover:bg-transparent">
                                    <TableCell colspan="5" class="py-8 text-center text-zinc-500">
                                        No tienes suscripciones registradas.
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
                        <DialogTitle>{{ isEditing ? 'Editar Suscripción' : 'Nueva Suscripción' }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Ingresa los detalles del servicio recurrente.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-zinc-400">Nombre del Servicio</Label>
                            <Input id="name" v-model="form.name" required
                                class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Netflix, Hetzner" />
                            <span v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="amount" class="text-zinc-400">Costo Mensual</Label>
                                <Input id="amount" v-model="form.amount" type="number" step="0.01" min="0" required
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="0.00" />
                                <span v-if="form.errors.amount" class="text-red-500 text-xs">{{ form.errors.amount
                                    }}</span>
                            </div>
                            <div class="space-y-2">
                                <Label for="next_billing_date" class="text-zinc-400">Próximo Cobro</Label>
                                <Input id="next_billing_date" v-model="form.next_billing_date" type="date" required
                                    class="bg-zinc-900 border-zinc-800 text-zinc-100 focus-visible:ring-zinc-500 scheme-dark" />
                                <span v-if="form.errors.next_billing_date" class="text-red-500 text-xs">{{
                                    form.errors.next_billing_date }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-zinc-400">Estado de la suscripción</Label>
                            <Select v-model="form.is_active" required>
                                <SelectTrigger class="bg-zinc-900 border-zinc-800 text-zinc-100 focus:ring-zinc-500">
                                    <SelectValue placeholder="Selecciona el estado" />
                                </SelectTrigger>
                                <SelectContent class="bg-zinc-950 border-zinc-800 text-zinc-100">
                                    <SelectGroup>
                                        <SelectItem value="1" class="focus:bg-zinc-800 focus:text-zinc-100">Activa
                                        </SelectItem>
                                        <SelectItem value="0" class="focus:bg-zinc-800 focus:text-zinc-100">Pausada /
                                            Cancelada</SelectItem>
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