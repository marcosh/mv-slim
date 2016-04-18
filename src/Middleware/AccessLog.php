<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr7Middlewares\Utils\AttributeTrait;
use Psr7Middlewares\Middleware\ClientIp;

class AccessLog
{
    use AttributeTrait;

    /**
     * @var LoggerInterface The router container
     */
    private $logger;

    /**
     * @var callable formats the log message. Receives in input a request and a
     * response and returns a string
     */
    private $formatter;

    /**
     * Set the LoggerInterface instance.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, callable $formatter)
    {
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!self::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('AccessLog middleware needs ClientIp executed before');
        }

        $response = $next($request, $response);
        $message = call_user_func($this->formatter, $request, $response);

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        return $response;
    }
}
