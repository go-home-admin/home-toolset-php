<?php

namespace GoLang\Parser\Generate;

class GoLangFunc
{
    protected $name;
    protected $parameter = [];
    protected $returns = [];
    protected $code = "";

    public function push(): string
    {
        // return
        $returns = "";
        foreach ($this->getReturns() as $return) {
            if (!$returns) {
                $returns .= $return;
            } else {
                $returns .= ",".$return;
            }
        }
        if (count($this->getReturns()) >= 2) {
            $returns = " ({$returns}) ";
        }else{
            $returns = " {$returns} ";
        }

        // 函数参数
        $pars = "";
        foreach ($this->getParameter() as $par) {
            if (!$pars) {
                $pars .= $par;
            } else {
                $pars .= ", ".$par;
            }
        }

        return "func {$this->getName()}({$pars}){$returns}{{$this->getCode()}}";
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
     * @return array
     */
    public function getParameter(): array
    {
        return $this->parameter;
    }

    /**
     * @param  array  $parameter
     */
    public function setParameter(array $parameter): void
    {
        $this->parameter = $parameter;
    }

    /**
     * @return array
     */
    public function getReturns(): array
    {
        return $this->returns;
    }

    /**
     * @param  array  $returns
     */
    public function setReturns(array $returns): void
    {
        $this->returns = $returns;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param  string  $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }
}