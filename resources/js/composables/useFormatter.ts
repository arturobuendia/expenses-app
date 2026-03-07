
export function useFormatter() {
    
    const formatCurrency = (value: number | string): string => {
        const num = Number(value);
        if (isNaN(num)) return '$0.00';
        
        return new Intl.NumberFormat('es-MX', { 
            style: 'currency', 
            currency: 'MXN' 
        }).format(num);
    };

    const formatDate = (dateString: string): string => {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        
        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: 'America/Mexico_City'
        }).format(date);
    };

    const getMonthName = (dateInput?: string | Date): string => {
        // Si no le pasamos fecha, usa la actual
        const date = dateInput ? new Date(dateInput) : new Date();
        
        const monthName = new Intl.DateTimeFormat('es-MX', { 
            month: 'long',
            timeZone: 'UTC' // Opcional, dependiendo de si quieres forzar la zona horaria
        }).format(date);
        
        // Capitalizar la primera letra (ej. "marzo" -> "Marzo")
        return monthName.charAt(0).toUpperCase() + monthName.slice(1);
    };

    return {
        formatCurrency,
        formatDate,
        getMonthName
    };
}