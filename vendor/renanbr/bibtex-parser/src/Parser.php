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

use ErrorException;
use RenanBr\BibTexParser\Exception\ParserException;

class Parser
{
    const TYPE = 'type';
    const CITATION_KEY = 'citation_key';
    const TAG_NAME = 'tag_name';
    const RAW_TAG_CONTENT = 'raw_tag_content';
    const BRACED_TAG_CONTENT = 'braced_tag_content';
    const QUOTED_TAG_CONTENT = 'quoted_tag_content';
    const ENTRY = 'entry';

    const NONE = 'none';
    const COMMENT = 'comment';
    const FIRST_TAG_NAME = 'first_tag_name';
    const POST_TYPE = 'post_type';
    const POST_TAG_NAME = 'post_tag_name';
    const PRE_TAG_CONTENT = 'pre_tag_content';

    /** @var string */
    private $state;

    /** @var string */
    private $buffer;

    /** @var int|null */
    private $bufferOffset;

    /** @var array|null */
    private $firstTagSnapshot;

    /** @var string|null */
    private $originalEntryBuffer;

    /** @var int|null */
    private $originalEntryOffset;

    /** @var bool */
    private $skipOriginalEntryReading;

    /** @var int */
    private $line;

    /** @var int */
    private $column;

    /** @var int */
    private $offset;

    /** @var bool */
    private $isTagContentEscaped;

    /** @var bool */
    private $mayConcatenateTagContent;

    /** @var string|null */
    private $tagContentDelimiter;

    /** @var int */
    private $braceLevel;

    /** @var ListenerInterface[] */
    private $listeners = [];

    public function addListener(ListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * @param string $file
     *
     * @throws ParserException if $file given is not a valid BibTeX
     * @throws ErrorException  if $file given is not readable
     */
    public function parseFile($file)
    {
        $handle = @fopen($file, 'r');
        if (!$handle) {
            throw new ErrorException(sprintf('Unable to open %s', $file));
        }
        try {
            $this->reset();
            while (!feof($handle)) {
                $buffer = fread($handle, 128);
                $this->parse($buffer);
            }
            $this->throwExceptionIfReadingEntry("\0");
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param string $string
     *
     * @throws ParserException if $string given is not a valid BibTeX
     */
    public function parseString($string)
    {
        $this->reset();
        $this->parse($string);
        $this->throwExceptionIfReadingEntry("\0");
    }

    /**
     * @param string $text
     */
    private function parse($text)
    {
        $length = mb_strlen($text);
        for ($position = 0; $position < $length; ++$position) {
            $char = mb_substr($text, $position, 1);
            $this->read($char);
            if ("\n" === $char) {
                ++$this->line;
                $this->column = 1;
            } else {
                ++$this->column;
            }
            ++$this->offset;
        }
    }

    private function reset()
    {
        $this->state = self::NONE;
        $this->buffer = '';
        $this->firstTagSnapshot = null;
        $this->originalEntryBuffer = null;
        $this->originalEntryOffset = null;
        $this->skipOriginalEntryReading = false;
        $this->line = 1;
        $this->column = 1;
        $this->offset = 0;
        $this->mayConcatenateTagContent = false;
        $this->isTagContentEscaped = false;
        $this->tagContentDelimiter = null;
        $this->braceLevel = 0;
    }

    // ----- Readers -----------------------------------------------------------

    /**
     * @param string $char
     */
    private function read($char)
    {
        $previousState = $this->state;

        switch ($this->state) {
            case self::NONE:
                $this->readNone($char);
                break;
            case self::COMMENT:
                $this->readComment($char);
                break;
            case self::TYPE:
                $this->readType($char);
                break;
            case self::POST_TYPE:
                $this->readPostType($char);
                break;
            case self::FIRST_TAG_NAME:
            case self::TAG_NAME:
                $this->readTagName($char);
                break;
            case self::POST_TAG_NAME:
                $this->readPostTagName($char);
                break;
            case self::PRE_TAG_CONTENT:
                $this->readPreTagContent($char);
                break;
            case self::RAW_TAG_CONTENT:
                $this->readRawTagContent($char);
                break;
            case self::QUOTED_TAG_CONTENT:
            case self::BRACED_TAG_CONTENT:
                $this->readDelimitedTagContent($char);
                break;
        }

        $this->readOriginalEntry($char, $previousState);
    }

    /**
     * @param string $char
     */
    private function readNone($char)
    {
        if ('@' === $char) {
            $this->state = self::TYPE;
        } elseif (!$this->isWhitespace($char)) {
            $this->state = self::COMMENT;
        }
    }

    /**
     * @param string $char
     */
    private function readComment($char)
    {
        if ($this->isWhitespace($char)) {
            $this->state = self::NONE;
        }
    }

    /**
     * @param string $char
     */
    private function readType($char)
    {
        if (preg_match('/^[a-zA-Z]$/', $char)) {
            $this->appendToBuffer($char);
        } else {
            $this->throwExceptionIfBufferIsEmpty($char);

            // Skips @comment type
            if ('comment' === mb_strtolower($this->buffer)) {
                $this->skipOriginalEntryReading = true;
                $this->buffer = '';
                $this->bufferOffset = null;
                $this->state = self::COMMENT;
                $this->readComment($char);

                return;
            }

            $this->triggerListenersWithCurrentBuffer();

            // once $char isn't a valid character
            // it must be interpreted as POST_TYPE
            $this->state = self::POST_TYPE;
            $this->readPostType($char);
        }
    }

    /**
     * @param string $char
     */
    private function readPostType($char)
    {
        if ('{' === $char) {
            $this->state = self::FIRST_TAG_NAME;
        } elseif (!$this->isWhitespace($char)) {
            throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
        }
    }

    /**
     * @param string $char
     */
    private function readTagName($char)
    {
        if (preg_match('/^[a-zA-Z0-9_\+:\-\.\/\x{00C0}-\x{01FF}]$/u', $char)) {
            $this->appendToBuffer($char);
        } elseif ($this->isWhitespace($char) && empty($this->buffer)) {
            // Skips because we didn't start reading
        } elseif ('}' === $char && empty($this->buffer)) {
            // No tag name found, $char is just closing current entry
            $this->state = self::NONE;
        } else {
            $this->throwExceptionIfBufferIsEmpty($char);

            if (self::FIRST_TAG_NAME === $this->state) {
                // Takes a snapshot of current state to be triggered later as
                // tag name or citation key, see readPostTagName()
                $this->firstTagSnapshot = $this->takeBufferSnapshot();
            } else {
                // Current buffer is a simple tag name
                $this->triggerListenersWithCurrentBuffer();
            }

            // Once $char isn't a valid tag name character, it must be
            // interpreted as post tag name
            $this->state = self::POST_TAG_NAME;
            $this->readPostTagName($char);
        }
    }

    /**
     * @param string $char
     */
    private function readPostTagName($char)
    {
        if ('=' === $char) {
            // First tag name isn't a citation key, because it has content
            $this->triggerListenersWithFirstTagSnapshotAs(self::TAG_NAME);
            $this->state = self::PRE_TAG_CONTENT;
        } elseif ('}' === $char) {
            // First tag name is a citation key, because $char closes entry and
            // lets first tag without value
            $this->triggerListenersWithFirstTagSnapshotAs(self::CITATION_KEY);
            $this->state = self::NONE;
        } elseif (',' === $char) {
            // First tag name is a citation key, because $char moves to the next
            // tag and lets first tag without value
            $this->triggerListenersWithFirstTagSnapshotAs(self::CITATION_KEY);
            $this->state = self::TAG_NAME;
        } elseif (!$this->isWhitespace($char)) {
            throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
        }
    }

    /**
     * @param string $char
     */
    private function readPreTagContent($char)
    {
        if (preg_match('/^[a-zA-Z0-9]$/', $char)) {
            // When concatenation is available it means there is already a
            // defined value, and parser expect a concatenator, a tag separator
            // or an entry closing char as next $char
            $this->throwExceptionAccordingToConcatenationAvailability($char, true);
            $this->state = self::RAW_TAG_CONTENT;
            $this->readRawTagContent($char);
        } elseif ('"' === $char) {
            // The exception is here for the same reason of the first case
            $this->throwExceptionAccordingToConcatenationAvailability($char, true);
            $this->tagContentDelimiter = '"';
            $this->state = self::QUOTED_TAG_CONTENT;
        } elseif ('{' === $char) {
            // The exception is here for the same reason of the first case
            $this->throwExceptionAccordingToConcatenationAvailability($char, true);
            $this->tagContentDelimiter = '}';
            $this->state = self::BRACED_TAG_CONTENT;
        } elseif ('#' === $char) {
            $this->throwExceptionAccordingToConcatenationAvailability($char, false);
            $this->mayConcatenateTagContent = false;
        } elseif (',' === $char) {
            $this->throwExceptionAccordingToConcatenationAvailability($char, false);
            $this->mayConcatenateTagContent = false;
            $this->state = self::TAG_NAME;
        } elseif ('}' === $char) {
            $this->throwExceptionAccordingToConcatenationAvailability($char, false);
            $this->mayConcatenateTagContent = false;
            $this->state = self::NONE;
        } elseif (!$this->isWhitespace($char)) {
            throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
        }
    }

    /**
     * @param string $char
     */
    private function readRawTagContent($char)
    {
        if (preg_match('/^[a-zA-Z0-9_\+:\-\.\/]$/', $char)) {
            $this->appendToBuffer($char);
        } else {
            $this->throwExceptionIfBufferIsEmpty($char);
            $this->triggerListenersWithCurrentBuffer();

            // once $char isn't a valid character
            // it must be interpreted as TAG_CONTENT
            $this->mayConcatenateTagContent = true;
            $this->state = self::PRE_TAG_CONTENT;
            $this->readPreTagContent($char);
        }
    }

    /**
     * @param string $char
     */
    private function readDelimitedTagContent($char)
    {
        if ($this->isTagContentEscaped) {
            $this->isTagContentEscaped = false;
            if ($this->tagContentDelimiter !== $char && '\\' !== $char && '%' !== $char) {
                $this->appendToBuffer('\\');
            }
            $this->appendToBuffer($char);
        } elseif ('}' === $this->tagContentDelimiter && '{' === $char) {
            ++$this->braceLevel;
            $this->appendToBuffer($char);
        } elseif ($this->tagContentDelimiter === $char) {
            if (0 === $this->braceLevel) {
                $this->triggerListenersWithCurrentBuffer();
                $this->mayConcatenateTagContent = true;
                $this->state = self::PRE_TAG_CONTENT;
            } else {
                --$this->braceLevel;
                $this->appendToBuffer($char);
            }
        } elseif ('\\' === $char) {
            $this->isTagContentEscaped = true;
        } else {
            $this->appendToBuffer($char);
        }
    }

    /**
     * @param string $char
     * @param string $previousState
     */
    private function readOriginalEntry($char, $previousState)
    {
        if ($this->skipOriginalEntryReading) {
            $this->originalEntryBuffer = '';
            $this->originalEntryOffset = null;
            $this->skipOriginalEntryReading = false;

            return;
        }

        // Checks whether we are reading an entry character or not
        $isPreviousStateEntry = $this->isEntryState($previousState);
        $isCurrentStateEntry = $this->isEntryState($this->state);
        $isEntry = $isPreviousStateEntry || $isCurrentStateEntry;
        if (!$isEntry) {
            return;
        }

        // Appends $char to the original entry buffer
        if (empty($this->originalEntryBuffer)) {
            $this->originalEntryOffset = $this->offset;
        }
        $this->originalEntryBuffer .= $char;

        // Sends original entry to the listeners when $char closes an entry
        $isClosingEntry = $isPreviousStateEntry && !$isCurrentStateEntry;
        if ($isClosingEntry) {
            $this->triggerListeners($this->originalEntryBuffer, self::ENTRY, [
                'offset' => $this->originalEntryOffset,
                'length' => $this->offset - $this->originalEntryOffset + 1,
            ]);
            $this->originalEntryBuffer = '';
            $this->originalEntryOffset = null;
        }
    }

    // ----- Listener triggers -------------------------------------------------

    /**
     * @param string $text
     * @param string $type
     */
    private function triggerListeners($text, $type, array $context)
    {
        foreach ($this->listeners as $listener) {
            $listener->bibTexUnitFound($text, $type, $context);
        }
    }

    private function triggerListenersWithCurrentBuffer()
    {
        $snapshot = $this->takeBufferSnapshot();
        $text = $snapshot['text'];
        $context = $snapshot['context'];
        $this->triggerListeners($text, $this->state, $context);
    }

    /**
     * @param string $type
     */
    private function triggerListenersWithFirstTagSnapshotAs($type)
    {
        if (empty($this->firstTagSnapshot)) {
            return;
        }
        $text = $this->firstTagSnapshot['text'];
        $context = $this->firstTagSnapshot['context'];
        $this->firstTagSnapshot = null;
        $this->triggerListeners($text, $type, $context);
    }

    // ----- Buffer tools ------------------------------------------------------

    /**
     * @param string $char
     */
    private function appendToBuffer($char)
    {
        if (empty($this->buffer)) {
            $this->bufferOffset = $this->offset;
        }
        $this->buffer .= $char;
    }

    /**
     * @return array
     */
    private function takeBufferSnapshot()
    {
        $snapshot = [
            'text' => $this->buffer,
            'context' => [
                'offset' => $this->bufferOffset,
                'length' => $this->offset - $this->bufferOffset,
            ],
        ];
        $this->bufferOffset = null;
        $this->buffer = '';

        return $snapshot;
    }

    // ----- Exception throwers ------------------------------------------------

    /**
     * @param string $char
     * @param bool   $availability
     */
    private function throwExceptionAccordingToConcatenationAvailability($char, $availability)
    {
        if ($availability === $this->mayConcatenateTagContent) {
            throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
        }
    }

    /**
     * @param string $char
     */
    private function throwExceptionIfBufferIsEmpty($char)
    {
        if (empty($this->buffer)) {
            throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
        }
    }

    /**
     * @param string $char
     */
    private function throwExceptionIfReadingEntry($char)
    {
        if ($this->isEntryState($this->state)) {
            throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
        }
    }

    // ----- Auxiliaries -------------------------------------------------------

    /**
     * @param string $state
     *
     * @return bool
     */
    private function isEntryState($state)
    {
        return self::NONE !== $state && self::COMMENT !== $state;
    }

    /**
     * @param string $char
     *
     * @return bool
     */
    private function isWhitespace($char)
    {
        return ' ' === $char || "\t" === $char || "\n" === $char || "\r" === $char;
    }
}
