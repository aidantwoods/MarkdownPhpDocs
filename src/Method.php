<?php

namespace Aidantwoods\MarkdownPhpDocs;

use \SimpleXMLElement;

class Method
{
    private $structure;
    private $docblock;
    private $name;
    private $constants = array();

    private $tags;
    private $overriddenDefaults;
    private $args;
    private $optionalArgStart;

    public function __construct(SimpleXMLElement $structure, SimpleXMLElement $constants)
    {
        $this->structure = $structure;
        $this->docblock = $structure->docblock;
        $this->name = $structure->name;
        
        foreach ($constants as $constant)
        {
            $this->constants[(string) $constant->name]
                = $this->stripNamespace($constant->full_name);
        }
    }

    public function generate()
    {
        $lines = array();

        $return = 'void';
        $returnDescription = '';

        foreach ($this->structure->docblock->tag as $tag)
        {
            if ($tag['name'] == 'return')
            {
                $return = $this->stripNamespace($tag->type);
                $returnDescription = (string) $tag['description'];
            }
        }

        $lines[] = '## Description';
        $lines[] = '```php';
        $lines[] = $return . ' '
                    . $this->name . ' (' . $this->generateArgTexts()  . ')';
        $lines[] = '```';

        $lines[] = '';

        $shortDesc = $this->populateLinks($this->docblock->description);

        $longDesc = $this->populateLinks($this->docblock->{'long-description'});

        if (substr($shortDesc, -1) === '.' and substr($longDesc, 0, 2) === '..')
        {
            $lines[] = $shortDesc . $longDesc;
        }
        else
        {
            $lines[] = $shortDesc;
            $lines[] = $longDesc;
        }

        $lines[] = '';
        
        if ( ! $this->isTagDescriptionEmpty())
        {
            $lines[] = '## Parameters';

            foreach ($this->tags as $tag)
            {
                $lines[] = '### ' . substr($tag['variable'], 1);

                $description = preg_replace('/<[\/]?p>/', '', $tag['description']);

                $lines[] = $this->populateLinks($description);

                $lines[] = '';
            }
        }

        if ( ! empty($returnDescription))
        {
            $lines[] = '## Return Values';

            $lines[] = $returnDescription;
        }

        return implode("\n", $lines);

    }

    private function generateArgTexts()
    {
        $this->processArgs();

        $texts = array();

        foreach ($this->args as $i => $argText)
        {
            $text = '';

            $text .= ($i === 0 ? ' ' : ', ') . $argText . ' ';

            if (isset($this->optionalArgStart) and $this->optionalArgStart <= $i)
            {
                $text = '[' . $text;
            }

            $texts[] = $text;
        }

        $linePerArg = false;

        if (count($texts) >= 2)
        {
            $linePerArg = true;
        }

        return ($linePerArg ?
                "\n   ". ($this->optionalArgStart === 0 ? ' ' : '')
                : '')
            . implode(($linePerArg ? "\n    " : ''), $texts)
            . (isset($this->optionalArgStart) ?
                str_repeat('] ', count($texts) - $this->optionalArgStart)
                : '')
            . ($linePerArg ? "\n" : '');
    }

    private function processArgs()
    {
        $this->processTags();

        $this->args = array();

        $i = 0;

        foreach ($this->structure->argument as $arg)
        {
            $argString = $this->processArg($arg);

            if ( ! empty($arg->default) and ! isset($this->optionalArgStart))
            {
                $this->optionalArgStart = $i;
            }

            $this->args[] = $argString;

            $i++;
        }
    }

    private function stripNamespace($value)
    {
        return preg_replace('/[^\\\]*+[\\\]/', '', $value);
    }

    private function processArg($arg)
    {
        $friendlyType = $this->stripNamespace($arg->type);
        $friendlyType = preg_replace('/[|]/', ' | ', $friendlyType);

        if ( ! empty($arg->default))
        {
            if (array_key_exists((string) $arg->name, $this->overriddenDefaults))
            {
                $default = $this->overriddenDefaults[(string) $arg->name];
            }
            else
            {
                $default = $arg->default;
            }

            $defaultText = ' = ' . $default;
        }
        else
        {
            $defaultText = '';
        }

        return $friendlyType . ' '
                . ($arg['by_reference'] === 'true' ? '&' : '')
                . $arg->name
                . $defaultText;
    }

    private function populateLinks($text)
    {
        if (
            preg_match('/\\\([A-Z_]+)/', $text, $match)
            and array_key_exists($match[1], $this->constants)
        ) {
            $text = preg_replace('/\\\(\w+)/', '`'.$this->constants[$match[1]].'`', $text);
        }

        $text = preg_replace('/\\\(\w+)/', '[`->$1`]($1)', $text);

        # fix any @see statements PhpDoc missed o.0

        $text = preg_replace('/[{][@]see[ ](\w+)[}]/', '[`->$1`]($1)', $text);

        return $text;
    }

    private function processTags()
    {
        $tags = array();

        $this->overriddenDefaults = array();

        foreach ($this->docblock->tag as $tag)
        {
            if ($tag['name'] == 'param')
            {
                if (preg_match('/^<p>[=][ ]?(\w+(?:[ ]\w+)?)\n/', $tag['description'], $match))
                {
                    $this->overriddenDefaults[(string) $tag['variable']] = $match[1];

                    $tag['description'] = preg_replace('/^<p>[=][ ]?(\w+(?:[ ]\w+)?)\n/', '<p>', $tag['description']);
                }

                $tags[] = $tag;
            }
        }

        $this->tags = $tags;
    }

    private function isTagDescriptionEmpty()
    {
        foreach ($this->tags as $tag)
        {
            if ( ! empty($tag['description']))
            {
                return false;
            }
        }
        
        return true;
    }
}
