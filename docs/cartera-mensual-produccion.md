# Plan de migración de cartera mensual para producción

Esta guía no autoriza ni ejecuta cambios en producción. La migración de Laravel se detiene deliberadamente si encuentra filas legacy en `cartera_clientes`: el esquema original no contiene una fecha de proceso verificable y asignarla por defecto alteraría el histórico.

## Antes de programar la intervención

1. Confirmar el número de filas, los índices y las dependencias de `cartera_clientes` en una copia de producción.
2. Exportar una copia verificable de la tabla original, incluyendo `id`, `historico` y `rango`; conservarla fuera de la tabla operativa con controles de acceso apropiados.
3. Definir y aprobar el origen de `FEC_PRO` para cada fila legacy. Debe provenir de una fecha de lote verificable, no de la fecha de ejecución ni de un valor único arbitrario.
4. Preparar una tabla de mapeo `id_legacy -> FEC_PRO` y validar que cada valor tenga exactamente ocho dígitos y represente una fecha real.
5. Ensayar el proceso completo en una copia restaurada de producción dentro de un entorno aislado.

## Ejecución propuesta

1. Guardar un respaldo lógico y comprobar su restauración.
2. Crear la copia de auditoría de las columnas `historico` y `rango` antes de eliminarlas de la operación.
3. Aplicar el mapeo de `FEC_PRO` validado a la copia de datos.
4. Ejecutar una migración aprobada que preserve los `id`, renombre la tabla a `Cartera_Mensual`, cambie `dni/documento` y `titular/nombre`, y cree `UNIQUE(codigo, FEC_PRO)`.
5. Verificar conteos, unicidad, integridad de `documentos.cliente_id` y `propuestas.cliente_id`, y que la consulta de vigentes devuelve exactamente una fila por `codigo`.
6. Ejecutar consultas de control para pagos y seguimientos: ningún pago ni seguimiento debe multiplicarse por las versiones históricas.
7. Habilitar la carga mensual únicamente después de la validación funcional y de rendimiento.

## Reversión

No se debe ejecutar un rollback automático sobre una cartera con datos. La reversión operativa consiste en restaurar la copia validada y los respaldos preparados antes de la intervención.
