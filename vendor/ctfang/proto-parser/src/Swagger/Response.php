<?php


namespace ProtoParser\Swagger;


class Response
{
    protected $code = 200;
    protected $description = '响应';

    /**
     * "schema": {
     *     "$ref": "#/definitions/Pet"
     * }
     * @var array
     */
    protected $schema;

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param  int  $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
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

    public function toArray()
    {
        $got = [
            "description" => $this->description,
            "schema"      => $this->schema,
        ];
        foreach ($got as $i => $value) {
            if ($value === null) {
                unset($got[$i]);
            }
        }
        return $got;
    }
}