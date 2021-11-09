<?php


namespace ProtoParser\Swagger;

/**
 * 定义对象
 * @package ProtoParser\Swagger
 */
class Definition
{
    protected $name;

    /**
     * 不存在时候, 使用引用 "$ref": "#/definitions/Category"
     * @var string array | object | string | integer | boolean
     */
    protected $type = 'object';

    /**
     * 格式化
     * @var string int32 date-time
     */
    protected $format;

    /**
     * 对象时候的属性
     * @var object[]
     */
    protected $properties;

    /**
     * 数组类型时候
     * @var Definition[]
     */
    protected $items;

    // 枚举定义
    protected $enum;

    /**
     * @var array {"wrapped": true,"name": "Pet"}
     */
    protected $xml;

    protected $description;

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
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param  string  $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param  string  $format
     */
    public function setFormat(string $format): void
    {
        if ($format) {
            $this->format = $format;
        } else {
            $this->format = null;
        }
    }

    /**
     * @return object[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param  object[]  $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * @return Definition[]
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @param  Definition[]  $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items ?: null;
    }

    /**
     * @return array
     */
    public function getEnum(): array
    {
        return $this->enum;
    }

    /**
     * @param  array  $enum
     */
    public function setEnum(array $enum): void
    {
        $this->enum = $enum;
    }

    /**
     * @return array
     */
    public function getXml(): array
    {
        return $this->xml;
    }

    /**
     * @param  array  $xml
     */
    public function setXml(array $xml): void
    {
        $this->xml = $xml;
    }

    public function toArray()
    {
        $got = [
            "type"        => $this->type,
            "format"      => $this->format,
            "properties"  => $this->properties,
            "description" => trim($this->description,"//"),
            "items"       => $this->items,
            "enum"        => $this->enum,
            "xml"         => $this->xml,
        ];

        foreach ($got as $i => $value) {
            if ($value === null) {
                unset($got[$i]);
            }
        }
        return $got;
    }
}