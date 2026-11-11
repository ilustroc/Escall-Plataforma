# Integración y cargas masivas

Las cargas de asignación y pagos se procesan de forma asíncrona. El navegador
solamente almacena el archivo en `storage/app/imports/{uuid}` y recibe el ID de
la ejecución; el contenedor `worker` lo valida y lo procesa por lotes de 1 000
filas usando la cola `database`.

## Operación local en Docker

Desde PowerShell, en la raíz del repositorio:

```powershell
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker exec app php artisan migrate --force
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs -f worker
docker compose --env-file .env.docker exec app php artisan queue:monitor imports --max=1
```

El servicio `worker` ejecuta `queue:work` con `--timeout=900`, dos intentos,
reintentos espaciados y un límite de memoria de 384 MB. No se expone MySQL a
Windows: tanto el worker como Laravel usan el servicio interno `db`.

## Rutas y permisos

Las vistas se encuentran bajo `/cargas` y requieren un administrador. Los tipos
disponibles son Asignación, Pagos, CCD y Data. CCD y Data permanecen bloqueados
porque el repositorio no tiene un contrato verificable para persistirlos. Las
rutas legacy de seguimientos y evolutivo siguen disponibles y conservan sus
controles previos.

Un administrador puede ver el historial completo. Los detalles y CSV privados
de errores sólo pueden ser vistos por el creador o por un administrador. Las
descargas se generan por streaming y nunca se publican en `public/`.

## Formato de asignación

CSV UTF-8 o XLSX, de hasta 50 MB y 500 000 filas de datos. La única plantilla
oficial tiene, exactamente y en ese orden, estos encabezados:

```text
CARTERA,DOCUMENTO,NOMBRE,CODIGO,ENTIDAD,COSECHA,PRODUCTO,SUB_PRODUCTO,DEPARTAMENTO,DEUDA_TOTAL,DEUDA_CAPITAL,CAMPANA,FEC_PRO
```

`DOCUMENTO`, `CODIGO` y `FEC_PRO` deben tratarse como texto; `FEC_PRO` es una
fecha válida `YYYYMMDD`. La carga tiene un único período. Un código en notación
científica se rechaza para evitar inventar dígitos perdidos. La identidad de
una asignación es `CODIGO + FEC_PRO`: otro período se conserva como histórico;
una reimportación equivalente se marca como omitida y una con cambios actualiza
solamente ese período.

## Formato de pagos

```text
CODIGO,FECHA,MONTO,ASESOR,OPERACION,OBSERVACION
```

La clave de idempotencia es la existente en `pagos`: código, fecha, monto y
operación. Sólo se actualizan asesor u observación cuando cambian.

## Estados y recuperación

Una ejecución recorre `archivo_recibido`, `pendiente`, `validando`,
`procesando`, `completado` o `fallido`. Primero valida todas las filas y sólo
después inicia escrituras, por lo que un archivo inválido no mezcla cambios
parciales con el histórico. Los fallos estructurales se registran como errores
de validación; un fallo de infraestructura durante los lotes deja el estado
fallido, sin marcar la ejecución como completada, y el job puede reintentarse
de forma segura gracias a los UPSERT idempotentes.

La interfaz consulta el estado cada 1,5 segundos y muestra filas procesadas,
insertadas, actualizadas, omitidas, fallidas y el período real. El porcentaje
sólo se muestra cuando existe un total de filas conocido.

## Límites de infraestructura

PHP acepta archivos de 50 MiB y un cuerpo multipart de 55 MiB; Nginx usa el
mismo margen. El lector CSV usa `fgetcsv` secuencial y XLSX usa OpenSpout, por
lo que no carga la hoja completa en memoria. La validación conserva una clave
por fila para detectar duplicados internos; para el límite objetivo debe
mantenerse el worker con al menos 384 MB y el contenedor PHP con 512 MB.

No elimines volúmenes para recuperar una carga. Si un archivo temporal expira,
el historial y sus contadores se conservan, pero una nueva ejecución requiere
subir el archivo de nuevo.

## Benchmark local verificado (11 de noviembre de 2026)

Se generó y procesó dentro del worker Docker un CSV ficticio de 500 000 filas,
23 000 125 bytes (aprox. 21,9 MiB), con una cartera y período exclusivos de
benchmark. La ejecución validó las 500 000 filas, insertó 500 000 registros y
terminó sin errores en **106 segundos** (aprox. **4 717 filas/segundo**). El
pico observado del cgroup del worker fue **104 558 592 bytes** (aprox. **99,7
MiB**). Al terminar se verificó y eliminó exclusivamente el archivo, la
auditoría y las filas sintéticas del benchmark.

La cifra es una medición local de Docker, no una promesa de producción: el
tiempo puede variar según disco, CPU, latencia de MySQL y concurrencia.
