<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useElementSize } from '@vueuse/core';
import { Check, Copy, Download, Share2, Trash2 } from 'lucide-vue-next';
import type { Loan } from '@/types/finances';
import LoanShareCard from '@/components/loans/LoanShareCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useFormatter } from '@/composables/useFormatter';
import { SHARE_CARD_WIDTH, useShareImage } from '@/composables/useShareImage';

const props = defineProps<{
    open: boolean;
    loan: Loan | null;
    /** Todos los préstamos de la contraparte, para el estado de cuenta. */
    personLoans: Loan[];
    /** Variante con la que abre: `person` cuando la lista está filtrada. */
    initialVariant: 'loan' | 'person';
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'delete-payment': [loanId: number, paymentId: number];
}>();

const { formatCurrency, formatDate } = useFormatter();
const { isExporting, error, share, download, copyText } = useShareImage();

const variant = ref<'loan' | 'person'>('loan');
const copied = ref(false);
const copyError = ref<string | null>(null);
let copiedTimer: number | undefined;

watch(
    () => props.open,
    (open) => {
        if (open) {
            variant.value = props.initialVariant;
            copied.value = false;
            copyError.value = null;
        }
    },
    { immediate: true },
);

const card = ref<InstanceType<typeof LoanShareCard> | null>(null);

const personName = computed(() => props.loan?.counterparty_name ?? '');

const hasOtherLoans = computed(() => props.personLoans.length > 1);

/* --- Preview escalado --- */

/**
 * La card mide 1080x1350 y se muestra escalada para que quepa completa en el
 * alto del teléfono. El `max-width` en `dvh` garantiza que nunca haya que
 * hacer scroll para verla. `useElementSize` da el factor exacto sin medir a
 * mano el ancho del dialog.
 */
const frame = ref<HTMLElement | null>(null);
const { width: frameWidth } = useElementSize(frame);

const scale = computed(() => {
    const measured = frameWidth.value || 300;

    return measured / SHARE_CARD_WIDTH;
});

/* --- Historial completo de abonos --- */

const historyEntries = computed(() => {
    const loans =
        variant.value === 'person'
            ? props.personLoans
            : props.loan
              ? [props.loan]
              : [];

    return loans
        .flatMap((loan) =>
            (loan.payments ?? []).map((payment) => ({ loan, payment })),
        )
        .sort((a, b) => b.payment.date.localeCompare(a.payment.date));
});

/* --- Acciones --- */

const fileName = computed(() => {
    const slug =
        (personName.value || 'prestamo')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '') || 'prestamo';

    return `${variant.value === 'person' ? 'estado-de-cuenta' : 'prestamo'}-${slug}`;
});

const shareTitle = computed(() =>
    variant.value === 'person'
        ? `Estado de cuenta con ${personName.value}`
        : `Préstamo con ${personName.value}`,
);

const summaryText = computed(() =>
    `${shareTitle.value}\n\n${card.value?.shareText ?? ''}`.trim(),
);

const handleShare = async () => {
    const node = card.value?.cardElement;

    if (!node) {
        return;
    }

    await share(node, {
        fileName: fileName.value,
        title: shareTitle.value,
        text: summaryText.value,
    });
};

const handleDownload = async () => {
    const node = card.value?.cardElement;

    if (!node) {
        return;
    }

    await download(node, {
        fileName: fileName.value,
        title: shareTitle.value,
    });
};

const handleCopy = async () => {
    copyError.value = null;

    if (!(await copyText(summaryText.value))) {
        copyError.value =
            'El navegador bloqueó el portapapeles. Intenta descargar la imagen.';

        return;
    }

    copied.value = true;
    window.clearTimeout(copiedTimer);
    copiedTimer = window.setTimeout(() => {
        copied.value = false;
    }, 2000);
};

onBeforeUnmount(() => window.clearTimeout(copiedTimer));
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="flex max-h-[92dvh] w-full flex-col gap-0 overflow-hidden border-zinc-800 bg-zinc-950 p-0 text-zinc-100 sm:max-w-md"
        >
            <DialogHeader
                class="shrink-0 space-y-1 border-b border-zinc-800 px-5 py-4 pr-12 text-left"
            >
                <DialogTitle class="text-base">
                    {{ variant === 'person' ? 'Estado de cuenta' : 'Préstamo' }}
                </DialogTitle>
                <DialogDescription class="text-xs text-zinc-500">
                    <template v-if="variant === 'person'">
                        Todo el saldo pendiente con {{ personName }}.
                    </template>
                    <template v-else-if="loan">
                        {{
                            loan.is_settled
                                ? `${loan.counterparty_name} · Liquidado`
                                : loan.direction === 'lent'
                                  ? `${loan.counterparty_name} te debe ${formatCurrency(loan.remaining_amount ?? loan.amount)}`
                                  : `Le debes ${formatCurrency(loan.remaining_amount ?? loan.amount)} a ${loan.counterparty_name}`
                        }}
                    </template>
                </DialogDescription>
            </DialogHeader>

            <div
                class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-4 py-4"
            >
                <!-- Cambio entre un préstamo y el estado de cuenta completo -->
                <div
                    v-if="hasOtherLoans"
                    class="grid shrink-0 grid-cols-2 gap-1 rounded-lg border border-zinc-800 bg-zinc-900/60 p-1"
                >
                    <button
                        type="button"
                        class="truncate rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            variant === 'loan'
                                ? 'bg-zinc-800 text-white'
                                : 'text-zinc-400 hover:text-zinc-200'
                        "
                        @click="variant = 'loan'"
                    >
                        Este préstamo
                    </button>
                    <button
                        type="button"
                        class="truncate rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            variant === 'person'
                                ? 'bg-zinc-800 text-white'
                                : 'text-zinc-400 hover:text-zinc-200'
                        "
                        @click="variant = 'person'"
                    >
                        Estado de cuenta
                    </button>
                </div>

                <!-- Card compartible -->
                <div class="shrink-0">
                    <div
                        ref="frame"
                        class="relative mx-auto aspect-4/5 w-full overflow-hidden rounded-xl ring-1 ring-zinc-800 select-none"
                        style="max-width: min(100%, 340px, calc(52dvh * 0.8))"
                    >
                        <div
                            class="absolute top-0 left-0 origin-top-left"
                            :style="{ transform: `scale(${scale})` }"
                        >
                            <LoanShareCard
                                ref="card"
                                :variant="variant"
                                :loan="loan"
                                :person-loans="personLoans"
                            />
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="grid shrink-0 grid-cols-2 gap-2">
                    <Button
                        class="col-span-2"
                        size="sm"
                        :disabled="isExporting"
                        @click="handleShare"
                    >
                        <Share2 />
                        Compartir
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="isExporting"
                        class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        @click="handleDownload"
                    >
                        <Download />
                        Descargar
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="border-zinc-700 bg-transparent text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        @click="handleCopy"
                    >
                        <Check v-if="copied" />
                        <Copy v-else />
                        {{ copied ? 'Copiado' : 'Copiar' }}
                    </Button>
                </div>

                <p v-if="isExporting" class="text-xs text-zinc-500">
                    Generando imagen...
                </p>

                <p v-else-if="error || copyError" class="text-xs text-red-400">
                    {{ error ?? copyError }}
                </p>

                <!-- Historial completo (la card solo muestra los últimos) -->
                <div class="shrink-0">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium tracking-wider text-zinc-500 uppercase"
                        >
                            Historial de abonos
                        </p>
                        <p class="text-xs text-zinc-500">
                            {{ historyEntries.length }}
                            {{
                                historyEntries.length === 1 ? 'abono' : 'abonos'
                            }}
                        </p>
                    </div>

                    <div
                        v-if="historyEntries.length > 0"
                        class="mt-2 max-h-[26dvh] overflow-y-auto rounded-lg border border-zinc-800"
                    >
                        <div
                            v-for="entry in historyEntries"
                            :key="`${entry.loan.id}-${entry.payment.id}`"
                            class="flex items-center justify-between gap-3 border-b border-zinc-800/70 px-3 py-2 last:border-b-0"
                        >
                            <div class="min-w-0">
                                <p
                                    class="font-mono text-sm font-medium text-zinc-100 tabular-nums"
                                >
                                    {{ formatCurrency(entry.payment.amount) }}
                                </p>
                                <p class="truncate text-xs text-zinc-500">
                                    {{ formatDate(entry.payment.date) }}
                                    <template v-if="entry.payment.note">
                                        · {{ entry.payment.note }}
                                    </template>
                                    <template v-else-if="variant === 'person'">
                                        ·
                                        {{
                                            entry.loan.description ||
                                            'Sin concepto'
                                        }}
                                    </template>
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="shrink-0 text-zinc-500 hover:bg-red-500/10 hover:text-red-400"
                                aria-label="Eliminar abono"
                                @click="
                                    emit(
                                        'delete-payment',
                                        entry.loan.id,
                                        entry.payment.id,
                                    )
                                "
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </div>

                    <p v-else class="mt-2 text-xs text-zinc-500">
                        Sin abonos registrados.
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
