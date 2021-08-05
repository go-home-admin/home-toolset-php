<?php


namespace GoLang\Parser\FileParser;


class Attribute
{
    protected $name;
    protected $type;
    protected $isPointer = false;

    // 非数组、切片类型
    protected $notArray = true;

    protected $tags = [];

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
     * @return bool
     */
    public function isPointer(): bool
    {
        return $this->isPointer;
    }

    /**
     * @param  bool  $isPointer
     */
    public function setIsPointer(bool $isPointer): void
    {
        $this->isPointer = $isPointer;
    }

    /**
     * @return bool
     */
    public function isNotArray(): bool
    {
        return $this->notArray;
    }

    /**
     * @param  bool  $notArray
     */
    public function setNotArray(bool $notArray): void
    {
        $this->notArray = $notArray;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param  array  $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }
}