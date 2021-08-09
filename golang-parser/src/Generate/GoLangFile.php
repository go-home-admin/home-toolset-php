<?php

namespace GoLang\Parser\Generate;

class GoLangFile
{
    protected $file;
    protected $package;
    protected $import = [];
    protected $func = [];
    protected $var = [];
    protected $templateCache;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function push()
    {
        $goFileContent = $this->getGoTemplate();
        $goFileContent = str_replace("{package}", $this->getPackage(), $goFileContent);

        $import        = $this->genImport();
        $goFileContent = str_replace("{import}", $import, $goFileContent);

        $var = "";
        foreach ($this->getVar() as $v) {
            $var .= "\n".$v->push();
        }
        $goFileContent = str_replace("{var}", $var, $goFileContent);

        $func = "";
        foreach ($this->getFunc() as $fun) {
            $func .= "\n\n".$fun->push();
        }
        $goFileContent = str_replace("{func}", $func, $goFileContent);
        $goFileContent = str_replace(['    ', "\n\t\t\n",], ["\t", "\n\n"], $goFileContent);

        file_put_contents($this->file, $goFileContent);
    }

    private function genImport(): string
    {
        $import = '';
        if ($this->getImport()) {
            foreach ($this->getImport() as $alias => $str) {
                $arr = explode("/", $str);
                if (end($arr) == $alias) {
                    $import .= "    \"{$str}\"\n";
                } else {
                    $import .= "    $alias \"{$str}\"\n";
                }
            }
            $import = "\nimport (\n{$import})\n";
        }

        return $import;
    }

    public function getGoTemplate(): string
    {
        if (!$this->templateCache) {
            $this->templateCache = file_get_contents(__DIR__."/template/go");
        }
        return $this->templateCache;
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
     * @return \GoLang\Parser\Generate\GoLangFunc[]
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

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @param  string  $file
     */
    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    /**
     * @return array
     */
    public function getImport(): array
    {
        return $this->import;
    }

    /**
     * @param  array  $import
     */
    public function setImport(array $import): void
    {
        ksort($import);
        $this->import = $import;
    }
}