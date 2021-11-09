<?php


namespace ProtoParser\Swagger;

/**
 * 单个输入参数描述
 * @package ProtoParser\Swagger\
 */
class Parameter
{
    protected $name;

    /**
     * @var string path formData
     */
    protected $in = 'formData';
    protected $description;
    protected $required = false;

    // "type": "integer",
    protected $type;

    // "format": "int64"
    protected $format;

    /**
     * "schema": {
     *     "$ref": "#/definitions/Pet"
     * }
     * @var array
     */
    protected $schema;
    protected $items;
    protected $default;

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param  mixed  $default
     */
    public function setDefault($default): void
    {
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  mixed  $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getIn(): string
    {
        return $this->in;
    }

    /**
     * @param  string  $in
     */
    public function setIn(string $in): void
    {
        $this->in = $in;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param  mixed  $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param  bool  $required
     */
    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  mixed  $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param  mixed  $format
     */
    public function setFormat($format): void
    {
        $this->format = $format;
    }

    /**
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @param  array  $schema
     */
    public function setSchema(array $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param  mixed  $items
     */
    public function setItems($items): void
    {
        $this->items = $items;
    }

    public function toArray(): array
    {
        $got = [
            "name"        => $this->name,
            "in"          => $this->in,
            "description" => $this->description,
            "required"    => $this->required,
            "type"        => $this->type,
            "format"      => $this->format,
            "schema"      => $this->schema,
            "items"       => $this->items,
            "default"     => $this->default,
        ];
        foreach ($got as $i => $value) {
            if ($value === null) {
                unset($got[$i]);
            }
        }
        return $got;
    }
}