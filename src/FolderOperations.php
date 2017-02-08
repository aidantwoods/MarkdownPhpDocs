<?php

namespace Aidantwoods\MarkdownPhpDocs;

class FolderOperations
{
    public static function normaliseDirectory($dir)
    {
        return preg_replace('/[\/]$/', '', $dir);
    }
}
