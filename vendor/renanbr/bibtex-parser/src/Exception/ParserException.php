<?php

/*
 * This file is part of the BibTex Parser.
 *
 * (c) Renan de Lima Barbosa <renandelima@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RenanBr\BibTexParser\Exception;

use Exception;

class ParserException extends Exception implements ExceptionInterface
{
    /**
     * @param string $character
     * @param int    $line
     * @param int    $column
     */
    public static function unexpectedCharacter($character, $line, $column)
    {
        // Avoid var_export() weird treatment for \0
        $character = "\0" === $character ? "'\\0'" : var_export($character, true);

        return new self(sprintf(
            'Unexpected character %s at line %d column %d',
            $character,
            $line,
            $column
        ));
    }
}
