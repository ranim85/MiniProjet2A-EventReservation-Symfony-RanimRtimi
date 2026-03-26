<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $passwordHash = null;

    /**
     * Relation vers les passkeys enregistrées pour cet utilisateur
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: WebauthnCredential::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $webauthnCredentials;

    public function __construct()
    {
        $this->webauthnCredentials = new ArrayCollection();
    }

    // ── Getters / Setters standards ────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getUsername(): ?string { return $this->username; }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->passwordHash;
    }

    public function getPasswordHash(): ?string { return $this->passwordHash; }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function eraseCredentials(): void {}

    // ── WebAuthn credentials ───────────────────────────────────────────

    public function getWebauthnCredentials(): Collection
    {
        return $this->webauthnCredentials;
    }

    public function addWebauthnCredential(WebauthnCredential $credential): static
    {
        if (!$this->webauthnCredentials->contains($credential)) {
            $this->webauthnCredentials->add($credential);
            $credential->setUser($this);
        }
        return $this;
    }

    public function removeWebauthnCredential(WebauthnCredential $credential): static
    {
        $this->webauthnCredentials->removeElement($credential);
        return $this;
    }
}