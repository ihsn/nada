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

trait TagSearchTrait
{
    /**
     * Searches for the actual name of a tag.
     *
     * The search performed is case-insensitive.
     *
     * @param string $needle
     *
     * @return string|null
     */
    protected function tagSearch($needle, array $haystack)
    {
        foreach ($haystack as $actual) {
            if (0 === strcasecmp($needle, $actual)) {
                return $actual;
            }
        }

        return null;
    }
}
