<?php

namespace GoLang\Parser\Generate;

class GoLangFile
{
    protected $file;
    protected $package;
    protected $func;
    protected $var;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function push()
    {
        var_dump($this);
    }

    /**
     * @return string
     */
    public function getPackage(): string
    {
        return $this->package;
    }

    /**
     * @param  string  $package
     */
    public function setPackage(string $package): void
    {
        $this->package = $package;
    }

    /**
     * @return array
     */
    public function getFunc(): array
    {
        return $this->func;
    }

    /**
     * @param  GoLangFunc  $func
     */
    public function addFunc(GoLangFunc $func): void
    {
        $this->func[] = $func;
    }


    /**
     * @return array
     */
    public function getVar(): array
    {
        return $this->var;
    }

    /**
     * @param $var
     */
    public function addVar($var): void
    {
        $this->var[] = $var;
    }
}