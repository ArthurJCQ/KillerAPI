<?php

declare(strict_types=1);

namespace App\Api\Controller;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health', format: 'json')]
class HealthController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
    }

    #[Route('/', name: 'health', methods: [Request::METHOD_GET])]
    public function health(): JsonResponse
    {
        return $this->json('OK', Response::HTTP_OK);
    }

    #[Route('/test-log', name: 'test_log', methods: [Request::METHOD_GET])]
    public function testLog(): JsonResponse
    {
        $this->logger->error('Test log');

        return $this->json(null, Response::HTTP_OK);
    }
}
