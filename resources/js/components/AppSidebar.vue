<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    FolderGit2,
    LayoutGrid,
    CreditCard,
    DollarSign,
    Lock,
    Repeat,
    FileText,
    Wallet,
    CalendarDays,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as expenses } from '@/routes/expenses';
import { index as events } from '@/routes/events';
import { index as incomes } from '@/routes/incomes';
import { index as vaults } from '@/routes/vaults';
import { index as cards } from '@/routes/cards';
import { index as subscriptions } from '@/routes/subscriptions';
import { index as loans } from '@/routes/loans';
import { index as categories } from '@/routes/categories';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Gastos',
        href: expenses(),
        icon: CreditCard,
    },
    {
        title: 'Eventos',
        href: events(),
        icon: CalendarDays,
    },
    {
        title: 'Ingresos',
        href: incomes(),
        icon: DollarSign,
    },
    {
        title: 'Bóvedas',
        href: vaults(),
        icon: Lock,
    },
    {
        title: 'Tarjetas',
        href: cards(),
        icon: Wallet,
    },
    {
        title: 'Suscripciones',
        href: subscriptions(),
        icon: Repeat,
    },
    {
        title: 'Préstamos',
        href: loans(),
        icon: FileText,
    },
    {
        title: 'Categorías',
        href: categories(),
        icon: FolderGit2,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="h-14">
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
