# Auditoría funcional previa a la reconstrucción

Referencia examinada: `_legacy_reference/original`, incluida la totalidad de los 9 controladores (y AuthGuard), Router, bootstrap, modelo, servicios, rutas, vistas y JavaScript funcional. No se consultó MySQL ni se ejecutó SQL. Esquema inferido del código; debe contrastarse con una copia autorizada del esquema antes del despliegue.

## Inventario de rutas y comportamiento

| Módulo | Rutas originales | Función y autorización original |
|---|---|---|
| Acceso | GET /, GET/POST /login, GET /logout | Login por `usuario`, password_verify con fallback texto plano, activo nullable; regeneración de ID ya existente. Logout únicamente session_destroy; sin invalidar cookie ni CSRF. |
| Inicio | GET /inicio | Login local al controller; filtros parciales DNI/código/titular y cartera exacta, cuentas agrupadas por DNI. |
| Clientes | GET /clientes, GET /api/clientes, POST /clientes/generar-word | Login; detalle todas las cuentas DNI; API hasta 200 cuentas; DOCX convenio/cancelación, persistencia BLOB y descarga. Identidad y cuenta enviadas por navegador se aceptaban sin reconstrucción servidor. |
| Cargas | GET /cargas, GET /cargas/plantilla; POST /cargas/data, /cargas/pagos, /cargas/seguimientos, /cargas/evolutivo | Basta login para todas, incluidos DATA y pagos. Plantilla implementada sólo para evolutivo pese a comentario anunciando más tipos. Lectura Excel automática, CSV UTF-8 con delimitador coma/punto y coma. |
| Reportes | GET /reportes, GET /reportes/descargar, POST /reportes/eliminar | Basta login; lista global documentos/propuestas con fecha, usuario y paginación 10. Cualquier autenticado descarga o elimina documentos ajenos por ID. |
| Propuestas | GET/POST /propuestas, GET /propuestas/excel, POST /propuestas/eliminar | Login; selección cuentas por DNI, importes por cuenta y sustento concatenado a observación. GET Excel genera y escribe BLOB sin CSRF ni propiedad. Delete exige propietario; listado a través de reportes. |
| Usuarios | GET /admin; POST /admin/crear, /admin/actualizar, /admin/toggle, /admin/eliminar | Administrador; usuario único, rol limitado; evita toggle/delete propio. Store/update escribían password sin hash; update permitía desactivar o degradar al propio administrador. |
| Seguimiento | GET /seguimientos, GET/POST /api/seguimientos, POST /api/seguimientos/eliminar, GET /api/evolutivo | Login; eventos por usuario y rango mensual; administrador `todos=1`; delete propietario/admin; evolutivo hasta 1000 filas. |
| Depuración | GET /vista | Ruta pública a archivo `vista.php` inexistente en copia; se retira del runtime y se documenta. |

No se detectó una ruta funcional de carga/reporte/API abierta a anónimos: los guards estaban repetidos en cada método. El riesgo principal era autorización insuficiente de usuarios autenticados y CSRF universal, además de `/vista`. `AuthGuard::ensureActive` existe pero los módulos no lo llaman, por lo que una sesión de usuario desactivado podía persistir.

## Tablas y SQL

| Tabla / modelo final | Columnas observadas | Reglas SQL preservadas |
|---|---|---|
| usuarios / User | id,usuario,nombre,contrasena,rol,activo | Roles administrador, asesor, supervisor. NULL activo históricamente equivale a 1. No se observó tabla de permisos ni email. |
| cartera_clientes / Cliente | id,cartera,dni,titular,codigo,entidad,cosecha,producto,sub_producto,historico,departamento,rango,deuda_total,deuda_capital,campana | DATA upsert código. Búsqueda LIKE y cartera exacta. Varias cuentas por DNI. |
| documentos / Documento | id,codigo,cliente_id,usuario_id,nombre_archivo,tipo_mime,contenido,creado_en | DOCX guardado BLOB, código 8 caracteres, relación cliente/autor. |
| propuestas / Propuesta | id,cliente_id,usuario_id,propuesta_cliente,fecha_pago,observacion,cuentas_seleccionadas,archivo_blob,nombre_archivo,tipo_archivo,ruta_archivo,creado_en | JSON cuentas código/importes, suma servidor, BLOB XLSX. `ruta_archivo` sólo listado; no lector de rutas arbitrarias. |
| sustentos / Sustento | id,sustento,texto | Catálogo ordenado para propuestas. No existe CRUD en código. |
| pagos / Pago | id inferido, codigo,fecha,monto,asesor,operacion,observacion,usuario_id | Upsert por código/fecha/monto/operación; modifica asesor/observación en duplicado. |
| seguimientos / Seguimiento | id,usuario_id,codigo,fecha,hora,tipo,descripcion,estado,created_at,updated_at | Inserción eventos tipo seguimiento/recordatorio; joins por código; filtros propios/admin. |
| seguimientos_evolutivo / SeguimientoEvolutivo | id,usuario_id,dni,tipo_acuerdo,fecha,monto,estado,gestion,created_at,updated_at | Merge por usuario/DNI/tipo/fecha. Replace elimina únicamente días presentes, global para administrador o propio para otros; estado CUMPLIDO deriva gestión Realizado, demás Pendiente. |

SQL directo estaba concentrado en controladores y Propuesta; usa parámetros, ON DUPLICATE KEY UPDATE, NOW(), DATE_FORMAT, COALESCE y LIMIT/OFFSET. Índices únicos sólo recomendados en comentarios, no verificados. La reconstrucción emplea Eloquent/Query Builder, transacciones y casts. Migración de instalación limpia se crea como código sin ejecutarse contra datos existentes. Mantiene nombres/columnas originales.

## Documentos, plantillas e importaciones

Se encuentran 15 DOCX: convenio 1–5 cuentas, cancelación directa 1–5 cuentas y cancelación en dos cuotas 1–5 cuentas. Cinco XLSX de propuesta 1–5 cuentas. Se conservan bytes de plantillas en almacenamiento privado. Word sustituye datos, deuda total, código único, fechas, facilidad, importes, cuotas y cuenta de depósito. Convenio calcula día de cada mes desde primera fecha. Cancelación dos cuotas exige importes positivos y suma al total (antes sólo comprobado en navegador). Excel preserva plantilla, fórmulas internas y columnas B–L, datos desde fila 6; texto ajeno se escribe explícitamente como texto.

Dompdf, TCPDF, FPDI, FPDF y PHPMailer figuran como dependencias; no existe invocación, endpoint ni plantilla que genere PDF o envíe correo en el código examinado. El comentario `guardarArchivo (PDF)` contradice el único flujo real, que genera XLSX. Los reportes admiten descarga privada de BLOB PDF previamente guardado si existe; no se inventa un generador PDF ausente del legacy.

DATA requiere 14 encabezados; pagos seis; seguimientos seis; evolutivo seis. Alias documento→dni, fecha_pago/fechapago→fecha, monto_pago/montopago→monto, tipo_de_acuerdo→tipo_acuerdo. Importa valores, admite XLSX/XLS/CSV realmente usados por UI, normaliza fechas Excel/ISO/día-mes-año e importes. Nuevos límites: 10 MiB comprimidos, 50 MiB descomprimidos, 10000 filas, 64 columnas y 200000 celdas; rechaza fórmulas aportadas, VBA, objetos embebidos, vínculos externos, XML DTD/entidades y archivos cifrados. Readers explícitos, temporales privados con nombres aleatorios. Filas totalmente vacías se omiten; datos parciales inválidos abortan transacción con número de fila, sin excepción SQL visible.

## Datos sensibles y archivos no publicables

`config/database.php` contiene configuración MySQL real en copia legacy; no se reproducen valores. Las vistas de cliente contienen cuentas bancarias de depósito que pasan a configuración privada y placeholders. `public/error_log` contiene diagnóstico interno y no debe publicarse ni entrar en Git. Plantillas/documentos pueden contener datos bancarios corporativos: conservarlas privadas y someter a revisión autorizada antes de versionarlas. No se identificó un archivo SMTP usado por esta copia; revisar variables MAIL_* al preparar entorno, nunca imprimirlas. Copias `_legacy_reference`, `.env`, logs, caches, vendor, node_modules y uploads se excluyen de Git/publicación. Credenciales existentes no se cambian ni se conectan.

## Adaptaciones explícitas

- Router y páginas PHP sustituidos por Laravel, middleware auth/activo/admin, Form Requests, políticas y Vue/Inertia.
- DATA/pagos y administración reservados a administrador. Los reportes dejan de ser globales: cada usuario ve sus propios documentos/propuestas y el administrador ve todos; descarga/eliminación también exige propietario o administrador.
- Generación/persistencia Excel pasa de GET a POST al crear propuesta; GET es descarga pura. DOCX POST devuelve enlace privado; no iframe ni script inline.
- Bancos: frontend sólo recibe IDs y etiquetas; servidor resuelve valores desde entorno privado. Se bloquea documento si la cuenta no está configurada.
- Contraseñas nuevas/actualizadas con hash; se debe documentar transición controlada de passwords legacy con responsable de despliegue.
- `/vista`, métodos duplicados sin ruta de CargasController y fallback de evolutivo que leía diez registros ajenos no se incorporan; el endpoint funcional usa siempre alcance propio/admin.
- Generador de plantillas se extiende coherentemente a los cuatro tipos; límite explícito de cinco cuentas evita silenciosamente escoger plantilla incapaz de representar más.
- CSRF nativo en todo POST, logout POST y paginación Laravel. No se altera esquema ni datos de producción durante este trabajo.

La copia legacy es respaldo de migración, nunca aplicación productiva. Conviene excluirla de Git y conservar respaldo cifrado offline hasta validar equivalencia en staging; después retirarla del directorio de despliegue.
