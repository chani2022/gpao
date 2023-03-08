<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LectureTransmissionRepository")
 */
class LectureTransmission
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $destinataire;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Transmission", inversedBy="lectureTransmissions", cascade={"persist","remove"})
     */
    private $transmission;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDestinataire(): ?int
    {
        return $this->destinataire;
    }

    public function setDestinataire(int $destinataire): self
    {
        $this->destinataire = $destinataire;

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
}
