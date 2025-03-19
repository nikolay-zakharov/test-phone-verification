<?php

namespace App\Entity;

use App\Repository\PhoneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhoneRepository::class)]
class Phone
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 12, unique: true)]
    private ?string $value = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verified_at = null;

    #[ORM\ManyToOne(inversedBy: 'phones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verified_at;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verified_at): static
    {
        $this->verified_at = $verified_at;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
