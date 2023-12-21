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

/**
 * @author Florent DESPIERRES <florent@despierres.pro>
 */
class TrimProcessor
{
    use TagCoverageTrait;

    public function __construct(array $fields = null)
    {
        if ($fields) {
            $this->setTagCoverage($fields);
        }
    }

    /**
     * @return array
     */
    public function __invoke(array $entry)
    {
        $covered = $this->getCoveredTags(array_keys($entry));
        foreach ($covered as $tag) {
            $entry[$tag] = $this->trim($entry[$tag]);
        }

        return $entry;
    }

    private function trim($value)
    {
        if (\is_array($value)) {
            $trimmed = [];
            foreach ($value as $key => $subValue) {
                $trimmed[$key] = $this->trim($subValue);
            }

            return $trimmed;
        }

        if (\is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}
