export function useFormatter() {
    const formatCurrency = (value: number | string): string => {
        const num = Number(value);
        if (isNaN(num)) return '$0.00';

        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
        }).format(num);
    };

    const formatDate = (dateString: string): string => {
        if (!dateString) return '';

        // Las fechas del backend son de solo día (Y-m-d). Tomamos únicamente
        // esa parte y la interpretamos en UTC para evitar desfases de zona.
        const [year, month, day] = dateString
            .slice(0, 10)
            .split('-')
            .map(Number);

        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(new Date(Date.UTC(year, month - 1, day)));
    };

    /**
     * Convierte una fecha del backend al valor que acepta un <input type="date">.
     *
     * El backend serializa las columnas `date` como ISO 8601
     * (ej. "2026-10-08T00:00:00.000000Z"); el input de fecha solo muestra un
     * valor cuando recibe exactamente "YYYY-MM-DD", así que recortamos la parte
     * del día.
     */
    const toDateInput = (dateString: string): string => {
        if (!dateString) return '';
        return dateString.slice(0, 10);
    };

    const getMonthName = (dateInput?: string | Date): string => {
        // Si no le pasamos fecha, usa la actual
        const date = dateInput ? new Date(dateInput) : new Date();

        const monthName = new Intl.DateTimeFormat('es-MX', {
            month: 'long',
            timeZone: 'UTC', // Opcional, dependiendo de si quieres forzar la zona horaria
        }).format(date);

        // Capitalizar la primera letra (ej. "marzo" -> "Marzo")
        return monthName.charAt(0).toUpperCase() + monthName.slice(1);
    };

    return {
        formatCurrency,
        formatDate,
        toDateInput,
        getMonthName,
    };
}
