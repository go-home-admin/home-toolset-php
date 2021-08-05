<?php


namespace GoLang\Parser;

class GolangParser
{
    protected $file;
    protected $fileInfo;

    public function parser(GolangToArray $goArray): self
    {
        $self       = clone $this;
        $self->file = $goArray->file;

        $self->fileInfo = ArrayToFileInfo::toInfo($goArray);
        return $self;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }


    /**
     * @return string
     */
    public function getPackage(): string
    {
        /** @var \GoLang\Parser\FileParser\Package $item */
        foreach ($this->fileInfo['package']??[] as $item){
            return $item->getValue();
        }
        return 'main';
    }

    /**
     * @return \GoLang\Parser\FileParser\Type[]
     */
    public function getType(): array
    {
        return $this->fileInfo['type'] ?? [];
    }
}