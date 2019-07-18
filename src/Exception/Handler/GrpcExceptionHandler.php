<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\GrpcServer\Exception\Handler;

use Google\Protobuf\Internal\Message;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Server\Exception\ServerException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GrpcExceptionHandler extends ExceptionHandler
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    protected $formatter;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->formatter = $container->get(FormatterInterface::class);
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->warning($this->formatter->format($throwable));

        return $this->transferToResponse($throwable->getCode(), $throwable->getMessage(), $response);
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ServerException;
    }

    /**
     * Transfer the non-standard response content to a standard response object.
     *
     * @param int|string $code
     * @param string $message
     * @param ResponseInterface $response
     */
    protected function transferToResponse($code, $message, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withAddedHeader('Content-Type', 'application/grpc')
            ->withAddedHeader('trailer', 'grpc-status, grpc-message');

        $response->getSwooleResponse()->trailer('grpc-status', (string) $code);
        $response->getSwooleResponse()->trailer('grpc-message', (string) $message);

        return $response;
    }
}