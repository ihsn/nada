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

use RenanBr\BibTexParser\Exception\ProcessorException;

/**
 * Splits names in four parts: First Von Last Jr.
 *
 * This class includes source code adapted from the Structures_BibTex package,
 * (c) Elmar Pitschke <elmar.pitschke@gmx.de>, included here under PHP license:
 * http://www.php.net/license/3_0.txt
 *
 * @author Andre Chalom <andrechalom@gmail.com>
 *
 * @see https://github.com/pear/Structures_BibTex
 */
class NamesProcessor
{
    use TagCoverageTrait;

    public function __construct()
    {
        $this->setTagCoverage(['author', 'editor']);
    }

    /**
     * @return array
     */
    public function __invoke(array $entry)
    {
        $covered = $this->getCoveredTags(array_keys($entry));
        foreach ($covered as $tag) {
            $entry[$tag] = $this->extractAuthors($entry[$tag]);
        }

        return $entry;
    }

    /**
     * Extracting the authors.
     *
     * @param string $entry The entry with the authors
     *
     * @return array the extracted authors
     *
     * @author Elmar Pitschke <elmar.pitschke@gmx.de>
     */
    private function extractAuthors($entry)
    {
        // Sanitizes the entry to remove unwanted whitespace
        $entry = trim(preg_replace('/\s+/', ' ', $entry));

        $authorarray = [];
        $authorarray = explode(' and ', $entry);
        for ($i = 0; $i < \count($authorarray); ++$i) {
            $author = trim($authorarray[$i]);
            /*The first version of how an author could be written (First von Last)
            has no commas in it*/
            $first = '';
            $von = '';
            $last = '';
            $jr = '';
            if (false === mb_strpos($author, ',')) {
                $tmparray = [];
                $tmparray = preg_split('/[\s\~]/', $author);
                $size = \count($tmparray);
                if (1 === $size) { //There is only a last
                    $last = $tmparray[0];
                } elseif (2 === $size) { //There is a first and a last
                    $first = $tmparray[0];
                    $last = $tmparray[1];
                } else {
                    $invon = false;
                    $inlast = false;
                    for ($j = 0; $j < ($size - 1); ++$j) {
                        if ($inlast) {
                            $last .= ' '.$tmparray[$j];
                        } elseif ($invon) {
                            try {
                                $case = $this->determineCase($tmparray[$j]);

                                if ((0 === $case) || (-1 === $case)) { //Change from von to last
                                    //You only change when there is no more lower case there
                                    $islast = true;
                                    for ($k = ($j + 1); $k < ($size - 1); ++$k) {
                                        try {
                                            $futurecase = $this->determineCase($tmparray[$k]);
                                            if (0 === $futurecase) {
                                                $islast = false;
                                            }
                                        } catch (ProcessorException $sbe) {
                                            // Ignore
                                        }
                                    }
                                    if ($islast) {
                                        $inlast = true;
                                        if (-1 === $case) { //Caseless belongs to the last
                                            $last .= ' '.$tmparray[$j];
                                        } else {
                                            $von .= ' '.$tmparray[$j];
                                        }
                                    } else {
                                        $von .= ' '.$tmparray[$j];
                                    }
                                } else {
                                    $von .= ' '.$tmparray[$j];
                                }
                            } catch (ProcessorException $sbe) {
                                // Ignore
                            }
                        } else {
                            try {
                                $case = $this->determineCase($tmparray[$j]);
                                if (0 === $case) { //Change from first to von
                                    $invon = true;
                                    $von .= ' '.$tmparray[$j];
                                } else {
                                    $first .= ' '.$tmparray[$j];
                                }
                            } catch (ProcessorException $sbe) {
                                // Ignore
                            }
                        }
                    }
                    //The last entry is always the last!
                    $last .= ' '.$tmparray[$size - 1];
                }
            } else { //Version 2 and 3
                $tmparray = [];
                $tmparray = explode(',', $author);
                //The first entry must contain von and last
                $vonlastarray = [];
                $vonlastarray = explode(' ', $tmparray[0]);
                $size = \count($vonlastarray);
                if (1 === $size) { //Only one entry->got to be the last
                    $last = $vonlastarray[0];
                } else {
                    $inlast = false;
                    for ($j = 0; $j < ($size - 1); ++$j) {
                        if ($inlast) {
                            $last .= ' '.$vonlastarray[$j];
                        } else {
                            if (0 !== ($this->determineCase($vonlastarray[$j]))) { //Change from von to last
                                $islast = true;
                                for ($k = ($j + 1); $k < ($size - 1); ++$k) {
                                    try {
                                        $case = $this->determineCase($vonlastarray[$k]);
                                        if (0 === $case) {
                                            $islast = false;
                                        }
                                    } catch (ProcessorException $sbe) {
                                        // Ignore
                                    }
                                }
                                if ($islast) {
                                    $inlast = true;
                                    $last .= ' '.$vonlastarray[$j];
                                } else {
                                    $von .= ' '.$vonlastarray[$j];
                                }
                            } else {
                                $von .= ' '.$vonlastarray[$j];
                            }
                        }
                    }
                    $last .= ' '.$vonlastarray[$size - 1];
                }
                //Now we check if it is version three (three entries in the array (two commas)
                if (3 === \count($tmparray)) {
                    $jr = $tmparray[1];
                }
                //Everything in the last entry is first
                $first = $tmparray[\count($tmparray) - 1];
            }
            $authorarray[$i] = ['first' => trim($first), 'von' => trim($von), 'last' => trim($last), 'jr' => trim($jr)];
        }

        return $authorarray;
    }

    /**
     * Case Determination according to the needs of BibTex.
     *
     * To parse the Author(s) correctly a determination is needed
     * to get the Case of a word. There are three possible values:
     * - Upper Case (return value 1)
     * - Lower Case (return value 0)
     * - Caseless   (return value -1)
     *
     * @param string $word
     *
     * @throws ProcessorException
     *
     * @return int The Case
     *
     * @author Elmar Pitschke <elmar.pitschke@gmx.de>
     */
    private function determineCase($word)
    {
        $ret = -1;
        $trimmedword = trim($word);
        /*We need this variable. Without the next of would not work
        (trim changes the variable automatically to a string!)*/
        if (\is_string($word) && (mb_strlen($trimmedword) > 0)) {
            $i = 0;
            $found = false;
            $openbrace = 0;
            while (!$found && ($i <= mb_strlen($word))) {
                $letter = mb_substr($trimmedword, $i, 1);
                $ord = \ord($letter);
                if (123 === $ord) { //Open brace
                    ++$openbrace;
                }
                if (125 === $ord) { //Closing brace
                    --$openbrace;
                }
                if (($ord >= 65) && ($ord <= 90) && (0 === $openbrace)) { //The first character is uppercase
                    $ret = 1;
                    $found = true;
                } elseif (($ord >= 97) && ($ord <= 122) && (0 === $openbrace)) { //The first character is lowercase
                    $ret = 0;
                    $found = true;
                } else { //Not yet found
                    ++$i;
                }
            }
        } else {
            throw new ProcessorException('Could not determine case on word: '.$word);
        }

        return $ret;
    }
}
