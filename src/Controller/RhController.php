<?php

namespace App\Controller;

use App\Model\GPAOModels\AbsencePersonnel;
use App\Model\GPAOModels\Allaitement;
use App\Model\GPAOModels\CongeMaternite;
use App\Model\GPAOModels\DemandeSupplementaire;
use App\Model\GPAOModels\EquipeTacheOperateur;
use App\Model\GPAOModels\Personnel;
use App\Model\GPAOModels\Fonction;
use App\Model\GPAOModels\Pointage;
use App\Model\GPAOModels\Production;
use Doctrine\DBAL\Driver\Connection;

use Doctrine\ORM\EntityManagerInterface;
use \Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\Request;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Cache\CacheInterface;

class RhController extends AbstractController
{

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function exceptionConnexion(Connection $cnx, Request $request)
    {

        $searchActive = false;
        $personnel = new Personnel($cnx);
        $except = new \App\Model\GPAOModels\ExceptionConnection($cnx);

        $data_exception = null;
        $critere_id = null;
        $critere_date = null;
        $historique_matricule = null;
        $historique_date = null;

        /**
         * delete
         */
        $id_delete = $request->query->get('delete');
        if (!is_null($id_delete) && is_numeric($id_delete)) {
            $except->deleteData()->where('exception_connection.id_personnel = :id')->setParameter('id', $id_delete)->execute();
            $this->addFlash('success', "Suppression effectuée");
            return $this->redirectToRoute("rh_exceptionConnexion");
        } else {
            $this->createNotFoundException("id introuvable");
        }

        /**
         * recuperation de tous les matricule de personnel qui sont absent
         */
        $personnel_get = $personnel->Get(array("id_personnel, nom, prenom"))
            ->where('actif = :a and id_personnel> :id')
            ->setParameter('a', 'Oui')
            ->setParameter('id', 0)
            ->orderBy('id_personnel', 'ASC')
            ->execute()
            ->fetchAll();

        $data_matricule = [];
        /**
         * creation de cles(matricule, nom, prenom) et valeur(matricule) pour le champ matricule
         */
        foreach ($personnel_get as $val) {
            $keys = "";
            $value = "";
            foreach ($val as $key => $matricule) {
                if ($key == "id_personnel") {
                    $value = $val["id_personnel"];
                    $keys = $matricule . ' - ';
                } else {
                    $keys .= $matricule . ' ';
                }
            }
            $data_matricule[$keys] = $value;
        }

        /**
         * save
         */
        $builder = $this->createFormBuilder();
        $form = $builder->add("Matricule", ChoiceType::class, [
            "required" => false,
            "choices" => $data_matricule,
            "constraints" => [new NotBlank(['message' => 'Matricule obligatoire'])],
            "placeholder" => "--Selectionnez--",
            "attr" => [
                "class" => "Matricule form-control"
            ]
        ])
            ->add("Motif", TextareaType::class, [
                "required" => false,
                "constraints" => [new NotBlank(['message' => 'Motif obligatoire'])],
                "attr" => [
                    "class" => "Motif form-control"
                ]
            ])
            ->add("DateException", DateType::class, [
                "required" => false,
                'attr' => [
                    "readonly" => true,
                    "class" => "DateException form-control"
                ],
                "constraints" => [new NotBlank(['message' => 'Date absence obligatoire'])],
                "widget" => "single_text",
                "html5" => false,
                "format" => "dd/MM/yyyy"
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $matricule = explode("-", $data["Matricule"])[0];
            $motif = $data["Motif"];
            $date = $data["DateException"];

            $list_exception = [
                "id_personnel" => $matricule,
                "motif" => $motif,
                "date_exception" => $date->format("Y-m-d")
            ];
            $except->insertData($list_exception)->execute();
            $this->addFlash("success", "Enregistrement effectué");

            return $this->redirectToRoute("rh_exceptionConnexion");
        }

        $sql = $except->Get();
        /**
         * search
         */
        if ($request->request->get('keyword') != "" and $request->request->get('date') != "") {

            $historique_date = $request->request->get('date');
            $historique_matricule = $request->request->get('keyword');

            $critere_date = explode('-', $request->request->get('date'));
            $critere_id = $request->request->get("keyword");
            if (!is_null($critere_id) && is_numeric($critere_id)) {
                $sql->where('exception_connection.id_personnel = :id and exception_connection.date_exception BETWEEN :debut and :fin')
                    ->setParameter('id', $critere_id)
                    ->setParameter('debut', $critere_date[0])
                    ->setParameter('fin', $critere_date[1]);
            }
        } else {

            if ($request->request->get('keyword') != "") {
                $historique_matricule = $request->request->get('keyword');
                $critere_id = $request->request->get("keyword");
                if (!is_null($critere_id) && is_numeric($critere_id)) {
                    $sql->where('exception_connection.id_personnel = :id')
                        ->setParameter('id', $critere_id);
                }
            } else if ($request->request->get('date') != "") {
                $historique_date = $request->request->get('date');
                $critere_date = explode('-', $request->request->get('date'));
                $sql->where('exception_connection.date_exception BETWEEN :date and :fin')
                    ->setParameter('date', $critere_date[0])
                    ->setParameter('fin', $critere_date[1]);
            } else {
                $historique_matricule = null;
                $historique_date = null;
                $sql->where('date_exception = :date')
                    ->setParameter('date', date('Y-m-d'));
            }
        }

        $data_exception = $sql->execute()->fetchAll();

        /**
        if($searchActive and count($data_exception) == 0){
            $this->addFlash("success", "Aucune resultat trouvé");
        }else if($searchActive and count($data_exception) > 0){
            
            $this->addFlash("success", count($data_exception)." résultat ont été trouvé");
        }
        $searchActive = false;
         * 
         */
        return $this->render('rh/exceptionConnexion.html.twig', [
            "form" => $form->createView(),
            "list_exception" => $data_exception,
            "value_search" => $historique_matricule,
            "value_date" => $historique_date
        ]);
    }


    /**
     * @Security ("is_granted('ROLE_ARH')")
     *
     * @param Request $request
     * @param Connection $cnx
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexStatistique(Request $request, Connection $cnx)
    {

        //$personnel = new Personnel($cnx);
        $fonction = new Fonction($cnx);

        $liste_fonction = $fonction->Get()->orderBy("nom_fonction", "ASC")->execute()->fetchAll();


        return $this->render('rh/index-statistique.html.twig', [
            "liste_fonction" => $liste_fonction
        ]);
    }



    public function getStatsVal(Request $request, Connection $cnx)
    {
        $resultat = ["status" => "OK"];

        $fonction = $request->request->get('fonction');
        $dates = $request->request->get('date');

        //si il y a des dates entrees
        if (!is_null($dates)) {
            $strDate = $dates;
            $dates = explode(" - ", $dates);


            $dateisValid = TRUE;
            try {
                $dt = new \DateTime(date($dates[0]));
            } catch (\Exception $e) {
                $dateisValid = FALSE;
            }
            try {
                $dt = new \DateTime(date($dates[1]));
            } catch (\Exception $e) {
                $dateisValid = FALSE;
            }

            //si les dates sont valides
            if ($dateisValid === TRUE) {
                $pointage = new Pointage($cnx);

                $objData = $pointage->Get()->where('date_debut BETWEEN :debut AND :fin')
                    ->setParameter('debut', $dates[0])->setParameter('fin', $dates[1]);

                $strSQLF = [];
                $vals = [];
                /**
                 * raha mis ilay fonction dia ren ihany no alaina ny pointageny
                 */
                if (!is_null($fonction)) {
                    foreach ($fonction as $i => $f) {
                        $strSQLF['f' . $i] = "nom_fonction = :f" . $i;
                        $vals[] = $f;
                    }

                    if (count($strSQLF) > 0) {
                        $objData->andWhere(implode(" OR ", array_values($strSQLF)));
                        foreach (array_keys($strSQLF) as $i => $sf) {
                            $objData->setParameter($sf, $vals[$i]);
                        }
                    }
                }


                $data = $objData->orderBy('date_debut', 'ASC')->execute()->fetchAll();

                $Values = [];
                $DataStats = [];

                $idx = -1;
                foreach ($data as $d) {
                    if (!array_key_exists($d['date_debut'], $DataStats)) {
                        $DataStats[$d['date_debut']] = [];
                    }

                    if (!in_array($d['id_personnel'], $DataStats[$d['date_debut']])) {
                        $DataStats[$d['date_debut']][] = $d['id_personnel'];
                    }
                }
                foreach (array_values($DataStats) as $v) {
                    $Values[] = count($v);
                }

                $resultat["stats"] = [
                    'type' => 'line',
                    'data' => [
                        'labels' => array_keys($DataStats),
                        'datasets' => [
                            [
                                "label" => "Effectif du " . $strDate,
                                "fill" => "false",
                                "borderColor" => 'rgb(0,162,232)',
                                "backgroundColor" => 'rgb(0,162,232)',
                                "data" => array_values($Values)
                            ]
                        ]
                    ]
                ];
            }
        }

        return new JsonResponse($resultat);
    }

    public function statistiqueAbsence(Connection $cnx, Request $request, EntityManagerInterface $manager)
    {

        if ($request->isXmlHttpRequest()) {
            $absence = new AbsencePersonnel($cnx);
            $sql = $absence->Get([
                "DISTINCT date_debut_absence",
                "COUNT(absence_personnel.id_absence_personnel) AS nb",
            ]);
            /**
             * search
             */
            $date_interval = null;
            if ($request->query->get('date') != "") {
                $dates = $request->query->get('date');
                $dates = explode(' - ', $dates);

                if (count($dates) > 0) {
                    //$dateisValid = TRUE;
                    $dt = implode('-', array_reverse(explode('/', $dates[0])));
                    if (count($dates) == 2) {
                        $dt1 = implode('-', array_reverse(explode('/', $dates[1])));
                    }
                    try {
                        $dt = (new \DateTime(date($dt)))->format("d/m/Y");
                        if (count($dates) == 2) {
                            $dt1 = (new \DateTime(date($dt1)))->format("d/m/Y");
                        } else {
                            $dt1 = $dt;
                        }
                        //dump($dt, $dt1);die();
                        $date_interval[] = $dt;
                        $date_interval[] = $dt1;
                    } catch (\Exception $e) {
                        return new JSONResponse([
                            "x" => 'error',
                            "y" => "date invalide"
                        ]);
                    }
                }
            }
            if ($date_interval != null or $date_interval != "") {
                $historique_date = implode('-', $date_interval);
            }
            $historique_piece = $request->query->get('piece');
            $historique_deduire = $request->query->get('deduire');
            $historique_matricule = $request->query->get('keyword');
            $historique_insertion_rh = $request->query->get('insertion_rh');
            $historique_validation_cp = $request->query->get('validation_cp');

            $critere_recherche = [];
            $tab_boolean = [
                "date_debut_absence" => $date_interval,
                "absence_personnel.id_personnel" => $historique_matricule,
                "avec_piece" => $historique_piece,
                "insertion_rh" => $historique_insertion_rh,
                "a_deduire_sur_salaire" => $historique_deduire,
                "validation_cp" => $historique_validation_cp
            ];

            foreach ($tab_boolean as $field => $data) {
                if ($data != null or $data != "") {
                    $critere_recherche[$field] = $data;
                }
            }
            //dump($critere_recherche);die();
            $nb_null = 0;
            $search_begin = false;
            $search_ok = true;
            foreach ($critere_recherche as $f => $d) {
                if ($d != null) {
                    $nb_null = $nb_null + 1;
                }
            }
            if ($nb_null > 0) {
                $search_ok = true;
            }

            if ($search_ok) {
                foreach ($critere_recherche as $field_table => $data) {
                    if (!$search_begin) {
                        $search_begin = true;
                        if (($field_table == "date_debut_absence") || ($field_table == "absence_personnel.id_personnel")
                            /**|| $field_table == "validation_cp"**/
                        ) {

                            if ($field_table == "date_debut_absence") {
                                $date = null;
                                $date = $data;
                                $sql->where($field_table . " BETWEEN :debut AND :fin")
                                    ->setParameter('debut', $date[0])
                                    ->setParameter('fin', $date[1]);
                            } else {
                                $sql->where($field_table . " = :personnel")
                                    ->setParameter("personnel", $data);
                            }
                        } else {
                            $d = "OUI";
                            $sql->where($field_table . ' = :' . $field_table)
                                ->setParameter($field_table, $d);
                        }
                    } else {
                        if ($field_table == "absence_personnel.id_personnel") {
                            $sql->andWhere($field_table . " = :personnel")
                                ->setParameter("personnel", $data);
                        } else {
                            $d = "OUI";
                            $sql->andWhere($field_table . ' = :' . $field_table)
                                ->setParameter($field_table, $d);
                        }
                    }
                }
            }

            $result = $sql->groupBy("date_debut_absence")->orderBy("date_debut_absence", "DESC")->execute()->fetchAll();
            $x = [];
            $y = [];
            foreach ($result as $tab_res) {
                $x[] = date('d-m-Y', strtotime($tab_res["date_debut_absence"]));
                $y[] = $tab_res["nb"];
            }
            return new JsonResponse([
                "x" => $x,
                "y" => $y
            ]);
        }
        return new \Symfony\Component\HttpFoundation\Response("KO");
    }
    /**
     *
     * @Security("is_granted('ROLE_ARH')")
     */
    public function regularisationAbs(PaginatorInterface $paginator, $defaultParam = 0, Request $request, Connection $cnx, EntityManagerInterface $manager)
    {

        /**
         * data form edit or save
         */
        $motif = null;
        $date_absence = date('d-m-Y');
        $date_valide = null;
        $piece = null;
        $deduire = null;
        $edit_active = false;
        $data_exception = null;
        $data_update = [];

        $historique_matricule = null;
        $historique_date = null;
        $historique_piece = null;
        $historique_deduire = null;
        $historique_insertion_rh = null;
        $historique_validation_cp = null;

        $personnel = new Personnel($cnx);
        $absence = new AbsencePersonnel($cnx);

        $data_matricule = [];

        /**
         * recuperation de tous les matricule de personnel qui sont absent
         */
        $personnel_get = $personnel->Get(array("id_personnel, nom, prenom"))
            ->where('actif = :a and id_personnel> :id')
            ->setParameter('a', 'Oui')
            ->setParameter('id', 0)
            ->orderBy('id_personnel', 'ASC')
            ->execute()
            ->fetchAll();
        /*
         * creation de cles(matricule, nom, prenom) et valeur(matricule) pour le champ matricule
         */
        foreach ($personnel_get as $val) {
            $keys = "";
            $value = "";
            foreach ($val as $key => $matricule) {
                if ($key == "id_personnel") {
                    $value = $val["id_personnel"];
                    $keys = $matricule . ' - ';
                } else {
                    $keys .= $matricule . ' ';
                }
            }
            $data_matricule[$keys] = $value;
        }

        /**
         * search
         */
        $date_interval = null;
        if ($request->query->get('date') != "") {
            $dates = $request->query->get('date');
            $dates = explode(' - ', $dates);

            if (count($dates) > 0) {
                //$dateisValid = TRUE;
                $dt = implode('-', array_reverse(explode('/', $dates[0])));
                if (count($dates) == 2) {
                    $dt1 = implode('-', array_reverse(explode('/', $dates[1])));
                }
                try {
                    $dt = (new \DateTime(date($dt)))->format("d/m/Y");
                    if (count($dates) == 2) {
                        $dt1 = (new \DateTime(date($dt1)))->format("d/m/Y");
                    } else {
                        $dt1 = $dt;
                    }
                    $date_interval[] = $dt;
                    $date_interval[] = $dt1;
                } catch (\Exception $e) {
                    //$dateisValid = FALSE;
                    $this->addFlash("danger", "Date invalide.");
                    return $this->redirectToRoute('regularisation_absence');
                }
            }
        }

        if ($date_interval != null) {
            $historique_date = implode(' - ', $date_interval);
        }
        $historique_piece = $request->query->get('piece');
        $historique_deduire = $request->query->get('deduire');
        $historique_matricule = $request->query->get('keyword');
        $historique_insertion_rh = $request->query->get('insertion_rh');
        $historique_validation_cp = $request->query->get('validation_cp');


        $critere_recherche = [];
        $tab_boolean = [
            "date_debut_absence" => $date_interval,
            "absence_personnel.id_personnel" => $historique_matricule,
            "avec_piece" => $historique_piece,
            "insertion_rh" => $historique_insertion_rh,
            "a_deduire_sur_salaire" => $historique_deduire,
            "validation_cp" => $historique_validation_cp
        ];

        foreach ($tab_boolean as $field => $data) {
            if ($data != null) {
                $critere_recherche[$field] = $data;
            }
        }



        $search_ok = false;
        $search_begin = false;
        $sql = $absence->Get([
            "absence_personnel.*",
            "personnel.nom", "personnel.prenom", "personnel.nom_fonction", "personnel.id_personnel"
        ]);

        /**
         * afatarana oe manw recherche le ul
         * raha null dul le champ de le resultat par defaut no aseon
         */
        $nb_null = 0;

        foreach ($critere_recherche as $f => $d) {
            if ($d != null) {
                $nb_null = $nb_null + 1;
            }
        }
        if ($nb_null > 0) {
            $search_ok = true;
        }
        if ($search_ok) {
            foreach ($critere_recherche as $field_table => $data) {
                if (!$search_begin) {
                    $search_begin = true;
                    if (($field_table == "date_debut_absence") || ($field_table == "absence_personnel.id_personnel")
                        /**|| $field_table == "validation_cp"**/
                    ) {

                        if ($field_table == "date_debut_absence") {
                            $date = null;
                            $date = $data;
                            $sql->where($field_table . " BETWEEN :debut AND :fin")
                                ->setParameter('debut', $date[0])
                                ->setParameter('fin', $date[1]);
                        } else {
                            $sql->where($field_table . " = :personnel")
                                ->setParameter("personnel", $data);
                        }
                    } else {
                        $d = "OUI";
                        $sql->where($field_table . ' = :' . $field_table)
                            ->setParameter($field_table, $d);
                    }
                } else {
                    if ($field_table == "absence_personnel.id_personnel") {
                        $sql->andWhere($field_table . " = :personnel")
                            ->setParameter("personnel", $data);
                    } else {
                        $d = "OUI";
                        $sql->andWhere($field_table . ' = :' . $field_table)
                            ->setParameter($field_table, $d);
                    }
                }
            }
        }
        if ($search_ok) {
            //$sql ->orderBy("id_personnel", "ASC");
            $sql->orderBy("date_debut_absence", "DESC");
        } else {
            $sql //->where('date_debut_absence = :debut')
                //->setParameter('debut', date('Y-m-d'))
                //->orderBy("id_personnel", "ASC");
                ->orderBy("date_debut_absence", "DESC");
        }
        /**
         * delete
         */

        $id_update = null;
        if ($defaultParam == 2) {
            $id_delete = $request->query->get('id');
            if ($id_delete) {

                $this->addFlash("success", "Suppression effectué pour la matricule:" . $absence->Get(["personnel.id_personnel"])->where('absence_personnel.id_absence_personnel = :id')->setParameter('id', $id_delete)->execute()->fetch()["id_personnel"] . ".");
                $absence->deleteData()->where('absence_personnel.id_absence_personnel = :id')->setParameter('id', $id_delete)->execute();

                return $this->redirectToRoute('regularisation_absence');
            }
        }
        /**
         * edit
         */
        else if ($defaultParam == 1) {
            $edit_active = true;
            $id_update = (int)$request->query->get('id');

            $user_edit = $absence->Get([
                "absence_personnel.*",
                "personnel.nom", "personnel.prenom", "personnel.nom_fonction"
            ])
                ->where('absence_personnel.id_absence_personnel = :id')
                ->setParameter('id', $id_update)
                ->execute()->fetchAll();

            foreach ($data_matricule as $infos => $id_user) {
                if ($id_user == $user_edit[0]["id_personnel"]) {
                    $data_update[$infos] = $id_user;
                }
            }

            $motif = $user_edit[0]["motif"];
            $date_absence = date('d-m-Y', strtotime($user_edit[0]["date_debut_absence"])) . ' - ' . date('d-m-Y', strtotime($user_edit[0]["date_fin_absence"]));

            $piece = $user_edit[0]["avec_piece"];
            $deduire = $user_edit[0]["a_deduire_sur_salaire"];
        }
        /**
         * save and edit
         */
        $builder = $this->createFormBuilder();
        $form = $builder->add("Matricule", ChoiceType::class, [
            "data" => implode('', $data_update),
            "required" => false,
            "choices" => $data_matricule,
            "constraints" => [new NotBlank(['message' => 'Matricule obligatoire'])],
            "placeholder" => "--Selectionnez--",
        ])
            ->add("Motif", TextareaType::class, [
                "data" => $motif,
                "required" => false,
                "constraints" => [new NotBlank(['message' => 'Motif obligatoire'])],
            ])
            ->add("DateAbsence", TextType::class, [
                "required" => false,
                'attr' => [
                    "readonly" => true,
                    "value" => $date_absence
                ],
                "constraints" => [new NotBlank(['message' => 'Date absence obligatoire'])],
            ])
            ->add("piece", ChoiceType::class, [
                "data" => $piece,
                "required" => false,
                "choices" => [
                    "OUI" => "OUI",
                    "NON" => "NON"
                ],
                "constraints" => [new NotBlank(['message' => 'Pièce obligatoire'])],
                "placeholder" => "--Selectionnez--"
            ])
            ->add("deduire", ChoiceType::class, [
                "data" => $deduire,
                'label' => false,
                "required" => false,
                "choices" => [
                    "OUI" => "OUI",
                    "NON" => "NON"
                ],
                "constraints" => [new NotBlank(['message' => 'a deduire sur salaire obligatoire'])],
                "placeholder" => "--Selectionnez--"
            ])

            ->add("insertionRh", HiddenType::class, [
                "attr" => ["value" => "OUI"]
            ])

            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() and $form->isValid()) {
            $search_begin = false;
            $data = $form->getData();
            $datas = [];
            $datas["id_personnel"] = $data["Matricule"];
            $datas["motif"] = $data["Motif"];
            $intervalDate = $data["DateAbsence"];
            $intervalDate = explode(" - ", $intervalDate);

            $datas["date_debut_absence"] = $intervalDate[0];
            $datas["date_fin_absence"] = $intervalDate[1];
            $datas["avec_piece"] = $data["piece"];
            $datas["a_deduire_sur_salaire"] = $data["deduire"];
            $datas["insertion_rh"] = $data["insertionRh"];
            $datas["validation_cp"] = "OUI";
            /**
             * edit
             */
            if ($edit_active) {

                $absence->updateData($datas)
                    ->where('absence_personnel.id_absence_personnel = :id')
                    ->setParameter('id', $id_update)
                    ->execute();
                $this->addFlash("success", "Modification effectuée pour la matricule: " . $data["Matricule"]);
                return $this->redirectToRoute('regularisation_absence');
            } else {
                $absence->insertData($datas)->execute();
                //$this->addFlash("success","Information enregistrée");
                $this->addFlash("success", "Insertion effectué pour la matricule:" . $data["Matricule"] . ". 
                Daté du " . $datas["date_debut_absence"] . " au " . $datas["date_fin_absence"]);
                $search_ok = false;
                return $this->redirectToRoute('regularisation_absence');
            }
        }


        $list_absence = $paginator->paginate(
            $sql,
            $request->query->getInt('page', 1),
            50
        );

        $chaine_flash = "";

        if ($search_begin) {
            //$nbResult = count($list_absence);
            $nbResult = $list_absence->getTotalItemCount();
            //$nbResult = count($nombre_resultat);
            if ($nbResult == 0) {
                $chaine_flash = "Aucune résultat trouvé";
            } else {
                if ($nbResult == 1) {
                    $chaine_flash = $nbResult . " résultat a été trouvé";
                } else {
                    $chaine_flash = $nbResult . " résultat ont été trouvé";
                }
            }
            $this->addFlash("success", $chaine_flash);
        }

        return $this->render('rh/regularisationAbs.html.twig', [
            'form' => $form->createView(),
            'list_absence' => $list_absence,
            'information' => $chaine_flash,
            "search_date" => $historique_date,
            "search_matricule" => $historique_matricule,
            "search_piece" => $historique_piece,
            "search_deduire" => $historique_deduire,
            "search_rh" => $historique_insertion_rh,
            "search_cp" => $historique_validation_cp
        ]);
    }
    /**
     *
     * @Security("is_granted('ROLE_ARH')")
     */
    public function dashbordStatistique(Connection $cnx, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $personnel = new Personnel($cnx);

            $actif_user = $request->query->get('actif');

            /**
             * champ a selectionné
             */
            $field_select = ["nom_fonction", "date_embauche", "sexe", "situation_familiale"];
            $data = [];
            foreach ($field_select as $field) {
                $c = "DISTINCT " . $field . "";
                if ($field == "date_embauche") {
                    $c = "DISTINCT(extract (year from " . $field . "))";
                }
                $sql = $personnel->Get([
                    $c,
                    "COUNT(id_personnel) AS nb"
                ]);
                if ($actif_user == "Oui" || $actif_user == "Non") {
                    $sql->where('actif = :actif')
                        ->setParameter('actif', $actif_user);
                }
                $sql->andWhere('id_personnel > :id')
                    ->setParameter('id', 0);
                if ($field == "date_embauche") {
                    $sql->groupBy("date_part")
                        ->orderBy('date_part', 'DESC');
                } else {
                    $sql->groupBy($field);
                }
                $personnels = $sql->execute()
                    ->fetchAll();

                $x = [];
                $y = [];

                foreach ($personnels as $tab_res) {
                    if ($field == "date_embauche") {
                        $x[] = $tab_res["date_part"];
                    } else {
                        if ($tab_res[$field] == null) {
                            $tab_res[$field] = "INDETERMINE";
                        }
                        $x[] = $tab_res[$field];
                    }
                    $y[] = $tab_res["nb"];
                }
                $data[$field]["x"] = $x;
                $data[$field]["y"] = $y;
            }

            return new JsonResponse([
                $data
            ]);
        }
        return $this->render('rh/statistiqueGenerale.html.twig');
    }

    public function absenceImprevu(PaginatorInterface $paginator, Request $request, EntityManagerInterface $manager, Connection $cnx)
    {
        $personnel = new Personnel($cnx);
        $pointage = new Pointage($cnx);
        $absence = new AbsencePersonnel($cnx);
        $type_pointage = "1";
        $date_debut = null;
        $date_intermediaire = null;
        $date_fin = null;
        $user_absents_in_date = [];
        $list_absence = [];
        $list_id_personnel = [];
        $all_absence = [];
        $list_user_not_valide = [];
        $list_personnel_retard_per_days = [];
        $list_personnel_retard_per_days_final = [];

        $fonctions = [];
        $matricule = [];
        $absence_frequent_par_semaines = [];

        $data_fonctions = $personnel->Get([
            "personnel.nom_fonction",
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.prenom"
        ])
            ->where('personnel.actif = :a AND personnel.id_personnel > 0')
            ->andWhere('personnel.nom_fonction NOT LIKE \'%Comptable%\' AND personnel.nom_fonction NOT LIKE \'%Logistique%\' AND personnel.nom_fonction NOT LIKE \'%Securite%\' ')
            ->setParameter('a', 'Oui')
            ->orderBy("id_personnel", "ASC")
            ->execute()->fetchAll();

        foreach ($data_fonctions as $fonction) {
            /**
             * matricule
             */
            if (!in_array($fonction["id_personnel"] . " - " . $fonction["prenom"] . " " . $fonction["nom"], $matricule)) {
                $matricule[$fonction["id_personnel"] . " - " . $fonction["prenom"] . " " . $fonction["nom"]] = $fonction["id_personnel"];
            }

            if (!in_array($fonction["nom_fonction"], $fonctions)) {
                $fonctions[$fonction["nom_fonction"]] = $fonction["nom_fonction"];
            }
        }

        $form = $this->createFormBuilder()
            ->add('date', TextType::class, [
                "required" => false,
            ])
            ->add('fonction', ChoiceType::class, [
                "required" => false,
                "multiple" => true,
                "placeholder" => '-Selectionnez-',
                "choices" => $fonctions
            ])
            ->add('matricule', ChoiceType::class, [
                "required" => false,
                "placeholder" => "-Selectionnez-",
                "choices" => $matricule
            ])
            ->add('equipe', ChoiceType::class, [
                "placeholder" => "--Selectionnez--",
                "required" => false,
                'choices' => [
                    'Equipe Matin' => 1,
                    'Equipe APM' => 24
                ]
            ])
            ->add('signe', ChoiceType::class, [
                "placeholder" => "--Selectionnez--",
                "required" => false,
                "choices" => [
                    ">=" => " >= ",
                    "<=" => " <= "
                ]
            ])
            ->add('nombre', TextType::class, [
                "required" => false,

            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();

            $dates = $data["date"];
            $fonction = $data["fonction"];
            $matricule = $data["matricule"];
            $type_pointage = $data["equipe"];
            $signe = $data["signe"];
            $nombre = $data["nombre"];

            if ((is_null($signe) && !is_null($nombre)) && (!is_null($signe) && is_null($nombre))) {

                $this->addFlash('danger', "Veuillez renseigner le signe ou nombre");
                return $this->redirectToRoute("absence_imprevu");
            }

            $date = explode(' - ', $dates);
            $date_f = $date[0];

            $list_date = [];
            $list_date_copie = [];

            /**
             * creation des date entre 2 interval
             */
            $debut_jour = explode("-", $date[0])[0];
            $debut_mois = explode("-", $date[0])[1];
            $debut_annee = explode("-", $date[0])[2];

            $fin_jour = explode("-", $date[1])[0];
            $fin_mois = explode("-", $date[1])[1];
            $fin_annee = explode("-", $date[1])[2];

            $debut_date = mktime(0, 0, 0, $debut_mois, $debut_jour, $debut_annee);
            $fin_date = mktime(0, 0, 0, $fin_mois, $fin_jour, $fin_annee);
            $count_days = [];

            for ($i = $debut_date; $i <= $fin_date; $i += 86400) {
                $list_date[] = date('Y-m-d', $i);
                if (!array_key_exists(date('D', strtotime(date('Y-m-d', $i))), $count_days)) {
                    $count_days[date('D', strtotime(date('Y-m-d', $i)))] = 1;
                } else {
                    $nb_count = $count_days[date('D', strtotime(date('Y-m-d', $i)))];
                    $count_days[date('D', strtotime(date('Y-m-d', $i)))] = $nb_count + 1;
                }
            }
            $list_date_copie = $list_date;

            /**
             * commentaire
             *
            switch(date('w')){
                /**
             * si lundi, date de jeudi, vendredi et samedi
             *
                case "1":                
                    $date_debut = date('Y-m-d', strtotime(" -4 days"));
                    $date_intermediaire = date('Y-m-d', strtotime("-3 days"));
                    $date_fin = date('Y-m-d', strtotime(" -2 days"));
                    break;
                /**
             * si mardi, date de vendredi et samedi et lundi
             *
                case "2":
                    $date_debut = date('Y-m-d', strtotime("-4 days"));
                    $date_intermediaire = date('Y-m-d', strtotime("-3 days"));
                    $date_fin = date('Y-m-d', strtotime('-1 days'));
                    break;
                /**
             * si mercredi, date du samedi, lundi et mardi
             *
                case "3":
                    $date_debut = date('Y-m-d', strtotime("-4 days"));
                    $date_intermediaire = date('Y-m-d', strtotime("-2 days"));
                    $date_fin = date('Y-m-d', strtotime("-1 days"));
                    break;
                default:
                    $date_debut = date('Y-m-d', strtotime(" -3 days"));
                    $date_intermediaire = date('Y-m-d', strtotime("-2 days"));
                    $date_fin = date('Y-m-d', strtotime(" -1 days"));
                    break;
            };
             * 
             *

            if(strtotime((new \DateTime())->format("H:i:s")) > strtotime("11:10:00")){
                $type_pointage = "24";
            }

            //$list_date = [$date_debut, $date_intermediaire, $date_fin];commentaire


            /**
             * jour feries
             */
            $date_feries = [];
            //dump($list_date);
            foreach ($list_date as $k => $date) {
                if ($this->isJourFeries(strtotime($date))) {
                    $date_feries[date('w')] = $date;
                }
                if (date('N', strtotime($date)) > 6) {
                    //dump($date);
                    unset($list_date[$k]);
                }
            }
            //dd($date_feries);

            $infoAllFeries = [];
            if (count($date_feries) > 0) {
                foreach ($date_feries as $ferie) {
                    foreach ($list_date as $key => $date_filter) {
                        if ($date_filter == $ferie) {
                            $infoFeries = [
                                "keyF"  => $key,
                                "dateF" => $date_filter
                            ];
                            $infoAllFeries[] = $infoFeries;
                        }
                    }
                }
            }
            //dump($list_date);
            //dd($infoAllFeries);
            if (count($infoAllFeries) > 0) {
                /**
                foreach($infoAllFeries as $list_feries){
                    if($list_feries["keyF"] == 0){
                        $list_date[0] = (new \DateTime($list_feries["dateF"]))->sub(new \DateInterval("P1D"))->format("Y-m-d");
                    }elseif($list_feries["keyF"] == 1){
                        $list_date[0] = (new \DateTime($list_feries["dateF"]))->sub(new \DateInterval("P2D"))->format("Y-m-d");
                        $list_date[1] = (new \DateTime($list_feries["dateF"]))->sub(new \DateInterval("P1D"))->format("Y-m-d");
                    }else{
                        $list_date[0] = (new \DateTime($list_feries["dateF"]))->sub(new \DateInterval("P3D"))->format("Y-m-d");
                        $list_date[1] = (new \DateTime($list_feries["dateF"]))->sub(new \DateInterval("P2D"))->format("Y-m-d");
                        $list_date[2] = (new \DateTime($list_feries["dateF"]))->sub(new \DateInterval("P1D"))->format("Y-m-d");
                    }
                }
                 * 
                 */
                foreach ($infoAllFeries as $ferie) {
                    if (in_array($ferie["dateF"], $list_date)) {
                        unset($list_date[$ferie["keyF"]]);
                    }
                }
            }


            /**
             * recuperation de TOUS les id personnels qui dépand de equipe apm ou matin
             * 
             */
            $sql_pers = $personnel->Get([
                "personnel.id_personnel",
                //"type_pointage.id_type_pointage",
                "personnel.nom", "personnel.prenom", "personnel.nom_fonction", "actif", "id_type_pointage", "date_embauche"
            ]);
            if ($matricule) {
                $sql_pers->where('personnel.id_personnel = :id_p')
                    ->setParameter('id_p', $matricule)
                    ->andWhere('actif = :a')
                    ->setParameter('a', 'Oui');
            } else {
                $sql_pers->where('actif = :a AND id_personnel > 0')
                    ->setParameter('a', 'Oui');
            }
            if (count($fonction) > 0) {
                $critere = "";
                foreach ($fonction as $f) {
                    $critere .= "personnel.nom_fonction = '" . $f . "' OR ";
                }
                $critere = substr($critere, 0, strrpos($critere, " OR "));
                $sql_pers->andWhere($critere);
            } else {
                //->andWhere('id_type_pointage = :type_pointage')ncommentek
                //->setParameter('type_pointage', $type_pointage)
                $sql_pers->andWhere('personnel.nom_fonction NOT LIKE \'%Comptable%\' AND personnel.nom_fonction NOT LIKE \'%Logistique%\' AND personnel.nom_fonction NOT LIKE \'%Securite%\' ');
            }
            if ($type_pointage) {
                $sql_pers->andWhere('id_type_pointage = :type_pointage')
                    ->setParameter('type_pointage', $type_pointage);
            }
            $sql_pers->andWhere('personnel.date_embauche < :date_em')
                ->setParameter('date_em', implode('-', array_reverse(explode('-', $date_f))));

            $id_personnels_actif_by_team = $sql_pers->orderBy('personnel.id_personnel', 'ASC')
                ->execute()
                ->fetchAll();
            //dump($date);

            $ids_actifs = [];

            foreach ($id_personnels_actif_by_team as $id) {
                $ids_actifs[] = $id["id_personnel"];
            }
            $str_ids = implode(',', $ids_actifs);
            $str_date = implode(',', $list_date);

            //-----------------------------------------
            /**
             * recuperation des id personnel 3 jours auparavant par equipe qui on fait des pointage
             */

            foreach ($list_date as $key => $date_f) {
                $sql = $pointage->Get(
                    [
                        "personnel.id_personnel",
                    ]
                ) //->where('pointage.id_type_pointage = :id_type_pointage')nocommente
                    //->setParameter('id_type_pointage', $type_pointage)

                    ->where("date_debut = :d" . $key)
                    ->setParameter('d' . $key, $date_f);
                if ($matricule) {
                    $sql->andWhere('personnel.id_personnel = :id_p')
                        ->setParameter('id_p', $matricule);
                } else {
                    $sql->andWhere('personnel.id_personnel IN(' . $str_ids . ')');
                }
                if (count($fonction) > 0) {
                    $critere = "";
                    foreach ($fonction as $f) {
                        $critere .= "personnel.nom_fonction = '" . $f . "' OR ";
                    }
                    $critere = substr($critere, 0, strrpos($critere, " OR "));

                    $sql->andWhere($critere);
                }
                if ($type_pointage) {
                    $sql->andWhere('pointage.id_type_pointage = :id_type_pointage')
                        ->setParameter('id_type_pointage', $type_pointage);
                }
                $sql->orderBy('personnel.id_personnel', 'ASC');
                $list_user_pointage = $sql->execute()->fetchAll();

                if (count($list_user_pointage) > 0) {
                    $ids_do_pointage = [];
                    foreach ($list_user_pointage as $id_do_pointage) {
                        $ids_do_pointage[] = $id_do_pointage["id_personnel"];
                    }
                    /**
                     * recuperation des utilisateur qui n'ont fait de pointages
                     */
                    foreach ($ids_actifs as $actif) {
                        if (!in_array($actif, $ids_do_pointage)) {

                            $user_find = false;
                            foreach ($user_absents_in_date as $key => $user_absent) {
                                if ($user_absent["id"] == $actif) {
                                    $user_find = true;
                                    $date_insert = [];
                                    $date = $user_absent["date_absent"];
                                    $date[] = $date_f;

                                    $user = [
                                        "id" => $user_absent["id"],
                                        "date_absent" => $date
                                    ];
                                    $user_absents_in_date[$key] = $user;
                                }
                            }
                            if (!$user_find) {

                                $users_info_absent = [
                                    "id" => $actif,
                                    "date_absent" => [$date_f]
                                ];
                                $user_absents_in_date[] = $users_info_absent;
                            }
                        }
                    }
                } else {
                    if ($matricule) {
                        $users_info_absent = [
                            "id" => $matricule,
                            "date_absent" => [$date_f]
                        ];
                        $user_absents_in_date[] = $users_info_absent;
                    }
                }
            }

            $ids_present = [];
            $ids_absents = [];
            $user_absents_final = [];



            foreach ($user_absents_in_date as $list_user) {

                $sql2 = $absence->Get([
                    "absence_personnel.*",
                    "personnel.nom", "personnel.prenom", "personnel.nom_fonction", "personnel.id_personnel", "personnel.id_type_pointage"
                ])

                    ->where('personnel.id_personnel = :id_p')
                    ->setParameter('id_p', $list_user["id"])
                    ->andWhere('absence_personnel.date_fin_absence >=\'' . $list_user["date_absent"][0] . '\'')
                    ->andWhere('absence_personnel.categorie_absence = \'CONGE\' AND (absence_personnel.validation_cp = \'OUI\' OR absence_personnel.validation_rh = \'OUI\' OR absence_personnel.insertion_rh = \'OUI\')');

                $list_conge = $sql2->execute()->fetchAll();

                /**
                 * conge normal dans un mois
                 */
                if ($list_conge) {
                    $list_conge_user = [];
                    foreach ($list_conge as $conge) {

                        $fin = $conge["date_fin_absence"];

                        $debut = explode('-', $conge["date_debut_absence"]);

                        $annee_debut = $debut[0];
                        $mois_debut = $debut[1];
                        $jour_debut = $debut[2];

                        $incr = 0;
                        while (TRUE) {
                            $dt = date("Y-m-d", mktime(0, 0, 0, $mois_debut, $jour_debut + $incr, $annee_debut));

                            $list_conge_user[] = $dt;

                            $incr += 1;
                            if ($fin == $dt) {
                                break;
                            }
                        }
                    }

                    foreach ($list_user["date_absent"] as $d_absent) {
                        if (!in_array($d_absent, $list_conge_user)) {
                            $user_find = false;
                            $key_user = 0;
                            foreach ($list_user_not_valide as $key_inv => $user_invalide) {
                                if ($conge["id_personnel"] == $user_invalide["id_personnel"]) {
                                    $user_find = true;
                                    $key_user = $key_inv;
                                }
                            }
                            if ($user_find) {
                                $date = [];
                                $date = $list_user_not_valide[$key_user]["date_absence"];
                                if (!in_array($d_absent, $date)) {
                                    $date[] = $d_absent;
                                }
                                $list_user_not_valide[$key_user]["date_absence"] = $date;
                            } else {
                                $date = [];
                                $date[] = $d_absent;
                                $conge["date_absence"] = $date;

                                $list_user_not_valide[] = $conge;
                            }
                        }
                    }

                    //}


                } else {
                    foreach ($list_user["date_absent"] as $d_absent) {
                        $user_absent = $absence->Get([
                            "absence_personnel.*",
                            "personnel.nom", "personnel.prenom", "personnel.nom_fonction", "personnel.id_personnel", "personnel.id_type_pointage"
                        ])
                            ->where('absence_personnel.id_personnel = :id_p')
                            ->setParameter('id_p', $list_user["id"])
                            ->execute()->fetch();
                        $user_find = false;
                        $key_user = 0;
                        if ($user_absent) {
                            foreach ($list_user_not_valide as $key_inv => $user_invalide) {
                                if ($user_absent["id_personnel"] == $user_invalide["id_personnel"]) {
                                    $user_find = true;
                                    $key_user = $key_inv;
                                }
                            }
                            if ($user_find) {
                                $date = [];
                                $date = $list_user_not_valide[$key_user]["date_absence"];
                                $date[] = $d_absent;
                                $list_user_not_valide[$key_user]["date_absence"] = $date;
                            } else {
                                $date = [];
                                $date[] = $d_absent;
                                $user_absent["date_absence"] = $date;
                                $list_user_not_valide[] = $user_absent;
                            }
                        }
                    }
                }
            }

            $tab_temp = [];
            foreach ($list_user_not_valide as $key => $user) {
                if (preg_match("/OP 1|CORE 2|CORE 1|OP 2/", $user["nom_fonction"])) {
                    unset($user["id_absence_personnel"]);
                    $user["nb_absence"] = count($user["date_absence"]);
                    $tab_temp[] = $user;
                }
            }

            $list_user_not_valide = $tab_temp;

            /**
             * trier par ordre decroissant par clés [0=>[],1=>[]]
             */
            $nb_absence = [];
            foreach ($list_user_not_valide as $key => $row) {
                $nb_absence[$key] = $row['nb_absence'];
            }

            array_multisort($nb_absence, SORT_DESC, $list_user_not_valide);

            $data_absent = [];
            foreach ($list_user_not_valide as $absent) {
                $data_absent[$absent["id_personnel"]] = $absent["nb_absence"];
            }
            /**
             * mtady ilay olona tara fun isan'andro
             *
            foreach($list_date_copie as $date){
                if(!$this->isWeekend($date)){
                    if($date == $retard["date_debut"]){
                        $list_personnel_retard_per_days[$date][] = $retard["id_personnel"];
                    }
                }
            }
            
            /**
             * mtady ilay olona tara isan'andro ao anatin interval
             */
            $percent =  floor(80 * count($list_date_copie) / 100);
            //$nb_dt = count($list_personnel_retard_per_days);

            foreach ($data_absent as $m => $nb_abs) {
                if ($nb_abs >= $percent) {
                    //dump("matr", $m, "nb",$i);
                    $list_personnel_retard_per_days_final[] = $m;
                    $list_personnel_retard_per_days_final = array_unique($list_personnel_retard_per_days_final);
                }
            }
            /**
             * trie
             */
            if ($signe) {
                foreach ($list_user_not_valide as $key => $user_not_valide) {
                    switch ($signe) {
                        case ' <= ':
                            if ($user_not_valide["nb_absence"] > $nombre) {
                                unset($list_user_not_valide[$key]);
                            }
                            break;
                        case ' >= ':

                            if ($user_not_valide["nb_absence"] < $nombre) {
                                unset($list_user_not_valide[$key]);
                            }

                            break;
                        default:
                            break;
                    }
                }
            }
        }

        /**
         * manala ilay date androan ho any apm satria maren iz tsy manw pointage de tafiditra
         */
        foreach ($list_user_not_valide as $user_abs) {
            if ($user_abs["id_type_pointage"] == 24) {
                foreach ($user["date_absence"] as $k => $dd_abs) {
                    if (strtotime($dd_abs) == strtotime(date('Y-m-d'))) {
                        if (strtotime(date('H:i:s')) < strtotime("12:10:00")) {
                            unset($list_user_not_valide[$user_abs["id_type_pointage"]][$user["date_absence"][$k]]);
                        }
                    }
                }
            }
        }
        /**
         * maka ny ul tara @andro precis (ohatra latsinen)
         */
        foreach ($list_user_not_valide as $user_v) {
            foreach ($user_v["date_absence"] as $dA) {
                if (!array_key_exists($user_v["id_personnel"], $absence_frequent_par_semaines)) {
                    $absence_frequent_par_semaines[$user_v["id_personnel"]] = [];

                    if (!array_key_exists(date('D', strtotime($dA)), $absence_frequent_par_semaines[$user_v["id_personnel"]])) {
                        $absence_frequent_par_semaines[$user_v["id_personnel"]][date('D', strtotime($dA))] = 1;
                    }
                } else {
                    if (!array_key_exists(date('D', strtotime($dA)), $absence_frequent_par_semaines[$user_v["id_personnel"]])) {
                        $absence_frequent_par_semaines[$user_v["id_personnel"]][date('D', strtotime($dA))] = 1;
                    } else {
                        $nb_ab = $absence_frequent_par_semaines[$user_v["id_personnel"]][date('D', strtotime($dA))];
                        $absence_frequent_par_semaines[$user_v["id_personnel"]][date('D', strtotime($dA))] = $nb_ab + 1;
                    }
                }
            }

            foreach ($absence_frequent_par_semaines as $mt => $absence_frequent) {
                foreach ($absence_frequent as $k => $nombre) {
                    $nb = round($count_days[$k] * 80 / 100, 2);
                    if ($nombre < $nb) {
                        unset($absence_frequent_par_semaines[$mt][$k]);
                    }
                }

                if (count($absence_frequent_par_semaines[$mt]) == 0) {
                    unset($absence_frequent_par_semaines[$mt]);
                }
            }
        }

        //dump($list_user_not_valide);
        //dump($absence_frequent_par_semaines);
        return $this->render('rh/absenceImprevu.html.twig', [
            "personnel_absent_per_days" => $list_personnel_retard_per_days_final,
            "list_absence_imprevu" => $list_user_not_valide,
            "form" => $form->createView(),
            "absence_frequent_par_semaines" => $absence_frequent_par_semaines
        ]);
    }
    /**
     * verifie si une date est fériée
     * @param type $date
     * @return type boolean
     */
    private function isJourFeries($date)
    {
        if ($date === null) {
            $date = time();
        }

        $date = strtotime(date('m/d/Y', $date));

        $year = date('Y', $date);

        $easterDate  = easter_date($year);
        $easterDay   = date('j', $easterDate);
        $easterMonth = date('n', $easterDate);
        $easterYear   = date('Y', $easterDate);

        $holidays = array(
            // Dates fixes
            mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
            mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
            //mktime(0, 0, 0, 12,  18 ,  $year),  // test ito
            mktime(0, 0, 0, 6,  26, $year),  // Fête nationale
            mktime(0, 0, 0, 8,  15, $year),  // Assomption
            mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
            //mktime(0, 0, 0, 11, 11, $year),  // Armistice
            mktime(0, 0, 0, 12, 25, $year),  // Noel

            // Dates variables
            mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear),
            mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear),
            mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear),
        );

        return in_array($date, $holidays);
    }
    /**
     * retourne le nombre de jour dans un mois
     * @param type $mois
     * @param type $an
     * @return int
     */
    private function getNumberDayInMonth($mois, $an)
    {
        $enmois = $an * 12 + $mois;
        if (($enmois > 2037 * 12 - 1) || ($enmois < 1970)) {
            return 0;
        }
        $an_suivant = floor(($enmois + 1) / 12);
        $mois_suivant = $enmois + 1 - 12 * $an_suivant;
        $duree = mktime(0, 0, 1, $mois_suivant, 1, $an_suivant) - mktime(0, 0, 1, $mois, 1, $an);
        return ($duree / (3600 * 24));
    }

    public function retard(
        Request $request,
        Connection $connexion,
        \App\DataTransformer\DateToStringTransformer $dateToStringTransformer
    ) {
        $data_final = [];
        $data_final1 = [];
        $fonctions = [];
        $date = null;
        $matricule = null;
        $equipe = 2;
        $refRetard = null;
        $name_excel = null;
        $list_personnel_retard_per_days = [];
        $list_personnel_retard_per_days_final = [];
        $list_interval_date = [];
        $list_personnel_retard_per_days_final1 = [];
        $jour_retard_frequent_semaine = [];
        $retard_jour_par_semaine = [];

        $critere_search = [];
        $info_user = [];
        $retards_to_twig = [];
        $retards_to_twig1 = [];
        $infos_retard = [];

        $pointage = new Pointage($connexion);
        $personnel = new Personnel($connexion);

        $dateFin = new \DateTime();
        $dateDebut = (new \DateTime())->sub(new \DateInterval("P5D"));
        $searchActive = false;


        $data_fonctions = $personnel->Get([
            "personnel.nom_fonction",
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.prenom"
        ])
            ->where('personnel.actif = :a AND personnel.id_personnel > 0')
            ->andWhere('personnel.nom_fonction NOT LIKE \'%Comptable%\' AND personnel.nom_fonction NOT LIKE \'%Logistique%\' AND personnel.nom_fonction NOT LIKE \'%Securite%\' ')
            ->setParameter('a', 'Oui')
            ->orderBy("id_personnel", "ASC")
            ->execute()->fetchAll();

        foreach ($data_fonctions as $fonction) {

            if (!in_array($fonction["nom_fonction"], $fonctions)) {
                $fonctions[$fonction["nom_fonction"]] = $fonction["nom_fonction"];
            }
            if (!in_array($fonction["id_personnel"] . " - " . $fonction["prenom"] . " " . $fonction["nom"], $info_user)) {
                $info_user[$fonction["id_personnel"] . " - " . $fonction["prenom"] . " " . $fonction["nom"]] = $fonction["id_personnel"];
            }
        }

        $form = $this->createFormBuilder()
            ->add("date_interval", TextType::class, [
                "label" => "Date",

            ])
            ->add("fonction", ChoiceType::class, [
                "multiple" => true,
                "required" => false,
                "choices" => $fonctions,
                "attr" => [
                    "class" => "fonction",

                ]
            ])->add("matricule", ChoiceType::class, [
                "placeholder" => "--Selectionnez--",
                "required" => false,
                "choices" => $info_user
            ])
            ->add('equipe', ChoiceType::class, [
                "placeholder" => "--Selectionnez--",
                "required" => false,
                'choices' => [
                    'Equipe Matin' => 1,
                    'Equipe APM' => 24
                ]
            ])
            ->add('signe', ChoiceType::class, [
                "placeholder" => "--Selectionnez--",
                "required" => false,
                "choices" => [
                    ">=" => " >= ",
                    "<=" => " <= "
                ]
            ])
            ->add('nombre', TextType::class, [
                "required" => false,

            ])
            ->add('export', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                "required" => false,

            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $searchActive = true;
            $data = $form->getData();

            $fonction = $data["fonction"];
            $date = explode(' - ', $data["date_interval"]);
            //dd($date);
            $matricule = $data["matricule"];
            $equipe = $data["equipe"];
            $signe = $data["signe"];
            $nombre = $data["nombre"];
            $isExport = $data["export"];

            if ((is_null($signe) && !is_null($nombre)) && (!is_null($signe) && is_null($nombre))) {

                $this->addFlash('danger', "Veuillez renseigner le signe ou nombre");
                return $this->redirectToRoute("retard_user");
            }
            /**
            if($nombre == 0 && is_string($nombre)){
                $this->addFlash('danger',"Veuillez saisissez un nombre");
                return $this->redirectToRoute("retard_user");
            }
             * 
             */
            if (preg_match('/,/', $nombre)) {
                $nombre = str_replace(',', '.', $nombre);
            }
            $nombre = round($nombre / 60, 2);
        }
        //dd($pointage->Get(["personnel.*"])->execute()->fetch());
        $sql1 = null;
        $infos_retard1 = null;
        $sql = $pointage->Get(
            [
                "date_debut",
                "personnel.nom_fonction",
                "retard",
                "personnel.id_personnel"
            ]
        )

            ->where('personnel.actif = :a AND personnel.id_personnel > 0')
            ->andWhere('personnel.nom_fonction NOT LIKE \'%Comptable%\' AND personnel.nom_fonction NOT LIKE \'%Logistique%\' AND personnel.nom_fonction NOT LIKE \'%Securite%\' ')
            ->setParameter('a', 'Oui')
            ->andWhere('retard > 0');

        if (!$searchActive) {

            $sql
                ->andWhere('date_debut BETWEEN :debut AND :fin')
                ->setParameter('debut', $dateDebut->format("Y-m-d"))
                ->setParameter('fin', $dateFin->format("Y-m-d"));
            $infos_retard = $sql
                ->orderBy("personnel.nom_fonction", "ASC")
                ->orderBy("date_debut", "ASC")
                ->execute()->fetchAll();
        } else {

            if ($matricule) {
                $sql->andWhere('personnel.id_personnel = :id')
                    ->setParameter('id', $matricule);
            }
            if (count($date) > 0) {

                $sql->andWhere('date_debut BETWEEN :debut AND :fin')
                    ->setParameter('debut', implode('-', array_reverse(explode('-', $date[0]))))
                    ->setParameter('fin', implode('-', array_reverse(explode('-', $date[1]))));
            }
            if (count($fonction) > 0) {
                $critere = "";


                foreach ($fonction as $f) {
                    $critere .= "personnel.nom_fonction = '" . $f . "' OR ";
                }

                $critere = substr($critere, 0, strrpos($critere, " OR "));

                $sql->andWhere($critere);
            }
            if ($equipe) {
                if ($equipe == 1 || $equipe == 24) {
                    $sql->andWhere('personnel.id_type_pointage = :id_pointage')
                        ->setParameter('id_pointage', $equipe);
                }
            }

            $sql1 = $sql; // infecten eto ilay $sql global izay tsy maints miseo

            $infos_retard = $sql
                ->orderBy("personnel.nom_fonction", "ASC")
                ->orderBy("date_debut", "ASC")
                ->execute()->fetchAll();


            /**
             * manipulation des graphp
             */
            if ($signe && $nombre) {
                $sql1->andWhere('retard ' . $signe . '' . $nombre);
                $infos_retard1 = $sql1
                    ->orderBy("date_debut", "ASC")
                    ->orderBy("retard", "DESC")
                    ->execute()->fetchAll();
            }
        }



        /**
         * graph 2 qui a le fitre > heure retard ou < heure retard
         */
        if (!is_null($infos_retard1)) {

            /**
             * excel
             */
            if ($isExport) {
                $name_excel = "/Retard_" . time() . ".xlsx";
                $dirPiece = $this->getParameter('app.temp_dir');
                $nomFichier = $dirPiece . "" . $name_excel;
                $headers = [
                    "Matricule", "Date", "Durée"
                ];

                $writer = WriterEntityFactory::createXLSXWriter();
                $writer->openToFile($nomFichier); // write data to a file or to a PHP stream
                $cells = WriterEntityFactory::createRowFromArray($headers);
                $writer->addRow($cells);
            }
            foreach ($infos_retard1 as $retard) {
                if (!array_key_exists($retard["id_personnel"], $data_final1)) {
                    $data_final1[$retard["id_personnel"]] = [$retard["date_debut"] => $retard["retard"]];
                } else {
                    $rt = $data_final1[$retard["id_personnel"]];
                    $data_final1[$retard["id_personnel"]] = array_merge($rt, [$retard["date_debut"] => $retard["retard"]]);
                }
                /**
                 * ajout de ligne en excel
                 */
                if ($isExport) {
                    $cells = WriterEntityFactory::createRowFromArray([
                        $retard["id_personnel"],
                        $retard["date_debut"],
                        $retard["retard"]
                    ]);
                    $writer->addRow($cells);
                }
            }
            if ($isExport) {
                $writer->close();
            }

            /**
             * creation des date entre 2 interval
             */
            //dd($date);
            $debut_jour = explode("/", $date[0])[0];
            $debut_mois = explode("/", $date[0])[1];
            $debut_annee = explode("/", $date[0])[2];

            $fin_jour = explode("/", $date[1])[0];
            $fin_mois = explode("/", $date[1])[1];
            $fin_annee = explode("/", $date[1])[2];

            $debut_date = mktime(0, 0, 0, $debut_mois, $debut_jour, $debut_annee);
            $fin_date = mktime(0, 0, 0, $fin_mois, $fin_jour, $fin_annee);
            //$list_interval_date = [];
            for ($i = $debut_date; $i <= $fin_date; $i += 86400) {
                $list_interval_date[] = date("Y-m-d", $i);
                $list_interval_date = array_unique($list_interval_date);
                foreach ($data_final1 as $matricule => $infos) {
                    if (!array_key_exists(date("Y-m-d", $i), $infos)) {

                        $tr = $data_final1[$matricule];
                        $data_final1[$matricule] = array_merge($tr, [date('Y-m-d', $i) => 0]);

                        ksort($data_final1[$matricule]);
                    }
                }
            }
            //dd($data_final1);
            foreach ($data_final1 as $mtr => $inf) {
                $x = [];
                $y = [];
                $mn = [];
                foreach ($inf as $d => $r) {
                    $x[] = $d;
                    $y[] = $r;
                    $mn[] = 60 * $r;
                }
                $retards_to_twig1[$mtr] = ["x" => $x, "y" => $y, "minute" => $mn];
            }
        }

        $nb_semaines = [];
        foreach ($infos_retard as $retard) {
            $list_interval_date[] = $retard["date_debut"];
            $list_interval_date = array_unique($list_interval_date);

            if (!array_key_exists($retard["nom_fonction"], $data_final)) {
                $data_final[$retard["nom_fonction"]][$retard["date_debut"]] = $retard["retard"];
                if (!array_key_exists($data_final[$retard["nom_fonction"]][$retard["date_debut"]], $data_final)) {
                    $data_final[$retard["nom_fonction"]][$retard["date_debut"]] = $retard["retard"];
                } else {
                    $ret = $data_final[$retard["nom_fonction"]][$retard["date_debut"]];
                    $data_final[$retard["nom_fonction"]][$retard["date_debut"]] = $ret + $retard["retard"];
                }
            } else {
                if (array_key_exists($retard["date_debut"], $data_final[$retard["nom_fonction"]])) {

                    $ret = $data_final[$retard["nom_fonction"]][$retard["date_debut"]];
                    $data_final[$retard["nom_fonction"]][$retard["date_debut"]] = $ret + $retard["retard"];
                } else {
                    $data_final[$retard["nom_fonction"]][$retard["date_debut"]] = $retard["retard"];
                }
            }
            /**
             * mtady ilay olona tara fun isan'andro
             */

            foreach ($list_interval_date as $date) {
                if (!$this->isWeekend($date)) {
                    if ($date == $retard["date_debut"]) {
                        $list_personnel_retard_per_days[$date][] = $retard["id_personnel"];
                    }
                }
            }

            /**
             * maka ny ul tara @andro re isak erinandro
             *
             **/
            if (!array_key_exists($retard["id_personnel"], $retard_jour_par_semaine)) {
                $retard_jour_par_semaine[$retard["id_personnel"]] = [
                    $retard["date_debut"] => date('l', strtotime($retard["date_debut"]))
                ];
            } else {
                $retard_jour_par_semaine[$retard["id_personnel"]][$retard["date_debut"]] = date('l', strtotime($retard["date_debut"]));
            }

            /**
             * maka ny isan'ny semaine
             */
            if (!in_array(date('W', strtotime($retard["date_debut"])), $nb_semaines)) {
                $nb_semaines[] = date('W', strtotime($retard["date_debut"]));
            }
        }


        $percent_semaine = floor(80 * count($nb_semaines) / 100);
        //$nb_semaines = count($nb_semaines);
        $nb_week = count($nb_semaines);
        //dump($nb_week);
        //dump($retard_jour_par_semaine);
        $jour_retard_frequent_semaine1 = [];
        $jour_retard_frequent_semaine2 = [];

        foreach ($retard_jour_par_semaine as $matric => $dat) {
            foreach ($dat as $d1 => $j1) {
                $index = 0;
                $jour_in_tab = null;
                foreach ($dat as $d => $j) {
                    if ($j1 == $j) {
                        $index = $index + 1;
                        $jour_in_tab = $j1;
                    }
                }
                if ($index >= $percent_semaine) {
                    if (!array_key_exists($matric, $jour_retard_frequent_semaine1)) {
                        $jour_retard_frequent_semaine1[$matric] = [$jour_in_tab];
                    } else {
                        if (!in_array($jour_in_tab, $jour_retard_frequent_semaine1[$matric])) {
                            $jour_retard_frequent_semaine1[$matric][] = $jour_in_tab;
                        }
                    }
                }
            }
        }
        /**
         * trie par ordre decroissant date izay be indrindra
         */
        foreach ($jour_retard_frequent_semaine1 as $mt => $jt) {
            $jour_retard_frequent_semaine2[] = [$mt => $jt];
        }

        for ($i = 0; $i < count($jour_retard_frequent_semaine2); $i++) {
            for ($j = 0; $j < count($jour_retard_frequent_semaine2); $j++) {
                foreach ($jour_retard_frequent_semaine2[$i] as $matricules => $dd) {
                    foreach ($jour_retard_frequent_semaine2[$j] as $matricules1 => $dd1) {
                        if (count($dd) > count($dd1)) {
                            $perm = $jour_retard_frequent_semaine2[$i];
                            $jour_retard_frequent_semaine2[$i] = $jour_retard_frequent_semaine2[$j];
                            $jour_retard_frequent_semaine2[$j] = $perm;
                        }
                    }
                }
            }
        }
        //dd($jour_retard_frequent_semaine2);
        foreach ($jour_retard_frequent_semaine2 as $dte) {
            foreach ($dte as $mtt => $ddte) {
                $jour_retard_frequent_semaine[$mtt] = $ddte;
            }
        }
        //dd($jour_retard_frequent_semaine);
        /**
         * mtady ilay olona tara isan'andro ao anatin interval
         */

        $percent =  floor(80 * count($list_personnel_retard_per_days) / 100);
        $nb_dt = count($list_personnel_retard_per_days);


        foreach ($list_personnel_retard_per_days as $date => $val) {
            foreach ($val as $m) {
                $i = 0;
                foreach ($list_personnel_retard_per_days as $v) {
                    if (in_array($m, $v)) {
                        $i++;
                    }
                }

                if ($i >= $percent) {
                    $list_personnel_retard_per_days_final[] = $m;
                    $list_personnel_retard_per_days_final = array_unique($list_personnel_retard_per_days_final);
                }
            }
        }


        foreach ($data_final as $fonction => $d) {
            foreach ($d as $date => $retard) {
                $retards_to_twig[$fonction]["x"][] = $date;
                $retards_to_twig[$fonction]["y"][] = round($retard, 2);
            }
        }

        $max_tab = [];

        foreach ($retards_to_twig as $key => $d) {
            $is_tab_petit = false;
            $date_add = null;
            foreach ($retards_to_twig as $d1) {
                if (count($d["x"]) < count($d1["x"])) {
                    $max_tab = $d1["x"];
                    foreach ($d1["x"] as $date) {
                        if (!in_array($date, $d["x"])) {

                            $is_tab_petit = true;
                            $date_add = $date;
                        }
                    }
                }
                if (count($max_tab) < count($d1["x"])) {
                    $max_tab = $d1["x"];
                }
            }
            if ($is_tab_petit) {

                $retards_to_twig[$key]["x"][] = $date_add;
                $retards_to_twig[$key]["y"][] = 0;
            }
        }
        //dump($jour_retard_frequent_semaine);
        //dump($list_personnel_retard_per_days_final);
        return $this->render('rh/retard.html.twig', [
            "personnel_retard_per_days" => $list_personnel_retard_per_days_final,
            "data" => $retards_to_twig,
            "data1" => $retards_to_twig1,
            "form" => $form->createView(),
            "label" => $max_tab,
            "fileNameExcel" => $name_excel,
            "jour_retard_frequent_semaine" => $jour_retard_frequent_semaine
        ]);
    }

    public function isWeekend($date)
    {
        return (date('N', strtotime($date)) > 6);
    }

    public function custom_sort($a, $b)
    {
        return count($a) < count($b);
    }
    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function heureManquantProductionNormal(Connection $connex, Request $request)
    {

        date_default_timezone_set('UTC');
        $pointage = new \App\Model\GPAOModels\Pointage($connex);
        $personnel = new \App\Model\GPAOModels\Personnel($connex);

        $data_days = [];
        $datas = [];
        $info_user = [];

        $data_fonctions = $personnel->Get([

            "personnel.nom_fonction",
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.prenom"

        ])
            ->where('personnel.actif = :a AND personnel.id_personnel > 0')
            ->andWhere('personnel.nom_fonction IN (\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'CQ 1\')')
            ->setParameter('a', 'Oui')
            ->orderBy("id_personnel", "ASC")
            ->execute()->fetchAll();


        foreach ($data_fonctions as $fonction) {
            if (!in_array($fonction["id_personnel"] . " - " . $fonction["prenom"] . " " . $fonction["nom"], $info_user)) {
                $info_user[$fonction["id_personnel"] . " - " . $fonction["prenom"] . " " . $fonction["nom"]] = $fonction["id_personnel"];
            }
        }
        $form = $this->createFormBuilder()
            ->add('dates', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                //"required" => false,
            ])
            ->add("matricule", ChoiceType::class, [
                "placeholder" => "--Selectionnez--",
                "required" => false,
                "choices" => $info_user
            ])
            ->add('equipe', ChoiceType::class, [
                "placeholder" => '--Selectionnez--',
                "required" => false,
                "choices" => [
                    "Matin " => 1,
                    "APM" => 24
                ]
            ])
            ->add('signe', ChoiceType::class, [
                "placeholder" => "--Selectionnez--",
                "required" => false,
                "attr" => [
                    "class" => "form-control signe"
                ],
                "choices" => [
                    "<" => "<",
                ]
            ])
            ->add('heure_sortie', TextType::class, [
                "label" => "Heure sortie",
                "required" => false,
                "attr" => [
                    "class" => "form-control",
                    "placeholder" => "hh:ii:ss",
                ]
            ])
            ->getForm();
        $form->handleRequest($request);
        /**
        dd($pointage->Get([
                                    "pointage.heure_sortie",
                                    "pointage.date_debut",
                                    "personnel.prenom",
                                    "personnel.nom",
                                    "personnel.id_personnel",
                                    "personnel.id_type_pointage",
                                    "description"
                            ])->where("personnel.id_personnel = :id_personnel")->setParameter("id_personnel", 2156)->execute()->fetch());
         **/


        if ($form->isSubmitted()) {
            $dates = $form->getData()["dates"];


            $date_begin = explode(' - ', $dates)[0];
            $date_end = explode(' - ', $dates)[1];

            $matr = $form->getData()["matricule"];
            $equipe = $form->getData()["equipe"];

            $signe = $form->getData()["signe"];
            $heure_sortie = $form->getData()["heure_sortie"];
            $filtreSigneExist = false;

            /**
             * vérification de signe
             */
            if ((is_null($signe) && !is_null($heure_sortie)) || (!is_null($signe) && is_null($heure_sortie))) {
                $this->addFlash("danger", "Veuillez renseigner le signe et l'heure de sortie");
                return $this->redirectToRoute("heure_manquant");
            } else {
                if (!is_null($signe) && !is_null($heure_sortie)) {
                    if (!preg_match("#[0-2][0-9]:[0-5][0-9]:[0-5][0-9]#", $heure_sortie)) {
                        $this->addFlash("danger", "Format heure invalide hh:ii:ss");
                        return $this->redirectToRoute("heure_manquant");
                    } else {
                        if (!$equipe) {
                            $this->addFlash("danger", "Veuillez renseigner aussi l'équipe");
                            return $this->redirectToRoute("heure_manquant");
                        } else {
                            if ($equipe == 1 && (strtotime($heure_sortie) > strtotime("12:10:00"))) {

                                $this->addFlash("danger", "Le filtre heure de sortie doit être inférieur à 12:10:00 pour l'équipe MATIN");
                                return $this->redirectToRoute("heure_manquant");
                            } else if ($equipe == 24 && strtotime($heure_sortie) < strtotime("12:10:00")) {

                                $this->addFlash("danger", "Le filtre heure de sortie doit être supérieur à 12:20:00 pour l'équipe APM");
                                return $this->redirectToRoute("heure_manquant");
                            }
                        }
                    }
                    $filtreSigneExist = true;
                }
            }



            /**
             * list allaitement
             */
            $list_allaitement = [261, 321, 332, 431, 441, 442, 757, 775, 792, 1002, 1014, 1107, 1151, 1198, 1335, 1352, 1441, 1473, 1508, 1585, 1611, 1673, 1700, 1709, 1766, 1804, 1836, 1844, 1885, 1936, 861, 972];
            $sqlPointage = $pointage->Get([
                "pointage.heure_sortie",
                "pointage.date_debut",
                "personnel.prenom",
                "personnel.nom",
                "personnel.id_personnel",
                "personnel.id_type_pointage",
                "description"
            ])
                ->where('date_debut BETWEEN :debut AND :fin')
                ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\', \'CQ 1\')')
                ->setParameter('debut', $date_begin)
                ->setParameter('fin', $date_end)
                ->andWhere('description != :description')
                ->setParameter('description', "Extra");
            if (!empty($matr)) {
                $sqlPointage->andWhere('personnel.id_personnel = :id_personnel')
                    ->setParameter('id_personnel', $matr);
            } else {
                if (!empty($equipe)) {
                    $heure_sortie_reel = "12:10:00";
                    $heure_entre_reel = "06:00:00";
                    $heure_sortie_demi = "09:30:00";
                    if ($equipe == 24) {
                        $heure_sortie_reel = "18:30:00";
                        $heure_entre_reel = "12:10:00";
                        $heure_sortie_demi = "12:10:00";
                    }
                    if ($filtreSigneExist) {
                        $heure_sortie_reel = $heure_sortie;
                    }

                    $sqlPointage->andWhere('personnel.id_type_pointage = :id_type_pointage AND (pointage.heure_entre >= :heure_entre AND pointage.heure_sortie < :heure_sortie)')
                        ->setParameter('heure_entre', $heure_entre_reel)
                        ->setParameter('heure_sortie', $heure_sortie_reel)
                        ->setParameter('id_type_pointage', $equipe);
                    /**
                                   if((strtotime($date_begin) <= strtotime("25-12-".explode("-",$date_begin)[3]))) && (strtotime("25-12-".explode("-",$date_begin)[3])) <= strtotime($date_end))){
                                       
                                   }**/
                } else {

                    $sqlPointage->andWhere('((personnel.id_type_pointage = :id_type_pointage1 AND pointage.heure_sortie < :heure_sortie1) OR '
                        .  '((personnel.id_type_pointage = :id_type_pointage2 AND pointage.heure_sortie < :heure_sortie2) AND '
                        .  '(personnel.id_type_pointage = :id_type_pointage2 AND pointage.heure_entre > :heure_entre2)))')

                        ->setParameter('id_type_pointage1', 1)
                        ->setParameter('heure_sortie1', "12:10:00")

                        ->setParameter('id_type_pointage2', 24)
                        ->setParameter('heure_sortie2', "18:30:00")
                        ->setParameter('heure_entre2', "12:10:00");
                }
            }

            //dump($sqlPointage);

            $heure_manquants = $sqlPointage->execute()->fetchAll();


            foreach ($heure_manquants as $info) {
                $heure_sortie_reel_default = "12:10:00";
                $is_allaitement = false;

                if (in_array($info["id_personnel"], $list_allaitement)) {
                    $is_allaitement = true;
                    $heure_sortie_reel_default = "17:30:00";
                    if ($info["id_type_pointage"] == 1) {
                        $heure_sortie_reel_default = "11:10:00";
                    }
                }
                if (!$is_allaitement) {
                    if ($info["id_type_pointage"] == 24) {
                        $heure_sortie_reel_default = "18:30:00";
                    }
                }



                /**
                 * date different de 31-12-20xx et 24-12-20xx
                 */
                if (!preg_match("#" . date('Y', strtotime($info["date_debut"])) . "-12-24#", $info["date_debut"]) && !preg_match("#" . date('Y', strtotime($info["date_debut"])) . "-12-31#", $info["date_debut"])) {
                    /**
                     * si l'heure de sortie est inferieur de l'heure sortie reel, donc il manque d'heure
                     * ny tena ilaina anaz dia ho any allaitement fa ny allaitement tsy mtovy @reetra ny heure de sortie
                     **/
                    $switchFiltre = !$filtreSigneExist ? $heure_sortie_reel_default : $heure_sortie;
                    if (strtotime($info["heure_sortie"]) < strtotime($switchFiltre)) {

                        if (!array_key_exists($info["id_personnel"], $data_days)) {
                            $data_days[$info["id_personnel"]] = [
                                "date" => [$info["date_debut"]],
                                "jour" => [date('l', strtotime($info["date_debut"]))],
                                "heure_sortie" => [$info["heure_sortie"]],
                                "heure_manquant" => [date('H:i:s', strtotime($heure_sortie_reel_default) - strtotime($info["heure_sortie"]))],
                                "nom" => $info["nom"],
                                "prenom" => $info["prenom"],
                                "equipe" => $info["id_type_pointage"],
                            ];
                        } else {
                            $data_days[$info["id_personnel"]]["jour"][] =  date('l', strtotime($info["date_debut"]));
                            $data_days[$info["id_personnel"]]["heure_sortie"][] =  $info["heure_sortie"];
                            $data_days[$info["id_personnel"]]["heure_manquant"][] =  date('H:i:s', strtotime($heure_sortie_reel_default) - strtotime($info["heure_sortie"]));
                            $data_days[$info["id_personnel"]]["date"][] =  $info["date_debut"];
                        }
                    }
                }
            }


            /**
             * trie par nombre de dates
             */
            $data_filter = [];
            foreach ($data_days as $matricule => $data) {
                $data_filter[] = [
                    $matricule => $data
                ];
            }

            for ($i = 0; $i < count($data_filter); $i++) {
                for ($j = 0; $j < count($data_filter); $j++) {
                    foreach ($data_filter[$i] as $matricule => $data) {
                        $mt = $matricule;
                        foreach ($data_filter[$j] as $matricule1 => $data1) {
                            if (count($data["date"]) > count($data1["date"])) {
                                $perm = $data_filter[$i];
                                $data_filter[$i] = $data_filter[$j];
                                $data_filter[$j] = $perm;
                            }
                        }
                    }
                }
            }
            for ($k = 0; $k < count($data_filter); $k++) {
                foreach ($data_filter[$k] as $mtt => $data2) {
                    $datas[$mtt] = $data2;
                }
            }
        }

        //dump($datas);
        return $this->render('rh/heure_manquant_prod.html.twig', [
            "form" => $form->createView(),
            //"data" => $list_personnel_heure_extra_manquant
            "data" => $datas
        ]);
    }
    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function importationAndExportationRib(Connection $connex, Request $request, $slug)
    {

        $rib = new \App\Model\GPAOModels\Rib($connex);
        $pers = new \App\Model\GPAOModels\Personnel($connex);
        $message = "";
        $users_not_rib = [];
        $matricules_rib = [];
        $info_banque = [];
        $search_data = null;
        $total_actif = 0;

        $ribs = $rib->Get(["rib.*", "personnel.nom", "personnel.prenom", "personnel.actif"])
            ->orderBy('rib.id_personnel')
            ->execute()->fetchAll();

        foreach ($ribs as $r) {
            $matricules_rib[] = $r["id_personnel"];
            /**
             * nombre de personne dans un banque
             */
            if ($r["actif"] == 'Oui') {
                $total_actif += 1;
                $nom_banque = explode(" ", $r["domiciliation"])[0];
                if (array_key_exists($nom_banque, $info_banque)) {
                    $info_banque[$nom_banque] = $info_banque[$nom_banque] + 1;
                } else {
                    $info_banque[$nom_banque] = 1;
                }
            }
        }
        /**
         * calcule de pourcentage
         */
        foreach ($info_banque as $key => $val) {
            $info_banque[$key] = $val . " (" . number_format(($val * 100) / $total_actif, 2) . "%)";
            //$total += number_format(($val*100)/$total_actif,2);
        }

        $sqlPersonnel = $pers->Get(["id_personnel", "login", "actif", "nom", "prenom"])
            ->where('personnel.id_personnel > 0');

        $personnels_actif = $sqlPersonnel->andWhere('personnel.actif = :actif')
            ->setParameter('actif', "Oui")
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()
            ->fetchAll();


        /**
         * personnel actif mais qui n'ont pas de rib
         */
        foreach ($personnels_actif as $personnel) {
            if (!in_array($personnel["id_personnel"], $matricules_rib)) {
                $users_not_rib[] = ["matricule" => $personnel["id_personnel"], "login" => $personnel["nom"] . ' ' . $personnel["prenom"], "status" => $personnel["actif"]];
            }
        }

        $form = $this->createFormBuilder()
            ->add('file', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                "label" => false,
                "attr" => [
                    "class" => "form-control"
                ],
                "constraints" => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader des fichier .xlsx ou .xls'
                    ]),
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        "message" => "Fichier excel obligatoire"
                    ])
                ]
            ])->getForm();
        $form->handleRequest($request);
        /**
         * exportation et search
         */
        if ($slug) {
            if ($slug != "search") {
                $dir = $this->getParameter('app.piece_dir');

                if (count($ribs) == 0) {
                    $this->addFlash('danger', "Veuillez importer d'abord le rib!");
                    return $this->redirectToRoute('rh_importation_exportation_rib');
                }
                /**
                 * longueur maximal des id_personnel
                 */
                $length_max_matricule = 1;
                foreach ($ribs as $rib) {
                    if (strlen($rib["id_personnel"]) > $length_max_matricule) {
                        $length_max_matricule = strlen($rib["id_personnel"]);
                    }
                }

                if (count($ribs) > 0) {
                    $name_file = $dir . "/" . uniqid() . ".xlsx";

                    $headers = ["MATRICULE", "NOM ET PRENOM", "BANQUE", "CODE BANQUE", "CODE AGENCE", "COMPTE", "CLE"];
                    $writer = WriterEntityFactory::createXLSXWriter();
                    $writer->setTempFolder($dir);
                    $writer->openToFile($name_file); // write data to a file or to a PHP stream
                    /**
                     * style header
                     */
                    $styleHeader = (new \Box\Spout\Writer\Common\Creator\Style\StyleBuilder())
                        ->setFontBold()
                        ->setFontSize(12)
                        ->setFontColor(\Box\Spout\Common\Entity\Style\Color::WHITE)
                        ->setShouldWrapText()
                        ->setCellAlignment(\Box\Spout\Common\Entity\Style\CellAlignment::CENTER)
                        ->setBackgroundColor(\Box\Spout\Common\Entity\Style\Color::BLUE)
                        ->build();
                    $row_headers = WriterEntityFactory::createRowFromArray($headers, $styleHeader);
                    $writer->addRow($row_headers);
                    /**
                     * style data
                     */
                    $styleRow = (new \Box\Spout\Writer\Common\Creator\Style\StyleBuilder())
                        ->setFontSize(12)
                        ->build();
                    foreach ($ribs as $rib) {
                        $numberZeroLeft = $length_max_matricule - strlen($rib["id_personnel"]);
                        $matricule = (string)$rib["id_personnel"];
                        $zeroDevant = "";
                        for ($i = 0; $i < $numberZeroLeft; $i++) {
                            $zeroDevant .= "0";
                        }
                        $matricule = $zeroDevant . (string)$rib["id_personnel"];
                        //$matricule = str_pad((string)$rib["id_personnel"], ($numberZeroLeft+1), 0, STR_PAD_LEFT);//ajout des zéros AVANT selon le nombre de caractère du matricule

                        $data = [
                            $matricule,
                            strtoupper(str_replace([
                                'à', 'â', 'ä', 'á', 'ã', 'å',
                                'î', 'ï', 'ì', 'í',
                                'ô', 'ö', 'ò', 'ó', 'õ', 'ø',
                                'ù', 'û', 'ü', 'ú',
                                'é', 'è', 'ê', 'ë',
                                'ç', 'ÿ', 'ñ'
                            ], [
                                'A', 'A', 'A', 'A', 'A', 'A',
                                'I', 'I', 'I', 'I',
                                'O', 'O', 'O', 'O', 'O', 'O',
                                'U', 'U', 'U', 'U',
                                'E', 'E', 'E', 'E',
                                'C', 'Y', 'N',

                            ], str_replace('*', '', $rib["nom"]))) . " " . strtoupper(str_replace([
                                'à', 'â', 'ä', 'á', 'ã', 'å',
                                'î', 'ï', 'ì', 'í',
                                'ô', 'ö', 'ò', 'ó', 'õ', 'ø',
                                'ù', 'û', 'ü', 'ú',
                                'é', 'è', 'ê', 'ë',
                                'ç', 'ÿ', 'ñ'
                            ], [

                                'A', 'A', 'A', 'A', 'A', 'A',
                                'I', 'I', 'I', 'I',
                                'O', 'O', 'O', 'O', 'O', 'O',
                                'U', 'U', 'U', 'U',
                                'E', 'E', 'E', 'E',
                                'C', 'Y', 'N',

                            ], str_replace('*', '', $rib["prenom"]))),
                            $rib["domiciliation"],
                            $rib["code_banque"],
                            $rib["code_agence"],
                            $rib["rib"],
                            $rib["clef"]
                        ];

                        $row = WriterEntityFactory::createRowFromArray($data, $styleRow);
                        $writer->addRow($row);
                    }
                    $writer->close();
                    $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($name_file);
                    $response->setContentDisposition(
                        \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        basename($name_file)
                    );
                    return $response;
                }

                return $this->redirectToRoute('rh_importation_exportation_rib');
            } else {
                /**
                 * search
                 */
                $keyWord = $request->request->get('keyword');
                $search_data = $keyWord;
                $searchData = [];
                foreach ($ribs as $rib) {
                    if ($keyWord ==  $rib["id_personnel"] || preg_match("#" . strtoupper($keyWord) . "#", $rib["domiciliation"])) {
                        $searchData[] = $rib;
                    }
                }
                if (count($searchData) > 0) {
                    $this->addFlash("success", count($searchData) . " résultats ont été trouvés");
                    $ribs = $searchData;
                } else {
                    $ribs = [];
                    $this->addFlash("warning", "0 résultat trouvé");
                }
            }
        }
        /**
         * importation rib
         */
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->getData()["file"];

            $extension = $uploadedFile->getClientOriginalExtension();
            $file_name = time() . "." . $extension;

            if ($uploadedFile->move($this->getParameter('app.piece_dir'), $file_name)) {
                $path = $this->getParameter('app.piece_dir');
                $nomFichier = $path . "/" . $file_name;

                $messageFlash = [
                    "success" => ["message" => null],
                    "warning" => ["message" => null],
                    "erreur" => ["message" => null]
                ];

                $matriculeNotListPersonnel = "";
                $matriculeUpdated = "";
                $matriculeInsered = "";

                $reader = \Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createReaderFromFile($nomFichier);
                $reader->open($nomFichier);

                $all_id_personnel = [];
                $allpersonnel = $pers->Get(["id_personnel", "login", "actif"])
                    ->where('personnel.id_personnel > 0')
                    ->orderBy('personnel.id_personnel', 'ASC')
                    ->execute()
                    ->fetchAll();

                foreach ($allpersonnel as $personnel) {
                    $all_id_personnel[] = $personnel["id_personnel"];
                }

                $i = 0;
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $i++;
                        /**
                         * ne pas prendre l'entete
                         */
                        if ($i > 1) {

                            $cells = $row->getCells();
                            $matr = $cells[0]->getValue();
                            //$matricules_rib[] = $matr;

                            $data = [
                                //"id_personnel" => $matr,
                                "code_banque" => explode(" ", $cells[1]->getValue())[0],
                                "code_agence" => explode(" ", $cells[1]->getValue())[1],
                                "rib" => explode(" ", $cells[1]->getValue())[2],
                                "clef" => explode(" ", $cells[1]->getValue())[3],
                                "domiciliation" =>   $cells[2]->getValue()
                            ];
                            /**
                             * vérification si le matr est dans la table personnel
                             */
                            if (in_array($matr, $all_id_personnel)) {
                                $get_rib = $rib->Get(["personnel.id_personnel, rib.id_rib"])
                                    ->where("rib.id_personnel = :id_personnel")
                                    ->setParameter('id_personnel', $matr)
                                    ->execute()->fetch();


                                /**
                                 * update rib s'il existe dans la table rib
                                 */
                                if ($get_rib) {

                                    $rib->updateData($data, ["id_rib" => $get_rib["id_rib"]])
                                        ->execute();
                                    $matriculeUpdated .= $matr . ", ";
                                    $messageFlash["warning"]["message"] = "Le rib de(s) matricule(s) [" . $matriculeUpdated . "] ont été mise à jour avec success";
                                } else {

                                    /**
                                     * insert nouveau ligne
                                     */
                                    $data["id_personnel"] = $matr;
                                    $matriculeInsered .= $matr . ", ";
                                    $rib->insertData($data)
                                        ->execute();
                                    $messageFlash["success"]["message"] = "Le rib de(s) matricule(s) [" . $matriculeInsered . "] ont été inseré avec succes!";
                                }
                            } else {
                                /**
                                 * notification d'erreur si le matricule n'est pas dans la liste personnel
                                 **/
                                $matriculeNotListPersonnel .= $matr . ", ";
                                $messageFlash["erreur"]["message"] = "Le(s) matricule(s) [" . $matriculeNotListPersonnel . "] n'est pas encore inseré dans la liste personnelle";
                            }
                        }
                    }
                }
                $reader->close();
                unlink($this->getParameter('app.piece_dir') . "/" . $file_name); //on supprime le fichier uploader
                /**
                 * messages flashs
                 */
                foreach ($messageFlash as $type => $message) {
                    if (!is_null($message["message"])) {
                        $this->addFlash($type, $message["message"]);
                    }
                }
                return $this->redirectToRoute("rh_importation_exportation_rib");
            }
        }
        /**
        dump($ribs);
        dump($info_banque);
        dump($search_data);
         **/
        return $this->render("rh/rib.html.twig", [
            "form" => $form->createView(),
            "message" => $message,
            "users_not_rib" => $users_not_rib,
            "ribs" => $ribs,
            "info_banques" => $info_banque,
            "keyword" => $search_data
        ]);
    }
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function gestionDemande(Request $request, Connection $connex, SessionInterface $session)
    {
        $isModif = false;

        $demande = new DemandeSupplementaire($connex);

        $id_demande = $request->query->get('id');
        $date_value = '';
        $heure_debut_value = '';
        $heure_fin_value = '';
        $etat_validation_value = '';
        $id_personnel_value = '';

        $sqlDemande = $demande->Get([
            "demande_supplementaire.*",

        ]);
        /**
         * id_demande existe, càd update
         */
        if ($id_demande) {
            $isModif = true;
            $pers_data = $demande->Get([
                "demande_supplementaire.*"
            ])->where('id_demande_supplementaire = :id_demande')
                ->setParameter('id_demande', $id_demande)
                ->execute()->fetch();

            $date_value = $pers_data["date_suplementaire"];
            $heure_debut_value = $pers_data["heure_debut_supplementaire"];
            $heure_fin_value = $pers_data["heure_fin_supplementaire"];
            $etat_validation_value = $pers_data["etat_validation"];
            $id_personnel_value = $pers_data["id_personnel"];
        }
        if ($request->query->get('slug') == "search") {
            if ($request->request->get('date') == "") {
                $this->addFlash("error_search", "Veuillez renseigner la date.");
                return $this->redirectToRoute("app_gestion_demande");
            }
        }
        /**
         * forcer l'utilisateur à renseigner la date dans le recherche
         */
        if ($request->query->get('slug') == "search") {
            if ($request->request->get('date') == "") {
                $this->addFlash("error_search", "Veuillez renseigner la date.");
                return $this->redirectToRoute("app_gestion_demande");
            }
        }
        /**
         * si session plage_date ou matricule existe, donc il y a une recherche qui est activé
         **/
        if ($session->get('plage_date') or $session->get('matricule')) {
            /**
             * si on fait une recherche
             */
            if ($request->query->get('slug') == "search") {
                $session->set('plage_date', $request->request->get('date'));
                $session->set('matricule', $request->request->get('matricule'));
            }
            $matricule_search = $session->get('matricule');
            $dates = $session->get('plage_date');
            /**
             * permet de vérifier si une insertion est déclenchée,
             * si $dates est null, donc c'est une insertion
             * sinon c'est une recherche
             */
            if ($dates) {
                if ($matricule_search && $dates) {
                    $dates = explode(' - ', $dates);

                    $sqlDemande->where('demande_supplementaire.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule_search)
                        ->andWhere('date_suplementaire BETWEEN :dateD AND :dateF')
                        ->setParameter('dateD', $dates[0])
                        ->setParameter('dateF', $dates[1]);
                } else if ($matricule_search) {
                    $sqlDemande->where('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule_search);
                } else {
                    $dates = explode(' - ', $dates);
                    $sqlDemande
                        ->where('date_suplementaire BETWEEN :dateD AND :dateF')
                        ->setParameter('dateD', $dates[0])
                        ->setParameter('dateF', $dates[1]);
                }
            }
        } else {
            if ($request->query->get('slug') && $request->query->get('slug') == 'search') {
                $matricule_search = $request->request->get('matricule');
                $dates = $request->request->get('date');

                $session->set('plage_date', $dates);
                $session->set('matricule', $matricule_search);

                if ($matricule_search && $dates) {

                    $dates = explode(' - ', $dates);
                    $sqlDemande->where('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule_search)
                        ->andWhere('date_suplementaire BETWEEN :dateD AND :dateF')
                        ->setParameter('dateD', $dates[0])
                        ->setParameter('dateF', $dates[1]);
                } else if ($matricule_search) {
                    $sqlDemande->where('personnel.id_personnel = :id_personnel')
                        ->setParameter('id_personnel', $matricule_search);
                } else {
                    $dates = explode(' - ', $dates);
                    $sqlDemande
                        ->where('date_suplementaire BETWEEN :dateD AND :dateF')
                        ->setParameter('dateD', $dates[0])
                        ->setParameter('dateF', $dates[1]);
                }
            }
        }

        $personnel = new Personnel($connex);
        /**
         * personnel form select formulaire
         */
        $personnels = $personnel->Get([
            "id_personnel",
            "nom",
            "prenom"
        ])->where('personnel.id_personnel > 0 AND actif =\'Oui\'')
            ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();

        $pers_choices = [];
        foreach ($personnels as $pers) {
            $pers_choices[$pers["id_personnel"] . ' - ' . $pers["nom"] . ' ' . $pers["prenom"]] = $pers["id_personnel"];
        }

        $formBuilder = $this->createFormBuilder()
            ->add('date_sup', TextType::class, [
                "attr" => [
                    "value" => $date_value
                ]
            ])
            ->add('personnel', ChoiceType::class, [
                "placeholder" => '-Selectionnez-',
                "choices" => $pers_choices,
                "data" => $id_personnel_value

            ])
            ->add('hD', TextType::class, [
                "attr" => [
                    "value" => $heure_debut_value
                ]
            ])
            ->add('hF', TextType::class, [
                "attr" => [
                    "value" => $heure_fin_value
                ]
            ]);

        if ($isModif) {
            $formBuilder->add("etat", ChoiceType::class, [
                "placeholder" => "-Selectionnez-",
                "choices" => [
                    "Accorder" => "Accorder",
                    "Rejeter" => "Rejeter",
                    "En Attente" => "En Attente"
                ],
                "data" => $etat_validation_value
            ]);
        }
        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $date = $form->get('date_sup')->getData();
            $matricule = $form->get('personnel')->getData();
            $heure_debut = $form->get('hD')->getData();
            $heure_fin = $form->get('hF')->getData();

            $heure_debut = new DateTime($heure_debut);
            $heure_fin = new DateTime($heure_fin);


            if ($heure_fin->getTimestamp() <= $heure_debut->getTimestamp()) {
                $this->addFlash("danger", "L'heure debut doit être inférieur à l'heure fin ");
                return $this->redirectToRoute("app_gestion_demande");
            }

            if ($heure_debut->add(new DateInterval("PT5H"))->getTimestamp() < $heure_fin->getTimestamp()) {
                $this->addFlash("danger", "L'heure debut + 5 heures doit être supérieur ou egale à l'heure fin");
                return $this->redirectToRoute("app_gestion_demande");
            }

            $data = [
                "date_suplementaire" => $date,
                "heure_debut_supplementaire" => $heure_debut->sub(new DateInterval("PT5H"))->format("H:i:s"),
                "heure_fin_supplementaire" => $heure_fin->format("H:i:s"),
                "id_personnel" => $matricule,
                "etat_validation" => $id_demande ? $form->get('etat')->getData() : "Accorder",
                "date_envoie" => date('Y-m-d')
            ];
            /**
             * insertion
             */
            if (!$isModif) {
                $demande->insertData($data)->execute();
                /**
                 * on enleve les sessions pour pouvoir entrer une autre demande
                 */
                $session->remove("plage_date");
                $session->remove('matricule');

                $this->addFlash("success", "Insertion demande effectué avec success!");
            } else {
                /**
                 * si modification, on fait une redirection
                 */
                $demande->updateData($data, ["id_demande_supplementaire" => $id_demande])->execute();
                $this->addFlash("success", "Modification demande effectué avec success!");
            }
            return $this->redirectToRoute("app_gestion_demande");
        }
        /**
         * search not active if session not defined
         */
        if (!$session->get('plage_date')) {
            $sqlDemande->where('date_suplementaire = :date')
                ->setParameter('date', date('Y-m-d'));
        }

        $demandes = $sqlDemande->orderBy('date_suplementaire', 'DESC')
            ->execute()
            ->fetchAll();

        return $this->render("rh/gestion_heure_suppl.html.twig", [
            "form" => $form->createView(),
            "demandes" => $demandes,
        ]);
    }
    /**
     * @Security("is_granted('ROLE_RH')")
     * @Route("/rh/fraude", name="app_rh_fraude")
     */
    public function fraude(Request $request, Connection $connex): Response
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        $data = [];
        $pointage = new Pointage($connex);
        $prod = new Production($connex);
        $equipe = new EquipeTacheOperateur($connex);


        $form = $this->createFormBuilder()
            ->add('date', TextType::class, [
                "required" => true
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $date = $form->get('date')->getData();
            $pointages = $pointage->Get([
                "personnel.id_personnel",
                "pointage.heure_entre",
                "pointage.heure_reel_entree",
            ])
                ->where('personnel.nom_fonction IN (\'OP 1\',\'OP 2\')')
                ->andWhere('date_debut = :date')
                ->setParameter('date', $date)
                ->orderBy('personnel.id_personnel', 'ASC')
                ->execute()
                ->fetchAll();

            $productions = $prod->Get([
                "personnel.id_personnel",
                "production.heure_reel_debut"
            ])->where("production.date_traitement = :date_debut")
                ->andWhere('personnel.nom_fonction IN (\'OP 1\', \'OP 2\')')
                ->setParameter("date_debut", $date)
                ->orderBy('production.heure_debut', 'ASC')
                ->execute()
                ->fetchAll();
            /**
             * prendre seulement le premier ligne du production qui correspond à son 
             * premier production de la journée
             */
            $premier_ligne_prods = [];
            foreach ($productions as $production) {
                if (!array_key_exists($production["id_personnel"], $premier_ligne_prods)) {
                    $premier_ligne_prods[$production["id_personnel"]] = $production;
                }
            }

            foreach ($pointages as $pointage) {
                foreach ($premier_ligne_prods as $production) {
                    if ($production["id_personnel"] == $pointage["id_personnel"]) {
                        $heure_dif_timestamp = strtotime($production["heure_reel_debut"]) - strtotime($pointage["heure_entre"]);

                        if (date("H:i:s", $heure_dif_timestamp) >= "00:30:00") {
                            if ($pointage["heure_entre"] < $production["heure_reel_debut"]) {
                                $data[$pointage["id_personnel"]] = [
                                    "heure_entre" => $pointage["heure_entre"],
                                    "heure_reel_entre" => $pointage['heure_reel_entree'],
                                    "heure_debut_prod" => $production["heure_reel_debut"],
                                    "difference" => date("H:i:s", $heure_dif_timestamp)
                                ];
                            }
                        }
                    }
                }
            }
        }
        dump($data);
        return $this->render("rh/fraude.html.twig", [
            "form" => $form->createView(),
            "data" => $data
        ]);
    }
    /**
     * @Security("is_granted('ROLE_RH')")
     * @Route("/rh/gestion/{option}", name="app_gestion_allaitement_conge_maternite")
     */
    public function gestionAllaitementAndcongeMaternite(string $option, Request $request, Connection $connex): Response
    {
        $entity = new CongeMaternite($connex);
        $pers = new Personnel($connex);
        $resultSearch = null;
        $personnels = [];
        $filter = [
            "personnel.id_personnel",
            "personnel.nom",
            "personnel.prenom"
        ];
        $personnels_get = $pers->Get($filter)
            ->where('personnel.actif =\'Oui\' AND personnel.sexe = \'FEMININ\'')
            ->orderBy('id_personnel', 'ASC')
            ->execute()
            ->fetchAll();

        foreach ($personnels_get as $pers) {
            $personnels[$pers['id_personnel'] . ' - ' . $pers['nom'] . ' ' . $pers['prenom']] = $pers['id_personnel'];
        }

        if ($option == "allaitement") {
            $entity = new Allaitement($connex);
        }

        /**
         * search
         */
        if ($request->request->get('search')) {
            $dates = $request->request->get('search');
            $date_fin = explode(' - ', $dates)[1];

            $filter[] = 'date_debut';
            $filter[] = 'date_fin';

            $resultSearch = $entity->Get($filter)
                ->where('date_fin >= :date_fin')
                ->setParameter('date_fin', $date_fin)
                ->execute()
                ->fetchAll();
        }
        /**
         * form
         */
        $form = $this->createFormBuilder()
            ->add('personnel', ChoiceType::class, [
                "placeholder" => '-Selectionnez-',
                "choices" => $personnels,
                "required" => true
            ])
            ->add('dates', TextType::class, [
                'required' => true
            ])
            ->add('remarques', TextareaType::class, [
                "required" => true
            ])->getForm();

        $form->handleRequest($request);
        /**
         * form submit
         */
        if ($form->isSubmitted()) {
            $data = $form->getData();
            $id_personnel = $data['personnel'];
            $dates = $data['dates'];
            $date_debut = explode(' - ', $dates)[0];
            $date_fin = explode(' - ', $dates)[1];
            $remarques = $data['remarques'];

            if (strtotime($date_debut) > strtotime($date_fin)) {
                $this->addFlash('error', "La date de debut doit être inférieur à la date de fin");
                $this->redirectToRoute("app_gestion_allaitement_conge_maternite", ['option' => $option]);
            }


            $entity->insertData([
                "id_personnel" => $id_personnel,
                "date_debut" => $date_debut,
                "date_fin" => $date_fin,
                "remarques" => $remarques
            ])->execute();

            $this->redirectToRoute("app_gestion_allaitement_conge_maternite", ['option' => $option]);
        }

        $data = $entity->Get([
            "personnel.id_personnel",
            "remarques",
            "date_debut",
            "date_fin",
            "personnel.nom",
            "personnel.prenom"
        ])->execute()->fetchAll();
        // dump($data);
        return $this->render('rh/gestion_allaitement_or_conge_maternite.html.twig', [
            "form" => $form->createView(),
            'option' => $option,
            "resultSearch" => $resultSearch,
            "data" => $data
        ]);
    }
}
