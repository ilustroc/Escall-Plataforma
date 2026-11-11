# Plataforma ESCALL Perú

CRM interno para la gestión de cartera, cobranzas, clientes, acuerdos, propuestas, documentos y seguimientos de ESCALL Perú.

La aplicación usa Laravel como backend y Vue mediante Inertia para una experiencia administrativa rápida y coherente.

## Stack

- PHP 8.2 y Laravel 12
- Vue 3 e Inertia 2
- Vite 5 y Tailwind CSS
- MySQL para producción; SQLite en memoria para pruebas
- PHPUnit 11

## Desarrollo aislado con Docker (obligatorio)

No se necesita ni se debe usar PHP, Composer, Node.js, npm o MySQL instalados en Windows. El archivo `compose.yaml` define un proyecto aislado llamado `escall-plataforma-dev`, con PHP 8.3-FPM, Nginx, MySQL 8.4 y Node 20.19. MySQL sólo está disponible dentro de la red Compose como `db`; el puerto 3306 no se publica en Windows.

Antes del primer inicio, abre Docker Desktop y, desde PowerShell en la raíz del repositorio, crea la configuración local ficticia (no modifica `.env`):

```powershell
Copy-Item .env.docker.example .env.docker
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker ps
```

La primera ejecución instala Composer y npm dentro de sus respectivos contenedores. También genera una clave de aplicación exclusiva en un volumen Docker privado; ni la clave ni las contraseñas de prueba se escriben en Git ni en el `.env` existente.

Abre [http://localhost:8088/login](http://localhost:8088/login). Vite de desarrollo queda disponible en `http://localhost:5178`; no es necesario acceder a él directamente.

Los datos de MySQL viven en el volumen exclusivo del proyecto. La migración `2026_09_13_000000_create_legacy_compatible_tables.php` fue diseñada para instalaciones vacías, pero no se ejecuta automáticamente ni debe ejecutarse sobre un esquema legacy sin una revisión y autorización explícitas. Por tanto, el entorno recién levantado sirve para la interfaz y las pruebas; no tiene usuarios MySQL de ejemplo hasta que se autorice un esquema de prueba. El seeder `DockerDevelopmentSeeder` queda preparado para una base Docker vacía y crea sólo el usuario ficticio `demo.escall` (contraseña `DockerOnlyLogin2026!`); tampoco se ejecuta automáticamente.

### Operación desde PowerShell

```powershell
# Iniciar o reconstruir servicios aislados
docker compose --env-file .env.docker up -d --build

# Detener sin borrar datos o volúmenes
docker compose --env-file .env.docker stop

# Ver estado y logs (incluye el worker de cargas masivas)
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs -f app web node db worker

# Laravel y Composer, siempre dentro del contenedor PHP
docker compose --env-file .env.docker exec app php artisan about
docker compose --env-file .env.docker exec app composer install

# Vite y build de producción, siempre dentro del contenedor Node
docker compose --env-file .env.docker exec node npm run build
docker compose --env-file .env.docker logs -f node

# Pruebas: SQLite efímero dentro del contenedor (no usa MySQL ni Windows)
docker compose --env-file .env.docker exec app escall-test

# Shell MySQL aislado; no hay puerto publicado en Windows
docker compose --env-file .env.docker exec db mysql -u escall_docker -p escall_plataforma_docker
```

No use `docker compose down -v`, `docker volume prune` ni comandos de limpieza global para este proyecto. Si necesitas detener y volver a iniciar todos sus contenedores sin borrar datos, usa `docker compose --env-file .env.docker down` seguido de `up -d`; los volúmenes permanecen intactos.

### Administrador demo local

El seeder conserva el asesor `demo.escall` y puede crear un administrador sólo
cuando las credenciales ficticias se proporcionan desde la sesión local de
PowerShell, nunca desde Git:

```powershell
$env:ESCALL_DEMO_ADMIN_USER = 'demo.admin'
$env:ESCALL_DEMO_ADMIN_PASSWORD = Read-Host 'Contraseña local de demo'
docker compose --env-file .env.docker exec -e ESCALL_DEMO_ADMIN_USER -e ESCALL_DEMO_ADMIN_PASSWORD app php artisan db:seed --class='Database\Seeders\DockerDevelopmentSeeder'
Remove-Item Env:ESCALL_DEMO_ADMIN_USER, Env:ESCALL_DEMO_ADMIN_PASSWORD
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
- **Integración masiva:** asignaciones y pagos Blade con cola de base de datos,
  lectura CSV/XLSX incremental, progreso, historial y CSV privado de errores.
- **Propuestas:** registro y descarga privada de Excel.
- **Reportes:** consulta, descarga y eliminación autorizada de documentos.
- **Seguimientos:** registro, historial y evolución de acuerdos.
- **Administración:** usuarios, roles y estado de acceso.

## Pruebas

```bash
docker compose --env-file .env.docker exec app escall-test
docker compose --env-file .env.docker exec app composer audit --locked
docker compose --env-file .env.docker exec node npm audit --omit=dev
```

Consulta [la operación de integraciones masivas](docs/INTEGRACION-MASIVA.md)
para los formatos, estados, límites y comandos del worker.

## Seguridad y branding

- No versiones `.env`, credenciales, archivos privados, plantillas ni `_legacy_reference`.
- La configuración sensible se obtiene solo desde variables de entorno.
- Las descargas y plantillas se mantienen fuera de `public/`.
- El logotipo corporativo se encuentra en `public/assets/img/logo.png` y se usa en login, sidebar y navegación móvil.
- El branding se basa en la identidad pública de [ESCALL Perú](https://www.escallperu.com/); los recursos remotos no se cargan en tiempo de ejecución.
