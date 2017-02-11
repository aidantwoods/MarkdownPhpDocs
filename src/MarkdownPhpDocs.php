<?php

namespace Aidantwoods\MarkdownPhpDocs;

class MarkdownPhpDocs
{
    private $structure,
            $options;

    public function __construct()
    {
        $this->options = (new Options)->getOptions();

        $this->output('Generating structure with phpdoc... ', false);

        $PhpDocWrapper = new PhpDocWrapper($this->options);
        $PhpDocWrapper->run();

        $this->structure = $PhpDocWrapper->getStructure();

        $this->output('done.');
    }

    public function generateMdFiles()
    {
        $this->output('Building markdown files... ', false);

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

        $this->output('done.');
    }

    private function output($text = '', $addNewline = true, $addCarriageReturn = false)
    {
        if (isset($this->options['v']))
        {
            echo ($addCarriageReturn ? "\r" : '')
                    . $text
                    . ($addNewline ? "\n" : '');
        }
    }
}
