<script setup lang="ts">
import { computed } from 'vue';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import type { Category } from '@/types/finances';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface Props {
    modelValue: string;
    categories: Category[];
    placeholder?: string;
    allowAll?: boolean;
    allLabel?: string;
    triggerClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Selecciona una categoría',
    allowAll: false,
    allLabel: 'Todas las Categorías',
    triggerClass: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

interface CategoryOption {
    value: string;
    label: string;
}

const options = computed<CategoryOption[]>(() => {
    const items: CategoryOption[] = props.categories.map((category) => ({
        value: category.id.toString(),
        label: category.name,
    }));

    if (props.allowAll) {
        items.unshift({ value: 'all', label: props.allLabel });
    }

    return items;
});

const selected = computed({
    get: () =>
        options.value.find((option) => option.value === props.modelValue),
    set: (option: CategoryOption | undefined) => {
        emit('update:modelValue', option?.value ?? '');
    },
});
</script>

<template>
    <Combobox v-model="selected" by="value">
        <ComboboxAnchor as-child class="w-full">
            <ComboboxTrigger as-child>
                <Button
                    variant="outline"
                    :class="
                        cn(
                            'w-full justify-between border-zinc-800 bg-zinc-900 text-zinc-100 hover:bg-zinc-800 hover:text-white focus-visible:ring-zinc-500',
                            triggerClass,
                        )
                    "
                >
                    <span v-if="selected" class="truncate">
                        {{ selected.label }}
                    </span>
                    <span v-else class="truncate text-zinc-500">
                        {{ placeholder }}
                    </span>
                    <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxList
            align="start"
            class="w-[var(--reka-combobox-trigger-width)] border-zinc-800 bg-zinc-950 text-zinc-100"
        >
            <ComboboxInput
                placeholder="Buscar categoría..."
                class="text-zinc-100 placeholder:text-zinc-500"
            />
            <ComboboxEmpty class="text-zinc-400">Sin resultados.</ComboboxEmpty>
            <ComboboxGroup>
                <ComboboxItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option"
                    :text-value="option.label"
                    class="data-[highlighted]:bg-zinc-800 data-[highlighted]:text-zinc-100"
                >
                    {{ option.label }}
                    <ComboboxItemIndicator>
                        <Check class="size-4" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
