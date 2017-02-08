<?php

namespace Aidantwoods\MarkdownPhpDocs;

class Options
{
    private $options;
    private $requiredOptions;

    public function __construct(array $requiredOptions)
    {
        $this->options = getopt(implode(':', $requiredOptions).':');
        $this->requiredOptions = $requiredOptions;

        $this->checkRequired();

    }

    private function checkRequired()
    {
        if (call_user_func(function() {
            foreach ($this->requiredOptions as $option)
            {
                if ( ! array_key_exists($option, $this->options))
                {
                    return true;
                }
            }
            return false;
        })) {
            die(
                "Not enough args.\nUse `-f` for input file, and `-t` for target directory.\n");
        }
    }

    public function getOptions()
    {
        return $this->options;
    }
}
