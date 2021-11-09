<?php


namespace ProtoParser\FileParser\Service;


class Option
{
    protected $doc;
    protected $key;
    protected $value;

    /**
     * @return string
     */
    public function getDoc(): string
    {
        return trim($this->doc, "//");
    }

    /**
     * @param  string  $doc
     */
    public function setDoc($doc): void
    {
        $this->doc = $doc;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param  string  $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param  string|array  $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}