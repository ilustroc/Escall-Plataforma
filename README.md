# Plataforma ESCALL Perú

CRM interno para la gestión de cartera, cobranzas, clientes, acuerdos, propuestas, documentos y seguimientos de ESCALL Perú.

La aplicación usa Laravel como backend y Vue mediante Inertia para una experiencia administrativa rápida y coherente.

## Stack

- PHP 8.2 y Laravel 12
- Vue 3 e Inertia 2
- Vite 5 y Tailwind CSS
- MySQL para producción; SQLite en memoria para pruebas
- PHPUnit 11

## Requisitos

- PHP 8.2 con extensiones `mbstring`, `pdo_mysql`, `fileinfo`, `gd` y `zip`
- Composer 2
- Node.js 20 o superior y npm
- MySQL 8 o compatible para entornos no de prueba

## Instalación local

```bash
composer install
npm ci
copy .env.example .env
php artisan key:generate
```

Configura en `.env` la conexión a una base de datos local. Nunca uses credenciales productivas en desarrollo.

La migración `2026_09_13_000000_create_legacy_compatible_tables.php` sirve únicamente para una instalación limpia o pruebas. No se debe ejecutar sobre una base legacy existente sin una revisión previa del esquema.

```bash
# Solo para una base local nueva
php artisan migrate

npm run dev
php artisan serve
```

Para generar assets de producción:

```bash
npm run build
```

## Estructura principal

```text
app/Http/Controllers/  Controladores Laravel
app/Http/Requests/     Validación de formularios
app/Models/            Modelos Eloquent compatibles con tablas legacy
app/Services/          Importación, documentos, métricas y reglas de aplicación
resources/js/          Páginas Vue, layout y componentes Inertia
resources/css/         Estilos Tailwind compartidos
routes/web.php         Rutas web y APIs de sesión protegidas
storage/app/templates/ Plantillas privadas Word y Excel
tests/                 Pruebas Feature y Unit
```

## Módulos

- **Login:** acceso seguro, rate limiting y transición controlada de contraseñas legacy.
- **Dashboard:** KPIs operativos de cartera, pagos y seguimiento.
- **Clientes:** búsqueda, detalle de cuentas y generación de documentos Word.
- **Cargas:** importación validada de DATA, pagos y seguimientos.
- **Propuestas:** registro y descarga privada de Excel.
- **Reportes:** consulta, descarga y eliminación autorizada de documentos.
- **Seguimientos:** registro, historial y evolución de acuerdos.
- **Administración:** usuarios, roles y estado de acceso.

## Pruebas

```bash
php artisan test
composer audit --locked
npm audit --omit=dev
```

## Seguridad y branding

- No versiones `.env`, credenciales, archivos privados, plantillas ni `_legacy_reference`.
- La configuración sensible se obtiene solo desde variables de entorno.
- Las descargas y plantillas se mantienen fuera de `public/`.
- El logotipo corporativo se encuentra en `public/assets/img/logo.png` y se usa en login, sidebar y navegación móvil.
- El branding se basa en la identidad pública de [ESCALL Perú](https://www.escallperu.com/); los recursos remotos no se cargan en tiempo de ejecución.
