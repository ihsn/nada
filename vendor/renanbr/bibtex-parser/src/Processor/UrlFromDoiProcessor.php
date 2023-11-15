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

class UrlFromDoiProcessor
{
    use TagSearchTrait;

    const FORMAT = 'https://doi.org/%s';

    /**
     * @var string
     */
    private $urlFormat;

    /**
     * @param string $urlFormat
     */
    public function __construct($urlFormat = null)
    {
        $this->urlFormat = $urlFormat ?: self::FORMAT;
    }

    /**
     * @return array
     */
    public function __invoke(array $entry)
    {
        $doiTag = $this->tagSearch('doi', array_keys($entry));
        $urlTag = $this->tagSearch('url', array_keys($entry));
        if (null === $urlTag && null !== $doiTag) {
            $doiValue = $entry[$doiTag];
            if (\is_string($doiValue) && '' !== $doiValue) {
                $entry['url'] = sprintf($this->urlFormat, $doiValue);
            }
        }

        return $entry;
    }
}
