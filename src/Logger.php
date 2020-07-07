<?php

declare(strict_types=1);

/*
 * This file is part of the DevCom Async Code Formatter
 * (c) The dev-community.de authors
 */

namespace DevCom\Fmt;

use Psr\Log\AbstractLogger;

final class Logger extends AbstractLogger
{
    /**
     * @override
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        echo '(', $level, '): ', $message, PHP_EOL;
    }
}
