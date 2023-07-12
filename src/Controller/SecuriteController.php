<?php

namespace App\Controller;

use App\Entity\Interne;
use App\Entity\Visiteur;
use App\Form\InterneType;
use App\Form\VisiteurType;
use App\Model\GPAOModels\EquipeTacheOperateur;
use App\Model\GPAOModels\Personnel;
use App\Model\GPAOModels\Pointage;
use App\Model\GPAOModels\TypePointage;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecuriteController extends AbstractController
{

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function visiteur($paramDefaults = 0, int $id, Request $request, EntityManagerInterface $manager)
    {
        $isSearchClicked = false;
        $isSearchDate = false;
        $list_visiteur = [];
        $visiteur = new Visiteur();
        $form = $this->createForm(VisiteurType::class, $visiteur, [
            "id" => $id
        ]);

        $builder = $this->createFormBuilder();
        $form_search = $builder->add("search", TextType::class, [
            'required' => false
        ])->getForm();

        $builder_search = $this->createFormBuilder();
        $form_search_date = $builder_search->add('date_search', TextType::class, [
            "required" => false
        ])->getForm();

        $newInsert = true;
        $message = "";
        /**
         * sortie visiteur
         */
        if ($paramDefaults == 1) {
            $id_user = $request->query->get('id');
            $get = $manager->getRepository(Visiteur::class)->find($id_user);
            $visiteur = $get;
            $newInsert = false;
            if ($visiteur === null) {
                throw $this->createNotFoundException('une erreur est survenue');
            } else {
                $get->setHeureSortie((new \DateTime())->setTimezone(new \DateTimeZone("Indian/Antananarivo")));
                $manager->persist($visiteur);
                $manager->flush();
                $this->addFlash("success", "Sortie effectuée avec success");
                return $this->redirectToRoute('visiteur');
            }
        }
        /**
         * suppression
         */
        if ($paramDefaults == 2) {
            $id_user = $request->query->get('id');
            $get = $manager->getRepository(Visiteur::class)->find($id_user);

            if ($get !== null) {
                $manager->remove($get);
                $manager->flush();
                $this->addFlash("success", "Suppression effectuéz avec success");
                return $this->redirectToRoute('visiteur');
            }
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * ajout
             */
            if ($newInsert) {
                $visiteur->setHeureEntrer((new \DateTime())->setTimezone(new \DateTimeZone("Indian/Antananarivo")));
                $message = "Information bien enregistrées";
            }

            $manager->persist($visiteur);
            $manager->flush();
            $this->addFlash("success", $message);
            return $this->redirectToRoute('visiteur');
        }
        /**
         * search
         */
        $form_search->handleRequest($request);
        $data = "";
        if ($form_search->isSubmitted() && $form_search->isValid()) {
            $data = $form_search->getData();
            $data = $data["search"];
            /**
             * on attribue a true s'il fait de recherche
             */
            $isSearchClicked = true;
        }
        /**
         * search by date
         */
        $debut = "";
        $fin = "";
        $form_search_date->handleRequest($request);
        if ($form_search_date->isSubmitted() and $form_search_date->isValid()) {
            $data = $form_search_date->getData();
            $data = $data["date_search"];

            if (stristr($data, " - ")) {
                $debut = explode("-", $data)[0];
                $debut = str_replace("/", "-", $debut);
                $fin = explode("-", $data)[1];
                $fin = str_replace("/", "-", $fin);
            } else {
                $debut = str_replace("/", "-", $data);
                $fin = $debut;
            }

            /**
             * on attribue a true s'il fait de recherche
             */
            $isSearchDate = true;
            $isSearchClicked = true;
        }
        if ($isSearchClicked) {
            if ($isSearchDate) {
                if ($debut == "" and $fin == "") {
                    $list_visiteur = $manager->getRepository(Visiteur::class)->findDataHierAndToDate();
                } else {
                    $list_visiteur = $manager->getRepository(Visiteur::class)->searchByDate($debut, $fin);
                }
                //on reinitialise le boolea
                $isSearchDate = false;
            } else {
                $list_visiteur = $manager->getRepository(Visiteur::class)->searchVisiteur($data);
            }
        } else {
            $list_visiteur = $manager->getRepository(Visiteur::class)->findDataHierAndToDate();
            //$list_visiteur = $manager->getRepository(Visiteur::class)->findAll();

        }
        return $this->render('visiteur/visiteur.html.twig', [
            'form' => $form->createView(),
            'list_visiteur' => $list_visiteur,
            "form_search" => $form_search->createView(),
            'form_search_by_date' => $form_search_date->createView()
        ]);
    }

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function interne($paramDefaults = 0, int $id = null, Request $request, EntityManagerInterface $manager)
    {
        $list_visiteur = [];
        $interne = new Interne();
        $isSearchClicked = false;
        $isSearchDate = false;

        $builder = $this->createFormBuilder();
        $form_search = $builder->add("search", TextType::class, [
            'required' => false
        ])->getForm();

        $builder_search = $this->createFormBuilder();
        $form_search_date = $builder_search->add('date_search', TextType::class, [
            "required" => false
        ])->getForm();


        $form = $this->createForm(InterneType::class, $interne, [
            'id' => $id
        ]);
        /**
         * suppression
         */
        if ($paramDefaults == 2) {
            // $id_user = $request->query->get('id');

            // dd($id_user);
            // dd($id);
            $get = $manager->getRepository(Interne::class)->find($id);

            if ($get !== null) {
                $manager->remove($get);
                $manager->flush();
                $this->addFlash("success", "Suppression effectuéz avec success");
                return $this->redirectToRoute("interne");
            }
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $interne->setDates((new \DateTime()));
            $interne->setHeuresortie((new \DateTime())->setTimezone(new \DateTimeZone("Indian/Antananarivo")));
            $manager->persist($interne);
            $manager->flush();
            $this->addFlash("success", "Donnée enregistrée");
        }
        /**
         * seach by name,firstname and cin
         */
        $data = "";
        $form_search->handleRequest($request);
        if ($form_search->isSubmitted() && $form_search->isValid()) {
            $data = $form_search->getData();
            $data = $data["search"];
            /**
             * on attribue a true s'il fait de recherche
             */
            $isSearchClicked = true;
        }
        /**
         * search by date
         */
        $debut = "";
        $fin = "";
        $form_search_date->handleRequest($request);
        if ($form_search_date->isSubmitted() and $form_search_date->isValid()) {
            $data = $form_search_date->getData();
            $data = $data["date_search"];
            /**
             * si interval de date, on la scie
             */
            if (stristr($data, " - ")) {
                $debut = explode("-", $data)[0];
                $debut = str_replace("/", "-", $debut);
                $fin = explode("-", $data)[1];
                $fin = str_replace("/", "-", $fin);
            } else {
                $debut = str_replace("/", "-", $data);
                $fin = $debut;
            }
            /**
             * on attribue a true s'il fait de recherche
             */
            $isSearchClicked = true;
            $isSearchDate = true;
        }

        if ($isSearchClicked) {
            if ($isSearchDate) {
                $list_visiteur = $manager->getRepository(Interne::class)->searchByDate($debut, $fin);
                //on reinitialise le boolean
                $isSearchDate = false;
            } else {
                $list_visiteur = $manager->getRepository(Interne::class)->searchInterne($data);
            }
        } else {
            //$list_visiteur = $manager->getRepository(Interne::class)->findAll();
            $list_visiteur = $manager->getRepository(Interne::class)->findDataHierAndToDate();
        }

        return $this->render('visiteur/interne.html.twig', [
            "form" => $form->createView(),
            'list_visiteur' => $list_visiteur,
            'form_search' => $form_search->createView(),
            'form_search_by_date' => $form_search_date->createView()
        ]);
    }

    public function identificationPersonne(Request $request, Connection $connex): Response
    {
        $pers = new Personnel($connex);
        $point = new Pointage($connex);
        // $tacheOperateur = new EquipeTacheOperateur($connex);
        $pointage_one = null;
        $retard = null;
        $retard_pause = null;

        $id_personnel = $request->query->get('id_personnel');
        /**
         * recherche
         */
        if ($id_personnel) {
            // dd(
            //     $pers->Get()->execute()->fetch()
            // );
            // $personnel = $pers->Get(["equipe_tache_operateur.*"])->where('id_personnel = :id_personnel')
            //     ->setParameter('id_personnel', $id_personnel)
            //     ->execute()->fetch();
            // dd($personnel);

            $pointages = $point->Get([
                "nom",
                "prenom",
                "personnel.id_personnel",
                "photo",
                "login",
                "type_pointage.description",
                "pointage.heure_entre",
                "date_debut",
                "retard",
                "total_pause",
                "sortie_pause",
                "entree_pause",
                "description"
            ])
                ->where('personnel.id_personnel = :id_personnel')
                ->setParameter('id_personnel', $id_personnel)
                ->andWhere("date_debut = :d")
                // ->setParameter('d', "2018-09-07")
                ->setParameter("d", (new \DateTime())->format("Y-m-d"))
                ->orderBy("date_debut", "ASC")
                ->execute()
                ->fetchAll();
            // dd($pointages);
            if (count($pointages) == 0) {
                $this->addFlash("danger", "Aucune pointage du matricule " . $id_personnel . " a été detectée aujourd'hui");
                return $this->redirectToRoute("app_securite_identification");
            }
            /**
             * recuperation du description du pointage
             * si on a deux pointages
             * par exemple un => pointage matin, deux => complement,
             * on verifie si par rapport à une heure fixe
             */
            foreach ($pointages as $pointage) {
                if ($pointage["heure_entre"] < "12:10:00" && $pointage["heure_entre"] < (new DateTime())->format("H:i:s")) {
                    $pointage_one = $pointage;
                    // $description = $pointage["description"];
                }
                if ($pointage["heure_entre"] > "12:10:00" && $pointage["heure_entre"] < (new DateTime())->format("H:i:s")) {
                    // $description = $pointage['description'];
                    $pointage_one = $pointage;
                }
            }

            $pers = $pers->Get(["nom_equipe"])
                ->where('id_personnel = :id')
                ->setParameter('id', $id_personnel)
                ->execute()
                ->fetch();
            $pointage_one["nom_equipe"] = $pers["nom_equipe"];
            // $personnel["description"] = $description;

            if ($request->query->get('type')) {
                $type = $request->query->get('type');

                if ($type == "retard") {
                    $retard = $pointage_one["retard"];
                } else {
                    $max_pause = new \DateTime("00:10:00");
                    $array_max_pause = explode(':', $max_pause->format("H:i:s"));
                    $array_time_sortie = explode(":", $pointage_one["sortie_pause"]);
                    $entree_pause = new \DateTime($pointage_one["entree_pause"]);
                    $heure_pause = $entree_pause->sub(new \DateInterval("PT" . $array_time_sortie[0] . "H" . $array_time_sortie[1] . "M" . $array_time_sortie[2] . "S"));

                    if ($heure_pause->getTimestamp() > strtotime("00:10:00")) {
                        $retard_pause = $heure_pause->sub(new \DateInterval("PT" . $array_max_pause[0] . "H" . $array_max_pause[1] . "M" . $array_max_pause[2] . "S"))->format("H:i:s");
                    }
                }
            }
        }
        dump($pointage_one, $id_personnel, $retard, $retard_pause);
        return $this->render("securite/identification.html.twig", [
            "personnel" => $pointage_one,
            "id_personnel" => $id_personnel,
            'retard' => $retard,
            "retard_pause" => $retard_pause
        ]);
    }
}