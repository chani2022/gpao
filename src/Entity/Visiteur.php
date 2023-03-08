<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VisiteurRepository")
 * @Vich\Uploadable
 */
class Visiteur
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank(message="Nom obligatoire")
     * @ORM\Column(type="string", length=100)
     */
    private $nom;

    /**
     *
     * @Assert\NotBlank(message="Prénom obligatoire")
     * @ORM\Column(type="string", length=100)
     */
    private $prenom;

    /**
     * @Assert\NotBlank(message="CIN obligatoire")
     * @Assert\Length(
     *      min = 12,
     *      max = 12,
     *      minMessage = "CIN INVALIDE, trop court",
     *      maxMessage = "CIN INVALIDE, trop long")
     * @ORM\Column(type="string", length=100)
     */
    private $cin;

    /**
     * @Vich\UploadableField(mapping="visiteur_image", fileNameProperty="fileName")
     * @var File|null
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fileName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $heureSortie;

    /**
     * @Assert\NotBlank(message="motif obligatoire")
     * @ORM\Column(type="string", length=255)
     */
    private $motif;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $heureentrer;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = strtoupper($nom);

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = ucwords($prenom);

        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(string $cin): self
    {
        $this->cin = $cin;

        return $this;
    }

    public function setImageFile(?File $photo = null): self
    {
        $this->imageFile = $photo;
        if ($this->imageFile instanceof UploadedFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getHeureSortie(): ?\DateTimeInterface
    {
        return $this->heureSortie;
    }

    public function setHeureSortie(?\DateTimeInterface $heureSortie): self
    {
        $hours_formated = \DateTime::createFromFormat("H:i:s", $heureSortie->format("H:i:s"));
        $this->heureSortie = $hours_formated;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): self
    {
        $this->motif = $motif;

        return $this;
    }

    public function getHeureentrer(): ?\DateTimeInterface
    {
        return $this->heureentrer;
    }

    public function setHeureentrer(?\DateTimeInterface $heureentrer): self
    {
        $hours_formated = \DateTime::createFromFormat("H:i:s", $heureentrer->format("H:i:s"));
        $this->heureentrer = $hours_formated;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

}
