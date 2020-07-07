<?php

declare(strict_types=1);

/*
 * This file is part of the DevCom Async Code Formatter
 * (c) The dev-community.de authors
 */

namespace DevCom\Fmt\Parser;

use function Amp\call;
use function Amp\File\get;
use Amp\Promise;
use Traversable;

/**
 * this parser uses pcre (regular expressions)
 * to parse incoming code.
 */
final class PregParser implements Parser
{
    /** pattern used to tokenize the text */
    private const RE_SPLIT = '/\[(?:\\/code|code(=\w+|(?:\s+\w+=(?:"[^"]*"|\S+))*))\]/i';

    /** pattern used to tokenize attributes */
    private const RE_ATTRS = '/\A\s*(\w+)=("[^"]*"|\S+)/';

    /**
     * {@inheritdoc}
     *
     * @param string $filePath
     *
     * @return Promise
     */
    public function parseFile(string $filePath): Promise
    {
        return call(function () use ($filePath) {
            $textData = yield get($filePath);
            return $this->parseText($textData);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @param string $textData
     *
     * @return Traversable
     */
    public function parseText(string $textData): Traversable
    {
        // 0 => text
        // 1 => code-tag attributes
        // 2 => code-tag body
        // 3 => text
        // 4 => ...

        $split = preg_split(
            self::RE_SPLIT, $textData, -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $count = \count($split);
        if (0 === $count) {
            return;
        }

        $index = 0;
        while ($index < $count) {
            $attrs = null;

            if (0 !== $index % 3) {
                $attrs = $this->parseAttrs($split[$index++]);
                $kind = Token::T_CODE;
            } else {
                $kind = Token::T_TEXT;
            }

            $body = $split[$index++];
            yield new Token($kind, $body, $attrs);
        }
    }

    /**
     * parses an attribute string from a code-tag.
     *
     * @param string $attrs
     *
     * @return array<string,string>|null
     */
    private function parseAttrs(string $attrs): ?array
    {
        // TODO ensure a "lang" in Token instead?
        $attrMap = ['lang' => 'text'];

        if (empty($attrs = trim($attrs))) {
            return $attrMap;
        }

        if ('=' === substr($attrs, 0, 1)) {
            // [code=lang] notation
            $attrMap['lang'] = substr($attrs, 1);
            return $attrMap;
        }

        $offset = 0;
        $length = \strlen($attrs);
        while ($offset < $length) {
            if (!preg_match(
                self::RE_ATTRS, $attrs, $m, 0,
                PREG_OFFSET_CAPTURE, $offset
            )) {
                // we could throw here, but the text-input is in most
                // cases provided by users. so we just discard it
                break;
            }

            $attrMap[$m[1][0]] = $this->parseAttr($m[2][0]);
            $offset = $m[0][1];
        }

        return $attrMap;
    }

    /**
     * parses an attribute (value).
     *
     * @param string $value
     *
     * @return string
     */
    private function parseAttr(string $value): string
    {
        switch (substr($value, 0, 1)) {
            case '"':
            case "'":
                return substr($value, 1, -1);
            default:
                return $value;
        }
    }
}
