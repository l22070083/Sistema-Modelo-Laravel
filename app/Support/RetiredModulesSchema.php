<?php

namespace App\Support;

use PDO;
use RuntimeException;

/** Explicit schema retirement shared by deployment and versioned migrations. */
final class RetiredModulesSchema
{
    public const TABLES = ['genero', 'resultados_salud', 'resultados_chaside'];
    public const COLUMNS = [
        'user' => ['genero_id'],
        'expediente_alumno' => ['genero', 'categoria_atencion', 'atencion_prioritaria', 'categoria_manual', 'motivo_clasificacion', 'clasificado_por'],
        'pregunta' => ['tipo_riesgo'],
        'respuesta_alumno' => ['valor_alerta'],
    ];

    public static function filterDefinition(array $schema): array
    {
        foreach (self::TABLES as $table) {
            unset($schema[$table]);
        }
        foreach (self::COLUMNS as $table => $columns) {
            if (!isset($schema[$table])) {
                continue;
            }
            $schema[$table]['columns'] = array_values(array_filter($schema[$table]['columns'], fn ($column) => !in_array($column['Field'], $columns, true)));
            $removedIndexes = [];
            foreach ($schema[$table]['indexes'] as $index) {
                if (in_array($index['Column_name'], $columns, true)) {
                    $removedIndexes[] = $index['Key_name'];
                }
            }
            $schema[$table]['indexes'] = array_values(array_filter($schema[$table]['indexes'], fn ($index) => !in_array($index['Key_name'], $removedIndexes, true)));
        }

        return $schema;
    }

    private static function quote(string $name): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $name)) {
            throw new RuntimeException('Identificador SQL inválido.');
        }

        return '`'.$name.'`';
    }

    public static function columns(PDO $db, string $table): array
    {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            return array_column($db->query('PRAGMA table_info('.self::quote($table).')')->fetchAll(PDO::FETCH_ASSOC), 'name');
        }
        $statement = $db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
        $statement->execute([$table]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function plan(PDO $db): array
    {
        $tables = array_values(array_filter(self::TABLES, fn ($table) => self::columns($db, $table)));
        $columns = [];
        foreach (self::COLUMNS as $table => $removed) {
            $present = array_values(array_intersect($removed, self::columns($db, $table)));
            if ($present) {
                $columns[$table] = $present;
            }
        }

        return ['tables' => $tables, 'columns' => $columns];
    }

    public static function apply(PDO $db): array
    {
        $plan = self::plan($db);
        if ($plan === ['tables' => [], 'columns' => []]) {
            return $plan;
        }
        $mysql = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        if ($mysql) {
            // Resolve every dependency before dropping a column or table. Never disable FK checks.
            $foreign = $db->query('SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
            $removed = [];
            foreach ($foreign as $key) {
                if (in_array($key['TABLE_NAME'], self::TABLES, true)
                    || in_array($key['REFERENCED_TABLE_NAME'], self::TABLES, true)
                    || in_array($key['COLUMN_NAME'], self::COLUMNS[$key['TABLE_NAME']] ?? [], true)) {
                    $id = $key['TABLE_NAME'].'.'.$key['CONSTRAINT_NAME'];
                    if (!isset($removed[$id])) {
                        $db->exec('ALTER TABLE '.self::quote($key['TABLE_NAME']).' DROP FOREIGN KEY '.self::quote($key['CONSTRAINT_NAME']));
                        $removed[$id] = true;
                    }
                }
            }
        }

        if (self::columns($db, 'coordinador_permiso')) {
            $db->exec("DELETE FROM coordinador_permiso WHERE seccion='clasificacion'");
            if ($mysql) {
                $checks = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='coordinador_permiso' AND CONSTRAINT_TYPE='CHECK'")->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('ck_permiso_seccion', $checks, true)) {
                    $db->exec('ALTER TABLE coordinador_permiso DROP CHECK ck_permiso_seccion');
                    $db->exec("ALTER TABLE coordinador_permiso ADD CONSTRAINT ck_permiso_seccion CHECK(seccion IN ('personales','antecedentes','cuestionario','notas','bitacora'))");
                }
            }
        }

        if (self::columns($db, 'expediente_historial')) {
            $db->exec("DELETE FROM expediente_historial WHERE accion IN ('CLASIFICACION','CLASIFICAR')");
        }
        if (self::columns($db, 'auditoria_sistema')) {
            $db->exec("DELETE FROM auditoria_sistema WHERE evento IN ('CLASIFICACION','CLASIFICAR','CATALOGO_GENERO','RESULTADO_SALUD','RESULTADO_CHASIDE')");
            $entries = $db->query('SELECT id, datos FROM auditoria_sistema WHERE datos IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
            $update = $db->prepare('UPDATE auditoria_sistema SET datos=? WHERE id=?');
            $delete = $db->prepare('DELETE FROM auditoria_sistema WHERE id=?');
            foreach ($entries as $entry) {
                $data = json_decode($entry['datos'], true);
                if (!is_array($data)) {
                    continue;
                }
                if (($data['catalogo'] ?? null) === 'genero' || in_array($data['tipo'] ?? null, ['clasificacion', 'atencion', 'resultados'], true)) {
                    $delete->execute([$entry['id']]);
                    continue;
                }
                $clean = self::cleanData($data);
                if ($clean !== $data) {
                    $update->execute([json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $entry['id']]);
                }
            }
        }

        foreach ($plan['columns'] as $table => $columns) {
            if (!$mysql) {
                // SQLite requires indexes mentioning removed columns to be removed first.
                foreach ($db->query('PRAGMA index_list('.self::quote($table).')')->fetchAll(PDO::FETCH_ASSOC) as $index) {
                    $fields = array_column($db->query('PRAGMA index_info('.self::quote($index['name']).')')->fetchAll(PDO::FETCH_ASSOC), 'name');
                    if (array_intersect($fields, $columns)) {
                        $db->exec('DROP INDEX '.self::quote($index['name']));
                    }
                }
            }
            foreach ($columns as $column) {
                $db->exec('ALTER TABLE '.self::quote($table).' DROP COLUMN '.self::quote($column));
            }
        }
        foreach ($plan['tables'] as $table) {
            $db->exec('DROP TABLE '.self::quote($table));
        }
        if (self::plan($db) !== ['tables' => [], 'columns' => []]) {
            throw new RuntimeException('El retiro del esquema quedó incompleto.');
        }

        return $plan;
    }

    private static function cleanData(array $data): array
    {
        $removed = array_merge(...array_values(self::COLUMNS));
        foreach ($data as $key => &$value) {
            if (is_string($key) && in_array($key, $removed, true)) {
                unset($data[$key]);
            } elseif ($key === 'secciones' && is_array($value)) {
                $value = array_values(array_diff($value, ['clasificacion']));
            } elseif (is_array($value)) {
                $value = self::cleanData($value);
            }
        }
        unset($value);

        return $data;
    }
}
