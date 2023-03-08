<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransmissionRepository")
 */
class Transmission
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
    private $objet;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_envoie;

    /**
     * @ORM\Column(type="text")
     */
    private $contenu;

    /**
     * @ORM\Column(type="integer")
     */
    private $expediteur;

    /**
     * @ORM\Column(type="array")
     */
    private $destinataires = [];

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TransmissionPieceJointe", mappedBy="transmission", cascade={"persist","remove"})
     */
    private $pieces;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $lu;

    

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Dossier", inversedBy="transmissions", cascade={"persist","remove"})
     */
    private $dossier;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LectureTransmission", mappedBy="transmission")
     */
    private $lectureTransmissions;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Transmission", inversedBy="transmissions")
     */
    private $reponses;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Transmission", mappedBy="reponses", cascade={"persist","remove"})
     */
    private $transmissions;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default": false})
     */
    private $mail_client;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default": false})
     */
    private $mail_navette;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $date_reel_reception;




    public function __construct()
    {
        $this->pieces = new ArrayCollection();
        $this->lu = FALSE;
        $this->transmissions = new ArrayCollection();
        $this->lectureTransmissions = new ArrayCollection();
        $this->reponses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): self
    {
        $this->objet = $objet;

        return $this;
    }

    public function getDateEnvoie(): ?\DateTimeInterface
    {
        return $this->date_envoie;
    }

    public function setDateEnvoie(\DateTimeInterface $date_envoie): self
    {
        $this->date_envoie = $date_envoie;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getExpediteur(): ?int
    {
        return $this->expediteur;
    }

    public function setExpediteur(int $expediteur): self
    {
        $this->expediteur = $expediteur;

        return $this;
    }

    public function getDestinataires(): ?array
    {
        return $this->destinataires;
    }

    public function setDestinataires(array $destinataires): self
    {
        $this->destinataires = $destinataires;

        return $this;
    }

    /**
     * @return Collection|TransmissionPieceJointe[]
     */
    public function getPieces(): Collection
    {
        return $this->pieces;
    }

    public function addPiece(TransmissionPieceJointe $piece): self
    {
        if (!$this->pieces->contains($piece)) {
            $this->pieces[] = $piece;
            $piece->setTransmission($this);
        }

        return $this;
    }

    public function removePiece(TransmissionPieceJointe $piece): self
    {
        if ($this->pieces->contains($piece)) {
            $this->pieces->removeElement($piece);
            // set the owning side to null (unless already changed)
            if ($piece->getTransmission() === $this) {
                $piece->setTransmission(null);
            }
        }

        return $this;
    }

    public function getLu(): ?bool
    {
        return $this->lu;
    }

    public function setLu(bool $lu): self
    {
        $this->lu = $lu;

        return $this;
    }

    

    public function removeTransmission(self $transmission): self
    {
        if ($this->transmissions->contains($transmission)) {
            $this->transmissions->removeElement($transmission);
            // set the owning side to null (unless already changed)
            if ($transmission->getReponses() === $this) {
                $transmission->setReponses(null);
            }
        }

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    

    /**
     * @return Collection|LectureTransmission[]
     */
    public function getLectureTransmissions(): Collection
    {
        return $this->lectureTransmissions;
    }

    public function addLectureTransmission(LectureTransmission $lectureTransmission): self
    {
        if (!$this->lectureTransmissions->contains($lectureTransmission)) {
            $this->lectureTransmissions[] = $lectureTransmission;
            $lectureTransmission->setTransmission($this);
        }

        return $this;
    }

    public function removeLectureTransmission(LectureTransmission $lectureTransmission): self
    {
        if ($this->lectureTransmissions->contains($lectureTransmission)) {
            $this->lectureTransmissions->removeElement($lectureTransmission);
            // set the owning side to null (unless already changed)
            if ($lectureTransmission->getTransmission() === $this) {
                $lectureTransmission->setTransmission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(self $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses[] = $reponse;
        }

        return $this;
    }

    public function removeReponse(self $reponse): self
    {
        if ($this->reponses->contains($reponse)) {
            $this->reponses->removeElement($reponse);
        }

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getTransmissions(): Collection
    {
        return $this->transmissions;
    }

    public function addTransmission(self $transmission): self
    {
        if (!$this->transmissions->contains($transmission)) {
            $this->transmissions[] = $transmission;
            $transmission->addReponse($this);
        }

        return $this;
    }

    public function getMailClient(): ?bool
    {
        return $this->mail_client;
    }

    public function setMailClient(?bool $mail_client): self
    {
        $this->mail_client = $mail_client;

        return $this;
    }

    public function getMailNavette(): ?bool
    {
        return $this->mail_navette;
    }

    public function setMailNavette(?bool $mail_navette): self
    {
        $this->mail_navette = $mail_navette;

        return $this;
    }

    public function getDateReelReception(): ?\DateTimeInterface
    {
        return $this->date_reel_reception;
    }

    public function setDateReelReception(?\DateTimeInterface $date_reel_reception): self
    {
        $this->date_reel_reception = $date_reel_reception;

        return $this;
    }
}
