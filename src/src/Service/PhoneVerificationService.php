<?php

namespace App\Service;

use App\Exception\CodeExpiredException;
use App\Exception\InvalidCodeException;
use App\Exception\TemporarilyBannedException;
use App\Infrastructure\PhoneVerificationConfig;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class PhoneVerificationService
{
    public function __construct(
        private PhoneVerificationConfig $config,
        private CacheInterface $phoneVerificatorCache,
        private RateLimiterFactory $phoneVerificationRequestCodeLimiter,
        private RateLimiterFactory $phoneVerificationVerifyLimiter,
    ) {
    }

    public function getActualCode(string $phone, callable $codeGenerator, ?callable $userNotificator = null): string
    {
        $unbannedAtTimestamp = $this->phoneVerificatorCache->get(
            key: 'verification-locked-' . $phone,
            callback: function (ItemInterface $item) {
                $item->expiresAfter(1);
                return null;
            }
        );

        if (null !== $unbannedAtTimestamp) {
            throw new TemporarilyBannedException($unbannedAtTimestamp - new \DateTimeImmutable()->getTimestamp());
        }

        return $this->phoneVerificatorCache->get(
            key: 'verification-' . $phone,
            callback: function (ItemInterface $item) use ($codeGenerator, $userNotificator, $phone) {
                $limiter = $this->phoneVerificationRequestCodeLimiter->create($phone);
                if (!$limiter->consume()->isAccepted()) {
                    $unbannedAt = new \DateTime()->add(\DateInterval::createFromDateString($this->config->banTime));

                    $this->phoneVerificatorCache->delete('verification-locked-' . $phone);
                    $this->phoneVerificatorCache->get(
                        key: 'verification-locked-' . $phone,
                        callback: function (ItemInterface $item) use ($unbannedAt) {
                            $item->expiresAfter($unbannedAt->getTimestamp() - new \DateTimeImmutable()->getTimestamp());
                            return $unbannedAt->getTimestamp();
                        }
                    );

                    throw new TemporarilyBannedException(
                        $unbannedAt->getTimestamp() - new \DateTimeImmutable()->getTimestamp()
                    );
                }

                $item->expiresAfter($this->config->codeLifetime);

                $code = $codeGenerator();

                if (is_callable($userNotificator)) {
                    $userNotificator($phone, $code);
                }

                return $code;
            }
        );
    }

    public function ensureVerified(string $phone, string $code): void
    {
        $limiter = $this->phoneVerificationVerifyLimiter->create($phone);
        $limiter->consume()->ensureAccepted();

        $key = 'verification-' . $phone;
        if (null === $actualCode = $this->phoneVerificatorCache->get(key: $key, callback: fn (ItemInterface $item) => null)) {
            $this->phoneVerificatorCache->delete($key);
            throw new CodeExpiredException();
        }

        if ($code !== (string) $actualCode) {
            throw new InvalidCodeException();
        }

        $this->phoneVerificatorCache->delete($key);
        $limiter->reset();
    }
}
