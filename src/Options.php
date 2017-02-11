<?php

namespace Aidantwoods\MarkdownPhpDocs;

class Options
{
    private $options,
            $requiredOptions = array('f', 't'),
            $optionalOptions = array('v');

    public function __construct()
    {
        $this->options = getopt(
            implode(':', $this->requiredOptions).':'
            . implode('::', $this->optionalOptions).'::'
        );

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
            die("Not enough args.\n"
                . "Use `-f` for input file, and `-t` for target directory.\n"
                . "Use `-v` for verbose output.\n"
            );
        }
    }

    public function getOptions()
    {
        return $this->options;
    }
}
