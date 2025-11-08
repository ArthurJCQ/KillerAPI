<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Domain\User\Entity\User;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use League\OAuth2\Client\Provider\AppleResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/oauth', format: 'json')]
class OAuthController extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly UserRepository $userRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly JWTTokenManagerInterface $tokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
    ) {
    }

    #[Route('/google', name: 'oauth_google_start', methods: [Request::METHOD_GET])]
    public function connectGoogle(): RedirectResponse
    {
        return $this->clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/google/check', name: 'oauth_google_check', methods: [Request::METHOD_GET])]
    public function checkGoogle(): JsonResponse
    {
        $client = $this->clientRegistry->getClient('google');

        try {
            /** @var GoogleUser $googleUser */
            $googleUser = $client->fetchUser();

            $googleId = $googleUser->getId();
            $email = $googleUser->getEmail();
            $name = $googleUser->getName();

            // Find or create user
            $user = $this->userRepository->findByGoogleId($googleId);

            if (!$user) {
                // Check if user exists by email
                if ($email) {
                    $user = $this->userRepository->findByEmail($email);
                }

                if (!$user) {
                    $user = new User();
                    $user->setDefaultName($name ?? 'User');
                    $user->setEmail($email);
                }

                $user->setGoogleId($googleId);
                $this->userRepository->store($user);
                $this->persistenceAdapter->flush();
            }

            // Generate JWT tokens
            $jwtToken = $this->tokenManager->create($user);
            $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, 15552000); // 180 days
            $this->refreshTokenManager->save($refreshToken);

            return $this->json([
                'token' => $jwtToken,
                'refresh_token' => $refreshToken->getRefreshToken(),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'defaultName' => $user->getDefaultName(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Authentication failed: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/apple', name: 'oauth_apple_start', methods: [Request::METHOD_GET])]
    public function connectApple(): RedirectResponse
    {
        return $this->clientRegistry
            ->getClient('apple')
            ->redirect(['name', 'email'], []);
    }

    #[Route('/apple/check', name: 'oauth_apple_check', methods: [Request::METHOD_GET])]
    public function checkApple(): JsonResponse
    {
        $client = $this->clientRegistry->getClient('apple');

        try {
            /** @var AppleResourceOwner $appleUser */
            $appleUser = $client->fetchUser();

            $appleId = $appleUser->getId();
            $email = $appleUser->getEmail();
            $firstName = $appleUser->getFirstName();
            $lastName = $appleUser->getLastName();
            $name = trim(($firstName ?? '') . ' ' . ($lastName ?? '')) ?: 'User';

            // Find or create user
            $user = $this->userRepository->findByAppleId($appleId);

            if (!$user) {
                // Check if user exists by email
                if ($email) {
                    $user = $this->userRepository->findByEmail($email);
                }

                if (!$user) {
                    $user = new User();
                    $user->setDefaultName($name);
                    $user->setEmail($email);
                }

                $user->setAppleId($appleId);
                $this->userRepository->store($user);
                $this->persistenceAdapter->flush();
            }

            // Generate JWT tokens
            $jwtToken = $this->tokenManager->create($user);
            $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, 15552000); // 180 days
            $this->refreshTokenManager->save($refreshToken);

            return $this->json([
                'token' => $jwtToken,
                'refresh_token' => $refreshToken->getRefreshToken(),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'defaultName' => $user->getDefaultName(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Authentication failed: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }
}
