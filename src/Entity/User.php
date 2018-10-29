<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use App\CouchDB\CouchEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends CouchEntity implements UserInterface
{
    /**
     * @Id(strategy="ASSIGNED")
     */
    protected $_id;

    /**
     * @Field(type="string")
     */
    protected $characterName;

    /**
     * @Field(type="mixed")
     */
    protected $roles = [];

    /**
     * @ReferenceOne(targetDocument="user")
     */
    protected $parentCharacterId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $accessToken;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $refreshToken;

    public function getCharacterId(): ?string
    {
        return $this->_id;
    }

    public function setCharacterId(string $characterId): self
    {
        $this->_id = $characterId;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->characterName;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword()
    {
        // not needed for apps that do not check user passwords
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed for apps that do not check user passwords
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getParentCharacterId(): ?string
    {
        return $this->parentCharacterId;
    }

    public function setParentCharacterId(?string $parentCharacterId): self
    {
        $this->parentCharacterId = $parentCharacterId;

        return $this;
    }

    public function setCharacterName(?string $characterName): self
    {
        $this->characterName = $characterName;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }
}
