<?php

namespace Appzcoder\CrudGenerator\Commands;

use Illuminate\Console\GeneratorCommand;

class CrudMigrationCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:migration
                            {name : The name of the migration.}
                            {--schema= : The name of the schema.}
                            {--indexes= : The fields to add an index to.}
                            {--foreign-keys= : Foreign keys.}
                            {--pk=id : The name of the primary key.}
                            {--soft-deletes=no : Include soft deletes fields.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Migration';

    /**
     *  Migration column types collection.
     *
     * @var array
     */
    protected $typeLookup = [
        'char' => 'char',
        'date' => 'date',
        'datetime' => 'dateTime',
        'time' => 'time',
        'timestamp' => 'timestamp',
        'text' => 'text',
        'mediumtext' => 'mediumText',
        'longtext' => 'longText',
        'json' => 'json',
        'jsonb' => 'jsonb',
        'binary' => 'binary',
        'number' => 'integer',
        'integer' => 'integer',
        'bigint' => 'bigInteger',
        'mediumint' => 'mediumInteger',
        'tinyint' => 'tinyInteger',
        'smallint' => 'smallInteger',
        'boolean' => 'boolean',
        'decimal' => 'decimal',
        'double' => 'double',
        'float' => 'float',
        'enum' => 'enum',
    ];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return config('crudgenerator.custom_template')
        ? config('crudgenerator.path') . '/migration.stub'
        : __DIR__ . '/../stubs/migration.stub';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace($this->laravel->getNamespace(), '', $name);
        $datePrefix = date('Y_m_d_His');

        return database_path('/migrations/') . $datePrefix . '_create_' . $name . '_table.php';
    }

    /**
     * Build the model class with the given name.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $tableName = $this->argument('name');
        $className = 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))) . 'Table';

        $fieldsToIndex = trim($this->option('indexes')) != '' ? explode(',', $this->option('indexes')) : [];
        $foreignKeys = trim($this->option('foreign-keys')) != '' ? explode(',', $this->option('foreign-keys')) : [];

        $schema = rtrim($this->option('schema'), ';');
        $fields = explode(';', $schema);

        $data = array();

        if ($schema) {
            $x = 0;
            foreach ($fields as $field) {
                $fieldArray = explode('#', $field);
                $data[$x]['name'] = trim($fieldArray[0]);
                $data[$x]['type'] = trim($fieldArray[1]);
                if (($data[$x]['type'] === 'select'
                    || $data[$x]['type'] === 'enum')
                    && isset($fieldArray[2])
                ) {
                    $options = trim($fieldArray[2]);
                    $data[$x]['options'] = str_replace('options=', '', $options);
                }

                $data[$x]['modifier'] = '';

                $modifierLookup = [
                    'comment',
                    'default',
                    'first',
                    'nullable',
                    'unsigned',
                ];

                if (isset($fieldArray[2]) && in_array(trim($fieldArray[2]), $modifierLookup)) {
                    $data[$x]['modifier'] = "->" . trim($fieldArray[2]) . "()";
                }

                $x++;
            }
        }

        $tabIndent = '    ';

        // One entry per statement of the Schema::create closure; they are laid out at the end.
        $statements = [];

        // `id()` for the default key: a signed INT key cannot be referenced by an unsigned BIGINT foreign key column.
        $primaryKey = $this->option('pk');
        $statements[] = $primaryKey === 'id'
            ? "\$table->id();"
            : "\$table->increments('" . $primaryKey . "');";

        if ($this->option('soft-deletes') == 'yes') {
            $statements[] = "\$table->softDeletes();";
        }

        foreach ($data as $item) {
            $modifier = $item['modifier'];

            if (isset($this->typeLookup[$item['type']])) {
                $type = $this->typeLookup[$item['type']];

                if ($type === 'select' || $type === 'enum') {
                    $enumOptions = array_keys(json_decode($item['options'], true));
                    $enumOptionsStr = implode(",", array_map(function ($string) {
                        return '"' . $string . '"';
                    }, $enumOptions));
                    $column = "\$table->" . $type . "('" . $item['name'] . "', [" . $enumOptionsStr . "])";
                } elseif ($type === 'bigInteger' && $modifier === '->unsigned()') {
                    // the framework's own shorthand for the same column
                    $column = "\$table->unsignedBigInteger('" . $item['name'] . "')";
                    $modifier = '';
                } else {
                    $column = "\$table->" . $type . "('" . $item['name'] . "')";
                }
            } else {
                $column = "\$table->string('" . $item['name'] . "')";
            }

            $statements[] = $column . $modifier . ';';
        }

        // add indexes and unique indexes as necessary
        foreach ($fieldsToIndex as $fldData) {
            $line = trim($fldData);

            // is a unique index specified after the #?
            // if no hash present, we append one to make life easier
            if (strpos($line, '#') === false) {
                $line .= '#';
            }

            // parts[0] = field name (or names if pipe separated)
            // parts[1] = unique specified
            $parts = explode('#', $line);
            if (strpos($parts[0], '|') !== 0) {
                $fieldNames = "['" . implode("', '", explode('|', $parts[0])) . "']"; // wrap single quotes around each element
            } else {
                $fieldNames = trim($parts[0]);
            }

            if (count($parts) > 1 && $parts[1] == 'unique') {
                $statements[] = "\$table->unique(" . trim($fieldNames) . ");";
            } else {
                $statements[] = "\$table->index(" . trim($fieldNames) . ");";
            }
        }

        // timestamps come after the fields and indexes, before the foreign keys
        $statements[] = "\$table->timestamps();";

        // foreign keys
        $continuation = str_repeat(' ', 18); // the lines of one chained call, under the `$table->foreign(` at 12
        foreach ($foreignKeys as $fk) {
            $line = trim($fk);

            $parts = explode('#', $line);

            // if we don't have three parts, then the foreign key isn't defined properly
            // --foreign-keys="foreign_entity_id#id#foreign_entity#onDelete#onUpdate"
            if (count($parts) == 3) {
                // no action given: refuse a delete that would orphan a row
                $statements[] = $this->foreignCall($tableName, trim($parts[0]))
                    . "\n" . $continuation . "->references('" . trim($parts[1]) . "')"
                    . "\n" . $continuation . "->on('" . trim($parts[2]) . "')"
                    . "\n" . $continuation . "->restrictOnDelete();";
            } elseif (count($parts) == 4) {
                $statements[] = $this->foreignCall($tableName, trim($parts[0]))
                    . "->references('" . trim($parts[1]) . "')->on('" . trim($parts[2]) . "')"
                    . "->onDelete('" . trim($parts[3]) . "')" . "->onUpdate('" . trim($parts[3]) . "');";
            } elseif (count($parts) == 5) {
                $statements[] = $this->foreignCall($tableName, trim($parts[0]))
                    . "->references('" . trim($parts[1]) . "')->on('" . trim($parts[2]) . "')"
                    . "->onDelete('" . trim($parts[3]) . "')" . "->onUpdate('" . trim($parts[4]) . "');";
            }
        }

        $schemaUp = "Schema::create('" . $tableName . "', function (Blueprint \$table) {\n"
            . implode("\n", array_map(function ($statement) use ($tabIndent) {
                return $tabIndent . $tabIndent . $tabIndent . $statement;
            }, $statements))
            . "\n" . $tabIndent . $tabIndent . "});";

        $schemaDown = "Schema::drop('" . $tableName . "');";

        return $this->replaceSchemaUp($stub, $schemaUp)
            ->replaceSchemaDown($stub, $schemaDown)
            ->replaceClass($stub, $className);
    }

    /**
     * `$table->foreign('column')`, with an explicit constraint name when Laravel's default `{table}_{column}_foreign` is
     * longer than MySQL's 64 character identifier limit (it would fail after the table was already created, error 1059).
     * The name is the first 55 characters, `_` and 8 characters of the md5 of the full default name.
     *
     * @param  string  $table
     * @param  string  $column
     *
     * @return string
     */
    protected function foreignCall($table, $column)
    {
        $default = $table . '_' . $column . '_foreign';

        if (strlen($default) <= 64) {
            return "\$table->foreign('" . $column . "')";
        }

        return "\$table->foreign('" . $column . "', '" . substr($default, 0, 55) . '_' . substr(md5($default), 0, 8) . "')";
    }

    /**
     * Replace the schema_up for the given stub.
     *
     * @param  string  $stub
     * @param  string  $schemaUp
     *
     * @return $this
     */
    protected function replaceSchemaUp(&$stub, $schemaUp)
    {
        $stub = str_replace('{{schema_up}}', $schemaUp, $stub);

        return $this;
    }

    /**
     * Replace the schema_down for the given stub.
     *
     * @param  string  $stub
     * @param  string  $schemaDown
     *
     * @return $this
     */
    protected function replaceSchemaDown(&$stub, $schemaDown)
    {
        $stub = str_replace('{{schema_down}}', $schemaDown, $stub);

        return $this;
    }
}
