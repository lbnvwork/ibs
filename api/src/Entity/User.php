<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('ROLE_ADMIN') or object == user"
        ),
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ADMIN') or object == user"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_users_medpers', columns: ['medical_personnel_id'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $login = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $userName = null;

    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '[]'])]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: MedicalPersonnel::class)]
    #[ORM\JoinColumn(name: 'medical_personnel_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['user:read'])]
    private ?MedicalPersonnel $medicalPersonnel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): self
    {
        $this->login = $login;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getMedicalPersonnel(): ?MedicalPersonnel
    {
        return $this->medicalPersonnel;
    }

    public function setMedicalPersonnel(?MedicalPersonnel $medicalPersonnel): self
    {
        $this->medicalPersonnel = $medicalPersonnel;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    /**
     * @deprecated since Symfony 7.3, use __serialize() instead
     */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // Пароль не сохраняется в сессии благодаря __serialize()
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'userName' => $this->userName,
            'roles' => $this->roles,
            'medicalPersonnel' => $this->medicalPersonnel,
            'comment' => $this->comment,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->login = $data['login'] ?? null;
        $this->userName = $data['userName'] ?? null;
        $this->roles = $data['roles'] ?? [];
        $this->medicalPersonnel = $data['medicalPersonnel'] ?? null;
        $this->comment = $data['comment'] ?? null;
    }
}