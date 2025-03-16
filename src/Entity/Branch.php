<?php

// src/Entity/Branch.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Branch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['branch:read'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank')]
    #[Groups(['branch:read'])]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Address cannot be blank')]
    #[Groups(['branch:read'])]
    private $address;

    #[ORM\OneToOne(targetEntity: User::class, mappedBy: 'branch', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['branch:read'])]
    private $director;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'branch')]
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getDirector(): ?User
    {
        return $this->director;
    }

    public function setDirector(?User $director): self
    {
        $this->director = $director;

        if ($director !== null && $this !== $director->getBranch()) {
            $director->setBranch($this);
        }

        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setBranch($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            if ($user->getBranch() === $this) {
                $user->setBranch(null);
            }
        }

        return $this;
    }
}
