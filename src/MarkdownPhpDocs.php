<?php

namespace Aidantwoods\MarkdownPhpDocs;

class MarkdownPhpDocs
{
    private $structure;
    private $options;

    public function __construct()
    {
        $this->options = (new Options(array('f', 't')))->getOptions();

        $PhpDocWrapper = new PhpDocWrapper($this->options);
        $PhpDocWrapper->run();

        $this->structure = $PhpDocWrapper->getStructure();
    }

    public function generateMdFiles()
    {
        if ( ! file_exists($this->options['t']))
        {
            mkdir($this->options['t']);
        }

        foreach ($this->structure->file->class->method as $methodStructure)
        {
            $method = new Method($methodStructure, $this->structure->file->class->constant);

            file_put_contents(
                FolderOperations::normaliseDirectory($this->options['t'])
                    . '/' . $methodStructure->name
                    . '.md' , $method->generate()
            );
        }
    }
}
