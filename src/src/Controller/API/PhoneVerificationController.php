<?php

namespace App\Controller\API;

use App\Exception\CodeExpiredException;
use App\Exception\InvalidCodeException;
use App\Request\API\PhoneVerification\RequestCodeDto;
use App\Request\API\PhoneVerification\VerifyDto;
use App\Service\PhoneValidationService;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

readonly class PhoneVerificationController
{
    public function __construct(private PhoneValidationService $phoneValidator) {}

    #[OA\Tag(name: 'Phone verification')]
    #[Route(path: '/api/phone-verification/request-code', methods: ['POST'])]
    public function requestCode(#[MapRequestPayload] RequestCodeDto $dto): JsonResponse
    {
        $code = $this->phoneValidator->getActualCode(
            phone: $dto->phone,
            codeGenerator: fn () => random_int(1000, 9999),
            userNotificator: function ($phone, $code) {

                // todo: send the $code here

            },
        );

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
            $this->phoneValidator->ensureValidated(
                phone: $dto->phone,
                code: $dto->code,
            );
        } catch (\Throwable $t) {
            throw match (get_class($t)) {
                CodeExpiredException::class => new HttpException(408, $t->getMessage()),
                InvalidCodeException::class => new HttpException(422, $t->getMessage()),
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
