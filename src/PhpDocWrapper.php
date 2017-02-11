<?php

namespace Aidantwoods\MarkdownPhpDocs;

class PhpDocWrapper
{
    const PHPDOC_BIN = 'vendor/phpdocumentor/phpdocumentor/bin';

    private $structure,
            $tmpDir,
            $options;

    public function __construct(array $options)
    {
        $this->tmpDir = FolderOperations::normaliseDirectory(
            shell_exec('mktemp -d')
        );

        $this->options = $options;
    }

    public function run()
    {
        $this->runPhpDoc();

        $this->loadStructureXML();

        $this->cleanup();
    }

    public function getStructure()
    {
        return $this->structure;
    }

    private function loadStructureXML()
    {
        $this->structure = simplexml_load_file($this->tmpDir.'/structure.xml');
    }

    private function cleanup()
    {
        shell_exec('rm -r '.$this->tmpDir.'/phpdoc-cache-*');
        shell_exec('rm '.$this->tmpDir.'/structure.xml');

        rmdir($this->tmpDir);
    }

    private function runPhpDoc()
    {
        shell_exec(
            __DIR__.'/../'.self::PHPDOC_BIN
                .'/phpdoc -f '
                . $this->options['f']
                . ' -t '.$this->tmpDir
                . ' --visibility public --template="xml"'
        );
    }
}
