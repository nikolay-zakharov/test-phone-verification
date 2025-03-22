<?php

namespace App\Controller\API;

use App\Exception\CodeExpiredException;
use App\Exception\InvalidCodeException;
use App\Exception\TemporarilyBannedException;
use App\Request\API\PhoneVerification\RequestCodeDto;
use App\Request\API\PhoneVerification\VerifyDto;
use App\Service\PhoneVerificationService;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\Routing\Attribute\Route;

readonly class PhoneVerificationController
{
    public function __construct(private PhoneVerificationService $phoneVerificator) {}

    /**
     * @throws \Throwable
     */
    #[OA\Tag(name: 'Phone verification')]
    #[Route(path: '/api/phone-verification/request-code', methods: ['POST'])]
    public function requestCode(#[MapRequestPayload] RequestCodeDto $dto): JsonResponse
    {
        try {
            $code = $this->phoneVerificator->getActualCode(
                phone: $dto->phone,
                codeGenerator: fn (): string => (string) random_int(1000, 9999),
                userNotificator: function (string $phone, string $code) {

                    // todo: send the $code here

                },
            );
        } catch (\Throwable $t) {
            throw match (get_class($t)) {
                TemporarilyBannedException::class => new HttpException(
                    statusCode: Response::HTTP_TOO_MANY_REQUESTS,
                    message: sprintf('Banned for %d seconds', $t->getSeconds()),
                ),
                RateLimitExceededException::class => new HttpException(
                    statusCode: Response::HTTP_TOO_MANY_REQUESTS,
                    message: sprintf(
                        'Rate limit exceeded. Try after %d seconds',
                        $t->getRateLimit()->getRetryAfter()->getTimestamp() - new \DateTimeImmutable()->getTimestamp(),
                    ),
                ),
                default => $t,
            };
        }
        
        return new JsonResponse(['code' => $code]);
    }

    /**
     * @throws \Throwable
     */
    #[OA\Tag(name: 'Phone verification')]
    #[Route(path: '/api/phone-verification/verify', methods: ['POST'])]
    public function verify(#[MapRequestPayload] VerifyDto $dto, UserService $userService): JsonResponse
    {
        try {
            $this->phoneVerificator->ensureVerified(phone: $dto->phone, code: $dto->code);
        } catch (\Throwable $t) {
            throw match (get_class($t)) {
                CodeExpiredException::class => new HttpException(
                    statusCode: Response::HTTP_REQUEST_TIMEOUT,
                    message: $t->getMessage(),
                ),
                InvalidCodeException::class => new HttpException(
                    statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                    message: $t->getMessage(),
                ),
                RateLimitExceededException::class => new HttpException(
                    statusCode: Response::HTTP_TOO_MANY_REQUESTS,
                    message: sprintf(
                        'Rate limit exceeded. Try after %d seconds',
                        $t->getRateLimit()->getRetryAfter()->getTimestamp() - new \DateTimeImmutable()->getTimestamp(),
                    ),
                ),
                default => $t,
            };
        }

        if ($user = $userService->findByPhone($dto->phone)) {
            return new JsonResponse([
                'action' => 'Authorized',
                'user_id' => $user->getId(),
            ]);
        }

        return new JsonResponse([
            'action' => 'Registered',
            'user_id' => $userService->createWithPhone($dto->phone)->getId(),
        ]);
    }
}
