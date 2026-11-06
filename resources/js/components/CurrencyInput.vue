<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue?: string | number | null;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputRef = ref<InstanceType<typeof Input> | null>(null);

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

const onInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const caret = input.selectionStart ?? input.value.length;
    const digitsBeforeCaret = (input.value.slice(0, caret).match(/\d/g) ?? [])
        .length;

    const normalized = normalize(input.value);

    text.value = format(normalized);
    emit('update:modelValue', normalized);

    nextTick(() => {
        const element = inputRef.value?.$el as HTMLInputElement | undefined;

        if (element) {
            restoreCaret(element, digitsBeforeCaret);
        }
    });
};
</script>

<template>
    <Input
        ref="inputRef"
        v-bind="$attrs"
        :model-value="text"
        type="text"
        inputmode="decimal"
        autocomplete="off"
        @input="onInput"
    />
</template>
