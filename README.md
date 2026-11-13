# XPS App · Gestor de finanzas personales

![Laravel](https://img.shields.io/badge/Laravel-12-ff2d20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777bb4?logo=php&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3.5-4fc08d?logo=vuedotjs&logoColor=white)
![Inertia](https://img.shields.io/badge/Inertia-2-9553e9)
![Tailwind](https://img.shields.io/badge/Tailwind-4-06b6d4?logo=tailwindcss&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5-3178c6?logo=typescript&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

Una aplicación de finanzas personales construida con **Laravel 12**, **Inertia 2** y **Vue 3 + TypeScript**, pensada para llevar el control completo de tu dinero en un solo lugar: gastos, ingresos, bóvedas de inversión, tarjetas de crédito, suscripciones y préstamos.

> Interfaz en español (es-MX / MXN). Código e identificadores en inglés.

---

## ✨ Funcionalidades

### 📊 Dashboard
- Totales del mes: ingresos vs. egresos y **flujo neto**.
- **Capital total** por bóveda, con balance calculado en tiempo real (nunca se almacena).
- **Rendimientos mensuales** estimados de tus inversiones.
- Dinero en la calle (te deben) vs. deudas pendientes (debes).
- Próximos gastos fijos y un vistazo a los últimos movimientos.

### 💸 Gastos
- Alta, edición y borrado con categoría, fecha y descripción.
- Compras a **meses sin intereses (MSI)** con calendario de parcialidades.
- Distinción entre gasto real y **transferencia** (p. ej. pago de tarjeta).

### 📥 Acciones por lote
- Registra varios gastos de una sola vez con **vista previa** antes de guardar.

### 💰 Ingresos
- CRUD de ingresos vinculados a una bóveda y categoría.

### 🏦 Bóvedas de capital
- Cuentas de ahorro/inversión con **saldo inicial** y tasa de rendimiento anual.
- Cálculo de rendimiento diario/mensual/anual, con **tope de rendimiento** (`yield_cap`).
- El saldo se computa: `saldo_inicial + ingresos − gastos`.

### 💳 Tarjetas de crédito
- Límite de crédito, **día de corte** y **día de pago** configurables.
- Ciclo de facturación automático y **estados de cuenta** con liquidación **FIFO** (los pagos cubren primero los cortes más viejos).
- Crédito disponible calculado y compras a MSI integradas al corte.

### 🔁 Suscripciones
- Cobros recurrentes **mensuales o anuales**.
- Facturación automática de suscripciones vencidas (sin necesidad de cron).
- Costo equivalente mensual para que una suscripción anual no infle tus gastos.

### 🤝 Préstamos
- Dirección bidireccional: **prestaste** (`lent`) o **pediste prestado** (`borrowed`).
- Marca préstamos como saldados (`is_paid`).

### 🏷️ Categorías
- Catálogo tipado (`income` | `expense`) con estado activo/inactivo y scopes `active()` / `ofType()`.

### 🔐 Seguridad (Fortify)
- Registro e inicio de sesión, verificación de email, cambio de contraseña y **autenticación de dos factores (2FA)**.

### 📱 Ingreso por WhatsApp *(en desarrollo)*
- Webhook de **Whaas** + categorización con **IA (OpenAI)** para registrar gastos/ingresos/préstamos enviando un mensaje.
- *Nota:* aún falta registrar la ruta y la autenticación del webhook (ver [Roadmap](#-roadmap)).

---

## 🧰 Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Vue 3.5, Inertia 2, TypeScript |
| UI | Tailwind CSS 4 (CSS-first), shadcn-vue / reka-ui, lucide-vue-next |
| Tablas | TanStack Table (Vue) |
| Auth | Laravel Fortify (2FA incluido) |
| Rutas tipadas | Laravel Wayfinder |
| Testing | Pest 4, SQLite `:memory:` con `RefreshDatabase` |
| Linting | Pint (PHP), ESLint + Prettier (JS) |

---

## 📋 Requisitos

- **PHP ≥ 8.4** con las extensiones habituales de Laravel.
- **Composer 2**
- **Node.js ≥ 20** y npm
- Base de datos: **SQLite** por defecto (sin configuración adicional). También compatible con MySQL/MariaDB.

---

## 🚀 Instalación

### Opción rápida (recomendada)

```bash
composer setup
```

Esto ejecuta en orden: `composer install`, copia `.env.example` → `.env`, genera la `APP_KEY`, migra la base de datos, instala dependencias de npm y compila los assets.

### Paso a paso

```bash
# 1. Dependencias PHP
composer install

# 2. Configuración
cp .env.example .env
php artisan key:generate

# 3. Base de datos (SQLite por defecto)
php artisan migrate

# 4. Dependencias frontend y build
npm install
npm run build
```

### Crear la cuenta de administrador

```bash
php artisan app:create-admin
```

Pide el email, nombre y contraseña del administrador (la contraseña se escribe de forma oculta). Cada instalación tiene credenciales propias.

Para automatizar un despliegue (CI/CD), pasa las opciones directamente:

```bash
php artisan app:create-admin --email=admin@example.com --name=Admin --password="$APP_ADMIN_PASSWORD"
```

Si el administrador ya existe, el comando no lo modifica a menos que uses `--update`.

> **¿Otra base de datos?** Ajusta `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en tu `.env` antes de migrar.

---

## ▶️ Ejecutar en local

```bash
composer dev
```

Levanta en paralelo el servidor (`artisan serve`), el worker de colas y Vite. La app queda disponible en `http://localhost:8000`.

Para validar el renderizado SSR:

```bash
composer dev:ssr
```

> Incluye el servidor SSR de Inertia y `artisan pail` para logs en tiempo real.

---

## 🌱 Datos de demostración

```bash
php artisan migrate:fresh --seed
```

Crea bóvedas, categorías, gastos, ingresos, una tarjeta de crédito, suscripciones y préstamos de ejemplo en español.

> ⚠️ El seeder de datos (`DatabaseSeeder`) **no es idempotente**: volver a ejecutarlo duplica los registros de ejemplo. Usa `migrate:fresh --seed` para un reinicio limpio. El seeder **no crea cuentas de usuario**; el administrador se crea por separado con `php artisan app:create-admin`.

---

## 🧪 Demo pública

La portada (`/`) tiene un botón **Ver demo** que entra a la app con datos de ejemplo, sin cuenta y con escritura habilitada.

- La demo corre sobre **su propia base de datos SQLite** (conexión `demo`, archivo en `DEMO_DB_PATH`, por defecto `database/demo.sqlite`), así que nunca toca los datos reales.
- Se reconstruye con `php artisan demo:refresh`. El comando arma el archivo nuevo aparte y lo intercambia de forma atómica, para que nadie vea errores mientras se regenera.
- `routes/console.php` la reinicia todos los lunes a las 04:00. En producción necesitas el cron de `php artisan schedule:run`.
- Cada IP tiene cupo de 20 escrituras y 120 peticiones por minuto (`config/demo.php`). Al pasarse, la IP queda bloqueada una hora, también para lecturas; los contadores viven en la caché real, así que sobreviven al reinicio.
- El visitante se autentica por request con `Auth::onceUsingId()`: la sesión real nunca queda autenticada.
- La base de la demo es desechable: vive en `database/demo.sqlite` y **se construye sola** en la primera visita si el archivo no existe (`DemoDatabase::ensureExists()`, unos 2 segundos). No necesita volumen persistente ni paso de deploy.

---

## 🧪 Demo en producción

Nada especial: como el archivo no importa, no hace falta montarlo en un volumen. En un contenedor con filesystem efímero (Coolify, Nixpacks) cada deploy lo borra y la primera visita lo vuelve a crear. Si prefieres que el primer visitante no pague esos dos segundos, corre el comando en el pre-deploy:

```bash
php artisan demo:refresh
```

Y para el reinicio semanal:

```cron
* * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1
```

---

## ✅ Tests y calidad

```bash
# Gate completo de CI local: ESLint, Prettier, vue-tsc y Pest
composer ci:check

# Solo backend: Pint + Pest
composer test

# Un test específico
php artisan test --filter=DashboardTest
```

Formateo:

```bash
composer lint          # Pint (PHP)
npm run format         # Prettier (Vue/TS)
```

Los tests corren sobre SQLite `:memory:` con `RefreshDatabase` aplicado a todos los tests de `tests/Feature`. No requieren servicios externos.

---

## 🗂️ Estructura del proyecto

```
app/
├── Actions/Fortify/        # Acciones de autenticación de Fortify
├── Http/
│   ├── Controllers/        # Controladores (módulos + settings + Api)
│   ├── Middleware/         # HandleInertiaRequests, etc.
│   └── Requests/           # Form Requests
├── Models/                 # Expense, Income, Vault, Card, Loan, Subscription…
├── Providers/
└── Services/               # SubscriptionBiller, AiCategorization, Whaas, FinanceManager
resources/js/
├── components/             # Componentes UI (shadcn-vue) y compartidos
├── layouts/                # Layouts de la app
└── pages/                  # Páginas Inertia (1 página = 1 archivo .vue)
routes/                     # web.php, settings.php, console.php
database/                   # Migraciones, factories y seeders
tests/                      # Pest (Feature + Unit)
```

Cada controlador renderiza su página Inertia por nombre: `DashboardController` → `Dashboard/Index` → `resources/js/pages/Dashboard/Index.vue`.

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Para empezar:

1. Haz un **fork** del repositorio.
2. Crea una rama con un nombre descriptivo (`feat/`, `fix/`, `docs/`).
3. Escribe tus cambios con pruebas cuando aplique.
4. Verifica que todo pase: `composer ci:check`.
5. Abre un **pull request** con una descripción clara del cambio y su motivación.

### Convenciones

- Commits en formato **Conventional Commits** (`feat:`, `fix:`, `docs:`, `test:`, …).
- Código PHP con **Pint** (preset Laravel) y frontend con **Prettier** (4 espacios, LF, comillas simples, punto y coma).
- UI/copy en español; identificadores, variables y comentarios de código en inglés.
- Importa los componentes con el alias `@/` y separa los `import type` (regla de ESLint obligatoria).

---

## 🗺️ Roadmap

- [ ] **WhatsApp:** registrar la ruta del webhook (`routes/api.php`) y añadir autenticación (token secreto / firma) en `WhaasWebhookController`.
- [ ] Scheduler de cobros de suscripciones (hoy se materializan bajo demanda).
- [ ] Reportes y gráficas históricas por categoría.
- [ ] Exportación a CSV/Excel.

---

## 📄 Licencia

Distribuido bajo la licencia **MIT**. Consulta el archivo `LICENSE` para más detalles.
