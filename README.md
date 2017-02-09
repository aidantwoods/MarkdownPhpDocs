# MarkdownPhpDocs

Very hastily thrown together package to generate `*.md` files from a slightly
customised version of phpdoc.

## How to use
[![Tutorial](https://img.youtube.com/vi/I8dMcq259h8/0.jpg)](https://www.youtube.com/watch?v=I8dMcq259h8)

## Installation:
```bash
git clone https://github.com/aidantwoods/MarkdownPhpDocs
cd MarkdownPhpDocs
composer update
```

You can then either directly run the file `markdown-phpdoc` located in the
`bin` directory, or add this bin directory to your `~/.bash_profile` to run
from anywhere. e.g. by adding the lines:

```bash
PATH="[local-file-path-goes-here]/MarkdownPhpDocs/bin:${PATH}"
export PATH
```

> Note that you'll need composer to pull down the the customised version of
> phpdoc as a dependency
> ([available here](https://github.com/aidantwoods/phpDocumentor2/tree/no-markdown)),
> which serves to disable markdown processing in phpdoc (this to avoid markdown
> ending up within HTML, and thus not being ignored by most markdown parsers).

## Usage
```
markdown-phpdoc -f [input file] -t [target directory]
```