<?php

namespace Aidantwoods\MarkdownPhpDocs;

use Aidantwoods\BetterOptions\OptionLoader;

class MarkdownPhpDocs
{
    private $structure,
            $options;

    public function __construct()
    {
        $OptionLoader = new OptionLoader(__DIR__.'/CommandLineOptions.json');

        $this->options = $OptionLoader->getOptions();

        if (
            $OptionLoader->getOption('help')->isSet()
            or ! $OptionLoader->getGroup('required')->isSet()
        ) {
            if ( ! $OptionLoader->getOption('help')->isSet())
            {
                echo implode("\n", $OptionLoader->getResponseMessages()) . "\n\n";
            }

            echo $OptionLoader->getHelp();
            die();
        }

        $this->output('Generating structure with phpdoc... ', false);

        $PhpDocWrapper = new PhpDocWrapper($this->options);
        $PhpDocWrapper->run();

        $this->structure = $PhpDocWrapper->getStructure();

        $this->output('done.');
    }

    public function generateMdFiles()
    {
        $this->output('Building markdown files... ', false);

        if ( ! file_exists($this->options['target']->getValue()))
        {
            mkdir($this->options['target']->getValue());
        }

        foreach ($this->structure->file->class->method as $methodStructure)
        {
            $method = new Method($methodStructure, $this->structure->file->class->constant);

            file_put_contents(
                FolderOperations::normaliseDirectory($this->options['target']->getValue())
                    . '/' . $methodStructure->name
                    . '.md' , $method->generate()
            );
        }

        $this->output('done.');
    }

    private function output($text = '', $addNewline = true, $addCarriageReturn = false)
    {
        if ($this->options['verbose']->isSet())
        {
            echo ($addCarriageReturn ? "\r" : '')
                    . $text
                    . ($addNewline ? "\n" : '');
        }
    }
}
