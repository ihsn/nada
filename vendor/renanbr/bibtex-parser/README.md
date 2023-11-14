<h1 align="center">PHP BibTeX Parser 2.x</h1>
<p align="center">
    This is a
    <a href="https://tug.org/bibtex/">BibTeX</a>
    parser written in
    <a href="https://php.net">PHP</a>.
</p>
<p align="center">
    <a href="https://tug.org/bibtex/">
        <img src="https://upload.wikimedia.org/wikipedia/commons/3/30/BibTeX_logo.svg" height="83" alt="BibTeX logo">
    </a>
    <a href="https://php.net">
        <img src="https://upload.wikimedia.org/wikipedia/commons/2/27/PHP-logo.svg" height="83" alt="PHP logo">
    </a>
</p>

![Tests](https://github.com/renanbr/bibtex-parser/workflows/Tests/badge.svg)
[![codecov](https://codecov.io/gh/renanbr/bibtex-parser/branch/master/graph/badge.svg)](https://codecov.io/gh/renanbr/bibtex-parser)
![Static Analysis](https://github.com/renanbr/bibtex-parser/workflows/Static%20Analysis/badge.svg)
![Coding Standards](https://github.com/renanbr/bibtex-parser/workflows/Coding%20Standards/badge.svg)

You are browsing the documentation of **BibTeX Parser 2.x**, the latest version.

## Table of contents

* [Installing](#installing)
* [Usage](#usage)
* [Vocabulary](#vocabulary)
* [Processors](#processors)
   * [Tag name case](#tag-name-case)
   * [Authors and editors](#authors-and-editors)
   * [Keywords](#keywords)
   * [Date](#date)
   * [Fill missing tag](#fill-missing-tag)
   * [Trim tags](#trim-tags)
   * [Determine URL from the DOI](#determine-url-from-the-doi)
   * [LaTeX to unicode](#latex-to-unicode)
   * [Custom](#custom)
* [Handling errors](#handling-errors)
* [Advanced usage](#advanced-usage)

## Installing

```bash
composer require renanbr/bibtex-parser
```

## Usage

```php
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor;

require 'vendor/autoload.php';

$bibtex = <<<BIBTEX
@article{einstein1916relativity,
  title={Relativity: The Special and General Theory},
  author={Einstein, Albert},
  year={1916}
}
BIBTEX;

// Create and configure a Listener
$listener = new Listener();
$listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
// $listener->addProcessor(new Processor\NamesProcessor());
// $listener->addProcessor(new Processor\KeywordsProcessor());
// $listener->addProcessor(new Processor\DateProcessor());
// $listener->addProcessor(new Processor\FillMissingProcessor([/* ... */]));
// $listener->addProcessor(new Processor\TrimProcessor());
// $listener->addProcessor(new Processor\UrlFromDoiProcessor());
// $listener->addProcessor(new Processor\LatexToUnicodeProcessor());
// ... you can append as many Processors as you want

// Create a Parser and attach the listener
$parser = new Parser();
$parser->addListener($listener);

// Parse the content, then read processed data from the Listener
$parser->parseString($bibtex); // or parseFile('/path/to/file.bib')
$entries = $listener->export();

print_r($entries);
```

This will output:

```
Array
(
    [0] => Array
        (
            [_type] => article
            [citation-key] => einstein1916relativity
            [title] => Relativity: The Special and General Theory
            [author] => Einstein, Albert
            [year] => 1916
        )
)
```

## Vocabulary

[BibTeX] is all about "entry", "tag's name" and "tag's content".

> A [BibTeX] **entry** consists of the type (the word after @), a citation-key and a number of tags which define various characteristics of the specific [BibTeX] entry.
> (...) A [BibTeX] **tag** is specified by its **name** followed by an equals sign, and the **content**.

Source: http://www.bibtex.org/Format/

Note:
This library considers "type" and "citation-key" as tags.
This behavior can be changed [implementing your own Listener](#advanced-usage).

## Processors

`Processor` is a [callable] that receives an entry as argument and returns a modified entry.

This library contains three main parts:

- `Parser` class, responsible for detecting units inside a [BibTeX] input;
- `Listener` class, responsible for gathering units and transforming them into a list of entries;
- `Processor` classes, responsible for manipulating entries.

Despite you can't configure the `Parser`, you can append as many `Processor` as you want to the `Listener` through `Listener::addProcessor()` before exporting the contents.
Be aware that `Listener` provides, by default, these features:

- Found entries are reachable through `Listener::export()` method;
- [Tag content concatenation](http://www.bibtex.org/Format/);
    - e.g. `hello # " world"` tag's content will generate `hello world` [string]
- [Tag content abbreviation handling](http://www.bibtex.org/Format/);
    - e.g. `@string{foo="bar"} @misc{bar=foo}` will make `$entries[1]['bar']` assume `bar` as value
- Publication's type exposed as `_type` tag;
- Citation key exposed as `citation-key` tag;
- Original entry text exposed as `_original` tag.

This project ships some useful processors.

### Tag name case

In [BibTeX] the tag's names aren't case-sensitive.
This library exposes entries as [array], in which keys are case-sensitive.
To avoid this misunderstanding, you can force the tags' name character case using `TagNameCaseProcessor`.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\TagNameCaseProcessor;

$listener->addProcessor(new TagNameCaseProcessor(CASE_UPPER)); // or CASE_LOWER
```

```bib
@article{
  title={BibTeX rocks}
}
```

```
Array
(
    [0] => Array
        (
            [TYPE] => article
            [TITLE] => BibTeX rocks
        )
)
```

</details>

### Authors and editors

[BibTeX] recognizes four parts of an author's name: First Von Last Jr.
If you would like to parse the `author` and `editor` tags included in your entries, you can use the `NamesProcessor` class.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\NamesProcessor;

$listener->addProcessor(new NamesProcessor());
```

```bib
@article{
  title={Relativity: The Special and General Theory},
  author={Einstein, Albert}
}
```

```
Array
(
    [0] => Array
        (
            [type] => article
            [title] => Relativity: The Special and General Theory
            [author] => Array
                (
                    [0] => Array
                        (
                            [first] => Albert
                            [von] =>
                            [last] => Einstein
                            [jr] =>
                        )
                )
        )
)
```

</details>

### Keywords

The `keywords` tag contains a list of expressions represented as [string], you might want to read them as an [array] instead.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\KeywordsProcessor;

$listener->addProcessor(new KeywordsProcessor());
```

```bib
@misc{
  title={The End of Theory: The Data Deluge Makes the Scientific Method Obsolete},
  keywords={big data, data deluge, scientific method}
}
```

```
Array
(
    [0] => Array
        (
            [type] => misc
            [title] => The End of Theory: The Data Deluge Makes the Scientific Method Obsolete
            [keywords] => Array
                (
                    [0] => big data
                    [1] => data deluge
                    [2] => scientific method
                )
        )
)
```

</details>

### Date

It adds a new tag `_date` as [DateTimeImmutable].
This processor adds the new tag **if and only if** this the tags `month` and `year` are fulfilled.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\DateProcessor;

$listener->addProcessor(new DateProcessor());
```

```bib
@misc{
  month="1~oct",
  year=2000
}
```

```
Array
(
    [0] => Array
        (
            [type] => misc
            [month] => 1~oct
            [year] => 2000
            [_date] => DateTimeImmutable Object
                (
                    [date] => 2000-10-01 00:00:00.000000
                    [timezone_type] => 3
                    [timezone] => UTC
                )
        )
)
```

</details>

### Fill missing tag

It puts a default value to some missing field.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\FillMissingProcessor;

$listener->addProcessor(new FillMissingProcessor([
    'title' => 'This entry has no title',
    'year' => 1970,
]));
```

```bib
@misc{
}

@misc{
    title="I do exist"
}
```

```
Array
(
    [0] => Array
        (
            [type] => misc
            [title] => This entry has no title
            [year] => 1970
        )
    [1] => Array
        (
            [type] => misc
            [title] => I do exist
            [year] => 1970
        )
)
```

</details>

### Trim tags

Apply [trim()] to all tags.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\TrimProcessor;

$listener->addProcessor(new TrimProcessor());
```

```bib
@misc{
  title=" too much space  "
}
```

```
Array
(
    [0] => Array
        (
            [type] => misc
            [title] => too much space
        )

)
```

</details>

### Determine URL from the DOI

Sets `url` tag with [DOI] if `doi` tag is present and `url` tag is missing.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\UrlFromDoiProcessor;

$listener->addProcessor(new UrlFromDoiProcessor());
```

```bib
@misc{
  doi="qwerty"
}

@misc{
  doi="azerty",
  url="http://example.org"
}
```

```
Array
(
    [0] => Array
        (
            [type] => misc
            [doi] => qwerty
            [url] => https://doi.org/qwerty
        )

    [1] => Array
        (
            [type] => misc
            [doi] => azerty
            [url] => http://example.org
        )
)
```

</details>

### LaTeX to unicode

[BibTeX] files store [LaTeX] contents.
You might want to read them as unicode instead.
The `LatexToUnicodeProcessor` class solves this problem, but before adding the processor to the listener you must:

- [install Pandoc](http://pandoc.org/installing.html) in your system; and
- add [ryakad/pandoc-php](https://github.com/ryakad/pandoc-php) or [ueberdosis/pandoc](https://github.com/ueberdosis/pandoc) as a dependency of your project.

<details><summary>Usage</summary>

```php
use RenanBr\BibTexParser\Processor\LatexToUnicodeProcessor;

$listener->addProcessor(new LatexToUnicodeProcessor());
```

```bib
@article{
  title={Caf\\'{e}s and bars}
}
```

```
Array
(
    [0] => Array
        (
            [type] => article
            [title] => Cafés and bars
        )
)
```

</details>

Note: Order matters, add this processor as the last.

### Custom

The `Listener::addProcessor()` method expects a [callable] as argument.
In the example shown below, we append the text `with laser` to the `title` tags for all entries.

<details><summary>Usage</summary>

```php
$listener->addProcessor(static function (array $entry) {
    $entry['title'] .= ' with laser';
    return $entry;
});
```

```
@article{
  title={BibTeX rocks}
}
```

```
Array
(
    [0] => Array
        (
            [type] => article
            [title] => BibTeX rocks with laser
        )
)
```

</details>

## Handling errors

This library throws two types of exception: `ParserException` and `ProcessorException`.
The first one may happen during the data extraction.
When it occurs it probably means the parsed BibTeX isn't valid.
The second exception may happen during the data processing.
When it occurs it means the listener's processors can't handle properly the data found.
Both implement `ExceptionInterface`.

```php
use RenanBr\BibTexParser\Exception\ExceptionInterface;
use RenanBr\BibTexParser\Exception\ParserException;
use RenanBr\BibTexParser\Exception\ProcessorException;

try {
    // ... parser and listener configuration

    $parser->parseFile('/path/to/file.bib');
    $entries = $listener->export();
} catch (ParserException $exception) {
    // The BibTeX isn't valid
} catch (ProcessorException $exception) {
    // Listener's processors aren't able to handle data found
} catch (ExceptionInterface $exception) {
    // Alternatively, you can use this exception to catch all of them at once
}
```

## Advanced usage

The core of this library contains these main classes:

- `RenanBr\BibTexParser\Parser` responsible for detecting units inside a [BibTeX] input;
- `RenanBr\BibTexParser\ListenerInterface` responsible for treating units found.

You can attach listeners to the parser through `Parser::addListener()`.
The parser is able to detect [BibTeX] units, such as "type", "tag's name", "tag's content".
As the parser finds a unit, it triggers the listeners attached to it.

You can code your own listener! All you have to do is handle units.

```php
namespace RenanBr\BibTexParser;

interface ListenerInterface
{
    /**
     * Called when an unit is found.
     *
     * @param string $text    The original content of the unit found.
     *                        Escape character will not be sent.
     * @param string $type    The type of unit found.
     *                        It can assume one of Parser's constant value.
     * @param array  $context Contains details of the unit found.
     */
    public function bibTexUnitFound($text, $type, array $context);
}
```

`$type` may assume one of these values:

- `Parser::TYPE`
- `Parser::CITATION_KEY`
- `Parser::TAG_NAME`
- `Parser::RAW_TAG_CONTENT`
- `Parser::BRACED_TAG_CONTENT`
- `Parser::QUOTED_TAG_CONTENT`
- `Parser::ENTRY`

`$context` is an [array] with these keys:

- `offset` contains the `$text`'s beginning position.
  It may be useful, for example, to [seek on a file pointer](https://php.net/fseek);
- `length` contains the original `$text`'s length.
  It may differ from [string] length sent to the listener because may there are escaped characters.

[BibTeX]: https://tug.org/bibtex/
[DOI]: https://www.doi.org/
[DateTimeImmutable]: https://www.php.net/manual/class.datetimeimmutable.php
[LaTeX]: https://www.latex-project.org/
[array]: https://php.net/manual/language.types.array.php
[callable]: https://php.net/manual/en/language.types.callable.php
[string]: https://php.net/manual/language.types.string.php
[trim()]: https://www.php.net/trim
