<?php

/*
 * This file is part of the BibTex Parser.
 *
 * (c) Renan de Lima Barbosa <renandelima@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RenanBr\BibTexParser\Processor;

use Composer\InstalledVersions;
use Exception;
use Pandoc\Pandoc;
use RenanBr\BibTexParser\Exception\ProcessorException;
use RuntimeException;

/**
 * Translates LaTeX texts to unicode.
 */
class LatexToUnicodeProcessor
{
    use TagCoverageTrait;

    /** @var (callable(string): string)|null */
    private $converter;

    /**
     * @return array
     */
    public function __invoke(array $entry)
    {
        $covered = $this->getCoveredTags(array_keys($entry));
        foreach ($covered as $tag) {
            // Translate string
            if (\is_string($entry[$tag])) {
                $entry[$tag] = $this->decode($entry[$tag]);
                continue;
            }

            // Translate array
            if (\is_array($entry[$tag])) {
                array_walk_recursive($entry[$tag], function (&$text) {
                    if (\is_string($text)) {
                        $text = $this->decode($text);
                    }
                });
            }
        }

        return $entry;
    }

    /**
     * @param mixed $text
     *
     * @return string
     */
    private function decode($text)
    {
        try {
            return \call_user_func($this->getConverter(), $text);
        } catch (Exception $exception) {
            throw new ProcessorException(sprintf('Error while processing LaTeX to Unicode: %s', $exception->getMessage()), 0, $exception);
        }
    }

    /**
     * @return (callable(string): string)
     */
    private function getConverter()
    {
        if ($this->converter) {
            return $this->converter;
        }

        if (InstalledVersions::isInstalled('ueberdosis/pandoc')) {
            $pandoc = new Pandoc();

            return $this->converter = static function ($text) use ($pandoc) {
                // @phpstan-ignore-next-line
                return mb_substr($pandoc->input($text)->execute([
                    '--from', 'latex',
                    '--to', 'plain',
                    '--wrap', 'none',
                ]), 0, -1);
            };
        } elseif (InstalledVersions::isInstalled('ryakad/pandoc-php')) {
            $pandoc = new Pandoc();

            return $this->converter = static function ($text) use ($pandoc) {
                return $pandoc->runWith($text, [
                    'from' => 'latex',
                    'to' => 'plain',
                    'wrap' => 'none',
                ]);
            };
        }

        throw new RuntimeException('Pandoc wrapper not installed. Try running "composer require ueberdosis/pandoc"');
    }
}
