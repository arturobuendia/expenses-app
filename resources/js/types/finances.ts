
export interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    is_active: boolean;
}

export interface Expense {
    id: number;
    category_id: number;
    amount: number | string;
    description: string;
    date: string;
    category: Category;
}

export interface Vault {
    id: number;
    name: string;
    balance: number | string;
    annual_yield_rate?: number | string | null;
    yield_cap?: number | string | null;
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