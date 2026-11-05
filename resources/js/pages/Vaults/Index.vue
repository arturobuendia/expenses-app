<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Vault } from '@/types/finances'; // Asegúrate de agregar daily_yield y monthly_yield a la interfaz en tu archivo de tipos
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

const { formatCurrency } = useFormatter();

defineProps<{
    vaults: (Vault & { daily_yield: number; monthly_yield: number })[];
    totals: {
        accounts: number;
        capital: number;
        daily_yield: number;
        monthly_yield: number;
    };
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
    initial_balance: '',
    annual_yield_rate: '',
    yield_cap: '',
});

const openModal = (vault?: Vault) => {
    if (vault) {
        isEditing.value = true;
        editingId.value = vault.id;
        form.name = vault.name;
        form.initial_balance = vault.initial_balance.toString();
        form.annual_yield_rate = vault.annual_yield_rate
            ? vault.annual_yield_rate.toString()
            : '';
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
    if (
        confirm(
            '¿Estás seguro de eliminar esta bóveda? Esto no eliminará tu historial de gastos/ingresos, solo la cuenta de capital.',
        )
    ) {
        router.delete(`/vaults/${id}`);
    }
};
</script>

<template>
    <Head title="Tus Bóvedas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header class="mb-8 flex items-end justify-between">
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Tus Bóvedas
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Gestiona tu capital, cuentas y topes de inversión.
                    </p>
                </div>
                <Button @click="openModal()"> + Nueva Bóveda </Button>
            </header>

            <div
                class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4"
            >
                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Total Bóvedas</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ totals.accounts }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Capital Total</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                        >
                            {{ formatCurrency(totals.capital) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Rendimiento Diario</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-400 tabular-nums"
                        >
                            +{{ formatCurrency(totals.daily_yield) }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 bg-zinc-900 text-zinc-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-zinc-400"
                            >Rendimiento Mensual</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-mono text-3xl font-bold text-emerald-400 tabular-nums"
                        >
                            +{{ formatCurrency(totals.monthly_yield) }}
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
                                        >Nombre</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Tasa / Tope</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Rend. Diario</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Rend. Mensual</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Saldo Inicial</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Saldo Actual</TableHead
                                    >
                                    <TableHead
                                        class="text-right font-medium text-zinc-400"
                                        >Acciones</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="vault in vaults"
                                    :key="vault.id"
                                    class="border-zinc-800 transition-colors hover:bg-zinc-800/50"
                                >
                                    <TableCell
                                        class="font-medium text-zinc-200"
                                        >{{ vault.name }}</TableCell
                                    >
                                    <TableCell class="text-right">
                                        <div class="text-sm text-zinc-300">
                                            {{
                                                vault.annual_yield_rate
                                                    ? `${vault.annual_yield_rate}%`
                                                    : '-'
                                            }}
                                        </div>
                                        <div
                                            class="font-mono text-xs text-zinc-500 tabular-nums"
                                        >
                                            {{
                                                vault.yield_cap
                                                    ? `Tope:
                                            ${formatCurrency(vault.yield_cap)}`
                                                    : 'Sin tope'
                                            }}
                                        </div>
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium text-emerald-400/80 tabular-nums"
                                    >
                                        {{
                                            vault.daily_yield > 0
                                                ? `+${formatCurrency(vault.daily_yield)}`
                                                : '-'
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium text-emerald-400 tabular-nums"
                                    >
                                        {{
                                            vault.monthly_yield > 0
                                                ? `+${formatCurrency(vault.monthly_yield)}`
                                                : '-'
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-medium text-zinc-500 tabular-nums"
                                    >
                                        {{
                                            formatCurrency(
                                                vault.initial_balance,
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono font-bold text-zinc-100 tabular-nums"
                                    >
                                        {{ formatCurrency(vault.balance) }}
                                    </TableCell>
                                    <TableCell class="space-x-2 text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openModal(vault)"
                                            class="text-zinc-400 hover:text-zinc-200"
                                        >
                                            Editar
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteVault(vault.id)"
                                            class="text-red-500 hover:bg-red-500/10 hover:text-red-400"
                                        >
                                            Borrar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="vaults.length === 0"
                                    class="border-zinc-800 hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="7"
                                        class="py-8 text-center text-zinc-500"
                                    >
                                        No tienes bóvedas registradas.
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
                            isEditing ? 'Editar Bóveda' : 'Nueva Bóveda'
                        }}</DialogTitle>
                        <DialogDescription class="text-zinc-400">
                            Ajusta el saldo o las tasas de tu cuenta de capital.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name" class="text-zinc-400"
                                >Nombre de la cuenta</Label
                            >
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="Ej. Didi, Nu, Efectivo"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-xs text-red-500"
                                >{{ form.errors.name }}</span
                            >
                        </div>

                        <div class="space-y-2">
                            <Label for="initial_balance" class="text-zinc-400"
                                >Saldo Inicial</Label
                            >
                            <Input
                                id="initial_balance"
                                v-model="form.initial_balance"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="border-zinc-800 bg-zinc-900 font-medium text-zinc-100 focus-visible:ring-zinc-500"
                                placeholder="0.00"
                            />
                            <p class="text-xs text-zinc-500">
                                Punto de partida. El saldo actual se calcula
                                sumando ingresos y restando los gastos marcados
                                para esta bóveda.
                            </p>
                            <span
                                v-if="form.errors.initial_balance"
                                class="text-xs text-red-500"
                                >{{ form.errors.initial_balance }}</span
                            >
                        </div>

                        <div
                            class="mt-4 grid grid-cols-2 gap-4 border-t border-zinc-800 pt-2"
                        >
                            <div class="space-y-2">
                                <Label
                                    for="annual_yield_rate"
                                    class="text-zinc-400"
                                    >Rendimiento Anual (%)
                                    <span class="text-xs text-zinc-600"
                                        >(Opcional)</span
                                    ></Label
                                >
                                <Input
                                    id="annual_yield_rate"
                                    v-model="form.annual_yield_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="Ej. 13.00"
                                />
                                <span
                                    v-if="form.errors.annual_yield_rate"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.annual_yield_rate }}</span
                                >
                            </div>
                            <div class="space-y-2">
                                <Label for="yield_cap" class="text-zinc-400"
                                    >Tope Inversión ($)
                                    <span class="text-xs text-zinc-600"
                                        >(Opcional)</span
                                    ></Label
                                >
                                <Input
                                    id="yield_cap"
                                    v-model="form.yield_cap"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="border-zinc-800 bg-zinc-900 text-zinc-100 focus-visible:ring-zinc-500"
                                    placeholder="Ej. 10000.00"
                                />
                                <span
                                    v-if="form.errors.yield_cap"
                                    class="text-xs text-red-500"
                                    >{{ form.errors.yield_cap }}</span
                                >
                            </div>
                        </div>

                        <DialogFooter class="mt-2 pt-4">
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
