<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="organization_member",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="user_organization", columns={"user_id", "organization_id"})}
 * )
 */
class Member
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization", inversedBy="members")
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private string $role;

    public function __construct(UuidInterface $id, User $user, Organization $organization, string $role)
    {
        if (!in_array($role, self::availableRoles(), true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported role: %s', $role));
        }

        $this->id = $id;
        $this->user = $user;
        $this->organization = $organization;
        $this->role = $role;
    }

    public function email(): string
    {
        return $this->user->getEmail();
    }

    /**
     * @return array<int,string>
     */
    public static function availableRoles(): array
    {
        return [
            self::ROLE_MEMBER,
            self::ROLE_ADMIN,
        ];
    }
}
