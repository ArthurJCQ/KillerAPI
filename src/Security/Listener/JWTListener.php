<?php

declare(strict_types=1);

namespace App\Security\Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: JWTExpiredEvent::class, method: 'onJWTExpired')]
#[AsEventListener(event: JWTInvalidEvent::class, method: 'onJWTInvalid')]
final class JWTListener
{
    public function onJWTExpired(JWTExpiredEvent $event): void
    {
        /** @var JWTAuthenticationFailureResponse $response */
        $response = $event->getResponse();

        $response->setMessage('EXPIRED_TOKEN');
    }

    public function onJWTInvalid(JWTInvalidEvent $event): void
    {
        /** @var JWTAuthenticationFailureResponse $response */
        $response = $event->getResponse();

        $response->setMessage('INVALID_TOKEN');
    }
}
