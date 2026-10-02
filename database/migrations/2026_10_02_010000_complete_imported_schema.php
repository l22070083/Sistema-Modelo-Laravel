<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('legacy_schema') as $name => $definition) {
            if (Schema::hasTable($name)) {
                continue;
            }
            Schema::create($name, function (Blueprint $table) use ($definition, $name) {
                foreach ($definition['columns'] as $field) {
                    $name = $field['Field'];
                    $type = $field['Type'];
                    if (str_contains($field['Extra'], 'auto_increment')) {
                        $table->increments($name);

                        continue;
                    }
                    if (str_contains($type, 'int')) {
                        $column = $table->integer($name);
                    } elseif (preg_match('/(?:var)?char\((\d+)\)/', $type, $matches)) {
                        $column = $table->string($name, (int) $matches[1]);
                    } elseif (str_contains($type, 'decimal')) {
                        $column = $table->decimal($name, 5, 2);
                    } elseif (in_array($type, ['datetime', 'timestamp'], true)) {
                        $column = $table->dateTime($name);
                    } elseif ($type === 'date') {
                        $column = $table->date($name);
                    } else {
                        $column = $table->text($name);
                    }
                    if ($field['Null'] === 'YES') {
                        $column->nullable();
                    }
                    if ($field['Default'] !== null && ! str_contains(strtoupper((string) $field['Default']), 'CURRENT_TIMESTAMP')) {
                        $column->default($field['Default']);
                    }
                }
                $groups = [];
                foreach ($definition['indexes'] as $index) {
                    if ($index['Key_name'] === 'PRIMARY') {
                        continue;
                    }
                    $groups[$index['Key_name']]['fields'][] = $index['Column_name'];
                    $groups[$index['Key_name']]['unique'] = ! $index['Non_unique'];
                }
                foreach ($groups as $indexName => $index) {
                    $scopedName = $table->getTable().'_'.$indexName;
                    if ($index['unique']) {
                        $table->unique($index['fields'], $scopedName);
                    } else {
                        $table->index($index['fields'], $scopedName);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('No se elimina automáticamente el esquema importado.');
        }
        foreach (array_reverse(array_keys(config('legacy_schema'))) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
