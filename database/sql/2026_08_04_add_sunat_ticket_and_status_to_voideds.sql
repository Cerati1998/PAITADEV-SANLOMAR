-- =====================================================================
--  voideds: columnas sunat_ticket y sunat_status  (PRODUCCION)
-- =====================================================================
--
--  Equivalente manual de la migracion:
--    database/migrations/2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table.php
--
--  Por que existe este fichero: deploy.php no engancha `artisan:migrate` en el
--  flujo de Deployer (recipe/laravel.php tampoco lo hace por defecto), asi que
--  el esquema de produccion se actualiza a mano.
--
--  Dos detalles que NO son obvios y que este script respeta:
--
--   1. VARCHAR(191), no 255. app/Providers/AppServiceProvider.php:25 fija
--      `Schema::defaultStringLength(191)`, asi que `$table->string()` genera
--      VARCHAR(191) en todo el proyecto. Poner 255 aqui dejaria produccion con
--      un esquema distinto del que produce la migracion.
--
--   2. La collation se DERIVA de la columna `hash` de la propia tabla, no se
--      fija a ciegas. En el entorno local las columnas nuevas quedaron en
--      utf8mb4_general_ci mientras `hash` esta en utf8mb4_unicode_ci, porque
--      el ALTER hereda el default del servidor y no el de la tabla. Derivarla
--      garantiza que produccion quede consistente con su propia tabla, sea
--      cual sea el default de su servidor.
--
--  Es IDEMPOTENTE: se puede ejecutar varias veces sin error. Cada ALTER va
--  guardado por information_schema, porque MySQL 8 no soporta
--  `ADD COLUMN IF NOT EXISTS` (MariaDB si, pero no queremos depender de ello).
--
--  NO hace falta backfill. Una fila historica con sunat_status = NULL es un
--  estado con significado propio: la Fase 1 fijo la semantica de tres estados
--  de modo que las filas anteriores a esta columna se distinguen justamente
--  por tenerla en NULL con sunatResponse poblado. Rellenarlas la destruiria.
--
--  Uso:
--    mysqldump -u USUARIO -p NOMBRE_BASE voideds > voideds_backup.sql   # primero
--    mysql -u USUARIO -p NOMBRE_BASE < 2026_08_04_add_sunat_ticket_and_status_to_voideds.sql
-- =====================================================================

-- ---------------------------------------------------------------------
--  0. Collation objetivo: la de `hash`, con el default de la tabla como
--     respaldo por si `hash` no estuviera (no deberia).
-- ---------------------------------------------------------------------
SET @target_collation := (
    SELECT COLLATION_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'voideds'
      AND COLUMN_NAME  = 'hash'
);

SET @target_collation := IFNULL(@target_collation, (
    SELECT TABLE_COLLATION
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'voideds'
));

-- Aborta de forma legible si la tabla no existe en esta base de datos.
-- El identificador entre backticks solo se evalua en el caso malo: MySQL corta
-- ahi con "Unknown column 'ERROR_la_tabla_voideds_no_existe...'", que se lee
-- solo. Si la tabla existe, esto ejecuta un inofensivo SELECT 1.
SET @preflight := IF(
    @target_collation IS NULL,
    'SELECT `ERROR_la_tabla_voideds_no_existe_en_esta_base_de_datos`',
    'SELECT 1 AS preflight_ok'
);
PREPARE stmt FROM @preflight; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
--  1. sunat_ticket  — VARCHAR(191) NULL, despues de `hash`
--     Guarda el ticket que SUNAT devuelve al aceptar una comunicacion de baja
--     para procesamiento asincrono. NULL = el envio nunca llego a SUNAT (y por
--     tanto la fila admite reenvio del XML).
-- ---------------------------------------------------------------------
SET @ddl := (
    SELECT IF(
        COUNT(*) = 0,
        CONCAT(
            'ALTER TABLE `voideds` ADD COLUMN `sunat_ticket` VARCHAR(191) ',
            'COLLATE ', @target_collation, ' NULL DEFAULT NULL AFTER `hash`'
        ),
        'SELECT ''sunat_ticket ya existe, ALTER omitido'' AS aviso'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'voideds'
      AND COLUMN_NAME  = 'sunat_ticket'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
--  2. sunat_status  — VARCHAR(191) NULL, despues de `sunat_ticket`
--     Respaldo del enum App\Enums\VoidedStatus (aceptada / pendiente /
--     rechazada). NULL = fila historica, anterior a esta columna.
-- ---------------------------------------------------------------------
SET @ddl := (
    SELECT IF(
        COUNT(*) = 0,
        CONCAT(
            'ALTER TABLE `voideds` ADD COLUMN `sunat_status` VARCHAR(191) ',
            'COLLATE ', @target_collation, ' NULL DEFAULT NULL AFTER `sunat_ticket`'
        ),
        'SELECT ''sunat_status ya existe, ALTER omitido'' AS aviso'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'voideds'
      AND COLUMN_NAME  = 'sunat_status'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
--  3. Registrar la migracion como ya aplicada
--     Sin esto, un futuro `php artisan migrate` intentaria crear columnas que
--     ya existen y reventaria el despliegue. Los SELECT sobre tablas derivadas
--     evitan el error 1093 de MySQL (no se puede leer la tabla destino de un
--     INSERT directamente en su propia subconsulta).
-- ---------------------------------------------------------------------
SET @batch := (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM (SELECT `batch` FROM `migrations`) AS b);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT src.migration, src.batch
FROM (
    SELECT '2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table' AS migration,
           @batch AS batch
) AS src
WHERE NOT EXISTS (
    SELECT 1
    FROM (SELECT `migration` FROM `migrations`) AS m
    WHERE m.`migration` = '2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table'
);

-- ---------------------------------------------------------------------
--  4. Verificacion — ejecutar y comprobar a ojo
-- ---------------------------------------------------------------------
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT,
       COLLATION_NAME, ORDINAL_POSITION
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'voideds'
  AND COLUMN_NAME IN ('hash', 'sunat_ticket', 'sunat_status')
ORDER BY ORDINAL_POSITION;
--  Esperado: las tres consecutivas; sunat_ticket y sunat_status en
--  varchar(191), IS_NULLABLE = YES, COLUMN_DEFAULT = NULL, y la misma
--  COLLATION_NAME que `hash`.

SELECT `id`, `migration`, `batch`
FROM `migrations`
WHERE `migration` = '2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table';
--  Esperado: exactamente 1 fila.

-- =====================================================================
--  ROLLBACK (solo si hay que revertir; DESTRUYE los tickets guardados,
--  y con ellos la unica via de reconsulta de las bajas pendientes)
-- =====================================================================
--  ALTER TABLE `voideds` DROP COLUMN `sunat_status`;
--  ALTER TABLE `voideds` DROP COLUMN `sunat_ticket`;
--  DELETE FROM `migrations`
--   WHERE `migration` = '2026_08_04_100000_add_sunat_ticket_and_status_to_voideds_table';
-- =====================================================================
