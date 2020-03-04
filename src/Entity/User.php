<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $end_subscription;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $payer_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $profile_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials()
    {
        
    }
    public function getSalt()
    {
        
    }

    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    public function getEndSubscription(): ?\DateTimeInterface
    {
        return $this->end_subscription;
    }

    public function setEndSubscription(?\DateTimeInterface $end_subscription): self
    {
        $this->end_subscription = $end_subscription;

        return $this;
    }

    public function getPayerId(): ?string
    {
        return $this->payer_id;
    }

    public function setPayerId(string $payer_id): self
    {
        $this->payer_id = $payer_id;

        return $this;
    }

    public function getProfileId(): ?string
    {
        return $this->profile_id;
    }

    public function setProfileId(string $profile_id): self
    {
        $this->profile_id = $profile_id;

        return $this;
    }
}
