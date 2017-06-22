<?php

namespace Aidantwoods\MarkdownPhpDocs;

use phpDocumentor\Descriptor\MethodDescriptor;
use phpDocumentor\Descriptor\Collection;
use phpDocumentor\Descriptor\Tag\ParamDescriptor;

class Method
{
    private $method,
            $docblock,
            $name,
            $constants = array(),

            $tags,
            $overriddenDefaults,
            $args,
            $optionalArgStart;

    public function __construct(MethodDescriptor $method, array $constants)
    {
        $this->method = $method;
        $this->summary = $method->getSummary();
        $this->description = $method->getDescription();
        $this->name = $method->getName();

        foreach ($constants as $constant)
        {
            $this->constants[(string) $constant->getName()]
                = $this->stripNamespace($constant->getFullyQualifiedStructuralElementName());
        }
    }

    public function generate()
    {
        $lines = array();

        $return = 'void';
        $returnDescription = '';

        foreach ($this->method->getTags() as $tags)
        {
            if ($tags instanceof Collection or is_array($tags))
            {
                foreach ($tags as $tag)
                {
                    if ($tag->getName() == 'return')
                    {
                        $return = $this->friendifyTag($tag);

                        $returnDescription = (string) $tag->getDescription();

                        break;
                    }
                }
            }
            elseif ($tags->getName() == 'return')
            {
                $return = $this->friendifyTag($tags);

                $returnDescription = (string) $tags->getDescription();
            }


        }

        $lines[] = '## Description';
        $lines[] = '```php';
        $lines[] = $return . ' '
                    . $this->name . ' (' . $this->generateArgTexts()  . ')';
        $lines[] = '```';

        $lines[] = '';

        $shortDesc = $this->populateLinks($this->summary);

        $longDesc = $this->populateLinks($this->description);

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
                $lines[] = '### ' . substr($tag->getVariableName(), 1);

                $description = $tag->getDescription();

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

        foreach ($this->method->getArguments() as $arg)
        {
            $argString = $this->processArg($arg);

            if ( ! empty($arg->getDefault()) and ! isset($this->optionalArgStart))
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

    private function friendifyTag($tag)
    {
        $s = '';

        foreach ($tag->getTypes() as $type)
        {
            $s .= $this->stripNamespace($type->getName()).'|';
        }

        if (strlen($s) > 0)
        {
            $s = substr($s, 0, strlen($s) -1);
        }

        return $s;
    }

    private function processArg($arg)
    {
        $friendlyType = $this->friendifyTag($arg);
        $friendlyType = preg_replace('/[|]/', ' | ', $friendlyType);

        if ( ! empty($arg->getDefault()))
        {
            if (array_key_exists((string) $arg->getName(), $this->overriddenDefaults))
            {
                $default = $this->overriddenDefaults[(string) $arg->getName()];
            }
            else
            {
                $default = $arg->getDefault();
            }

            $defaultText = ' = ' . $default;
        }
        else
        {
            $defaultText = '';
        }

        return $friendlyType . ' '
                . ($arg->isByReference() ? '&' : '')
                . $arg->getName()
                . $defaultText;
    }

    private function populateLinks($text)
    {
        $text = preg_replace('/[{][@]see[ ](\w+)[}]/', '\\\$1', $text);

        if (
            preg_match('/\\\([A-Z_]+)/', $text, $match)
            and array_key_exists($match[1], $this->constants)
        ) {
            $text = preg_replace('/\\\(\w+)/', '`'.$this->constants[$match[1]].'`', $text);
        }

        $text = preg_replace('/\\\(\w+)/', '[`->$1`]($1)', $text);

        # fix any @see statements PhpDoc missed o.0



        return $text;
    }

    private function processTags()
    {
        $tags = array();

        $this->overriddenDefaults = array();

        foreach ($this->method->getTags() as $tagCollection)
        {
            foreach ($tagCollection as $tag)
            {
                if ($tag->getName() === 'param')
                {
                    if (preg_match('/^[=][ ]?(.++)(?:\n|$)/', $tag->getDescription(), $match))
                    {
                        $this->overriddenDefaults[(string) $tag->getVariableName()] = $match[1];

                        $tag->setDescription(preg_replace('/^[=][ ]?(.++)(?:\n|$)/', '', $tag->getDescription()));
                    }

                    $tags[] = $tag;
                }
            }
        }

        $this->tags = $tags;
    }

    private function isTagDescriptionEmpty()
    {
        foreach ($this->tags as $tag)
        {
            if ( ! empty($tag->getDescription()))
            {
                return false;
            }
        }

        return true;
    }
}
