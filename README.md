tu-proyecto/
├─ app/
│  ├─ Core/
│  │  ├─ DB.php                 # Conexión PDO (MySQL), singleton
│  │  ├─ Router.php             # Router sencillo
│  │  ├─ Auth.php               # Manejo de sesión/roles
│  │  └─ Response.php
│  ├─ Controllers/
│  │  ├─ AuthController.php     # login, logout
│  │  ├─ DashboardController.php# inicio
│  │  ├─ ClienteController.php  # buscar, vista del cliente, generar Word
│  │  ├─ PropuestaController.php# formulario, guardar, generar Excel
│  │  ├─ ReporteController.php  # índice (acordeón), listados/descargas
│  │  ├─ SeguimientoController.php
│  │  └─ AdminController.php    # usuarios, asesores, permisos
│  ├─ Models/
│  │  ├─ Usuario.php
│  │  ├─ Cliente.php
│  │  ├─ Propuesta.php
│  │  └─ Seguimiento.php
│  └─ Services/
│     ├─ WordService.php        # PHPWord
│     └─ ExcelService.php       # PhpSpreadsheet
├─ bootstrap/
│  └─ app.php                   # arranque común (autoload, sesión, .env)
├─ config/
│  ├─ app.php
│  └─ database.php
├─ public/
│  ├─ index.php                 # front controller (única entrada)
│  ├─ .htaccess                 # rewrites a /public/index.php
│  └─ assets/
│     ├─ css/
│     │  └─ app.css
│     ├─ js/
│     │  ├─ common/
│     │  │  ├─ api.js           # fetch/ajax genérico
│     │  │  ├─ ui.js            # helpers UI (modales, toasts, acordeón)
│     │  │  └─ utils.js         # validaciones, formateos
│     │  └─ pages/              # JS separados por pantalla
│     │     ├─ login.js
│     │     ├─ inicio.js
│     │     ├─ clientes.js
│     │     ├─ propuestas.js
│     │     ├─ reportes.js
│     │     ├─ seguimientos.js
│     │     └─ admin.js
│     └─ img/
├─ resources/
│  ├─ templates/                # (antes: plantillas) .docx, etc.
│  └─ views/
│     ├─ layouts/
│     │  └─ app.php             # header/nav/footer
│     ├─ auth/login.php
│     ├─ dashboard/index.php
│     ├─ clientes/show.php
│     ├─ propuestas/form.php
│     ├─ reportes/index.php     # acordeón Word/Excel
│     ├─ seguimientos/index.php
│     └─ admin/{usuarios.php, asesores.php, permisos.php}
├─ routes/
│  └─ web.php                   # definición de rutas
├─ storage/
│  ├─ logs/
│  ├─ docs/                     # generados Word
│  ├─ excels/                   # generados Excel
│  └─ temp/
├─ vendor/
├─ .env.example
├─ .env
├─ composer.json
└─ README.md
