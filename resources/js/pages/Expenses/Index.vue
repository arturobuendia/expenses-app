<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import type { BreadcrumbItem } from '@/types';
import type { Expense, Category } from '@/types/finances';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const { formatCurrency, formatDate, toDateInput } = useFormatter();

const props = defineProps<{
    expenses: {
        data: Expense[];
        links: any[];
    };
    categories: Category[];
    vaults: { id: number; name: string }[];
    cards: { id: number; name: string }[];
    totals: {
        total_amount: number;
        transaction_count: number;
        average_expense: number;
        biggest_expense: number;
        transfer_amount: number;
        transfer_count: number;
    };
    filters: {
        search?: string;
        category_id?: string;
        date_filter?: string;
    };
    available_years: number[];
    open_create?: boolean;
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

// Meses en español para el submenú de "Mes específico" y la etiqueta del botón.
const MONTHS = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

const monthLabel = (year: number, month: number): string =>
    `${MONTHS[month - 1]} ${year}`;

const dateFilterLabel = computed(() => {
    const value = activeFilters.value.date_filter;

    if (/^\d{4}-\d{2}$/.test(value)) {
        const [year, month] = value.split('-').map(Number);
        return monthLabel(year, month);
    }

    const labels: Record<string, string> = {
        this_month: 'Este mes',
        last_month: 'Mes pasado',
        this_year: 'Este año',
        all_time: 'Todo el tiempo',
    };

    return labels[value] ?? 'Periodo';
});

const selectDateFilter = (value: string) => {
    activeFilters.value.date_filter = value;
};

let timeoutId: ReturnType<typeof setTimeout>;

// Observamos los cambios en los filtros para recargar los datos
watch(
    activeFilters,
    (newValues) => {
        clearTimeout(timeoutId);
        // Un pequeño debounce para no hacer spam al backend si escriben rápido
        timeoutId = setTimeout(() => {
            router.get('/expenses', newValues, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onBefore: () => {
                    clearTimeout(timeoutId);
                },
            });
        }, 300);
    },
    { deep: true },
);

const refreshData = () => {
    router.reload({ only: ['expenses', 'totals'] });
};

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    category_id: '',
    vault_id: 'none',
    card_id: 'none',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
    is_transfer: false,
    installment_count: '1',
});

const openModal = (expense?: Expense) => {
    if (expense) {
        isEditing.value = true;
        editingId.value = expense.id;
        form.category_id = expense.category_id.toString();
        form.vault_id = expense.vault_id ? expense.vault_id.toString() : 'none';
        form.card_id = expense.card_id ? expense.card_id.toString() : 'none';
        form.amount = expense.amount.toString();
        form.description = expense.description;
        form.date = toDateInput(expense.date);
        form.is_transfer = expense.is_transfer;
        form.installment_count = expense.installment_count.toString();
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.vault_id = 'none';
        form.card_id = 'none';
        form.is_transfer = false;
        form.installment_count = '1';
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

// Atajo desde el dashboard: /expenses?new=1 abre el formulario de inmediato.
onMounted(() => {
    if (props.open_create) {
        openModal();
    }
});

const submitForm = () => {
    const usesCard = form.card_id !== 'none';

    form.transform((data) => ({
        ...data,
        card_id: data.card_id === 'none' ? null : data.card_id,
        // Un cargo o sale de la bóveda o se debe en la tarjeta, nunca ambos.
        vault_id:
            data.vault_id === 'none' || (usesCard && !data.is_transfer)
                ? null
                : data.vault_id,
        // Una transferencia sin bóveda no tendría de dónde restar.
        is_transfer: data.vault_id !== 'none' && data.is_transfer,
        installment_count:
            usesCard && !data.is_transfer ? data.installment_count : '1',
    }));

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
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header
                class="mb-6 flex flex-col items-start justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Historial de Gastos
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Auditoría completa de salidas de dinero e historial.
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
                    <Button @click="openModal()"> + Nuevo Gasto </Button>
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
                        placeholder="Buscar gastos..."
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
                                >Todas las Categorías
                            </SelectItem>
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

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-full justify-between border-zinc-800 bg-zinc-950 px-3 text-zinc-200 hover:bg-zinc-800 hover:text-white md:w-50"
                        >
                            {{ dateFilterLabel }}
                            <ChevronDown class="ml-2 size-4 opacity-50" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="border-zinc-800 bg-zinc-950 text-zinc-100"
                    >
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                @select="selectDateFilter('this_month')"
                                >Este mes</DropdownMenuItem
                            >
                            <DropdownMenuItem
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                @select="selectDateFilter('last_month')"
                                >Mes pasado</DropdownMenuItem
                            >
                            <DropdownMenuItem
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                @select="selectDateFilter('this_year')"
                                >Este año</DropdownMenuItem
                            >
                            <DropdownMenuItem
                                class="focus:bg-zinc-800 focus:text-zinc-100"
                                @select="selectDateFilter('all_time')"
                                >Todo el tiempo</DropdownMenuItem
                            >
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger
                                class="focus:bg-zinc-800 focus:text-zinc-100 data-[state=open]:bg-zinc-800 data-[state=open]:text-zinc-100"
                                >Mes específico</DropdownMenuSubTrigger
                            >
                            <DropdownMenuSubContent
                                class="border-zinc-800 bg-zinc-950 text-zinc-100"
                            >
                                <DropdownMenuSub
                                    v-for="year in available_years"
                                    :key="year"
                                >
                                    <DropdownMenuSubTrigger
                                        class="focus:bg-zinc-800 focus:text-zinc-100 data-[state=open]:bg-zinc-800 data-[state=open]:text-zinc-100"
                                        >{{ year }}</DropdownMenuSubTrigger
                                    >
                                    <DropdownMenuSubContent
                                        class="border-zinc-800 bg-zinc-950 text-zinc-100"
                                    >
                                        <DropdownMenuItem
                                            v-for="(name, index) in MONTHS"
                                            :key="index"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                            @select="
                                                selectDateFilter(
                                                    `${year}-${String(
                                                        index + 1,
                                                    ).padStart(2, '0')}`,
                                                )
                                            "
                                            >{{ name }}</DropdownMenuItem
                                        >
                                    </DropdownMenuSubContent>
                                </DropdownMenuSub>
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                    </DropdownMenuContent>
                </DropdownMenu>
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
                            class="font-mono text-3xl font-bold text-red-500 tabular-nums"
                        >
                            -{{ formatCurrency(totals.total_amount) }}
                        </p>
                        <p
                            v-if="totals.transfer_count > 0"
                            class="mt-1 text-xs text-zinc-500"
                        >
                            +{{ formatCurrency(totals.transfer_amount) }} en
                            {{ totals.transfer_count }}
                            transferencia(s) sin contar como gasto
                        </p>
                        <p class="mt-1 text-xs text-zinc-600">
                            Es el total comprado. Las compras a MSI cuentan
                            completas aquí y prorrateadas en el dashboard.
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
                            >Promedio por Gasto</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-red-400/80 tabular-nums"
                        >
                            -{{ formatCurrency(totals.average_expense) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Gasto Mayor</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-red-600 tabular-nums"
                        >
                            -{{ formatCurrency(totals.biggest_expense) }}
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
                                        >Origen</TableHead
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
                                    v-for="expense in expenses.data"
                                    :key="expense.id"
                                    class="border-zinc-800 transition-colors hover:bg-zinc-800/50"
                                >
                                    <TableCell
                                        class="whitespace-nowrap text-zinc-400"
                                        >{{ formatDate(expense.date) }}
                                    </TableCell>
                                    <TableCell
                                        class="font-medium text-zinc-200"
                                        >{{ expense.description }}</TableCell
                                    >
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-zinc-700 ring-inset"
                                        >
                                            {{ expense.category.name }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            v-if="expense.card"
                                            class="inline-flex items-center rounded-md bg-violet-500/10 px-2 py-1 text-xs font-medium text-violet-300 ring-1 ring-violet-500/20 ring-inset"
                                        >
                                            {{ expense.card.name }}
                                        </span>
                                        <span
                                            v-else-if="expense.vault"
                                            class="inline-flex items-center rounded-md bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-300 ring-1 ring-zinc-700 ring-inset"
                                        >
                                            {{ expense.vault.name }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs text-zinc-600"
                                            >-</span
                                        >
                                        <span
                                            v-if="expense.is_transfer"
                                            class="ml-2 inline-flex items-center rounded-md bg-amber-500/10 px-2 py-1 text-xs font-medium text-amber-400 ring-1 ring-amber-500/20 ring-inset"
                                        >
                                            Transferencia
                                        </span>
                                        <span
                                            v-if="expense.installment_count > 1"
                                            class="ml-2 inline-flex items-center rounded-md bg-sky-500/10 px-2 py-1 text-xs font-medium text-sky-300 ring-1 ring-sky-500/20 ring-inset"
                                        >
                                            {{ expense.installment_count }} MSI
                                        </span>
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium text-red-400 tabular-nums"
                                    >
                                        -{{ formatCurrency(expense.amount) }}
                                    </TableCell>
                                    <TableCell class="space-x-2 text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openModal(expense)"
                                            class="text-zinc-400 hover:text-zinc-200"
                                        >
                                            Editar
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteExpense(expense.id)"
                                            class="text-red-500 hover:bg-red-500/10 hover:text-red-400"
                                        >
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="expenses.data.length === 0"
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="6"
                                        class="py-8 text-center text-zinc-500"
                                    >
                                        No se encontraron gastos con estos
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
                            isEditing ? 'Editar Gasto' : 'Nuevo Gasto'
                        }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Ingresa los detalles de tu salida de dinero.
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
                                placeholder="Ej. Tacos de la esquina"
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
                            <Label class="text-zinc-400"
                                >Restar de bóveda
                                <span class="text-xs text-zinc-600"
                                    >(Opcional)</span
                                ></Label
                            >
                            <Select v-model="form.vault_id">
                                <SelectTrigger
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus:ring-zinc-500"
                                >
                                    <SelectValue
                                        placeholder="No afecta ninguna bóveda"
                                    />
                                </SelectTrigger>
                                <SelectContent
                                    class="border-zinc-800 bg-zinc-950 text-zinc-100"
                                >
                                    <SelectGroup>
                                        <SelectItem
                                            value="none"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                        >
                                            No afecta bóveda (compra con
                                            tarjeta)
                                        </SelectItem>
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
                            <span
                                v-if="form.errors.vault_id"
                                class="text-xs text-red-500"
                                >{{ form.errors.vault_id }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label class="text-zinc-400"
                                >Tarjeta de crédito
                                <span class="text-xs text-zinc-600"
                                    >(Opcional)</span
                                ></Label
                            >
                            <Select v-model="form.card_id">
                                <SelectTrigger
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus:ring-zinc-500"
                                >
                                    <SelectValue
                                        placeholder="No se debe en tarjeta"
                                    />
                                </SelectTrigger>
                                <SelectContent
                                    class="border-zinc-800 bg-zinc-950 text-zinc-100"
                                >
                                    <SelectGroup>
                                        <SelectItem
                                            value="none"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                        >
                                            No se debe en tarjeta
                                        </SelectItem>
                                        <SelectItem
                                            v-for="card in cards"
                                            :key="card.id"
                                            :value="card.id.toString()"
                                            class="focus:bg-zinc-800 focus:text-zinc-100"
                                        >
                                            {{ card.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="
                                    form.card_id !== 'none' && !form.is_transfer
                                "
                                class="text-xs text-violet-300/80"
                            >
                                Este cargo cae en el corte de la tarjeta y no
                                sale de ninguna bóveda.
                            </p>
                            <span
                                v-if="form.errors.card_id"
                                class="text-xs text-red-500"
                                >{{ form.errors.card_id }}</span
                            >
                        </div>

                        <div
                            v-if="form.card_id !== 'none' && !form.is_transfer"
                            class="space-y-2"
                        >
                            <Label for="installment_count" class="text-zinc-400"
                                >Meses sin intereses</Label
                            >
                            <Input
                                id="installment_count"
                                v-model="form.installment_count"
                                type="number"
                                min="1"
                                max="48"
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="1"
                            />
                            <p class="text-xs text-zinc-500">
                                Con 1 se cobra completo en el corte. Con más, el
                                gasto se reparte en parcialidades mensuales y el
                                dashboard cuenta solo la del mes.
                            </p>
                            <span
                                v-if="form.errors.installment_count"
                                class="text-xs text-red-500"
                                >{{ form.errors.installment_count }}</span
                            >
                        </div>

                        <div
                            v-if="form.vault_id !== 'none'"
                            class="flex items-start gap-3 rounded-lg border border-zinc-800 bg-zinc-900/50 p-3"
                        >
                            <Checkbox
                                id="is_transfer"
                                v-model="form.is_transfer"
                                class="mt-0.5 border-zinc-600"
                            />
                            <div class="space-y-1">
                                <Label
                                    for="is_transfer"
                                    class="cursor-pointer text-sm font-medium text-zinc-200"
                                >
                                    Es pago de tarjeta o transferencia
                                </Label>
                                <p class="text-xs text-zinc-500">
                                    Resta de la bóveda pero no cuenta como gasto
                                    del mes.
                                </p>
                            </div>
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
