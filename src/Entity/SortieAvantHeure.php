<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SortieAvantHeureRepository")
 */
class SortieAvantHeure
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_at;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $matricule;

    /**
     * @ORM\Column(type="time")
     */
    private $heureSortie;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $donneurOrdre;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAt(): ?\DateTimeInterface
    {
        return $this->date_at;
    }

    public function setDateAt(\DateTimeInterface $date_at): self
    {
        $this->date_at = $date_at;

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

    public function getHeureSortie(): ?\DateTimeInterface
    {
        return $this->heureSortie;
    }

    public function setHeureSortie(\DateTimeInterface $heureSortie): self
    {
        $this->heureSortie = $heureSortie;

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
