<?php

namespace Aidantwoods\MarkdownPhpDocs;

class PhpDocWrapper
{
    const PHPDOC_BIN = 'vendor/phpdocumentor/phpdocumentor/bin';

    private $structure,
            $tmpDir,
            $options,
            $dir;

    public function __construct(array $options, $dir)
    {
        $this->tmpDir = FolderOperations::normaliseDirectory(
            shell_exec('mktemp -d')
        );

        $this->dir = $dir;

        $this->options = $options;
    }

    public function run()
    {
        $this->runPhpDoc();

        $this->loadStructure();

        $this->cleanup();
    }

    public function getStructure()
    {
        return $this->structure;
    }

    private function loadStructure()
    {
        $file = unserialize(
            file_get_contents(
                call_user_func(
                    function()
                    {
                        foreach (scandir("$this->tmpDir/phpdoc-cache-9f") as $f)
                        {
                            if (strpos($f, 'phpdoc-cache-file') === 0)
                            {
                                return "$this->tmpDir/phpdoc-cache-9f/$f";
                            }
                        }

                        throw new \Exception('Could not get phpdoc cache file');
                    }
                )
            )
        );

        foreach($file->getClasses()->getAll() as $class)
        {
            $this->structure = $class;

            break;
        }
    }

    private function cleanup()
    {
        shell_exec('rm -r '.$this->tmpDir.'/phpdoc-cache-*');

        rmdir($this->tmpDir);
    }

    private function runPhpDoc()
    {
        shell_exec(
            'php '.$this->dir.'/../'.self::PHPDOC_BIN.'/phpdoc'
                . ' project:parse'
                . ' -f ' . $this->options['file']->getValue()
                . ' -t ' . $this->tmpDir
        );
    }
}
