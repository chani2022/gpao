<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RecolteHeureRepository")
 */
class RecolteHeure
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
    private $matricule;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prenom;

    /**
     * @ORM\Column(type="date")
     */
    private $date_embauche;

    /**
     * @ORM\Column(type="date")
     */
    private $debut_compte;

    /**
     * @ORM\Column(type="date")
     */
    private $fin_compte;

    /**
     * @ORM\Column(type="float")
     */
    private $heure_references;

    /**
     * @ORM\Column(type="float")
     */
    private $nb_jour_references;

    /**
     * @ORM\Column(type="float")
     */
    private $horaire;



    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heures_travailles;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heures_majores;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heures_recuperes;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heures_supp_30;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heures_supp_nuit_75;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heures_supp_dimanche_100;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_heures_supp;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $avantage_en_nature;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $absence_conge_paye;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $absence_reguliere;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $absence_irreguliere;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $indemnite_conge_paye;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $indemnite_repas_transport;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $fonction;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?int
    {
        return $this->matricule;
    }

    public function setMatricule(int $matricule): self
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->date_embauche;
    }

    public function setDateEmbauche(\DateTimeInterface $date_embauche): self
    {
        $this->date_embauche = $date_embauche;

        return $this;
    }

    public function getDebutCompte(): ?\DateTimeInterface
    {
        return $this->debut_compte;
    }

    public function setDebutCompte(\DateTimeInterface $debut_compte): self
    {
        $this->debut_compte = $debut_compte;

        return $this;
    }

    public function getFinCompte(): ?\DateTimeInterface
    {
        return $this->fin_compte;
    }

    public function setFinCompte(\DateTimeInterface $fin_compte): self
    {
        $this->fin_compte = $fin_compte;

        return $this;
    }

    public function getHeureReferences(): ?float
    {
        return $this->heure_references;
    }

    public function setHeureReferences(float $heure_references): self
    {
        $this->heure_references = $heure_references;

        return $this;
    }

    public function getNbJourReferences(): ?float
    {
        return $this->nb_jour_references;
    }

    public function setNbJourReferences(float $nb_jour_references): self
    {
        $this->nb_jour_references = $nb_jour_references;

        return $this;
    }

    public function getHoraire(): ?float
    {
        return $this->horaire;
    }

    public function setHoraire(float $horaire): self
    {
        $this->horaire = $horaire;

        return $this;
    }

  

    public function getHeuresTravailles(): ?float
    {
        return $this->heures_travailles;
    }

    public function setHeuresTravailles(?float $heures_travailles): self
    {
        $this->heures_travailles = $heures_travailles;

        return $this;
    }

    public function getHeuresMajores(): ?float
    {
        return $this->heures_majores;
    }

    public function setHeuresMajores(?float $heures_majores): self
    {
        $this->heures_majores = $heures_majores;

        return $this;
    }

    public function getHeuresRecuperes(): ?float
    {
        return $this->heures_recuperes;
    }

    public function setHeuresRecuperes(?float $heures_recuperes): self
    {
        $this->heures_recuperes = $heures_recuperes;

        return $this;
    }

    public function getHeuresSupp30(): ?float
    {
        return $this->heures_supp_30;
    }

    public function setHeuresSupp30(?float $heures_supp_30): self
    {
        $this->heures_supp_30 = $heures_supp_30;

        return $this;
    }

    public function getHeuresSuppNuit75(): ?float
    {
        return $this->heures_supp_nuit_75;
    }

    public function setHeuresSuppNuit75(?float $heures_supp_nuit_75): self
    {
        $this->heures_supp_nuit_75 = $heures_supp_nuit_75;

        return $this;
    }

    public function getHeuresSuppDimanche100(): ?float
    {
        return $this->heures_supp_dimanche_100;
    }

    public function setHeuresSuppDimanche100(?float $heures_supp_dimanche_100): self
    {
        $this->heures_supp_dimanche_100 = $heures_supp_dimanche_100;

        return $this;
    }

    public function getTotalHeuresSupp(): ?float
    {
        return $this->total_heures_supp;
    }

    public function setTotalHeuresSupp(?float $total_heures_supp): self
    {
        $this->total_heures_supp = $total_heures_supp;

        return $this;
    }

    public function getAvantageEnNature(): ?float
    {
        return $this->avantage_en_nature;
    }

    public function setAvantageEnNature(?float $avantage_en_nature): self
    {
        $this->avantage_en_nature = $avantage_en_nature;

        return $this;
    }

    public function getAbsenceCongePaye(): ?float
    {
        return $this->absence_conge_paye;
    }

    public function setAbsenceCongePaye(?float $absence_conge_paye): self
    {
        $this->absence_conge_paye = $absence_conge_paye;

        return $this;
    }

    public function getAbsenceReguliere(): ?float
    {
        return $this->absence_reguliere;
    }

    public function setAbsenceReguliere(?float $absence_reguliere): self
    {
        $this->absence_reguliere = $absence_reguliere;

        return $this;
    }

    public function getAbsenceIrreguliere(): ?float
    {
        return $this->absence_irreguliere;
    }

    public function setAbsenceIrreguliere(?float $absence_irreguliere): self
    {
        $this->absence_irreguliere = $absence_irreguliere;

        return $this;
    }

    public function getIndemniteCongePaye(): ?float
    {
        return $this->indemnite_conge_paye;
    }

    public function setIndemniteCongePaye(?float $indemnite_conge_paye): self
    {
        $this->indemnite_conge_paye = $indemnite_conge_paye;

        return $this;
    }

    public function getIndemniteRepasTransport(): ?float
    {
        return $this->indemnite_repas_transport;
    }

    public function setIndemniteRepasTransport(?float $indemnite_repas_transport): self
    {
        $this->indemnite_repas_transport = $indemnite_repas_transport;

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): self
    {
        $this->fonction = $fonction;

        return $this;
    }
}
