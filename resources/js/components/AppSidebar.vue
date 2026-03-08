<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, CreditCard, DollarSign, Lock, Repeat, FileText } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { dashboard } from '@/routes'
import { index as expenses } from '@/routes/expenses';
import { index as incomes } from '@/routes/incomes';
import { index as vaults } from '@/routes/vaults';
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

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
