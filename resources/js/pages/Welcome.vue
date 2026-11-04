<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, home, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const page = usePage();
const name = page.props.name;

const features = [
    'Registra y clasifica tus gastos e ingresos',
    'Controla suscripciones y préstamos',
    'Administra tu capital en bóvedas',
    'Visualiza tu flujo neto en el dashboard',
];
</script>

<template>
    <Head title="Bienvenido" />
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header class="mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-between gap-4">
                <Link
                    :href="home()"
                    class="flex items-center gap-2 font-medium"
                >
                    <AppLogoIcon class="size-7" />
                    <span class="truncate">{{ name }}</span>
                </Link>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-block rounded-sm border border-[#7036d1] px-5 py-1.5 text-sm leading-normal text-[#7036d1] hover:bg-[#7036d1] hover:text-white dark:border-[#a78bfa] dark:text-[#a78bfa] dark:hover:bg-[#a78bfa] dark:hover:text-[#1b1b18]"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        >
                            Iniciar sesión
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="inline-block rounded-sm border border-[#7036d1] bg-[#7036d1] px-5 py-1.5 text-sm leading-normal text-white hover:border-[#5b2bb0] hover:bg-[#5b2bb0]"
                        >
                            Registrarse
                        </Link>
                    </template>
                </div>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1 class="mb-1 font-medium">Bienvenido a {{ name }}</h1>
                    <p class="mb-4 text-[#706f6c] dark:text-[#A1A09A]">
                        La forma más simple de entender a dónde va tu dinero.
                    </p>
                    <ul class="mb-6 flex flex-col gap-3">
                        <li
                            v-for="feature in features"
                            :key="feature"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#7036d1]/10"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#7036d1]"
                                />
                            </span>
                            <span>{{ feature }}</span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : register()
                                "
                                class="inline-block rounded-sm border border-[#7036d1] bg-[#7036d1] px-5 py-1.5 text-sm leading-normal text-white hover:border-[#5b2bb0] hover:bg-[#5b2bb0]"
                            >
                                Comenzar ahora
                            </Link>
                        </li>
                    </ul>
                </div>
                <div
                    class="relative -mb-px flex aspect-335/376 w-full shrink-0 items-center justify-center overflow-hidden rounded-t-lg bg-[#7036d1] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg"
                >
                    <div
                        class="flex flex-col items-center gap-4 p-8 text-center"
                    >
                        <AppLogoIcon
                            class="size-28 rounded-3xl ring-1 ring-white/25 drop-shadow-lg"
                            aria-hidden="true"
                        />
                        <span class="text-2xl font-semibold text-white">
                            {{ name }}
                        </span>
                        <p class="max-w-xs text-sm text-white/80">
                            Gastos, ingresos, suscripciones y capital en un solo
                            lugar.
                        </p>
                    </div>
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:overflow-hidden lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    />
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
