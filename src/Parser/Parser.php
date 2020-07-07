<?php

declare(strict_types=1);

/*
 * This file is part of the DevCom Async Code Formatter
 * (c) The dev-community.de authors
 */

namespace DevCom\Fmt\Parser;

use Amp\Promise;
use Traversable;

interface Parser
{
    /**
     * parses a file.
     *
     * @param string $filePath
     *
     * @return Promise
     */
    public function parseFile(string $filePath): Promise;

    /**
     * parses a string (text).
     *
     * @param string $textData
     *
     * @return Traversable
     */
    public function parseText(string $textData): Traversable;
}
