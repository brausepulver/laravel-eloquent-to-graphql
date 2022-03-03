<?php

namespace Brausepulver\EloquentToGraphQL;

class GraphQLField
{
    /**
     * Name of the field, e.g. `id`
     * 
     * @var string
     */
    public $name;

    /**
     * Type of the field, e.g. `ID`
     * 
     * @var string
     */
    public $type;

    /**
     * Whether the field has the not-nullable modifier `!`
     * 
     * @var boolean
     */
    public $notNullable;

    /**
     * Currently, this holds at most one directive, namely specifying the rela-
     * tionship type for Lighthouse, e.g. `hasMany`.
     * 
     * Could be expanded to hold more directives in the future.
     * 
     * @var array
     */
    public $directives;

    /**
     * Whether the field has the array modifier `[...]`
     * 
     * @var boolean
     */
    public $isArray;

    /**
     * Whether the array has the not-nullable modifier
     * 
     * @var boolean
     */
    public $arrayNotNullable;

    public function __construct(
        string $name,
        string $type,
        bool $notNullable,
        array $directives,
        bool $isArray = false,
        ?bool $arrayNotNullable = true
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->notNullable = $notNullable;
        $this->directives = $directives;
        $this->isArray = $isArray;
        $this->arrayNotNullable = $arrayNotNullable;
    }

    public function __toString()
    {
        $schema = $this->name . ': ';

        $typePart = $this->type . (($this->isArray ? $this->arrayNotNullable : $this->notNullable) ? '!' : '');
        if ($this->isArray) {
            $typePart = "[$typePart]" . ($this->notNullable ? '!' : '');
        }
        $schema .= $typePart;

        foreach ($this->directives as $directive) {
            $schema .= " @$directive";
        }

        return $schema;
    }
}
