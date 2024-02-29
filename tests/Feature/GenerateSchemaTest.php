<?php

namespace Brausepulver\EloquentToGraphQL\Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

final class GenerateSchemaTest extends TestCase
{
    /**
     * Relationships to exclude for all tests not looking at nullable relationships.
     * 
     * @var array<string>
     */
    private static $nullableRelationships = [
        'secondDummyNullable',
        'thirdDummiesNullable',
        'fourthDummyNullableFirst',
        'fourthDummyNullableSecond',
        'fourthDummyNullableThird',
        'fifthDummiesNullableFirst',
        'fifthDummiesNullableSecond',
        'fifthDummiesNullableThird',
        'sixthDummyNullable',
        'seventhDummiesNullableFirst',
        'seventhDummiesNullableSecond',
        'seventhDummiesNullableThird',
        'tenthDummiesNullable'
    ];

    private static $nonNullableRelationships = [
        'secondDummy',
        'thirdDummies',
        'fourthDummy',
        'fifthDummies',
        'sixthDummy',
        'seventhDummies',
        'eighthDummy',
        'ninthDummies',
        'tenthDummies',
        'externalDummy',
        'dummyable',
        'alsoDummyable',
        'firstDummies'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        App::shouldReceive('basePath')
            ->once()
            ->with('composer.json')
            ->andReturn(__DIR__.'/../composer.json');

        App::shouldReceive('basePath')
            ->once()
            ->with('/vendor/composer/autoload_classmap.php')
            ->andReturn(__DIR__.'/../vendor/composer/autoload_classmap.php');

        App::shouldReceive('basePath')
            ->with('graphql/generated.graphql')
            ->andReturn('');

        File::shouldReceive('exists')
            ->once()
            ->with('')
            ->andReturn(false);
    }

    public function testNoNullableRelations()
    {
        $parts[] = <<<EOF
type FirstDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    dummy_column_1: String!,
    dummy_column_2: String,
    dummy_column_3: Int!,
    secondDummy: SecondDummy! @hasOne,
    thirdDummies: [ThirdDummy!]! @hasMany,
    fourthDummy: FourthDummy! @hasOne,
    fifthDummies: [FifthDummy!]! @hasMany,
    sixthDummy: SixthDummy! @belongsTo,
    seventhDummies: [SeventhDummy!]! @belongsToMany,
    eighthDummy: EighthDummy! @morphOne,
    ninthDummies: [NinthDummy!]! @morphMany,
    tenthDummies: [TenthDummy!]! @morphMany
}


EOF;

        $parts[] = <<<EOF
type EighthDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    dummyable: Dummyable! @morphTo
}


EOF;

        $parts[] = <<<EOF
type NinthDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    alsoDummyable: AlsoDummyable! @morphTo
}


EOF;

        $parts[] = <<<EOF
type TenthDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    firstDummies: [FirstDummy!]! @morphMany
}

EOF;

        $parts[] = <<<EOF

union Dummyable = FirstDummy
union AlsoDummyable = FirstDummy

EOF;

        foreach ($parts as $part) {
            File::shouldReceive('append')
                ->once()
                ->with('', $part);
        }

        $this->artisan('e2gql', [
            'model' => ['FirstDummy', 'EighthDummy', 'NinthDummy', 'TenthDummy'],
            '--exclude-relationships' => 'externalDummy' . ',' . implode(',', self::$nullableRelationships),
            '--exclude-foreign-keys' => true
        ])->assertSuccessful();
    }

    public function testIncludeAbsoluteNamespaceModel()
    {
        $parts[] = <<<EOF
type FirstDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    dummy_column_1: String!,
    dummy_column_2: String,
    dummy_column_3: Int!,
    secondDummy: SecondDummy! @hasOne,
    thirdDummies: [ThirdDummy!]! @hasMany,
    fourthDummy: FourthDummy! @hasOne,
    fifthDummies: [FifthDummy!]! @hasMany,
    sixthDummy: SixthDummy! @belongsTo,
    seventhDummies: [SeventhDummy!]! @belongsToMany,
    eighthDummy: EighthDummy! @morphOne,
    ninthDummies: [NinthDummy!]! @morphMany,
    tenthDummies: [TenthDummy!]! @morphMany,
    externalDummy: ExternalDummy! @belongsTo
}


EOF;

        $parts[] = <<<EOF
type ExternalDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    column: String!
}

EOF;

        foreach ($parts as $part) {
            File::shouldReceive('append')
            ->once()
                ->with('', $part);
        }

        $this->artisan('e2gql', [
            'model' => ['FirstDummy'],
            '--include-models' => 'MyPackage\\Models\\ExternalDummy',
            '--exclude-relationships' => implode(',', self::$nullableRelationships),
            '--exclude-foreign-keys' => true
        ])->assertSuccessful();
    }

    public function testIncludeRelativeNamespaceModel()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->artisan('e2gql', [
            'model' => ['FirstDummy'],
            '--include-models' => 'ExternalDummy',
            '--exclude-relationships' => implode(',', self::$nullableRelationships),
            '--exclude-foreign-keys' => true
        ])->assertFailed();
    }

    public function testExcludeModel()
    {
        $part = <<<EOF
type FirstDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    dummy_column_1: String!,
    dummy_column_2: String,
    dummy_column_3: Int!,
    secondDummy: SecondDummy! @hasOne,
    thirdDummies: [ThirdDummy!]! @hasMany,
    fourthDummy: FourthDummy! @hasOne,
    fifthDummies: [FifthDummy!]! @hasMany,
    sixthDummy: SixthDummy! @belongsTo,
    seventhDummies: [SeventhDummy!]! @belongsToMany,
    eighthDummy: EighthDummy! @morphOne,
    ninthDummies: [NinthDummy!]! @morphMany,
    tenthDummies: [TenthDummy!]! @morphMany,
    externalDummy: ExternalDummy! @belongsTo
}

EOF;

        File::shouldReceive('append')
            ->once()
            ->with('', $part);

        $this->artisan('e2gql', [
            'model' => ['FirstDummy', 'SecondDummy'],
            '--exclude-models' => 'SecondDummy',
            '--exclude-relationships' => implode(',', self::$nullableRelationships),
            '--exclude-foreign-keys' => true
        ]);
    }

    public function testIncludeExcludeModels()
    {
        $parts[] = <<<EOF
type FirstDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    dummy_column_1: String!,
    dummy_column_2: String,
    dummy_column_3: Int!,
    secondDummy: SecondDummy! @hasOne,
    thirdDummies: [ThirdDummy!]! @hasMany,
    fourthDummy: FourthDummy! @hasOne,
    fifthDummies: [FifthDummy!]! @hasMany,
    sixthDummy: SixthDummy! @belongsTo,
    seventhDummies: [SeventhDummy!]! @belongsToMany,
    eighthDummy: EighthDummy! @morphOne,
    ninthDummies: [NinthDummy!]! @morphMany,
    tenthDummies: [TenthDummy!]! @morphMany,
    externalDummy: ExternalDummy! @belongsTo
}


EOF;

        $parts[] = <<<EOF
type ExternalDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    column: String!
}

EOF;

        foreach ($parts as $part) {
            File::shouldReceive('append')
            ->once()
                ->with('', $part);
        }

        $this->artisan('e2gql', [
            'model' => ['FirstDummy'],
            '--include-models' => '\\MyPackage\\Models\\ExternalDummy',
            '--exclude-models' => '\\MyPackage\\Models\\ExternalDummy',
            '--exclude-relationships' => implode(',', self::$nullableRelationships),
            '--exclude-foreign-keys' => true
        ]);
    }

    public function testWithNullableRelations()
    {
        $part = <<<EOF
type FirstDummy {
    id: ID!,
    created_at: DateTime,
    updated_at: DateTime,
    dummy_column_1: String!,
    dummy_column_2: String,
    dummy_column_3: Int!,
    secondDummy: SecondDummy! @hasOne,
    secondDummyNullable: SecondDummy @hasOne,
    thirdDummies: [ThirdDummy!]! @hasMany,
    thirdDummiesNullable: [ThirdDummy!] @hasMany,
    fourthDummy: FourthDummy! @hasOne,
    fourthDummyNullableFirst: FourthDummy @hasOne,
    fourthDummyNullableSecond: FourthDummy @hasOne,
    fourthDummyNullableThird: FourthDummy @hasOne,
    fifthDummies: [FifthDummy!]! @hasMany,
    fifthDummiesNullableFirst: [FifthDummy!] @hasMany,
    fifthDummiesNullableSecond: [FifthDummy!] @hasMany,
    fifthDummiesNullableThird: [FifthDummy!] @hasMany,
    sixthDummy: SixthDummy! @belongsTo,
    sixthDummyNullable: SixthDummy @belongsTo,
    seventhDummies: [SeventhDummy!]! @belongsToMany,
    seventhDummiesNullableFirst: [SeventhDummy!] @belongsToMany,
    seventhDummiesNullableSecond: [SeventhDummy!] @belongsToMany,
    seventhDummiesNullableThird: [SeventhDummy!] @belongsToMany,
    eighthDummy: EighthDummy! @morphOne,
    ninthDummies: [NinthDummy!]! @morphMany,
    tenthDummies: [TenthDummy!]! @morphMany,
    tenthDummiesNullable: [TenthDummy!] @morphMany
}

EOF;

        File::shouldReceive('append')
            ->once()
            ->with('', $part);

        $this->artisan('e2gql', [
            'model' => ['FirstDummy'],
            '--exclude-relationships' => 'externalDummy',
            '--exclude-foreign-keys' => true
        ]);
    }

    public function testExcludeColumn()
    {
        $part = <<<EOF
type FirstDummy {
    dummy_column_1: String!,
    dummy_column_2: String,
    dummy_column_3: Int!
}

EOF;

        File::shouldReceive('append')
            ->once()
            ->with('', $part);

        $this->artisan('e2gql', [
            'model' => ['FirstDummy'],
            '--exclude-columns' => implode(',', ['id', 'created_at', 'updated_at']),
            '--exclude-relationships' => implode(',', array_merge(self::$nullableRelationships, self::$nonNullableRelationships)),
            '--exclude-foreign-keys' => true
        ]);
    }

    public function testAllModels()
    {
        $parts[] = <<<EOF
type EighthDummy {
}


EOF;
        $parts[] = <<<EOF
type FifthDummy {
}


EOF;
        $parts[] = <<<EOF
type FirstDummy {
}


EOF;
        $parts[] = <<<EOF
type FourthDummy {
}


EOF;
        $parts[] = <<<EOF
type NinthDummy {
}


EOF;
        $parts[] = <<<EOF
type SecondDummy {
}


EOF;
        $parts[] = <<<EOF
type SeventhDummy {
}


EOF;
        $parts[] = <<<EOF
type SixthDummy {
}


EOF;
        $parts[] = <<<EOF
type TenthDummy {
}

EOF;
        $parts[] = <<<EOF
type ThirdDummy {
}


EOF;

        foreach ($parts as $part) {
            File::shouldReceive('append')
            ->once()
                ->with('', $part);
        }

        $this->artisan('e2gql', [
            '--exclude-columns' => implode(',', ['id', 'created_at', 'updated_at', 'dummy_column_1', 'dummy_column_2', 'dummy_column_3']),
            '--exclude-relationships' => implode(',', array_merge(self::$nullableRelationships, self::$nonNullableRelationships)),
            '--exclude-foreign-keys' => true
        ]);
    }

    public function testIgnoreEmptyObjectTypes()
    {
        File::shouldNotReceive('append');

        $this->artisan('e2gql', [
            '--exclude-columns' => implode(',', ['id', 'created_at', 'updated_at', 'dummy_column_1', 'dummy_column_2', 'dummy_column_3']),
            '--exclude-relationships' => implode(',', array_merge(self::$nullableRelationships, self::$nonNullableRelationships)),
            '--ignore-empty' => true,
            '--exclude-foreign-keys' => true
        ]);
    }
}
