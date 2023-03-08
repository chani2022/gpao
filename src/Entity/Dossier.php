<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DossierRepository")
 * @UniqueEntity(
 *     fields={"nom_dossier"},
 *     message="Ce nom est déjà utilisé"
 * )
 */
class Dossier
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
    private $nom_dossier;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $date_ajout;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $actif="oui";

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CDC", inversedBy="dossiers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cdc;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Navette", mappedBy="dossier")
     */
    private $navettes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transmission", mappedBy="dossier")
     */
    private $transmissions;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $nom_mairie;

    public function __construct()
    {
        $this->navettes = new ArrayCollection();
        $this->transmissions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNomDossier()
    {
        return $this->nom_dossier;
    }

    public function setNomDossier(string $nom_dossier): self
    {
        $this->nom_dossier = strtoupper($nom_dossier);

        return $this;
    }

    public function getDateAjout(): \DateTimeInterface
    {
        return $this->date_ajout;
    }

    public function setDateAjout(\DateTimeInterface $date_ajout): self
    {
        $this->date_ajout = $date_ajout;

        return $this;
    }

    public function getActif(): string
    {
        return $this->actif;
    }

    public function setActif(string $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    public function getCdc(): ?CDC
    {
        return $this->cdc;
    }

    public function setCdc(?CDC $cdc): self
    {
        $this->cdc = $cdc;

        return $this;
    }

    /**
     * @return Collection|Navette[]
     */
    public function getNavettes(): Collection
    {
        return $this->navettes;
    }

    public function addNavette(Navette $navette): self
    {
        if (!$this->navettes->contains($navette)) {
            $this->navettes[] = $navette;
            $navette->setDossier($this);
        }

        return $this;
    }

    public function removeNavette(Navette $navette): self
    {
        if ($this->navettes->contains($navette)) {
            $this->navettes->removeElement($navette);
            // set the owning side to null (unless already changed)
            if ($navette->getDossier() === $this) {
                $navette->setDossier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Transmission[]
     */
    public function getTransmissions(): Collection
    {
        return $this->transmissions;
    }

    public function addTransmission(Transmission $transmission): self
    {
        if (!$this->transmissions->contains($transmission)) {
            $this->transmissions[] = $transmission;
            $transmission->setDossier($this);
        }

        return $this;
    }

    public function removeTransmission(Transmission $transmission): self
    {
        if ($this->transmissions->contains($transmission)) {
            $this->transmissions->removeElement($transmission);
            // set the owning side to null (unless already changed)
            if ($transmission->getDossier() === $this) {
                $transmission->setDossier(null);
            }
        }

        return $this;
    }

    public function getNomMairie(): ?string
    {
        return $this->nom_mairie;
    }

    public function setNomMairie(?string $nom_mairie): self
    {
        $this->nom_mairie = strtoupper( $nom_mairie );

        return $this;
    }
}
