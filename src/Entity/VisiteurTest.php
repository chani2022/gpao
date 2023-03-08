<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VisiteurTestRepository")
 */
class VisiteurTest
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
    private $description;

    /**
     * @ORM\Column(type="time")
     */
    private $heure_entre;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getHeureEntre(): ?\DateTimeInterface
    {
        return $this->heure_entre;
    }

    public function setHeureEntre(\DateTimeInterface $heure_entre): self
    {
        $this->heure_entre = $heure_entre;

        return $this;
    }
}
