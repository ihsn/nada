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

trait TagCoverageTrait
{
    use TagSearchTrait;

    /** @var array */
    private $tagCoverageList = [
        '_original',
        '_type',
    ];

    /** @var string */
    private $tagCoverageStrategy = 'blacklist';

    /**
     * @param array  $tags     List of tags to be covered
     * @param string $strategy Can assume "whitelist" (default) or "blacklist"
     */
    public function setTagCoverage($tags, $strategy = null)
    {
        $this->tagCoverageList = $tags;
        $this->tagCoverageStrategy = $strategy ?: 'whitelist';
    }

    /**
     * Calculates which tags are covered.
     *
     * The search performed internally is case-insensitive.
     *
     * @return array
     */
    protected function getCoveredTags(array $tags)
    {
        // Finds for actual tag names
        $matched = [];
        foreach ($this->tagCoverageList as $original) {
            $actual = $this->tagSearch($original, $tags);
            if (null !== $actual) {
                $matched[] = $actual;
            }
        }

        // Whitelist
        if ('whitelist' === $this->tagCoverageStrategy) {
            return $matched;
        }

        // Blacklist
        return array_values(array_diff($tags, $matched));
    }
}
