<?php

namespace Aidantwoods\MarkdownPhpDocs;

class PhpDocWrapper
{
    const TMP_DIR = 'markdown-php-docs-tmp';
    const PHPDOC_BIN = '/vendor/aidantwoods/phpDocumentor2/bin';

    private $initcwd;
    private $structure;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function run()
    {
        $this->preserveCurrentDirectory();

        $this->changeToPhpDocExpectedDirectory();
        $this->runPhpDoc();

        $this->loadStructureXML();

        $this->restoreOriginalDirectory();
    }

    public function getStructure()
    {
        return $this->structure;
    }

    private function loadStructureXML()
    {
        $this->structure = simplexml_load_file(self::TMP_DIR.'/structure.xml');
    }

    private function changeToPhpDocExpectedDirectory()
    {
        chdir(preg_replace('/[\/][^\/]+$/', '', __DIR__).self::PHPDOC_BIN);
    }

    private function preserveCurrentDirectory()
    {
        $this->initcwd = getcwd();
    }

    private function runPhpDoc()
    {
        shell_exec(
            'phpdoc -f ' . FolderOperations::normaliseDirectory($this->initcwd) . '/' . $this->options['f']
                . ' -t '.self::TMP_DIR
                . ' --visibility public --template="xml"'
        );
    }

    private function restoreOriginalDirectory()
    {
        chdir($this->initcwd);
    }
}
