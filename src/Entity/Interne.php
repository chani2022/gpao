<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InterneRepository")
 */
class Interne
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     *
     * @ORM\Column(type="datetime")
     */
    private $dates;

    /**
     * @Assert\NotBlank(message="Matricule obligatoire")
     * @ORM\Column(type="string", length=255)
     */
    private $matricule;

    /**
     * @Assert\NotBlank(message="Motif obligatoire")
     * @ORM\Column(type="string", length=255)
     */
    private $motifs;

    /**
     * @ORM\Column(type="datetime")
     */
    private $heuresortie;

    /**
     * @Assert\NotBlank(message="Donneur d'ordre obligatoire")
     * @ORM\Column(type="string", length=255)
     */
    private $donneurOrdre;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDates(): ?\DateTimeInterface
    {
        return $this->dates;
    }

    public function setDates(\DateTimeInterface $dates): self
    {
        $dates = \DateTime::createFromFormat("d/m/Y", $dates->format("d/m/Y"));
        $this->dates = $dates;

        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): self
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getMotifs(): ?string
    {
        return $this->motifs;
    }

    public function setMotifs(string $motifs): self
    {
        $this->motifs = $motifs;

        return $this;
    }

    public function getHeuresortie(): ?\DateTimeInterface
    {
        return $this->heuresortie;
    }

    public function setHeuresortie(\DateTimeInterface $heuresortie): self
    {
        $dates = \DateTime::createFromFormat("H:i:s", $heuresortie->format("H:i:s"));
        $dates->setTimezone(new \DateTimeZone("Indian/Antananarivo"));
        $this->heuresortie = $dates;

        return $this;
    }

    public function getDonneurOrdre(): ?string
    {
        return $this->donneurOrdre;
    }

    public function setDonneurOrdre(string $donneurOrdre): self
    {
        $this->donneurOrdre = $donneurOrdre;

        return $this;
    }
}
