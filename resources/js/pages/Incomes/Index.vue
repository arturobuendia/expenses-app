<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import type { BreadcrumbItem } from '@/types';
import type { Category, Income } from '@/types/finances';
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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';

const { formatCurrency, formatDate } = useFormatter();

const props = defineProps<{
    incomes: {
        data: Income[];
        links: any[];
    };
    categories: Category[];
    vaults: { id: number; name: string }[];
    totals: {
        total_amount: number;
        transaction_count: number;
        average_income: number;
        biggest_income: number;
    };
    filters: {
        search?: string;
        category_id?: string;
        date_filter?: string;
    };
    open_create?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Ingresos', href: '/incomes' },
];

// --- Lógica de Filtros ---
const activeFilters = ref({
    search: props.filters?.search || '',
    category_id: props.filters?.category_id || 'all',
    date_filter: props.filters?.date_filter || 'this_month',
});

let timeoutId: ReturnType<typeof setTimeout>;

watch(
    activeFilters,
    (newValues) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            router.get('/incomes', newValues, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);
    },
    { deep: true },
);

const refreshData = () => {
    router.reload({ only: ['incomes', 'totals'] });
};
// ---------------------------------

// Lógica del Modal
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    category_id: '',
    vault_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const openModal = (income?: Income) => {
    if (income) {
        isEditing.value = true;
        editingId.value = income.id;
        form.category_id = income.category_id.toString();
        form.vault_id = income.vault_id ? income.vault_id.toString() : '';
        form.amount = income.amount.toString();
        form.description = income.description;
        form.date = income.date;
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

// Atajo desde el dashboard: /incomes?new=1 abre el formulario de inmediato.
onMounted(() => {
    if (props.open_create) {
        openModal();
    }
});

const submitForm = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/incomes/${editingId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/incomes', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteIncome = (id: number) => {
    if (confirm('¿Estás seguro de eliminar este ingreso?')) {
        router.delete(`/incomes/${id}`);
    }
};
</script>

<template>
    <Head title="Historial de Ingresos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header
                class="mb-6 flex flex-col items-start justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Historial de Ingresos
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Registro de entradas de dinero, sueldos y rendimientos.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        @click="refreshData"
                        class="border-zinc-800 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                    >
                        <svg
                            class="mr-2 h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                            />
                        </svg>
                        Actualizar
                    </Button>
                    <Button @click="openModal()"> + Nuevo Ingreso </Button>
                </div>
            </header>

            <div
                class="mb-8 flex flex-col items-center gap-4 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 md:flex-row"
            >
                <div class="relative w-full md:w-64">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                    >
                        <svg
                            class="h-4 w-4 text-zinc-500"
                            aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 20 20"
                        >
                            <path
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                            />
                        </svg>
                    </div>
                    <Input
                        v-model="activeFilters.search"
                        placeholder="Buscar ingresos..."
                        class="border-zinc-800 bg-zinc-950 pl-10 text-zinc-200 focus-visible:ring-zinc-500"
                    />
                </div>

                <Select v-model="activeFilters.category_id">
                    <SelectTrigger
                        class="w-full border-zinc-800 bg-zinc-950 text-zinc-200 focus:ring-zinc-500 md:w-50"
                    >
                        <SelectValue placeholder="Todas las Categorías" />
                    </SelectTrigger>
                    <SelectContent
                        class="border-zinc-800 bg-zinc-950 text-zinc-100"
                    >
                        <SelectGroup>
                            <SelectItem
                                value="all"
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                >Todas las Categorías</SelectItem
                            >
                            <SelectItem
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id.toString()"
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                            >
                                {{ cat.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <Select v-model="activeFilters.date_filter">
                    <SelectTrigger
                        class="w-full border-zinc-800 bg-zinc-950 text-zinc-200 focus:ring-zinc-500 md:w-50"
                    >
                        <SelectValue placeholder="Periodo" />
                    </SelectTrigger>
                    <SelectContent
                        class="border-zinc-800 bg-zinc-950 text-zinc-100"
                    >
                        <SelectGroup>
                            <SelectItem
                                value="this_month"
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                >Este mes</SelectItem
                            >
                            <SelectItem
                                value="last_month"
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                >Mes pasado</SelectItem
                            >
                            <SelectItem
                                value="this_year"
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                >Este año</SelectItem
                            >
                            <SelectItem
                                value="all_time"
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                >Todo el tiempo</SelectItem
                            >
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <div
                class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4"
            >
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Total Filtrado</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-500 tabular-nums"
                        >
                            +{{ formatCurrency(totals.total_amount) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Operaciones</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ totals.transaction_count }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Promedio por Ingreso</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-400/80 tabular-nums"
                        >
                            +{{ formatCurrency(totals.average_income) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Ingreso Mayor</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-600 tabular-nums"
                        >
                            +{{ formatCurrency(totals.biggest_income) }}
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
                                        >Descripción</TableHead
                                    >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Categoría</TableHead
                                    >
                                    <TableHead class="font-medium text-zinc-400"
                                        >Bóveda</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Monto</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Acciones</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="income in incomes.data"
                                    :key="income.id"
                                    class="border-zinc-800 transition-colors hover:bg-zinc-800/50"
                                >
                                    <TableCell
                                        class="whitespace-nowrap text-zinc-400"
                                        >{{
                                            formatDate(income.date)
                                        }}</TableCell
                                    >
                                    <TableCell
                                        class="font-medium text-zinc-200"
                                        >{{ income.description }}</TableCell
                                    >
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-zinc-700 ring-inset"
                                        >
                                            {{ income.category.name }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            v-if="income.vault"
                                            class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/20 ring-inset"
                                        >
                                            {{ income.vault.name }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs text-zinc-600"
                                            >Sin bóveda</span
                                        >
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium text-emerald-400 tabular-nums"
                                    >
                                        +{{ formatCurrency(income.amount) }}
                                    </TableCell>
                                    <TableCell class="space-x-2 text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openModal(income)"
                                            class="text-zinc-400 hover:text-zinc-200"
                                        >
                                            Editar
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteIncome(income.id)"
                                            class="text-red-500 hover:bg-red-500/10 hover:text-red-400"
                                        >
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="incomes.data.length === 0"
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="6"
                                        class="py-8 text-center text-zinc-500"
                                    >
                                        No se encontraron ingresos con estos
                                        filtros.
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
                            isEditing ? 'Editar Ingreso' : 'Nuevo Ingreso'
                        }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Registra una nueva entrada de dinero a tu capital.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="description" class="text-zinc-400"
                                >Descripción</Label
                            >
                            <Input
                                id="description"
                                v-model="form.description"
                                required
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Pago de proyecto, Rendimientos Didi"
                            />
                            <span
                                v-if="form.errors.description"
                                class="text-xs text-red-500"
                                >{{ form.errors.description }}</span
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-4">
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
                            <div class="space-y-2">
                                <Label for="date" class="text-zinc-400"
                                    >Fecha</Label
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
                        </div>

                        <div class="space-y-2">
                            <Label class="text-zinc-400">Categoría</Label>
                            <Select v-model="form.category_id" required>
                                <SelectTrigger
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus:ring-zinc-500"
                                >
                                    <SelectValue
                                        placeholder="Selecciona una categoría"
                                    />
                                </SelectTrigger>
                                <SelectContent
                                    class="border-zinc-800 bg-zinc-950 text-zinc-100"
                                >
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="cat in categories"
                                            :key="cat.id"
                                            :value="cat.id.toString()"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                        >
                                            {{ cat.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <span
                                v-if="form.errors.category_id"
                                class="text-xs text-red-500"
                                >{{ form.errors.category_id }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label class="text-zinc-400">Bóveda destino</Label>
                            <Select v-model="form.vault_id" required>
                                <SelectTrigger
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus:ring-zinc-500"
                                >
                                    <SelectValue
                                        placeholder="Selecciona una bóveda"
                                    />
                                </SelectTrigger>
                                <SelectContent
                                    class="border-zinc-800 bg-zinc-950 text-zinc-100"
                                >
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="vault in vaults"
                                            :key="vault.id"
                                            :value="vault.id.toString()"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                        >
                                            {{ vault.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="vaults.length === 0"
                                class="text-xs text-amber-400"
                            >
                                No tienes bóvedas. Crea una en "Bóvedas" antes
                                de registrar ingresos.
                            </p>
                            <span
                                v-if="form.errors.vault_id"
                                class="text-xs text-red-500"
                                >{{ form.errors.vault_id }}</span
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
