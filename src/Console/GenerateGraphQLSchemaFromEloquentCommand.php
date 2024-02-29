<?php

namespace Brausepulver\EloquentToGraphQL\Console;

use Doctrine\DBAL\Schema\{Column, ForeignKeyConstraint, AbstractSchemaManager};
use Doctrine\DBAL\Types\{
    SmallIntType,
    IntegerType,
    BigIntType,
    DecimalType,
    FloatType,
    StringType,
    AsciiStringType,
    TextType,
    GuidType,
    BinaryType,
    BlobType,
    BooleanType,
    DateType,
    DateTimeType,
    DateTimeTzType,
    TimeType,
    JsonType,
    ArrayType,
    SimpleArrayType,
    ObjectType,
    Type
};
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    HasOne,
    BelongsTo,
    HasMany,
    HasOneThrough,
    HasManyThrough,
    BelongsToMany,
    HasOneOrMany,
    MorphOne,
    MorphMany,
    MorphOneOrMany,
    MorphTo,
    MorphToMany,
    Relation
};
use Illuminate\Support\Facades\{App, Config, DB, File};
use Brausepulver\EloquentToGraphQL\GraphQLField;

class GenerateGraphQLSchemaFromEloquentCommand extends Command
{
    /**
     * Mapping of DBAL types to GraphQL types. Additional type mappings may be
     * specified in the configuration file.
     * 
     * @var array<string,string>
     */
    private static $graphQLTypeMap = [
        SmallIntType::class => "Int",
        IntegerType::class => "Int",
        BigIntType::class => "String",
        DecimalType::class => "String",
        FloatType::class => "Float",
        StringType::class => "String",
        AsciiStringType::class => "String",
        TextType::class => "String",
        GuidType::class => "ID",
        BinaryType::class => null,
        BlobType::class => null,
        BooleanType::class => "Boolean",
        DateType::class => "DateTime", // Lighthouse defines a custom DateTime scalar.
        DateTimeType::class => "DateTime",
        DateTimeTzType::class => "DateTime",
        TimeType::class => "DateTime",
        JsonType::class => "String",
        ArrayType::class => "String",
        SimpleArrayType::class => "String",
        ObjectType::class => null,
    ];

    /**
     * Mapping of Eloquent relationship types to GraphQL directives.
     * 
     * @var array<string,string>
     */
    private static $graphQLRelationshipDirectiveMap = [
        HasOne::class => 'hasOne',
        HasMany::class => 'hasMany',
        HasOneThrough::class => 'hasOne',
        HasManyThrough::class => 'hasMany',
        BelongsTo::class => 'belongsTo',
        BelongsToMany::class => 'belongsToMany',
        MorphOne::class => 'morphOne',
        MorphMany::class => 'morphMany',
        MorphTo::class => 'morphTo',
        MorphToMany::class => 'morphMany'
    ];

    /**
     * {@inheritdoc}
     */
    protected $signature = '
        e2gql
        {model?* : Eloquent models to use, e.g. User}
        {--f|force : Overwrite without prompt}
        {--d|directory : Directory instead of single file}
        {--indentation=4 : spaces}
        {--exclude-columns= : Column names, e.g. id,created_at}
        {--exclude-relationships= : Method names, e.g. user,audits}
        {--include-models= : Namespaced model names, e.g. MyPackage\\\\Models\\\\Model}
        {--exclude-models= : e.g. Audit}
        {--ignore-empty : Do not write empty object types to schema}
        {--exclude-foreign : Do not include foreign key _id fields in schema}
        {--exclude-polymorphic : Do not include polymorphic relationship key fields in schema}
    ';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Generate GraphQL schema(s) from Eloquent models and their tables';

    /**
     * Depending on the execution mode, if either target directory or file
     * exists, prompt the user whether to overwrite it, if desired.
     * 
     * @param bool $directory Whether in directory mode
     * @param bool $prompt    Whether user wants to be prompted
     * 
     * @return bool Whether directory or file was successfully created/emptied
     */
    private function tryCreateDirectoryOrFile(bool $directory, bool $prompt): bool
    {
        if ($directory) {
            $path = base_path('graphql/generated');

            if (File::exists($path)) {
                if ($prompt &&
                   !$this->confirm('Directory `/graphql/generated` already exists. Overwrite relevant files?')
                ) {
                    return false;
                } else {
                    File::deleteDirectory($path);
                }
            }
            if (!File::makeDirectory($path)) {
                $this->error('Could not create `/graphql/generated` directory');
                return false;
            }
        } else if (File::exists(App::basePath('graphql/generated.graphql'))) {
            if (!$prompt || $this->confirm('File `/graphql/generated.graphql` already exists. Overwrite?')) {
                File::replace(base_path('graphql/generated.graphql'), ''); // Empty the file
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all of the classes in the \App namespace extending the 
     * \Illuminate\Database\Eloquent\Model base class.
     * 
     * @return array List of absolute model namespace names
     */
    private static function getModels(): array
    {
        $rootNamespace = key(json_decode(file_get_contents(App::basePath('composer.json')), true)['autoload']['psr-4']);
        $classes = include App::basePath('/vendor/composer/autoload_classmap.php');

        return array_filter(array_keys($classes), fn ($class) => (
            preg_match("/^$rootNamespace\\/", $class) && is_subclass_of($class, Model::class)
        ));
    }

    /**
     * For each of the models, get its relationship methods.
     * 
     * A relationship method is any method that is type-hinted with an Eloquent
     * relation class.
     * 
     * @param array<string> $models List of absolute model namespace names
     * @return array<string,array<string,Relation>> List of maps from method names to relation objects for each model
     */
    private static function getModelRelations(array $models): array
    {
        $modelRelations = [];

        foreach ($models as $model) {
            try {
                $reflector = new \ReflectionClass($model);
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException("Please make sure class \"$model\" exists and is fully qualified", previous: $e);
            }

            $methods = array_filter(
                $reflector->getMethods(\ReflectionMethod::IS_PUBLIC),
                fn ($method) =>
                    $method->hasReturnType() &&
                    preg_match(
                        "/^Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\/",
                        $method->getReturnType()
                    )
            );

            $methods = array_reduce(
                $methods,
                fn ($t, $method) => $t + [$method->getName() => $method->invoke($reflector->newInstance())],
                []
            );
            $modelRelations[$model] = $methods;
        }
        return $modelRelations;
    }

    /**
     * Get a map of models to their table names.
     * 
     * @param array<string> $models List of absolute model namespace names
     * @return array<string,string> Map of absolute model namespace names to table names
     */
    private static function getModelTables(array $models): array
    {
        return array_reduce($models, fn ($t, $model) => $t + [$model => (new $model)->getTable()], []);
    }

    /**
     * Create a schema for a single model.
     * 
     * @param string $modelName Model class name, not namespace name; e.g. User, not App\Models\User
     * @param array<GraphQLField> $fields
     * 
     * @return string
     */
    private static function createModelSchema(string $modelName, array $fields, int $indentation): string
    {
        $modelSchema = "type $modelName {\n";

        foreach ($fields as $field) {
            $modelSchema .= str_repeat(' ', $indentation) . $field . (end($fields) === $field ? '' : ',') . "\n";
        }

        $modelSchema .= "}\n";
        return $modelSchema;
    }

    /**
     * Get the class name of a model.
     * 
     * @param  string $model Absolute model namespace name; e.g. App\Models\User
     * @return string|null   e.g. User
     */
    private static function getModelClassName(string $model): ?string
    {
        $matches = [];
        preg_match("/\\\\(\w+)$/", $model, $matches);
        return $matches[1] ?? null; // Do not issue warning if no match
    }

    /**
     * Get the GraphQL type for a database column type.
     * 
     * @param Type $type
     * @return string|null
     */
    private static function getGraphQLTypeForColumnType(Type $type): ?string
    {
        // https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#mapping-matrix
        return self::$graphQLTypeMap[get_class($type)];
    }

    /**
     * Get the foreign key constraint referencing a column.
     * 
     * @param array<ForeignKeyConstraint> $foreignKeys All foreign key constraints on the table
     * @param string                      $columnName
     * 
     * @return ForeignKeyConstraint|null
     */
    private static function getForeignKey(array $foreignKeys, string $columnName): ?ForeignKeyConstraint
    {
        foreach ($foreignKeys as $foreignKey) {
            if (in_array($columnName, $foreignKey->getLocalColumns())) {
                return $foreignKey;
            }
        }
        return null;
    }

    /**
     * Get the GraphQL field corresponding to a single table column.
     * 
     * Columns that are either part of foreign key constraints on the column's
     * table or that represent polymorphic relationships will be ignored.
     * 
     * @param Column                      $column      Column to get the GraphQL field for
     * @param array<ForeignKeyConstraint> $foreignKeys Foreign key constraints on the column's table
     * @param array<string>               $morphTos    Names of polymorphic relationships on the table
     * @param string                      $table       Name of the table the column belongs to
     * 
     * @return GraphQLField|null
     */
    private function getFieldForColumn(Column $column, array $foreignKeys, array $morphTos, bool $excludeForeign, string $table, bool $excludePolymorphic): ?GraphQLField
    {
        $columnName = $column->getName();
        $fieldNotNullable = $column->getNotNull();

        // Determine if column is primary key, foreign key, or polymorphic key
        $isPrimaryKey = "id" === $columnName;
        $isForeignKey = null !== self::getForeignKey($foreignKeys, $columnName);

        $matches = [];
        $isPolymorphicIdKey = preg_match("/^(\w+)_id$/", $columnName, $matches) && in_array($matches[1], $morphTos);
        $isPolymorphicTypeKey = preg_match("/^(\w+)_type$/", $columnName, $matches) && in_array($matches[1], $morphTos);
        $isPolymorphicKey = $isPolymorphicIdKey || $isPolymorphicTypeKey;

        // Exclude foreign keys and polymorphic keys if desired
        if (($excludeForeign && $isForeignKey) || ($excludePolymorphic && $isPolymorphicKey)) {
            return null;
        }

        // Determine GraphQL type
        if ($isPrimaryKey || $isForeignKey || $isPolymorphicIdKey) {
            $type = "ID";
        } else {
            $type = self::getGraphQLTypeForColumnType($column->getType());
        }

        if (null === $type) {
            $message = "No matching GraphQL type found for column type " . get_class($column->getType()) .
                " in column " . $column->getName() .
                " of table " . $table;
            $message .= "\nPlease register a custom type mapping in the configuration file.";
            throw new \InvalidArgumentException($message);
        }

        return new GraphQLField(
            $columnName,
            $type,
            $fieldNotNullable,
            [], // Scalar-type fields do not have directives currently.
            false,
            null
        );
    }

    /**
     * Determine if there is a nullable column with a certain name.
     * 
     * @param array<Column> $columns    All possible columns
     * @param string        $columnName Name of column to test
     * 
     * @return bool Whether column with that name is nullable
     */
    private static function hasNullable(array $columns, string $columnName): bool
    {
        foreach ($columns as $column) {
            if ($columnName === $column->getName() && !$column->getNotNull()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the GraphQL field corresponding to one relationship on the model.
     * 
     * @param string                      $methodName        Name of the relationship method
     * @param Relation                    $relation          Relation object corresponding to relationship
     * @param array<string>               $excludedRelations Relationships excluded by user
     * @param AbstractSchemaManager       $schema
     * @param array<string,array<string>> $unionTypes        List of maps from union types to all models belonging to type
     * @param array<string>               $morphTos          Names of polymorphic relationships
     * @param array<string>               $models            List of absolute model namespace names
     * 
     * @return GraphQLField|null
     */
    private static function getFieldForRelation(
        string $methodName,
        Relation $relation,
        array $excludedRelations,
        AbstractSchemaManager $schema,
        array &$unionTypes,
        array &$morphTos,
        array $models
    ): ?GraphQLField
    {
        // Check if relationship can be nullable
        $nullable = false;

        if ($relation instanceof HasOneOrMany) { // HasOne or HasMany or MorphOne or MorphMany
            [$table, $key] = explode('.', $relation->getQualifiedForeignKeyName());
            $nullable = self::hasNullable($schema->listTableColumns($table), $key);
        } else if ($relation instanceof HasManyThrough) { // HasManyThrough or HasOneThrough
            $farForeign = $relation->getQualifiedFarKeyName();
            $throughForeign = $relation->getQualifiedFirstKeyName();
            $parentLocal = $relation->getQualifiedLocalKeyName();
            $throughLocal = $relation->getQualifiedParentKeyName();

            foreach ([$farForeign, $throughForeign, $parentLocal, $throughLocal] as $qualifiedKey) {
                [$table, $key] = explode('.', $qualifiedKey);
                if ($nullable = self::hasNullable($schema->listTableColumns($table), $key)) {
                    break;
                }
            }
        } else if ($relation instanceof BelongsTo) { // BelongsTo or MorphTo
            [$table, $key] = explode('.', $relation->getQualifiedForeignKeyName());
            $nullable = self::hasNullable($schema->listTableColumns($table), $key);
        } else if ($relation instanceof BelongsToMany) { // BelongsToMany or MorphToMany
            $pivotForeign = $relation->getQualifiedForeignPivotKeyName();
            $pivotRelated = $relation->getQualifiedRelatedPivotKeyName();

            foreach ([$pivotForeign, $pivotRelated] as $qualifiedKey) {
                [$table, $key] = explode('.', $qualifiedKey);
                if ($nullable = self::hasNullable($schema->listTableColumns($table), $key)) {
                    break;
                }
            }
        }

        // Get related model
        $relatedModel = get_class($relation->getRelated());
        $type = self::getModelClassName($relatedModel);

        // Store polymorphic relationships for later union-type declaration
        if ($relation instanceof MorphOneOrMany || $relation instanceof MorphTo) {
            $matches = [];
            preg_match("/^(\w+)_type$/", $relation->getMorphType(), $matches);
            $morphTos[] = $matches[1];
            $morphType = str_replace(' ', '', ucwords(str_replace('_', ' ', $matches[1])));

            if ($relation instanceof MorphTo) {
                $type = $morphType;
            }
        }

        // Check if relationship was excluded by user
        if (in_array($methodName, $excludedRelations)) {
            return null;
        }

        if ($relation instanceof MorphOneOrMany && in_array(get_class($relation->getRelated()), $models)) {
            $unionTypes[$morphType][] = self::getModelClassName($relation->getMorphClass()); // Add to union type only if relationship was not excluded
        }

        $directive = self::$graphQLRelationshipDirectiveMap[get_class($relation)];

        // Check if GraphQL field is of array type
        $xToNRelationships = [
            HasMany::class,
            HasManyThrough::class,
            BelongsToMany::class,
            MorphMany::class,
            MorphToMany::class
        ];
        $isArray = in_array(get_class($relation), $xToNRelationships);

        return new GraphQLField(
            $methodName,
            $type,
            !$nullable,
            [$directive],
            $isArray,
            true
        );
    }

    /**
     * Get the schema part for union types.
     * 
     * @param array<string,array<string>> $unionTypes List of maps from union types to all models belonging to type
     * @return string
     */
    private static function makeUnionTypes(array $unionTypes): string
    {
        $r = '';
        foreach (array_keys($unionTypes) as $unionType) {
            $relatedTypes = $unionTypes[$unionType];
            $r .= "union $unionType = " . implode(" | ", $relatedTypes) . "\n";
        }
        return $r;
    }

    /**
     * Try to save a schema part.
     * 
     * @param string      $schemaPart
     * @param bool        $inDirectory Whether to save in directory or file
     * @param string|null $file        If saving in directory, name of the file under directory to save in
     * 
     * @return bool Whether saving was successful
     */
    private function saveSchemaPart(string $schemaPart, bool $inDirectory, ?string $file = null): bool
    {
        $path = 'graphql/' . ($inDirectory ? "generated/$file" : 'generated') . '.graphql';

        if ("" !== $schemaPart && !File::append(App::basePath($path), $schemaPart)) {
            $this->error("Could not save to \"$path\"");
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        // Handle arguments and options
        $argumentModels = $this->argument('model');
        $force = $this->option('force');
        $inDirectory = $this->option('directory');
        $indentation = $this->option('indentation')
            ?? Config::get('eloquent_to_graphql.default_indentation');
        $excludedColumns = array_merge(
            Config::get('eloquent_to_graphql.default_excluded_columns'),
            explode(',', $this->option('exclude-columns'))
        );
        $excludedRelations = array_merge(
            Config::get('eloquent_to_graphql.default_excluded_relationships'),
            explode(',', $this->option('exclude-relationships'))
        );
        $includeModels = array_filter(
            explode(',', $this->option('include-models')),
            fn ($v) => "" !== $v
        );
        $excludeModels = array_filter(
            explode(',',$this->option('exclude-models')),
            fn ($v) => "" !== $v
        );
        $ignoreEmpty = $this->option('ignore-empty');
        $excludeForeign = $this->option('exclude-foreign');
        $excludePolymorphic = $this->option('exclude-polymorphic');

        // Create file/directory
        if (!$this->tryCreateDirectoryOrFile($inDirectory, !$force)) {
            $this->warn('Ok, exiting');
            return; // Stop execution
        }

        // Prepare data relating to models
        $models = self::getModels();

        if (!empty($excludeModels)) {
            $models = array_filter(
                $models,
                fn ($model) =>
                    !(in_array($model, $excludeModels) || in_array(self::getModelClassName($model), $excludeModels))
            );
        }

        $models = array_merge($models, $includeModels);
        $modelRelations = self::getModelRelations($models);
        $modelTables = self::getModelTables($models);

        if (!empty($argumentModels)) {
            $models = array_filter(
                $models,
                fn ($model) =>
                    in_array($model, $argumentModels) ||
                    in_array(self::getModelClassName($model), $argumentModels)
            );
            $models = array_merge($models, $includeModels);
        }

        $unionTypes = [];
        $schema = DB::getDoctrineSchemaManager();

        // Map custom types
        foreach (Config::get('eloquent_to_graphql.custom_type_mappings', []) as $fromType => $toType) {
            $schema->getDatabasePlatform()->registerDoctrineTypeMapping($fromType, $toType);
        }

        foreach ($models as $model) {
            $modelInstance = new $model;
            $connectionName = $modelInstance->getConnectionName();
            $schema = DB::connection($connectionName)->getDoctrineSchemaManager();

            $table = $modelTables[$model];
            $foreignKeys = $schema->listTableForeignKeys($table);
            $columns = $schema->listTableColumns($table);
            $morphTos = [];

            // Fields from Eloquent relationships
            $firstFields = [];
            foreach ($modelRelations[$model] as $methodName => $relation) {
                $field = self::getFieldForRelation($methodName, $relation, $excludedRelations, $schema, $unionTypes, $morphTos, $models);
                if (isset($field)) {
                    $firstFields[] = $field;
                }
            }

            // Fields from table columns
            $secondFields = [];
            foreach ($columns as $column) {
                if (in_array($column->getName(), $excludedColumns)) {
                    continue;
                }

                $field = $this->getFieldForColumn($column, $foreignKeys, $morphTos, $excludeForeign, $table, $excludePolymorphic);
                if (isset($field)) {
                    $secondFields[] = $field;
                }
            }

            // Save schemas
            $fields = array_merge($secondFields, $firstFields);
            if ($ignoreEmpty && empty($fields)) {
                continue;
            }

            $modelClassName = self::getModelClassName($model);
            $schemaPart = self::createModelSchema($modelClassName, $fields, $indentation);

            if (!$inDirectory && $model !== end($models)) {
                $schemaPart .= "\n";
            }
            $this->saveSchemaPart($schemaPart, $inDirectory, $modelClassName);
        }

        // Save union types
        $unionTypePart = self::makeUnionTypes($unionTypes);
        if ("" !== $unionTypePart) {
            $this->saveSchemaPart("\n" . $unionTypePart, $inDirectory);
        }
    }
}
