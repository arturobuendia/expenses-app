<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    ClipboardCopy,
    Eye,
    ListPlus,
    Save,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Category } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';

import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';

const { formatCurrency, formatDate } = useFormatter();

interface PreviewInstallment {
    number: number;
    amount: number;
    cut_date: string;
}

interface PreviewItem {
    index: number;
    date: string | null;
    description: string | null;
    amount: number | string | null;
    category: string | null;
    installment_count: number;
    errors: Record<string, string>;
    category_id?: number;
    installments?: PreviewInstallment[];
}

interface PreviewResult {
    card: { id: number; name: string };
    items: PreviewItem[];
    totals: { count: number; amount: number };
    has_errors: boolean;
}

const props = defineProps<{
    categories: Category[];
    cards: { id: number; name: string }[];
    preview?: PreviewResult | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Acciones por lote', href: '/batch-expenses' },
];

const form = useForm({
    card_id: '',
    json: '',
});

const page = usePage<{ flash?: { success?: string } }>();
const successMessage = computed(() => page.props.flash?.success ?? null);

const clientJsonError = ref<string | null>(null);
const copied = ref(false);

const previewedJson = ref('');
const previewedCardId = ref('');

const exampleJson = `[
  {
    "date": "2026-10-03",
    "description": "Suscripción Netflix",
    "amount": 219.0,
    "category": "Entretenimiento"
  },
  {
    "date": "2026-10-08",
    "description": "Lavadora",
    "amount": 7400.0,
    "category": "Hogar",
    "installment_count": 12
  }
]`;

async function copyToClipboard(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            return true;
        } catch {
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
}

const buildPrompt = (): string => {
    const categoryList = props.categories
        .map((category) => category.name)
        .join(', ');

    return `Convierte los gastos que te doy al final en un JSON para importar a mi app de finanzas. Responde ÚNICAMENTE con el JSON (una lista de objetos), sin texto, sin markdown y sin explicaciones.

Esquema exacto de cada objeto:
{
  "date": "YYYY-MM-DD",
  "description": "texto",
  "amount": 123.45,
  "category": "nombre de categoría",
  "installment_count": 1
}

Reglas:
- "date" es obligatorio, formato YYYY-MM-DD.
- "description" es obligatorio, máximo 255 caracteres.
- "amount" es obligatorio, un número mayor o igual a 0.01, sin símbolo de moneda.
- "category" es obligatorio y debe coincidir (sin importar mayúsculas) con una de estas categorías válidas: ${categoryList}.
- "installment_count" es opcional; si se omite vale 1. Úsalo solo para compras a meses sin intereses (MSI), con un entero entre 1 y 48.
- Todos los cargos van a una misma tarjeta de crédito: NO incluyas campo de tarjeta.
- Solo cargos y compras. Nunca pagos, transferencias ni abonos.

Mis gastos:
[PEGA AQUÍ TUS GASTOS]`;
};

const onCopyPrompt = async () => {
    const ok = await copyToClipboard(buildPrompt());

    if (ok) {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    }
};

const onPreview = () => {
    clientJsonError.value = null;

    let parsed: unknown;
    try {
        parsed = JSON.parse(form.json);
    } catch {
        clientJsonError.value = 'El JSON no es válido.';
        return;
    }

    if (!Array.isArray(parsed)) {
        clientJsonError.value =
            'El JSON debe ser una lista (array) de objetos.';
        return;
    }

    previewedJson.value = form.json;
    previewedCardId.value = form.card_id;
    form.post('/batch-expenses/preview', { preserveScroll: true });
};

const onStore = () => {
    form.post('/batch-expenses', { preserveScroll: true });
};

const isPreviewStale = computed(
    () =>
        previewedJson.value !== form.json ||
        previewedCardId.value !== form.card_id,
);

const canSave = computed(
    () => !!props.preview && !props.preview.has_errors && !isPreviewStale.value,
);
</script>

<template>
    <Head title="Acciones por lote" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-zinc-950 p-8 font-sans text-zinc-100">
            <header
                class="mb-6 flex flex-col items-start justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <h1
                        class="font-display text-3xl font-bold tracking-tight text-white"
                    >
                        Acciones por lote
                    </h1>
                    <p class="mt-1 text-zinc-400">
                        Pega un JSON con los cargos del estado de cuenta y
                        regístralos de un solo golpe.
                    </p>
                </div>
                <Button
                    variant="outline"
                    @click="onCopyPrompt"
                    class="border-zinc-800 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                >
                    <ClipboardCopy class="mr-2 h-4 w-4" />
                    {{ copied ? '¡Prompt copiado!' : 'Copiar prompt para IA' }}
                </Button>
            </header>

            <div
                v-if="successMessage"
                class="mb-6 flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
            >
                <CheckCircle2 class="h-5 w-5 shrink-0" />
                {{ successMessage }}
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="space-y-4">
                    <div
                        class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4"
                    >
                        <Label class="text-zinc-400">Tarjeta de crédito</Label>
                        <Select v-model="form.card_id">
                            <SelectTrigger
                                class="mt-2 w-full border-zinc-800 bg-zinc-950 text-zinc-200 focus:ring-zinc-500"
                            >
                                <SelectValue
                                    placeholder="Elige la tarjeta del lote"
                                />
                            </SelectTrigger>
                            <SelectContent
                                class="border-zinc-800 bg-zinc-950 text-zinc-100"
                            >
                                <SelectGroup>
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
                        <span
                            v-if="form.errors.card_id"
                            class="mt-1 block text-xs text-red-500"
                        >
                            {{ form.errors.card_id }}
                        </span>
                        <p class="mt-2 text-xs text-violet-300/80">
                            Todos los cargos del JSON se asignarán a esta
                            tarjeta. Solo se permiten cargos, no pagos ni
                            transferencias.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <Label for="json" class="text-zinc-400"
                                >JSON de cargos</Label
                            >
                            <span class="text-xs text-zinc-600"
                                >date · description · amount · category ·
                                installment_count?</span
                            >
                        </div>
                        <textarea
                            id="json"
                            v-model="form.json"
                            rows="14"
                            spellcheck="false"
                            class="mt-2 w-full resize-y rounded-lg border border-zinc-800 bg-zinc-950 p-3 font-mono text-sm text-zinc-200 placeholder:text-zinc-600 focus-visible:ring-1 focus-visible:ring-zinc-500 focus-visible:outline-none"
                            placeholder="Pega aquí el JSON..."
                        ></textarea>

                        <p
                            v-if="clientJsonError"
                            class="mt-2 text-xs text-red-500"
                        >
                            {{ clientJsonError }}
                        </p>

                        <div
                            v-if="form.errors.json"
                            class="mt-2 space-y-1 rounded-lg border border-red-500/30 bg-red-500/10 p-3 text-xs text-red-400"
                        >
                            <p
                                v-for="(message, i) in form.errors.json"
                                :key="i"
                            >
                                {{ message }}
                            </p>
                        </div>

                        <div class="mt-3 flex items-center gap-3">
                            <Button
                                type="button"
                                variant="secondary"
                                @click="form.json = exampleJson"
                                class="border-zinc-800 bg-zinc-800/60 text-zinc-300 hover:bg-zinc-800 hover:text-white"
                            >
                                Cargar ejemplo
                            </Button>
                            <Button
                                type="button"
                                @click="onPreview"
                                :disabled="form.processing"
                                class="ml-auto"
                            >
                                <Eye class="mr-2 h-4 w-4" />
                                {{
                                    form.processing
                                        ? 'Validando...'
                                        : 'Previsualizar'
                                }}
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div
                        v-if="!preview"
                        class="flex h-full min-h-60 flex-col items-center justify-center rounded-xl border border-dashed border-zinc-800 bg-zinc-900/30 p-8 text-center"
                    >
                        <ListPlus class="mb-3 h-8 w-8 text-zinc-700" />
                        <p class="text-sm text-zinc-500">
                            Pega un JSON y presiona
                            <span class="text-zinc-300">Previsualizar</span>
                            para ver cómo quedará cada cargo antes de guardarlo.
                        </p>
                    </div>

                    <template v-else>
                        <div
                            class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-zinc-400">Resumen</p>
                                    <p
                                        class="font-mono text-2xl font-bold text-white tabular-nums"
                                    >
                                        {{ preview.totals.count }} cargos
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-zinc-400">
                                        Total en {{ preview.card.name }}
                                    </p>
                                    <p
                                        class="font-mono text-2xl font-bold text-red-400 tabular-nums"
                                    >
                                        -{{
                                            formatCurrency(
                                                preview.totals.amount,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Alert
                            v-if="preview.has_errors"
                            variant="destructive"
                            class="border-red-500/30 bg-red-500/10 text-red-300"
                        >
                            <AlertTriangle class="h-4 w-4" />
                            <AlertTitle>Hay filas con errores</AlertTitle>
                            <AlertDescription>
                                Corrige las filas marcadas y vuelve a
                                previsualizar.
                            </AlertDescription>
                        </Alert>

                        <div class="space-y-3">
                            <div
                                v-for="item in preview.items"
                                :key="item.index"
                                class="rounded-xl border bg-zinc-900/50 p-4"
                                :class="
                                    item.errors &&
                                    Object.keys(item.errors).length
                                        ? 'border-red-500/40'
                                        : 'border-zinc-800'
                                "
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs text-zinc-500"
                                        >#{{ item.index + 1 }}</span
                                    >
                                    <span class="text-sm text-zinc-400">
                                        {{
                                            item.date
                                                ? formatDate(item.date)
                                                : '—'
                                        }}
                                    </span>
                                    <span class="font-medium text-zinc-200">
                                        {{ item.description || '—' }}
                                    </span>
                                    <span
                                        class="ml-auto font-mono font-semibold text-red-400 tabular-nums"
                                    >
                                        -{{ formatCurrency(item.amount ?? 0) }}
                                    </span>
                                </div>

                                <div
                                    class="mt-2 flex flex-wrap items-center gap-2"
                                >
                                    <Badge
                                        v-if="
                                            !item.errors ||
                                            !Object.keys(item.errors).length
                                        "
                                        class="bg-zinc-800 text-zinc-300 ring-1 ring-zinc-700"
                                    >
                                        {{ item.category }}
                                    </Badge>
                                    <Badge
                                        v-if="item.installment_count > 1"
                                        class="bg-sky-500/10 text-sky-300 ring-1 ring-sky-500/20"
                                    >
                                        {{ item.installment_count }} MSI
                                    </Badge>
                                </div>

                                <div
                                    v-if="
                                        item.errors &&
                                        Object.keys(item.errors).length
                                    "
                                    class="mt-3 space-y-1 rounded-lg bg-red-500/10 p-3 text-xs text-red-400"
                                >
                                    <p
                                        v-for="(message, field) in item.errors"
                                        :key="field"
                                    >
                                        {{ message }}
                                    </p>
                                </div>

                                <div
                                    v-else-if="
                                        item.installments &&
                                        item.installments.length
                                    "
                                    class="mt-3 rounded-lg border border-zinc-800 bg-zinc-950/60 p-3"
                                >
                                    <p
                                        class="mb-2 text-xs font-medium text-zinc-400"
                                    >
                                        Calendario de parcialidades
                                    </p>
                                    <div
                                        v-for="partial in item.installments"
                                        :key="partial.number"
                                        class="flex items-center justify-between py-1 text-xs text-zinc-400"
                                    >
                                        <span
                                            >{{ partial.number }}/{{
                                                item.installment_count
                                            }}
                                            ·
                                            {{
                                                formatDate(partial.cut_date)
                                            }}</span
                                        >
                                        <span class="font-mono tabular-nums">{{
                                            formatCurrency(partial.amount)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <p
                                v-if="isPreviewStale"
                                class="text-xs text-amber-400"
                            >
                                Editaste el JSON o la tarjeta. Previsualiza de
                                nuevo.
                            </p>
                            <Button
                                :disabled="!canSave || form.processing"
                                @click="onStore"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                {{
                                    form.processing
                                        ? 'Guardando...'
                                        : 'Guardar lote'
                                }}
                            </Button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
