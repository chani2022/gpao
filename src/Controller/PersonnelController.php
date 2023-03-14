<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;

use App\Model\GPAOModels\LimitationAccess;
use App\Model\GPAOModels\Personnel;
use App\Model\GPAOModels\Information;

use Doctrine\DBAL\Driver\Connection;

class PersonnelController extends AbstractController
{

    private  $listeJours = [
        "1" => "Lundi", "2" => "Mardi", "3" => "Mercredi", "4" => "Jeudi", "5" => "Vendredi", "6" => "Samedi"
    ];

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function index(Request $request, Connection $cnx)
    {

        $laObj = new LimitationAccess($cnx);

        $obj = $laObj->Get();

        $matriculeSearch = $request->query->get('keywords');
        if (!is_null($matriculeSearch) && $matriculeSearch != "" && is_numeric($matriculeSearch)) {
            $obj->where('limitation_acces.id_personnel = :id')->setParameter('id', $matriculeSearch);
        }

        $liste = $obj->orderBy('limitation_acces.date_debut,limitation_acces.id_personnel', 'ASC')->execute()->fetchAll();

        //ny donnee groupee
        $liste_grouped = [];
        $liste_type_pointage = [];
        foreach ($liste as $l) {
            $j = $l['jours_autorisations'];
            $idp = $l['id_type_pointage'];
            if (!in_array($idp, $liste_type_pointage)) {
                $liste_type_pointage[] = $idp;
            }
            if (!array_key_exists($j, $liste_grouped)) {
                $liste_grouped[$j] = [];
            }
            if (!array_key_exists($idp, $liste_grouped[$j])) {
                $liste_grouped[$j][$idp] = [];
            }

            $liste_grouped[$j][$idp][] = [
                "nom" => $l['nom'],
                "prenom" => $l['prenom'],
                "id_personnel" => $l['id_personnel'],
                "equipe" => $l['id_equipe_tache_operateur']
            ];
        }

        sort($liste_type_pointage);

        return $this->render('limitation_access/index.html.twig', [
            "liste" => $liste,
            "liste_grouped" => $liste_grouped,
            "liste_jours" => $this->listeJours,
            "liste_type_pointage" => $liste_type_pointage
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function addFromList(Request $req, Connection $cnx)
    {

        $fichier = $req->files->get('liste_txt');

        if (!is_null($fichier)) {
            $dirDest = $this->getParameter('app.temp_dir');

            $fileName = time() . '-' . uniqid() . '.' . $fichier->guessExtension();

            try {
                $fichier->move($dirDest, $fileName);
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $ex) {

                $this->addFlash("danger", "Le fichier n'a pas pu être envoyé");
                return new Response("KO");
            }

            $content = file_get_contents($dirDest . "/" . $fileName);

            $datas = explode("\n", trim($content));

            $nbLigneInserted = 0;
            if (count($datas) > 0) {
                $objLimite = new LimitationAccess($cnx);

                foreach ($datas as $d) {
                    $rD = explode(";", $d);
                    if (count($rD) == 3) {

                        $id_personnel = $rD[0];
                        $jours_autorisations = $rD[1];
                        $date_debut = $rD[2];

                        $objLimite->insertData([
                            "id_personnel" => $id_personnel,
                            "jours_autorisations" => $jours_autorisations,
                            "date_debut" => $date_debut
                        ])->execute();
                        $nbLigneInserted += 1;
                    }
                }
            }

            if ($nbLigneInserted > 0) {
                $this->addFlash("success", $nbLigneInserted . " lignes ont été insérées");
            } else {
                $this->addFlash("warning", "Aucune donnée n'a été insérée");
            }
        }

        return new Response("OK");
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * Limitation acces
     */
    public function edit(Request $request, Connection $cnx)
    {

        //suppression
        $objLimite = new LimitationAccess($cnx);
        $delete = $request->query->get('delete');

        if (!is_null($delete) && is_numeric($delete)) {
            $objLimite->deleteData()->where('id_limitation_acces = :id')->setParameter('id', $delete)->execute();
            $this->addFlash('success', "Suppression effectuée");

            return $this->redirectToRoute('index_limitation_access');
        }

        $persObj = new Personnel($cnx);

        $liste_personnel = $persObj->Get()
            ->where('actif = :a AND id_personnel > :id')->setParameter('a', 'Oui')->setParameter('id', 0)
            ->orderBy('id_personnel', 'ASC')
            ->execute()
            ->fetchAll();

        $choix_personnel =  [];
        foreach ($liste_personnel as $lp) {
            $choix_personnel[$lp['id_personnel'] . " " . $lp['prenom'] . " " . $lp['nom']] = $lp['id_personnel'];
        }

        $choix_jours = [];
        foreach ($this->listeJours as $k => $j) {
            $choix_jours[$j] = $k;
        }
        /**
         * formulaire ajout
         */
        $defaultDataForm = ['id_limitation_acces' => NULL, 'id_personnel' => '', 'date_debut' => new \DateTime(date("Y-m-d")), 'jours_autorisations' => []];

        $edit = $request->query->get('edit');
        $formAction = $this->generateUrl('edit_limitation_access');

        if (!is_null($edit)) {
            $formAction = $this->generateUrl('edit_limitation_access') . "?id_limitation_acces=" . $edit;
            $getData = $objLimite->Get()->where('limitation_acces.id_limitation_acces = :id')->setParameter('id', $edit)->execute()->fetchAll();



            if (count($getData) > 0) {
                $getData = $getData[0];
                $defaultDataForm = [
                    'id_limitation_acces' => $getData['id_limitation_acces'],
                    'id_personnel' => $getData['id_personnel'],
                    'jours_autorisations' => explode(",", $getData['jours_autorisations']),
                    'date_debut' => new \DateTime($getData['date_debut'])
                ];
            } else {
                $this->addFlash('warning', "Objet introuvable");
                return $this->redirectToRoute('index_limitation_access');
            }
        }

        //formulaire
        $form = $this->createFormBuilder($defaultDataForm, [
            "method" => "POST",
            "action" => $formAction,
        ])->add('id_limitation_acces', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, [
            "required" => false
        ])
            ->add('id_personnel', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "label" => "Matricule",
                "required" => true,
                "attr" => [
                    "class" => "form-control id_personnel",
                ],
                "choices" => $choix_personnel,
                "placeholder" => "--Choisir--"

            ])->add('date_debut', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                "widget" => "single_text",
                "html5" => false,
                "label" => "Date début",
                "required" => true,
                "format" => "dd/MM/yyyy",
                "attr" => [
                    "class" => "form-control date_debut",
                ],
                "constraints" => [
                    new \Symfony\Component\Validator\Constraints\Date(["message" => "Ce champ doit contenir une date valide"])
                ]
            ])
            ->add('jours_autorisations', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                "label" => "Jours autorisations",
                "required" => true,
                "attr" => [
                    "class" => "form-control jours",
                ],

                "multiple" => true,
                "expanded" => true,
                "choices" => $choix_jours

            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $id_personnel = $data['id_personnel'];
            $jours_autorisations = $data['jours_autorisations'];
            $date_debut = $data['date_debut'];
            $id_limitation_acces = $data['id_limitation_acces'];

            //verification renseignement jours
            if (count($jours_autorisations) > 0) {

                $userData = [
                    "id_personnel" => $id_personnel,
                    "jours_autorisations" => implode(",", $jours_autorisations),
                    "date_debut" => $date_debut->format("d/m/Y")
                ];

                $url = $this->generateUrl('index_limitation_access') . "?keywords=" . $id_personnel;

                //mise a jour
                if (!is_null($id_limitation_acces) && is_numeric($id_limitation_acces)) {
                    $objLimite->updateData($userData)->where('id_limitation_acces = :id')
                        ->setParameter('id', $id_limitation_acces)
                        ->execute();

                    $this->addFlash('primary', "Modification effectuée pour le matricule " . $data['id_personnel']);
                    //return $this->redirectToRoute('edit_limitation_access');

                    return $this->redirect($url);
                }

                //si insertion
                $objLimite->insertData($userData)->execute();

                $this->addFlash('success', "Insertion effectuée pour le matricule " . $data['id_personnel']);


                //return $this->redirectToRoute('edit_limitation_access');
                return $this->redirect($url);
            } else {
                $this->addFlash('danger', "Veuillez renseigner les jours");
            }
        }


        return $this->render('limitation_access/edit.html.twig', [
            "liste_personnel" => $liste_personnel,
            "liste_jour" => $this->listeJours,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Security ("is_granted('ROLE_RH')")
     * @param Request $req
     */

    public function indexAjoutCommunique(Request $req, Connection $cnx)
    {

        $delete = $req->query->get('delete');
        $mInfo = new Information($cnx);

        //suppression message
        if (!is_null($delete) && is_numeric($delete)) {

            $mInfo->deleteData()->where('id_information = :id')
                ->setParameter('id', $delete)
                ->execute();

            $this->addFlash('primary', 'Le message a été supprimé');

            return $this->redirectToRoute('add_communique');
        }

        $defaultDataForm = ['text' => ''];

        $form = $this->createFormBuilder($defaultDataForm, [
            "method" => "POST",
            "action" => $this->generateUrl('add_communique'),
        ])
            ->add('text', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                "label" => "Texte",
                "required" => true,
                "attr" => [
                    "class" => "form-control",
                    "rows" => 8
                ],
                "constraints" => [
                    new \Symfony\Component\Validator\Constraints\Length([
                        "min" => 10,
                        "minMessage" => "Ce champ doit contenir aux moins {{ limit }} caractères"
                    ])
                ]
            ])
            ->getForm();

        $form->handleRequest($req);
        //enregistrement donnee
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $texteInfo = $data['text'];
            $texteInfo = strip_tags(trim($texteInfo));

            $mInfo->insertData([
                "obs" => "COMMUNIQUE",
                "titre" => "COMMUNIQUE",
                "date" => date("Y-m-d"),
                "heure" => date("H:i:s"),
                "id_personnel" => $this->getUser()->getUserDetails()['id_personnel'],
                "texte" => $texteInfo
            ])->execute();

            $this->addFlash('success', 'Votre message a été enregistré');

            return $this->redirectToRoute('add_communique');
        }

        $liste_info = $mInfo->Get()->where('information.obs = :o')
            ->setParameter('o', 'COMMUNIQUE')
            ->orderBy('information.id_information', 'DESC')
            ->execute()->fetchAll();

        return $this->render('rh/index-insertion-information.html.twig', [
            "liste_info" => $liste_info,
            "form" => $form->createView(),
        ]);
    }
}