<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "user.username.not_blank", normalizer: "trim")]
    private ?string $username = null;

    #[ORM\Column(type: "json", options: ["prefix" => "user.role."])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 4096)]
    private ?string $plainPassword = null;

    #[ORM\Column(length: 255)]
    private string $Email;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $FirstName;

    #[ORM\Column(length: 255)]
    private string $LastName;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $PhoneNumber;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $CreatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $UpdatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $CreatedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $UpdatedBy = null;

    #[ORM\Column]
    private string $UserStatus;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $LastLoginAt = null;

    #[ORM\Column]
    private bool $ForcePasswordChange;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $LastAction = null;

    #[ORM\Column(type: "json")]
    private ?array $UserGroups = [];

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
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
        // guarantee every user at least has ROLE_USER
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;
    }

    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $Email): self
    {
        $this->Email = $Email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->FirstName;
    }

    public function setFirstName(?string $FirstName): self
    {
        $this->FirstName = $FirstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->LastName;
    }

    public function setLastName(string $LastName): self
    {
        $this->LastName = $LastName;

        return $this;
    }

    public function __toString()
    {
        return ($this->FirstName ?? '') . ' ' . ($this->LastName ?? '');
    }

    public function getPhoneNumber(): ?string
    {
        return $this->PhoneNumber;
    }

    public function setPhoneNumber(?string $PhoneNumber): self
    {
        $this->PhoneNumber = $PhoneNumber;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->CreatedAt;
    }

    public function setCreatedAt(\DateTimeInterface $CreatedAt): self
    {
        $this->CreatedAt = $CreatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->UpdatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $UpdatedAt): self
    {
        $this->UpdatedAt = $UpdatedAt;

        return $this;
    }

    public function getCreatedBy(): ?self
    {
        return $this->CreatedBy;
    }

    public function setCreatedBy(?self $CreatedBy): self
    {
        $this->CreatedBy = $CreatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?self
    {
        return $this->UpdatedBy;
    }

    public function setUpdatedBy(?self $UpdatedBy): self
    {
        $this->UpdatedBy = $UpdatedBy;

        return $this;
    }

    public function getUserStatus(): ?string
    {
        return $this->UserStatus;
    }

    public function setUserStatus($UserStatus): self
    {
        $this->UserStatus = $UserStatus;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->LastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $LastLoginAt): self
    {
        $this->LastLoginAt = $LastLoginAt;

        return $this;
    }

    public function getForcePasswordChange(): ?bool
    {
        return $this->ForcePasswordChange;
    }

    public function setForcePasswordChange(bool $ForcePasswordChange): self
    {
        $this->ForcePasswordChange = $ForcePasswordChange;

        return $this;
    }

    public function getLastAction(): ?\DateTimeInterface
    {
        return $this->LastAction;
    }

    public function setLastAction(?\DateTimeInterface $LastAction): self
    {
        $this->LastAction = $LastAction;

        return $this;
    }

    public function getUserGroups(): ?array
    {
        return $this->UserGroups;
    }

    public function setUserGroups(array $UserGroups): self
    {
        $this->UserGroups = $UserGroups;

        return $this;
    }

}
