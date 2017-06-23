<?php

namespace Aidantwoods\MarkdownPhpDocs;

use Aidantwoods\BetterOptions\OptionLoader;

class MarkdownPhpDocs
{
    private $structure,
            $options;

    public function __construct()
    {
        $OptionLoader = new OptionLoader(__DIR__.'/CommandLineOptions.yaml');

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
        $this->output('Building markdown files...');

        $target = FolderOperations::normaliseDirectory(
            $this->options['target']->getValue()
        );

        if ( ! file_exists($target))
        {
            mkdir($target);
        }

        $supplements = $this->getSupplements();

        foreach ($this->structure->getMethods()->getAll() as $methodStructure)
        {
            if ($methodStructure->getVisibility() !== 'public')
            {
                continue;
            }

            $fileName = $methodStructure->getName() . '.md';

            if (($supplement = $this->getSupplement($fileName)) !== false)
            {
                $this->output("Using supplement file '$fileName'");

                $content = $supplement;

                unset($supplements[$fileName]);
            }
            else
            {
                $method = new Method($methodStructure, $this->structure->getConstants()->getAll());
                $content = $method->generate();
            }

            if (($complement = $this->getComplement($fileName)) !== false)
            {
                $this->output("Appending complement file '$fileName'");

                $content .= "\n$complement";
            }

            file_put_contents("$target/$fileName", $content);
        }

        if ( ! empty($supplements))
        {
            foreach ($supplements as $fileName)
            {
                $content = '';

                if (($supplement = $this->getSupplement($fileName)) !== false)
                {
                    $this->output("Using supplement file '$fileName'");

                    $content = $supplement;
                }

                if (($complement = $this->getComplement($fileName)) !== false)
                {
                    $this->output("Appending complement file '$fileName'");

                    $content .= "\n$complement";
                }

                file_put_contents("$target/$fileName", $content);
            }
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

    private function getSupplement($file)
    {
        $file = (string) $file;

        if ($this->options['supplement']->isSet())
        {
            $supplementDir = FolderOperations::normaliseDirectory($this->options['supplement']->getValue());

            if (is_file("$supplementDir/$file"))
            {
                return file_get_contents("$supplementDir/$file");
            }
        }

        return false;
    }

    private function getSupplements()
    {
        $supplements = array();

        $supplementDir = FolderOperations::normaliseDirectory($this->options['supplement']->getValue());

        if ($this->options['supplement']->isSet())
        {
            foreach (scandir($supplementDir) as $item)
            {
                if (is_file("$supplementDir/$item"))
                {
                    $supplements[$item] = $item;
                }
            }
        }

        return $supplements;
    }

    private function getComplement($file)
    {
        $file = (string) $file;

        if ($this->options['complement']->isSet())
        {
            $complementDir = FolderOperations::normaliseDirectory($this->options['complement']->getValue());

            if (is_file("$complementDir/$file"))
            {
                return file_get_contents("$complementDir/$file");
            }
        }

        return false;
    }
}
