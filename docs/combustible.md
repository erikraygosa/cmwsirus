# Cargas de combustible

El módulo está en `/combustible` y utiliza el checkout de este proyecto (`cmwsirus`). No se crearon ni modificaron tablas operativas ni credenciales. La referencia recibida menciona «basecmw»; la base operativa solicitada es **bdcbcsirus**.

## Actualizar una instalación existente: ticket y kilometraje

La captura y edición incluyen **Número de ticket** obligatorio (texto latin1 de hasta 50 caracteres, conserva ceros iniciales) y **Kilometraje (opcional)** (entero entre 0 y 4294967295). Una lectura vacía se guarda como NULL; cero es una lectura válida. Se muestran en Consulta, XLSX y PDF, y se conservan en el borrador y la auditoría. Ticket/Km no cambian la regla existente de duplicados de cargas ni recalculan el importe.

Esta actualización requiere dos columnas nuevas en `tblcargascombustible`. El comando dedicado valida la conexión y base real de combustible, revisa columnas existentes y sólo agrega las faltantes. No modifica `.env`, credenciales, conexiones ajenas, índices, motores ni datos históricos. No se deben ejecutar migraciones de otros módulos para este cambio.

En el checkout de producción, actualizar y revisar primero:

```powershell
git pull --ff-only origin main
php artisan config:clear
php artisan combustible:actualizar-captura
```

Para agregar `NumeroTicket VARCHAR(50) NULL` y `Km INT UNSIGNED NULL`, ejecutar explícitamente en una ventana sin capturas:

```powershell
php artisan combustible:actualizar-captura --aplicar
php artisan config:cache
php artisan view:cache
```

El comando sin `--aplicar` sólo revisa. Se puede repetir: conserva columnas ya presentes, incluso si una ejecución anterior agregó una y quedó pendiente la otra. Agregar columnas ejecuta ALTER TABLE y requiere el permiso ALTER; puede esperar metadatos, con el límite de sesión vigente. Los registros anteriores conservan NULL en las columnas nuevas; al editarlos se solicita el ticket. Recuperar un envío cuyo plan se guardó antes de la actualización utiliza el plan original desde Consulta → Recuperar un envío pendiente, sin agregar valores nuevos al mismo token.

Validación: 53 pruebas aprobadas en memoria y con dobles, tres omitidas por requerir MySQL real. Incluye captura sin Km, con cero y con lectura, ticket con ceros iniciales, validaciones, reintento sin duplicado, edición y limpieza de Km, exportaciones sin fórmulas y actualización idempotente del esquema histórico. No se ejecutó el comando ni pruebas contra producción.

## Aplicar en producción (por el propietario)

1. Revisar y respaldar la aplicación y las bases por el procedimiento habitual. Aplicar la versión aprobada de `origin/main`, que incluye la conexión exclusiva de combustible. Desde el checkout de producción sin cambios locales pendientes:

   ```sh
   git switch main
   git pull --ff-only origin main
   ```

   Si el avance directo no es posible, resolver la integración por el flujo normal de revisión; no usar reset ni force push. Esta entrega no despliega a producción.

2. Instalar las dependencias bloqueadas y compilar en el directorio del proyecto:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   npm ci
   npm run build
   ```

   Se reutilizan AdminLTE, Spatie, Dompdf, Brick Math (dependencia de Laravel) y el lector `html5-qrcode` ya empleado en Imágenes. PHP requiere PDO MySQL, mbstring, iconv, XML, GD y **ZipArchive** para XLSX. No se agregaron paquetes Composer/NPM. El lector usa la versión fijada 2.3.8 desde unpkg.com; permitir ese recurso en la política CSP del navegador si corresponde. Si no carga, la búsqueda manual sigue disponible.

3. **Seleccionar la conexión exclusiva**. La inspección de tablas confirmó las cinco tablas del módulo en `bdcbcsirus`; `mysql2` apunta a `bdcmcsirus` y debe conservarse para los demás módulos. Se agregó el alias `mysql_combustible`, que usa el servidor, puerto, socket y credenciales existentes de `DB2_*`, pero fija únicamente su propia base a `bdcbcsirus`. No modifica `mysql2`, `mysql3` ni las credenciales. En el `.env` privado de producción, cambiar sólo esta selección:

   ```dotenv
   COMBUSTIBLE_DB_CONNECTION=mysql_combustible
   ```

   No cambiar `DB2_DATABASE` ni las otras variables `DB2_*`. El usuario MySQL existente debe tener acceso a `bdcbcsirus`; el módulo ya no solicita LOCK TABLES. El servicio conserva la conexión configurable y compara `SELECT DATABASE()` contra `config('combustible.database')`, además de validar el driver. Si se selecciona `mysql2` con base `bdcmcsirus`, se rechazará. No se crean, renombran, copian ni eliminan bases o tablas. No se accedió a producción para validar esta corrección.

4. Confirmar manualmente las estructuras existentes proporcionadas: `tblcargascombustible`, `tblavisospreciodiesel`, `tblcostos`, `catunidades` y `tblexcepciones`. Deben conservar sus claves únicas, columnas, MyISAM y latin1. Las tablas ya fueron creadas manualmente: **no hay migración operativa ni se debe ejecutar el fixture de tests sobre ellas**. En particular, `ControlDuplicado` debe admitir NULL y existir `uq_carga_token`, `uq_carga_activa` y `uq_aviso_usuario_fecha`. La conexión lee/escribe UTF-8 y MySQL convierte hacia las columnas latin1.

5. Crear únicamente los permisos en la base de autenticación de la aplicación:

   ```sh
   php artisan db:seed --class=CombustiblePermissionSeeder --force
   php artisan permission:cache-reset
   php artisan route:clear
   php artisan config:cache
   php artisan view:cache
   ```

   El seeder es idempotente, usa guard `web` y no asigna roles ni usuarios. Usa guiones bajos para cumplir la convención y el validador de la administración actual:

   | Requisito | Permiso creado |
   | --- | --- |
   | combustible.ver | combustible_ver |
   | combustible.crear | combustible_crear |
   | combustible.editar | combustible_editar |
   | combustible.cancelar | combustible_cancelar |
   | combustible.exportar | combustible_exportar |
   | combustible.precios | combustible_precios |
   | combustible.auditoria | combustible_auditoria |

   En **Administración → Roles** asignar explícitamente `combustible_ver` y las acciones necesarias. Después asignar el rol desde Usuarios. No se asumen IDs ni nombres de roles. El backend exige autenticación, verificación y permisos, y la interfaz oculta las acciones no autorizadas. El reintento de una operación exige su propietario original y el permiso actual de esa acción.

6. Servir por **HTTPS**. La cámara de un teléfono requiere un contexto seguro y permiso del usuario; preferirá la cámara trasera. Comprobar cámara, enfoque y permisos en un teléfono real. La búsqueda manual no requiere cámara. Verificar `/up`, acceso a `/combustible`, una captura controlada y sus reportes en el entorno de aceptación antes de habilitar usuarios de producción. Reiniciar los procesos PHP según el despliegue habitual para que lean la versión nueva.

## Reglas implementadas

- Fechas y control diario en `America/Merida`. Fecha de carga decide el último precio `IdCosto=2, Fecha <= FechaCarga`; fecha de trabajo es independiente. AM/PM con PM inicial. Unidad activa seleccionada por ID; el QR exige el valor exacto, sin recortar espacios ni convertir ceros iniciales, y una sola coincidencia activa.
- Litros positivos, hasta tres decimales. Costo positivo hasta tres decimales. El servidor ignora precios/importes enviados por el cliente y calcula con `Brick\Math\BigDecimal`, redondeo HALF_UP a dos decimales y control del límite DECIMAL(14,2).
- **Convención del descuento pendiente de verificar**: no se encontró una fórmula existente. Se conserva o hereda `PorcDto` sin exponerlo en captura. Si es distinto de cero o está sin definir (NULL) se bloquean capturas y recálculos que requieren calcular un importe; no se inventa un porcentaje. Las ediciones de observaciones, turno o fecha de trabajo conservan el precio histórico.
- Un UUID por captura; borrador y token se conservan por usuario en sessionStorage de la pestaña y durante reintentos. El botón se bloquea durante el envío. «Descartar borrador» comienza otra captura, pero no deshace escrituras previas. El token confirmado devuelve el resultado original, incluso si posteriormente fue cancelado; no crea una carga distinta. Un token con otros valores/usuario se rechaza.
- La clave única permite 80 + 20 litros y bloquea cargas activas idénticas. Cancelar exige motivo y escribe Estatus C, ControlDuplicado NULL y todos los datos de cancelación en una sola sentencia; no elimina filas.
- El aviso diario usa ID estable y la clave única por usuario/fecha, sin solicitar bloqueos explícitos sobre cargas, catálogo o auditoría. Sólo lo solicitan administradores de precios. Guarda MOSTRADO, OMITIDO o ACTUALIZADO y la respuesta cuando corresponde. El acceso manual a precios siempre está disponible.
- La revisión del precio muestra cantidad y detalle de cargas activas. La confirmación cifrada está vinculada a usuario, fecha, precio propuesto, versión de precio/conjunto de registros y caduca en 30 minutos si todavía no comenzó la operación. Revalida bajo bloqueo. «No» mantiene precios/importes históricos; «Sí» recalcula exclusivamente las activas que fueron confirmadas. Las canceladas permanecen intactas. Sólo se elimina el último precio y si ninguna carga, ni cancelada, lo referencia.
- Filtros por rango, tipo de fecha, unidad, turno y estatus; paginación de 25 filas. Totales sobre **todas** las activas filtradas. XLSX auténtico con celdas numéricas y texto explícito (sin fórmulas provenientes de datos), y PDF con los mismos filtros, ambas fechas, turno y estatus. Las canceladas se identifican y no suman.
- Textos de captura/cancelación incompatibles con latin1 se rechazan. El nombre operativo puede transliterarse/abreviarse a 150 caracteres; nombre completo e ID se conservan sin pérdida en la auditoría. No hay operador, inventario ni fotos. El kilometraje es opcional y no se usa para calcular rendimientos.

## MyISAM, auditoría y recuperación

No se utilizan transacciones, rollback ni claves foráneas. Las operaciones **no adquieren `LOCK TABLES`**; la serialización a nivel de tabla en MyISAM, combinada con las claves únicas y los hashes de versión, garantiza todos los invariantes sin bloquear lecturas ni escrituras de otros clientes:

- **`uq_carga_token`** (UNIQUE `TokenRegistro`): el INSERT rechaza atómicamente un segundo intento con el mismo token; el journal reconoce el resultado ya grabado en el siguiente reintento.
- **`uq_carga_activa`** (UNIQUE unidad/fecha/turno/litros/ControlDuplicado): duplicados de contenido son rechazados por la base incluso con tokens distintos.
- **Hash de versión en `active()`**: editar o cancelar compara la huella del registro actual; si otro cliente modificó la fila entre la lectura y la escritura, `matches()` detecta la divergencia y retorna un error recuperable.
- **Precio concurrente**: `validatePendingReferences()` relee el estado de precios y cargas; si cambió desde la confirmación cifrada, el guardado se rechaza antes de escribir.

El journal de escritura anticipada persiste BEGIN, plan completo y confirmación de cada paso antes de ejecutar los cambios. Reintentar el mismo token recupera las etapas pendientes y verifica las escrituras sin repetirlas. Las claves únicas siguen siendo la defensa de última instancia contra duplicados. No se cambian índices existentes.

Antes de escribir se persiste una cabecera BEGIN con huella y hora estable, seguida del plan completo: usuario/ID, operación, entrada, claves y valores antes/después. Cada cambio tiene sus propios fragmentos y confirmación. Los detalles se codifican como JSON ASCII (Unicode escapado, reversible) y se dividen en fragmentos de 170 bytes, con prefijo correlacionado, dentro del TINYTEXT de 255 bytes. Ningún detalle se trunca silenciosamente. `Usuario`, `Modulo` y `Excepcion` respetan VARCHAR(30).

En Auditoría, para un identificador `id`:

- `id|BEGIN|...`: cabecera de operación; por sí sola no demuestra una modificación.
- `id|P|i/n|...`: unir fragmentos i=1…n para recuperar el JSON íntegro.
- `id|OK`: la operación/cambio terminó y su auditoría quedó confirmada.
- Cada cambio tiene ID `md5(idOperacion + ':' + indice)` y su JSON incluye `operation`, acción, tabla, clave, antes/después y actor. Una carga nueva se identifica por su TokenRegistro único; ediciones y cancelaciones incluyen IdCarga.

No se muestra éxito completo si falta una parte. Un 503 incluye el identificador que se debe conservar. Reenviar **los mismos valores y el mismo token** recupera las etapas pendientes, verifica las escrituras y evita repetirlas. Si se perdió la página y el plan completo ya estaba guardado, en Consulta → **Recuperar un envío pendiente** introducir el identificador: sólo el propietario y con el permiso correspondiente puede reproducir el plan original. Si sólo existe parte de la intención y no hubo escritura, se requieren los datos del formulario original. No cambiar opciones ni descartar un borrador para «resolver» un fallo parcial sin revisar su estado.

Los reintentos comparan valores antes/después. Si el registro cambió externamente o cambiaron referencias/precios antes de una inserción pendiente, se detienen para conciliación; no pisan el cambio de Delphi. El plan fijado no agrega cargas posteriores a una confirmación. Las escrituras externas fuera de este módulo no llevan su diario; después de liberar los bloqueos pueden cambiar datos. MyISAM no ofrece una transacción atómica ni durabilidad ante corrupción/apagado, y no se promete recuperación automática de todo fallo externo. Respaldar, inspeccionar los registros correlacionados y conciliar manualmente ante un conflicto. Nunca borrar filas de auditoría para forzar un reintento.

## Validación y límites

Pruebas reproducibles desde el directorio del proyecto, fuera de producción:

```sh
php vendor/bin/pest -c phpunit.combustible.xml
npm run build
```

`phpunit.combustible.xml` fuerza la base de autenticación SQLite en memoria y una clave pública exclusivamente de prueba. El fixture operativo predeterminado también es SQLite en memoria. Sirve para pruebas rápidas, **no** sustituye MySQL 5.7.

Para ejecutar los mismos casos y los de concurrencia real en un contenedor local desechable:

```sh
docker run -d --name cmwsirus-fuel-mysql57 \
  -e MYSQL_ALLOW_EMPTY_PASSWORD=yes -e MYSQL_DATABASE=bdcbcsirus \
  -p 127.0.0.1:33075:3306 mysql:5.7.44
docker exec cmwsirus-fuel-mysql57 mysqladmin ping --silent
# Esperar a que el comando anterior confirme que está listo.
FUEL_MYSQL_TEST=1 php vendor/bin/pest -c phpunit.combustible.xml
# Al terminar, eliminar exclusivamente el contenedor de prueba creado aquí.
docker rm -f cmwsirus-fuel-mysql57
```

El fixture de `tests/Support/FuelSchema.php` crea/recrea **únicamente** tablas del contenedor local en 127.0.0.1:33075, sin aceptar conexiones ni credenciales de producción. Usa las columnas operativas proporcionadas y un catálogo mínimo de unidades. Comprueba versión 5.7; el servicio real verifica base y ejecuta LOCK TABLES. Los 25 casos originales pasaron en MySQL 5.7 (242 aserciones). La suite incorpora pruebas de configuración y selección de conexión mediante dobles, sin consultas reales. La corrección de conexión exclusiva se validó en memoria: 32 aprobadas y dos omitidas, exclusivas de MySQL; no se crearon contenedores ni se ejecutaron los fixtures MySQL. Incluye claves únicas/NULL de MyISAM, cuatro procesos concurrentes, idempotencia, rechazos de duplicado, captura parcial, cancelación y recaptura, avisos concurrentes, permisos HTTP, confirmaciones falsificadas/obsoletas, auditoría fragmentada, fallos inyectados y recuperación, edición histórica, recálculo opcional, restricciones de eliminación, totales y exportaciones.

La validación original del módulo se realizó con MySQL **5.7.44**, tablas **MyISAM / latin1_swedish_ci** de prueba; no se accedió a las tablas reales de bdcbcsirus. En Chromium se comprobó login local, captura, cotización, persistencia de borrador/token tras recarga, fallback sin lector QR, bloqueo de doble envío y confirmación de precios. La cámara física de un celular queda como prueba de aceptación pendiente.

La suite general conserva dos fallos anteriores en AuthenticationTest/RegistrationTest por `route('dashboard')` inexistente; el proyecto tiene `dashboard.index`. No se cambian esas pruebas ni las rutas ajenas para ocultarlos. Las siete omisiones anteriores corresponden a funciones Jetstream deshabilitadas; la suite de combustible agrega dos omisiones (concurrencia y pérdida de sesión) cuando no se solicita MySQL.


## Revisión de rendimiento y captura

- Las pantallas de combustible desactivan únicamente para su propia solicitud el preloader, Google Fonts y los plugins CDN globales que no utilizan. La configuración se restaura al terminar, sin afectar otras pantallas. Select2 4.0.13 se sirve desde `public/vendor/select2` con su licencia MIT y busca unidades activas por AJAX. El QR se descarga sólo al abrir el lector, con tiempo de espera y alternativa manual.
- La captura elimina las notas auxiliares solicitadas, el rótulo «Buscar unidad activa» y el identificador visible. El token UUID continúa oculto y conservado en el borrador para proteger reintentos; el QR actualiza también la selección visible de Select2.
- MySQL suma DECIMAL en el servidor para devolver una sola fila de totales, sin transferir todas las cargas. Auditoría pagina sin contar todo el historial y recupera los fragmentos de la página en una sola lectura por lote, en lugar de repetirla por operación. No se agregaron índices ni se alteraron tablas.
- El alias exclusivo tiene un timeout de conexión de 5 segundos; no es un límite de ejecución de todas las consultas SQL. Las solicitudes de 2 segundos o más registran ruta y duración en el log de Laravel como `Combustible: solicitud lenta`, sin credenciales ni datos de captura. Si la demora continúa en producción, estos registros permiten distinguir latencia del servidor de carga de recursos del navegador.
- Validación de esta revisión: 36 pruebas aprobadas en memoria y con dobles, dos omitidas por requerir MySQL real. El agregado MySQL se verifica con una conexión simulada; no se ejecutó contra producción ni se crearon contenedores. Chromium verificó navegación sin solicitudes a CDN, Select2, búsqueda, borrador/token, QR simulado y bloqueo del doble envío. La cámara física y los tiempos reales del servidor de producción siguen requiriendo comprobación allí.

Después de actualizar desde `origin/main`, ejecutar `php artisan config:clear`, `php artisan config:cache` y `php artisan view:cache`. Los recursos Select2 están incluidos en Git; no hace falta instalar paquetes nuevos. Mantener `COMBUSTIBLE_DB_CONNECTION=mysql_combustible`.

## Eliminación de LOCK TABLES

La causa raíz de las peticiones bloqueadas era `LOCK TABLES` sobre tablas MyISAM: mientras un cliente mantiene el bloqueo, **todas** las lecturas y escrituras de otros clientes sobre esas tablas quedan en espera. Con varias tablas bloqueadas a la vez, un único request lento suspendía el módulo completo para todos los usuarios.

El módulo ya no usa `LOCK TABLES` ni `UNLOCK TABLES`. La prevención de duplicados, la detección de conflictos y la recuperación se apoyan exclusivamente en:

- Restricciones de unicidad de la base (INSERT falla con 1062 si ya existe el token o el contenido).
- Hashes de versión que detectan modificaciones externas antes de escribir (editar, cancelar, precio).
- El journal de escritura anticipada: idempotencia y recuperación sin serialización global.

`SET SESSION lock_wait_timeout = 5` se mantiene para limitar la espera por MDL (p. ej. si un `ALTER TABLE` retiene un metadata lock); no afecta la duración de consultas normales.

Un error 1205 en producción tras aplicar este cambio indicaría un `ALTER TABLE` u otro DDL en curso, no el módulo esperando `LOCK TABLES`.

Validación: 45 pruebas aprobadas (5 para el nuevo comportamiento de `locked()` sin bloqueos, más las 40 anteriores), 2 omitidas por requerir MySQL real. No se ejecutaron pruebas ni cancelaciones en producción desde el entorno de desarrollo.
