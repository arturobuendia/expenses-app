<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Loan, LoanPayment } from '@/types/finances';
import { useFormatter } from '@/composables/useFormatter';
import {
    SHARE_CARD_HEIGHT,
    SHARE_CARD_WIDTH,
} from '@/composables/useShareImage';

const props = defineProps<{
    /** `loan` detalla un préstamo; `person` resume todo el saldo de una persona. */
    variant: 'loan' | 'person';
    loan: Loan | null;
    /** Todos los préstamos de la contraparte, para totales y desglose. */
    personLoans: Loan[];
}>();

const { formatCurrency, formatDate } = useFormatter();

const MAX_PAYMENTS = 4;
const MAX_PERSON_LOANS = 3;
const MAX_PERSON_PAYMENTS = 3;

/** Nodo real que se exporta: siempre mide 1080x1350, sin importar el zoom del preview. */
const cardElement = ref<HTMLElement | null>(null);

const accents = {
    lent: {
        color: '#fbbf24',
        soft: 'rgba(251, 191, 36, 0.10)',
        border: 'rgba(251, 191, 36, 0.32)',
        glow: 'rgba(251, 191, 36, 0.18)',
    },
    borrowed: {
        color: '#f87171',
        soft: 'rgba(248, 113, 113, 0.10)',
        border: 'rgba(248, 113, 113, 0.32)',
        glow: 'rgba(248, 113, 113, 0.18)',
    },
    neutral: {
        color: '#a1a1aa',
        soft: 'rgba(161, 161, 170, 0.10)',
        border: 'rgba(161, 161, 170, 0.30)',
        glow: 'rgba(161, 161, 170, 0.14)',
    },
} as const;

const remainingOf = (loan: Loan) =>
    loan.remaining_amount ?? Number(loan.amount);

const paidOf = (loan: Loan) => loan.paid_amount ?? 0;

const isSettled = (loan: Loan) => loan.is_settled ?? remainingOf(loan) <= 0;

const statusLabel = (loan: Loan) => {
    if (isSettled(loan)) {
        return loan.direction === 'lent' ? 'Cobrado' : 'Pagado';
    }

    return paidOf(loan) > 0 ? 'Parcial' : 'Pendiente';
};

const directionLabel = (loan: Loan) =>
    loan.direction === 'lent' ? 'Te debe' : 'Le debes';

const today = (() => {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
})();

/* --- Préstamo (variant `loan`) --- */

const loanRemaining = computed(() =>
    props.loan ? remainingOf(props.loan) : 0,
);
const loanPaid = computed(() => (props.loan ? paidOf(props.loan) : 0));
const loanAmount = computed(() => Number(props.loan?.amount ?? 0));
const loanStatus = computed(() => (props.loan ? statusLabel(props.loan) : ''));
const loanDate = computed(() => props.loan?.date ?? today);
const loanDescription = computed(() => props.loan?.description ?? '');
const loanDirection = computed(() => props.loan?.direction ?? 'lent');

const loanProgress = computed(() => {
    if (loanAmount.value <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((loanPaid.value / loanAmount.value) * 100));
});

const sortedPayments = computed<LoanPayment[]>(() =>
    [...(props.loan?.payments ?? [])].sort((a, b) =>
        b.date.localeCompare(a.date),
    ),
);

const visiblePayments = computed(() =>
    sortedPayments.value.slice(0, MAX_PAYMENTS),
);

const totalPayments = computed(() =>
    props.variant === 'person'
        ? personPayments.value.length
        : sortedPayments.value.length,
);

const visiblePaymentsCount = computed(() =>
    props.variant === 'person'
        ? visiblePersonPayments.value.length
        : visiblePayments.value.length,
);

/* --- Persona (variant `person`) --- */

const personName = computed(
    () =>
        props.loan?.counterparty_name ??
        props.personLoans[0]?.counterparty_name ??
        'Sin nombre',
);

const pendingPersonLoans = computed(() =>
    props.personLoans.filter((loan) => !isSettled(loan)),
);

const lentPending = computed(() =>
    pendingPersonLoans.value.filter((loan) => loan.direction === 'lent'),
);

const borrowedPending = computed(() =>
    pendingPersonLoans.value.filter((loan) => loan.direction === 'borrowed'),
);

const sumRemaining = (loans: Loan[]) =>
    loans.reduce((total, loan) => total + remainingOf(loan), 0);

const owedToMe = computed(() => sumRemaining(lentPending.value));
const iOwe = computed(() => sumRemaining(borrowedPending.value));

const personBlocks = computed(() => {
    const blocks = [];

    if (owedToMe.value > 0) {
        blocks.push({
            key: 'lent',
            label: 'Te debe',
            amount: owedToMe.value,
            count: lentPending.value.length,
            accent: accents.lent,
        });
    }

    if (iOwe.value > 0) {
        blocks.push({
            key: 'borrowed',
            label: 'Le debes',
            amount: iOwe.value,
            count: borrowedPending.value.length,
            accent: accents.borrowed,
        });
    }

    return blocks;
});

const personLoansPreview = computed(() =>
    [...pendingPersonLoans.value]
        .sort((a, b) => remainingOf(b) - remainingOf(a))
        .slice(0, MAX_PERSON_LOANS),
);

const personPayments = computed(() =>
    props.personLoans
        .flatMap((loan) =>
            (loan.payments ?? []).map((payment) => ({ payment, loan })),
        )
        .sort((a, b) => b.payment.date.localeCompare(a.payment.date)),
);

const visiblePersonPayments = computed(() =>
    personPayments.value.slice(0, MAX_PERSON_PAYMENTS),
);

/* --- Pie de la card --- */

/**
 * Con un préstamo abierto, el pie recuerda el saldo total con esa persona.
 * Solo aparece cuando hay más de un préstamo pendiente en esa dirección:
 * si es el único, repetir el mismo monto confunde.
 */
const personTotals = computed(() => {
    const lines: { label: string; amount: number }[] = [];

    if (lentPending.value.length > 1) {
        lines.push({
            label: `${personName.value} te debe en total`,
            amount: owedToMe.value,
        });
    }

    if (borrowedPending.value.length > 1) {
        lines.push({
            label: `Le debes a ${personName.value} en total`,
            amount: iOwe.value,
        });
    }

    return lines;
});

const accent = computed(() => {
    if (props.variant === 'loan') {
        return accents[loanDirection.value];
    }

    if (owedToMe.value > 0 && iOwe.value === 0) {
        return accents.lent;
    }

    if (iOwe.value > 0 && owedToMe.value === 0) {
        return accents.borrowed;
    }

    return accents.neutral;
});

const eyebrow = computed(() =>
    props.variant === 'loan' ? 'PRÉSTAMO' : 'ESTADO DE CUENTA',
);

const badgeLabel = computed(() => {
    if (props.variant === 'loan') {
        return props.loan ? directionLabel(props.loan) : 'SIN PRÉSTAMO';
    }

    const count = pendingPersonLoans.value.length;

    return count === 0
        ? 'SIN SALDO PENDIENTE'
        : `${count} ${count === 1 ? 'PRÉSTAMO PENDIENTE' : 'PRÉSTAMOS PENDIENTES'}`;
});

const cardStyle = computed(() => ({
    width: `${SHARE_CARD_WIDTH}px`,
    height: `${SHARE_CARD_HEIGHT}px`,
    padding: '72px',
    backgroundColor: '#09090b',
    color: '#f4f4f5',
    fontFamily: "'Sora Variable', ui-sans-serif, system-ui, sans-serif",
    backgroundImage: `radial-gradient(circle at 88% -8%, ${accent.value.glow}, transparent 58%), linear-gradient(160deg, #111114 0%, #09090b 58%, #070709 100%)`,
}));

/**
 * Texto plano de respaldo: se copia al portapapeles cuando compartir archivos
 * no está disponible (o cuando el usuario solo quiere pegar en un chat).
 */
const shareText = computed(() => {
    if (props.variant === 'loan' && props.loan) {
        const lines = [
            `${directionLabel(props.loan)}: ${props.loan.counterparty_name}`,
            `Saldo pendiente: ${formatCurrency(loanRemaining.value)}`,
            `Monto original: ${formatCurrency(loanAmount.value)}`,
            `Abonado: ${formatCurrency(loanPaid.value)} (${loanProgress.value}%)`,
            `Fecha del préstamo: ${formatDate(loanDate.value)}`,
        ];

        if (loanDescription.value) {
            lines.push(`Concepto: ${loanDescription.value}`);
        }

        if (visiblePayments.value.length > 0) {
            lines.push('Abonos:');

            visiblePayments.value.forEach((payment) => {
                const note = payment.note ? ` (${payment.note})` : '';
                lines.push(
                    `- ${formatDate(payment.date)}: ${formatCurrency(payment.amount)}${note}`,
                );
            });
        }

        if (sortedPayments.value.length > visiblePayments.value.length) {
            lines.push(
                `+${sortedPayments.value.length - visiblePayments.value.length} abonos más`,
            );
        }

        return lines.join('\n');
    }

    const lines = [`Estado de cuenta con ${personName.value}`];

    personBlocks.value.forEach((block) => {
        lines.push(
            `${block.label}: ${formatCurrency(block.amount)} (${block.count} ${block.count === 1 ? 'préstamo' : 'préstamos'})`,
        );
    });

    if (personBlocks.value.length === 0) {
        lines.push('Sin saldo pendiente.');
    }

    personLoansPreview.value.forEach((loan) => {
        lines.push(
            `- ${formatDate(loan.date)} · ${loan.description || 'Sin concepto'}: ${formatCurrency(remainingOf(loan))}`,
        );
    });

    if (visiblePersonPayments.value.length > 0) {
        lines.push('Abonos recientes:');

        visiblePersonPayments.value.forEach(({ payment }) => {
            lines.push(
                `- ${formatDate(payment.date)}: ${formatCurrency(payment.amount)}`,
            );
        });
    }

    return lines.join('\n');
});

defineExpose({ cardElement, shareText });
</script>

<template>
    <div
        ref="cardElement"
        class="relative flex flex-col overflow-hidden"
        :style="cardStyle"
    >
        <!-- Encabezado -->
        <div class="flex shrink-0 items-center justify-between">
            <p
                class="text-[22px] font-semibold tracking-[0.3em] text-[#52525b]"
            >
                {{ eyebrow }}
            </p>
            <p class="text-[22px] text-[#52525b]">{{ formatDate(today) }}</p>
        </div>

        <!-- Dirección y estado -->
        <div class="mt-[36px] flex shrink-0 items-center gap-[16px]">
            <span
                class="inline-flex items-center rounded-full border px-[20px] py-[10px] text-[22px] font-semibold tracking-[0.12em]"
                :style="{
                    backgroundColor: accent.soft,
                    borderColor: accent.border,
                    color: accent.color,
                }"
            >
                {{ badgeLabel }}
            </span>
            <span v-if="variant === 'loan'" class="text-[22px] text-[#71717a]">
                {{ loanStatus }}
            </span>
        </div>

        <!-- Persona -->
        <p
            class="mt-[28px] shrink-0 truncate font-display text-[64px] leading-[1.05] font-semibold text-white"
        >
            {{ personName }}
        </p>

        <!-- Monto principal: préstamo -->
        <section v-if="variant === 'loan'" class="mt-[32px] shrink-0">
            <p class="text-[22px] tracking-[0.22em] text-[#71717a] uppercase">
                Saldo pendiente
            </p>
            <p
                class="mt-[8px] font-mono text-[88px] leading-none font-bold text-white tabular-nums"
            >
                {{ formatCurrency(loanRemaining) }}
            </p>

            <div
                class="mt-[24px] h-[10px] w-full overflow-hidden rounded-full bg-[#27272a]"
            >
                <div
                    class="h-full rounded-full"
                    :style="{
                        width: `${loanProgress}%`,
                        backgroundColor: accent.color,
                    }"
                />
            </div>

            <div class="mt-[26px] grid grid-cols-2 gap-[24px]">
                <div>
                    <p
                        class="text-[20px] tracking-[0.2em] text-[#71717a] uppercase"
                    >
                        Monto original
                    </p>
                    <p
                        class="mt-[6px] font-mono text-[34px] font-semibold text-[#d4d4d8] tabular-nums"
                    >
                        {{ formatCurrency(loanAmount) }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-[20px] tracking-[0.2em] text-[#71717a] uppercase"
                    >
                        Abonado · {{ loanProgress }}%
                    </p>
                    <p
                        class="mt-[6px] font-mono text-[34px] font-semibold text-[#34d399] tabular-nums"
                    >
                        {{ formatCurrency(loanPaid) }}
                    </p>
                </div>
            </div>

            <div v-if="loanDescription" class="mt-[24px]">
                <p
                    class="text-[20px] tracking-[0.2em] text-[#71717a] uppercase"
                >
                    Concepto
                </p>
                <p
                    class="mt-[8px] line-clamp-2 text-[28px] leading-[1.35] text-[#d4d4d8]"
                >
                    {{ loanDescription }}
                </p>
            </div>
        </section>

        <!-- Monto principal: persona -->
        <section v-else class="mt-[32px] shrink-0">
            <div
                :class="[
                    'grid gap-[32px]',
                    personBlocks.length > 1 ? 'grid-cols-2' : 'grid-cols-1',
                ]"
            >
                <div
                    v-for="block in personBlocks"
                    :key="block.key"
                    class="min-w-0"
                >
                    <p
                        class="text-[22px] tracking-[0.22em] uppercase"
                        :style="{ color: block.accent.color }"
                    >
                        {{ block.label }}
                    </p>
                    <p
                        :class="[
                            'mt-[8px] font-mono leading-none font-bold tabular-nums',
                            personBlocks.length > 1
                                ? 'text-[62px]'
                                : 'text-[88px]',
                        ]"
                        :style="{ color: block.accent.color }"
                    >
                        {{ formatCurrency(block.amount) }}
                    </p>
                    <p class="mt-[10px] text-[22px] text-[#71717a]">
                        {{ block.count }}
                        {{ block.count === 1 ? 'préstamo' : 'préstamos' }}
                    </p>
                </div>
            </div>

            <div
                v-if="personBlocks.length === 0"
                class="rounded-2xl border border-[#1f1f23] bg-[#111114] px-[28px] py-[32px]"
            >
                <p class="text-[34px] font-semibold text-[#d4d4d8]">
                    Sin saldo pendiente
                </p>
                <p class="mt-[8px] text-[24px] text-[#71717a]">
                    Todos los préstamos con esta persona están liquidados.
                </p>
            </div>
        </section>

        <!-- Desglose por persona -->
        <section
            v-if="variant === 'person' && personLoansPreview.length > 0"
            class="mt-[28px] shrink-0"
        >
            <div class="flex items-baseline justify-between gap-[16px]">
                <p
                    class="text-[20px] tracking-[0.2em] text-[#71717a] uppercase"
                >
                    Préstamos pendientes
                </p>
                <p class="text-[18px] text-[#52525b]">
                    {{ pendingPersonLoans.length }} en total
                </p>
            </div>
            <div class="mt-[10px] flex flex-col">
                <div
                    v-for="item in personLoansPreview"
                    :key="item.id"
                    class="flex items-baseline justify-between gap-[24px] border-b border-[#1f1f23] py-[12px]"
                >
                    <div class="min-w-0">
                        <p
                            class="truncate text-[26px] leading-[1.25] text-[#d4d4d8]"
                        >
                            {{ item.description || 'Sin concepto' }}
                        </p>
                        <p class="text-[20px] leading-[1.25] text-[#71717a]">
                            {{ formatDate(item.date) }} ·
                            {{ directionLabel(item) }}
                        </p>
                    </div>
                    <p
                        class="shrink-0 font-mono text-[28px] font-medium tabular-nums"
                        :style="{
                            color:
                                item.direction === 'lent'
                                    ? '#fbbf24'
                                    : '#f87171',
                        }"
                    >
                        {{ formatCurrency(remainingOf(item)) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Abonos -->
        <section class="mt-[24px] flex min-h-0 flex-1 flex-col">
            <div
                class="flex shrink-0 items-baseline justify-between gap-[16px]"
            >
                <p
                    class="text-[20px] tracking-[0.2em] text-[#71717a] uppercase"
                >
                    Abonos
                </p>
                <p v-if="totalPayments > 0" class="text-[18px] text-[#52525b]">
                    {{ visiblePaymentsCount }} de {{ totalPayments }}
                </p>
            </div>

            <div
                v-if="
                    variant === 'loan'
                        ? visiblePayments.length > 0
                        : visiblePersonPayments.length > 0
                "
                class="mt-[10px] flex min-h-0 flex-col overflow-hidden"
            >
                <template v-if="variant === 'loan'">
                    <div
                        v-for="payment in visiblePayments"
                        :key="payment.id"
                        class="flex items-baseline justify-between gap-[24px] border-b border-[#1f1f23] py-[9px]"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-[24px] leading-[1.25] text-[#d4d4d8]"
                            >
                                {{ payment.note || 'Abono' }}
                            </p>
                            <p
                                class="text-[19px] leading-[1.25] text-[#71717a]"
                            >
                                {{ formatDate(payment.date) }}
                            </p>
                        </div>
                        <p
                            class="shrink-0 font-mono text-[26px] font-medium text-[#34d399] tabular-nums"
                        >
                            {{ formatCurrency(payment.amount) }}
                        </p>
                    </div>
                </template>

                <template v-else>
                    <div
                        v-for="entry in visiblePersonPayments"
                        :key="`${entry.loan.id}-${entry.payment.id}`"
                        class="flex items-baseline justify-between gap-[24px] border-b border-[#1f1f23] py-[9px]"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-[24px] leading-[1.25] text-[#d4d4d8]"
                            >
                                {{ entry.payment.note || 'Abono' }}
                            </p>
                            <p
                                class="text-[19px] leading-[1.25] text-[#71717a]"
                            >
                                {{ formatDate(entry.payment.date) }}
                            </p>
                        </div>
                        <p
                            class="shrink-0 font-mono text-[26px] font-medium text-[#34d399] tabular-nums"
                        >
                            {{ formatCurrency(entry.payment.amount) }}
                        </p>
                    </div>
                </template>
            </div>

            <p v-else class="mt-[14px] text-[24px] text-[#52525b]">
                Sin abonos registrados.
            </p>
        </section>

        <!-- Pie -->
        <footer class="mt-auto shrink-0 border-t border-[#1f1f23] pt-[18px]">
            <div
                v-for="line in variant === 'loan' ? personTotals : []"
                :key="line.label"
                class="flex items-baseline justify-between gap-[24px] pb-[10px]"
            >
                <p class="truncate text-[22px] text-[#a1a1aa]">
                    {{ line.label }}
                </p>
                <p
                    class="shrink-0 font-mono text-[26px] font-semibold text-[#d4d4d8] tabular-nums"
                >
                    {{ formatCurrency(line.amount) }}
                </p>
            </div>

            <div class="flex items-end justify-between gap-[24px]">
                <div class="min-w-0">
                    <p
                        class="text-[19px] tracking-[0.2em] text-[#52525b] uppercase"
                    >
                        {{ variant === 'loan' ? 'Registrado' : 'Resumen' }}
                    </p>
                    <p class="mt-[4px] truncate text-[22px] text-[#a1a1aa]">
                        {{
                            variant === 'loan'
                                ? formatDate(loanDate)
                                : `${pendingPersonLoans.length} ${pendingPersonLoans.length === 1 ? 'préstamo pendiente' : 'préstamos pendientes'}`
                        }}
                    </p>
                </div>
                <p class="shrink-0 text-right text-[19px] text-[#52525b]">
                    Actualizado al {{ formatDate(today) }}
                </p>
            </div>
        </footer>
    </div>
</template>
