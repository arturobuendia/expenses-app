export interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    is_active: boolean;
}

export interface Vault {
    id: number;
    name: string;
    initial_balance: number | string;
    /** Saldo calculado por el backend: initial_balance + ingresos - gastos. */
    balance: number | string;
    annual_yield_rate?: number | string | null;
    yield_cap?: number | string | null;
}

export interface Expense {
    id: number;
    category_id: number;
    vault_id: number | null;
    amount: number | string;
    description: string;
    date: string;
    is_transfer: boolean;
    category: Category;
    vault?: Vault | null;
}

export interface Subscription {
    id: number;
    name: string;
    amount: number | string;
    next_billing_date: string;
    is_active: boolean;
}

export interface Totals {
    incomes: number;
    expenses: number;
    capital: number;
}

export interface Income {
    id: number;
    category_id: number;
    vault_id: number | null;
    amount: number | string;
    description: string;
    date: string;
    category: Category;
    vault?: Vault | null;
}

export interface Loan {
    id: number;
    debtor_name: string;
    amount: number | string;
    description: string | null;
    date: string;
    is_paid: boolean;
}
