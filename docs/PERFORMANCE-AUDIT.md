# Auditoría de rendimiento

## Cambios aplicados

- Dashboard: agregados SQL para cartera, clientes, cuentas, pagos, seguimientos y pendientes; actividad reciente limitada a seis filas y relación cliente eager-loaded.
- Búsqueda agrupada: la segunda consulta selecciona sólo `id`, `dni`, `titular`, `codigo` y `cartera`.
- Cliente y propuesta: se seleccionan explícitamente las columnas usadas por las tablas y formularios.
- API de clientes: mantiene el límite funcional de 200 resultados y elimina columnas no mostradas por la búsqueda.
- Reportes: consulta exclusivamente la pestaña activa, selecciona columnas de listado y nunca lee BLOBs de documentos/XLSX hasta la descarga autorizada.
- Usuarios: el listado deja de leer hashes de contraseña.
- Seguimientos y evolutivo: respuestas paginadas en servidor a 50 filas; la interfaz recupera páginas bajo demanda.
- Dashboard: no consulta cartera sin filtros; reduce la primera carga a KPIs y seis actividades.

## Índices recomendados para verificar antes de crear

No se modifica el esquema de producción. En una copia del esquema se deben comprobar primero los índices existentes y, si faltan, evaluar:

| Tabla | Índice candidato | Uso |
|---|---|---|
| `cartera_clientes` | `(dni, codigo)` | detalle y agrupación por cliente |
| `cartera_clientes` | `(cartera, dni)` | filtros por cartera |
| `cartera_clientes` | `codigo` único | import DATA y relaciones por cuenta |
| `seguimientos` | `(usuario_id, fecha, hora, id)` | historial propio ordenado |
| `seguimientos` | `(codigo, fecha)` | detalle y actividad de cuenta |
| `seguimientos_evolutivo` | `(usuario_id, fecha, id)` | historial y pendientes propios |
| `documentos` | `(usuario_id, creado_en)` | reportes privados paginados |
| `propuestas` | `(usuario_id, creado_en)` | reportes privados paginados |
| `pagos` | `(codigo, fecha, monto, operacion)` | idempotencia de importación |

Los filtros `LIKE '%texto%'` sobre DNI, código y titular no aprovechan un índice B-tree convencional. Si el volumen lo requiere, se debe decidir con datos reales entre búsqueda por prefijo, índice full-text compatible o un motor de búsqueda; no se recomienda introducirlo sin medir.
