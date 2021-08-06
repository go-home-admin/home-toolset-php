<?php

namespace GoLang\Parser\Generate;

class GoLangVar
{
    protected $name;
    protected $type;

    public function push(): string
    {
        return "var {$this->getName()} {$this->getType()}";
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
}