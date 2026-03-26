<?php
namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: 'admin')]
class Admin implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private ?string $passwordHash = null;

    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }
    public function getUserIdentifier(): string { return (string) $this->username; }
    public function getRoles(): array { return ['ROLE_ADMIN']; }
    public function getPassword(): string { return $this->passwordHash; }
    public function getPasswordHash(): ?string { return $this->passwordHash; }
    public function setPasswordHash(string $passwordHash): static { $this->passwordHash = $passwordHash; return $this; }
    public function eraseCredentials(): void {}
}