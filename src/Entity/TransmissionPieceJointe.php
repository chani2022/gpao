<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransmissionPieceJointeRepository")
 */
class TransmissionPieceJointe
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
    private $nom_piece;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Transmission", inversedBy="pieces")
     */
    private $transmission;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom_origine;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPiece(): ?string
    {
        return $this->nom_piece;
    }

    public function setNomPiece(string $nom_piece): self
    {
        $this->nom_piece = $nom_piece;

        return $this;
    }

    public function getTransmission(): ?Transmission
    {
        return $this->transmission;
    }

    public function setTransmission(?Transmission $transmission): self
    {
        $this->transmission = $transmission;

        return $this;
    }

    public function getNomOrigine(): ?string
    {
        return $this->nom_origine;
    }

    public function setNomOrigine(string $nom_origine): self
    {
        $this->nom_origine = $nom_origine;

        return $this;
    }
    
    public function getChemin(){
        return "upload/piece/".$this->nom_piece;
    }
}
