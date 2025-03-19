<?php

namespace App\Service;

use App\Entity\Phone;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    public function findByPhone(string $number): ?User
    {
        return $this->entityManager->getRepository(Phone::class)->findOneBy(['value' => $number])?->getOwner();
    }

    public function createWithPhone(string $number): User
    {
        $user = new User();
        $this->entityManager->persist($user);

        $phone = new Phone();
        $phone->setValue($number);
        $phone->setVerifiedAt(new \DateTimeImmutable());
        $phone->setOwner($user);
        $this->entityManager->persist($phone);
        $this->entityManager->flush();

        return $user;
    }
}