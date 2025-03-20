<?php

namespace App\Controller\API;

use App\Exception\MethodLockedException;
use App\Request\API\PhoneVerification\RequestCodeDto;
use App\Request\API\PhoneVerification\VerifyDto;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PhoneVerificationController
{
    public function __construct(private CacheInterface $redisCache)
    {
    }

    #[OA\Tag(name: 'Phone verification')]
    #[Route(path: '/api/phone-verification/request-code', methods: ['POST'])]
    public function requestCode(
        #[MapRequestPayload] RequestCodeDto $dto,
        RateLimiterFactory $phoneVerificationRequestCodeLimiter,
    ): JsonResponse
    {
        /** @var int|null $verificationLockedUntil */
        $verificationLockedUntil = $this->redisCache->get(
            key: 'verification-locked-' . $dto->phone,
            callback: function (ItemInterface $item) {
                $item->expiresAfter(1);
                return null;
            }
        );
        if (null !== $verificationLockedUntil) {
            throw new MethodLockedException(
                $verificationLockedUntil - new \DateTimeImmutable()->getTimestamp()
            );
        }

        $code = $this->redisCache->get(
            key: 'verification-' . $dto->phone,
            callback: function (ItemInterface $item) use ($dto, $phoneVerificationRequestCodeLimiter) {
                $limiter = $phoneVerificationRequestCodeLimiter->create($dto->phone);
                if (!$limiter->consume()->isAccepted()) {
                    $lockedUntil = new \DateTime()->add(\DateInterval::createFromDateString('1 hour'));
                    $this->redisCache->delete('verification-locked-' . $dto->phone);
                    $this->redisCache->get(
                        key: 'verification-locked-' . $dto->phone,
                        callback: function (ItemInterface $item) use ($lockedUntil) {
                            $item->expiresAfter($lockedUntil->getTimestamp() - new \DateTimeImmutable()->getTimestamp());
                            return $lockedUntil->getTimestamp();
                        }
                    );
                    throw new MethodLockedException(
                        $lockedUntil->getTimestamp() - new \DateTimeImmutable()->getTimestamp()
                    );
                }

                $item->expiresAfter(2);
                $code = random_int(1000, 9999);

                // todo: send SMS here (queue it)

                return $code;
            }
        );

        return new JsonResponse(['code' => $code]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[OA\Tag(name: 'Phone verification')]
    #[Route(path: '/api/phone-verification/verify', methods: ['POST'])]
    public function verify(
        #[MapRequestPayload] VerifyDto $dto,
        RateLimiterFactory $phoneVerificationVerifyLimiter,
        UserService $userService,
    ): JsonResponse
    {
        $limiter = $phoneVerificationVerifyLimiter->create($dto->phone);
        $limiter->consume()->ensureAccepted();

        $key = 'verification-' . $dto->phone;
        if (null === $code = $this->redisCache->get(key: $key, callback: fn (ItemInterface $item) => null)) {
            $this->redisCache->delete($key);
            throw new HttpException(statusCode: 408, message: 'Verification code expired. Request another one');
        }

        if ($code !== $dto->code) {
            throw new HttpException(statusCode: 422, message: 'Invalid code');
        }

        $this->redisCache->delete($key);
        $limiter->reset();

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