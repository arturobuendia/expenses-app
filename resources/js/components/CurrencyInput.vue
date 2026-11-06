<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue?: string | number | null;
    class?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);

/** Conserva solo dígitos y un punto decimal, con un máximo de dos decimales. */
const normalize = (value: string | number | null | undefined): string => {
    const raw = String(value ?? '').replace(/[^\d.]/g, '');

    if (!raw) return '';

    const [integer, ...rest] = raw.split('.');
    const decimals = rest.join('').slice(0, 2);
    const whole = integer.replace(/^0+(?=\d)/, '');

    return raw.includes('.') ? `${whole || '0'}.${decimals}` : whole;
};

/** Agrupa los miles y antepone el símbolo de moneda. */
const format = (value: string): string => {
    if (!value) return '';

    const [integer, decimals] = value.split('.');
    const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return `$${grouped}${decimals === undefined ? '' : `.${decimals}`}`;
};

const text = ref(format(normalize(props.modelValue)));

watch(
    () => props.modelValue,
    (value) => {
        const incoming = normalize(value);

        // No reformatear mientras el usuario escribe el mismo valor.
        if (incoming !== normalize(text.value)) {
            text.value = format(incoming);
        }
    },
);

/** Deja el cursor después del mismo número de dígitos que había antes de formatear. */
const restoreCaret = (input: HTMLInputElement, digits: number) => {
    let seen = 0;
    let position = 0;

    for (const char of input.value) {
        if (seen >= digits) break;

        seen += /\d/.test(char) ? 1 : 0;
        position += char.length;
    }

    input.setSelectionRange(position, position);
};

const onInput = () => {
    const input = inputRef.value;

    if (!input) return;

    const caret = input.selectionStart ?? input.value.length;
    const digitsBeforeCaret = (input.value.slice(0, caret).match(/\d/g) ?? [])
        .length;

    const normalized = normalize(input.value);
    const formatted = format(normalized);

    // Se reescribe el DOM directamente para que los caracteres inválidos
    // desaparezcan aunque el valor normalizado no haya cambiado.
    input.value = formatted;
    text.value = formatted;
    emit('update:modelValue', normalized);

    nextTick(() => {
        if (inputRef.value) {
            restoreCaret(inputRef.value, digitsBeforeCaret);
        }
    });
};
</script>

<template>
    <input
        ref="inputRef"
        v-bind="$attrs"
        :value="text"
        type="text"
        inputmode="decimal"
        autocomplete="off"
        :class="
            cn(
                'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                'aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
                props.class,
            )
        "
        @input="onInput"
    />
</template>
