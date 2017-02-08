<?php

namespace Aidantwoods\MarkdownPhpDocs;

class PhpDocWrapper
{
    const TMP_DIR = '.markdown-php-docs-tmp';
    const PHPDOC_BIN = 'vendor/phpdocumentor/phpdocumentor/bin';

    private $structure;

    public function __construct(array $options)
    {
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
        $this->structure = simplexml_load_file(self::TMP_DIR.'/structure.xml');
    }

    private function cleanup()
    {
        shell_exec('rm -rf '.self::TMP_DIR.'/phpdoc-cache-*');
        shell_exec('rm '.self::TMP_DIR.'/structure.xml');

        rmdir(self::TMP_DIR);
    }

    private function runPhpDoc()
    {
        shell_exec(
            __DIR__.'/../'.self::PHPDOC_BIN
                .'/phpdoc -f '
                . $this->options['f']
                . ' -t '.self::TMP_DIR
                . ' --visibility public --template="xml"'
        );
    }
}
