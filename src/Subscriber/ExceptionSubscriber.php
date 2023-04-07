<?php

declare(strict_types=1);

namespace App\Subscriber;

use App\Api\Exception\KillerHttpException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function logException(ExceptionEvent $exceptionEvent): void
    {
        $this->logger->critical($exceptionEvent->getThrowable());
    }

    public function exposeExceptionMessage(ExceptionEvent $exceptionEvent): void
    {
        $exception = $exceptionEvent->getThrowable();

        if ($exception instanceof KillerHttpException) {
            $exceptionEvent->setResponse(new JsonResponse(
                ['detail' => $exception->getMessage()],
                $exception->getStatusCode(),
                $exception->getHeaders(),
            ));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['logException', 0],
                ['exposeExceptionMessage', 10],
            ],
        ];
    }
}
