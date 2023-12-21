<?php

/*
 * This file is part of the BibTex Parser.
 *
 * (c) Renan de Lima Barbosa <renandelima@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @param array  $context contains details of the unit found
     */
    public function bibTexUnitFound($text, $type, array $context);
}
