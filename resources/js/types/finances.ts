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
    card_id: number | null;
    card_statement_id: number | null;
    amount: number | string;
    description: string;
    date: string;
    is_transfer: boolean;
    installment_count: number;
    category: Category;
    vault?: Vault | null;
    card?: Card | null;
}

export interface CardStatement {
    id: number;
    card_id: number;
    period_start: string;
    period_end: string;
    cut_date: string;
    due_date: string;
    total_charges?: number | string | null;
    total?: number;
    paid_amount?: number;
    pending_amount?: number;
    is_settled?: boolean;
    charges?: Array<{
        id: number;
        description: string;
        amount: number | string;
        date: string;
        category?: Category | null;
    }>;
    installments?: Array<{
        id: number;
        number: number;
        amount: number | string;
        cut_date: string;
        expense?: {
            id: number;
            description: string;
            installment_count: number;
        } | null;
    }>;
}

export interface Card {
    id: number;
    name: string;
    credit_limit: number | string;
    cut_off_day: number;
    payment_day: number;
    is_active: boolean;
    /** Calculado por el backend: cargos - pagos. */
    debt?: number;
    available_credit?: number;
    cycle?: {
        period_start: string;
        period_end: string;
        cut_date: string;
        due_date: string;
    };
    statement_summaries?: CardStatement[];
    next_due?: string | null;
}

export interface Subscription {
    id: number;
    name: string;
    amount: number | string;
    category_id: number | null;
    card_id: number | null;
    next_billing_date: string;
    frequency: 'monthly' | 'annual';
    is_active: boolean;
    category?: Category | null;
    card?: Card | null;
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
    /** Persona de la contraparte: deudor si es `lent`, acreedor si es `borrowed`. */
    counterparty_name: string;
    /** `lent` = te deben, `borrowed` = tú debes. */
    direction: 'lent' | 'borrowed';
    amount: number | string;
    description: string | null;
    date: string;
    is_paid: boolean;
}
