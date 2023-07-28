<?php

namespace App\Controller;


use App\Entity\CDC;
use App\Form\CDCType;
use App\Entity\Dossier;
use App\Entity\Navette;
use App\Form\DossierType;

use App\Form\NavetteType;

use App\Entity\Transmission;
use App\Model\GPAOModels\AttributionDossier;
use PhpOffice\PhpWord\PhpWord;

use Box\Spout\Common\Entity\Row;
use PhpOffice\PhpWord\IOFactory;
use App\Model\GPAOModels\Personnel;
use Doctrine\DBAL\Driver\Connection;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Model\GPAOModels\DemandeSupplementaire;
use App\Model\GPAOModels\Dossier as GPAODossier;
use App\Model\GPAOModels\FichiersHistoriqueModif;
use App\Model\GPAOModels\LivraisonDossier;
use Symfony\Component\Routing\Annotation\Route;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Types\StringType;
use phpDocumentor\Reflection\Types\Integer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * DossierController
 *
 * @author Administrateur
 */
class DossierController extends AbstractController
{

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function gestionCDC(Request $req, $idCdc = 0)
    {

        $cdc = new CDC();

        $em = $this->getDoctrine()->getManager();

        if ($idCdc != 0) {
            $get = $em->getRepository(CDC::class)->find($idCdc);
            if ($get) {
                $cdc = $get;
            } else {
                throw $this->createNotFoundException('CDC Introuvable');
            }
        }

        $form = $this->createForm(CDCType::class, $cdc, [
            "method" => "POST",
            "action" => "",
            "attr" => []
        ]);
        //form handling
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $cdc = $form->getData();

            $em->persist($cdc);

            $em->flush();

            $this->addFlash('success', 'Enregistrement effectué');
            return $this->redirectToRoute('cdc_gestion');
        }

        $keywords = $req->request->get('keywords');

        $listeCDC = [];

        if (!is_null($keywords)) {
            $listeCDC = $em->getRepository(CDC::class)->search($keywords);
        } else {
            //ra misy filtre idCDC dia iny ihany no aseho ery @ liste
            if ($idCdc !== 0) {
                $listeCDC = $em->getRepository(CDC::class)->findBy(["id" => $idCdc], ["nom_cdc" => "ASC"]);
            } else {
                $listeCDC = $em->getRepository(CDC::class)->findBy([], ["nom_cdc" => "ASC"]);
            }
        }



        return $this->render('dossier/cdc.html.twig', [
            "form" => $form->createView(),
            "liste_cdc" => $listeCDC
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function gestionDossier(Request $req, PaginatorInterface $paginator, $idDossier = 0)
    {

        $dossier = new Dossier();

        $em = $this->getDoctrine()->getManager();

        $isNewInsert = TRUE;

        if ($idDossier != 0) {
            $get = $em->getRepository(Dossier::class)->find($idDossier);
            if ($get) {
                $dossier = $get;
                $isNewInsert = FALSE;
            } else {
                throw $this->createNotFoundException('Dossier Introuvable');
            }
        }

        $form = $this->createForm(DossierType::class, $dossier, [
            "method" => "POST",
            "action" => "",
            "attr" => []
        ]);
        //form handling
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $dossier = $form->getData();

            //si insertion apetraka ny date d'ajout
            if ($isNewInsert === TRUE) {
                $dossier->setDateAjout(new \DateTime(date("Y-m-d")));
            }

            $em->persist($dossier);

            $em->flush();

            $this->addFlash('success', 'Enregistrement effectué');
            return $this->redirectToRoute('dossier_gestion');
        }

        $keywords = $req->request->get('keywords');
        $dates = $req->request->get('dates');

        if (!is_null($dates) && preg_match("~^[0-9]{2}\/[0-9]{2}\/[0-9]{4} - [0-9]{2}\/[0-9]{2}\/[0-9]{4}$~", $dates)) {
            $dates = explode(" - ", $dates);
            $dateStart = explode("/", $dates[0]);
            $dateEnd = explode("/", $dates[1]);

            $dates[0] = $dateStart[2] . '-' . $dateStart[1] . "-" . $dateStart[0];
            $dates[1] = $dateEnd[2] . '-' . $dateEnd[1] . "-" . $dateEnd[0];
        } else {
            $dates = null;
        }

        $listeDossier = [];

        if (!is_null($keywords)) {
            $listeDossier = $em->getRepository(Dossier::class)->search($keywords, $dates);
        } else {
            //ra misy filtre idDossier dia iny ihany no aseho ery @ liste
            if ($idDossier !== 0) {
                $listeDossier = $em->getRepository(Dossier::class)->findBy(["id" => $idDossier], ["nom_dossier" => "ASC"]);
            } else {
                $listeDossier = $em->getRepository(Dossier::class)->findBy([], ["nom_dossier" => "ASC"]);
            }
        }

        $dossierPaginated = $paginator->paginate(
            $listeDossier,
            $req->query->getInt('page', 1),
            21
        );

        return $this->render('dossier/dossier.html.twig', [
            "form" => $form->createView(),
            "liste_dossier" => $listeDossier,
            "dossierPaginated" => $dossierPaginated
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * Ajout note à un dossier
     */
    public function ajoutNavette(Dossier $dossier, Request $req)
    {
        $em = $this->getDoctrine()->getManager();

        $navette = new Navette();

        $navette->setDossier($dossier);

        $form = $this->createForm(NavetteType::class, $navette, [
            "method" => "POST",
            "action" => "",
            "attr" => []
        ]);
        //form handling
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($navette);
            $em->flush();

            $this->addFlash('success', 'Note enregistrée pour le dossier ' . $dossier->getNomDossier());

            return $this->redirectToRoute('navette_ajout', ["id" => $dossier->getId()]);
        }

        // maka izay ajou recent
        $recent_ads = $em->getRepository(Navette::class)->getRecentlyAds($dossier->getId());



        return $this->render('dossier/envoie.html.twig', [
            "form" => $form->createView(),
            "dossier" => $dossier,
            "recent" => $recent_ads
        ]);
    }
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * visualisation de la liste des Notes ajoutées pour un dossier
     */
    public function showAllForDossier(Dossier $dossier, Request $req, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();


        /**
         * parametres de recherche
         */
        $params = ["dossier_id" => $dossier->getId()];

        $mailClient = $req->request->get('mailClient');
        $sansReponses = $req->request->get('sansReponses');
        $dateSearch = $req->request->get('date');

        if ($mailClient == 1) {
            $params["mailClient"] = $mailClient;
        }
        if ($sansReponses == 1) {
            $params['sansReponses'] = $sansReponses;
        }

        $expoDate = explode(" - ", $dateSearch);

        $dateDebut = $dateFin = NULL;
        if (count($expoDate) > 0) {
            if (strlen($expoDate[0]) == 10) {
                $params['dateDebut'] = $expoDate[0];

                $dateDebut = $expoDate[0];
                if (count($expoDate) == 2) {
                    $params['dateFin'] = $expoDate[1];
                    $dateFin = $expoDate[1];
                } else {
                    $dateFin = $dateDebut;
                }
                //var_dump($expoDate);
            }
        }

        $navette = $em->getRepository(Transmission::class)->getMessagesNavette($params);

        $navettePaginated = $paginator->paginate(
            $navette,
            $req->query->getInt('page', 1),
            18
        );

        return $this->render('dossier/show-navette-dossiers.html.twig', [
            "notes" => $navette,
            "dossier" => $dossier,
            "navettePaginated" => $navettePaginated,
            "dateDebut" => $dateDebut,
            "dateFin" => $dateFin
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * Exportation de tous les navettes pour le dossier
     */
    public function exportationNavette(Dossier $dossier, Request $req)
    {

        $tempDir = $this->getParameter('app.temp_dir');

        $em = $this->getDoctrine()->getManager();

        $params = ["dossier" => $dossier->getId(), "mail_navette" => 1];

        $datas = [];

        //$navette = $em->getRepository(Transmission::class)->findBy($params,["date_envoie"=>"DESC"]);
        $navette = $em->getRepository(Transmission::class)->findBy($params, ["date_reel_reception" => "DESC"]);

        $nomDossier = $dossier->getNomDossier();

        foreach ($navette as $n) {
            //on n'exporte que les mails qui ont été indiqué à archiver
            if ($n->getMailNavette() == TRUE) {

                $entete = "[ENVOIE";
                if ($n->getMailClient() == TRUE || $n->getMailClient() == 1) $entete = "[RECEPTION";

                if (!is_null($n->getDateReelReception())) {
                    $entete = $entete . " " . $n->getDateReelReception()->format("d/m/Y") . "]";
                } else {
                    $entete = $entete . "]";
                }

                $datas[] = $entete;
                //$datas[] = strip_tags(html_entity_decode($n->getContenu()));
                $contenu = $n->getContenu();
                $contenu = preg_replace('/<[^>]*>/', '', $contenu);
                $contenu = html_entity_decode($contenu);

                $datas[] = $contenu;
            }
        }

        $fname = $tempDir . "/" . time() . ".txt";
        file_put_contents($fname, implode("\n\n", $datas));

        return $this->file($fname, "NAVETTE " . $nomDossier);
    }
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * Exportation de tous les navettes pour le dossier
     */
    public function exportationNavetteWord(Dossier $dossier, Request $req)
    {
        $tempDir = $this->getParameter('app.temp_dir');

        $em = $this->getDoctrine()->getManager();

        $params = ["dossier" => $dossier->getId(), "mail_navette" => 1];

        //$datas = [];

        //$navette = $em->getRepository(Transmission::class)->findBy($params,["date_envoie"=>"DESC"]);
        $navette = $em->getRepository(Transmission::class)->findBy($params, ["date_reel_reception" => "DESC"]);

        $nomDossier = $dossier->getNomDossier();
        /**
         * word
         */
        $phpWord = new PhpWord();

        $section = $phpWord->addSection();

        $saut_ligne = [
            "envoie" => "",
            "reception" => ""
        ];

        foreach ($navette as $n) {
            //on n'exporte que les mails qui ont été indiqué à archiver
            if ($n->getMailNavette() == TRUE) {

                $style = array('color' => "green", 'name' => 'Comics', 'size' => 15, 'bold' => true);

                $entete = "[ENVOIE";

                if ($n->getMailClient() == TRUE || $n->getMailClient() == 1) $entete = "[RECEPTION";

                if (!is_null($n->getDateReelReception())) {
                    $entete = $entete . " " . $n->getDateReelReception()->format("d/m/Y") . "]";
                } else {
                    $entete = $entete . "]";
                }

                if (preg_match('/RECEPTION/', $entete)) {
                    $style["color"] = "red";
                }

                $section->addText(
                    $entete,
                    $style
                );

                $contenu = $n->getContenu();

                if (preg_match('/&lt;/', $contenu)) {
                    $contenu = preg_replace('/&lt;/', '[', $contenu);
                    if (preg_match('/&gt;/', $contenu)) {
                        $contenu = preg_replace('/&gt;/', ']', $contenu);
                    }
                }

                if (preg_match('/&nbsp;/', $contenu)) {
                    $contenu = preg_replace('/&nbsp;/', '<w:br/>', $contenu);
                }

                $contenu = strip_tags($contenu); //on enleve les balise html
                $contenu = htmlspecialchars($contenu); //si une balise existe en le rend si < => &lt



                if (preg_match('/\r\n/', $contenu)) {
                    $contenu = preg_replace('/\r\n/', '<w:br/>', $contenu);
                }

                $section->addText(
                    $contenu,
                    array('name' => 'Comics', 'size' => 12)
                );

                /**
                 * creation de saut de ligne 
                 */
                if (preg_match('/\[ENVOIE/', $entete)) {
                    $saut_ligne["envoie"] = $contenu;
                } else {
                    $saut_ligne["reception"] = $contenu;
                }

                if (!empty($saut_ligne["envoie"]) && !empty($saut_ligne["reception"])) {

                    $section->addTextBreak(4);
                    $saut_ligne["envoie"] = "";
                    $saut_ligne["reception"] = "";
                }
            }
        }
        $timeC = time();
        $fname = $tempDir . "/" . $timeC . ".docx";
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fname);


        return $this->file($fname, "NAVETTE " . $nomDossier . '.docx');
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * Visualisation navette
     */
    public function showNavette(Navette $navette, Request $req)
    {

        return $this->render('dossier/show-navette.html.twig', [
            "messages" => $navette
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function planning(Request $req)
    {

        return $this->render('dossier/planning.html.twig', []);
    }
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function suiviProd(Request $request, Connection $connexion)
    {

        date_default_timezone_set("Indian/Antananarivo");

        $type_pointage = "1"; //equipe matin
        $total_encours_traitement = 0;
        $user_prod = [];
        $list_user_encours_traitement = [];

        $list_fonction_query = ' IN (\'CORE 2\',\'CORE 1\',\'OP 2\', \'OP 1\',\'CQ 1\') ';
        $equipe_one = 0;
        $equipe_two = 0;
        $personne_dot_work = [];
        $equipe_one_dot_work = 0;
        $equipe_two_dot_work = 0;

        //dump((new Personnel($connexion))->Get(["id_personnel, login, nom_fonction"])->where("nom_fonction ='CQ 1'")->execute()->fetchAll());
        if (strtotime((new \DateTime())->format("H:i:s")) > strtotime("12:10:00")) {
            $type_pointage = "24"; //equipe apm
        }
        $pt = new \App\Model\GPAOModels\Pointage($connexion);
        $prod = new \App\Model\GPAOModels\Production($connexion);
        //dump((new \App\Model\GPAOModels\Personnel($connexion))->Get()->where("personnel.id_personnel = :id_personnel")->setParameter("id_personnel", 36)->execute()->fetch());
        /**
         * liste des personnel qui sont présent
         */
        $query = $pt->Get([
            "personnel.id_personnel", "personnel.nom", "personnel.prenom", "personnel.id_equipe_tache_operateur", "personnel.id_type_pointage", "pointage.heure_reel_entree", "personnel.nom_fonction", "personnel.nom_fonction", "personnel.nom_privilege", "type_pointage.description"
        ])
            ->where('pointage.date_debut = :db')
            ->setParameter('db', (new \DateTime())->format("Y-m-d")) //averenina
            // ->setParameter('db', "2018-09-20")
            ->andWhere('personnel.actif = :actif')
            ->setParameter('actif', 'Oui')
            ->andWhere('pointage.heure_reel_sortie is NULL ')

            ->andWhere('personnel.nom_fonction ' . $list_fonction_query);


        if (strtotime(date('H:i:s')) < strtotime("12:10:00")) {
            $query->andWhere('pointage.heure_reel_entree < :heure_entre OR type_pointage.description = :description')
                ->setParameter('heure_entre', "12:10:00")
                ->setParameter("description", "Extra");
        } else {
            $query->andWhere('pointage.heure_reel_entree > :heure_entre OR type_pointage.description = :description')
                ->setParameter('heure_entre', "12:10:00")
                ->setParameter("description", "Extra");
        }

        $pers_present = $query->orderBy("personnel.id_personnel", "ASC")->execute()->fetchAll();
        // dd($pers_present);
        //dump($pers_present);
        /**
         * listes des personnels qui travaille
         */
        $queryWork = $prod->Get([

            "DISTINCT personnel.id_personnel",
            "heure_debut",
            "heure_fin",
            //"production.*",
            "personnel.nom_fonction",
            "personnel.nom_privilege",
            "production.etat",
            "personnel.id_equipe_tache_operateur"
        ])
            ->where('production.etat = :etat')
            ->setParameter('etat', "Encours-Traitement")
            ->andWhere('personnel.nom_fonction ' . $list_fonction_query)

            //->orWhere("nom_privilege = :nom_privilege AND nom_fonction = 'CORE 1'")
            //->setParameter("nom_privilege", "admin")

            ->andWhere('personnel.actif = :actif')
            ->setParameter('actif', 'Oui');
        if (strtotime(date('H:i:s')) < strtotime("12:10:00")) {
            $queryWork->andWhere('heure_reel_debut < :heure_debut')
                ->setParameter('heure_debut', "12:10:00");
        } else {
            $queryWork->andWhere('heure_reel_debut > :heure_debut')
                ->setParameter('heure_debut', "12:10:00");
        }

        $pers_works = $queryWork->andWhere('date_traitement = :date_tr')
            ->setParameter('date_tr', (new \DateTime())->format("Y-m-d"))
            // ->setParameter('date_tr', "2018-09-20")
            ->execute()
            ->fetchAll();
        // dump($pers_works);
        /**
         * filtre des informations des personnes qui ne travaillent pas
         **/
        foreach ($pers_present as $present) {
            $pers_find = false;
            $is_user_prod = false;
            foreach ($pers_works as $work) {
                if ($present["id_personnel"] == $work["id_personnel"]) {
                    $is_user_prod = true;
                    $pers_find = true;
                    $list_prods[] = $work;
                    /**
                     * listes des production encours (C'EST QUI ONT DE TRAVAILLE)
                     */
                    $list_user_encours_traitement[$work["id_personnel"]] = array_merge($present, $work);
                }
            }

            /**
             * effectif qui prod
             */
            if ($is_user_prod) {
                $user_prod[] = $present["id_personnel"];
                if ($present["id_equipe_tache_operateur"] == 1) {
                    $equipe_one += 1;
                } else {
                    $equipe_two += 1;
                }
            }
            /**
             * personne not extra
             */
            if (!$pers_find) {
                /**
                 * si fonction != cq
                 */
                if ($present["nom_fonction"] != "CQ 1") {

                    /*
                     * on filtre que les user comme privilege
                     */
                    if ($present["nom_privilege"] != "admin") {
                        /**
                         * recuperation du heure fin du traitement prod
                         */
                        $sqlProdNotWork = $prod->Get([
                            "heure_debut",
                            "heure_fin"
                        ])->where("personnel.id_personnel = :id_personnel")
                            ->setParameter('id_personnel', $present["id_personnel"])
                            ->andWhere('production.etat != :etat')
                            ->setParameter('etat', "Encours-Traitement");

                        if (strtotime(date('H:i:s')) < strtotime("12:10:00")) {
                            $sqlProdNotWork->andWhere('heure_reel_debut <= :heure_debut')
                                ->setParameter('heure_debut', "12:10:00");
                        } else {
                            $sqlProdNotWork->andWhere('heure_reel_debut > :heure_debut')
                                ->setParameter('heure_debut', "12:10:00");
                        }
                        $prodNotWorks = $sqlProdNotWork->andWhere('date_traitement = :date_tr')
                            ->setParameter('date_tr', (new \DateTime())->format("Y-m-d"))
                            ->orderBy('heure_debut', "ASC")
                            ->execute()->fetchAll();
                        // dd($prodNotWorks, $present);
                        $present["heure_fin"] = null;
                        if (count($prodNotWorks) > 0) {
                            $present["heure_fin"] = $prodNotWorks[count($prodNotWorks) - 1]["heure_fin"];
                            /**
                             * recuperation du production AVANT qu'il n'a pas de travail
                             */
                            //$list_user_encours_traitement[$present["id_personnel"]] = array_merge($present, $prodNotWorks[count($prodNotWorks) - 1]);
                        }
                        $personne_dot_work[] = $present;
                        if ($present["id_equipe_tache_operateur"] == 1) {
                            $equipe_one_dot_work += 1;
                        } else {
                            $equipe_two_dot_work += 1;
                        }
                    }
                }
            }
        }
        asort($personne_dot_work);
        //dump($personne_dot_work);
        /**
         * total de personne qui traite UN dossier
         */
        $queryCount = $prod->Get([
            "DISTINCT COUNT(nom_dossier) as nb_dossier",
            "nom_dossier",

        ])
            ->where('production.etat = :etat')
            ->setParameter('etat', "Encours-Traitement")
            ->andWhere('personnel.nom_fonction ' . $list_fonction_query)

            ->andWhere('personnel.actif = :actif')
            ->setParameter('actif', 'Oui');
        if (strtotime(date('H:i:s')) < strtotime("12:10:00")) {
            $queryCount->andWhere('heure_debut < :heure_entre')
                ->setParameter('heure_entre', "12:10:00");
        } else {
            $queryCount->andWhere('heure_debut > :heure_entre')
                ->setParameter('heure_entre', "12:10:00");
        }

        $dossiers = $queryCount->andWhere('date_traitement = :date_tr')
            ->setParameter('date_tr', (new \DateTime())->format("Y-m-d"))
            ->groupBy("nom_dossier")
            ->orderBy('nom_dossier', "ASC")
            ->execute()
            ->fetchAll();

        rsort($dossiers); //tri par ordre decroissant
        foreach ($dossiers as $dossier) {
            $total_encours_traitement += $dossier["nb_dossier"];
        }
        //dump($total_encours_traitement);
        //dump($personne_dot_work);
        //dump($equipe_two_dot_work);
        ksort($list_user_encours_traitement);
        dump($list_user_encours_traitement);
        return $this->render('dossier/suivi.html.twig', [
            //"effectif" => count($pers_present),
            "effectif" => count($user_prod),
            "dossier" => $dossiers,
            "type_pointage" => $type_pointage,
            //"pers_non_travaille" => $pers_non_encours,
            "pers_non_travaille" => count($personne_dot_work),
            "nb_equipe_one" => $equipe_one,
            "nb_equipe_two" => $equipe_two,
            "list_pers_not_work" => $personne_dot_work,
            "nb_equipe_one_inactif" => $equipe_one_dot_work,
            "nb_equipe_two_inactif" => $equipe_two_dot_work,
            "list_prod_en_cours" => $list_user_encours_traitement
        ]);
    }
    /**
     * 
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @ParamConverter("IncidentGeneral", options={"id" = "incident_id"})
     * @param \App\Entity\IncidentGeneral $incident
     * @param Request $request
     * @param \App\Repository\IncidentGeneralRepository $incidentRepo
     * @return type
     */
    public function incidentGenerale($id, PaginatorInterface $paginator, Request $request, \App\Repository\IncidentGeneralRepository $incidentRepo)
    {
        $em = $this->getDoctrine()->getManager();
        $search_active = false;

        if ($request->request->get('keyword')) {
            $critere = [];
            $search_active = true;
            $keyword = $request->request->get('keyword');

            $incidents = $incidentRepo->searchByKeyWord($keyword);
        }

        /**
         * suppression
         */
        if ($id) {

            $incident = $incidentRepo->find($id);
            if ($incident) {
                $incident_text = $incident->getDateIncident()->format("d/m/Y") . " à " . $incident->getHeureDebut()->format("H:i") . ":" . $incident->getHeureFin()->format("H:i");
                $em->remove($incident);
                $em->flush();
                $this->addFlash("danger", "Incident du " . $incident_text . " a été bien effacée");
                return $this->redirectToRoute("incident_generale");
            }
        }

        $form = $this->createFormBuilder()
            ->add('date_incident', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
                "label" => "Date d'incident",
                "attr" => [
                    "readonly" => true
                ]

            ])
            ->add('heure_debut', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "label" => "Heure Début",
                "required" => false,
                "attr" => [
                    "placeholder" => "HH:MM"
                ]
            ])
            ->add('heure_fin', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "label" => "Heure Fin",
                "required" => false,
                "attr" => [
                    "placeholder" => "HH:MM"
                ]
            ])
            ->add('raison', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                "required" => false
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!preg_match('/(([0-1]){1,}([0-9]{1,})|(2[0-3]))(:)([0-5]{1}[0-9]{1})/', $data["heure_debut"]) || !preg_match('/(([0-1]){1,}([0-9]{1,})|(2[0-3]))(:)([0-5]{1}[0-9]{1})/', $data["heure_fin"]) || $data["heure_debut"] == "" || $data["heure_fin"] == "") {
                $this->addFlash("danger", "Format heure invalide");
                //return $this->redirectToRoute("incident_generale");
            } else if ($data["raison"] == "") {
                $this->addFlash("danger", "Raison obligatoire");
                //return $this->redirectToRoute("incident_generale");
            } else {

                $date_incident = implode('-', array_reverse(explode('/', $data["date_incident"])));

                $heure_debut = explode(":", $data["heure_debut"]);
                $heure_fin = explode(':', $data["heure_fin"]);
                //dd((new \DateTime($date_incident))->setTime($heure_debut[0], $heure_debut[1]));

                if (strtotime($data["heure_debut"]) > strtotime($data["heure_fin"])) {
                    $this->addFlash("danger", "Veuillez vérifiez l'heure de début et fin ");
                    return $this->redirectToRoute("incident_generale");
                }
                $incident = new \App\Entity\IncidentGeneral();
                $incident->setDateIncident(new \DateTime($date_incident))
                    ->setHeureDebut((new \DateTime($date_incident))->setTime($heure_debut[0], $heure_debut[1]))
                    ->setHeureFin((new \DateTime($date_incident))->setTime($heure_fin[0], $heure_fin[1]))
                    ->setRaison($data["raison"])
                    ->setInsererPar($this->getUser()->getUserDetails()["id_personnel"]);

                $em->persist($incident);
                $em->flush();

                $this->addFlash("success", "incident générale du " . $incident->getDateIncident()->format("d/m/Y") . " à " . $incident->getHeureDebut()->format("H:i:s") . ":" . $incident->getHeureFin()->format("H:i:s") . " a été bien enregistrée");
            }
            return $this->redirectToRoute("incident_generale");
        }
        if (!$search_active) {
            $incidents = $incidentRepo->incidentOrderBy();
        }
        //dump($incidents);

        return $this->render('dossier/incidentGenerale.html.twig', [
            "form" => $form->createView(),
            "incidents" => $incidents
        ]);
    }

    /**
     * 
     */
    public function evolutionTravail(Request $request, Connection $connexion)
    {

        $fichier = new \App\Model\GPAOModels\Fichiers($connexion);
        $labels = [
            "06:00:00", "07:00:00", "08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00",
            "17:00:00", "18:00:00", "19:00:00", "20:00:00"
        ];
        //dd($fichier->Get(["etape_travail.*"])->execute()->fetch());
        //$objQuery->innerJoin($this->table, $classExt->getTableName(),$classExt->getTableName(), $this->table.".".$field."=".$classExt->getTableName().".".$field );
        $prod = new \App\Model\GPAOModels\Production($connexion);
        $field_select = ["fichiers.nom_dossier"];
        $nom_dossiers = $prod->Get(
            $field_select

        )
            ->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
            ->where('date_traitement = :date_t')
            ->setParameter('date_t', (new \DateTime())->format("Y-m-d"))
            ->andWhere('heure_fin IS NOT NULL')

            ->execute()->fetchAll();


        $datas = [];
        $dossiers_name = [];


        foreach ($nom_dossiers as $dossier) {
            if (!in_array($dossier["nom_dossier"], $dossiers_name)) {
                $dossiers_name[$dossier["nom_dossier"]] = $dossier["nom_dossier"];
            }
        }

        $form = $this->createFormBuilder()
            ->add('dossier', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "choices" => $dossiers_name,
                "attr" => [
                    "class" => "form-control"
                ]
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() or ($request->query->get('call') && $request->query->get('call') == "python")) {
            $dossier_choice = $form->getData()["dossier"];
            /**
             * api 
             */
            if (!empty($request->query->get('dossier')) && $request->query->get('call') == "python") {
                $dossier_choice = $request->query->get('dossier');
            }

            $field_select = [];
            $field_select = [
                "fichiers.nom_dossier",
                "etape_travail.nom_etape",
                "heure_fin",
                //"id_etape_travail",
                //"fichiers.etape_travail",
                "production.incident",
                "production.temps_realisation",
                "production.volume",
                "etape_travail.objectif",
                "personnel.id_personnel"
            ];

            $nom_dossiers = $prod->Get($field_select)
                ->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                ->where('date_traitement = :date_t')
                ->setParameter('date_t', (new \DateTime())->format("Y-m-d"))
                ->andWhere('heure_fin IS NOT NULL')
                ->andWhere('fichiers.nom_dossier = :nom_d')
                ->setParameter("nom_d", $dossier_choice)
                /**
                     ->groupBy("fichiers.nom_dossier")
                     ->groupBy("etape_travail.nom_etape")
                     ->groupBy("heure_fin")
                 * 
                 */
                ->orderBy('heure_fin', 'ASC')
                ->execute()->fetchAll();
            //dd($nom_dossiers);
            foreach ($nom_dossiers as $dossier) {

                if (array_key_exists($dossier["nom_dossier"], $datas)) {
                    $isDataFind = false;

                    foreach ($datas as $key => $data) {
                        foreach ($data as $k => $d) {
                            if ($key == $dossier["nom_dossier"] && $k == $dossier["nom_etape"]) {

                                $isDataFind = true;
                                $array_heure = $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["duree_x"];
                                $array_volume = $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["vitesse_y"];

                                $array_heure[] = $dossier["heure_fin"];
                                $vitesse = 0;
                                if ($dossier["temps_realisation"] != 0) {
                                    //dump($dossier["temps_realisation"]);
                                    if ($dossier["temps_realisation"] - $dossier["incident"] == 0 || $dossier["temps_realisation"] - $dossier["incident"] < 0) {
                                        $vitesse = 0;
                                    } else {
                                        $vitesse = round($dossier["volume"] / ($dossier["temps_realisation"] - $dossier["incident"]), 2);
                                    }
                                }
                                if ($dossier["objectif"] != 0) {

                                    $vitesse = round($vitesse * 100 / $dossier["objectif"], 2);
                                }
                                //if($dossier["temps_realisation"] != 0){
                                /**
                                if($dossier["objectif"] != 0){
                                    $vitesse = round(100*$dossier["volume"]/$dossier["objectif"],2);
                                    /**
                                    if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                                        $vitesse = 0;
                                    }else{
                                        $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                                    }
                                }**/

                                $array_volume[] = $vitesse;

                                $datas[$key][$dossier["nom_etape"]]["duree_x"] = $array_heure;
                                $datas[$key][$dossier["nom_etape"]]["vitesse_y"] = $array_volume;
                                if (!in_array($dossier["id_personnel"], $datas[$key][$dossier["nom_etape"]]["matricule"])) {
                                    $datas[$key][$dossier["nom_etape"]]["matricule"][] = $dossier["id_personnel"];
                                    $datas[$key][$dossier["nom_etape"]]["effectif"] = count($datas[$key][$dossier["nom_etape"]]["matricule"]);
                                }
                                //$datas[$key][$dossier["nom_etape"]]["effectif"] = $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["effectif"] + 1;
                            }
                        }
                    }
                    if (!$isDataFind) {
                        $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["duree_x"][] = $dossier["heure_fin"];

                        $vitesse = 0;
                        if ($dossier["temps_realisation"] != 0) {
                            //dump($dossier["temps_realisation"]);
                            if ($dossier["temps_realisation"] - $dossier["incident"] == 0 || $dossier["temps_realisation"] - $dossier["incident"] < 0) {
                                $vitesse = 0;
                            } else {
                                $vitesse = round($dossier["volume"] / ($dossier["temps_realisation"] - $dossier["incident"]), 2);
                            }
                            //dump(round($dossier["volume"]/($dossier["temps_realisation"]-$dossier["incident"]), 2));
                            //dump($dossier["temps_realisation"], $vitesse);

                        }
                        if ($dossier["objectif"] != 0) {
                            //dump($dossier["heure_fin"], $vitesse);
                            $vitesse = round($vitesse * 100 / $dossier["objectif"], 2);
                        }
                        /**
                        if($dossier["objectif"] != 0){
                            $vitesse = round(100*$dossier["volume"]/$dossier["objectif"],2);
                            /**
                            if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                                $vitesse = 0;
                            }else{
                                $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                            }
                        }
                        /**
                        if($dossier["temps_realisation"] != 0){
                            if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                                $vitesse = 0;
                            }else{
                                $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                            }
                        }**/

                        $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["vitesse_y"][] = $vitesse;
                        $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["matricule"][] = $dossier["id_personnel"];
                        $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["effectif"] = count($datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["matricule"]);
                    }
                } else {
                    $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["duree_x"][] = $dossier["heure_fin"];

                    $vitesse = 0;
                    if ($dossier["temps_realisation"] != 0) {
                        //dump($dossier["temps_realisation"]);
                        if ($dossier["temps_realisation"] - $dossier["incident"] == 0 || $dossier["temps_realisation"] - $dossier["incident"] < 0) {
                            $vitesse = 0;
                        } else {
                            $vitesse = round($dossier["volume"] / ($dossier["temps_realisation"] - $dossier["incident"]), 2);
                        }
                        //dump(round($dossier["volume"]/($dossier["temps_realisation"]-$dossier["incident"]), 2));
                        //dump($dossier["temps_realisation"], $vitesse);

                    }
                    if ($dossier["objectif"] != 0) {
                        //dump($dossier["heure_fin"], $vitesse);
                        $vitesse = round($vitesse * 100 / $dossier["objectif"], 2);
                    }

                    /**
                    if($dossier["temps_realisation"] != 0){
                        if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                            $vitesse = 0;
                        }else{
                            $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                        }
                    }
                    if($dossier["objectif"] != 0){
                        $vitesse = round(100*$dossier["volume"]/$dossier["objectif"],2);
                        /**
                        if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                            $vitesse = 0;
                        }else{
                            $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                        }
                    }**/
                    $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["vitesse_y"][] = $vitesse;
                    $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["matricule"][] = $dossier["id_personnel"];
                    $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["effectif"] = count($datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["matricule"]);
                }
            }
        }


        /**
         * moyenne par etape
         */

        foreach ($datas as $nom_dossier => $data_dossier) {
            foreach ($data_dossier as $etape => $data_etape) {
                $data_y = [];
                $interval_begin = true;
                for ($i = 0, $j = $i + 1; $i <= count($labels), $j < count($labels); $i++, $j++) {
                    $date_begin = $labels[$i];
                    $date_fin = $labels[$j];
                    $data_vitesse = 0;
                    $divizer = 0;
                    /**
                        if($interval_begin){
                            $date_begin = "06:00:00";
                            $date_fin = "07:00:00";
                            $interval_begin = false;
                        }
                     **/
                    for ($h = 0; $h < count($data_etape["duree_x"]); $h++) {

                        if (
                            strtotime($data_etape["duree_x"][$h]) >= strtotime($date_begin)
                            && strtotime($data_etape["duree_x"][$h]) <= strtotime($date_fin)
                        ) {
                            //$is_in_interval_date = true;
                            $data_vitesse += $data_etape["vitesse_y"][$h];
                            $divizer++;
                            //dump($data_etape["duree_x"][$h]);
                            //dump($data_etape["vitesse_y"][$h]);
                        }
                    }
                    if ($divizer != 0) {
                        $data_y[] = round($data_vitesse / $divizer, 2);
                    } else {
                        $data_y[] = 0;
                    }
                }
                unset($data_y[count($data_y) - 1]);
                $datas[$nom_dossier][$etape]["vitesse_y"] = $data_y;
                unset($datas[$nom_dossier][$etape]["matricule"]);
            }
        }

        unset($labels[count($labels) - 1]);
        unset($labels[0]);
        $libele = [];
        foreach ($labels as $lab) {
            $libele[] = $lab;
        }

        if (($request->query->get("call") && $request->query->get('call') == "python")) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                "data" => $datas,
            ]);
        }

        dump($datas);
        return $this->render('dossier/evolutionTravail.html.twig', [
            "datas" => $datas,
            "form" => $form->createView(),
            "labels" => $libele
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function evolutionOperateur(Request $request, \App\Service\UserService $userService, \App\Service\ProductionService $prodService)
    {

        $fields = [
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.prenom",
            "personnel.nom_fonction"

        ];
        $query = $userService->getQueryWithFieldToSelect($fields);
        $query = $userService->orderByUser(["id_personnel" => "ASC"]);
        $users = $userService->fetchAllUser();
        $datas = [];
        $labels = [
            "06:00:00", "07:00:00", "08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00",
            "17:00:00", "18:00:00", "19:00:00", "20:00:00"
        ];
        $data_user = [];
        /**
         * data form
         */
        foreach ($users as $user) {
            $data_user[$user["id_personnel"] . " - " . $user["prenom"] . " " . $user["nom"]] = $user["id_personnel"];
        }

        $form = $this->createFormBuilder()
            ->add('user', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "choices" => $data_user,
                "label" => "Opérateur",
                'required' => false,
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data_form = $form->getData();
            $matricule = $data_form["user"];

            $field_prod = [

                "fichiers.nom_dossier",
                "etape_travail.nom_etape",
                "etape_travail.objectif",
                "heure_fin",

                //"id_etape_travail",
                //"fichiers.etape_travail",
                "production.incident",
                "production.temps_realisation",

                "production.volume",
                "personnel.id_personnel"

            ];
            $critere = [
                "personnel.id_personnel = :personnel" => $matricule,
            ];

            $query_prod = $prodService->getQueryWithCritere($field_prod, $critere);
            //$query_prod = $prodService->groupByProd("etape_travail.nom_etape");
            $prods = $query_prod->orderBy("heure_fin", "ASC")
                ->execute()->fetchAll();

            foreach ($prods as $dossier) {
                //dump($dossier["temps_realisation"]);
                if (array_key_exists($dossier["nom_dossier"], $datas)) {
                    $isDataFind = false;
                    foreach ($datas as $key => $data) {

                        foreach ($data as $k => $d) {
                            if ($key == $dossier["nom_dossier"] && $k == $dossier["nom_etape"]) {
                                $isDataFind = true;
                                $array_heure = $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["duree_x"];
                                $array_volume = $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["vitesse_y"];

                                $array_heure[] = $dossier["heure_fin"];
                                $vitesse = 0;
                                //dump($dossier["temps_realisation"]);
                                if ($dossier["temps_realisation"] != 0) {
                                    //dump($dossier["temps_realisation"]);
                                    if ($dossier["temps_realisation"] - $dossier["incident"] == 0 || $dossier["temps_realisation"] - $dossier["incident"] < 0) {
                                        $vitesse = 0;
                                    } else {
                                        $vitesse = round($dossier["volume"] / ($dossier["temps_realisation"] - $dossier["incident"]), 2);
                                    }
                                    //dump(round($dossier["volume"]/($dossier["temps_realisation"]-$dossier["incident"]), 2));
                                    //dump($dossier["temps_realisation"], $vitesse);

                                }
                                if ($dossier["objectif"] != 0) {
                                    //dump($dossier["heure_fin"], $vitesse);
                                    $vitesse = round($vitesse * 100 / $dossier["objectif"], 2);
                                }

                                /**
                                if($dossier["objectif"] != 0){
                                    //dump($dossier["volume"], $dossier["objectif"]);
                                    $vitesse = round(100*$dossier["volume"]/$dossier["objectif"],2);
                                    
                                    /**
                                    if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                                        $vitesse = 0;
                                    }else{
                                        $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                                    }
                                }**/
                                //dump($vitesse);
                                $array_volume[] = $vitesse;

                                $datas[$key][$dossier["nom_etape"]]["duree_x"] = $array_heure;
                                $datas[$key][$dossier["nom_etape"]]["vitesse_y"] = $array_volume;
                            }
                        }
                    }
                    if (!$isDataFind) {
                        $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["duree_x"][] = $dossier["heure_fin"];
                        $vitesse = 0;

                        if ($dossier["temps_realisation"] != 0) {
                            if ($dossier["temps_realisation"] - $dossier["incident"] == 0 || $dossier["temps_realisation"] - $dossier["incident"] < 0) {
                                $vitesse = 0;
                            } else {
                                $vitesse = round($dossier["volume"] / $dossier["temps_realisation"] - $dossier["incident"]);
                            }
                        }
                        if ($dossier["objectif"] != 0) {
                            $vitesse = round(100 * $dossier["volume"] / $dossier["objectif"], 2);
                            /**
                            if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                                $vitesse = 0;
                            }else{
                                $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                            }
                        }**/
                        }
                        $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["vitesse_y"][] = $vitesse;
                    }
                } else {
                    $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["duree_x"][] = $dossier["heure_fin"];
                    $vitesse = 0;
                    if ($dossier["temps_realisation"] != 0) {
                        //dump($dossier["temps_realisation"]);
                        if ($dossier["temps_realisation"] - $dossier["incident"] == 0 || $dossier["temps_realisation"] - $dossier["incident"] < 0) {
                            $vitesse = 0;
                        } else {
                            $vitesse = round($dossier["volume"] / ($dossier["temps_realisation"] - $dossier["incident"]), 2);
                        }
                        //dump(round($dossier["volume"]/($dossier["temps_realisation"]-$dossier["incident"]), 2));
                        //dump($dossier["temps_realisation"], $vitesse);

                    }
                    if ($dossier["objectif"] != 0) {
                        //dump($dossier["heure_fin"], $vitesse);
                        $vitesse = round($vitesse * 100 / $dossier["objectif"], 2);
                    }
                    /**
                    if($dossier["temps_realisation"] != 0){
                        if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                            $vitesse = 0;
                        }else{
                            $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                        }
                    }
                    if($dossier["objectif"] != 0){
                        $vitesse = round(100*$dossier["volume"]/$dossier["objectif"],2);
                        /**
                        if($dossier["temps_realisation"]-$dossier["incident"] == 0 || $dossier["temps_realisation"]-$dossier["incident"] < 0){
                            $vitesse = 0;
                        }else{
                            $vitesse = round($dossier["volume"]/$dossier["temps_realisation"]-$dossier["incident"]);
                        }
                    }**/
                    $datas[$dossier["nom_dossier"]][$dossier["nom_etape"]]["vitesse_y"][] = $vitesse;
                    //$datas[$dossier["nom_dossier"]]['etape'][] = $dossier["nom_etape"];
                }
            }
        }

        foreach ($datas as $nom_dossier => $data_dossier) {
            foreach ($data_dossier as $etape => $data_etape) {
                $data_y = [];
                $interval_begin = true;
                for ($i = 0, $j = $i + 1; $i <= count($labels), $j < count($labels); $i++, $j++) {
                    $date_begin = $labels[$i];
                    $date_fin = $labels[$j];
                    $data_vitesse = 0;
                    $divizer = 0;
                    /**
                        if($interval_begin){
                            $date_begin = "06:00:00";
                            $date_fin = "07:00:00";
                            $interval_begin = false;
                        }
                     **/
                    for ($h = 0; $h < count($data_etape["duree_x"]); $h++) {
                        if (
                            strtotime($data_etape["duree_x"][$h]) >= strtotime($date_begin)
                            && strtotime($data_etape["duree_x"][$h]) <= strtotime($date_fin)
                        ) {

                            //$is_in_interval_date = true;
                            $data_vitesse += $data_etape["vitesse_y"][$h];
                            $divizer++;
                            //dump($data_etape["duree_x"][$h]);
                            //dump($data_etape["vitesse_y"][$h]);
                        }
                    }

                    if ($divizer != 0) {
                        $data_y[] = round($data_vitesse / $divizer, 2);
                    } else {
                        $data_y[] = 0;
                    }
                }
                unset($data_y[count($data_y) - 1]);
                $datas[$nom_dossier][$etape]["vitesse_y"] = $data_y;

                /**
                $data_y = [];
                foreach($labels as $label){
                    $data = 0;
                     for($i=0; $i<count($data_etape["duree_x"]); $i++){
                        if(strtotime($data_etape["duree_x"][$i]) == strtotime($label)){
                            $data = $data_etape["vitesse_y"][$i];
                        }
                    }
                    if($data != 0){
                        $data_y[] = $data;
                    }else{
                        $data_y[] = 0;
                    }
                }
                $datas[$nom_dossier][$etape]["vitesse_y"] = $data_y;
            }
            $datas[$nom_dossier]["labels"] = $labels;
                 * 
                 */
            }
        }
        unset($labels[count($labels) - 1]);
        unset($labels[0]);
        $libele = [];
        foreach ($labels as $lab) {
            $libele[] = $lab;
        }
        return $this->render("dossier/evolutionPersonnel.html.twig", [
            "form" => $form->createView(),
            "datas" => $datas,
            "labels" => $libele
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function suiviRejet(Request $request, Connection $connexion)
    {
        $data_to_twig = [];
        $dates = [];
        $dossiers_name = [];
        $operateurs = [];
        $total_fichier_traite = 0;
        /**
         * liste des dossier en cours
         */
        $prod = new \App\Model\GPAOModels\Production($connexion);
        $field_select = ["date_traitement, production.etat,production.volume,personnel.nom, personnel.actif, personnel.prenom, personnel.id_personnel, personnel.nom_fonction, fichiers.nom_dossier"];
        $sql = $prod->Get(
            $field_select
        )
            ->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
            ->where('date_traitement = :date_t')
            ->setParameter('date_t', (new \DateTime())->format("Y-m-d"));

        $nom_dossiers = $sql->orderBy("nom_dossier", "ASC")->execute()->fetchAll();

        /**
         * liste des operateur actif
         */
        $pers = new \App\Model\GPAOModels\Personnel($connexion);
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where('actif = \'Oui\'')
            ->andWhere('id_personnel > 0')
            ->andWhere('nom_fonction IN (\'OP 1\',\'CORE 2\',\'CORE 1\',\'OP 2\')')
            ->orderBy('id_personnel', 'ASC')
            ->execute()->fetchAll();

        /**
         * personnel pour le choicetype
         */
        foreach ($personnels as $personnel) {
            if (!array_key_exists($personnel["id_personnel"] . " - " . $personnel["nom"] . " " . $personnel["prenom"], $operateurs)) {
                $operateurs[$personnel["id_personnel"] . " - " . $personnel["nom"] . " " . $personnel["prenom"]] = $personnel["id_personnel"];
            }
        }
        /**
         * dossier pour le choiceType
         */
        foreach ($nom_dossiers as $dossier) {

            if (!array_key_exists($dossier["nom_dossier"], $dossiers_name)) {
                $dossiers_name[$dossier["nom_dossier"]] = $dossier["nom_dossier"];
            }
        }
        /**
         * form search
         */
        $form = $this->createFormBuilder()
            ->add("dossier", \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => $dossiers_name
            ])
            ->add('operateur', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "choices" => $operateurs,
                "required" => false,
            ])
            ->add('date', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            $matricule = $data["operateur"];
            $dossier = $data["dossier"];
            $date = $data["date"];
            $dateisValid = TRUE;

            if (!empty($date)) {
                $interval_dates = explode(' - ', $date);
                if (count($interval_dates) == 1) {
                    $dates[] = $date;
                } else {
                    $dates[] = $interval_dates[0];
                    $dates[] = $interval_dates[1];
                }


                try {

                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[0]))));
                    //dd($dt);
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                }
                try {
                    //$dt = new \DateTime($dates[1]);
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[1]))));
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                }
            }

            $criteres = [];
            $sql_run = false;
            if (!empty($matricule)) {
                $criteres["personnel.id_personnel = :id"] = $matricule;
            }
            if (!empty($dossier)) {
                $criteres["fichiers.nom_dossier = :dossier"] = $dossier;
            }
            if (count($dates) > 0) {

                if (!$dateisValid) {
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("suivi_rejet");
                }
                $criteres["date_traitement BETWEEN :debut AND :fin"] = $dates;
            }

            if (count($criteres) == 1) {
                if (array_key_exists("personnel.id_personnel = :id", $criteres)) {
                    $this->addFlash("danger", "Veuillez renseigner le nom du dossier et la date");
                    return $this->redirectToRoute("suivi_rejet");
                } else {
                    $sql_run = true;
                }
            } else {
                //dd($criteres);
                if (count($criteres) > 1) {
                    $sql_run = true;
                } else {
                    $this->addFlash("danger", "Veuillez renseigner au moins le champ date");
                    return $this->redirectToRoute("suivi_rejet");
                }
            }

            if ($sql_run) {
                $sql2 = $prod->Get(
                    $field_select
                )
                    ->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail');
                foreach ($criteres as $keys => $critere) {
                    if ($keys == "date_traitement BETWEEN :debut AND :fin") {
                        $sql2->andWhere($keys);
                        //dd($critere);
                        //$dates = explode(' - ', $critere);
                        $sql2->setParameter("debut", $critere[0])
                            ->setParameter('fin', $critere[1]);
                    } else {
                        $sql2->andWhere($keys)
                            ->setParameter(explode(" = :", $keys)[1], $critere);
                    }
                }

                $datas = $sql2->orderBy("production.volume")
                    ->execute()->fetchAll();

                //dump($datas);
                foreach ($datas as $dt) {
                    /**
                     * filtrage des operateur
                     */
                    if (preg_match('/OP 1|CORE 2|CORE 1|OP 2/', $dt["nom_fonction"])) {
                        /**
                    if($dt["etat"] == "Rejet"){
                        if(!array_key_exists($dt["nom_dossier"], $data_to_twig)){
                            if($dt["volume"] != 0){
                                $data_to_twig[$dt["nom_dossier"]][$dt["id_personnel"]][] = [
                                    "id_personnel" => $dt["id_personnel"],
                                    "nom" => $dt["nom"]." ".$dt["prenom"],
                                    "nb_fichier_traite" => $dt["volume"],
                                    "nom_dossier" => $dt["nom_dossier"],
                                    "taux" => 100/$dt["volume"] 
                                ];
                            }
                        }
                        /**
                        $_data["id_personnel"] = $dt["id_personnel"];
                        $_data["nom"] = $dt["nom"]." ".$dt["prenom"];
                        $_data["nb_fichier_traite"] = $dt["volume"];
                        $_data["nom_dossier"] = $dt["nom_dossier"];
                         * 
                         *
                    }**/

                        $_data["id_personnel"] = $dt["id_personnel"];
                        $_data["nom"] = $dt["nom"] . " " . $dt["prenom"];
                        $_data["nb_fichier_traite"] = 1;
                        //$_data["nb_fichier_traite"] = $dt["volume"];

                        //$total_fichier_traite += 1;
                        if ($dt["etat"] == "Rejet") {
                            $_data["nb_rejet"] = 1;
                        } else {
                            $_data["nb_rejet"] = 0;
                        }


                        if (!array_key_exists($dt["id_personnel"], $data_to_twig)) {
                            $data_to_twig[$dt["id_personnel"]] = $_data;
                        } else {
                            foreach ($data_to_twig as $k => $dtwig) {
                                if ($k == $dt["id_personnel"]) {
                                    /**
                                     * volume
                                     */
                                    $volume = $data_to_twig[$k]["nb_fichier_traite"];
                                    $volume = $volume + $_data["nb_fichier_traite"];
                                    $data_to_twig[$k]["nb_fichier_traite"] = $volume;
                                    /**
                                     * nb rejet
                                     */
                                    $rejet = $data_to_twig[$k]["nb_rejet"];
                                    $data_to_twig[$k]["nb_rejet"] = $rejet + $_data["nb_rejet"];
                                    /**
                                     * taux
                                    
                                   $data_to_twig[$k]["taux"] = 100* $data_to_twig[$k]["nb_fichier_traite"]/ $data_to_twig[$k]["nb_fichier_traite"];
                                     * 
                                     */
                                }
                            }
                        }
                    }
                }
            }
        }

        $datas = [];
        if (count($data_to_twig) > 0) {

            foreach ($data_to_twig as $key => $data) {
                $volume = $data["nb_fichier_traite"];
                $rejet = $data["nb_rejet"];
                if ($volume != 0) {
                    $taux = round(100 * $rejet / $volume, 2);
                } else {
                    $taux = 0;
                }
                $data_to_twig[$key]["taux"] = $taux;

                $tab = [
                    "id_personnel" => $data["id_personnel"],
                    "nom" => $data["nom"],
                    "nb_fichier_traite" => $data["nb_fichier_traite"],
                    "nb_rejet" => $data["nb_rejet"],
                    "taux" => $taux
                ];

                $datas[] = $tab;
            }
        }
        if (count($datas) > 0) {
            usort($datas, array($this, "custom_sort"));
        }

        $total_fautes = 0;

        $taux_fautes_general = 0;
        //$ligne_trouve = 0;
        if (count($datas) > 0) {
            //$ligne_trouve = count($datas);
            foreach ($datas as $data) {
                $total_fautes += $data["nb_rejet"];
                $total_fichier_traite += $data["nb_fichier_traite"];
            }
            $taux_fautes_general = round(100 * $total_fautes / $total_fichier_traite, 2);
        }

        //dump($total_fautes);
        //dump($total_fichier_traite);
        //dump($taux_fautes_general);
        //dump(implode(' - ',$dates));
        return $this->render('dossier/suivi_rejet.html.twig', [
            "form" => $form->createView(),
            "datas" => $datas,
            "fautes" => $total_fautes,
            "fichier_traites" => $total_fichier_traite,
            "taux_general_fautes" => $taux_fautes_general,
            "date" => implode(' - ', $dates)
        ]);
    }

    /**
     * trie le tableau associatif
     * @param type $a
     * @param type $b
     * 
     */
    public function custom_sort($a, $b)
    {
        return $a['taux'] < $b['taux'];
    }
    public function custom_sortDate($a, $b)
    {
        return $a["date"] < $b["date"];
    }
    public function custom_sortExtra($a, $b)
    {
        return $a['matricule'] > $b['matricule'];
    }
    public function custom_sortHeureManquant($a, $b)
    {
        return $a['heure_manquant'] < $b['heure_manquant'];
    }

    public function custom_sortExtraEffectuez($a, $b)
    {
        return $a['nb_extra'] < $b['nb_extra'];
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function extra(Connection $connex, Request $request)
    {
        $search_actif = false;
        $data_to_twig = [];
        $operateurs = null;
        $dates = [];
        $criteres = [];
        $personnel_extras = [];

        $pers = new \App\Model\GPAOModels\Personnel($connex);
        $prod = new \App\Model\GPAOModels\Production($connex);
        $pointage = new \App\Model\GPAOModels\Pointage($connex);

        $dossier = new \App\Model\GPAOModels\Dossier($connex);
        $fichier = new \App\Model\GPAOModels\Fichiers($connex);
        /**
        dump($fichier->Get(
            ["dossier_client.*"]
        )//->innerJoin('fichiers','dossier','dossier','fichiers.nom_dossier = dossier.nom_dossier')
         ->execute()->fetch());
        dd($dossier->Get(
            
        )->execute()->fetch());
         **/
        /**
         * id_type_pointage extra
         */
        $sqlExtra = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description"])
            //->where("date_debut = :debut")
            //->setParameter("debut", date('Y-m-d'))
            ->where("description = :desc")
            ->setParameter("desc", "Extra");




        //dump($sqlExtra->execute()->fetchAll());
        /**
        dd($extra);
        
        //$extra = $extra["id_type_pointage"];
        dump($extra);
        $extra = 24;
         **/
        $sql = $prod->Get([

            "id_type_pointage",
            "personnel.id_personnel",
            "fichiers.nom_fichiers",
            "fichiers.nom_dossier",
            "personnel.nom",
            "personnel.prenom",
            "personnel.login",
            "nom_etape",
            "etape_travail.objectif",
            "production.volume",
            "temps_realisation",
            "etape_travail.prix",
            "production.etat",
            "production.heure_debut",
            "production.heure_fin",
            "dossier_client.facturable",
            "dossier_client.date_reel_livraison"
            //"fichiers.*"
            //"fichiers.facturable",
            //"dossier.*"
            //"fichiers.date_reel_livraison"


        ])->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
            ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier');
        //->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
        //->andWhere('nom_fonction IN(\'ACP\',\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
        //->where('date_traitement = :id_type')
        //->setParameter('id_type', date('Y-m-d'));
        //->setParameter('id_type', $extra);

        //dd($sql->execute()->fetch());
        /**
         * personnel choiceType
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'ACP\',\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }


        /**
         * form 
         */
        $form = $this->createFormBuilder()
            ->add('operateur', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => $operateurs,
            ])
            ->add('dates', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])
            ->add('isFacturable', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,

            ])->setAction($this->generateUrl("dossier_extra_v2"))->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            //dump("ok");
            $search_actif = true;
            $data = $form->getData();
            $matricule = $data["operateur"];
            $date = $data["dates"];
            $isFacturable = $data["isFacturable"];

            $dateisValid = TRUE;
            $isMatriculeEmpty = false;
            $isDossierFacturable = false;
            //$criteres = [];
            $sql_run = false;
            if (!empty($matricule)) {
                $criteres["personnel.id_personnel = :id"] = $matricule;
                $isMatriculeEmpty = true;
            }
            /**
            if($isFacturable){
                $criteres["dossier_client.facturable = :fact"] = 1;
                $isDossierFacturable = true;
            }else{
                $criteres["dossier_client.facturable = :fact"] = 0;
            }**/
            if (!empty($date)) {
                $interval_dates = explode(' - ', $date);
                if (count($interval_dates) == 1) {
                    $dates[] = $date;
                } else {
                    $dates[] = $interval_dates[0];
                    $dates[] = $interval_dates[1];
                }

                try {
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[0]))));
                    //dd($dt);
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }
                try {
                    //$dt = new \DateTime($dates[1]);
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[1]))));
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }

                $criteres["date_traitement BETWEEN :debut AND :fin"] = $dates;

                if (!$isMatriculeEmpty) {

                    $data_beetween_extra = $sqlExtra->andWhere("date_debut BETWEEN :debut AND :fin")
                        ->setParameter("debut", $dates[0])
                        ->setParameter("fin", $dates[1])
                        ->execute()->fetchAll();
                    /**
                     * s'il n'y pas de matricule à recherche, on cherche tous les employé qui ont fait 
                     * des extras DANS L'INTERVAL DE DATE, et puis on verifie que des employé fait des extra
                     */
                    if (count($data_beetween_extra) > 0) {
                        $list_matricule = " IN (";
                        foreach ($data_beetween_extra as $extra) {
                            $list_matricule .= $extra["id_personnel"] . ",";
                        }
                        $list_matricule = substr($list_matricule, 0, -1);
                        $list_matricule .= ")";

                        $criteres["personnel.id_personnel_list"] = $list_matricule;
                    }
                }

                $data_fact = 0;
                if ($isFacturable) {
                    //$data_facturable = $sqlExtra->andWhere('dossier.facturable = :fact')
                    //                            ->setParameter("fact", $isFacturable)
                    $data_fact = 1;
                }
                $criteres["dossier_client.facturable = :fact"] = $data_fact;
            }
            //dd($criteres);
        }

        $prods = null;
        $productions = [];
        $total_prix = 0;
        $personnel_extras = [];
        $extras = null;
        $nb_lignes = 0;
        $list_dossier_not_comptabilise = [];
        /**
         * search
         * tsis recherche natao par defaut
         */
        if (!$search_actif || count($criteres) == 0) {
            /**
             * extra aujourd'hui
             */
            $extras = $sqlExtra->andWhere('date_debut = :date_t')
                ->setParameter('date_t', date('Y-m-d'))
                ->orderBy("personnel.id_personnel", "ASC")
                ->execute()->fetchAll();

            /**
             * on fixe les heure qui ont fait des extras (s'il est matin heure entre=12:10:00 et sortie=18:30:00 et vice versa)
             */
            foreach ($extras as $key => $extra) {
                if ($extra["id_type_pointage"] == 1) {
                    //$heure_sortie = "18:30:00";
                    //$heure_entre = "12:10:00";
                    $personnel_extras[$extra["id_personnel"]]["heure_entre"] = "12:10:00";
                    $personnel_extras[$extra["id_personnel"]]["heure_sortie"] = "18:30:00";
                } else {
                    $personnel_extras[$extra["id_personnel"]]["heure_entre"] = "05:30:00";
                    $personnel_extras[$extra["id_personnel"]]["heure_sortie"] = "12:10:00";
                }

                $personnel_extras[$extra["id_personnel"]]["id_type_pointage"] = $extra["id_type_pointage"];
            }
            //dump($personnel_extras);
            /**
             * on stoque tous les productions des personnes qui ont fait d'extras aujourd'hui (defaut) sans recherche
             */
            foreach ($personnel_extras as $matr => $pers_extra) {
                /**
                $heure_sortie = $pers_extra["heure_sortie"];
                $heure_entre = null;
                if($pers_extra["id_type_pointage"] == 1){
                    if($heure_sortie === null){
                        $heure_sortie = "18:30:00";
                        $heure_entre = "12:10:00";
                    }
                }else{
                    if($heure_sortie === null){
                        $heure_sortie = "12:20:00";
                    }
                }
                dump($heure_sortie);
                 * 
                 */
                $prods[] = $sql->andWhere('date_traitement = :date_t')
                    ->setParameter('date_t', date('Y-m-d'))
                    ->andWhere('personnel.id_personnel = :id')
                    ->setParameter('id', $matr)
                    //->andWhere('production.heure_debut BETWEEN :debut AND :fin')
                    //->setParameter('debut', $pers_extra["heure_entre"])
                    //->setParameter('fin', $pers_extra["heure_entre"])//eto zo za
                    //->andWhere('production.heure_fin BETWEEN :h_debut AND :h_fin')
                    //->setParameter('h_debut', $heure_sortie)
                    //->setParameter('h_fin', $heure_sortie)
                    ->execute()->fetchAll();
                //dump($personnel_extras);
                //dd($prods);
            }
            //dump($personnel_extras);
            //dd($prods);
            /**
            foreach($personnel_extras as $matr => $pers_extra){
                foreach($prods as $p){
                    foreach($p as $prod){
                        if($prod["id_personnel"] == $matr){
                            //if(strtotime($prod["heure_debut"]) <= strtotime($heure_sortie)){
                            if(strtotime($prod["heure_debut"]) <= strtotime($pers_extra["heure_sortie"])){
                                //dump($prod);
                                if((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet"){
                                    if($prod["volume"] !== null || (float)$prod["volume"] != 0){
                                        //$prods[$key]["prix"] = $prod["volume"]*$prod["prix"];                                        
                                        //$prod["prix"] = $prix;

                                        $prix = $prod["volume"]*$prod["prix"];
                                        $total_prix += $prix;
                                        
                                        $matricule = $prod["id_personnel"];
                                        $login = $prod["login"];
                                        $dossier = $prod["nom_dossier"];
                                        $fichier = $prod["nom_fichiers"];
                                        $etape = $prod["nom_etape"];
                                        $volume = $prod["volume"];
                                        $temps = $prod["temps_realisation"];
                                        $taux = null;
                                        
                                        if((float)$prod["temps_realisation"] != 0){   
                                            $vitesse = $prod["volume"]/$prod["temps_realisation"];
                                            $objectif = $prod["objectif"] == 0?0:$prod["objectif"];
                                            if($objectif != 0){
                                                $taux = $vitesse*100/$objectif;
                                            }
                                            //$prods[$key]["taux"] = $taux;
                                        }else{
                                            $taux = 0;
                                        }
                                        
                                        
                                        $productions[] = [
                                            "matricule" => $matricule,
                                            "login" => $login,
                                            "dossier" => $dossier,
                                            "etape" => $etape,
                                            "volume" => $volume,
                                            "temps" => $temps,
                                            "prix" => $prix,
                                            "taux" => $taux,
                                            "fichier" => $fichier
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }**/

            //dd($productions);     
        } else {
            /**
             * eto za zo!
             */

            /**
             * extra aujourd'hui
             
            $extras = $sqlExtra->andWhere('date_debut = :date_t')
                                ->setParameter('date_t', date('Y-m-d'))
                                ->orderBy("personnel.id_personnel","ASC")
                                ->execute()->fetchAll();
             * 
             */
            foreach ($criteres as $keys => $critere) {
                if ($keys == "personnel.id_personnel = :id") {
                    $sql->andWhere($keys)
                        ->setParameter("id", $critere);

                    $sqlExtra->andWhere($keys)
                        ->setParameter("id", $critere);
                }
                if ($keys == "personnel.id_personnel_list") {
                    $sql->andWhere('personnel.id_personnel ' . $critere);
                    //dd('personnel.id_personnel '.$critere);
                    $sqlExtra->andWhere('personnel.id_personnel ' . $critere);
                }
                if ($keys == "dossier_client.facturable = :fact") {
                    /*
                     * si is facture decoche, on fait rien
                     */
                    if ($critere != 0) {
                        $sql->andWhere($keys)
                            ->setParameter("fact", $critere);
                    }
                }
                if ($keys == "date_traitement BETWEEN :debut AND :fin") {
                    $sql->andWhere($keys)
                        ->setParameter('debut', $critere[0])
                        ->setParameter('fin', $critere[1]);

                    $critere_field = str_replace("date_traitement", "date_debut", $keys);

                    $sqlExtra->andWhere($critere_field)
                        ->setParameter('debut', $critere[0])
                        ->setParameter("fin", $critere[1]);
                }
            }

            $extras = $sqlExtra->orderBy('personnel.id_personnel', 'ASC')
                ->execute()->fetchAll();

            $prods[] = $sql->orderBy('personnel.id_personnel', 'ASC')
                ->execute()->fetchAll();

            /**
             * fixena ilay heure_entre sy sortie ny extra (raha piasa maren iz dia ny extra heure apres midi dia vice versa) 
             */
            foreach ($extras as $key => $extra) {
                if ($extra["id_type_pointage"] == 1) {
                    //$heure_sortie = "18:30:00";
                    //$heure_entre = "12:10:00";
                    $personnel_extras[$extra["id_personnel"]]["heure_entre"] = "12:10:00";
                    $personnel_extras[$extra["id_personnel"]]["heure_sortie"] = "18:30:00";
                } else {
                    $personnel_extras[$extra["id_personnel"]]["heure_entre"] = "05:30:00";
                    $personnel_extras[$extra["id_personnel"]]["heure_sortie"] = "12:10:00";
                }

                $personnel_extras[$extra["id_personnel"]]["id_type_pointage"] = $extra["id_type_pointage"];
            }
            //dump($personnel_extras);
            //dd($prods);
        }
        //dump($personnel_extras);
        //dd($prods);
        $total_facturable = 0;
        foreach ($personnel_extras as $matr => $pers_extra) {
            foreach ($prods as $p) {
                foreach ($p as $prod) {

                    if ($prod["id_personnel"] == $matr) {
                        //$heure_fin = null;
                        //if($prod["id_type_pointage"] == 24){
                        //$heure_fin = strtotime($pers_extra["heure_sortie"]);
                        //}else{
                        //$heure_fin = strtotime($pers_extra["heure_debut"]);
                        //}
                        //if(strtotime($prod["heure_debut"]) <= strtotime($heure_sortie) /**&& strtotime($pers_extra["heure_entre"] <= strtotime($prod["heure_fin"]))**/){
                        if ($prod["heure_fin"] !== null && strtotime($prod["heure_debut"]) >= strtotime($pers_extra["heure_entre"]) && strtotime($prod["heure_fin"]) <= strtotime($pers_extra["heure_sortie"])) {
                            //if(strtotime($prod["heure_debut"]) <= strtotime($pers_extra["heure_sortie"]) && strtotime($prod["heure_fin"]) > strtotime($pers_extra["heure_entre"])){
                            //dump($prod);
                            if ((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet") {
                                if ($prod["volume"] !== null || (float)$prod["volume"] != 0) {
                                    //$prods[$key]["prix"] = $prod["volume"]*$prod["prix"];                                        
                                    //$prod["prix"] = $prix;


                                    $etat = $prod["etat"];
                                    $matricule = $prod["id_personnel"];
                                    $login = $prod["login"];
                                    $dossier = $prod["nom_dossier"];
                                    $fichier = $prod["nom_fichiers"];
                                    $etape = $prod["nom_etape"];
                                    $volume = $prod["volume"];
                                    $temps = $prod["temps_realisation"];
                                    $prix_unitaire = $prod["prix"];
                                    $facturable = $prod["facturable"];

                                    if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE/', $prod["nom_etape"])) {
                                        $prix_unitaire = 0;
                                    }
                                    $prix = $prod["volume"] * $prix_unitaire;
                                    $total_prix += $prix;
                                    $taux = null;

                                    if ((float)$prod["temps_realisation"] != 0) {
                                        $vitesse = $prod["volume"] / $prod["temps_realisation"];
                                        $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                        if ($objectif != 0) {
                                            $taux = $vitesse * 100 / $objectif;
                                        }
                                        //$prods[$key]["taux"] = $taux;
                                    } else {
                                        $taux = 0;
                                    }


                                    $productions[] = [
                                        "etat" => $etat,
                                        "matricule" => $matricule,
                                        "login" => $login,
                                        "dossier" => $dossier,
                                        "etape" => $etape,
                                        "volume" => $volume,
                                        "temps" => $temps,
                                        "prix" => $prix,
                                        "taux" => $taux,
                                        "fichier" => $fichier,
                                        "prix_unitaire" => $prix_unitaire,
                                        "facturable" => $facturable
                                    ];
                                    $nb_lignes += 1;
                                    /**
                                     * dossier facturable
                                     */
                                    if ($prod["facturable"] != 0) {
                                        $total_facturable += $prix;
                                    }
                                    //dump($prod);
                                }
                            } else {
                                if ($prod["prix"] == 0) {
                                    $list_dossier_not_comptabilise[] = $prod["nom_dossier"];
                                }
                                if ((float)$prod["temps_realisation"] != 0) {
                                    $vitesse = $prod["volume"] / $prod["temps_realisation"];
                                    $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                    if ($objectif != 0) {
                                        $taux = $vitesse * 100 / $objectif;
                                    }
                                    //$prods[$key]["taux"] = $taux;
                                } else {
                                    $taux = 0;
                                }
                                $productions[] = [
                                    "etat" => $prod["etat"],
                                    "matricule" => $prod["id_personnel"],
                                    "login" => $prod["login"],
                                    "dossier" => $prod["nom_dossier"],
                                    "etape" => $prod["nom_fichiers"],
                                    "volume" => $prod["volume"],
                                    "temps" => $prod["temps_realisation"],
                                    "prix" => $prod["volume"] * $prod["prix"],
                                    "taux" => $taux,
                                    "fichier" => $prod["nom_fichiers"],
                                    "prix_unitaire" => $prod["prix"],
                                    "facturable" => $prod["facturable"]
                                ];
                                $nb_lignes += 1;
                            }
                        }
                    }
                }
            }
            //}

        }

        //$prods = $sql->orderBy("personnel.id_personnel","ASC")
        //           ->execute()->fetch();

        //$total_prix = 0;
        //$productions = [];

        //dd($prods);
        /**
        if(count($prods) > 0){
            foreach($prods as $key => $prod){
                if((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet"){
                    if($prod["volume"] !== null || (float)$prod["volume"] != 0){
                        //$prods[$key]["prix"] = $prod["volume"]*$prod["prix"];
                        $prix = $prod["volume"]*$prod["prix"];
                        $prod["prix"] = $prix;
                        
                        $total_prix += $prod["prix"];
                        
                        if((float)$prod["temps_realisation"] != 0){   
                            $vitesse = $prod["volume"]/$prod["temps_realisation"];
                            $objectif = $prod["objectif"] == 0?0:$prod["objectif"];
                            if($objectif != 0){
                                $taux = $vitesse*100/$objectif;
                            }
                            $prods[$key]["taux"] = $taux;
                        }else{
                            $prods[$key]["taux"] = 0;
                        }
                        $productions[] = $prod;
                    }
                }else{
                    /**
         * eto commentain ref vita tsara le iz
                    
                    $perm = [];
                    if(!array_key_exists("taux", $prod)){
                        $perm = $prod;
                        if((float)$prod["temps_realisation"] != 0){   
                            $vitesse = $prod["volume"]/$prod["temps_realisation"];
                            $objectif = $prod["objectif"] == 0?0:$prod["objectif"];
                            if($objectif != 0){
                                $taux = $vitesse*100/$objectif;
                            }
                            $perm["taux"] = $taux;
                        }else{
                            $perm["taux"] = 0;
                        }
                        //$perm["taux"] = 0;
                    }
                    $productions[] = $perm;
                }
                //dump((int)$prod["temps_realisation"]);
            }
        }**/

        //dump($nb_lignes);
        //dump($productions);
        //$list_dossier_not_comptabilise = array_unique($list_dossier_not_comptabilise);
        //dump($list_dossier_not_comptabilise);
        //dump($total_facturable);    
        return $this->render('dossier/extra.html.twig', [
            "form" => $form->createView(),
            "prods" => $productions,
            "date" => implode(' - ', $dates),
            "total_prix" => $total_prix,
            "nb_lignes" => $nb_lignes,
            "total_facturable" => $total_facturable
            //"dossier_not_price" => $list_dossier_not_comptabilise
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function extraV1(Connection $connex, Request $request)
    {
        $operateurs = [];
        $prods = [];
        $extras = [];
        $productions = [];
        $personnel_extras = [];
        $list_dossier_no_comptabilise = [];
        $list_dossier_comptabilise = [];
        $list_dossier_prix_pas_indique = [];
        $total_prix = 0;
        $total_facturable = 0;
        $nb_lignes = 0;
        $name_excel = "";
        //$dates = [];
        $pointageNormal = false;
        $taux_reussite_general = 0;

        $pers = new \App\Model\GPAOModels\Personnel($connex);
        $prod = new \App\Model\GPAOModels\Production($connex);
        $pointage = new \App\Model\GPAOModels\Pointage($connex);
        $exportToExcel = false;
        $list_personnel = []; //list contenant tous les extras

        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }

        /**
         * form 
         */

        $form = $this->createFormBuilder(null, [
            "method" => "POST",
            "action" => $this->generateUrl("dossier_extra_v2"),
            "attr" => []
        ])
            ->add('operateur', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => $operateurs,
            ])
            ->add('dates', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])
            ->add('export', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,
            ])
            ->add('isFacturable', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,

            ])
            ->add('type_pointage', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => [
                    "Matin" => 1,
                    "APM" => 24
                ]
            ])
            ->add('pointage_normal', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false
            ])->getForm();

        /**
         * submit form
         */
        $form->handleRequest($request);
        if ($form->isSubmitted()) {


            //test jimmy
            //            $getP = $prod->Get([
            //                "personnel.id_type_pointage",
            //                "personnel.id_personnel",
            //                "type_pointage.description as type_pointage",
            //                "fichiers.nom_fichiers",
            //                "fichiers.nom_dossier",
            //                "personnel.nom",
            //                "personnel.prenom",
            //                "personnel.login",
            //                "etape_travail.nom_etape",
            //                "etape_travail.objectif",
            //                "production.volume",
            //                "temps_realisation",
            //                "etape_travail.prix",
            //                "production.etat",
            //                "production.heure_debut",
            //                "production.heure_fin",
            //                "dossier_client.facturable",
            //                "dossier_client.date_reel_livraison",
            //                "production.date_traitement"
            //            
            //            ])
            //                ->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
            //                ->innerJoin('fichiers','dossier_client','dossier_client','fichiers.nom_dossier = dossier_client.nom_dossier')
            //                ->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')
            //                    
            //                ->where('production.date_traitement BETWEEN :d AND :d2')
            //                ->setParameter('d','16/04/2021')
            //                ->setParameter('d2','17/04/2021')
            //                
            //                ->andWhere('personnel.id_personnel in (select pointage.id_personnel from pointage,type_pointage where type_pointage.id_type_pointage=pointage.id_type_pointage and type_pointage.description = :extra and pointage.date_debut between :db1 and :db2)')
            //                ->setParameter('extra','Extra')
            //                ->setParameter('db1','16/04/2021')
            //                ->setParameter('db2','17/04/2021')
            //                    
            //                ->andWhere('((type_pointage.description LIKE :type1 and (production.heure_debut>:htype1_1 and production.heure_debut<:htype1_2)) or (type_pointage.description LIKE :type2 and (production.heure_debut>:htype2_1 and production.heure_debut<:htype2_2)))')
            //                
            //                    ->setParameter('type1','Après-midi%')
            //                    ->setParameter('htype1_1','06:00:00')
            //                    ->setParameter('htype1_2','12:20:00')
            //                    ->setParameter('type2','Matin%')
            //                    ->setParameter('htype2_1','11:20:00')
            //                    ->setParameter('htype2_2','18:30:00')
            //                    
            //                //->setMaxResults(500)
            //                ->execute()->fetchAll();
            //          
            //            dump($getP);
            //            
            //            return new Response("<html><body>TEST JIMMY</body></html>");

            /**
             * sql prod
             */

            $sqlProd = $prod->Get([
                "personnel.id_type_pointage",
                "personnel.id_personnel",
                "type_pointage.description as type_pointage",
                "fichiers.nom_fichiers",
                "fichiers.nom_dossier",
                "personnel.nom",
                "personnel.prenom",
                "personnel.login",
                "etape_travail.nom_etape",
                "etape_travail.objectif",
                "production.volume",
                "production.temps_realisation",
                "etape_travail.prix",
                "production.etat",
                "production.heure_debut",
                "production.heure_fin",
                "dossier_client.facturable",
                "dossier_client.date_reel_livraison",
                "production.date_traitement"

            ])->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail and dossier_client.nom_dossier=fichiers.nom_dossier');
            $sqlDateLivr = $sqlProd;


            /**
             * sql extra
             * id_type_pointage extra
             */
            $sqlExtra = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description", "date_debut"])
                //->where("date_debut = :debut")
                //->setParameter("debut", date('Y-m-d'))
                ->where("description = :desc")
                ->setParameter("desc", "Extra")
                ->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')'); //napiko anty
            //->andWhere('personnel.nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\', \'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')'); ito no iz aveo

            $search_actif = true;
            $data = $form->getData();

            $matricule = $data["operateur"];
            $date = $data["dates"];
            $isFacturable = $data["isFacturable"];
            $exportToExcel = $data["export"];
            $pointageNormal = $data["pointage_normal"];
            $typepointage = $data["type_pointage"];

            $dateisValid = TRUE;
            $isMatriculeEmpty = false;
            $isDossierFacturable = false;
            //$criteres = [];
            $sql_run = false;
            //$exportToExcel = true;

            $dateFiltre = [];

            //filtrage-ny date oe valide v
            if (!empty($date)) {
                $interval_dates = explode(' - ', $date);
                if (count($interval_dates) == 1) {
                    $dates[] = $date;
                } else {
                    $dates[] = $interval_dates[0];
                    $dates[] = $interval_dates[1];
                }

                try {
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[0]))));
                    //dd($dt);
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }
                try {
                    //$dt = new \DateTime($dates[1]);
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[1]))));
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }

                $dateFiltre = $interval_dates;
                $name_excel = "/extra" . date('YmdHis') . "" . str_replace("/", "", implode("", $interval_dates)) . ".xlsx";
            }

            if (count($dateFiltre) > 0) {
                $intervalDateFact = $this->getIntervalDateInCompte($dateFiltre[0], $dateFiltre[1]);

                //dd($this->getIntervalDateInCompte((new \DateTime())->format("d/m/Y"), (new \DateTime())->format("d/m/Y")));
                /**
                    $extras = $sqlProd->andWhere('dossier_client.date_reel_livraison BETWEEN :db AND :df')
                                     ->setParameter('db', $intervalDateFact["dateDebutCompte"])
                                     ->setParameter('df', $intervalDateFact["dateFinCompte"])
                                     ->andWhere('type_pointage.description = :typeP')
                                     ->setParameter('typeP', 'Extra')
                                     ->execute()->fetchAll();
                    dd($extras);
                 * 
                 */
                //filtre date
                $sqlProd->andWhere('dossier_client.date_reel_livraison BETWEEN :db AND :df')
                    ->setParameter('db', $intervalDateFact["dateDebutCompte"])
                    ->setParameter('df', $intervalDateFact["dateFinCompte"])
                    ->orWhere('date_traitement BETWEEN :d AND :f')
                    ->setParameter('d', $dateFiltre[0])
                    ->setParameter('f', $dateFiltre[1]);
                /**
                            ->andWhere('date_reel_livraison BETWEEN :db AND :df')
                            ->setParameter('db', $intervalDateFact["dateDebutCompte"])
                            ->setParameter('df', $intervalDateFact["dateFinCompte"]);
                 **/

                $sqlExtra->andWhere("date_debut BETWEEN :debut AND :fin")
                    //->setParameter("debut", $dateFiltre[0])
                    //->setParameter("fin", $dateFiltre[1]);
                    ->setParameter('debut', $intervalDateFact["dateDebutCompte"])
                    ->setParameter('fin', $intervalDateFact["dateFinCompte"]);
            }

            //raha ho an'olo-tokana
            if (!empty($matricule)) {
                $sqlProd->andWhere('personnel.id_personnel = :id')
                    ->setParameter('id', $matricule);
                //$criteres["personnel.id_personnel = :id"] = $matricule;


                $sqlExtra->andWhere('personnel.id_personnel = :id')
                    ->setParameter('id', $matricule);

                $isMatriculeEmpty = true;
            } else {

                if (!$pointageNormal) {

                    $sqlProd->andWhere('personnel.id_personnel IN (SELECT pointage.id_personnel as id_personnel FROM pointage,type_pointage WHERE pointage.id_type_pointage=type_pointage.id_type_pointage AND type_pointage.description = :typeP)')
                        ->setParameter('typeP', 'Extra')
                        //->setParameter('dp1',$dateFiltre[0])
                        //->setParameter('dp2',$dateFiltre[1])
                        ->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')'); //napiko anty
                    //->andWhere('personnel.nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')'); ito no iz aveo

                    /**
                    $sqlProd->andWhere('((type_pointage.id_type_pointage = :type1 and (production.heure_debut>=:htype1_1 and production.heure_debut<=:htype1_2)) or (type_pointage.id_type_pointage = :type2 and (production.heure_debut>=:htype2_1 and production.heure_debut<=:htype2_2)))')
                            ->setParameter('type1',24)
                            ->setParameter('htype1_1','05:30:00')
                            ->setParameter('htype1_2','12:20:00')
                            ->setParameter('type2',1)
                            ->setParameter('htype2_1','11:10:00')
                            ->setParameter('htype2_2','18:30:00');
                    
                     **/
                } else {
                    dump("makote ve? heure norme");
                    $sqlProd->andWhere('((type_pointage.id_type_pointage = :type1 and (production.heure_debut>=:htype1_1 and production.heure_debut<=:htype1_2)) or (type_pointage.id_type_pointage = :type2 and (production.heure_debut>=:htype2_1 and production.heure_debut<=:htype2_2)))')
                        ->setParameter('type1', 24)
                        ->setParameter('htype1_1', '12:20:00')
                        ->setParameter('htype1_2', '18:30:00')
                        ->setParameter('type2', 1)
                        ->setParameter('htype2_1', '06:00:00')
                        ->setParameter('htype2_2', '12:20:00');

                    $sqlProd->andWhere('etape_travail.nom_etape !=  \'VALIDATION_ECHANT\'');
                    if (!$isMatriculeEmpty) {
                        $sqlProd->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')'); //nesoriko ilay acp

                        //$sqlProd->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\', \'CORE 2\',\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')'); //ito no iz aveo
                    }
                }
            }

            if ($isFacturable) {
                //$criteres["dossier_client.facturable = :fact"] = 1;
                $sqlProd->andWhere('dossier_client.facturable = :fact')
                    ->setParameter('fact', 1);
            }
            if (!empty($typepointage)) {
                $sqlProd->andWhere('personnel.id_type_pointage = :pointage')
                    ->setParameter('pointage', $typepointage);

                $sqlExtra->andWhere('personnel.id_type_pointage = :point')
                    ->setParameter('point', $typepointage);
            }


            $extras = $sqlExtra->orderBy('personnel.id_personnel', 'ASC')
                ->orderBy('date_debut', "ASC")
                ->execute()->fetchAll();

            /**
             * afaan maka ny extra ao @production cles matricule, valeur => [date=>heure]
             */
            foreach ($extras as $extra) {
                if (!array_key_exists($extra["id_personnel"], $list_personnel)) {
                    $list_personnel[$extra["id_personnel"]][$extra["date_debut"]] = [
                        "heure_entre" => $extra["heure_entre"],
                        "heure_sortie" => $extra["heure_sortie"],
                        "id_type_pointage" => $extra["id_type_pointage"]
                    ];
                } else {
                    $date_extra = $list_personnel[$extra["id_personnel"]];
                    $date_and_hours = [
                        $extra["date_debut"] => [
                            "heure_entre" => $extra["heure_entre"],
                            "heure_sortie" => $extra["heure_sortie"],
                            "id_type_pointage" => $extra["id_type_pointage"]
                        ]
                    ];
                    $list_personnel[$extra["id_personnel"]] = array_merge($date_extra, $date_and_hours);
                }
            }

            foreach ($list_personnel as $matricule => $dates) {
                $date_begin = "";
                $hr = "";
                $dateB = false;
                foreach ($dates as $date => $heures) {
                    if (!$dateB) {
                        $date_begin = $date;
                        $hr = $heures;
                        $dateB = true;
                    }
                }
                $list_personnel[$matricule]["0000-00-00"] = $date_begin;
                //$list_personnel[$matricule]["0000-00-00|$date_begin"] = $hr[0]."-".$hr[1];//ito aveo
            }
            //dd($list_personnel);

            /**
             * maka ny allaitement, ilain @ilay production tany aloha compte
             */
            $list_allaitement = [];
            foreach ($list_personnel as $matricule => $data) {
                foreach ($data as $dates => $hres) {
                    if (is_array($hres)) {
                        if (strtotime($hres["heure_entre"]) > strtotime("11:10:00") && strtotime($hres["heure_entre"]) < strtotime("11:15:00")) {
                            if (!in_array($matricule, $list_allaitement)) {
                                $list_allaitement[] = $matricule;
                            }
                        }
                    }
                }
            }


            $prods = $sqlProd->orderBy('production.id_production', 'ASC')
                ->orderBy("personnel.id_personnel", 'ASC')

                //->orderBy('type_pointage.description','ASC')
                ->execute()->fetchAll();


            //return new Response('<html><body>{{dump($prods)}}</body></html>');
        }

        /**
         * exportation excel
         * preparation des outils utiles pour excel
         */
        $nomFichier = "";
        if ($exportToExcel) {
            $dirPiece = $this->getParameter('app.temp_dir');
            $nomFichier = $dirPiece . "" . $name_excel;


            $headers = [
                "Matricule", "Login", "Date du traitement", "Heure début", "heure fin", "Dossier", "Fichier", "Etat", "Etape", "Volume", "Temps", "Taux", "Prix Unitaire", "Coût", "Facturable", "Date reel livraison"
            ];

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($nomFichier); // write data to a file or to a PHP stream
            $cells = WriterEntityFactory::createRowFromArray($headers);
            $writer->addRow($cells);
        }

        /**
         * maka ny olona izay nanw extra ao @ productions
         */

        if (!$pointageNormal) {
            //dump("EXTRA");



            foreach ($prods as $prod) {
                if (preg_match('/2021-05-10|2021-05-11/', $prod["date_traitement"])) {
                    dump($prod);
                }
                $userFound = false;

                foreach ($list_personnel as $matricule => $dates) {
                    if ($prod["id_personnel"] == $matricule) {
                        $userFound = true;
                        $datePremier = "";
                        $dateBegin = true;

                        foreach ($dates as $date => $pers_extra) {
                            if ($date == $prod["date_traitement"]) {
                                if (is_array($pers_extra)) {

                                    //if($prod["heure_fin"] !== null && strtotime($prod["heure_debut"]) >= strtotime($pers_extra["heure_entre"]) && strtotime($prod["heure_fin"]) <= strtotime($pers_extra["heure_sortie"])){
                                    if (($prod["id_type_pointage"] == 24 && $prod["heure_fin"] !== null && strtotime($prod["heure_debut"]) <= strtotime($pers_extra["heure_sortie"]))
                                        || $prod["id_type_pointage"] == 1 && strtotime($prod["heure_debut"]) >= strtotime($pers_extra["heure_entre"])
                                    ) {
                                        $taux = null;
                                        if ((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet") {
                                            if ($prod["volume"] !== null || (float)$prod["volume"] != 0) {
                                                //$prods[$key]["prix"] = $prod["volume"]*$prod["prix"];                                        
                                                //$prod["prix"] = $prix;


                                                $etat = $prod["etat"];
                                                $matricule = $prod["id_personnel"];
                                                $login = $prod["login"];
                                                $dossier = $prod["nom_dossier"];
                                                $fichier = $prod["nom_fichiers"];
                                                $etape = $prod["nom_etape"];
                                                $volume = $prod["volume"];
                                                $temps = $prod["temps_realisation"];
                                                $prix_unitaire = $prod["prix"];
                                                $facturable = $prod["facturable"];
                                                $date_livraison = $prod["date_reel_livraison"];

                                                if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE/', $prod["nom_etape"])) {
                                                    $prix_unitaire = 0;
                                                }
                                                $prix = $prod["volume"] * $prix_unitaire;
                                                $total_prix += $prix;
                                                //$taux = null;

                                                if ((float)$prod["temps_realisation"] != 0) {
                                                    $vitesse = $prod["volume"] / $prod["temps_realisation"];
                                                    $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                                    if ($objectif != 0) {
                                                        $taux = $vitesse * 100 / $objectif;
                                                    } else {
                                                        $taux = 0;
                                                    }
                                                    //$prods[$key]["taux"] = $taux;
                                                } else {
                                                    $taux = 0;
                                                }

                                                $taux_reussite_general += $taux;

                                                $productions[] = [
                                                    "matricule" => $matricule,
                                                    "login" => $login,
                                                    "heure_debut" => $prod["heure_debut"],
                                                    "heure_fin" => $prod["heure_fin"],
                                                    "date_traitement" => $prod["date_traitement"],
                                                    "dossier" => $dossier,
                                                    "fichier" => $fichier,
                                                    "etat" => $etat,
                                                    "etape" => $etape,
                                                    "volume" => $volume,
                                                    "temps" => $temps,
                                                    "taux" => $taux,
                                                    "prix_unitaire" => $prix_unitaire,
                                                    "prix" => $prix,
                                                    "facturable" => $facturable,
                                                    "date_livraison" => $date_livraison
                                                ];
                                                $nb_lignes += 1;
                                                if ($prod["prix"] == 0) {
                                                    if (!in_array($prod["nom_dossier"], $list_dossier_no_comptabilise)) {
                                                        if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                            $list_dossier_no_comptabilise[] = $prod["nom_dossier"];
                                                        }
                                                    }
                                                } else {
                                                    if (!in_array($prod["nom_dossier"], $list_dossier_comptabilise)) {
                                                        $list_dossier_comptabilise[] = $prod["nom_dossier"];
                                                    }
                                                }
                                                /**
                                                 * dossier facturable
                                                 */

                                                if ($prod["facturable"] != 0) {
                                                    $intervalDateFacturable = $this->getIntervalDateInCompte($dateFiltre[0], $dateFiltre[1]);
                                                    //dd($intervalDateFacturable);

                                                    if ((strtotime($intervalDateFacturable["dateDebutCompte"]) <= strtotime($prod["date_reel_livraison"]))
                                                        && (strtotime($prod["date_reel_livraison"]) <= strtotime($intervalDateFacturable["dateFinCompte"]))
                                                    ) {

                                                        $total_facturable += $prix;
                                                    }
                                                }
                                            }
                                        } else {


                                            if ((float)$prod["temps_realisation"] != 0) {
                                                $vitesse = round($prod["volume"] / $prod["temps_realisation"], 2);
                                                $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                                if ($objectif != 0) {
                                                    $taux = $vitesse * 100 / $objectif;
                                                } else {
                                                    $taux = 0;
                                                }
                                                //$prods[$key]["taux"] = $taux;
                                            } else {
                                                $taux = 0;
                                            }
                                            $taux_reussite_general += $taux;
                                            $prix_unitaire = 0;
                                            if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE|CQ_DECOUPE/', $prod["nom_etape"])) {
                                                $prix_unitaire = 0;
                                            }
                                            $productions[] = [
                                                "matricule" => $prod["id_personnel"],
                                                "login" => $prod["login"],
                                                "heure_debut" => $prod["heure_debut"],
                                                "heure_fin" => $prod["heure_fin"],
                                                "dossier" => $prod["nom_dossier"],
                                                "date_traitement" => $prod["date_traitement"],
                                                "fichier" => $prod["nom_fichiers"],
                                                "etat" => $prod["etat"],
                                                "etape" => $prod["nom_etape"],
                                                "volume" => $prod["volume"],
                                                "temps" => $prod["temps_realisation"],
                                                "taux" => $taux,
                                                //"prix_unitaire" => $prod["prix"],
                                                "prix_unitaire" => $prix_unitaire,
                                                "prix" => $prod["volume"] * $prod["prix"],
                                                "facturable" => $prod["facturable"],
                                                "date_livraison" => $prod["date_reel_livraison"]
                                            ];
                                            $nb_lignes += 1;
                                            if ($prod["prix"] == 0) {
                                                if ($prod["facturable"] != 0) {
                                                    if (!array_key_exists($prod["nom_dossier"], $list_dossier_prix_pas_indique)) {
                                                        if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                            $list_dossier_prix_pas_indique[$prod["nom_dossier"]] = [$prod["nom_etape"]];
                                                        }
                                                    } else {
                                                        $list_etape = $list_dossier_prix_pas_indique[$prod["nom_dossier"]];
                                                        if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                            if (!in_array($prod["nom_etape"], $list_etape)) {
                                                                $list_etape = array_merge($list_etape, [$prod["nom_etape"]]);
                                                            }
                                                            $list_dossier_prix_pas_indique[$prod["nom_dossier"]] = $list_etape;
                                                        }
                                                    }
                                                }
                                                if (!in_array($prod["nom_dossier"], $list_dossier_no_comptabilise)) {
                                                    if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                        $list_dossier_no_comptabilise[] = $prod["nom_dossier"];
                                                    }
                                                }
                                            } else {
                                                if (!in_array($prod["nom_dossier"], $list_dossier_comptabilise)) {
                                                    $list_dossier_comptabilise[] = $prod["nom_dossier"];
                                                }
                                            }
                                        }
                                        /**
                                         * exportation excel
                                         * recuperation des donnees
                                         */
                                        if ($exportToExcel) {
                                            $fact = "En attente";
                                            if ($prod["facturable"] == 1) {
                                                $fact = "OK";
                                            }
                                            $cells = WriterEntityFactory::createRowFromArray([
                                                $prod["id_personnel"],
                                                ucwords($prod["login"]),
                                                $prod["date_traitement"],
                                                $prod["heure_debut"],
                                                $prod["heure_fin"],
                                                $prod["nom_dossier"],
                                                $prod["nom_fichiers"],
                                                $prod["etat"],
                                                $prod["nom_etape"],
                                                $prod["volume"],
                                                $prod["temps_realisation"],
                                                $taux,
                                                //$prod["prix"],
                                                $prix_unitaire,
                                                $prod["volume"] * $prod["prix"],
                                                $fact,
                                                $prod["date_reel_livraison"]
                                                //$prod["facturable"]
                                            ]);
                                            $writer->addRow($cells);
                                        }
                                    }
                                }
                            } else {
                                //$list_personnel[$matricule]["0000-00-00|$date_begin"] = $hr[0]."-".$hr[1];//ito aveo
                                if (is_string($pers_extra)) {
                                    //$date_b = explode('|', $date)[1];ireto no iz
                                    //$heure_debut = explode('-',$pers_extra)[0];//ito ku no iz
                                    //ato no maka ny production extra ka ny date de livraison tsy ao anatin ilay interval de date
                                    //if($prod["date_traitement"] < $date_b){//ireto no iz
                                    if ($prod["date_traitement"] < $pers_extra) {

                                        /**
                                         * ireto miala dul aveo
                                         */
                                        $heure_debut = "12:10:00";
                                        if ($prod["id_type_pointage"] == 1) {
                                            if (in_array($prod["id_personnel"], $list_allaitement)) {
                                                $heure_debut = "11:10:00";
                                            } else {
                                                $heure_debut = "12:15:00";
                                            }
                                        }
                                        //ka atreto

                                        if (($prod["id_type_pointage"] == 24 && $prod["heure_fin"] !== null && strtotime($prod["heure_debut"]) <= strtotime($heure_debut))
                                            || $prod["id_type_pointage"] == 1 && strtotime($prod["heure_debut"]) >= strtotime($heure_debut)
                                        ) {
                                            $taux = null;

                                            if ((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet") {
                                                if ($prod["volume"] !== null || (float)$prod["volume"] != 0) {
                                                    //$prods[$key]["prix"] = $prod["volume"]*$prod["prix"];                                        
                                                    //$prod["prix"] = $prix;


                                                    $etat = $prod["etat"];
                                                    $matricule = $prod["id_personnel"];
                                                    $login = $prod["login"];
                                                    $dossier = $prod["nom_dossier"];
                                                    $fichier = $prod["nom_fichiers"];
                                                    $etape = $prod["nom_etape"];
                                                    $volume = $prod["volume"];
                                                    $temps = $prod["temps_realisation"];
                                                    $prix_unitaire = $prod["prix"];
                                                    $facturable = $prod["facturable"];

                                                    if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE/', $prod["nom_etape"])) {
                                                        $prix_unitaire = 0;
                                                    }
                                                    $prix = $prod["volume"] * $prix_unitaire;
                                                    $total_prix += $prix;
                                                    //$taux = null;

                                                    if ((float)$prod["temps_realisation"] != 0) {
                                                        $vitesse = $prod["volume"] / $prod["temps_realisation"];
                                                        $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                                        if ($objectif != 0) {
                                                            $taux = $vitesse * 100 / $objectif;
                                                        } else {
                                                            $taux = 0;
                                                        }
                                                        //$prods[$key]["taux"] = $taux;
                                                    } else {
                                                        $taux = 0;
                                                    }

                                                    $taux_reussite_general += $taux;

                                                    $productions[] = [
                                                        "matricule" => $matricule,
                                                        "login" => $login,
                                                        "heure_debut" => $prod["heure_debut"],
                                                        "heure_fin" => $prod["heure_fin"],
                                                        "date_traitement" => $prod["date_traitement"],
                                                        "dossier" => $dossier,
                                                        "fichier" => $fichier,
                                                        "etat" => $etat,
                                                        "etape" => $etape,
                                                        "volume" => $volume,
                                                        "temps" => $temps,
                                                        "taux" => $taux,
                                                        "prix_unitaire" => $prix_unitaire,
                                                        "prix" => $prix,
                                                        "facturable" => $facturable,
                                                        "date_livraison" => $prod["date_reel_livraison"]
                                                    ];
                                                    $nb_lignes += 1;
                                                    /**
                                                     * filtre des dossier comptablise et non comptablise
                                                     */
                                                    if ($prod["prix"] == 0) {
                                                        if (!in_array($prod["nom_dossier"], $list_dossier_no_comptabilise)) {
                                                            if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                                $list_dossier_no_comptabilise[] = $prod["nom_dossier"];
                                                            }
                                                        }
                                                    } else {
                                                        if (!in_array($prod["nom_dossier"], $list_dossier_comptabilise)) {
                                                            $list_dossier_comptabilise[] = $prod["nom_dossier"];
                                                        }
                                                    }

                                                    /**
                                                     * dossier facturable
                                                     */

                                                    if ($prod["facturable"] != 0) {
                                                        $intervalDateFacturable = $this->getIntervalDateInCompte($dateFiltre[0], $dateFiltre[1]);
                                                        //dd($intervalDateFacturable);
                                                        if ($prod["prix"] == 0) {
                                                        }

                                                        if ((strtotime($intervalDateFacturable["dateDebutCompte"]) <= strtotime($prod["date_reel_livraison"]))
                                                            && (strtotime($prod["date_reel_livraison"]) <= strtotime($intervalDateFacturable["dateFinCompte"]))
                                                        ) {

                                                            $total_facturable += $prix;
                                                        }
                                                    }
                                                    //dump($prod);
                                                    //dd($productions);
                                                }
                                            } else {


                                                if ((float)$prod["temps_realisation"] != 0) {
                                                    $vitesse = round($prod["volume"] / $prod["temps_realisation"], 2);
                                                    $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                                    if ($objectif != 0) {
                                                        $taux = $vitesse * 100 / $objectif;
                                                        //dump($vitesse);
                                                    } else {
                                                        $taux = 0;
                                                    }
                                                    //$prods[$key]["taux"] = $taux;
                                                } else {
                                                    $taux = 0;
                                                }
                                                $taux_reussite_general += $taux;
                                                $prix_unitaire = 0;
                                                if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE|CQ_DECOUPE/', $prod["nom_etape"])) {
                                                    $prix_unitaire = 0;
                                                }
                                                $productions[] = [
                                                    "matricule" => $prod["id_personnel"],
                                                    "login" => $prod["login"],
                                                    "heure_debut" => $prod["heure_debut"],
                                                    "heure_fin" => $prod["heure_fin"],
                                                    "dossier" => $prod["nom_dossier"],
                                                    "date_traitement" => $prod["date_traitement"],
                                                    "fichier" => $prod["nom_fichiers"],
                                                    "etat" => $prod["etat"],
                                                    "etape" => $prod["nom_etape"],
                                                    "volume" => $prod["volume"],
                                                    "temps" => $prod["temps_realisation"],
                                                    "taux" => $taux,
                                                    //"prix_unitaire" => $prod["prix"],
                                                    "prix_unitaire" => $prix_unitaire,
                                                    "prix" => $prod["volume"] * $prod["prix"],
                                                    "facturable" => $prod["facturable"],
                                                    "date_livraison" => $prod["date_reel_livraison"]

                                                ];
                                                $nb_lignes += 1;
                                                if ($prod["prix"] == 0) {
                                                    /**
                                                     * list dossier tsis prix nef ef facturable
                                                     */
                                                    if ($prod["facturable"] != 0) {
                                                        if (!array_key_exists($prod["nom_dossier"], $list_dossier_prix_pas_indique)) {
                                                            if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                                $list_dossier_prix_pas_indique[$prod["nom_dossier"]] = [$prod["nom_etape"]];
                                                            }
                                                        } else {
                                                            $list_etape = $list_dossier_prix_pas_indique[$prod["nom_dossier"]];
                                                            if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                                if (!in_array($prod["nom_etape"], $list_etape)) {
                                                                    $list_etape = array_merge($list_etape, [$prod["nom_etape"]]);
                                                                }
                                                                $list_dossier_prix_pas_indique[$prod["nom_dossier"]] = $list_etape;
                                                            }
                                                        }
                                                    }
                                                    /**
                                                     * list des dossier comptabilise et non comptabilisé
                                                     */
                                                    if (!in_array($prod["nom_dossier"], $list_dossier_no_comptabilise)) {
                                                        if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                            $list_dossier_no_comptabilise[] = $prod["nom_dossier"];
                                                        }
                                                    } else {
                                                        if (!in_array($prod["nom_dossier"], $list_dossier_comptabilise)) {
                                                            $list_dossier_comptabilise[] = $prod["nom_dossier"];
                                                        }
                                                    }
                                                }
                                            }
                                            /**
                                             * exportation excel
                                             * recuperation des donnees
                                             */
                                            if ($exportToExcel) {
                                                $fact = "En attente";
                                                if ($prod["facturable"] == 1) {
                                                    $fact = "OK";
                                                }
                                                $cells = WriterEntityFactory::createRowFromArray([
                                                    $prod["id_personnel"],
                                                    ucwords($prod["login"]),
                                                    $prod["date_traitement"],
                                                    $prod["heure_debut"],
                                                    $prod["heure_fin"],
                                                    $prod["nom_dossier"],
                                                    $prod["nom_fichiers"],
                                                    $prod["etat"],
                                                    $prod["nom_etape"],
                                                    $prod["volume"],
                                                    $prod["temps_realisation"],
                                                    $taux,
                                                    //$prod["prix"],
                                                    $prix_unitaire,
                                                    $prod["volume"] * $prod["prix"],
                                                    $fact,
                                                    $prod["date_reel_livraison"]
                                                    //$prod["facturable"]
                                                ]);
                                                $writer->addRow($cells);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (!$userFound) {

                    //eto zo za
                    //raha tsy ita nanao pointage tao @ interval de date nef tafiditra tao anatin date_reel_livraison
                    if (($prod["id_type_pointage"] == 24 && $prod["heure_fin"] !== null && strtotime($prod["heure_debut"]) <= strtotime("12:10:00"))
                        || $prod["id_type_pointage"] == 1 && strtotime($prod["heure_debut"]) >= strtotime("12:20:00")
                    ) {

                        $taux = null;
                        if ((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet") {
                            if ($prod["volume"] !== null || (float)$prod["volume"] != 0) {
                                //$prods[$key]["prix"] = $prod["volume"]*$prod["prix"];                                        
                                //$prod["prix"] = $prix;


                                $etat = $prod["etat"];
                                $matricule = $prod["id_personnel"];
                                $login = $prod["login"];
                                $dossier = $prod["nom_dossier"];
                                $fichier = $prod["nom_fichiers"];
                                $etape = $prod["nom_etape"];
                                $volume = $prod["volume"];
                                $temps = $prod["temps_realisation"];
                                $prix_unitaire = $prod["prix"];
                                $facturable = $prod["facturable"];

                                if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE/', $prod["nom_etape"])) {
                                    $prix_unitaire = 0;
                                }
                                $prix = $prod["volume"] * $prix_unitaire;
                                $total_prix += $prix;
                                //$taux = null;

                                if ((float)$prod["temps_realisation"] != 0) {
                                    $vitesse = $prod["volume"] / $prod["temps_realisation"];
                                    $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                    if ($objectif != 0) {
                                        $taux = $vitesse * 100 / $objectif;
                                    } else {
                                        $taux = 0;
                                    }
                                    //$prods[$key]["taux"] = $taux;
                                } else {
                                    $taux = 0;
                                }

                                $taux_reussite_general += $taux;

                                $productions[] = [
                                    "matricule" => $matricule,
                                    "login" => $login,
                                    "heure_debut" => $prod["heure_debut"],
                                    "heure_fin" => $prod["heure_fin"],
                                    "date_traitement" => $prod["date_traitement"],
                                    "dossier" => $dossier,
                                    "fichier" => $fichier,
                                    "etat" => $etat,
                                    "etape" => $etape,
                                    "volume" => $volume,
                                    "temps" => $temps,
                                    "taux" => $taux,
                                    "prix_unitaire" => $prix_unitaire,
                                    "prix" => $prix,
                                    "facturable" => $facturable,
                                    "date_livraison" => $prod["date_reel_livraison"]
                                ];
                                $nb_lignes += 1;
                                /**
                                 * lister tous les dossier comptablise et non comptabilise
                                 */
                                if ($prod["prix"] == 0) {
                                    if (!in_array($prod["nom_dossier"], $list_dossier_no_comptabilise)) {
                                        if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                            $list_dossier_no_comptabilise[] = $prod["nom_dossier"];
                                        }
                                    }
                                } else {
                                    if (!in_array($prod["nom_dossier"], $list_dossier_comptabilise)) {
                                        $list_dossier_comptabilise[] = $prod["nom_dossier"];
                                    }
                                }
                                /**
                                 * dossier facturable
                                 */

                                if ($prod["facturable"] != 0) {
                                    $intervalDateFacturable = $this->getIntervalDateInCompte($dateFiltre[0], $dateFiltre[1]);
                                    //dd($intervalDateFacturable);
                                    if ($prod["prix"] == 0) {
                                        if (!array_key_exists($prod["nom_dossier"], $list_dossier_prix_pas_indique)) {
                                            if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                $list_dossier_prix_pas_indique[$prod["nom_dossier"]] = [$prod["nom_etape"]];
                                            }
                                        } else {
                                            $list_etape = $list_dossier_prix_pas_indique[$prod["nom_dossier"]];
                                            if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                                if (!in_array($prod["nom_etape"], $list_etape)) {
                                                    $list_etape = array_merge($list_etape, [$prod["nom_etape"]]);
                                                }
                                                $list_dossier_prix_pas_indique[$prod["nom_dossier"]] = $list_etape;
                                            }
                                        }
                                    }
                                    if ((strtotime($intervalDateFacturable["dateDebutCompte"]) <= strtotime($prod["date_reel_livraison"]))
                                        && (strtotime($prod["date_reel_livraison"]) <= strtotime($intervalDateFacturable["dateFinCompte"]))
                                    ) {

                                        $total_facturable += $prix;
                                    }
                                }
                                //dump($prod);
                                //dd($productions);
                            }
                        } else {


                            if ((float)$prod["temps_realisation"] != 0) {
                                $vitesse = round($prod["volume"] / $prod["temps_realisation"], 2);
                                $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                if ($objectif != 0) {
                                    $taux = $vitesse * 100 / $objectif;
                                    //dump($vitesse);
                                } else {
                                    $taux = 0;
                                }
                                //$prods[$key]["taux"] = $taux;
                            } else {
                                $taux = 0;
                            }
                            $taux_reussite_general += $taux;
                            $prix_unitaire = 0;
                            if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE|CQ_DECOUPE/', $prod["nom_etape"])) {
                                $prix_unitaire = 0;
                            }
                            $productions[] = [
                                "matricule" => $prod["id_personnel"],
                                "login" => $prod["login"],
                                "heure_debut" => $prod["heure_debut"],
                                "heure_fin" => $prod["heure_fin"],
                                "dossier" => $prod["nom_dossier"],
                                "date_traitement" => $prod["date_traitement"],
                                "fichier" => $prod["nom_fichiers"],
                                "etat" => $prod["etat"],
                                "etape" => $prod["nom_etape"],
                                "volume" => $prod["volume"],
                                "temps" => $prod["temps_realisation"],
                                "taux" => $taux,
                                //"prix_unitaire" => $prod["prix"],
                                "prix_unitaire" => $prix_unitaire,
                                "prix" => $prod["volume"] * $prod["prix"],
                                "facturable" => $prod["facturable"],
                                "date_livraison" => $prod["date_reel_livraison"]
                            ];
                            $nb_lignes += 1;
                            if ($prod["prix"] == 0) {
                                if (!in_array($prod["nom_dossier"], $list_dossier_no_comptabilise)) {
                                    if (!preg_match('/SUBDIVISION|PREPARATION|DECOUPE|VALIDATION_ECHANT/', $prod["nom_etape"])) {
                                        $list_dossier_no_comptabilise[] = $prod["nom_dossier"];
                                    }
                                }
                            } else {
                                if (!in_array($prod["nom_dossier"], $list_dossier_comptabilise)) {
                                    $list_dossier_comptabilise[] = $prod["nom_dossier"];
                                }
                            }
                        }
                        /**
                         * exportation excel
                         * recuperation des donnees
                         */
                        if ($exportToExcel) {
                            $fact = "En attente";
                            if ($prod["facturable"] == 1) {
                                $fact = "OK";
                            }
                            $cells = WriterEntityFactory::createRowFromArray([
                                $prod["id_personnel"],
                                ucwords($prod["login"]),
                                $prod["date_traitement"],
                                $prod["heure_debut"],
                                $prod["heure_fin"],
                                $prod["nom_dossier"],
                                $prod["nom_fichiers"],
                                $prod["etat"],
                                $prod["nom_etape"],
                                $prod["volume"],
                                $prod["temps_realisation"],
                                $taux,
                                //$prod["prix"],
                                $prix_unitaire,
                                $prod["volume"] * $prod["prix"],
                                $fact,
                                $prod["date_reel_livraison"]
                                //$prod["facturable"]
                            ]);
                            $writer->addRow($cells);
                        }
                    }
                }
            }
        } else { //production normal

            foreach ($prods as $prod) {
                $heure_entre_normal = strtotime("06:00:00");
                $heure_sortie_normal = strtotime("12:10:00");
                if ($prod["id_type_pointage"] == 24) {
                    $heure_entre_normal = strtotime("12:20:00");
                    $heure_sortie_normal = strtotime("18:30:00");
                }
                if (strtotime($prod["heure_debut"]) >= $heure_entre_normal && strtotime($prod["heure_fin"]) <= $heure_sortie_normal) {
                    if ((float)$prod["temps_realisation"] != 0) {
                        $vitesse = (float)$prod["volume"] / $prod["temps_realisation"];
                        $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                        if ($objectif != 0) {
                            $taux = $vitesse * 100 / $objectif;

                            //$prods[$key]["taux"] = $taux;
                        } else {
                            $taux = 0;
                        }

                        if ($prod["nom_etape"] == "VALIDATION_ECHANT") {
                            $taux = 0;
                        }
                        $taux_reussite_general += $taux;
                        $productions[] = [
                            "matricule" => $prod["id_personnel"],
                            "login" => $prod["login"],
                            "heure_debut" => $prod["heure_debut"],
                            "heure_fin" => $prod["heure_fin"],
                            "dossier" => $prod["nom_dossier"],
                            "date_traitement" => $prod["date_traitement"],
                            "fichier" => $prod["nom_fichiers"],
                            "etat" => $prod["etat"],
                            "etape" => $prod["nom_etape"],
                            "volume" => $prod["volume"],
                            "temps" => $prod["temps_realisation"],
                            "taux" => $taux,
                            "prix_unitaire" => $prod["prix"],
                            "prix" => $prod["volume"] * $prod["prix"],
                            "facturable" => $prod["facturable"]
                        ];
                        //dump($productions);
                        $nb_lignes++;

                        /**
                         * exportation excel
                         * recuperation des donnees
                         **/
                        if ($exportToExcel) {
                            $fact = "En attente";
                            if ($prod["facturable"] == 1) {
                                $fact = "OK";
                            }
                            $cells = WriterEntityFactory::createRowFromArray([
                                $prod["id_personnel"],
                                ucwords($prod["login"]),
                                $prod["date_traitement"],
                                $prod["heure_debut"],
                                $prod["heure_fin"],
                                $prod["nom_dossier"],
                                $prod["nom_fichiers"],
                                $prod["etat"],
                                $prod["nom_etape"],
                                $prod["volume"],
                                $prod["temps_realisation"],
                                $taux,
                                $prod["prix"],
                                $prod["volume"] * $prod["prix"],
                                $fact
                                //$prod["facturable"]
                            ]);
                            $writer->addRow($cells);
                        }
                    }
                }
            }
        }

        if (count($productions) > 0) {
            $taux_reussite_general = round($taux_reussite_general / $nb_lignes, 2);
            foreach ($productions as $product) {
                if (strtotime($product["heure_debut"]) < strtotime("12:10:00")) {
                    dump($product);
                }
            }
            //usort($productions, array($this,"custom_sortExtra"));
            /**
             * fermeture exportation excel
             */
            if ($exportToExcel) {
                $writer->close();
            }
            //dump($list_dossier_comptabilise);
            //dump($list_dossier_prix_pas_indique);
            //return new Response('<html><body>Arrivé</body></html');

        }


        return $this->render('dossier/extraV1.html.twig', [
            "form" => $form->createView(),
            "prods" => $productions,
            //"date" => implode(' - ', $dates),
            "total_prix" => $total_prix,
            "nb_lignes" => $nb_lignes,
            "total_facturable" => $total_facturable,
            "excel" => [
                "isExportToExcel" => $exportToExcel,
                "fileName" => $name_excel
            ],
            "taux_generale" => $taux_reussite_general,
            "comptablise" => $list_dossier_comptabilise,
            "nonComptablise" => $list_dossier_no_comptabilise,
            "dossierComptabilisePasPrix" => $list_dossier_prix_pas_indique
        ]);
    }

    private function getIntervalDateInCompte(string $dateDebut, string $dateFin)
    {
        $dateDebut = implode('-', array_reverse(explode('/', $dateDebut)));
        $dateFin = implode('-', array_reverse(explode('/', $dateFin)));

        $daySearch = (int)(new \DateTime($dateDebut))->format("d");
        $monthBegin = null;
        $yearBegin = (new \DateTime($dateDebut))->format("Y");

        if ($daySearch > 20) {
            $monthBegin = (new \DateTime($dateDebut))->format("m");
        } else {
            $monthBegin = (new \DateTime($dateDebut))->sub(new \DateInterval('P1M'))->format("m");
        }
        $dateDebutCompte = "21-" . $monthBegin . "-" . $yearBegin;

        $daySearchEnd = (int)(new \DateTime($dateFin))->format("d");
        $monthEndCompte = (new \DateTime($dateFin))->format("m");
        $yearEndCompte = (new \DateTime($dateFin))->format("Y");

        if ($daySearchEnd > 20) {
            $monthEndCompte = (new \DateTime($dateFin))->add(new \DateInterval('P1M'))->format("m");
        }

        //$dateFin = new \DateTime($dateFin);
        $dateFinCompte = "20-" . $monthEndCompte . "-" . $yearEndCompte;

        //dump($dateDebutCompte);
        //dd($dateFinCompte);
        return [
            "dateDebutCompte" => $dateDebutCompte,
            "dateFinCompte" => $dateFinCompte
        ];
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function suiviExtra(Connection $connex, Request $request)
    {
        $operateurs = [];
        $datas = [];
        $productions = [];
        $pers = new Personnel($connex);
        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom",
            "nom_fonction"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }
        $form = $this->createFormBuilder()
            ->add('matricule', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "required" => false,
                "choices" =>  $operateurs,
                "placeholder" => "-Selectionnez-"
            ])
            ->add('heure_extra_manquant', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,

            ])
            ->add('nombre_extra_effectie', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,
            ])
            ->add('absence_extra', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false
            ])
            ->add('interval_date_extra', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

            $sqlDemandeExtras = $demandeSupplementaire->Get([
                //"personnel.id_personnel",
                //"date_suplementaire",
                //"heure_debut_supplementaire",
                //"heure_fin_supplementaire",
                "demande_supplementaire.*",
                "type_pointage.id_type_pointage",
                "personnel.id_personnel",
                "personnel.nom",
                "personnel.login",
                "personnel.nom_fonction"

            ])
                ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('demande_supplementaire.etat_validation = :etat')
                ->setParameter('etat', "Accorder")
                ->andWhere('personnel.nom_fonction != :nom_fonction')
                ->setParameter('nom_fonction', "ACP 1");
            //->orderBy('date_suplementaire','ASC')
            //->orderBy('personnel.id_personnel')
            //->execute()->fetchAll();

            //dd($demandes);


            $search_begin = false;
            $critere_search = [];

            $form_data = $form->getData();
            $matricule = $form_data["matricule"];
            $personnel_absent_extra = $form_data["absence_extra"];
            $interval_date_extra = $form_data["interval_date_extra"];
            $nombre_extra_fait = $form_data["nombre_extra_effectie"];
            $heure_extra_manquant = $form_data["heure_extra_manquant"];

            $date_empty = true;
            $critere_to_search["matricule"] = false;
            $critere_to_search["absence_extra"] = false;
            $critere_to_search["heure_extra_manquant"] = false;
            $critere_to_search["nombre_extra_effectue"] = false;
            $matriculeEmpty = true;

            if (!empty($interval_date_extra)) {

                if ((new \DateTime(implode('-', array_reverse(explode('/', explode(' - ', $interval_date_extra)[0])))))->getTimestamp() < (new \DateTime("2021-04-01"))->getTimestamp()) {
                    $this->addFlash("danger", "Date du début de l'extra doit être superieur ou egale à 01/04/2021");
                    return $this->redirectToRoute("dossier_suivi_extra");
                }

                $critere_to_search["date_interval"] = $interval_date_extra;
                if (!empty($matricule)) {
                    $matriculeEmpty = false;
                    //$search_begin = true;
                    $critere_to_search["matricule"]["actif"] = true;
                    $critere_to_search["matricule"]["id_personnel"] = $matricule;

                    $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                }
                /**
                else{
                    $sqlDemandeExtras->where('demande_supplementaire.date_suplementaire BETWEEN :debut AND :fin')
                                     ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                                     ->setParameter('fin', explode(' - ', $interval_date_extra)[1]);
                }**/

                //if($personnel_absent_extra){
                //    $search_begin = true;
                $critere_to_search["absence_extra"] = true;
                //}
                //if($heure_extra_manquant){
                $search_begin = true;
                $critere_to_search["heure_extra_manquant"] = true;

                //}
                //if($nombre_extra_fait){
                //    $search_begin = true;
                $critere_to_search["nombre_extra_effectue"] = true;

                //}

                if ($search_begin) {
                    $demandeExtras = [];
                    $demandes_extras = $sqlDemandeExtras->andWhere('demande_supplementaire.date_suplementaire BETWEEN :debut AND :fin')
                        ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                        ->setParameter('fin', explode(' - ', $interval_date_extra)[1])
                        ->orderBy('date_suplementaire', 'ASC')
                        ->orderBy('personnel.id_personnel')
                        ->execute()->fetchAll();
                    //dump($demandes_extras);

                    foreach ($demandes_extras as $extra) {
                        if ($extra["nom_fonction"] != "Directeur de Plateau") {
                            $demandeExtras[] = $extra;
                        }
                    }

                    if (count($demandeExtras) > 0) {
                        $pointage = new \App\Model\GPAOModels\Pointage($connex);
                        $sqlPointageExtra = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description", "date_debut", "personnel.nom", "personnel.login"])
                            //->where("date_debut = :debut")
                            //->setParameter("debut", date('Y-m-d'))
                            ->where("description = :desc")
                            ->setParameter("desc", "Extra");
                        if (!$matriculeEmpty) {
                            $sqlPointageExtra->andWhere('personnel.id_personnel = :id_personnel')
                                ->setParameter('id_personnel', $matricule);
                        }
                        $pointageExtras = $sqlPointageExtra->andWhere('date_debut BETWEEN :debut AND :fin')
                            ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                            ->setParameter('fin', explode(' - ', $interval_date_extra)[1])

                            ->orderBy('personnel.id_personnel')->execute()->fetchAll();

                        //$critere_search["date_traitement BETWEEN :debut AND :fin"] = explode(' - ', $interval_date_extra);
                        $datas = $this->getProductions($connex, $critere_to_search, $demandeExtras, $pointageExtras, $interval_date_extra);
                    }
                } else {
                    $this->addFlash("danger", "Veuillez cocher au moins une case");
                    return $this->redirectToRoute("dossier_suivi_extra");
                }
            }
        }
        dump($datas);
        return $this->render('dossier/suivi_extra.html.twig', [
            "form" => $form->createView(),
            "data" => $datas
        ]);
    }

    private function getProductions(Connection $connex, array $critere_search, array $demandeExtras, $pointageExtras, string $interval_date)
    {


        $list_personnel_absence_extra = [];
        $list_extras_manque_heure = [];
        $list_extra_effectue = [];
        $tab_test = [];
        //$fields = [];
        //dd($critere_search);
        $fields = ["personnel.nom", "personnel.prenom", "personnel.login", "production.date_traitement", "production.heure_debut", "production.heure_fin", "personnel.id_personnel"];
        foreach ($critere_search as $key => $whatCritereActif) {
            if (($key == "nombre_extra_effectue" && $whatCritereActif)
                /**|| ($key == "absence_extra" && $whatCritereActif)**/
            ) {
                /**
                 * maka ny semaine ny date napidirina ao @le interval
                 */
                $fields[] = "to_char(production.date_traitement::date, 'IW') AS week1";
            }
        }

        $whereBegin = false;
        $prod = new \App\Model\GPAOModels\Production($connex);
        $sqlProd = $prod->Get(
            $fields
        ); //->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
        //->innerJoin('fichiers','dossier_client','dossier_client','fichiers.nom_dossier = dossier_client.nom_dossier')
        //->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')

        //dd($demandeExtras);
        if (count($demandeExtras) > 0) {
            //$list_matricule = " IN (";
            //$list_dates = " IN (";
            //$list_heures_extra
            $list_demande_extras = [];
            $date_demande_extras = [];
            foreach ($demandeExtras as $demande) {
                if (!array_key_exists($demande["id_personnel"], $date_demande_extras)) {
                    $date_demande_extras[$demande["id_personnel"]] = [
                        "id_personnel" => $demande["id_personnel"],
                        "login" => $demande["login"],
                        "nom" => $demande["nom"],
                        "id_type_pointage" => $demande["id_type_pointage"],
                        "heure_debut" => $demande["heure_debut_supplementaire"],
                        "heure_fin" => $demande["heure_fin_supplementaire"],
                        "date" => [$demande["date_suplementaire"]]
                    ];
                    $list_demande_extras[$demande["id_personnel"]][] = [
                        "id_personnel" => $demande["id_personnel"],
                        "login" => $demande["login"],
                        "nom" => $demande["nom"],
                        "id_type_pointage" => $demande["id_type_pointage"],
                        "heure_debut" => $demande["heure_debut_supplementaire"],
                        "heure_fin" => $demande["heure_fin_supplementaire"],
                        "date" => [$demande["date_suplementaire"]]
                    ];
                } else {
                    $info_personnel = $date_demande_extras[$demande["id_personnel"]];
                    $dates = $info_personnel["date"];
                    $date_merge = array_merge([$demande["date_suplementaire"]], $dates);
                    /**
                     * trie les dates
                     */
                    for ($i = 0; $i < count($date_merge); $i++) {
                        $perm = 0;
                        for ($j = 0; $j < count($date_merge); $j++) {
                            if (strtotime($date_merge[$i]) < strtotime($date_merge[$j])) {
                                $perm = $date_merge[$i];
                                $date_merge[$i] = $date_merge[$j];
                                $date_merge[$j] = $perm;
                            }
                        }
                    }
                    $info_personnel["date"] = $date_merge;
                    $date_demande_extras[$demande["id_personnel"]] = $info_personnel;

                    $list_demande_extras[$demande["id_personnel"]][] = [
                        "id_personnel" => $demande["id_personnel"],
                        "login" => $demande["login"],
                        "nom" => $demande["nom"],
                        "id_type_pointage" => $demande["id_type_pointage"],
                        "heure_debut" => $demande["heure_debut_supplementaire"],
                        "heure_fin" => $demande["heure_fin_supplementaire"],
                        "date" => [$demande["date_suplementaire"]]
                    ];
                }
            }

            $list_pointage = [];
            $list_date_productions = [];

            /**
             * maka ny heure manquant
             */
            //$list_extras_manque_heure = $this->getHeureManquant($sqlProd, $list_demande_extras, $pointageExtras);
            //dd($list_extras_manque_heure);

            foreach ($date_demande_extras as $matricule => $info_pers) {

                $matriculeFound = false;
                foreach ($pointageExtras as $pointage) {

                    if ($matricule == $pointage["id_personnel"]) {
                        $matriculeFound = true;
                    }
                }
                if (!$matriculeFound) {
                    /**
                     * absent extra
                     */

                    $list_personnel_absence_extra[] = $info_pers;
                } else {
                    $heure_fin = "12:10:00";
                    $heure_debut = "";

                    $date_debut = $info_pers["date"][0];
                    $date_fin = $info_pers["date"][count($info_pers["date"]) - 1];
                    $list_date_absence = [];


                    $sqlProd->where('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $info_pers["id_personnel"])
                        ->andWhere('date_traitement BETWEEN :dD AND :dF')
                        ->setParameter('dD', $date_debut)
                        ->setParameter('dF', $date_fin);
                    //foreach($info_pers["date"] as $dt){
                    //$sqlProd->andWhere('date_traitement = :debut')
                    //        ->setParameter('debut', $dt);
                    //->setParameter('fin', $date_fin);
                    if ($info_pers["id_type_pointage"] == 24) {
                        $sqlProd->andWhere('production.heure_debut <= :heure_fin')
                            ->setParameter('heure_fin', "12:10:00");
                    } else {
                        $sqlProd->andWhere('production.heure_debut BETWEEN :hD AND :hf')
                            ->setParameter('hD', "12:10:00")
                            ->setParameter('hf', "18:30:00");
                    }

                    $resultats = $sqlProd
                        ->orderBy("production.date_traitement", "ASC")
                        ->orderBy("personnel.id_personnel", "ASC")
                        //->orderBy("production.week1","ASC")
                        ->execute()->fetchAll();

                    //dump($sqlProd->execute());
                    $list_date_productions = [];
                    $total_heure_effectuer = 0;
                    $compteur = 1;

                    /**
                     * absence extra
                     */
                    foreach ($resultats as $res) {
                        if (!in_array($res["date_traitement"], $list_date_productions)) {
                            $list_date_productions[] = $res["date_traitement"];
                        }
                    }
                    foreach ($info_pers["date"] as $dt) {
                        if (!in_array($dt, $list_date_productions)) {
                            //$info_pers["date"] = $dt;
                            $list_date_absence[] = $dt;
                        }
                    }


                    if (count($list_date_absence) > 0) {
                        //if($critere_search["absence_extra"]){
                        $info_pers["date"] = $list_date_absence;
                        $list_personnel_absence_extra[] = $info_pers;
                        //}
                    }
                    //-----------------------------------
                    $res = $resultats;
                    //dd($list_date_productions);
                    //dd($res);
                    $indexI = count($res);
                    $indexJ = count($res) + 1;
                    if (count($res) != 1) {
                        $indexI = count($res) - 1;
                        $indexJ = count($res);
                    }
                    //for($i=0, $j=$i+1; $i<count($res), $j<=count($res); $i++, $j++){
                    for ($i = 0, $j = $i + 1; $i < $indexI, $j < $indexJ; $i++, $j++) {
                        //dd($res[$i]);
                        /**
                         * lera tsy feno t@ extra
                         **/
                        if ($critere_search["heure_extra_manquant"]) {
                            //dump($info_pers["heure_fin"], $info_pers["heure_debut"]);
                            //dd($res[$i]);

                            if (count($res) == 1) {
                                $heure_a_effectuez = strtotime($info_pers["heure_fin"]) - strtotime($info_pers["heure_debut"]);
                                $total_heure_effectuer = (strtotime($res[0]["heure_fin"]) - strtotime($res[0]["heure_debut"]));
                                $info_pers["date"] = [$res[0]["date_traitement"]];
                                //$info_pers["heure_manquant"] = strftime('%H:%I:%S', $heure_a_effectuez-$total_heure_effectuer);
                                $timestamp = $heure_a_effectuez - $total_heure_effectuer;

                                $info_pers["heure_manquant"] = $this->ConvertisseurTime($timestamp);
                                $list_extras_manque_heure[$info_pers["id_personnel"]][] = $info_pers;
                            } else {
                                foreach ($info_pers["date"] as $date_demande) {
                                    //$total_heure_effectuer = 0;
                                    $heure_a_effectuez = strtotime($info_pers["heure_fin"]) - strtotime($info_pers["heure_debut"]);
                                    if ($res[$i]["date_traitement"] == $date_demande && $res[$i]["id_personnel"] == $info_pers["id_personnel"]) {
                                        $heure_manquant = 0;


                                        $total_heure_effectuer += (strtotime($res[$i]["heure_fin"]) - strtotime($res[$i]["heure_debut"]));
                                        if ($j == count($res) - 1) {

                                            //dump($total_heure_effectuer);
                                            $total_heure_effectuer += (strtotime($res[$i]["heure_fin"]) - strtotime($res[$i]["heure_debut"]));
                                            //dump($total_heure_effectuer);
                                            //dump($heure_a_effectuez);
                                            if ($heure_a_effectuez > $total_heure_effectuer) {
                                                $date_manquant_heure = [$date_demande];
                                                $heure_manquant = $heure_a_effectuez - $total_heure_effectuer;
                                                //dd($heure_manquant);
                                                //dd(date('H:i:s', $heure_manquant));
                                                if (count($list_extras_manque_heure) > 0) {
                                                    $matriculeFound = false;

                                                    foreach ($list_extras_manque_heure as $matricule => $heure_manque) {
                                                        if ($matricule == $info_pers["id_personnel"]) {
                                                            $matriculeFound = true;

                                                            if (!in_array($date_demande, $heure_manque["date"])) {

                                                                $info_pers["date"] = array_merge($date_manquant_heure, $info_pers["date"]);
                                                                $info_pers["heure_manquant"] = array_merge([$this->ConvertisseurTime($heure_manquant)], $info_pers["heure_manquant"]);
                                                                //$info_pers["heure_manquant"] = array_merge($heure_manque["heure_manquant"], $info_pers["heure_manquant"]);
                                                                $list_extras_manque_heure[$info_pers["id_personnel"]]["date"] = $info_pers["date"];
                                                                $list_extras_manque_heure[$info_pers["id_personnel"]]["heure_manquant"] = $info_pers["heure_manquant"];
                                                            }
                                                        }
                                                    }
                                                    if (!$matriculeFound) {
                                                        $date_manquant_heure = [$date_demande];

                                                        $info_pers["date"] = $date_manquant_heure;
                                                        $info_pers["heure_manquant"] = [[$this->ConvertisseurTime($heure_manquant)]];
                                                        $list_extras_manque_heure[$info_pers["id_personnel"]][] = $info_pers;
                                                    }
                                                } else {
                                                    $date_manquant_heure = [$date_demande];

                                                    $info_pers["date"] = $date_manquant_heure;
                                                    $info_pers["heure_manquant"] = [[$this->ConvertisseurTime($heure_manquant)]];
                                                    $list_extras_manque_heure[$info_pers["id_personnel"]][] = $info_pers;

                                                    //dd($list_extras_manque_heure);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        /*
                             * comptage de nombre extra effectuer par semaine
                             */
                        if ($critere_search["nombre_extra_effectue"]) {
                            $tab_test[] = $res[$i];
                            if (count($res) > 1) {
                                if ($res[$i]["id_personnel"] == $res[$j]["id_personnel"]) {

                                    if ($res[$i]["week1"] == $res[$j]["week1"]) {
                                        if ($res[$i]["date_traitement"] != $res[$j]["date_traitement"]) {
                                            $compteur++;
                                            //dump($res[$i]["week1"]);
                                            //dump($res[$i]["date_traitement"]);
                                            //dump($res[$j]["week1"]);

                                            //dd($res[$j]["date_traitement"]);
                                        }
                                        if ($j == count($res) - 1) {
                                            $list_extra_effectue[] = [
                                                "id_personnel" => $res[$i]["id_personnel"],
                                                "login" => $res[$i]["login"],
                                                "nom" => $res[$i]["nom"],
                                                //"id_type_pointage" => $res[$i]["id_type_pointage"],
                                                "nb_extra" => $compteur,
                                                //"semaine du " => $res[$i]["date_traitement"],
                                                "semaine_de_annee" => $res[$i]["week1"]
                                            ];
                                            $compteur = 1;
                                        }
                                    } else {
                                        /**
                                            dump($res[$i]["week1"]);
                                            dump($res[$i]["date_traitement"]);
                                            dump($res[$j]["week1"]);

                                            dd($res[$j]["date_traitement"]);
                                         * 
                                         */
                                        if (!in_array($info_pers["id_personnel"], $list_extra_effectue) && !in_array($res[$i]["week1"], $list_extra_effectue)) {
                                            $list_extra_effectue[] = [
                                                "id_personnel" => $res[$i]["id_personnel"],
                                                "login" => $res[$i]["login"],
                                                "nom" => $res[$i]["nom"],
                                                //"id_type_pointage" => $res[$i]["id_type_pointage"],
                                                "nb_extra" => $compteur,
                                                //"semaine du " => $res[$i]["date_traitement"],
                                                "semaine_de_annee" => $res[$i]["week1"]
                                            ];
                                            $compteur = 1;
                                        }
                                    }
                                } else {

                                    $list_extra_effectue[] = [
                                        "id_personnel" => $res[$i]["id_personnel"],
                                        "login" => $res[$i]["login"],
                                        "nom" => $res[$i]["nom"],
                                        //"id_type_pointage" => $res[$i]["id_type_pointage"],
                                        "nb_extra" => $compteur,
                                        //"semaine du " => $res[$i]["date_traitement"],
                                        "semaine de l'annee" => $res[$i]["week1"]
                                    ];
                                    $compteur = 1;
                                }
                            } else {
                                $list_extra_effectue[] = [
                                    "id_personnel" => $res[$i]["id_personnel"],
                                    "login" => $res[$i]["login"],
                                    "nom" => $res[$i]["nom"],
                                    //"id_type_pointage" => $res[$i]["id_type_pointage"],
                                    "nb_extra" => 1,
                                    //"semaine du " => $res[$i]["date_traitement"],
                                    "semaine_de_annee" => $res[$i]["week1"]
                                ];
                            }
                        }
                        //dump($list_date_absence);
                        //dump($list_date_productions);


                    }

                    /**
                     * absent extra
                     */
                    //dump($list_date_productions);
                    //dump($info_pers["date"]);


                }
            }
        }

        return [
            "absence_extra" => $list_personnel_absence_extra,
            "heure_manquant_extras" => $list_extras_manque_heure,
            "extra_effectuer" => $list_extra_effectue
        ];
    }

    private function ConvertisseurTime($Time)
    {
        if ($Time < 3600) {
            $heures = 0;

            if ($Time < 60) {
                $minutes = 0;
            } else {
                $minutes = round($Time / 60);
            }

            $secondes = floor($Time % 60);
        } else {
            $heures = round($Time / 3600);
            $secondes = round($Time % 3600);
            $minutes = floor($secondes / 60);
        }

        $secondes2 = round($secondes % 60);
        if ($minutes == 60) {
            $heures++;
            $minutes = 0;
        }
        $TimeFinal = "$heures : $minutes : $secondes2 ";
        return $TimeFinal;
    }

    public function absenceExtra(Connection $connex, Request $request)
    {
        $operateurs = [];
        $list_personnel_absence_extra = [];
        $productions = [];

        $pers = new Personnel($connex);
        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom",
            "nom_fonction"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }
        $form = $this->createFormBuilder()
            ->add('matricule', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "required" => false,
                "choices" =>  $operateurs,
                "placeholder" => "-Selectionnez-"
            ])

            ->add('interval_date_extra', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

            $sqlDemandeExtras = $demandeSupplementaire->Get([
                //"personnel.id_personnel",
                //"date_suplementaire",
                //"heure_debut_supplementaire",
                //"heure_fin_supplementaire",
                "demande_supplementaire.*",
                "type_pointage.id_type_pointage",
                "personnel.id_personnel",
                "personnel.nom",
                "personnel.login",
                "personnel.nom_fonction"

            ])
                ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('demande_supplementaire.etat_validation = :etat')
                ->setParameter('etat', "Accorder")
                ->andWhere('personnel.nom_fonction NOT IN(\'ACP 1\',\'Directeur de Plateau\',\'Transmission\',\'TECH\',\'CP 2\',\'CP 1\')');
            //->andWhere('personnel.nom_fonction != :nom_fonction AND personnel.nom_fonction != :n_f AND personnel.nom_fonction != :nf1')
            //->setParameter('nom_fonction',"ACP 1")
            //->setParameter('n_f', "Directeur de Plateau")
            //->setParameter('nf1','Transmission');

            $form_data = $form->getData();
            $matricule = $form_data["matricule"];
            $interval_date_extra = $form_data["interval_date_extra"];

            $matriculeEmpty = true;

            if (!empty($interval_date_extra)) {

                if ((new \DateTime(implode('-', array_reverse(explode('/', explode(' - ', $interval_date_extra)[0])))))->getTimestamp() < (new \DateTime("2021-04-01"))->getTimestamp()) {
                    $this->addFlash("danger", "Date du début de l'extra doit être superieur ou egale à 01/04/2021");
                    return $this->redirectToRoute("dossier_suivi_extra_absence");
                }

                //$critere_to_search["date_interval"] = $interval_date_extra;
                if (!empty($matricule)) {
                    $matriculeEmpty = false;
                    //$search_begin = true;
                    //$critere_to_search["matricule"]["actif"] = true;
                    //$critere_to_search["matricule"]["id_personnel"] = $matricule;

                    $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                }

                $demandeExtras = [];
                $demandes_extras = $sqlDemandeExtras->andWhere('demande_supplementaire.date_suplementaire BETWEEN :debut AND :fin')
                    ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                    ->setParameter('fin', explode(' - ', $interval_date_extra)[1])
                    ->orderBy('date_suplementaire', 'ASC')
                    ->orderBy('personnel.id_personnel')
                    ->execute()->fetchAll();
                //dump($demandes_extras);

                $list_matricule = " IN (";
                $tab_matricule = [];
                $max_date = [];
                $debut_date = "";
                $fin_date = "";
                foreach ($demandes_extras as $demande) {

                    $max_date[] = $demande["date_suplementaire"];

                    if (!in_array($demande["id_personnel"], $tab_matricule)) {
                        $tab_matricule[] = $demande['id_personnel'];
                    }

                    if (!array_key_exists($demande["id_personnel"], $demandeExtras)) {
                        //$dates = [$demande["date_suplementaire"]];
                        $demande["date_suplementaire"] = [$demande["date_suplementaire"]];
                        $demandeExtras[$demande["id_personnel"]] = $demande;
                        //dd($demandeExtras);
                    } else {
                        $dates = $demandeExtras[$demande["id_personnel"]]["date_suplementaire"];
                        $date_merge = array_merge($dates, [$demande["date_suplementaire"]]);
                        for ($i = 0; $i < count($date_merge); $i++) {
                            $perm = 0;
                            for ($j = 0; $j < count($date_merge); $j++) {
                                if (strtotime($date_merge[$i]) < strtotime($date_merge[$j])) {
                                    $perm = $date_merge[$i];
                                    $date_merge[$i] = $date_merge[$j];
                                    $date_merge[$j] = $perm;
                                }
                            }
                        }

                        $demande["date_suplementaire"] = $date_merge;
                        $demandeExtras[$demande["id_personnel"]] = $demande;
                    }
                }


                foreach ($tab_matricule as $matricule) {
                    $list_matricule .= $matricule . ",";
                }
                $list_matricule = substr($list_matricule, 0, -1);
                $list_matricule .= ")";
                $fin_date = max($max_date);
                $debut_date = min($max_date);

                /**
                 * maka ny pointage ny allaitement irery ihany
                 */

                $list_allaitement_extra = [];
                $pointage = new \App\Model\GPAOModels\Pointage($connex);
                $sqlPointage = $pointage->Get()
                    ->where('personnel.id_personnel ' . $list_matricule)
                    ->andWhere("description = :desc")
                    ->setParameter("desc", "Extra")
                    ->andWhere('date_debut BETWEEN :debut and :fin')
                    ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                    ->setParameter('fin', explode(' - ', $interval_date_extra)[1])
                    ->andWhere('pointage.heure_entre BETWEEN :h_e AND :h_s')
                    ->setParameter('h_e', '11:00:00')
                    ->setParameter('h_s', '11:30:00');


                $list_pointage_allaitement = $sqlPointage->execute()->fetchAll();
                foreach ($list_pointage_allaitement as $pointage_allaitement) {
                    $list_allaitement_extra[] = $pointage_allaitement["id_personnel"];
                }

                /**
                 * maka ny production
                 */
                $fields = ["personnel.nom", "personnel.prenom", "personnel.login", "production.date_traitement", "production.heure_debut", "production.heure_fin", "personnel.id_personnel", "personnel.id_type_pointage"];
                $prod = new \App\Model\GPAOModels\Production($connex);
                $sqlProd = $prod->Get(
                    $fields
                )->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                    ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                    ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage');
                //->innerJoin('type_pointage','pointage','pointage','pointage.id_type_pointage = type_pointage.id_type_pointage');

                $sqlProd->where('personnel.id_personnel ' . $list_matricule)
                    ->andWhere('date_traitement BETWEEN :db AND :fin')
                    ->setParameter('db', $debut_date)
                    ->setParameter('fin', $fin_date)
                    ->andWhere('((type_pointage.id_type_pointage = :type1 and (production.heure_debut>=:htype1_1 and production.heure_debut<=:htype1_2)) or (type_pointage.id_type_pointage = :type2 and (production.heure_debut>=:htype2_1 and production.heure_debut<=:htype2_2)))')
                    ->setParameter('type1', 24)
                    ->setParameter('htype1_1', '06:00:00')
                    ->setParameter('htype1_2', '12:10:00')
                    ->setParameter('type2', 1)

                    ->setParameter('htype2_1', '11:00:00')
                    ->setParameter('htype2_2', '18:30:00');

                $sqlProd->orderBy('personnel.id_personnel', 'ASC');

                $productions = $sqlProd->execute()->fetchAll();
                $dateProductions = [];

                /**
                 * ato no filtren ny prod extra fa mis ny equipe 1 mis tafiditra ny panw extra
                 */
                foreach ($productions as $prod) {
                    if (!in_array($prod["id_personnel"], $list_allaitement_extra)) {
                        if (strtotime($prod["heure_debut"]) > strtotime("12:10:00") && $prod["id_type_pointage"] == 1) {
                            if (!array_key_exists($prod["id_personnel"], $dateProductions)) {
                                $dateProductions[$prod["id_personnel"]] = [$prod["date_traitement"]];
                            } else {
                                //;
                                $dateP = $dateProductions[$prod["id_personnel"]];
                                if (!in_array($prod["date_traitement"], $dateP)) {
                                    $dateP = array_merge($dateP, [$prod["date_traitement"]]);
                                }
                                $dateProductions[$prod["id_personnel"]] = $dateP;
                            }
                        }

                        if (strtotime($prod["heure_debut"]) < strtotime("12:10:00") && $prod["id_type_pointage"] == 24) {
                            if (!array_key_exists($prod["id_personnel"], $dateProductions)) {
                                $dateProductions[$prod["id_personnel"]] = [$prod["date_traitement"]];
                            } else {
                                //;
                                $dateP = $dateProductions[$prod["id_personnel"]];
                                if (!in_array($prod["date_traitement"], $dateP)) {
                                    $dateP = array_merge($dateP, [$prod["date_traitement"]]);
                                }
                                $dateProductions[$prod["id_personnel"]] = $dateP;
                            }
                        }
                    } else {
                        if (strtotime($prod["heure_debut"]) > strtotime("11:10:00") && $prod["id_type_pointage"] == 1) {
                            if (!array_key_exists($prod["id_personnel"], $dateProductions)) {
                                $dateProductions[$prod["id_personnel"]] = [$prod["date_traitement"]];
                            } else {
                                //;
                                $dateP = $dateProductions[$prod["id_personnel"]];
                                if (!in_array($prod["date_traitement"], $dateP)) {
                                    $dateP = array_merge($dateP, [$prod["date_traitement"]]);
                                }
                                $dateProductions[$prod["id_personnel"]] = $dateP;
                            }
                        }
                    }
                }

                if (count($demandeExtras) > 0) {
                    $dateDf = "";
                    foreach ($demandeExtras as $matricule => $demande) {
                        $userAbsent = true;
                        $nb_absent = 0;
                        foreach ($dateProductions as $m => $dateProd) {
                            if ($matricule == $m) {
                                $userAbsent = false;
                                foreach ($demande["date_suplementaire"] as $dateD) {
                                    if (!in_array($dateD, $dateProd)) {
                                        if (count($list_personnel_absence_extra) == 0) {
                                            $dateDf = $dateD;
                                            $demande["date_absence"] = [$dateD];
                                            $demande["nb_absence"] = $nb_absent + 1;
                                            $demande["heure_extra"] = [[$demande["heure_debut_supplementaire"], $demande["heure_fin_supplementaire"]]];
                                            $list_personnel_absence_extra[$matricule] = $demande;
                                        } else {
                                            $userFound = false;
                                            foreach ($list_personnel_absence_extra as $mtr => $data) {
                                                if ($mtr == $matricule) {
                                                    $userFound = true;
                                                    $dates = $list_personnel_absence_extra[$mtr]["date_absence"];
                                                    $heure_extra = $list_personnel_absence_extra[$mtr]["heure_extra"];
                                                    $abs = $list_personnel_absence_extra[$mtr]["nb_absence"];
                                                    $abs += 1;
                                                    $heure_extra = array_merge($heure_extra, [[$demande["heure_debut_supplementaire"], $demande["heure_fin_supplementaire"]]]);
                                                    $dates = array_merge($dates, [$dateD]);
                                                    $list_personnel_absence_extra[$mtr]["nb_absence"] = $abs;
                                                    $list_personnel_absence_extra[$mtr]["date_absence"] = $dates;
                                                    $list_personnel_absence_extra[$mtr]["heure_extra"] = $heure_extra;
                                                }
                                            }

                                            if (!$userFound) {
                                                $demande["heure_extra"] = [[$demande["heure_debut_supplementaire"], $demande["heure_fin_supplementaire"]]];
                                                $demande["date_absence"] = [$dateD];
                                                $demande["nb_absence"] = $nb_absent += 1;
                                                //$demande["date_absence"] = [$dateDf];
                                                $list_personnel_absence_extra[$matricule] = $demande;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($userAbsent) {
                            $demande["heure_extra"] = [[$demande["heure_debut_supplementaire"], $demande["heure_fin_supplementaire"]]];
                            $demande["nb_absence"] = $nb_absent += 1;
                            $demande["date_absence"] = [$dateDf];
                            $list_personnel_absence_extra[$matricule] = $demande;
                        }
                    }
                }
                //dump($list_personnel_absence_extra);
                if (count($list_personnel_absence_extra) > 0) {
                    usort($list_personnel_absence_extra, array($this, "custom_sort_absence"));
                }
            }
        }

        return $this->render('dossier/absence_extra.html.twig', [
            "form" => $form->createView(),
            "data" => $list_personnel_absence_extra
        ]);
    }

    public function custom_sort_absence($a, $b)
    {
        return $a['nb_absence'] < $b['nb_absence'];
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function heureManquantExtra(Connection $connex, Request $request)
    {
        $operateurs = [];
        $list_personnel_heure_extra_manquant = [];
        $productions = [];
        $heure_extras_manquant = [];
        $pers = new Personnel($connex);
        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom",
            "nom_fonction"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }
        $form = $this->createFormBuilder()
            ->add('matricule', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "required" => false,
                "choices" =>  $operateurs,
                "placeholder" => "-Selectionnez-"
            ])

            ->add('interval_date_extra', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

            $sqlDemandeExtras = $demandeSupplementaire->Get([
                //"personnel.id_personnel",
                //"date_suplementaire",
                //"heure_debut_supplementaire",
                //"heure_fin_supplementaire",
                "demande_supplementaire.*",
                "type_pointage.id_type_pointage",
                "personnel.id_personnel",
                "personnel.nom",
                "personnel.login",
                "personnel.nom_fonction",


            ])
                ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('demande_supplementaire.etat_validation = :etat')
                ->setParameter('etat', "Accorder")
                ->andWhere('personnel.nom_fonction != :nom_fonction AND personnel.nom_fonction != :n_f')
                ->setParameter('nom_fonction', "ACP 1")
                ->setParameter('n_f', "Directeur de Plateau");

            $form_data = $form->getData();
            $matricule = $form_data["matricule"];
            $interval_date_extra = $form_data["interval_date_extra"];

            $matriculeEmpty = true;
            $tab_matricule = [];

            if (!empty($interval_date_extra)) {

                if ((new \DateTime(implode('-', array_reverse(explode('/', explode(' - ', $interval_date_extra)[0])))))->getTimestamp() < (new \DateTime("2021-04-01"))->getTimestamp()) {
                    $this->addFlash("danger", "Date du début de l'extra doit être superieur ou egale à 01/04/2021");
                    return $this->redirectToRoute("dossier_suivi_extra_heure_manquant");
                }

                //$critere_to_search["date_interval"] = $interval_date_extra;
                if (!empty($matricule)) {
                    $matriculeEmpty = false;
                    $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                }

                $demandeExtras = [];
                $demandes_extras = $sqlDemandeExtras->andWhere('demande_supplementaire.date_suplementaire BETWEEN :debut AND :fin')
                    ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                    ->setParameter('fin', explode(' - ', $interval_date_extra)[1])
                    ->orderBy('date_suplementaire', 'ASC')
                    ->orderBy('personnel.id_personnel')
                    ->execute()->fetchAll();


                $list_matricule = " IN (";

                $max_date = [];
                $debut_date = "";
                $fin_date = "";
                foreach ($demandes_extras as $demande) {
                    $max_date[] = $demande["date_suplementaire"];

                    if (!in_array($demande["id_personnel"], $tab_matricule)) {
                        $tab_matricule[] = $demande['id_personnel'];
                    }

                    if (!array_key_exists($demande["id_personnel"], $demandeExtras)) {
                        //$dates = [$demande["date_suplementaire"]];
                        $demande["date_suplementaire"] = [$demande["date_suplementaire"] => [$demande["heure_debut_supplementaire"], $demande["heure_fin_supplementaire"]]];
                        $demandeExtras[$demande["id_personnel"]] = $demande;
                        //dd($demandeExtras);
                    } else {
                        $dates = $demandeExtras[$demande["id_personnel"]]["date_suplementaire"];
                        $date_merge = array_merge($dates, [$demande["date_suplementaire"] => [$demande["heure_debut_supplementaire"], $demande["heure_fin_supplementaire"]]]);
                        ksort($date_merge); //trie par clés


                        $demande["date_suplementaire"] = $date_merge;
                        $demandeExtras[$demande["id_personnel"]] = $demande;
                    }
                }

                foreach ($tab_matricule as $matricule) {
                    $list_matricule .= $matricule . ",";
                }
                $list_matricule = substr($list_matricule, 0, -1);
                $list_matricule .= ")";
                if (count($max_date) > 0) {
                    $fin_date = max($max_date);
                    $debut_date = min($max_date);
                }
            }
            /**
             * maka ny pointage ny allaitement irery ihany
             */

            if (count($tab_matricule) > 0) {
                $list_allaitement_extra = [];
                $pointage = new \App\Model\GPAOModels\Pointage($connex);
                $sqlPointage = $pointage->Get()
                    ->where('personnel.id_personnel ' . $list_matricule)
                    ->andWhere("description = :desc")
                    ->setParameter("desc", "Extra")
                    ->andWhere('date_debut BETWEEN :debut and :fin')
                    ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                    ->setParameter('fin', explode(' - ', $interval_date_extra)[1])
                    ->andWhere('pointage.heure_entre BETWEEN :h_e AND :h_s')
                    ->setParameter('h_e', '11:00:00')
                    ->setParameter('h_s', '11:30:00');


                $list_pointage_allaitement = $sqlPointage->execute()->fetchAll();

                foreach ($list_pointage_allaitement as $pointage_allaitement) {
                    $list_allaitement_extra[] = $pointage_allaitement["id_personnel"];
                }
                //dump($list_pointage);
                $fields = ["personnel.nom", "personnel.prenom", "personnel.login", "production.date_traitement", "production.heure_debut", "production.heure_fin", "personnel.id_personnel", "personnel.id_type_pointage"];
                $prod = new \App\Model\GPAOModels\Production($connex);
                $sqlProd = $prod->Get(
                    $fields
                )->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                    ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                    ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage');
                //->innerJoin('type_pointage','pointage','pointage','pointage.id_type_pointage = type_pointage.id_type_pointage');

                $sqlProd->where('personnel.id_personnel ' . $list_matricule)
                    ->andWhere('date_traitement BETWEEN :db AND :fin')
                    ->setParameter('db', $debut_date)
                    ->setParameter('fin', $fin_date)
                    ->andWhere('((type_pointage.id_type_pointage = :type1 and (production.heure_debut>=:htype1_1 and production.heure_debut<=:htype1_2)) or (type_pointage.id_type_pointage = :type2 and (production.heure_debut>=:htype2_1 and production.heure_debut<=:htype2_2)))')
                    ->setParameter('type1', 24)
                    ->setParameter('htype1_1', '06:00:00')
                    ->setParameter('htype1_2', '12:10:00')
                    ->setParameter('type2', 1)
                    ->setParameter('htype2_1', '11:00:00')
                    ->setParameter('htype2_2', '18:30:00')
                    ->orderBy('personnel.id_personnel', 'ASC');

                $productions = $sqlProd->execute()->fetchAll();
                //foreach($productions as $prod){
                //dd($prod);
                //}
                //dd($productions);
                //dump($list_allaitement_extra);
                foreach ($demandeExtras as $matricule => $demande) {
                    foreach ($demande["date_suplementaire"] as $date => $heures) {
                        $total_heure_a_effectuer = strtotime($heures[1]) - strtotime($heures[0]);
                        $total_heure_extra = 0;
                        $heures_deb_prod = [];
                        $heures_fin_prod = [];
                        foreach ($productions as $prod) {

                            if ($prod["id_personnel"] == $matricule) {
                                $userNotAllaitement = false;
                                if (!in_array($prod["id_personnel"], $list_allaitement_extra)) {
                                    $userNotAllaitement = true;
                                }
                                /**
                                 * raha tsy allaitement iz ny heure netombohan tokony mahery ny 12:10:00
                                 */
                                if ($userNotAllaitement) {
                                    if ($prod["date_traitement"] == $date) {

                                        if (strtotime($prod["heure_debut"]) > strtotime("12:10:00") && $prod["id_type_pointage"] == 1) {
                                            $heures_deb_prod[] = $prod["heure_debut"];
                                            $heures_fin_prod[] = $prod["heure_fin"];
                                            $total_heure_extra += strtotime($prod["heure_fin"]) - strtotime($prod["heure_debut"]);
                                        }
                                        if (strtotime($prod["heure_debut"]) < strtotime("12:10:00") && $prod["id_type_pointage"] == 24) {
                                            $heures_deb_prod[] = $prod["heure_debut"];
                                            $heures_fin_prod[] = $prod["heure_fin"];
                                            $total_heure_extra += strtotime($prod["heure_fin"]) - strtotime($prod["heure_debut"]);
                                        }
                                    }
                                } else {
                                    if ($prod["date_traitement"] == $date) {

                                        if (strtotime($prod["heure_debut"]) > strtotime("11:10:00") && $prod["id_type_pointage"] == 1) {
                                            $heures_deb_prod[] = $prod["heure_debut"];
                                            $heures_fin_prod[] = $prod["heure_fin"];
                                            $total_heure_extra += strtotime($prod["heure_fin"]) - strtotime($prod["heure_debut"]);
                                        }
                                    }
                                }
                            }
                        }
                        $heure_manquant =  $total_heure_extra - $total_heure_a_effectuer;

                        if ($heure_manquant < 0 && $total_heure_extra > 0) {
                            $heure_manquant = $total_heure_a_effectuer - $total_heure_extra;

                            $heure_debut_p = $heures_deb_prod[0];
                            $heure_fin_p = $heures_fin_prod[count($heures_fin_prod) - 1];

                            //dd($demande, $date, $heure_manquant);
                            if (count($list_personnel_heure_extra_manquant) == 0) {
                                $demande["heure_debut_supplementaire"] = [$heures[0]];
                                $demande["heure_fin_supplementaire"] = [$heures[1]];
                                //$demande["date_suplementaire"] = [$date => [$heures[0]." à ".$heures[1] => $this->ConvertisseurTime($heure_manquant)]];
                                $demande["date_suplementaire"] = [$date => [$heure_debut_p . " à " . $heure_fin_p => $this->ConvertisseurTime($heure_manquant)]];
                                $demande["heure_manquant"] =  $heure_manquant;
                                $list_personnel_heure_extra_manquant[$matricule] = $demande;
                            } else {
                                //foreach($list_personnel_heure_extra_manquant as $key_matr => $personnel_manque_heure){
                                if (array_key_exists($matricule, $list_personnel_heure_extra_manquant)) {
                                    //dump($key,$list_personnel_heure_extra_manquant);
                                    //dd("ato",$personnel_manque_heure);
                                    $dt = $list_personnel_heure_extra_manquant[$matricule]["date_suplementaire"];
                                    $date_merge = array_merge($dt, [$date => [$heure_debut_p . " à " . $heure_fin_p => $this->ConvertisseurTime($heure_manquant)]]);
                                    $demande["date_suplementaire"] = $date_merge;
                                    //dd($list_personnel_heure_extra_manquant);
                                    $demande["heure_manquant"] = $list_personnel_heure_extra_manquant[$matricule]["heure_manquant"] + $heure_manquant;
                                    $heures_deb_supls = $list_personnel_heure_extra_manquant[$matricule]["heure_debut_supplementaire"];
                                    $heures_fin_supls = $list_personnel_heure_extra_manquant[$matricule]["heure_fin_supplementaire"];
                                    $heures_deb_supls = array_merge($heures_deb_supls, [$heures[0]]);
                                    $heures_fin_supls = array_merge($heures_fin_supls, [$heures[1]]);
                                    $demande["heure_debut_supplementaire"] = $heures_deb_supls;
                                    $demande["heure_fin_supplementaire"] = $heures_fin_supls;
                                    $list_personnel_heure_extra_manquant[$matricule] = $demande;
                                } else {
                                    $demande["heure_debut_supplementaire"] = [$heures[0]];
                                    $demande["heure_fin_supplementaire"] = [$heures[1]];
                                    $demande["date_suplementaire"] = [$date => [$heure_debut_p . " à " . $heure_fin_p => $this->ConvertisseurTime($heure_manquant)]];
                                    $demande["heure_manquant"] =  $heure_manquant;
                                    $list_personnel_heure_extra_manquant[$matricule] = $demande;
                                    //dd($list_personnel_heure_extra_manquant);
                                }
                                //}
                            }
                        }
                    }
                }
            }
            if (count($list_personnel_heure_extra_manquant) > 0) {
                usort($list_personnel_heure_extra_manquant, array($this, "custom_sortHeureManquant"));
            }
            dump($list_personnel_heure_extra_manquant);
        }

        return $this->render('dossier/heure_manquant_extra.html.twig', [
            "form" => $form->createView(),
            "data" => $list_personnel_heure_extra_manquant
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function nombreExtraEffectuez(Connection $connex, Request $request)
    {
        $list_heure_extra_effectuez = [];
        $list_nombre_extra_effectuez = [];

        $list_prod = [];
        $pers = new Personnel($connex);
        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom",
            "nom_fonction"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }
        $form = $this->createFormBuilder()
            ->add('matricule', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "required" => false,
                "choices" =>  $operateurs,
                "placeholder" => "-Selectionnez-"
            ])

            ->add('interval_date_extra', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

            $sqlDemandeExtras = $demandeSupplementaire->Get([
                //"personnel.id_personnel",
                //"date_suplementaire",
                //"heure_debut_supplementaire",
                //"heure_fin_supplementaire",
                "demande_supplementaire.*",
                "type_pointage.id_type_pointage",
                "personnel.id_personnel",
                "personnel.nom",
                "personnel.login",
                "personnel.nom_fonction",
                "to_char(date_suplementaire::date, 'IW') AS weekDemande"

            ])
                //->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('demande_supplementaire.etat_validation = :etat')
                ->setParameter('etat', "Accorder")
                ->andWhere('personnel.nom_fonction != :nom_fonction AND personnel.nom_fonction != :n_f')
                ->setParameter('nom_fonction', "ACP 1")
                ->setParameter('n_f', "Directeur de Plateau");

            $form_data = $form->getData();
            $matricule = $form_data["matricule"];
            $interval_date_extra = $form_data["interval_date_extra"];
            $tab_matricule = [];
            $matriculeEmpty = true;

            if (!empty($interval_date_extra)) {


                if ((new \DateTime(implode('-', array_reverse(explode('/', explode(' - ', $interval_date_extra)[0])))))->getTimestamp() < (new \DateTime("2021-04-01"))->getTimestamp()) {
                    $this->addFlash("danger", "Date du début de l'extra doit être superieur ou egale à 01/04/2021");
                    return $this->redirectToRoute("dossier_suivi_extra_heure_manquant");
                }

                //$critere_to_search["date_interval"] = $interval_date_extra;
                if (!empty($matricule)) {
                    $matriculeEmpty = false;
                    $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                }

                $demandeExtras = [];
                $demandes_extras = $sqlDemandeExtras->andWhere('demande_supplementaire.date_suplementaire BETWEEN :debut AND :fin')
                    ->setParameter('debut', explode(' - ', $interval_date_extra)[0])
                    ->setParameter('fin', explode(' - ', $interval_date_extra)[1])
                    ->orderBy('date_suplementaire', 'ASC')
                    ->orderBy('personnel.id_personnel')
                    ->execute()->fetchAll();


                $list_matricule = " IN (";
                $tab_matricule = [];
                $max_date = [];
                $debut_date = "";
                $fin_date = "";

                foreach ($demandes_extras as $demande) {
                    $max_date[] = $demande["date_suplementaire"];
                    if (!in_array($demande["id_personnel"], $tab_matricule)) {
                        $tab_matricule[] = $demande['id_personnel'];
                    }
                    /**
                    if(count($demandeExtras) == 0){
                        $demandeExtras[] = $demande;
                    }else{
                        $userF = false;
                        foreach($demandeExtras as $extraD){
                            if($extraD["id_personnel"] == $demande["id_personnel"] && $extraD["weekdemande"] == $demande["weekdemande"]){
                                $userF = true;
                            }
                        }
                        if(!$userF){
                            $demandeExtras[] = $demande;
                        }
                    }**/
                }
                foreach ($tab_matricule as $matricule) {
                    $list_matricule .= $matricule . ",";
                }

                $list_matricule = substr($list_matricule, 0, -1);
                $list_matricule .= ")";
                if (count($max_date) > 0) {
                    $fin_date = max($max_date);
                    $debut_date = min($max_date);
                }


                $fields = ["personnel.nom", "personnel.prenom", "personnel.login", "production.date_traitement", "production.heure_debut", "production.heure_fin", "personnel.id_personnel", "personnel.id_type_pointage"];
                $fields[] = "to_char(production.date_traitement::date, 'IW') AS weekProd";
                $prod = new \App\Model\GPAOModels\Production($connex);
                $sqlProd = $prod->Get(
                    $fields
                )->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                    ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                    ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage');

                $sqlProd->where('personnel.id_personnel ' . $list_matricule)
                    ->andWhere('date_traitement BETWEEN :db AND :fin')
                    ->setParameter('db', $debut_date)
                    ->setParameter('fin', $fin_date)
                    ->andWhere('((type_pointage.id_type_pointage = :type1 and (production.heure_debut>=:htype1_1 and production.heure_debut<=:htype1_2)) or (type_pointage.id_type_pointage = :type2 and (production.heure_debut>=:htype2_1 and production.heure_debut<=:htype2_2)))')
                    ->setParameter('type1', 24)
                    ->setParameter('htype1_1', '06:00:00')
                    ->setParameter('htype1_2', '12:10:00')
                    ->setParameter('type2', 1)
                    ->setParameter('htype2_1', '12:10:00')
                    ->setParameter('htype2_2', '18:30:00')
                    ->orderBy('personnel.id_personnel', 'ASC');

                $productions = $sqlProd->execute()->fetchAll();


                $list_final = [];
                foreach ($productions as $prod) {
                    //if(!in_array($prod["id_personnel"]))


                    if (count($list_prod) == 0) {
                        $list_prod[] = [
                            "login" => $prod["login"],
                            "nom" => $prod["nom"],
                            "week" => $prod["weekprod"],
                            "date" => [$prod["date_traitement"]],
                            "id_type_pointage" => $prod["id_type_pointage"],
                            "id_personnel" => $prod["id_personnel"]
                        ];
                    } else {
                        $userFound = false;
                        foreach ($list_prod as $key => $p) {
                            if ($p["id_personnel"] == $prod["id_personnel"]) {
                                if ($p["week"] == $prod["weekprod"]) {
                                    $userFound = true;
                                    if (!in_array($prod["date_traitement"], $p["date"])) {
                                        $dates = $p["date"];
                                        $date_merge = array_merge($dates, [$prod["date_traitement"]]);
                                        $p["date"] = $date_merge;
                                        $list_prod[$key] = $p;
                                    }
                                }
                            }
                        }
                        if (!$userFound) {
                            $list_prod[] = [
                                "login" => $prod["login"],
                                "nom" => $prod["nom"],
                                "week" => $prod["weekprod"],
                                "date" => [$prod["date_traitement"]],
                                "id_type_pointage" => $prod["id_type_pointage"],
                                "id_personnel" => $prod["id_personnel"]
                            ];
                        }
                    }
                }

                foreach ($list_prod as $key => $prod) {
                    $nb_extra = 0;
                    foreach ($prod["date"] as $d) {
                        $nb_extra++;
                    }
                    $list_prod[$key]["nb_extra"] = $nb_extra;
                }

                //dd($list_prod);
            }

            if (count($list_prod) > 0) {
                usort($list_prod, array($this, "custom_sortExtraEffectuez"));
            }
        }
        //dump($list_prod);
        return $this->render('dossier/nombreExtraEffectuez.html.twig', [
            "form" => $form->createView(),
            "data" => $list_prod
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function extraCadre(Connection $connex, Request $request)
    {
        $prod_final = [];
        $total_prix = 0;
        $taux_reussite_general = 0;
        $nb_lignes = 0;
        $list_dossier_not_comptabilise = [];
        $total_facturable = 0;
        $name_excel = "";
        $exportToExcel = null;

        $pers = new Personnel($connex);
        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom",
            "nom_fonction"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();



        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }
        $form = $this->createFormBuilder()
            ->add('matricule', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "required" => false,
                "choices" =>  $operateurs,
                "placeholder" => "-Selectionnez-"
            ])

            ->add('interval_date_extra', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])
            ->add('export', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,
            ])
            ->add('isFacturable', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,

            ])

            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            $data = $form->getData();
            $matricule = $data["matricule"];
            $matr = $matricule;
            $dates = $data["interval_date_extra"];
            $isFacturable = $data["isFacturable"];
            $exportToExcel = $data["export"];


            if (!empty($dates)) {
                $dD = explode(' - ', $dates)[0];
                $dF = explode(' - ', $dates)[1];
                $name_excel = "/extraCadre" . date('YmdH:i:s') . ".xlsx";
                //$intervalDateFacturable = $this->getIntervalDateInCompte((new \DateTime())->format("d/m/Y"), (new \DateTime())->format("d/m/Y"));
                $intervalDateFacturable = $this->getIntervalDateInCompte($dD, $dF);

                $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

                $sqlDemandeExtras = $demandeSupplementaire->Get([
                    //"personnel.id_personnel",
                    //"date_suplementaire",
                    //"heure_debut_supplementaire",
                    //"heure_fin_supplementaire",
                    "demande_supplementaire.*",
                    "type_pointage.id_type_pointage",
                    "personnel.id_personnel",
                    "personnel.nom",
                    "personnel.login",
                    "personnel.nom_fonction",
                ])
                    //->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                    ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                    ->where('demande_supplementaire.etat_validation = :etat')
                    ->setParameter('etat', "Accorder")
                    ->andWhere('personnel.nom_fonction IN (\'ACP 1\', \'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')')
                    ->andWhere('date_suplementaire BETWEEN :db AND :df')
                    //->setParameter('db', $dD)
                    //->setParameter('df', $dF);
                    ->setParameter('db', $intervalDateFacturable["dateDebutCompte"])
                    ->setParameter('df', $intervalDateFacturable["dateFinCompte"]);

                if (!empty($matricule)) {
                    $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                }

                $demandes = $sqlDemandeExtras->orderBy('personnel.id_personnel', "ASC")
                    ->execute()->fetchAll();

                //dd($demandes);
                if (count($demandes) > 0) {
                    $str_matricule = " IN (";
                    foreach ($demandes as $demande) {
                        $str_matricule .= $demande["id_personnel"] . ",";
                    }
                    $str_matricule = substr($str_matricule, 0, -1);
                    $str_matricule .= ")";

                    $pointage = new \App\Model\GPAOModels\Pointage($connex);
                    $sqlPointage = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description", "date_debut"])
                        //->where("date_debut = :debut")
                        //->setParameter("debut", date('Y-m-d'))
                        ->where("description = :desc")
                        ->setParameter("desc", "Extra")

                        ->andWhere('personnel.id_personnel ' . $str_matricule)
                        ->andWhere('date_debut BETWEEN :deb AND :fin')
                        ->setParameter('deb', $intervalDateFacturable["dateDebutCompte"])
                        ->setParameter('fin', $intervalDateFacturable["dateFinCompte"])
                        //->setParameter('deb', $dD)
                        //->setParameter('fin', $dF)
                        ->orderBy("personnel.id_personnel", "ASC");
                    $pointageCadre = $sqlPointage->execute()->fetchAll();

                    //dd($pointageCadre);

                    $list_demandes_supl = [];
                    /**
                     * creation de liste des demandes, cles=>matricule, et heure debut supplementaire => -5 min heure debut demande
                     *
                     */
                    foreach ($pointageCadre as $pointage) {
                        foreach ($demandes as $demande) {
                            $heure_fin = null;
                            if ($pointage["id_personnel"] == $demande["id_personnel"]) {
                                if (count($list_demandes_supl) == 0) {

                                    if ($pointage["date_debut"] == $demande["date_suplementaire"]) {
                                        $list_demandes_supl[$demande["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $demande["heure_fin_supplementaire"]];
                                    }
                                } else {
                                    $userFound = false;

                                    foreach ($list_demandes_supl as $matricule => $dem) {
                                        if (!array_key_exists($matricule, $list_demandes_supl)) {

                                            if ($pointage["date_debut"] == $demande["date_suplementaire"]) {
                                                $list_demandes_supl[$demande["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $demande["heure_fin_supplementaire"]];
                                            }
                                        } else {
                                            $userFound = true;
                                        }
                                    }
                                    if ($userFound) {

                                        if ($pointage["date_debut"] == $demande["date_suplementaire"]) {
                                            $list_demandes_supl[$demande["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $demande["heure_fin_supplementaire"]];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $tab_matricule = [];
                    $str_matricule = " IN (";
                    foreach ($list_demandes_supl as $matricule => $v) {
                        $tab_matricule[] = $matricule;
                        $str_matricule .= $matricule . ",";
                    }
                    $str_matricule = substr($str_matricule, 0, -1);
                    $str_matricule .= ")";



                    /**
                     * production
                     */
                    if (count($tab_matricule) > 0) {

                        $productions = [];
                        $prodConnex = new \App\Model\GPAOModels\Production($connex);
                        $whereBegin = false;
                        $fields = [
                            "personnel.id_type_pointage",
                            "personnel.id_personnel",
                            "type_pointage.description as type_pointage",
                            "fichiers.nom_fichiers",
                            "fichiers.nom_dossier",
                            "personnel.nom",
                            "personnel.prenom",
                            "personnel.login",
                            "etape_travail.nom_etape",
                            "etape_travail.objectif",
                            "production.volume",
                            "production.temps_realisation",
                            "etape_travail.prix",
                            "production.etat",
                            "production.heure_debut",
                            "production.heure_fin",
                            "dossier_client.facturable",
                            "dossier_client.date_reel_livraison",
                            "production.date_traitement"
                        ];
                        $sqlProd = $prodConnex->Get(
                            $fields
                        )->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                            ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                            ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                            ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail and dossier_client.nom_dossier=fichiers.nom_dossier');
                        if (!empty($matr)) {
                            $sqlProd->andWhere('personnel.id_personnel = :id_p')
                                ->setParameter('id_p', $matr);
                        } else {
                            $sqlProd->andWhere('personnel.id_personnel ' . $str_matricule)
                                ->andWhere('personnel.nom_fonction IN (\'ACP 1\', \'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')');
                        }

                        $sqlProd->andWhere('date_traitement BETWEEN :deb AND :fin')
                            ->setParameter('deb', $dD)
                            ->setParameter('fin', $dF)
                            ->orWhere('date_reel_livraison BETWEEN :db AND :df AND date_reel_livraison != :d_t')
                            ->setParameter('db', $intervalDateFacturable["dateDebutCompte"])
                            ->setParameter('df', $intervalDateFacturable["dateFinCompte"])
                            ->setParameter('d_t', NULL);
                        //->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail and dossier_client.nom_dossier=fichiers.nom_dossier');


                        /**
                         * raha ny facturable ian no affichena
                         */
                        if ($isFacturable) {
                            $whereBegin = true;
                            //$criteres["dossier_client.facturable = :fact"] = 1;
                            $sqlProd->andWhere('dossier_client.facturable = :fact')
                                ->setParameter('fact', 1);
                        }
                        /**
                         * exportation excel
                         * preparation des outils utiles pour excel
                         */
                        $nomFichier = "";
                        if ($exportToExcel) {
                            $dirPiece = $this->getParameter('app.temp_dir');
                            $nomFichier = $dirPiece . "" . $name_excel;


                            $headers = [
                                "Matricule", "Login", "Date du traitement", "Heure début", "heure fin", "Dossier", "Fichier", "Etat", "Etape", "Volume", "Temps", "Taux", "Prix Unitaire", "Coût", "Facturable", "Date reel livraison"
                            ];

                            $writer = WriterEntityFactory::createXLSXWriter();
                            $writer->openToFile($nomFichier); // write data to a file or to a PHP stream
                            $cells = WriterEntityFactory::createRowFromArray($headers);
                            $writer->addRow($cells);
                        }

                        $prods = $sqlProd->orderBy('personnel.id_personnel', 'ASC')
                            ->execute()->fetchAll();


                        foreach ($prods as $prod) {
                            foreach ($list_demandes_supl as $matricule => $data) {
                                if ($matricule == $prod["id_personnel"]) {
                                    foreach ($data as $date => $heures) {

                                        if ($date == $prod["date_traitement"]) {
                                            if ($prod["heure_debut"] !== null && strtotime($prod["heure_debut"]) >= strtotime($heures[0]) && strtotime($prod["heure_debut"]) < strtotime($heures[1])) {
                                                $taux = null;
                                                if ((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet") {
                                                    if ($prod["volume"] !== null || (float)$prod["volume"] != 0) {
                                                        //$prods[$key]["prix"] = $prod["volume"]*$prod["prix"];                                        
                                                        //$prod["prix"] = $prix;


                                                        $etat = $prod["etat"];
                                                        $matricule = $prod["id_personnel"];
                                                        $login = $prod["login"];
                                                        $dossier = $prod["nom_dossier"];
                                                        $fichier = $prod["nom_fichiers"];
                                                        $etape = $prod["nom_etape"];
                                                        $volume = $prod["volume"];
                                                        $temps = $prod["temps_realisation"];
                                                        $prix_unitaire = $prod["prix"];
                                                        $facturable = $prod["facturable"];

                                                        if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE/', $prod["nom_etape"])) {
                                                            $prix_unitaire = 0;
                                                        }
                                                        $prix = $prod["volume"] * $prix_unitaire;
                                                        $total_prix += $prix;
                                                        //$taux = null;

                                                        if ((float)$prod["temps_realisation"] != 0) {
                                                            $vitesse = $prod["volume"] / $prod["temps_realisation"];
                                                            $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                                            if ($objectif != 0) {
                                                                $taux = $vitesse * 100 / $objectif;
                                                            } else {
                                                                $taux = 0;
                                                            }
                                                            //$prods[$key]["taux"] = $taux;
                                                        } else {
                                                            $taux = 0;
                                                        }

                                                        $taux_reussite_general += $taux;

                                                        $prod_final[] = [
                                                            "matricule" => $matricule,
                                                            "login" => $login,
                                                            "heure_debut" => $prod["heure_debut"],
                                                            "heure_fin" => $prod["heure_fin"],
                                                            "date_traitement" => $prod["date_traitement"],
                                                            "dossier" => $dossier,
                                                            "fichier" => $fichier,
                                                            "etat" => $etat,
                                                            "etape" => $etape,
                                                            "volume" => $volume,
                                                            "temps" => $temps,
                                                            "taux" => $taux,
                                                            "prix_unitaire" => $prix_unitaire,
                                                            "prix" => $prix,
                                                            "facturable" => $facturable,
                                                            "date_livraison" => $prod["date_reel_livraison"]
                                                        ];
                                                        $nb_lignes += 1;

                                                        /**
                                                         * dossier facturable
                                                         */
                                                        if ($prod["facturable"] != 0) {
                                                            $intervalDateFacturable = $this->getIntervalDateInCompte($dD, $dF);
                                                            //dd($intervalDateFacturable);

                                                            if ((strtotime($intervalDateFacturable["dateDebutCompte"]) <= strtotime($prod["date_reel_livraison"]))
                                                                && (strtotime($prod["date_reel_livraison"]) <= strtotime($intervalDateFacturable["dateFinCompte"]))
                                                            ) {

                                                                $total_facturable += $prix;
                                                            }
                                                        }
                                                    }
                                                } else {

                                                    if ($prod["prix"] == 0) {
                                                        $list_dossier_not_comptabilise[] = $prod["nom_dossier"];
                                                    }
                                                    if ((float)$prod["temps_realisation"] != 0) {
                                                        $vitesse = round($prod["volume"] / $prod["temps_realisation"], 2);
                                                        $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                                        if ($objectif != 0) {
                                                            $taux = $vitesse * 100 / $objectif;
                                                            //dump($vitesse);
                                                        } else {
                                                            $taux = 0;
                                                        }
                                                        //$prods[$key]["taux"] = $taux;
                                                    } else {
                                                        $taux = 0;
                                                    }
                                                    $taux_reussite_general += $taux;
                                                    $prix_unitaire = 0;
                                                    if (preg_match('/SUBDIVISION|PREPARATION|DECOUPE/', $prod["nom_etape"])) {
                                                        $prix_unitaire = 0;
                                                    }
                                                    $prod_final[] = [
                                                        "matricule" => $prod["id_personnel"],
                                                        "login" => $prod["login"],
                                                        "heure_debut" => $prod["heure_debut"],
                                                        "heure_fin" => $prod["heure_fin"],
                                                        "dossier" => $prod["nom_dossier"],
                                                        "date_traitement" => $prod["date_traitement"],
                                                        "fichier" => $prod["nom_fichiers"],
                                                        "etat" => $prod["etat"],
                                                        "etape" => $prod["nom_etape"],
                                                        "volume" => $prod["volume"],
                                                        "temps" => $prod["temps_realisation"],
                                                        "taux" => $taux,
                                                        //"prix_unitaire" => $prod["prix"],
                                                        "prix_unitaire" => $prix_unitaire,
                                                        "prix" => $prod["volume"] * $prod["prix"],
                                                        "facturable" => $prod["facturable"],
                                                        "date_livraison" => $prod["date_reel_livraison"]

                                                    ];
                                                    $nb_lignes += 1;
                                                }
                                                /**
                                                 * exportation excel
                                                 * recuperation des donnees
                                                 */
                                                if ($exportToExcel) {
                                                    $fact = "En attente";
                                                    if ($prod["facturable"] == 1) {
                                                        $fact = "OK";
                                                    }
                                                    $cells = WriterEntityFactory::createRowFromArray([
                                                        $prod["id_personnel"],
                                                        ucwords($prod["login"]),
                                                        $prod["date_traitement"],
                                                        $prod["heure_debut"],
                                                        $prod["heure_fin"],
                                                        $prod["nom_dossier"],
                                                        $prod["nom_fichiers"],
                                                        $prod["etat"],
                                                        $prod["nom_etape"],
                                                        $prod["volume"],
                                                        $prod["temps_realisation"],
                                                        $taux,
                                                        $prod["prix"],
                                                        //$prix_unitaire,
                                                        $prod["volume"] * $prod["prix"],
                                                        $fact,
                                                        $prod["date_reel_livraison"]
                                                        //$prod["facturable"]
                                                    ]);
                                                    $writer->addRow($cells);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (count($prod_final) > 0) {
                $taux_reussite_general = round($taux_reussite_general / $nb_lignes, 2);

                /**
                 * fermeture exportation excel
                 */
                if ($exportToExcel) {
                    $writer->close();
                }
                //dd($prod_final);
            }
        }


        return $this->render("dossier/extraCadre.html.twig", [
            "form" => $form->createView(),
            "prods" => $prod_final,
            //"date" => implode(' - ', $dates),
            "total_prix" => $total_prix,
            "nb_lignes" => $nb_lignes,
            "total_facturable" => $total_facturable,
            "excel" => [
                "isExportToExcel" => $exportToExcel,
                "fileName" => $name_excel
            ],
            "taux_generale" => $taux_reussite_general
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function statistiqueExtraProduction(Connection $connex, Request $request)
    {

        $labelGraph = [];
        $data = null;
        $pers = new \App\Model\GPAOModels\Personnel($connex);

        $productionNormalGeneralEquipeMatin = [];
        $extrasGeneralEquipeMatin = [];
        $productionNormalGeneralEquipeAPM = [];
        $extrasGeneralEquipeAPM = [];
        $tabByOperateurs = [];

        $extrasMatin = [];
        $extrasAPM = [];
        $productionsMatin = [];
        $productionsAPM = [];

        $extrasGeneral = [];
        $productionGeneral = [];

        $moyenneProd = 0;
        $moyenneExtra = 0;

        $list_personnel = []; //list contenant tous les extras
        $taux_extra = null;
        $taux_production = null;
        $date_bdd = null;

        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();
        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }

        /**
         * form 
         */

        $form = $this->createFormBuilder()
            ->add('operateur', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => $operateurs,
            ])
            ->add('dates', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])
            ->add('equipe', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => [
                    "Matin" => 1,
                    "APM" => 24
                ]
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()) {


            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

            $sqlDemandeExtras = $demandeSupplementaire->Get([
                //"personnel.id_personnel",
                "date_suplementaire",
                "heure_debut_supplementaire",
                "heure_fin_supplementaire",

                //"type_pointage.id_type_pointage",
                "personnel.id_personnel",
                "personnel.nom",
                "personnel.login",
                "personnel.nom_fonction",
            ])
                //->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                //->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('demande_supplementaire.etat_validation = :etat')
                ->setParameter('etat', "Accorder");

            /**
             * sql extra
             * id_type_pointage extra
             */
            $pointage = new \App\Model\GPAOModels\Pointage($connex);
            $sqlPointage = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description", "date_debut"])
                ->where("description = :desc")
                ->setParameter("desc", "Extra");

            $data = $form->getData();
            $matricule = $data["operateur"];
            $date = $data["dates"];
            $equipe = $data["equipe"];

            $dateisValid = TRUE;
            $dateFiltre = [];

            //filtrage-ny date oe valide v
            if (!empty($date)) {
                $interval_dates = explode(' - ', $date);
                if (count($interval_dates) == 1) {
                    $dates[] = $date;
                } else {
                    $dates[] = $interval_dates[0];
                    $dates[] = $interval_dates[1];

                    $date1 = explode('/', $interval_dates[0]);
                    $date2 = explode('/', $interval_dates[1]);

                    $labelGraph = $this->createIntervalDate($date1[0], $date1[1], $date1[2], $date2[0], $date2[1], $date2[2]);

                    /**
                     * date au format YYYY-mm-dd
                     */
                    foreach ($labelGraph as $key => $date) {
                        $dateInverse =  implode('-', array_reverse(explode('-', $date)));
                        $labelGraph[$key] = $dateInverse;
                    }
                }

                try {
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[0]))));
                    //dd($dt);
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }
                try {
                    //$dt = new \DateTime($dates[1]);
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[1]))));
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }

                $dateFiltre = $interval_dates;
            }

            if (count($dateFiltre) > 0) {

                if (!empty($matricule)) {
                    $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                } else {
                    $sqlDemandeExtras->andWhere('personnel.nom_fonction IN (\'OP 1\', \'OP 2\', \'CORE 1\', \'CORE 2\')');
                }

                if (!empty($equipe)) {
                    $sqlDemandeExtras->andWhere('personnel.id_type_pointage = :id_pointage')
                        ->setParameter('id_pointage', $equipe);
                }

                $demandesExtras = $sqlDemandeExtras->andWhere('date_suplementaire BETWEEN :db AND :df')
                    ->setParameter('db', $dateFiltre[0])
                    ->setParameter('df', $dateFiltre[1])
                    ->orderBy("personnel.id_personnel", 'ASC')
                    ->execute()->fetchAll();
                $str_matricule = " IN (";
                $list_matricule = [];
                $extraExiste = false;
                if (count($demandesExtras) > 0) {


                    foreach ($demandesExtras as $demande) {
                        if (!in_array($demande["id_personnel"], $list_matricule)) {
                            $list_matricule[] = $demande["id_personnel"];
                            $str_matricule .= $demande["id_personnel"] . ",";
                        }
                    }
                    $str_matricule = substr($str_matricule, 0, -1);
                    $str_matricule .= ")";


                    $pointages = $sqlPointage->andWhere('personnel.id_personnel ' . $str_matricule)
                        ->andWhere('date_debut BETWEEN :db and :df')
                        ->setParameter('db', $dateFiltre[0])
                        ->setParameter('df', $dateFiltre[1])
                        ->orderBy("personnel.id_personnel", "ASC")
                        ->execute()->fetchAll();

                    if (count($pointages) > 0) {

                        $str_matricule = " IN (";
                        $list_matricule = [];
                        $list_allaitement = [];
                        /**
                         * creation de liste des demandes, cles=>matricule, et heure debut supplementaire => -5 min heure debut demande
                         *
                         */
                        foreach ($pointages as $pointage) {

                            foreach ($demandesExtras as $demande) {
                                $heure_fin = null;
                                if ($pointage["id_personnel"] == $demande["id_personnel"]) {
                                    if (!in_array($pointage["id_personnel"], $list_matricule)) {
                                        //if(strtotime($demande["heure_debut_supplementaire"]) < strtotime("11:00:00") || strtotime($demande["heure_debut_supplementaire"]) > strtotime("11:20:00")){
                                        $list_matricule[] = $pointage["id_personnel"];
                                        $str_matricule .= $pointage["id_personnel"] . ",";
                                        //}
                                    }
                                    if (count($list_allaitement) == 0) {
                                        if ($pointage["date_debut"] == $demande["date_suplementaire"]) {
                                            //if(strtotime($demande["heure_debut_supplementaire"])>= strtotime("11:00:00") && strtotime($demande["heure_debut_supplementaire"]) < strtotime("11:20:00")){
                                            $list_allaitement[$demande["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $demande["heure_fin_supplementaire"]];
                                            //}
                                        }
                                    } else {
                                        $userFound = false;
                                        foreach ($list_allaitement as $matr => $dem) {
                                            if (!array_key_exists($matr, $list_allaitement)) {

                                                if ($pointage["date_debut"] == $demande["date_suplementaire"]) {
                                                    //if(strtotime($demande["heure_debut_supplementaire"])>= strtotime("11:00:00") && strtotime($demande["heure_debut_supplementaire"]) < strtotime("11:20:00")){
                                                    $list_allaitement[$demande["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $demande["heure_fin_supplementaire"]];
                                                    //}

                                                }
                                            } else {
                                                $userFound = true;
                                            }
                                        }
                                        if ($userFound) {

                                            if ($pointage["date_debut"] == $demande["date_suplementaire"]) {
                                                //if(strtotime($demande["heure_debut_supplementaire"])>= strtotime("11:00:00") && strtotime($demande["heure_debut_supplementaire"]) < strtotime("11:20:00")){
                                                $list_allaitement[$demande["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $demande["heure_fin_supplementaire"]];
                                                //}
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $str_matricule = substr($str_matricule, 0, -1);
                        $str_matricule .= ")";
                        if (count($list_allaitement) > 0) {
                            $extraExiste = true;
                        }
                    }
                }


                $prodConnex = new \App\Model\GPAOModels\Production($connex);
                /**
                 * sql prod
                 */
                $sqlProd = $prodConnex->Get([
                    "personnel.id_type_pointage",
                    "personnel.id_personnel",
                    "personnel.login",
                    "etape_travail.objectif",
                    "production.volume",
                    "production.temps_realisation",
                    "production.date_traitement",
                    "production.heure_debut",
                    "production.heure_fin",
                    "etape_travail.nom_etape",
                    "dossier_client.dossier_acp"

                ])->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                    ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                    //->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')
                    ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail');
                if (empty($matricule)) {
                    $sqlProd->where('personnel.nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')');
                } else {
                    $sqlProd->where('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                }
                if ($equipe == 1) {
                    $sqlProd->andWhere('personnel.id_type_pointage = :id_type')
                        ->setParameter('id_type', $equipe);
                } else {
                    if ($equipe == 24) {
                        $sqlProd->andWhere('personnel.id_type_pointage = :id_type')
                            ->setParameter('id_type', $equipe);
                    }
                }
                $sqlProd->andWhere('date_traitement BETWEEN :db AND :df')
                    ->setParameter('db', $dateFiltre[0])
                    ->setParameter('df', $dateFiltre[1])
                    ->andWhere('dossier_acp = :dossier')
                    ->setParameter('dossier', 'NON')
                    ->andWhere('nom_etape NOT IN (\'VALIDATION_ECHANT\',\'ETAPE1\',\'ETAPE2\')')
                    //->setParameter('nom_etape', "VALIDATION_ECHANT")
                    ->orderBy("personnel.id_personnel", "ASC");

                $prods = $sqlProd->execute()->fetchAll();


                if (count($prods) > 0) {

                    //$list_demandes_supl[$demande["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $demande["heure_fin_supplementaire"]];  
                    foreach ($prods as $prod) {
                        $taux = 0;
                        $userDoExtra = false;
                        if ((float)$prod["temps_realisation"] != 0) {
                            $vitesse = $prod["volume"] / $prod["temps_realisation"];
                            $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                            if ($objectif != 0) {
                                $taux = $vitesse * 100 / $objectif;
                            } else {
                                $taux = 0;
                            }
                        } else {
                            $taux = 0;
                        }

                        /**
                         * extra et production
                         */
                        if ($extraExiste) {

                            foreach ($labelGraph as $date) {
                                $dateProdOnly = $date;
                                $isDateProdFound = false;
                                foreach ($list_allaitement as $matricule => $data) {
                                    foreach ($data as $date => $heure) {
                                        //if($prod["nom_etape"] != "VALIDATION_ECHANT"){
                                        if ($prod["id_personnel"] == $matricule) {
                                            $userDoExtra = true;
                                            if ($date == $prod["date_traitement"]) {
                                                $isExtra = false;
                                                $isDateProdFound = true;
                                                /**
                                                 * extra
                                                 */
                                                if (strtotime($prod["heure_debut"]) < strtotime($heure[1]) && strtotime($prod["heure_debut"]) >= strtotime($heure[0])) {
                                                    $isExtra = true;
                                                }
                                                if ($isExtra) {

                                                    if (count($tabByOperateurs) == 0) {

                                                        $tabByOperateurs[$matricule] = [
                                                            "login" => $prod["login"],
                                                            "equipe" => $prod["id_type_pointage"],
                                                            "taux" => [
                                                                "extra" => $taux,
                                                                "nb_extra" => 1,
                                                                "production" => 0,
                                                                "nb_production" => 0
                                                            ]
                                                        ];
                                                    } else {

                                                        if (array_key_exists($prod["id_personnel"], $tabByOperateurs)) {

                                                            $nb_extra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_extra"];
                                                            $tauxExtra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["extra"];
                                                            $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_extra"] = $nb_extra + 1;
                                                            $tabByOperateurs[$prod["id_personnel"]]["taux"]["extra"] = $tauxExtra + $taux;
                                                        } else {

                                                            $tabByOperateurs[$matricule] = [
                                                                "login" => $prod["login"],
                                                                "equipe" => $prod["id_type_pointage"],
                                                                "taux" => [
                                                                    "extra" => $taux,
                                                                    "nb_extra" => 1,
                                                                    "production" => 0,
                                                                    "nb_production" => 0
                                                                ]
                                                            ];
                                                        }
                                                    }
                                                    /**
                                                     * extra et production normal (matin sy apm) graphique
                                                     */
                                                    if ($prod["id_type_pointage"] == 1) {
                                                        if ($date == $prod["date_traitement"]) {
                                                            /**
                                                             * extra matin
                                                             */
                                                            if (strtotime($prod["heure_debut"]) < strtotime($heure[1]) && strtotime($prod["heure_debut"]) >= strtotime($heure[0])) {

                                                                if (!array_key_exists($date, $extrasGeneralEquipeMatin)) {
                                                                    $extrasGeneralEquipeMatin[$date] = ["nb" => 1, "taux" => $taux];
                                                                } else {
                                                                    $tauxMatin = $extrasGeneralEquipeMatin[$date]["taux"];
                                                                    $extrasGeneralEquipeMatin[$date]["taux"] = $tauxMatin + $taux;
                                                                    $nb = $extrasGeneralEquipeMatin[$date]["nb"];
                                                                    $extrasGeneralEquipeMatin[$date]["nb"] = $nb + 1;
                                                                }
                                                            } else {
                                                                /**
                                                                 * production matin
                                                                 */

                                                                if (!array_key_exists($date, $productionNormalGeneralEquipeMatin)) {
                                                                    $productionNormalGeneralEquipeMatin[$date] = ["nb" => 1, "taux" => $taux];
                                                                } else {
                                                                    $tauxMatin = $productionNormalGeneralEquipeMatin[$date]["taux"];
                                                                    $productionNormalGeneralEquipeMatin[$date]["taux"] = $tauxMatin + $taux;
                                                                    $nb = $productionNormalGeneralEquipeMatin[$date]["nb"];
                                                                    $productionNormalGeneralEquipeMatin[$date]["nb"] = $nb + 1;
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        if ($date == $prod["date_traitement"]) {
                                                            /**
                                                             * extra APM
                                                             */
                                                            if (strtotime($prod["heure_debut"]) < strtotime($heure[1]) && strtotime($prod["heure_debut"]) >= strtotime($heure[0])) {

                                                                if (!array_key_exists($date, $extrasGeneralEquipeAPM)) {
                                                                    $extrasGeneralEquipeAPM[$date] = ["nb" => 1, "taux" => $taux];
                                                                } else {
                                                                    $tauxMatin = $extrasGeneralEquipeAPM[$date]["taux"];
                                                                    $extrasGeneralEquipeAPM[$date]["taux"] = $tauxMatin + $taux;
                                                                    $nb = $extrasGeneralEquipeAPM[$date]["nb"];
                                                                    $extrasGeneralEquipeAPM[$date]["nb"] = $nb + 1;
                                                                }
                                                            } else {
                                                                /**
                                                                 * production APM
                                                                 */

                                                                if (!array_key_exists($date, $productionNormalGeneralEquipeAPM)) {
                                                                    $productionNormalGeneralEquipeAPM[$date] = ["nb" => 1, "taux" => $taux];
                                                                } else {
                                                                    $tauxMatin = $productionNormalGeneralEquipeAPM[$date]["taux"];
                                                                    $productionNormalGeneralEquipeAPM[$date]["taux"] = $tauxMatin + $taux;
                                                                    $nb = $productionNormalGeneralEquipeAPM[$date]["nb"];
                                                                    $productionNormalGeneralEquipeAPM[$date]["nb"] = $nb + 1;
                                                                }
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    /**
                                                     * production olona nanw extra
                                                     * tableau
                                                     */

                                                    if (count($tabByOperateurs) == 0) {
                                                        $tabByOperateurs[$matricule] = [
                                                            "login" => $prod["login"],
                                                            "equipe" => $prod["id_type_pointage"],
                                                            "taux" => [
                                                                "extra" => 0,
                                                                "nb_extra" => 0,
                                                                "production" => $taux,
                                                                "nb_production" => 1
                                                            ]
                                                        ];
                                                    } else {

                                                        if (array_key_exists($prod["id_personnel"], $tabByOperateurs)) {

                                                            $nb_extra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"];
                                                            $tauxExtra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"];
                                                            $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"] = $nb_extra + 1;
                                                            $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"] = $tauxExtra + $taux;
                                                        } else {
                                                            $tabByOperateurs[$matricule] = [
                                                                "login" => $prod["login"],
                                                                "equipe" => $prod["id_type_pointage"],
                                                                "taux" => [
                                                                    "extra" => 0,
                                                                    "nb_extra" => 0,
                                                                    "production" => $taux,
                                                                    "nb_production" => 1
                                                                ]
                                                            ];
                                                        }
                                                    }
                                                    /**
                                                     * graphique
                                                     */

                                                    /**
                                                     * production normal matin graphp
                                                     */
                                                    if ($prod["id_type_pointage"] == 1) {
                                                        if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeMatin)) {
                                                            $productionNormalGeneralEquipeMatin[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                                        } else {
                                                            $tauxMatin = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"];
                                                            $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                                            $nb = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"];
                                                            $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"] = $nb + 1;
                                                        }
                                                    } else {
                                                        /**
                                                         * production normal APM graph
                                                         */
                                                        if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeAPM)) {
                                                            $productionNormalGeneralEquipeAPM[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                                        } else {
                                                            $tauxMatin = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"];
                                                            $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                                            $nb = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"];
                                                            $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"] = $nb + 1;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        //}
                                    }
                                }
                                /**
                                 * nanw extra iz t@interval FA MIS DATY IRAY TSY NANAOVAN EXTRA
                                 */
                                if (!$isDateProdFound) {

                                    if (count($tabByOperateurs) == 0) {
                                        $tabByOperateurs[$prod["id_personnel"]] = [
                                            "login" => $prod["login"],
                                            "equipe" => $prod["id_type_pointage"],
                                            "taux" => [
                                                "extra" => 0,
                                                "nb_extra" => 0,
                                                "production" => $taux,
                                                "nb_production" => 1
                                            ]
                                        ];
                                    } else {

                                        if (array_key_exists($prod["id_personnel"], $tabByOperateurs)) {

                                            $nb_extra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"];
                                            $tauxExtra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"];
                                            $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"] = $nb_extra + 1;
                                            $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"] = $tauxExtra + $taux;
                                        } else {
                                            $tabByOperateurs[$prod["id_personnel"]] = [
                                                "login" => $prod["login"],
                                                "equipe" => $prod["id_type_pointage"],
                                                "taux" => [
                                                    "extra" => 0,
                                                    "nb_extra" => 0,
                                                    "production" => $taux,
                                                    "nb_production" => 1
                                                ]
                                            ];
                                        }
                                    }
                                    /**
                                     * production normal matin
                                     */
                                    if ($prod["id_type_pointage"] == 1) {
                                        if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeMatin)) {
                                            $productionNormalGeneralEquipeMatin[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                        } else {
                                            $tauxMatin = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"];
                                            $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                            $nb = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"];
                                            $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"] = $nb + 1;
                                        }
                                    } else {
                                        /**
                                         * production normal APM
                                         */
                                        if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeAPM)) {
                                            $productionNormalGeneralEquipeAPM[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                        } else {
                                            $tauxMatin = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"];
                                            $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                            $nb = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"];
                                            $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"] = $nb + 1;
                                        }
                                    }
                                }
                            }
                            /**
                             * tsy nanw extra mits fa production normal
                             */
                            if (!$userDoExtra) {

                                /**
                                 * production fotsiny ny ato
                                 */

                                if (count($tabByOperateurs) == 0) {
                                    $tabByOperateurs[$prod["id_personnel"]] = [
                                        "login" => $prod["login"],
                                        "equipe" => $prod["id_type_pointage"],
                                        "taux" => [
                                            "extra" => 0,
                                            "nb_extra" => 0,
                                            "production" => $taux,
                                            "nb_production" => 1
                                        ]
                                    ];
                                } else {

                                    if (array_key_exists($prod["id_personnel"], $tabByOperateurs)) {

                                        $nb_extra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"];
                                        $tauxExtra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"];
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"] = $nb_extra + 1;
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"] = $tauxExtra + $taux;
                                    } else {
                                        $tabByOperateurs[$prod["id_personnel"]] = [
                                            "login" => $prod["login"],
                                            "equipe" => $prod["id_type_pointage"],
                                            "taux" => [
                                                "extra" => 0,
                                                "nb_extra" => 0,
                                                "production" => $taux,
                                                "nb_production" => 1
                                            ]
                                        ];
                                    }
                                }

                                /**
                                 * production normal matin
                                 */
                                if ($prod["id_type_pointage"] == 1) {
                                    if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeMatin)) {
                                        $productionNormalGeneralEquipeMatin[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                    } else {
                                        $tauxMatin = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"];
                                        $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                        $nb = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"];
                                        $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"] = $nb + 1;
                                    }
                                } else {
                                    /**
                                     * production normal APM
                                     */
                                    if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeAPM)) {
                                        $productionNormalGeneralEquipeAPM[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                    } else {
                                        $tauxMatin = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"];
                                        $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                        $nb = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"];
                                        $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"] = $nb + 1;
                                    }
                                }
                            }
                        } else {
                            //tsy mis ul  nanw extra fa prod dul

                            if (count($tabByOperateurs) == 0) {

                                $tabByOperateurs[$prod["id_personnel"]] = [
                                    "login" => $prod["login"],
                                    "equipe" => $prod["id_type_pointage"],
                                    "taux" => [
                                        "extra" => 0,
                                        "nb_extra" => 0,
                                        "production" => $taux,
                                        "nb_production" => 1
                                    ]
                                ];
                            } else {

                                if (array_key_exists($prod["id_personnel"], $tabByOperateurs)) {

                                    $nb_extra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"];
                                    $tauxExtra = $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"];
                                    $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"] = $nb_extra + 1;
                                    $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"] = $tauxExtra + $taux;
                                } else {
                                    $tabByOperateurs[$prod["id_personnel"]] = [
                                        "login" => $prod["login"],
                                        "equipe" => $prod["id_type_pointage"],
                                        "taux" => [
                                            "extra" => 0,
                                            "nb_extra" => 0,
                                            "production" => $taux,
                                            "nb_production" => 1
                                        ]
                                    ];
                                }
                            }

                            /**
                             * production normal matin graph
                             */
                            if ($prod["id_type_pointage"] == 1) {

                                if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeMatin)) {
                                    $productionNormalGeneralEquipeMatin[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                } else {

                                    $tauxMatin = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"];
                                    $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                    $nb = $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"];
                                    $productionNormalGeneralEquipeMatin[$prod["date_traitement"]]["nb"] = $nb + 1;
                                }
                            } else {
                                /**
                                 * production normal APM graphp
                                 */
                                if (!array_key_exists($prod["date_traitement"], $productionNormalGeneralEquipeAPM)) {
                                    $productionNormalGeneralEquipeAPM[$prod["date_traitement"]] = ["nb" => 1, "taux" => $taux];
                                } else {
                                    $tauxMatin = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"];
                                    $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["taux"] = $tauxMatin + $taux;
                                    $nb = $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"];
                                    $productionNormalGeneralEquipeAPM[$prod["date_traitement"]]["nb"] = $nb + 1;
                                }
                            }

                            //}
                        }
                    }
                } else {
                    /**
                     * raha mapiditra matricule ka tsis prod dia atw 0 ny extra, 0 ny production
                     */
                    if (!empty($matricule)) {
                        $pers = $pers->Get([
                            "id_personnel",
                            "login",
                            "id_type_pointage"
                        ])->where('personnel.id_personnel = :id_personnel')
                            ->setParameter('id_personnel', $matricule)

                            ->execute()->fetch();

                        $tabByOperateurs[$matricule] = [
                            "login" => $pers["login"],
                            "equipe" => $pers["id_type_pointage"],
                            "taux" => [
                                "extra" => 0,
                                "nb_extra" => 0,
                                "production" => 0,
                                "nb_production" => 0
                            ]
                        ];
                    }
                }
            }
        }
        /**
         * moyenne extra et moyenne production normal dans un interval de date
         */
        $nb_extra = 0;
        $nb_production = 0;
        $total_extra = 0;
        $total_production = 0;

        if (count($tabByOperateurs) > 0) {
            /**
             * calcule de moyenne par matricule
             */
            foreach ($tabByOperateurs as $matricule => $data) {
                /**
                 * extra
                 */
                if ($data["taux"]["nb_extra"] > 0) {
                    $tabByOperateurs[$matricule]["taux"]["extra"] = $data["taux"]["extra"] / $data["taux"]["nb_extra"];
                }
                /**
                 * production
                 */
                if ($data["taux"]["nb_production"] > 0) {
                    $tabByOperateurs[$matricule]["taux"]["production"] = $data["taux"]["production"] / $data["taux"]["nb_production"];
                }

                $nb_extra += 1;
                $nb_production += 1;
                $total_extra += $tabByOperateurs[$matricule]["taux"]["extra"];
                $total_production += $tabByOperateurs[$matricule]["taux"]["production"];
            }

            $taux_extra = $total_extra / $nb_extra;
            $taux_production = $total_production / $nb_production;
        }


        if (count($productionNormalGeneralEquipeAPM) > 0 || count($productionNormalGeneralEquipeMatin) > 0 || count($extrasGeneralEquipeAPM) > 0 || count($extrasGeneralEquipeMatin) > 0) {
            /**
             * trie par date
             */
            ksort($extrasGeneralEquipeAPM);
            ksort($extrasGeneralEquipeMatin);
            ksort($productionNormalGeneralEquipeMatin);
            ksort($productionNormalGeneralEquipeAPM);

            foreach ($labelGraph as $date) {
                if (!array_key_exists($date, $extrasGeneralEquipeMatin) && !array_key_exists($date, $extrasGeneralEquipeAPM)) {
                    $extrasMatin[] = 0;
                    $extrasGeneral[] = 0;
                } else {
                    if (array_key_exists($date, $extrasGeneralEquipeAPM) && !array_key_exists($date, $extrasGeneralEquipeMatin)) {
                        $extrasGeneral[] = ($extrasGeneralEquipeAPM[$date]["taux"] / $extrasGeneralEquipeAPM[$date]["nb"]) / 2;
                    } else {
                        if (!array_key_exists($date, $extrasGeneralEquipeAPM) && array_key_exists($date, $extrasGeneralEquipeMatin)) {
                            $extrasGeneral[] = ($extrasGeneralEquipeMatin[$date]["taux"] / $extrasGeneralEquipeMatin[$date]["nb"]) / 2;
                        } else {
                            $extrasGeneral[] = ($extrasGeneralEquipeMatin[$date]["taux"] / $extrasGeneralEquipeMatin[$date]["nb"] + ($extrasGeneralEquipeAPM[$date]["taux"] / $extrasGeneralEquipeAPM[$date]["nb"]) / 2);
                        }
                    }
                }

                if (!array_key_exists($date, $productionNormalGeneralEquipeMatin) && !array_key_exists($date, $productionNormalGeneralEquipeAPM)) {
                    $productionsMatin[] = 0;
                    $productionGeneral[] = 0;
                } else {
                    if (array_key_exists($date, $productionNormalGeneralEquipeAPM) && !array_key_exists($date, $productionNormalGeneralEquipeMatin)) {
                        $productionGeneral[] = ($productionNormalGeneralEquipeAPM[$date]["taux"] / $productionNormalGeneralEquipeAPM[$date]["nb"]) / 2;
                    } else {
                        if (!array_key_exists($date, $productionNormalGeneralEquipeAPM) && array_key_exists($date, $productionNormalGeneralEquipeMatin)) {
                            $productionGeneral[] = ($productionNormalGeneralEquipeMatin[$date]["taux"] / $productionNormalGeneralEquipeMatin[$date]["nb"]) / 2;
                        } else {
                            $productionGeneral[] = ($productionNormalGeneralEquipeMatin[$date]["taux"] / $productionNormalGeneralEquipeMatin[$date]["nb"] + ($productionNormalGeneralEquipeAPM[$date]["taux"] / $productionNormalGeneralEquipeAPM[$date]["nb"]) / 2);
                        }
                    }
                }
            }


            for ($i = 0; $i < count($extrasGeneral); $i++) {
                $moyenneExtra += $extrasGeneral[$i];
                $moyenneProd += $productionGeneral[$i];
            }
            $moyenneExtra = $moyenneExtra / count($extrasGeneral);
            $moyenneProd = $moyenneProd / count($productionGeneral);
        }


        return $this->render('dossier/comparaison.html.twig', [
            "form" => $form->createView(),
            "listOperateurExtraProd" => $tabByOperateurs,
            "taux_extra" => $taux_extra,
            "taux_production" => $taux_production,
            "label" => $labelGraph,

            "extrasMatin" => $extrasMatin,
            "extrasAPM" => $extrasAPM,
            "productionMatin" => $productionsMatin,
            "productionAPM" => $productionsAPM,

            "extrasParDate" => [$taux_extra],
            "productionsParDate" => [$taux_production],

            "moyenneExtra" => $moyenneExtra,
            "moyenneProduction" => $moyenneProd
        ]);
    }

    private function createIntervalDate($jD, $mD, $yD, $dF, $mF, $yF)
    {
        $debut_jour = $jD;
        $debut_mois = $mD;
        $debut_annee = $yD;

        $fin_jour = $dF;
        $fin_mois = $mF;
        $fin_annee = $yF;

        $debut_date = mktime(0, 0, 0, $debut_mois, $debut_jour, $debut_annee);
        $fin_date = mktime(0, 0, 0, $fin_mois, $fin_jour, $fin_annee);

        $interval_date = [];
        for ($i = $debut_date; $i <= $fin_date; $i += 86400) {
            $interval_date[] = date('d-m-Y', $i);
        }
        return $interval_date;
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function compExtraProd(Connection $connex, Request $request)
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        $labelGraph = [];
        $data = null;
        $list_allaitement = [];
        $pers = new \App\Model\GPAOModels\Personnel($connex);

        $tabByOperateurs = [];

        $taux_moyenne_per_days_production = [];
        $taux_moyenne_per_days_extra = [];
        $taux_moyenne_per_days_complement = [];

        $moyenneProd = [
            "taux" => 0,
            "nb" => 0,
            "moyenne_taux" => 0
        ];
        $moyenneExtra = [
            "taux" => 0,
            "nb" => 0,
            "moyenne_taux" => 0
        ];
        $moyenneComplement = [
            "taux" => 0,
            "nb" => 0,
            "moyenne_taux" => 0
        ];

        $list_personnel = []; //list contenant tous les extras

        $taux_moyenne_extra = null;
        $taux_moyenne_production = null;
        $taux_moyenne_complement = null;

        $date_bdd = null;

        /**
         * personnel form select
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom",
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }

        /**
         * form 
         */
        $form = $this->createFormBuilder()
            ->add('operateur', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => $operateurs,
            ])
            ->add('dates', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])
            ->add('equipe', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "required" => false,
                "choices" => [
                    "Matin" => 1,
                    "APM" => 24
                ]
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

            $sqlDemandeExtras = $demandeSupplementaire->Get([
                //"personnel.id_personnel",
                "date_suplementaire",
                "heure_debut_supplementaire",
                "heure_fin_supplementaire",

                //"type_pointage.id_type_pointage",
                "personnel.id_personnel",
                "personnel.nom",
                "personnel.login",
                "personnel.nom_fonction",
            ])
                //->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                //->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('demande_supplementaire.etat_validation = :etat')
                ->setParameter('etat', "Accorder");

            /**
             * sql extra
             * id_type_pointage extra
             */
            $pointage = new \App\Model\GPAOModels\Pointage($connex);
            $sqlPointage = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description", "date_debut"]);
            // ->where("description = :desc")
            // ->setParameter("desc", "Extra");

            $data = $form->getData();
            $matricule = $data["operateur"];
            $date = $data["dates"];
            $equipe = $data["equipe"];

            $dateisValid = TRUE;
            $dateFiltre = [];

            //filtrage-ny date oe valide v
            if (!empty($date)) {
                $interval_dates = explode(' - ', $date);
                if (count($interval_dates) == 1) {
                    $dates[] = $date;
                } else {
                    $dates[] = $interval_dates[0];
                    $dates[] = $interval_dates[1];

                    $date1 = explode('/', $interval_dates[0]);
                    $date2 = explode('/', $interval_dates[1]);

                    $labelGraph = $this->createIntervalDate($date1[0], $date1[1], $date1[2], $date2[0], $date2[1], $date2[2]);

                    /**
                     * date au format YYYY-mm-dd
                     */
                    foreach ($labelGraph as $key => $date) {
                        $dateInverse =  implode('-', array_reverse(explode('-', $date)));
                        $labelGraph[$key] = $dateInverse;
                    }
                }

                try {
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[0]))));
                    //dd($dt);
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }
                try {
                    //$dt = new \DateTime($dates[1]);
                    $dt = new \DateTime(implode('-', array_reverse(explode('/', $dates[1]))));
                } catch (\Exception $e) {
                    $dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide");
                    return $this->redirectToRoute("dossier_extra_v2");
                }

                $dateFiltre = $interval_dates;
            }

            if (count($dateFiltre) > 0) {
                $list_id_pointages = [];
                $pointages_extra_or_complement = [];
                $pointages = $sqlPointage //->andWhere('personnel.id_personnel '. $str_matricule.' OR personnel.type_contrat = :type_contrat OR type_pointage.description LIKE :complement')
                    ->andWhere('type_pointage.description LIKE :extraProd OR personnel.type_contrat = :type_contrat OR type_pointage.description LIKE :complement')
                    ->andWhere('personnel.nom_fonction IN (\'OP 1\', \'OP 2\', \'CORE 1\', \'CORE 2\')')
                    ->setParameter('extraProd', "Extra%")
                    ->setParameter('type_contrat', "EXTRA")
                    ->andWhere('date_debut BETWEEN :db and :df')
                    ->setParameter('db', $dateFiltre[0])
                    ->setParameter('df', $dateFiltre[1])
                    ->setParameter('complement', 'Complement%')
                    ->orderBy('date_debut', 'ASC')
                    ->orderBy("personnel.id_personnel", "ASC")

                    ->execute()->fetchAll();

                foreach ($pointages as $pointage) {
                    if (!in_array($pointage["id_personnel"], $list_id_pointages)) {
                        $list_id_pointages[] = $pointage["id_personnel"];
                    }
                    $heure_sortie = $pointage["heure_sortie"];
                    $heure_entre = $pointage["heure_entre"];
                    if (is_null($heure_sortie)) {
                        if ($pointage["id_type_pointage"] == 24) {
                            $heure_sortie = "18:30:00";
                        } else {
                            $heure_sortie = "12:10:00";
                        }
                    }
                    if (!in_array($pointage["id_personnel"], $pointages_extra_or_complement)) {
                        if (preg_match('/Complement/', $pointage["description"])) {
                            $pointages_extra_or_complement[$pointage["id_personnel"]]["complement"][$pointage["date_debut"]] = [$heure_entre, $heure_sortie];
                        } else {
                            $pointages_extra_or_complement[$pointage["id_personnel"]]["extra"][$pointage["date_debut"]] = [$heure_entre, $heure_sortie];
                        }
                    }
                }



                $prodConnex = new \App\Model\GPAOModels\Production($connex);
                /**
                 * sql prod
                 */
                $sqlProd = $prodConnex->Get([
                    "personnel.type_contrat",
                    "personnel.id_type_pointage",
                    "personnel.id_personnel",
                    "personnel.login",
                    "etape_travail.objectif",
                    "production.volume",
                    "production.temps_realisation",
                    "production.date_traitement",
                    "production.heure_debut",
                    "production.heure_fin",
                    "etape_travail.nom_etape",
                    "dossier_client.dossier_acp",
                    "type_pointage.description"

                ])->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                    ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                    ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                    //->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail and dossier_client.nom_dossier=fichiers.nom_dossier')
                    //->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')
                    ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail');
                if (empty($matricule)) {
                    //$sqlProd->andWhere('personnel.nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')'); 
                    $sqlProd->andWhere("personnel.id_personnel IN (" . implode(",", $list_id_pointages) . ")");
                } else {
                    $sqlProd->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule);
                }
                if ($equipe == 1) {
                    $sqlProd->andWhere('personnel.id_type_pointage = :id_type')
                        ->setParameter('id_type', $equipe);
                } else {
                    if ($equipe == 24) {
                        $sqlProd->andWhere('personnel.id_type_pointage = :id_type')
                            ->setParameter('id_type', $equipe);
                    }
                }
                $sqlProd->andWhere('date_traitement BETWEEN :db AND :df')
                    //->orWhere('personnel.type_contrat = :type_contrat')
                    //->setParameter('type_contrat', "EXTRA")
                    ->setParameter('db', $dateFiltre[0])
                    ->setParameter('df', $dateFiltre[1])
                    ->andWhere('dossier_acp = :dossier')
                    ->setParameter('dossier', 'NON')
                    ->andWhere('nom_etape NOT IN (\'VALIDATION_ECHANT\',\'ETAPE1\',\'ETAPE2\')')
                    //->setParameter('nom_etape', "VALIDATION_ECHANT")
                    ->orderBy("production.date_traitement", "ASC")
                    ->orderBy("personnel.id_personnel", "ASC");
                //dd($sqlProd->execute());
                $prods = $sqlProd->execute()->fetchAll();

                //return new Response("<html><body>Arrivé</body></html>");
                foreach ($pointages_extra_or_complement as $matricule => $data) {
                    foreach ($prods as $prod) {

                        /**
                         * calcule de taux
                         */
                        $taux = 0;
                        if ((float)$prod["temps_realisation"] != 0) {
                            $vitesse = $prod["volume"] / $prod["temps_realisation"];
                            $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                            if ($objectif != 0) {
                                $taux = $vitesse * 100 / $objectif;
                            } else {
                                $taux = 0;
                            }
                        } else {
                            $taux = 0;
                        }
                        $isProdNormal = true;
                        $extraOrComplement = null;
                        if ($matricule == $prod["id_personnel"]) {
                            foreach ($data as $key => $values) {
                                $extraOrComplement = $key;
                                foreach ($values as $date => $heures) {
                                    if ($date == $prod["date_traitement"]) {
                                        if ($prod["heure_debut"] >= $heures[0] && $prod["heure_debut"] <= $heures[1]) {
                                            $isProdNormal = false;
                                        }
                                    }
                                }
                            }
                            /**
                             * moyenne par personne prod ou extra ou complement
                             */
                            if (!array_key_exists($prod["id_personnel"], $tabByOperateurs)) {
                                $tabByOperateurs[$prod["id_personnel"]]["login"] = $prod["login"];
                                $tabByOperateurs[$prod["id_personnel"]]["equipe"] = $prod["id_type_pointage"];
                                $taux_tab = [
                                    "production" => 0,
                                    "nb_production" => 0,
                                    "moyen_production" => 0,
                                    "complement" => 0,
                                    "nb_complement" => 0,
                                    "moyen_complement" => 0,
                                    "extra" => 0,
                                    "nb_extra" => 0,
                                    "moyen_extra" => 0
                                ];
                                if ($isProdNormal) {
                                    $taux_tab["production"] = $taux;
                                    $taux_tab["nb_production"] = 1;
                                } else {
                                    if (preg_match('/compl/', $extraOrComplement)) {
                                        $taux_tab["complement"] = $taux;
                                        $taux_tab["nb_complement"] = 1;
                                    } else {
                                        $taux_tab["extra"] = $taux;
                                        $taux_tab["nb_extra"] = 1;
                                    }
                                }
                                $tabByOperateurs[$prod["id_personnel"]]["taux"] = $taux_tab;
                            } else {
                                if ($isProdNormal) {
                                    $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"] + $taux;
                                    $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"] + 1;
                                    $tabByOperateurs[$prod["id_personnel"]]["taux"]["moyen_production"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["production"] / $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_production"];
                                } else {
                                    if (preg_match('/compl/', $extraOrComplement)) {
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["complement"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["complement"] + $taux;
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_complement"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_complement"] + 1;
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["moyen_complement"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["complement"] / $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_complement"];
                                    } else {
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["extra"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["extra"] + $taux;
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_extra"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_extra"] + 1;
                                        $tabByOperateurs[$prod["id_personnel"]]["taux"]["moyen_extra"] = $tabByOperateurs[$prod["id_personnel"]]["taux"]["extra"] / $tabByOperateurs[$prod["id_personnel"]]["taux"]["nb_extra"];
                                    }
                                }
                            }
                            /**
                             * moyenne general prod, complement, extra
                             */
                            if ($isProdNormal) {
                                $moyenneProd["taux"] += $taux;
                                $moyenneProd["nb"] += 1;
                                $taux_moyenne_production = $moyenneProd["taux"] / $moyenneProd["nb"];
                                //$moyenneProd["moyenne_taux"] = $moyenneProd["taux"] / $moyenneProd["nb"];
                                /**
                                 * moyenne prod par date
                                 */
                                if (!array_key_exists($prod["date_traitement"], $taux_moyenne_per_days_production)) {
                                    $taux_moyenne_per_days_production[$prod["date_traitement"]] = $taux;
                                    // $taux_moyenne_per_days_production[$prod["date_traitement"]]["nb_lignes"] = 1;
                                } else {
                                    $taux_moyenne_per_days_production[$prod["date_traitement"]] = ($taux_moyenne_per_days_production[$prod["date_traitement"]] + $taux) / 2;
                                    // $taux_moyenne_per_days_production[$prod["date_traitement"]]["nb_lignes"] += 1; 
                                }
                            } else {
                                if (preg_match('/compl/', $extraOrComplement)) {
                                    $moyenneComplement["taux"] += $taux;
                                    $moyenneComplement["nb"] += 1;
                                    // $moyenneComplement["moyenne_taux"] = $moyenneComplement["taux"] / $moyenneComplement["nb"];
                                    $taux_moyenne_complement = $moyenneComplement["taux"] / $moyenneComplement["nb"];
                                    if (!array_key_exists($prod["date_traitement"], $taux_moyenne_per_days_complement)) {
                                        $taux_moyenne_per_days_complement[$prod["date_traitement"]] = $taux;
                                        // $taux_moyenne_per_days_complement[$prod["date_traitement"]]["nb_lignes"] = 1;
                                    } else {
                                        $taux_moyenne_per_days_complement[$prod["date_traitement"]] = ($taux_moyenne_per_days_complement[$prod["date_traitement"]] + $taux) / 2;
                                        // $taux_moyenne_per_days_complement[$prod["date_traitement"]]["nb_lignes"] += 1;
                                    }
                                } else {
                                    $moyenneExtra["taux"] += $taux;
                                    $moyenneExtra["nb"] += 1;
                                    $taux_moyenne_extra = $moyenneExtra["taux"] / $moyenneExtra["nb"];
                                    // $moyenneExtra["moyenne_taux"] = $moyenneExtra["taux"] / $moyenneExtra["nb"];
                                    if (!array_key_exists($prod["date_traitement"], $taux_moyenne_per_days_extra)) {
                                        $taux_moyenne_per_days_extra[$prod["date_traitement"]] = $taux;
                                        // $taux_moyenne_per_days_extra[$prod["date_traitement"]]["nb_lignes"] = 1;
                                    } else {
                                        $taux_moyenne_per_days_extra[$prod["date_traitement"]] = ($taux_moyenne_per_days_extra[$prod["date_traitement"]] + $taux) / 2;
                                        // $taux_moyenne_per_days_extra[$prod["date_traitement"]]["nb_lignes"] += 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        dump("tab", $tabByOperateurs);
        dump("moyenne extra", $taux_moyenne_extra, "moyenne prod", $taux_moyenne_production, "moyenne complement", $taux_moyenne_complement);
        dump("data statistiques", $taux_moyenne_per_days_extra, $taux_moyenne_per_days_production, $taux_moyenne_per_days_complement);
        return $this->render('dossier/comparaison.html.twig', [
            "form" => $form->createView(),
            "listOperateurExtraProd" => $tabByOperateurs,
            "taux_extra" => $taux_moyenne_extra,
            "taux_production" => $taux_moyenne_production,
            "taux_complement" => $taux_moyenne_complement,
            "label" => $labelGraph,

            // "extrasMatin" => $extrasMatin,
            // "extrasAPM" => $extrasAPM,
            // "productionMatin" => $productionsMatin,
            // "productionAPM" => $productionsAPM,

            "extrasParDate" => $taux_moyenne_per_days_extra, //[$taux_extra],
            "productionsParDate" => $taux_moyenne_per_days_production,
            "complementParDate" => $taux_moyenne_per_days_complement //[$taux_production],
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function extraGeneral(Connection $connex, Request $request)
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        $prod_final = [];
        $total_prix = 0;
        $taux_reussite_general = 0;
        $nb_lignes = 0;
        $list_dossier_not_comptabilise = [];
        $total_facturable = 0;
        $name_excel = "";
        $exportToExcel = null;
        $operateurs = [];
        $list_dossier_facture = [];
        $list_dossier_no_facture = [];
        $list_dossier_dont_price = [];

        $pers = new \App\Model\GPAOModels\Personnel($connex);
        $prod = new \App\Model\GPAOModels\Production($connex);
        $demandeExtras = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

        /**
         * personnel form select formulaire
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $data) {
            $operateurs[$data["id_personnel"] . " - " . $data["nom"] . " " . $data["prenom"]] = $data["id_personnel"];
        }

        /**
         * form 
         */


        $form = $this->createFormBuilder()
            ->add('matricule', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "required" => false,
                "choices" =>  $operateurs,
                "placeholder" => "-Selectionnez-"
            ])

            ->add('interval_date_extra', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
            ])
            ->add('export', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,
            ])
            ->add('isFacturable', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,

            ])

            ->getForm();
        //dump("ok");
        /**
         * submit form
         */
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            $matricule = $data["matricule"];
            $matr = $matricule;
            $dates = $data["interval_date_extra"];
            $isFacturable = $data["isFacturable"];
            $exportToExcel = $data["export"];
            //dd($dates);

            if (!empty($dates)) {

                /**
                 * preparation du requete de production
                 */
                $productions = [];
                $prodConnex = new \App\Model\GPAOModels\Production($connex);
                $whereBegin = false;
                $fields = [
                    "personnel.id_type_pointage",
                    "personnel.id_personnel",
                    "personnel.type_contrat",
                    "type_pointage.description as type_pointage",
                    "fichiers.nom_fichiers",
                    "fichiers.nom_dossier",
                    "personnel.nom",
                    "personnel.prenom",
                    "personnel.login",
                    "etape_travail.nom_etape",
                    "etape_travail.objectif",
                    "production.volume",
                    "production.temps_realisation",
                    "etape_travail.prix",
                    "production.etat",
                    "production.heure_debut",
                    "production.heure_fin",
                    "dossier_client.facturable",
                    "dossier_client.date_reel_livraison",
                    "production.date_traitement"
                ];

                $sqlProd = $prodConnex->Get(
                    $fields
                )->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                    ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                    ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                    ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail and dossier_client.nom_dossier=fichiers.nom_dossier')
                    ->andWhere('nom_etape NOT IN(\'PREPARATION\',\'DECOUPE\',\'CQ-DECOUPE\',\'CQ_DECOUPE\',\'CQ DECOUPE\',\'RELECTURE\',\'VALIDATION_ECHANT\',\'SUBDIVISION\')')
                    ->andWhere('(date_reel_livraison BETWEEN :db AND :df) OR (date_traitement BETWEEN :db AND :df)');
                //->andWhere('type_pointage.description LIKE :description');
                //->andWhere('(type_pointage.description LIKE :description) AND ((date_reel_livraison BETWEEN :db AND :df) OR (date_traitement BETWEEN :db AND :df))')
                //->orWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\') AND ((date_reel_livraison BETWEEN :db AND :df) OR (date_traitement BETWEEN :db AND :df))');
                //->andWhere('((date_reel_livraison BETWEEN :db AND :df) OR (date_traitement BETWEEN :db AND :df))');

                $dD = explode(' - ', $dates)[0];
                $dF = explode(' - ', $dates)[1];

                $name_excel = "/extra" . date('YmdH:i:s') . ".xlsx";

                $intervalDateFacturable = $this->getIntervalDateInCompte($dD, $dF);

                /**
                 * maka ny debut et fin du compte teo aloha
                 * afaan maka ny extra ny cadre satria ny heuren variable
                 */
                $timestamp = strtotime($intervalDateFacturable["dateDebutCompte"]);
                $datePrecedent = date("Y-m-d", strtotime("-1 day", $timestamp));
                $comptePrecedent = $this->getIntervalDateInCompte($datePrecedent, $datePrecedent);

                /**
                 * pointage
                 */
                $pointage = new \App\Model\GPAOModels\Pointage($connex);
                $sqlPointage = $pointage->Get([
                    "personnel.nom_fonction",
                    "personnel.id_personnel",
                    "personnel.id_type_pointage",
                    "pointage.heure_entre",
                    "pointage.heure_sortie",
                    "type_pointage.description",
                    "date_debut",
                    "personnel.type_contrat"
                ])
                    ->where("((date_debut BETWEEN :deb AND :fin) OR (date_debut BETWEEN :db AND :df))");
                //->setParameter("debut", date('Y-m-d'))
                //->where('type_contrat = :type_contrat AND ((date_debut BETWEEN :deb AND :fin) OR (date_debut BETWEEN :d AND :f))');

                if (!empty($matr)) {
                    $sqlProd->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matr);

                    $sqlPointage->andWhere('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matr);
                }
                $sqlPointage->andWhere('type_pointage.description LIKE :desc')
                    ->setParameter('deb', $intervalDateFacturable["dateDebutCompte"])
                    ->setParameter('fin', $intervalDateFacturable["dateFinCompte"])
                    ->setParameter('db', $comptePrecedent["dateDebutCompte"])
                    ->setParameter('df', $comptePrecedent["dateFinCompte"])
                    ->setParameter('desc', 'Extra%')
                    ->orderBy("personnel.id_personnel", "ASC");
                //->orderBy('date_debut','ASC');

                $pointageAll = $sqlPointage->execute()->fetchAll();
                //dd($pointageAll);
                $str_matricule = "IN (";
                $unique_matr = [];
                // $tests = [];
                foreach ($pointageAll as $pointage) {
                    // if($pointage["id_personnel"] == 1307){
                    //     if(strtotime($pointage["date_debut"]) == strtotime("2022-08-24")){
                    //         $tests[] = $pointage;
                    //     }
                    // }
                    if (!in_array($pointage["id_personnel"], $unique_matr)) {
                        $str_matricule .= $pointage["id_personnel"] . ",";
                        $unique_matr[] = $pointage["id_personnel"];
                    }
                }
                $str_matricule = substr($str_matricule, 0, -1);
                $str_matricule .= ")";


                if (empty($matr)) {
                    $sqlProd->andWhere('personnel.id_personnel ' . $str_matricule);
                }


                /**
                 * raha ny facturable ian no affichena
                 */
                if ($isFacturable) {
                    $whereBegin = true;
                    $sqlProd->andWhere('dossier_client.facturable = :fact')
                        ->setParameter('fact', 1);
                }
                /**
                 * exportation excel
                 * preparation des outils utiles pour excel
                 */
                $nomFichier = "";
                if ($exportToExcel) {
                    $dirPiece = $this->getParameter('app.temp_dir');
                    $nomFichier = $dirPiece . "" . $name_excel;


                    $headers = [
                        "Matricule", "Login", "Date du traitement", "Heure début", "heure fin", "Dossier", "Fichier", "Etat", "Etape", "Volume", "Temps", "Taux", "Prix Unitaire", "Coût", "Facturable", "Date reel livraison"
                    ];

                    $writer = WriterEntityFactory::createXLSXWriter();
                    $writer->openToFile($nomFichier); // write data to a file or to a PHP stream
                    $cells = WriterEntityFactory::createRowFromArray($headers);
                    $writer->addRow($cells);
                }
                $prods = $sqlProd
                    ->setParameter('db', $intervalDateFacturable["dateDebutCompte"])
                    ->setParameter('df', $intervalDateFacturable["dateFinCompte"])
                    ->orderBy('personnel.id_personnel', 'ASC')
                    ->execute()->fetchAll();

                /**
                 * maka ny extra
                 */
                $pros = [];
                foreach ($prods as $prod) {
                    // if($prod["id_personnel"] == 1307){
                    //     if(strtotime($prod["date_traitement"]) == strtotime("2022-08-24")){
                    //         $pros[] = $prod;
                    //     }
                    // }
                    $isExtra = false;
                    foreach ($pointageAll as $pointage) {
                        if ($pointage["id_personnel"] == $prod["id_personnel"]) {
                            if (
                                strtotime($pointage["date_debut"]) == strtotime($prod["date_traitement"]) && strtotime($prod["date_reel_livraison"]) >= strtotime($intervalDateFacturable["dateDebutCompte"]) && strtotime($prod["date_reel_livraison"]) <= strtotime($intervalDateFacturable["dateFinCompte"]) ||
                                ($pointage["date_debut"] == $prod["date_traitement"] && (is_null($prod["date_reel_livraison"]) && (strtotime($prod["date_traitement"]) <= strtotime($intervalDateFacturable["dateFinCompte"]) && strtotime($prod["date_traitement"]) >= strtotime($intervalDateFacturable["dateDebutCompte"]))))
                            ) {
                                if ($prod["heure_debut"] !== null && ((strtotime($prod["heure_debut"]) >= strtotime($pointage["heure_entre"]) && strtotime($prod["heure_debut"]) <= strtotime($pointage["heure_sortie"])))) {
                                    $isExtra = true;
                                }
                            }
                        }
                    }
                    // }
                    if ($isExtra) {
                        $taux = null;
                        $prix_unitaire = $prod["prix"];
                        $prix = $prod["volume"] * $prix_unitaire;

                        /**
                         * liste des dossier dont les prix ne sont pas indiqués
                         */
                        if ($prod["prix"] == 0) {
                            if (!array_key_exists($prod["nom_dossier"], $list_dossier_dont_price)) {
                                $list_dossier_dont_price[$prod["nom_dossier"]] = [$prod["nom_etape"]];
                            } else {
                                if (!in_array($prod["nom_etape"], $list_dossier_dont_price[$prod["nom_dossier"]])) {
                                    $etapes = $list_dossier_dont_price[$prod["nom_dossier"]];
                                    $etapes = array_merge($etapes, [$prod["nom_etape"]]);
                                    $list_dossier_dont_price[$prod["nom_dossier"]] = $etapes;
                                }
                            }
                        }
                        /**
                         * liste dossier facturable sy tsy facturable
                         */

                        if ($prod["facturable"] == 1) {
                            /**
                             * facturable, dossier list
                             */
                            if (!in_array($prod["nom_dossier"], $list_dossier_facture)) {
                                $list_dossier_facture[] = $prod["nom_dossier"];
                            }

                            if ((strtotime($intervalDateFacturable["dateDebutCompte"]) <= strtotime($prod["date_reel_livraison"]))
                                && (strtotime($prod["date_reel_livraison"]) <= strtotime($intervalDateFacturable["dateFinCompte"]))
                            ) {
                                $total_facturable += $prix;
                            }
                        } else {
                            /**
                             * tsy facturable
                             */
                            if (!in_array($prod["nom_dossier"], $list_dossier_no_facture)) {
                                $list_dossier_no_facture[] = $prod["nom_dossier"];
                            }
                        }

                        if ((float)$prod["prix"] != 0 && $prod["etat"] != "Rejet") {
                            if ($prod["volume"] !== null || (float)$prod["volume"] != 0) {
                                //$prix = $prod["volume"]*$prix_unitaire;

                                //$taux = null;

                                if ((float)$prod["temps_realisation"] != 0) {
                                    $vitesse = $prod["volume"] / $prod["temps_realisation"];
                                    $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                    if ($objectif != 0) {
                                        $taux = $vitesse * 100 / $objectif;
                                    } else {
                                        $taux = 0;
                                    }
                                    //$prods[$key]["taux"] = $taux;
                                } else {
                                    $taux = 0;
                                }

                                $taux_reussite_general += $taux;
                            }
                        } else {

                            if ($prod["prix"] == 0) {
                                $list_dossier_not_comptabilise[] = $prod["nom_dossier"];
                            }
                            if ((float)$prod["temps_realisation"] != 0) {
                                $vitesse = round($prod["volume"] / $prod["temps_realisation"], 2);
                                $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                                if ($objectif != 0) {
                                    $taux = $vitesse * 100 / $objectif;
                                    //dump($vitesse);
                                } else {
                                    $taux = 0;
                                }
                                //$prods[$key]["taux"] = $taux;
                            } else {
                                $taux = 0;
                            }
                            $taux_reussite_general += $taux;
                            $prix_unitaire = 0;
                        }
                        $nb_lignes += 1;
                        $total_prix += $prix;
                        /**
                         * exportation excel
                         * recuperation des donnees
                         */
                        if ($exportToExcel) {
                            $fact = "En attente";
                            if ($prod["facturable"] == 1) {
                                $fact = "OK";
                            }
                            $cells = WriterEntityFactory::createRowFromArray([
                                $prod["id_personnel"],
                                ucwords($prod["login"]),
                                $prod["date_traitement"],
                                $prod["heure_debut"],
                                $prod["heure_fin"],
                                $prod["nom_dossier"],
                                $prod["nom_fichiers"],
                                $prod["etat"],
                                $prod["nom_etape"],
                                $prod["volume"],
                                $prod["temps_realisation"],
                                $taux,
                                $prod["prix"],
                                //$prix_unitaire,
                                //$prod["volume"]*$prod["prix"],
                                $prod["volume"] * $prix_unitaire,
                                $fact,
                                $prod["date_reel_livraison"]
                                //$prod["facturable"]
                            ]);
                            $writer->addRow($cells);
                        }
                    }
                }
                // dump($pros); 
                // dump($tests);       
            }

            if (count($list_dossier_facture) > 0 || count($list_dossier_no_facture) > 0) {

                $taux_reussite_general = round($taux_reussite_general / $nb_lignes, 2);

                /**
                 * fermeture exportation excel
                 */
                if ($exportToExcel) {
                    $writer->close();
                }
            }
        }


        return $this->render("dossier/extraGeneral.html.twig", [
            "form" => $form->createView(),
            //"prods" => $prod_final,
            //"date" => implode(' - ', $dates),
            "total_prix" => $total_prix,
            "nb_lignes" => $nb_lignes,
            "total_facturable" => $total_facturable,
            "excel" => [
                "isExportToExcel" => $exportToExcel,
                "fileName" => $name_excel
            ],
            "taux_generale" => $taux_reussite_general,
            "dossier_facture" => $list_dossier_facture,
            "dossier_no_facture" => $list_dossier_no_facture,
            "dossier_dont_prix_pas_indique" => $list_dossier_dont_price
        ]);
    }


    public function ApiCompar(Request $request, Connection $connex)
    {

        $dates = "";
        $date_debut = $request->query->get('date_debut');
        $date_fin = $request->query->get('date_fin');

        $intervalDateCompte = $this->getIntervalDateInCompte($date_debut, $date_fin);


        $dates .= implode('/', array_reverse(explode('-', $request->query->get('date_debut'))));
        $dates .= " - ";
        $dates .= implode('/', array_reverse(explode('-', $request->query->get('date_fin'))));
        $matricule = $request->query->get('id');

        $dates = explode(' - ', $dates);

        /**
         * demande extra 
         */
        $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

        $sqlDemandeExtras = $demandeSupplementaire->Get([
            //"personnel.id_personnel",
            "date_suplementaire",
            "heure_debut_supplementaire",
            "heure_fin_supplementaire",

            //"type_pointage.id_type_pointage",
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.login",
            "personnel.nom_fonction",
        ])

            ->where('demande_supplementaire.etat_validation = :etat')
            ->setParameter('etat', "Accorder");


        $demandes = $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
            ->setParameter('id_personnel', $matricule)
            ->andWhere('date_suplementaire BETWEEN :d AND :f')
            ->setParameter('d', $dates[0])
            ->setParameter('f', $dates[1])
            ->execute()->fetchAll();



        /**
         * pointage
         */
        $pointage = new \App\Model\GPAOModels\Pointage($connex);
        $sqlPointage = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description", "date_debut"])
            ->where("description = :desc")
            ->setParameter("desc", "Extra");

        $pointages = $sqlPointage->andWhere('personnel.id_personnel = :id_p')
            ->setParameter('id_p', $matricule)
            ->andWhere('date_debut BETWEEN :db and :df')
            ->setParameter('db', str_replace('/', '-', $dates[0]))
            ->setParameter('df', str_replace('/', '-', $dates[1]))
            ->orderBy('date_debut', 'ASC')
            ->orderBy("personnel.id_personnel", "ASC")
            ->execute()->fetchAll();

        $list_pointage_extra = [];
        foreach ($pointages as $pointage) {
            foreach ($demandes as $demande) {
                if ($demande["date_suplementaire"] == $pointage["date_debut"]) {
                    if (!array_key_exists($pointage["id_personnel"], $list_pointage_extra)) {
                        $list_pointage_extra[$pointage["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $pointage["heure_sortie"]];
                    } else {
                        if (!array_key_exists($pointage["date_debut"], $list_pointage_extra[$pointage["id_personnel"]])) {
                            $list_pointage_extra[$pointage["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $pointage["heure_sortie"]];
                        }
                    }
                }
            }
        }

        $prodConnex = new \App\Model\GPAOModels\Production($connex);
        /**
         * counter de productions
         *
            $total_prod = $prodConnex->Get([
                "count(id_production) AS nb_total"
            ])->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
             ->innerJoin('fichiers','dossier_client','dossier_client','fichiers.nom_dossier = dossier_client.nom_dossier')
             //->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')
             ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail')
             ->andWhere('personnel.id_personnel = :id_p')
             ->setParameter('id_p', $matricule)
             ->andWhere('date_traitement BETWEEN :d AND :f')
             ->setParameter('d', str_replace('/','-',$intervalDateCompte["dateDebutCompte"]))
             ->setParameter('f', str_replace('/','-',$intervalDateCompte["dateFinCompte"]))
             ->andWhere('dossier_acp = :dossier')
             ->setParameter('dossier','NON')
             ->andWhere('nom_etape NOT IN (\'VALIDATION_ECHANT\',\'ETAPE1\',\'ETAPE2\')')->execute()->fetchAll();
            
            
            /**
         * sql prod
         */
        $sqlProd = $prodConnex->Get([
            "personnel.id_type_pointage",
            "personnel.id_personnel",
            "personnel.login",
            "etape_travail.objectif",
            "production.volume",
            "production.temps_realisation",
            "production.date_traitement",
            "production.heure_debut",
            "production.heure_fin",
            "etape_travail.nom_etape",
            "dossier_client.dossier_acp",
            "production.etat"

        ])->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
            ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
            //->innerJoin('personnel','type_pointage','type_pointage','personnel.id_type_pointage = type_pointage.id_type_pointage')
            ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail')
            ->andWhere('personnel.id_personnel = :id_p')
            ->setParameter('id_p', $matricule)
            ->andWhere('date_traitement BETWEEN :d AND :f')
            ->setParameter('d', str_replace('/', '-', $dates[0]))
            ->setParameter('f', str_replace('/', '-', $dates[1]))
            ->andWhere('dossier_acp = :dossier')
            ->setParameter('dossier', 'NON')
            ->andWhere('nom_etape NOT IN (\'VALIDATION_ECHANT\',\'ETAPE1\',\'ETAPE2\')');

        $prods = $sqlProd->execute()->fetchAll();


        $pourcentage = [];
        $nb_rejet = 0;
        $nb_prod = 0;
        foreach ($prods as $prod) {
            /**
             * nombre rejet
             */
            if ($prod["etat"] == "Rejet") {
                $nb_rejet = $nb_rejet + 1;
            }
            /**
             * calcule de taux
             */
            $taux = 0;
            if ((float)$prod["temps_realisation"] != 0) {
                $vitesse = $prod["volume"] / $prod["temps_realisation"];
                $objectif = $prod["objectif"] == 0 ? 0 : $prod["objectif"];
                if ($objectif != 0) {
                    $taux = $vitesse * 100 / $objectif;
                } else {
                    $taux = 0;
                }
            } else {
                $taux = 0;
            }
            $dateProd = false;
            foreach ($list_pointage_extra as $matricule => $data) {
                if ($matricule == $prod["id_personnel"]) {
                    foreach ($data as $date => $heures) {
                        if ($date == $prod["date_traitement"]) {
                            $dateProd = true;
                            if (strtotime($prod["heure_debut"]) >= strtotime($heures[0]) && strtotime($prod["heure_debut"]) < strtotime($heures[1])) {

                                if (count($pourcentage) == 0) {
                                    $pourcentage = [
                                        "login" => $prod["login"],
                                        "equipe" => $prod["id_type_pointage"],
                                        "taux" => [
                                            "extra" => $taux,
                                            "nb_extra" => 1,
                                            "production" => 0,
                                            "nb_production" => 0
                                        ]
                                    ];
                                } else {
                                    $tauxs = $pourcentage["taux"]["extra"];
                                    $tauxs = $tauxs + $taux;
                                    $pourcentage["taux"]["extra"] = $tauxs;
                                    $nb_extra = $pourcentage["taux"]["nb_extra"];
                                    $nb_extra = $nb_extra + 1;
                                    $pourcentage["taux"]["nb_extra"] = $nb_extra;
                                }
                            } else {
                                if (count($pourcentage) == 0) {
                                    $pourcentage = [
                                        "login" => $prod["login"],
                                        "equipe" => $prod["id_type_pointage"],
                                        "taux" => [
                                            "extra" => 0,
                                            "nb_extra" => 0,
                                            "production" => $taux,
                                            "nb_production" => 1
                                        ]
                                    ];
                                } else {
                                    $tauxs_pro = $pourcentage["taux"]["production"];
                                    $tauxs_pro = $tauxs_pro + $taux;
                                    $pourcentage["taux"]["production"] = $tauxs_pro;
                                    $nb_prod = $pourcentage["taux"]["nb_production"];
                                    $nb_prod = $nb_prod + 1;
                                    $pourcentage["taux"]["nb_production"] = $nb_prod;
                                }
                            }
                        }
                    }
                }
            }
            if (!$dateProd) {
                /**
                 * nanw extra izy t@date interval fa MISY ANDRO IRAY TSY NANAOVAN extra dia las prod
                 */
                if (count($pourcentage) == 0) {
                    $pourcentage = [
                        "login" => $prod["login"],
                        "equipe" => $prod["id_type_pointage"],
                        "taux" => [
                            "extra" => 0,
                            "nb_extra" => 0,
                            "production" => $taux,
                            "nb_production" => 1
                        ]
                    ];
                } else {
                    $tauxs_pro = $pourcentage["taux"]["production"];
                    $tauxs_pro = $tauxs_pro + $taux;
                    $pourcentage["taux"]["production"] = $tauxs_pro;
                    $nb_prod = $pourcentage["taux"]["nb_production"];
                    $nb_prod = $nb_prod + 1;
                    $pourcentage["taux"]["nb_production"] = $nb_prod;
                }
            }
        }
        $data_api = [
            "status" => "ok",
            "data" => null
        ];
        if (count($pourcentage) > 0) {

            $data = [];
            if ($pourcentage["taux"]["nb_extra"] == 0) {
                $data["taux_prod_extra"] = 0;
            } else {
                $data["taux_prod_extra"] = round($pourcentage["taux"]["extra"] / $pourcentage["taux"]["nb_extra"], 2);
            }

            if ($pourcentage["taux"]["nb_production"] == 0) {
                $data["taux_prod_normal"] = 0;
            } else {
                $data["taux_prod_normal"] = round($pourcentage["taux"]["production"] / $pourcentage["taux"]["nb_production"], 2);
            }

            $data["taux_rejet"] = round(($nb_rejet * 100) / count($prods), 2);
            $data_api["status"] = "ok";
            $data["nb_prod"] = count($prods);
            $data["nb_rejet"] = $nb_rejet;
            $data_api["data"] = $data;
        }

        return new \Symfony\Component\HttpFoundation\JsonResponse($data_api);
    }
    /**
    public function ApiAbsence(Request $request, Connection $connex){
            $dates = "";
            $dates .= implode('/',array_reverse(explode('-',$request->query->get('date_debut'))));
            $dates .= " - ";
            $dates .= implode('/',array_reverse(explode('-',$request->query->get('date_fin'))));
            $matricule = $request->query->get('id');
            
            $dates = explode(' - ',$dates);
            
            /**
     * demande extra 
     *
            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);
            
            $sqlDemandeExtras = $demandeSupplementaire->Get([
              //"personnel.id_personnel",
                "date_suplementaire",
                "heure_debut_supplementaire",
                "heure_fin_supplementaire",
                
                //"type_pointage.id_type_pointage",
                "personnel.id_personnel",
                "personnel.nom",
                "personnel.login",
                "personnel.nom_fonction",
            ])
            
            ->where('demande_supplementaire.etat_validation = :etat')
            ->setParameter('etat', "Accorder");
            
            
            $demandes = $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
                             ->setParameter('id_personnel', $matricule)
                             ->andWhere('date_suplementaire BETWEEN :d AND :f')
                             ->setParameter('d', $dates[0])
                             ->setParameter('f', $dates[1])
                             ->execute()->fetchAll();
            
            /**
     * pointage
     *
            $pointage = new \App\Model\GPAOModels\Pointage($connex);
            $sqlPointage = $pointage->Get(["personnel.id_personnel","personnel.id_type_pointage","pointage.heure_entre","pointage.heure_sortie","type_pointage.description","date_debut"])
                                ->where("description = :desc")
                                ->setParameter("desc", "Extra");
            
            $pointages = $sqlPointage->andWhere('personnel.id_personnel = :id_p')
                                     ->setParameter('id_p', $matricule)
                                             ->andWhere('date_debut BETWEEN :db and :df')
                                             ->setParameter('db', str_replace('/','-',$dates[0]))
                                             ->setParameter('df', str_replace('/','-',$dates[1]))
                                             ->orderBy('date_debut','ASC')
                                            ->orderBy("personnel.id_personnel","ASC")
                                            ->execute()->fetchAll();
            
            /**
     * comptage de nombre de presence et absence dans une interval de date donnée
     *
            
            $nb_absence_extra = 0;
            foreach($demandes as $demande){
                $pointageFound = false;
                foreach($pointages as $pointage){
                    if($demande["date_suplementaire"] == $pointage["date_debut"]){
                        $pointageFound = true;
                    }
                }
                if(!$pointageFound){
                    $nb_absence_extra = $nb_absence_extra + 1;
                }
            }
            
            $data_api["status"] = "ok";
            $data_api["data"] = [
                                    "nb_demande_extra" => count($demandes),
                                    "nb_absence_extra" => $nb_absence_extra
                                ];
            
            return new \Symfony\Component\HttpFoundation\JsonResponse($data_api);
    }
     **/
    public function ApiAbsence(Request $request, Connection $connex)
    {
        $dates = "";
        $dates .= implode('/', array_reverse(explode('-', $request->query->get('date_debut'))));
        $dates .= " - ";
        $dates .= implode('/', array_reverse(explode('-', $request->query->get('date_fin'))));
        $matricule = $request->query->get('id');

        $dates = explode(' - ', $dates);

        /**
         * demande extra 
         */
        $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);

        $sqlDemandeExtras = $demandeSupplementaire->Get([
            //"personnel.id_personnel",
            "date_suplementaire",
            "heure_debut_supplementaire",
            "heure_fin_supplementaire",

            //"type_pointage.id_type_pointage",
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.login",
            "personnel.nom_fonction",
        ])

            ->where('demande_supplementaire.etat_validation = :etat')
            ->setParameter('etat', "Accorder");


        $demandes = $sqlDemandeExtras->andWhere('personnel.id_personnel = :id_personnel')
            ->setParameter('id_personnel', $matricule)
            ->andWhere('date_suplementaire BETWEEN :d AND :f')
            ->setParameter('d', $dates[0])
            ->setParameter('f', $dates[1])
            ->orderBy('date_suplementaire', 'ASC')
            ->execute()->fetchAll();

        /**
         * pointage
         */
        $pointage = new \App\Model\GPAOModels\Pointage($connex);
        $sqlPointage = $pointage->Get(["personnel.id_personnel", "personnel.id_type_pointage", "pointage.heure_entre", "pointage.heure_sortie", "type_pointage.description", "date_debut"])
            ->where("description = :desc")
            ->setParameter("desc", "Extra");

        $pointages = $sqlPointage->andWhere('personnel.id_personnel = :id_p')
            ->setParameter('id_p', $matricule)
            ->andWhere('date_debut BETWEEN :db and :df')
            ->setParameter('db', str_replace('/', '-', $dates[0]))
            ->setParameter('df', str_replace('/', '-', $dates[1]))
            ->orderBy('date_debut', 'ASC')
            //->orderBy("personnel.id_personnel","ASC")
            ->execute()->fetchAll();


        /**
         * comptage de nombre de presence et absence dans une interval de date donnée
         */

        $nb_absence_extra = 0;
        $list_date_absence = [];
        foreach ($demandes as $demande) {
            $pointageFound = false;
            foreach ($pointages as $pointage) {
                if ($demande["date_suplementaire"] == $pointage["date_debut"]) {
                    $pointageFound = true;
                }
            }
            if (!$pointageFound) {

                $list_date_absence[] = implode('-', array_reverse(explode('-', $demande["date_suplementaire"])));
                $nb_absence_extra = $nb_absence_extra + 1;
            }
        }

        $data_api["status"] = "ok";
        $data_api["data"] = [
            "nb_demande_extra" => count($demandes),
            "nb_absence_extra" => $nb_absence_extra,
            "date_absence" => $list_date_absence,
        ];

        return new \Symfony\Component\HttpFoundation\JsonResponse($data_api);
    }

    public function fileContent()
    {
        $api_url = 'http://192.168.8.3:9999/api/absence?id=256&date_debut=2021-06-01&&date_fin=2021-06-30';

        $json_data = file_get_contents($api_url);

        // Decode JSON data into PHP array
        $response_data = json_decode($json_data);
        dump($response_data);
        return new Response('<html><body>Ok</body></html>');
        // All user data exists in 'data' object
        $user_data = $response_data->data;
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function progressionDossier(Request $request, Connection $connexion)
    {
        $labels = [];
        $datas = [];
        $moyenne_par_etape = [];
        $data_other = []; //mise a jour
        //$data_final = [];//mise a jour
        $moyenne_par_etape_other = [];
        $moyenne_generale_dossier = 0;

        $prod = new \App\Model\GPAOModels\Production($connexion);
        /**
        $dossier = new \App\Model\GPAOModels\Dossier($connexion);
        $nom_dossiers = $dossier->Get(["nom_dossier","cloturer"])
                                ->where('cloturer = :cloturer')
                                ->setParameter('cloturer', 'oui')
                                ->orderBy('nom_dossier','ASC')
                                ->execute()
                                ->fetchAll();
         *
        $dossiers_name = [];
        
        foreach($nom_dossiers as $dossier){
            if(!in_array($dossier["nom_dossier"], $dossiers_name)){
                $dossiers_name[$dossier["nom_dossier"]] = $dossier["nom_dossier"];
            }
        }**/

        $form = $this->createFormBuilder()
            ->add("dossier", \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
                "attr" => [
                    "class" => "form-control"
                ]
            ]);
        /**
         * add event field dossier
         */
        $form->get('dossier')->addEventListener(\Symfony\Component\Form\FormEvents::POST_SUBMIT, function (\Symfony\Component\Form\FormEvent $event) use ($connexion) {
            $dossierParent = $event->getData();
            $form = $event->getForm()->getParent();

            $dossier = new \App\Model\GPAOModels\Dossier($connexion);
            $nom_dossiers = $dossier->Get(["nom_dossier"])
                ->where('nom_dossier LIKE :nom_d')
                ->setParameter('nom_d', '%' . $dossierParent . '%')
                ->orderBy('nom_dossier', 'ASC')
                ->execute()
                ->fetchAll();

            $dossiers_name = [];

            foreach ($nom_dossiers as $dossier) {
                if (!in_array($dossier["nom_dossier"], $dossiers_name)) {
                    $dossiers_name[$dossier["nom_dossier"]] = $dossier["nom_dossier"];
                }
            }

            $form->add('sub', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez le sous dossier-",
                "choices" => $dossiers_name,
                "attr" => [
                    "class" => "sousDossier"
                ],
            ]);

            //dd($form->get('sub'));

        });


        $formBuilder = $form->getForm();
        $formBuilder->handleRequest($request);
        /**
                     ->add('dossier', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                         "placeholder" => "-Selectionnez-",
                         "choices" => $dossiers_name,
                         "attr" => [
                             "class" => "form-control"
                         ]
                     ])**/


        //$form->getForm()->handleRequest($request);
        if (($formBuilder->isSubmitted() && !$request->isXmlHttpRequest()) or ($request->query->get('call') && $request->query->get('call') == "python")) {

            //$form->handleRequest($request);
            /**
             * soumission ou api
             */
            //if($form->isSubmitted() OR ($request->query->get('call') && $request->query->get('call') == "python")){
            //$dossier_choice = $form->getData()["dossier"];
            //dd($form->getData());
            $dossier_choice = $formBuilder->getData()["sub"];

            //dd($dossier_choice);
            /**
             * api 
             */
            if (!empty($request->query->get('dossier')) && $request->query->get('call') == "python") {
                $dossier_choice = $request->query->get('dossier');
            }

            $field_select = [
                "fichiers.nom_dossier",
                "etape_travail.nom_etape",
                "heure_fin",
                "date_traitement",
                "production.incident",
                "production.temps_realisation",
                "production.volume",
                "etape_travail.objectif",
                "personnel.id_personnel"
            ];

            $prods = $prod->Get($field_select)
                ->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                //->where('date_traitement = :date_t')
                //->setParameter('date_t', (new \DateTime())->format("Y-m-d"))
                //->where('heure_fin IS NOT NULL')
                //->where('fichiers.nom_dossier = :nom_d')
                ->where('fichiers.nom_dossier LIKE :nom_d')
                ->setParameter("nom_d", '%' . $dossier_choice . '%')
                ->andWhere('heure_fin IS NOT NULL')
                ->orderBy('heure_fin', 'ASC')
                ->execute()->fetchAll();
            //dd($prods);
            foreach ($prods as $dossier) {
                if ($dossier["temps_realisation"] - $dossier["incident"] > 0) {
                    if (!array_key_exists($dossier["nom_etape"], $data_other)) {
                        $data_other[$dossier["nom_etape"]]["volume"] = $dossier["volume"];
                        $data_other[$dossier["nom_etape"]]["temps_realisation"] = $dossier["temps_realisation"] - $dossier["incident"];
                        //dump("vao2",$dossier["nom_etape"], $dossier["volume"], $dossier["temps_realisation"], $dossier["incident"]);
                    } else {
                        $data_other[$dossier["nom_etape"]]["volume"] = $data_other[$dossier["nom_etape"]]["volume"] + $dossier["volume"];
                        $data_other[$dossier["nom_etape"]]["temps_realisation"] = $data_other[$dossier["nom_etape"]]["temps_realisation"] + ($dossier["temps_realisation"] - $dossier["incident"]);
                        //dump("ef",$dossier["nom_etape"], $dossier["volume"], $dossier["temps_realisation"], $dossier["incident"]);
                    }
                }
                /**
                if(!in_array($dossier["date_traitement"], $labels)){
                    $labels[] = $dossier["date_traitement"];
                }
                $taux = 0;
                $vitesse = 0;

                if($dossier["temps_realisation"]-$dossier["incident"] > 0){ 
                    $vitesse = round($dossier["volume"]/($dossier["temps_realisation"]-$dossier["incident"]), 2);
                    if($dossier["objectif"] != 0){
                        $taux = round(100*$vitesse/$dossier["objectif"],2);
                    }
                }

                
                 * mise a jour
                 * creer tableau sous forme ["nom_etape" => [
                 *                               "date"=>[2011-01-03,....],
                 *                                "taux"=>[12,...]
                 *                             ],
                 *                             "nom_etapa1" => [
                 *                                  "date"=>[2011-01-03,....],
                 *                                  "taux"=>[12,...]
                 *                             ],
                 *                              ....
                 *                          ]
                 *
                 if(!array_key_exists($dossier["nom_etape"], $data_other)){
                    $data_other[$dossier["nom_etape"]]["date"] = [$dossier["date_traitement"]];
                    $data_other[$dossier["nom_etape"]]["taux"] = [$taux];
                    $data_other[$dossier["nom_etape"]]["count"] = [1];

                 }else{
                    $k1 = array_search($dossier["date_traitement"], $data_other[$dossier["nom_etape"]]["date"]);
                    if($k1 !== false){
                        $data_other[$dossier["nom_etape"]]["taux"][$k1] = $data_other[$dossier["nom_etape"]]["taux"][$k1] + $taux;
                        $data_other[$dossier["nom_etape"]]["count"][$k1] = $data_other[$dossier["nom_etape"]]["count"][$k1] + 1;
                        //$data_other[$dossier["nom_etape"]][$dossier["id_personnel"]]["count"][] = 1;
                    }else{
                        $data_other[$dossier["nom_etape"]]["date"][] = $dossier["date_traitement"];
                        $data_other[$dossier["nom_etape"]]["taux"][] = $taux;
                        $data_other[$dossier["nom_etape"]]["count"][] = 1;
                    }   
                 }
                 // --farany---
                 * 
                 */
            }
            /**
             * a jour
             * total moyenne
             *
            foreach($data_other as $etape_t => $dt){
                foreach($data_other[$etape_t]["date"] as $k => $d){
                    $data_other[$etape_t]["taux"][$k] = ceil($data_other[$etape_t]["taux"][$k]/$data_other[$etape_t]["count"][$k]);
                    unset($data_other[$etape_t]["count"][$k]);
                }
                unset($data_other[$etape_t]["count"]);
            }

             foreach($data_other as $etape_t => $dt){
                foreach($labels as $label){
                    if(!in_array($label, $dt["date"])){
                        $data_other[$etape_t]["date"][] = $label;
                        $data_other[$etape_t]["taux"][] = 0;
                    }
                }   
            }

            /**
             * 
             * trie ordre croissant
             *
            foreach($data_other as $etape_t => $dt){
                foreach($dt["date"] as $k => $v){
                    foreach($dt["date"] as $k1 => $v1){
                        if(strtotime($data_other[$etape_t]["date"][$k]) < strtotime($data_other[$etape_t]["date"][$k1])){
                            /**
             * perm date
             *
                           $perm = $data_other[$etape_t]["date"][$k];
                           $data_other[$etape_t]["date"][$k] = $data_other[$etape_t]["date"][$k1];
                           $data_other[$etape_t]["date"][$k1] = $perm;

                           /**
             * perm taux
             *
                           $perm1 = $data_other[$etape_t]["taux"][$k];
                           $data_other[$etape_t]["taux"][$k] = $data_other[$etape_t]["taux"][$k1];
                           $data_other[$etape_t]["taux"][$k1] = $perm1;
                        }
                    }
                }
            }
            /**
             * moyenne generale par etape
             *
            foreach($data_other as $etap_t => $dt){
                $i = 0;
                $total = 0;
                foreach($dt["taux"] as $taux){
                    if($taux != 0){
                        $i++;
                        $total += $taux;
                    }
                }
                if($i != 0){
                    $moyenne_par_etape_other[$etap_t] = ceil($total/$i);
                    $moyenne_generale_dossier = $moyenne_generale_dossier + $moyenne_par_etape_other[$etap_t];//total generale dossier 
                }
            }
            /**
             * api json response
             **/
            if (($request->query->get("call") && $request->query->get('call') == "python")) {
                if (count($data_other) != 0) {
                    $moyenne_generale_dossier = ceil($moyenne_generale_dossier / count($data_other));
                }
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    "data" => $data_other,
                    "moyenne_par_etape" => $moyenne_par_etape_other,
                    "moyenne_general_dossier" => $moyenne_generale_dossier
                ]);
            }
        }


        //dump($data_other);
        //dump(ceil($moyenne_generale_dossier/count($data_other)));
        //dump(new \Symfony\Component\HttpFoundation\JsonResponse(["data" => $data_other,"moyenne_par_etape" => $moyenne_par_etape_other]));
        //dump($datas);   
        //dump($moyenne_par_etape_other);
        dump($data_other);
        return $this->render('dossier/progressionDossier.html.twig', [
            //"data_other_2" => $data_other,
            "datas" => $data_other,
            "form" => $formBuilder->createView(),
            "labels" => $labels,
            "moyenne_par_etape" => $moyenne_par_etape_other,
            "moyenne_general" => $moyenne_generale_dossier
            //"moyenne_par_etape_other" => $moyenne_par_etape_other
        ]);
    }
    /**
    public function heureManquantProductionNormal(Connection $connex, Request $request){
        
        $pointage = new \App\Model\GPAOModels\Pointage($connex);
        $retard_frequent= [];
        $retards = [];
       
        $form = $this->createFormBuilder()
                     ->add('dates', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                         //"required" => false,
                     ])->getForm();
        
        $form->handleRequest($request);
        if($form->isSubmitted()){
            $dates = $form->getData()["dates"];
            $date_begin = explode(' - ', $dates)[0];
            $date_end = explode(' - ', $dates)[1];
     
            $manquant_heure = $pointage->Get([
                                "pointage.date_debut",
                                "personnel.prenom",
                                "personnel.nom",
                                "personnel.id_personnel"
                        ])
                    ->where('date_debut BETWEEN :date_debut AND :date_fin')
                    ->andWhere('nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'CQ 1\')')
                    ->setParameter('date_debut', $date_begin)
                    ->setParameter('date_fin', $date_end)
                    ->andWhere('total < 5.50')
                    ->andWhere('(personnel.id_type_pointage = :id_pointage_1 AND pointage.heure_entre < :heure_entre) OR (personnel.id_type_pointage = :id_pointage_2 AND pointage.heure_entre > :heure_entre)')
                    ->setParameter('id_pointage_1', 1)
                    ->setParameter('id_pointage_2', 24)
                    ->setParameter('heure_entre',"12:10:00")
                    ->execute()->fetchAll();
            
            
            foreach($manquant_heure as $info){
                if(!array_key_exists($info["id_personnel"], $retards)){
                    $retards[$info["id_personnel"]] = [
                        "days" => [
                            $info["date_debut"] => date('l', strtotime($info["date_debut"]))
                        ],
                        "nom" => $info["nom"],
                        "prenom" => $info["prenom"]
                    ];
                }else{
                    $retards[$info["id_personnel"]]["days"][$info["date_debut"]] = date('l',strtotime($info["date_debut"]));
                }
            }
            
            foreach($retards as $id=>$inf){
                $i = 0;
                $jour = null;
                foreach($inf["days"] as $day){
                    foreach($inf["days"] as $d){
                        if($day == $d){
                            $i++;
                            $jour = $day;
                        }
                    }
                }
                if($i >= 2){
                   $retard_frequent[] = [
                       "id" => $id,
                       "nom" => $info["nom"],
                       "prenom" => $info["prenom"],
                       "retards_frequent" => $jour
                   ]; 
                }
            }
        }
        dump($retard);
        dump($retard_frequent);
        return $this->render('dossier/heure_manquant_prod.html.twig', [
            "form" => $form->createView(),
            "retards" => $retards,
            "retard_frequent" => $retard_frequent
        ]);
        
    }
     * 
     */

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function analyse(\Doctrine\DBAL\Connection $connex, Request $request): Response
    {
        $prod = new \App\Model\GPAOModels\Production($connex);

        $data = [];
        $form = $this->createFormBuilder()
            ->add("dates", \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required" => false,
                "constraints" => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        "message" => "Date obligatoire"
                    ])
                ],
            ])
            ->add("etapes", \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez une étape-",
                "label" => "Etape",
                "required" => false,
                "constraints" => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        "message" => "Etape obligatoire"
                    ])
                ],
                "attr" => [
                    "class" => "form-control"
                ],
                "choices" => [
                    "SAISIE2_CALE" => "SAISIE2_CALE",
                    "SAISIE2_CALE_MENTION" => "SAISIE2_CALE_MENTION"
                ]
            ])->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dataForm = $form->getData();
            $dates = $dataForm["dates"];
            $dates = explode(' - ', $dates);
            $dD = implode('-', array_reverse(explode('/', $dates[0])));
            $dF = implode('-', array_reverse(explode('/', $dates[1])));
            $etape = $dataForm["etapes"];
            $list_demandes = [];
            //dd($dD);

            /**
             * demande extra
             */
            $demande = new \App\Model\GPAOModels\DemandeSupplementaire($connex);
            $demandes = $demande->Get([
                "date_suplementaire",
                "heure_debut_supplementaire",
                "heure_fin_supplementaire",
                "personnel.id_personnel",

            ])->where("demande_supplementaire.etat_validation = :etat")
                ->setParameter("etat", "Accorder")
                ->andWhere("date_suplementaire BETWEEN :debut AND :fin")
                ->setParameter("debut", $dD)
                ->setParameter("fin", $dF)
                //->setParameter("debut", "2022-03-23")
                //->setParameter("fin", "2022-03-23")
                ->execute()->fetchAll();
            foreach ($demandes as $demande) {

                $list_demandes[$demande["id_personnel"]][$demande["date_suplementaire"]] = [
                    "heure_debut" => $demande["heure_debut_supplementaire"],
                    "heure_fin" => $demande["heure_fin_supplementaire"]
                ];
            }

            //dd($list_demandes);
            $prod = new \App\Model\GPAOModels\Production($connex);
            $prods = $prod->Get([
                "production.volume",
                "etape_travail.nom_etape",
                "personnel.id_personnel",
                "production.heure_debut",
                "production.heure_fin",
                "production.date_traitement"
            ])
                ->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                ->where("date_traitement BETWEEN :debut AND :fin")
                ->andWhere("etape_travail.nom_etape = :etape")
                ->setParameter("etape", $etape)
                ->setParameter("debut", $dD)
                ->setParameter("fin", $dF)
                ->execute()->fetchAll();
            //dump($prods);
            if (count($list_demandes) == 0) {
                $list_demandes[0]["1900-00-00"] = [
                    "heure_debut" => "00:00:00",
                    "heure_fin" => "00:00:00"
                ];
            }
            //foreach($prods as $prod){
            foreach ($list_demandes as $id_personnel => $demande) {
                foreach ($prods as $prod) {
                    if ($id_personnel == $prod["id_personnel"]) {
                        foreach ($demande as $date => $heure_extra) {
                            if ($date == $prod["date_traitement"]) {
                                if (!array_key_exists($prod["nom_etape"], $data)) {
                                    $data[$prod["nom_etape"]]["general"] = (int)$prod["volume"];
                                    if (strtotime($prod["heure_debut"]) >= strtotime($heure_extra["heure_debut"]) && strtotime($prod["heure_debut"]) <= strtotime($heure_extra["heure_fin"])) {
                                        $data[$prod["nom_etape"]]["extra"] = (int)$prod["volume"];
                                    } else {
                                        $data[$prod["nom_etape"]]["interne"] = (int)$prod["volume"];
                                    }
                                } else {
                                    $data[$prod["nom_etape"]]["general"] = $data[$prod["nom_etape"]]["general"] + (int)$prod["volume"];
                                    if (strtotime($prod["heure_debut"]) >= strtotime($heure_extra["heure_debut"]) && strtotime($prod["heure_debut"]) <= strtotime($heure_extra["heure_fin"])) {
                                        if (!array_key_exists("extra", $data[$prod["nom_etape"]])) {
                                            $data[$prod["nom_etape"]]["extra"] = (int)$prod["volume"];
                                        } else {
                                            $data[$prod["nom_etape"]]["extra"] = $data[$prod["nom_etape"]]["extra"] + (int)$prod["volume"];
                                        }
                                    } else {
                                        if (!array_key_exists("interne", $data[$prod["nom_etape"]])) {
                                            $data[$prod["nom_etape"]]["interne"] = (int)$prod["volume"];
                                        } else {
                                            $data[$prod["nom_etape"]]["interne"] = $data[$prod["nom_etape"]]["interne"] + (int)$prod["volume"];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //dump($data);
        return $this->render("dossier/analyse.html.twig", [
            "data" => $data,
            "form" => $form->createView()
        ]);
    }
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function heureObjectif(Request $request, Connection $connex)
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        $data = [];
        $nomFichier = null;
        $export = false;
        $statistiques = [];
        $equipe_choix = "";
        $categorie = "";
        $choices_equipe = [
            "Matin 6h" => 1,
            "APM 6h" => 24,
            "Extra" => "Extra",
            "Complement" => "Complement"
        ];

        $user = new Personnel($connex);
        $users = $user->Get(
            ["id_personnel", "nom", "prenom", "personnel.id_type_pointage"]
        )
            ->where('id_personnel > 0 and actif = \'Oui\'')
            ->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
            ->orderBy("id_personnel", "ASC")
            ->execute()
            ->fetchAll();
        // dump($users);

        $pers = [];
        foreach ($users as $u) {
            $eqp = "Matin 6h";
            if ($u["id_type_pointage"] == 24) {
                $eqp = "APM 6h";
            }
            $pers[$u['id_personnel'] . ' - ' . $u["nom"] . ' ' . $u["prenom"] . " (" . $eqp . ")"] = $u["id_personnel"];
        }

        $form = $this->createFormBuilder()
            ->add('matricule', ChoiceType::class, [
                "placeholder" => '-Selectionnez-',
                "choices" => $pers,
                "required" => false
            ])
            ->add('dates', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "attr" => [
                    "class" => "form-control"
                ]
            ])
            ->add('equipe', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "attr" => [
                    "class" => "form-control"
                ],
                "choices" => $choices_equipe,
                "required" => false
            ])
            ->add('export', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,

            ])
            ->add('categorie', ChoiceType::class, [
                "required" => false,
                "placeholder" => '-Selectionnez-',
                "attr" => [
                    "class" => "form-control"
                ],
                "choices" => [
                    "Equipe 1" => 1,
                    "Equipe 2" => 2
                ]
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $dates = $form->getData()["dates"];
            $dateDebut = explode(' - ', $dates)[0];
            $dateFin = explode(' - ', $dates)[1];
            $export = $form->getData()["export"];
            $equipe_selected = $form->getData()["equipe"];
            $matricule = $form->getData()["matricule"];
            $categorie = $form->getData()['categorie'];

            foreach ($choices_equipe as $equipe => $type_pointage) {
                if ($type_pointage == $equipe_selected) {
                    $equipe_choix = $equipe;
                }
            }
            $writer = null;


            $headersExcel = [
                "Matricule", "Login", "Equipe", "Heure de ref", "Heure réel", "Heure sur Objectif"
            ];

            if ($export) {
                $dirPiece = $this->getParameter('app.temp_dir');
                $nomFichier = $dirPiece . "" . uniqid() . '.xlsx';
                $writer = WriterEntityFactory::createXLSXWriter();
                $writer->openToFile($nomFichier); // write data to a file or to a PHP stream
                $cells = WriterEntityFactory::createRowFromArray($headersExcel);
                $writer->addRow($cells);
            }

            $type_pointage = $form->getData()["equipe"];
            /**
             * demande extra 
             */
            $extras = [];
            $pointage = new \App\Model\GPAOModels\Pointage($connex); //pour les complement
            $pointages = [];
            $demandeSupplementaire = new \App\Model\GPAOModels\DemandeSupplementaire($connex);
            $sqlDemandeSuppl = $demandeSupplementaire->Get([
                "demande_supplementaire.*",
                "personnel.id_personnel",
                "personnel.id_type_pointage"

            ])
                ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('demande_supplementaire.etat_validation = :etat')
                ->setParameter('etat', "Accorder")
                // ->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')')
                ->andWhere('date_suplementaire BETWEEN :debut AND :fin')
                ->setParameter('debut', $dateDebut)
                ->setParameter('fin', $dateFin);
            if ($matricule) {
                $sqlDemandeSuppl->andWhere("personnel.id_personnel = :id_personnel")
                    ->setParameter("id_personnel", $matricule);
            } else {
                $sqlDemandeSuppl->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')');
                if ($categorie) {
                    $sqlDemandeSuppl->andWhere('personnel.id_equipe_tache_operateur = :equipe')
                        ->setParameter('equipe', $categorie);
                }
            }

            /**
             * prod
             */
            $prod = new \App\Model\GPAOModels\Production($connex);

            $sqlProd = $prod->Get([
                "personnel.id_personnel",
                "personnel.id_type_pointage",
                "type_pointage.description",
                "personnel.type_contrat",
                "personnel.login",
                "production.temps_realisation",
                "etape_travail.nom_etape",
                "etape_travail.objectif",
                "personnel.id_equipe_tache_operateur",
                "production.volume",
                "production.heure_debut",
                "production.heure_fin",
                "production.incident",
                "production.date_traitement",
                "type_pointage.description",
                "fichiers.nom_dossier",

            ])->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
                ->innerJoin('fichiers', 'dossier_client', 'dossier_client', 'fichiers.nom_dossier = dossier_client.nom_dossier')
                ->innerJoin('personnel', 'type_pointage', 'type_pointage', 'personnel.id_type_pointage = type_pointage.id_type_pointage')
                ->where('personnel.id_personnel=production.id_personnel and production.id_fichiers=fichiers.id_fichiers and fichiers.id_etape_travail=etape_travail.id_etape_travail and dossier_client.nom_dossier=fichiers.nom_dossier')
                ->andWhere('production.date_traitement BETWEEN :dD AND :dF')
                ->setParameter('dD', $dateDebut)
                ->setParameter('dF', $dateFin);
            if ($type_pointage != "Complement") {

                /**
                 * napina
                 */
                if ($matricule) {
                    // $sqlDemandeSuppl->andWhere("personnel.id_personnel = :id_personnel")
                    //     ->setParameter("id_personnel", $matricule);
                    $sqlProd->andWhere("personnel.id_personnel = :id_personnel")
                        ->setParameter("id_personnel", $matricule);
                } else {
                    $sqlProd->andWhere('nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')');
                    if ($categorie) {
                        $sqlProd->andWhere('personnel.id_equipe_tache_operateur = :equipe')
                            ->setParameter('equipe', $categorie);
                    }
                }
            }





            if ($type_pointage != "Extra") {
                /**
                 * complement pointages
                 */
                $sqlPointage = $pointage->Get([
                    "personnel.id_personnel",
                    "personnel.id_type_pointage",
                    "pointage.heure_entre",
                    "pointage.heure_sortie",
                    "type_pointage.description",
                    "date_debut"
                ])->where('date_debut BETWEEN :dD AND :dF')
                    ->andWhere('type_pointage.description LIKE :desc')
                    ->setParameter('dD', $dateDebut)
                    ->setParameter('dF', $dateFin)
                    ->setParameter('desc', "Complement%");
                /**
                 * napina
                 */
                if ($matricule) {
                    $sqlPointage->andWhere("personnel.id_personnel = :id_personnel")
                        ->setParameter("id_personnel", $matricule);
                }
                if ($categorie) {
                    $sqlPointage->andWhere('personnel.id_equipe_tache_operateur = :equipe')
                        ->setParameter('equipe', $categorie);
                }
                $pointages = $sqlPointage
                    ->orderBy("personnel.id_personnel", "ASC")
                    ->execute()
                    ->fetchAll();

                /**
                 * list complement
                 */
                // $list_complement = [];
                // foreach ($pointages as $pointage) {
                //     if (!in_array($pointage["id_personnel"], $list_complement)) {
                //         $list_complement[] = $pointage["id_personnel"];
                //     }
                // }
                /**
                 * seulement complement
                 */
                if ($type_pointage == "Complement") {
                    $list_complement = [];
                    foreach ($pointages as $pointage) {
                        if (!in_array($pointage["id_personnel"], $list_complement)) {
                            $list_complement[] = $pointage["id_personnel"];
                        }
                    }

                    if (count($list_complement) > 0) {
                        $sqlProd->andWhere('personnel.id_personnel IN (' . implode(',', $list_complement) . ')');
                    }
                    //}
                } else {

                    $sqlProd->andWhere('personnel.type_contrat != :type_contrat')
                        ->setParameter('type_contrat', "EXTRA");
                    // ->andWhere('nom_etape NOT IN(\'PREPARATION\',\'DECOUPE\',\'CQ-DECOUPE\',\'VALIDATION_ECHANT\',\'SUBDIVISION\')'); //napina anty
                    // ->andWhere('personnel.id_personnel IN (' . implode(',', $list_complement) . ')'); //napina anty

                    if (!is_null($type_pointage)) {
                        $sqlProd->andWhere('personnel.id_type_pointage = :id_type_pointage')
                            ->setParameter('id_type_pointage', $type_pointage);


                        $sqlDemandeSuppl->andWhere('personnel.id_type_pointage = :id_type_pointage')
                            ->setParameter('id_type_pointage', $type_pointage);
                    }
                }
            } else {
                $sqlProd->andWhere('nom_etape NOT IN(\'PREPARATION\',\'DECOUPE\',\'CQ-DECOUPE\',\'VALIDATION_ECHANT\',\'SUBDIVISION\')');
                if (!$matricule) {
                    $sqlProd->orWhere('personnel.type_contrat = :type_contrat AND production.date_traitement BETWEEN :dD AND :dF');
                }
                $sqlProd->setParameter('type_contrat', "EXTRA")
                    ->setParameter('dD', $dateDebut)
                    ->setParameter('dF', $dateFin);
                // dump($sqlDemandeSuppl, $sqlProd);
            }

            $prods = $sqlProd->orderBy('personnel.id_personnel', 'ASC')
                ->execute()
                ->fetchAll();

            $demandesExtras = $sqlDemandeSuppl
                ->orderBy('personnel.id_personnel', 'ASC')
                ->execute()
                ->fetchAll();
            // $demandes_test = [];
            // $complement_test = [];
            // dump($demandesExtras);
            if ($type_pointage == "Complement") {

                foreach ($pointages as $pointage) {
                    $date = date_create($pointage["heure_entre"]);
                    $heure_entre = date_sub($date, date_interval_create_from_date_string("10 min"));
                    $heure_entre = date_format($heure_entre, "H:i:s");
                    // $extras[$pointage["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $pointage["heure_sortie"]];
                    $extras[$pointage["id_personnel"]][$pointage["date_debut"]] = [$heure_entre, $pointage["heure_sortie"]];
                }
            } else {
                /**
                 * type de pointage recherche formulaire renseigne
                 */
                if (!is_null($type_pointage)) {
                    //matin apm et extra
                    /**
                     * extra
                     */
                    foreach ($demandesExtras as $demande) {
                        $date = date_create($demande["heure_debut_supplementaire"]);
                        $heure_entre = date_sub($date, date_interval_create_from_date_string("10 min"));
                        $heure_entre = date_format($heure_entre, "H:i:s");
                        $extras[$demande["id_personnel"]][$demande["date_suplementaire"]] = [$heure_entre, $demande["heure_fin_supplementaire"]];
                        /**
                         * test
                         */
                        // if ($demande["id_personnel"] == 417) {
                        //     $demandes_test[] = $demande;
                        // }
                    }
                    /**
                     * si extra recherche => complement = []
                     */
                    foreach ($pointages as $pointage) {
                        $date = date_create($pointage["heure_entre"]);
                        $heure_entre = date_sub($date, date_interval_create_from_date_string("10 min"));
                        $heure_entre = date_format($heure_entre, "H:i:s");
                        // $extras[$pointage["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $pointage["heure_sortie"]];
                        $extras[$pointage["id_personnel"]][$pointage["date_debut"]] = [$heure_entre, $pointage["heure_sortie"]];

                        // if ($pointage["id_personnel"] == 1039) {
                        //     $complement_test[] = $pointage;
                        // }
                    }
                } else {
                    /**
                     * complement seulement
                     */
                    foreach ($pointages as $pointage) {
                        $date = date_create($pointage["heure_entre"]);
                        $heure_entre = date_sub($date, date_interval_create_from_date_string("10 min"));
                        $heure_entre = date_format($heure_entre, "H:i:s");
                        // $extras[$pointage["id_personnel"]][$pointage["date_debut"]] = [$pointage["heure_entre"], $pointage["heure_sortie"]];
                        $extras[$pointage["id_personnel"]][$pointage["date_debut"]] = [$heure_entre, $pointage["heure_sortie"]];

                        // if ($pointage["id_personnel"] == 1039) {
                        //     $complement_test[] = $pointage;
                        // }
                    }
                }
            }




            // $prod_test = [];
            // $total_heure = 0;
            // dump($extras, $prods);
            foreach ($prods as $prod) {



                // if ($prod["id_personnel"] == 1060 || $prod["id_personnel"] == ) {
                //     $prod_test[$prod["id_personnel"]][] = $prod;
                //     $total_heure += $prod["temps_realisation"];
                // }

                if ((int)$prod["objectif"] != 0 && !is_null($prod["objectif"])) {

                    $volume = $prod["volume"];
                    $objectif = $prod["objectif"];
                    $heure_ref = 6;
                    $heure_reel = $prod["temps_realisation"];
                    $isExtraOrComplement = false;
                    $isProdExtra = false;


                    $date_traitement = $prod["date_traitement"];
                    $nom_dossier = $prod["nom_dossier"];
                    $nom_etape = $prod["nom_etape"];
                    /**
                     * filtre des données NON extras
                     */

                    /**
                     *  on filtre les données qui sont extra ou pas qui depand du filtre de l'utilisateur
                     **/
                    foreach ($extras as $matricule => $dates) {
                        if ($matricule == $prod["id_personnel"]) {
                            foreach ($dates as $date => $heures) {
                                if ($date == $prod["date_traitement"]) {
                                    if ($prod["heure_debut"] >= $heures[0] && ($prod["heure_debut"] <= $heures[1] || is_null($heures[1]))) { //eto no mila amboarin
                                        $isProdExtra = true;
                                        // $isComplement = false;
                                        /**
                                         * complement
                                         */
                                        // if ($prod["type_contrat"] != "EXTRA") {

                                        //     $heure_ref = 4;
                                        //     // $isProdExtra = false;
                                        //     // $isComplement = true;
                                        //     // dump("heure de reference");
                                        // }

                                        // $isExtra = true;
                                        if ($equipe_selected  == 1 || $equipe_selected == 24) {
                                            //$isExtra = false;
                                            //$heure_ref = 6;
                                            $volume = null;
                                            $objectif = null;
                                            $date_traitement = null;
                                            $nom_dossier = null;
                                            $nom_etape = null;
                                            $heure_reel = null;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($equipe_selected == 1 || $equipe_selected ==  24) {
                        $heure_ref = 6;
                        // if ($prod["id_personnel"] == 417) {
                        //     $prod_test[] = $prod;
                        //     $total_heure += $heure_reel;
                        // }
                    } else {
                        if (!is_null($type_pointage)) {
                            $isExtraOrComplement = true;
                            if ($prod["type_contrat"] == "EXTRA") {
                                $isProdExtra = true;
                            }
                            if ($type_pointage == "Complement") {
                                $heure_ref = 4;
                            }

                            if (!$isProdExtra) {
                                // dd($prod);

                                $volume = null;
                                $objectif = null;
                                $date_traitement = null;
                                $nom_dossier = null;
                                $nom_etape = null;
                                $heure_reel = null;


                                // if ($prod["id_personnel"] == 749) {
                                //     $filtres[] = 
                                // }
                            }
                        } else {
                            if ($isProdExtra) {
                                // dump("makato");
                                $isExtraOrComplement = true;
                                $heure_ref = 4;
                            }
                        }
                    }




                    /*
                     * si ces conditions sont remplit donc ces donnees ne sont pas filtre (ex: filtre matin, on exclut les extras et si ces extras, on exclut les productions 
                     */
                    if (!is_null($volume) && !is_null($objectif) && !is_null($nom_dossier) && !is_null($heure_reel)) {

                        // $tab_if_type_pointage_null[$prod["id_personnel"]][$date_traitement] = $isExtraOrComplement ? ["c"] : ["p"];
                        /**
                         * statistiques
                         */
                        if (!array_key_exists($date_traitement, $statistiques)) {
                            $statistiques[$prod["date_traitement"]]["heure_objectif"] = round($volume / $objectif, 2) - $prod["incident"];
                            $statistiques[$prod["date_traitement"]]["heure_reel"] = $heure_reel;
                            $statistiques[$prod["date_traitement"]]["heure_reference"] = !$isExtraOrComplement ? $heure_ref : ($type_pointage == "Complement" || is_null($type_pointage)  ? $heure_ref : 0);
                            $statistiques[$prod["id_personnel"]][] = $date_traitement;
                            $statistiques[$prod["date_traitement"]]["test"][$prod["id_personnel"]] = $isExtraOrComplement ? ["c"] : ["p"];
                        } else {

                            $statistiques[$prod["date_traitement"]]["heure_objectif"] += round($volume / $objectif, 2) - $prod["incident"];
                            $statistiques[$prod["date_traitement"]]["heure_reel"] += $heure_reel;
                            /**
                             * si type pointage vide
                             */
                            if (is_null($type_pointage)) {
                                if (!array_key_exists($prod["id_personnel"], $statistiques[$prod["date_traitement"]]["test"])) {
                                    $statistiques[$prod["date_traitement"]]["test"][$prod["id_personnel"]] = $isExtraOrComplement ? ["c"] : ["p"];
                                    // $statistiques[$date_traitement]["heure_reference"] += $isExtraOrComplement ? 4 : 6;

                                } else {
                                    if (
                                        !in_array("c", $statistiques[$prod["date_traitement"]]["test"][$prod["id_personnel"]]) &&
                                        $isExtraOrComplement
                                    ) {
                                        $statistiques[$date_traitement]["heure_reference"] += 4;
                                        $statistiques[$date_traitement]["test"][$prod["id_personnel"]][] = "c";
                                    }
                                    if (
                                        !in_array("p", $statistiques[$prod["date_traitement"]]["test"][$prod["id_personnel"]]) &&
                                        !$isExtraOrComplement
                                    ) {
                                        $statistiques[$date_traitement]["heure_reference"] += 6;
                                        $statistiques[$date_traitement]["test"][$prod["id_personnel"]][] = "p";
                                    }
                                }
                            }
                            // $statistiques[$prod["date_traitement"]]["heure_reference"] = !$isExtraOrComplement ? $statistiques[$prod["date_traitement"]]["heure_reference"] : (is_null($type_pointage)  ?$statistiques[$prod["date_traitement"]]["heure_reference"] + $heure_ref : $statistiques[$prod["date_traitement"]]["heure_reference"]);
                            if (!array_key_exists($prod["id_personnel"], $statistiques)) {
                                $statistiques[$prod["date_traitement"]]["heure_reference"] += !$isExtraOrComplement ? $heure_ref : ($type_pointage == "Complement" || is_null($type_pointage) ? $heure_ref : 0);
                                $statistiques[$prod["id_personnel"]][] = $date_traitement;
                            } else {

                                if (!in_array($date_traitement, $statistiques[$prod["id_personnel"]])) {
                                    // dd("makato");
                                    $statistiques[$prod["date_traitement"]]["heure_reference"] += !$isExtraOrComplement ? $heure_ref : ($type_pointage == "Complement" || is_null($type_pointage) ? $heure_ref : 0);
                                    $statistiques[$prod["id_personnel"]][] = $date_traitement;
                                }
                            }
                        }


                        if (!array_key_exists($prod["id_personnel"], $data)) {

                            $data[$prod["id_personnel"]] = [
                                "matricule" => $prod["id_personnel"],
                                "login" => $prod["login"],
                                "type_contrat" => $prod["type_contrat"],
                                "description" => $prod["description"],
                                "equipe" => $prod["id_equipe_tache_operateur"],
                                "type_pointage" => $prod["id_type_pointage"],
                                "heure_ref" => !$isExtraOrComplement ? $heure_ref : ($type_pointage == "Complement" || is_null($type_pointage) ? $heure_ref : null),
                                "heure_reel" => $heure_reel,
                                "date_traitement" => [$date_traitement],
                                "tab_complement_or_prod" => [$date_traitement => $isExtraOrComplement ? ["c"] : ["p"]],
                                "heure_objectif" => 0,
                                "dossiers" => [
                                    $nom_dossier => [
                                        $nom_etape => [
                                            "volume" => $volume, //$prod["volume"],
                                            "objectif" => $objectif //$prod["objectif"]
                                        ]
                                    ]
                                ],

                            ];
                            // if ($prod["id_personnel"] == 417) {
                            //     if (!$isExtraOrComplement) {
                            //         dd($data);
                            //     }
                            // }
                        } else {
                            /**
                             * si on ne trouve pas le date parcouru, donc c'est un nouveau jour et on ajout 6 ou 4 à l'heure_ref
                             */
                            if (!in_array($date_traitement, $data[$prod["id_personnel"]]["date_traitement"])) {
                                $data[$prod["id_personnel"]]["heure_ref"] = !$isExtraOrComplement ? ($data[$prod["id_personnel"]]["heure_ref"] + $heure_ref) : ($type_pointage == "Complement" || is_null($type_pointage) ? ($data[$prod["id_personnel"]]["heure_ref"] + $heure_ref) : null);
                                $data[$prod["id_personnel"]]["date_traitement"][] = $date_traitement;
                                $data[$prod["id_personnel"]]["heure_reel"] = ($data[$prod["id_personnel"]]["heure_reel"] + $heure_reel);
                                //$data[$prod["id_personnel"]]["heure_objectif"] = $data[$prod["id_personnel"]]["heure_objectif"] + round($prod["volume"]/$prod["objectif"],2);

                            } else {
                                $data[$prod["id_personnel"]]["heure_reel"] = ($data[$prod["id_personnel"]]["heure_reel"] + $heure_reel);

                                // if (is_null($type_pointage)) {
                                //     if (!in_array("c", $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement])) {

                                //         $data[$prod["id_personnel"]]["heure_ref"] += 4; //napina anty
                                //         $data[$prod["id_personnel"]]["tab_complement_or_prod"][] = "c";

                                //         // array_unique($data[$prod["id_personnel"]]["tab_complement_or_prod"]);
                                //     }
                                //     if (!in_array("p", $data[$prod["id_personnel"]]["tab_complement_or_prod"])) {
                                //         $data[$prod["id_personnel"]]["heure_ref"] += 6; //napina anty
                                //         $data[$prod["id_personnel"]]["tab_complement_or_prod"][] = "p";
                                //     }
                                // }

                                // $data[$prod["id_personnel"]]["heure_ref"] = !$isExtraOrComplement ? ($data[$prod["id_personnel"]]["heure_ref"]) : (is_null($type_pointage) ? ($data[$prod["id_personnel"]]["heure_ref"] + $heure_ref) : $data[$prod["id_personnel"]]["heure_ref"]); //napina anty
                                //$data[$prod["id_personnel"]]["heure_objectif"] = $data[$prod["id_personnel"]]["heure_objectif"] + round($prod["volume"]/$prod["objectif"],2);
                            }
                            if (is_null($type_pointage)) {

                                if (!array_key_exists($date_traitement, $data[$prod["id_personnel"]]["tab_complement_or_prod"])) {
                                    $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement] = $isExtraOrComplement ? ["c"] : ["p"];

                                    if (
                                        !in_array("c", $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement]) &&
                                        $isExtraOrComplement
                                    ) {

                                        $data[$prod["id_personnel"]]["heure_ref"] += 4; //napina anty
                                    }
                                    if (
                                        !in_array("p", $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement]) &&
                                        !$isExtraOrComplement
                                    ) {

                                        $data[$prod["id_personnel"]]["heure_ref"] += 6; //napina anty

                                    }
                                } else {
                                    if (
                                        !in_array("c", $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement]) &&
                                        $isExtraOrComplement
                                    ) {

                                        $data[$prod["id_personnel"]]["heure_ref"] += 4; //napina anty
                                        $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement][] = "c";
                                    }
                                    if (
                                        !in_array("p", $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement]) &&
                                        !$isExtraOrComplement
                                    ) {

                                        $data[$prod["id_personnel"]]["heure_ref"] += 6; //napina anty
                                        $data[$prod["id_personnel"]]["tab_complement_or_prod"][$date_traitement][] = "p";
                                    }
                                }
                            }
                            /**
                             * recuperation de volume et objectif filtré par dossier et etape
                             */
                            if (!array_key_exists($nom_dossier, $data[$prod["id_personnel"]]["dossiers"])) {
                                $data[$prod["id_personnel"]]["dossiers"][$nom_dossier][$nom_etape] = [
                                    "volume" => $volume, //$prod["volume"],
                                    "objectif" => $objectif //$prod["objectif"]
                                ];
                            } else {
                                if (!array_key_exists($nom_etape, $data[$prod["id_personnel"]]["dossiers"][$nom_dossier])) {
                                    $data[$prod["id_personnel"]]["dossiers"][$nom_dossier][$nom_etape] = [
                                        "volume" => $volume, //$prod["volume"],
                                        "objectif" => $objectif //$prod["objectif"]
                                    ];
                                } else {
                                    $data[$prod["id_personnel"]]["dossiers"][$nom_dossier][$nom_etape]["volume"] = $data[$prod["id_personnel"]]["dossiers"][$nom_dossier][$nom_etape]["volume"] + $volume; //$prod["volume"];
                                }
                            }
                        }
                    }
                }
            }
            // dump($data);
            // dump("complement", $complement_test, "prod complement", $prod_test, "total heure", $total_heure);
            //dump($prod_test, $total_heure);

            /**
             * calcule heure objectif par dossier et puis par etapes
             */
            foreach ($data as $id_personnel => $d) {
                $heure_obj = 0;
                foreach ($d["dossiers"] as $nom_dossier => $values) {
                    foreach ($values as $nom_etape => $info) {
                        $heure_obj += round($info["volume"] / $info["objectif"], 2);
                    }
                }
                $data[$id_personnel]["heure_objectif"] = $heure_obj;
                unset($data[$id_personnel]["dossiers"]); //on enleve le cle dossier qui ne sert plus à rien
            }
            /**
             * on enleve les matricule, càd sans le tiret
             */
            foreach ($statistiques as $key => $d) {
                if (!preg_match("/-/", $key)) {
                    unset($statistiques[$key]);
                }
            }
            /**
             * export excel
             */
            if ($export) {
                // dd($data);
                foreach ($data as $d) {
                    $equipe = "EXTRA";
                    //$equipe = $d["description"];
                    if ($equipe_selected != "Extra") {
                        if ($equipe_selected != "Complement") {
                            if ($d["equipe"] == 1 && $d["type_contrat"] != "EXTRA" && $d["type_pointage"] == 1) {
                                $equipe = "Matin 6h (Equipe 1)";
                            }
                            if ($d["equipe"] == 1 && $d["type_contrat"] != "EXTRA" && $d["type_pointage"] == 24) {
                                $equipe = "APM 6h (Equipe 1)";
                            }
                            if ($d["equipe"] == 2 && $d["type_contrat"] != "EXTRA" && $d["type_pointage"] == 24) {
                                $equipe = "APM 6h (Equipe 2)";
                            }
                            if ($d["equipe"] == 2 && $d["type_contrat"] != "EXTRA" && $d["type_pointage"] == 1) {
                                $equipe = "Matin 6h (Equipe 2)";
                            }
                        } else {

                            if (preg_match('/Matin/', $d["description"]) && $d["equipe"] == 1) {
                                $equipe = "Complement APM 4H (Equipe 1)";
                            }
                            if (preg_match('/Matin/', $d["description"]) && $d["equipe"] == 2) {
                                $equipe = "Complement APM 4H (Equipe 2)";
                            }
                            if (preg_match('/Après-midi/', $d["description"]) && $d["equipe"] == 1) {
                                $equipe = "Complement Matin 4H (Equipe 1)";
                            }
                            if (preg_match('/Après-midi/', $d["description"]) && $d["equipe"] == 2) {
                                $equipe = "Complement Matin 4H (Equipe 2)";
                            }
                        }
                    }
                    $cells = WriterEntityFactory::createRowFromArray([
                        $d["matricule"],
                        ucwords($d["login"]),
                        $equipe,
                        $d["heure_ref"],
                        $d["heure_reel"],
                        //$d["nom_dossier"],
                        $d["heure_objectif"]
                    ]);
                    $writer->addRow($cells);
                }
                $writer->close();
                /**
                 * force donwload
                 */
                $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($nomFichier);
                $response->setContentDisposition(
                    \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    basename($nomFichier)
                );
                return $response;
            }
        }
        ksort($statistiques, SORT_LOCALE_STRING);
        dump($statistiques);

        return $this->render('dossier/heure_objectif.html.twig', [
            "form" => $form->createView(),
            "data" => $data,
            "statistique" => $statistiques,
            "equipe" => $equipe_choix,
            "categorie" => $categorie
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function fichierHistoriqueModif(Request $request, Connection $connex): Response
    {
        $fichiers = [];
        $search_active = false;

        $fichierHistorique = new FichiersHistoriqueModif($connex);
        $sqlHistorique = $fichierHistorique->Get([
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.prenom",
            "fichiers.nom_dossier",
            "fichiers.nom_fichiers",
            "ancien_etat",
            "nouveau_etat",
            "heure_modification",
            "date_modification",
            "nouvelle_attribution",
            "nom_etape"
        ])
            ->innerJoin('fichiers', 'etape_travail', 'etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail')
            ->where('date_modification BETWEEN :db AND :df');

        $personnel = new Personnel($connex);
        $personnels = $personnel->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where("id_personnel > 0")
            ->andWhere('actif = :actif')
            ->setParameter('actif', 'Oui')
            ->orderBy('id_personnel', 'ASC')
            ->execute()->fetchall();

        $data_id_personnel = [];
        foreach ($personnels as $personnel) {
            $data_id_personnel[$personnel["id_personnel"] . " - " . $personnel["nom"] . " " . $personnel["prenom"]] = $personnel["id_personnel"];
        }
        /**
         * form_search
         */
        $form = $this->createFormBuilder()
            ->add('date', TextType::class, [])
            ->add('dossier', TextType::class, [
                "required" => false,
            ])
            ->add('matricule', ChoiceType::class, [
                "choices" => $data_id_personnel,
                "placeholder" => '-Selectionnez-',
                "required" => false
            ])->getForm();

        $date = null;
        $dossier = null;
        $matricule = null;
        /**
         * search
         */
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $date = $form->getData()['date'];
            $dossier = $form->getData()['dossier'];
            $matricule = $form->getData()['matricule'];
            $search_active = true;

            $sqlHistorique
                ->setParameter('db', explode(' - ', $date)[0])
                ->setParameter('df', explode(' - ', $date)[1]);

            if ($dossier && $matricule) {
                $sqlHistorique
                    ->andWhere('fichiers.nom_dossier LIKE :nom_dossier')
                    ->andWhere('fichiers_historique_modif.id_personnel = :id_personnel')
                    ->setParameter('nom_dossier', '%' . $dossier . '%')
                    ->setParameter('id_personnel', $matricule);
            } else if ($matricule) {
                $sqlHistorique
                    ->andWhere('fichiers_historique_modif.id_personnel = :id_personnel')
                    ->setParameter('id_personnel', $matricule);
            } else {
                $sqlHistorique->andWhere('fichiers.nom_dossier LIKE :nom_dossier')
                    ->setParameter("nom_dossier", '%' . $dossier . '%');
            }
        }
        /**
         * search default
         */
        if (!$search_active) {
            $sqlHistorique->setParameter('db', date('Y-m-d'))
                ->setParameter('df', date('Y-m-d'));
        }

        $fichiers = $sqlHistorique->orderBy('date_modification', 'ASC')
            ->execute()
            ->fetchAll();

        $info_modif = [];
        foreach ($fichiers as $fichier) {
            if (!array_key_exists($fichier["id_personnel"], $info_modif)) {
                $info_modif[$fichier["id_personnel"]] = 1;
            } else {
                $info_modif[$fichier["id_personnel"]] += 1;
            }
        }
        arsort($info_modif);
        return $this->render('dossier/fichier_historique_modif.html.twig', [
            "form" => $form->createView(),
            "fichiers" => $fichiers,
            "info_modif" => $info_modif
        ]);
    }
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function attributionDossier(Request $request, Connection $connex)
    {
        $attributionDossier = new AttributionDossier($connex);
        /**
         * delete
         */
        if ($request->query->get('id_delete')) {
            $id_delete = $request->query->get('id_delete');
            $attributionDossier->deleteData()
                ->where('id_attribution_dossier = :id')
                ->setParameter('id', $id_delete)
                ->execute();
            $this->addFlash("danger", "La suppression de l'attribution N°" . $id_delete . " effectuée avec success");
            return $this->redirectToRoute("attribution_dossier");
        }

        $attributions = [];
        $isSearch = false;

        $pers_data = [];
        $pers = new Personnel($connex);
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where('actif = \'Oui\'')
            ->andWhere('id_personnel > 0')
            ->orderBy('id_personnel', 'ASC')
            ->execute()->fetchAll();

        foreach ($personnels as $personnel) {
            $pers_data[$personnel['id_personnel'] . ' - ' . $personnel['nom'] . ' ' . $personnel["prenom"]] = $personnel["id_personnel"];
        }

        $dossier_data = [];
        $dossier = new GPAODossier($connex);
        $dossiers = $dossier->Get(["nom_dossier"])
            ->where('cloturer = :cloturer')
            ->setParameter('cloturer', 'non')
            ->orderBy('nom_dossier', 'ASC')
            ->execute()
            ->fetchAll();

        foreach ($dossiers as $dossier) {
            $dossier_data[$dossier["nom_dossier"]] = $dossier["nom_dossier"];
        }

        /**
         * form save
         */
        $form_save = $this->createFormBuilder()
            ->add('personnel', ChoiceType::class, [
                "choices" => $pers_data,
                "placeholder" => "-Selectionnez-"
            ])
            ->add('dossier', ChoiceType::class, [
                "choices" => $dossier_data,
                "placeholder" => "-Selectionnez-"
            ])->getForm();

        $form_save->handleRequest($request);

        if ($form_save->isSubmitted()) {
            $data = $form_save->getData();
            $pers = $data["personnel"];
            $dossier = $data["dossier"];

            $attributionExist = $attributionDossier->Get()
                ->where('attribution_dossier.nom_dossier = :nom_dossier')
                ->andWhere('attribution_dossier.id_personnel = :id_personnel')
                ->setParameter('nom_dossier', $dossier)
                ->setParameter('id_personnel', $pers)
                ->execute()->fetch();
            if (!$attributionExist) {
                $attributionDossier->insertData([
                    "nom_dossier" => $dossier,
                    "id_personnel" => $pers,
                    "date_attribution" => date("Y-m-d")
                ])->execute();
            } else {
                $this->addFlash("danger", "L'attribution pour cette personne existe déjà");
            }
            return $this->redirectToRoute("attribution_dossier");
        }
        /**
         * search
         */
        $matricule_search = null;
        $dossier_search = null;
        if ($request->query->get('dossier')) {
            $isSearch = true;
            $criteres = [];

            $dossier_search = $request->query->get('dossier');
            $criteres["attribution_dossier.nom_dossier LIKE :nom_dossier"] = ["nom_dossier" => '%' . $dossier_search . '%'];

            if ($request->query->get('matricule')) {
                $matricule_search = $request->query->get('matricule');
                $criteres["attribution_dossier.id_personnel = :id_personnel"] = ["id_personnel" => $matricule_search];
            }
        }

        if ($request->query->get('matricule')) {
            $isSearch = true;
            $criteres = [];

            $matricule_search = $request->query->get('matricule');
            $criteres["attribution_dossier.id_personnel = :id_personnel"] = ["id_personnel" => $matricule_search];

            if ($request->query->get('dossier')) {
                $dossier_search = $request->query->get('dossier');
                $criteres["attribution_dossier.nom_dossier LIKE :nom_dossier"] = ["nom_dossier" => '%' . $dossier_search . '%'];
            }
        }


        $sqlAttributionDossier = $attributionDossier->Get([
            "attribution_dossier.nom_dossier",
            "personnel.nom",
            "personnel.prenom",
            "attribution_dossier.id_personnel",
            "date_attribution",
            "id_attribution_dossier"
        ]);

        if ($isSearch) {
            $whereBegin = false;
            foreach ($criteres as $critere => $values) {
                if (!$whereBegin) {
                    $sqlAttributionDossier->where($critere);
                    $whereBegin = true;
                } else {
                    $sqlAttributionDossier->andWhere($critere);
                }
                $sqlAttributionDossier->setParameter(array_key_first($values), $values[array_key_first($values)]);
            }
        } else {
            $sqlAttributionDossier->where('date_attribution = :date')
                ->setParameter('date', date('Y-m-d'));
        }

        $attributions = $sqlAttributionDossier->orderBy('date_attribution', 'DESC')
            ->execute()
            ->fetchAll();

        return $this->render("dossier/attribution.html.twig", [
            "form" => $form_save->createView(),
            "attributions" => $attributions,
            "matricule_search" => $matricule_search,
            "dossier_search" => $dossier_search,
            "personnels" => $pers_data,
            "dossiers" => $dossier_data
        ]);
    }
    /**
     * @Route("/dossier/livraison/{id}", name="app_dossier_livraison")
     */
    public function livraisionDossier(Request $request, Connection $connex, int $id = null): Response
    {
        $livraison = new LivraisonDossier($connex);
        $livraisons = null;
        $data_livraison = null;
        $search_active = false;


        /**
         * search
         */
        // dump(empty($request->query->get('nom_dossier')), empty($request->query->get('dates')));
        if ($request->query->get('nom_dossier') || $request->query->get('dates')) {

            $search_active = true;

            $queryBuilderLivraison = $livraison->Get();

            if (!empty($request->query->get('nom_dossier')) && empty($request->query->get('dates'))) {
                $queryBuilderLivraison->where('nom_dossier LIKE :nom_dossier')
                    ->setParameter('nom_dossier', '%' . strtoupper($request->query->get('nom_dossier')) . '%');
            }
            if (!empty($request->query->get('dates')) && empty($request->query->get('nom_dossier'))) {
                $dates = explode(" - ", $request->query->get('dates'));
                $queryBuilderLivraison->where('date_livraison BETWEEN :d AND :f')
                    ->setParameter('d', $dates[0])
                    ->setParameter('f', $dates[1]);
            }
            if (!empty($request->query->get('dates')) && !empty($request->query->get('nom_dossier'))) {
                $dates = explode(" - ", $request->query->get('dates'));
                $queryBuilderLivraison->where('nom_dossier LIKE :nom_dossier')
                    ->setParameter('nom_dossier', '%' . $request->query->get('nom_dossier'))
                    ->andWhere('date_livraison BETWEEN :d AND :f')
                    ->setParameter('d', $dates[0])
                    ->setParameter('f', $dates[1]);
            }

            $livraisons = $queryBuilderLivraison->orderBy('date_livraison', "DESC")
                ->execute()
                ->fetchAll();
        }
        /**
         * mise a jour livraison
         */
        if ($id) {
            $data_livraison = $livraison->Get()
                ->where('id_livraison_dossier = :id_livraison')
                ->setParameter('id_livraison', $id)
                ->execute()
                ->fetch();
        }

        $form = $this->createFormBuilder()
            ->add('nom_dossier', TextType::class, [
                "required" => true,
                "attr" => [
                    "value" => $data_livraison ? $data_livraison['nom_dossier'] : null
                ]
            ])
            ->add('date_livraison', TextType::class, [
                "required" => true,
                "attr" => [
                    "value" => $data_livraison ? $data_livraison['date_livraison'] : null
                ]
            ])
            ->add('volume', IntegerType::class, [
                "required" => true,
                "attr" => [
                    "value" => $data_livraison ? $data_livraison['volumes'] : null
                ]
            ])
            ->add('observations', TextareaType::class, [
                "required" => false,
                "data" => $data_livraison ? $data_livraison['observations'] : null
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $msg = " a été bien insérée";

            $data = $form->getData();

            $nom_dossier = strtoupper($data['nom_dossier']);
            $date_livraison = $data["date_livraison"];
            $volume = $data["volume"];
            $observations = $data["observations"];

            /**
             * mise a jour
             */
            if ($id) {
                $livraison->updateData([
                    "nom_dossier" => $nom_dossier,
                    "date_livraison" => $date_livraison,
                    "volumes" => $volume,
                    "observations" => $observations
                ], [
                    "id_livraison_dossier" => $id
                ])->execute();

                $msg = " a été bien modifiée";
            } else {
                /**
                 * eviter le doublant du nom du dossier
                 */
                $data_livraison = $livraison->Get()
                    ->where('nom_dossier = :nom')
                    ->setParameter('nom', $nom_dossier)
                    ->execute()
                    ->fetch();

                if ($data_livraison) {
                    $this->addFlash("danger", "Ce dossier nommée " . $nom_dossier . " existe déjà");
                    return $this->redirectToRoute("app_dossier_livraison");
                }
                /**
                 * insertion
                 */
                $livraison->insertData([
                    "nom_dossier" => $nom_dossier,
                    "date_livraison" => $date_livraison,
                    "volumes" => $volume,
                    "observations" => $observations
                ])->execute();
            }
            $this->addFlash("success", "Dossier " . $nom_dossier . "" . $msg);
            return $this->redirectToRoute("app_dossier_livraison");
        }
        /**
         * aucune recherche effectuée
         */
        if (!$search_active) {
            $livraisons = $livraison->Get()
                ->orderBy('date_livraison', "DESC")
                ->setMaxResults(100)
                ->execute()
                ->fetchAll();
        }
        dump($livraisons);
        return $this->render("dossier/livraison_dossier.html.twig", [
            "form" => $form->createView(),
            "livraisons" => $livraisons
        ]);
    }
}
