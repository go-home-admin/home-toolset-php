<?php


namespace GoLang\Parser\FileParser;


class Parameter
{
    protected $name;
    protected $type;
    protected $isPointer = false;

    // 非数组、切片类型
    protected $notArray = true;

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
}