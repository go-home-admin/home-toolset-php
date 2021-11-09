<?php


namespace ProtoParser\Swagger;


class Tag
{
    protected $name;
    protected $description;
    protected $externalDocs = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param  string  $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param  mixed  $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getExternalDocs(): array
    {
        return $this->externalDocs;
    }

    /**
     * [
     * "description": "Find out more",
     * "url": "http://swagger.io"
     * ]
     * @param  mixed  $externalDocs
     */
    public function setExternalDocs(array $externalDocs): void
    {
        $this->externalDocs = $externalDocs;
    }

    public function toArray(): array
    {
        $got = [
            "name"         => $this->name,
            "description"  => $this->description,
        ];
        if ($this->externalDocs){
            $got["externalDocs"] = $this->externalDocs;
        }
        return $got;
    }
}