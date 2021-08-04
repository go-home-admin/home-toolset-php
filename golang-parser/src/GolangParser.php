<?php


namespace GoLang\Parser;


class GolangParser
{
    protected $parserObject;

    public function __construct()
    {
        $this->parserObject = [

        ];
    }

    public function parser(GolangToArray $goArray, string $file = ''): self
    {
        $self = clone $this;

        return $self;
    }
}