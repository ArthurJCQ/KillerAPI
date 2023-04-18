<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Cookie;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Cookie\JWTCookieProvider;
use Symfony\Component\HttpFoundation\Cookie;

class CookieProvider
{
    public static function getJwtCookie(
        array $claim,
        #[\SensitiveParameter] string $tokenSecret,
        string $cookieName,
        ?string $expiresAt,
        ?string $sameSite,
        ?string $path,
        ?string $domain,
        ?bool $secure = null,
        bool $httpOnly = false,
    ): Cookie {
        if ($tokenSecret === '') {
            throw new \LogicException('Token secret should not be null');
        }

        $jwt = (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->withClaim($claim[0], $claim[1])
            ->getToken(new Sha256(), InMemory::plainText($tokenSecret));

        return (new JWTCookieProvider())->createCookie(
            $jwt->toString(),
            $cookieName,
            $expiresAt,
            $sameSite,
            $path,
            $domain,
            $secure,
            $httpOnly,
        );
    }
}
