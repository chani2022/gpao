<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IncidentGeneralRepository")
 */
class IncidentGeneral
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $date_incident;

    /**
     * @Assert\NotBlank(message="Heure début obligatoire")
     * @ORM\Column(type="time")
     */
    private $heure_debut;

    /**
     * @Assert\NotBlank(message="Heure fin obligatoire")
     * @ORM\Column(type="time")
     */
    private $heure_fin;

    /**
     * @Assert\NotBlank(message="Raison obligatoire")
     * @ORM\Column(type="text")
     */
    private $raison;

    /**
     * @ORM\Column(type="integer")
     */
    private $inserer_par;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateIncident(): ?\DateTimeInterface
    {
        return $this->date_incident;
    }

    public function setDateIncident(\DateTimeInterface $date_incident): self
    {
        $this->date_incident = $date_incident;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heure_debut;
    }

    public function setHeureDebut(\DateTimeInterface $heure_debut): self
    {
        $this->heure_debut = $heure_debut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heure_fin;
    }

    public function setHeureFin(\DateTimeInterface $heure_fin): self
    {
        $this->heure_fin = $heure_fin;

        return $this;
    }

    public function getRaison(): ?string
    {
        return $this->raison;
    }

    public function setRaison(string $raison): self
    {
        $this->raison = $raison;

        return $this;
    }

    public function getInsererPar(): ?int
    {
        return $this->inserer_par;
    }

    public function setInsererPar(int $inserer_par): self
    {
        $this->inserer_par = $inserer_par;

        return $this;
    }
}
