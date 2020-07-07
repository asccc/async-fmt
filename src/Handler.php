<?php

declare(strict_types=1);

/*
 * This file is part of the DevCom Async Code Formatter
 * (c) The dev-community.de authors
 */

namespace DevCom\Fmt;

use function Amp\call;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use DevCom\Fmt\Parser\Parser;
use DevCom\Fmt\Parser\Token;
use Traversable;

final class Handler implements RequestHandler
{
    /** default headers  */
    private const HEADERS = [
        'content-type' => 'text/plain; charset=utf-8',
    ];

    /** @var Parser */
    private $parser;

    /**
     * constructor.
     *
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request
     *
     * @return Promise
     */
    public function handleRequest(Request $request): Promise
    {
        return call(function () use ($request) {
            if ('POST' !== $request->getMethod()) {
                return new Response(
                    Status::METHOD_NOT_ALLOWED,
                    self::HEADERS
                 );
            }

            if (!$this->authRequest($request)) {
                return new Response(
                    Status::UNAUTHORIZED,
                    self::HEADERS
                );
            }

            $buffer = yield $request->getBody()->buffer();
            $result = '';

            foreach ($this->parseBuffer($buffer) as $token) {
                $result .= $this->formatToken($token);
            }

            return new Response(
                Status::OK,
                self::HEADERS,
                $result
            );
        });
    }

    /**
     * authorizes a request.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function authRequest(Request $request): bool
    {
        $query = $request->getUri()->getQuery();
        parse_str($query, $bucket);

        if (!isset($bucket['api_key'])) {
            return false;
        }

        $apiKey = $bucket['api_key'];
        return hash_equals('aabbcc', $apiKey);
    }

    /**
     * parses a request buffer.
     *
     * @param string $buffer
     *
     * @return Traversable
     */
    private function parseBuffer(string $buffer): Traversable
    {
        return $this->parser->parseText($buffer);
    }

    /**
     * formats a token.
     *
     * @param Token $token
     *
     * @return string
     */
    private function formatToken(Token $token): string
    {
        if ($token->isText()) {
            // return body as-is
            return $token->getBody();
        }

        $lang = $token->getAttribute('lang');
        \assert(null !== $lang);

        return 'FORMAT: ' . $token->getBody();
    }
}
