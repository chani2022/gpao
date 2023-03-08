<?php

/**
 * gestion des paie, recolte d'heure, generation fiche de paie
 */

namespace App\Controller;

use App\Entity\GPAOUser;
use Couchbase\Exception;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Docx_reader\Docx_reader;
use Dompdf\Dompdf;
use Dompdf\Options;
use mysql_xdevapi\Session;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Void_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Tests\Compiler\J;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\CallbackTransformer;

use Symfony\Component\HttpFoundation\Response;

use Knp\Component\Pager\PaginatorInterface;

use App\Service\DateTools;

use App\Form\RecolteHeureType;
use App\Entity\RecolteHeure;
use App\Entity\JourFeries;

use App\Model\GPAOModels\Personnel;
use App\Model\GPAOModels\Pointage;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Validator\Constraints\NotBlank;

use Knp\Snappy\Pdf;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;



/**
 * @author Jimmy
 */
class PaieController extends AbstractController
{
    /**
     * @Route(path="/test-pdf-dodson", name="test_pdf_dodson")
     */
    public function testPdfDodson(Pdf $pdf)
    {
        $html = $this->renderView('test/test_pdf_dodson.html.twig', [
        ]);
        $pdfView = $pdf->getOutputFromHtml($html);
        // return new Response($pdf);
        // return new PdfResponse(
        //     $pdfView,
        //     'file.pdf'
        // );
        return new Response($pdfView,200, array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'inline; filename="file.pdf"'
            )
        );
    }


    /**
     * @Security("is_granted('ROLE_RH')")
     * Index Recolte
     * @param Request $req
     * @param DateTools $dateTools
     * @return type
     */
    public function indexRecolte(Request $req, DateTools $dateTools)
    {

        $searchDate = $req->request->get('date');

        $dateDebQuery = $dateFinQuery = "";

        //lancement de la recherche
        if (!is_null($searchDate)) {
            $dA = explode(" - ", $searchDate);
            if (count($dA) == 2) {
                $dateDebQuery = $dA[0];
                $dateFinQuery = $dA[1];
            }

            $objDates = $dateTools->getDatesFromDateRangePicker($searchDate);

            //redirection à la page d'édition
            return $this->redirectToRoute('recolte_creation', [
                "dateDebut" => $objDates["debut"],
                "dateFin" => $objDates["fin"]
            ]);
        }


        return $this->render('paie/index-recolte.html.twig', [
            "dateDebut" => $dateDebQuery,
            "dateFin" => $dateFinQuery
        ]);
    }

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function creationRecolte($dateDebut, $dateFin, Request $req, Connection $cnx, SessionInterface $sess)
    {

        $personnel = new Personnel($cnx);

        $liste_personnel = $personnel->getListePersonnel();

        $sess->set("dateDebutCompte", $dateDebut);
        $sess->set("dateFinCompte", $dateFin);

        return $this->render('paie/creation-recolte.html.twig', [
            "dateDebut" => $dateDebut,
            "dateFin" => $dateFin,
            "liste_personnel" => $liste_personnel
        ]);
    }

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function saveRecolte(Request $req, Connection $cnx, SessionInterface $sess)
    {

        $recolte = new RecolteHeure();

        $Personnel = new Personnel($cnx);

        //chargement
        $idPersonnel = $req->query->get('user');

        if (!$sess->has("dateDebutCompte")) {
            $this->addFlash("danger", "Les dates de comptes ne sont pas encore défini");
            return $this->redirectToRoute('recolte_index');
        }

        $debutCompte = $sess->get("dateDebutCompte");
        $finCompte = $sess->get("dateFinCompte");

        if (!is_null($idPersonnel)) {
            $get = $Personnel->getListePersonnel($idPersonnel);

            if (count($get) > 0) {
                $recolte->setMatricule($get[0]['id_personnel']);
                $recolte->setNom($get[0]['nom']);
                $recolte->setPrenom($get[0]['prenom']);
                $recolte->setFonction($get[0]['nom_fonction']);
                $recolte->setDateEmbauche(new \DateTime($get[0]['date_embauche']));

                $recolte->setDebutCompte(new \DateTime($debutCompte));
                $recolte->setFinCompte(new \DateTime($finCompte));

                /**
                 * recherche du pointage
                 */
                $Pointage = new Pointage($cnx);
                $liste_pointages = $Pointage->getPointages($idPersonnel, $debutCompte, $finCompte);

                $totalHeure = 0;
                $nbJourTravailles = 0;
                $dateTravailles = array();

                $supp30 = 0;
                $supp75 = 0;
                $supp100 = 0;

                //heure an'ny mpiasa alina entre 22 h à 5 h du matin
                $heureMajores = 0;

                foreach ($liste_pointages as $p) {
                    //maka ny totalina lera
                    if (preg_match("#^Supp#", $p['nom_type_pointage'])) {
                        $expDate = explode("-", $p['date_debut']);
                        //amantarana ny andro hamaritana an'ilay pourcentage
                        $jourDeLaSemaine = date("w", mktime(0, 0, 0, $expDate[1], $expDate[2], $expDate[1]));

                        $jourFerie = FALSE;

                        //raha alahady
                        if ($jourDeLaSemaine == 0) {
                            $supp75 += $p['total'];
                        } else {
                            //raha tsy jour férié
                            if ($jourFerie == FALSE) {
                                $supp30 += $p['total'];
                            } else {
                                $supp100 += $p['total'];
                            }
                        }

                    } else {
                        $totalHeure += $p['total'];
                    }

                    //manisa ny andro niasana
                    if (!in_array($p['date_debut'], $dateTravailles)) {
                        $dateTravailles[] = $p['date_debut'];
                    }
                }
                $nbJourTravailles = count($dateTravailles);

                $recolte->setHeuresTravailles($totalHeure);
                $recolte->setIndemniteRepasTransport($nbJourTravailles);
                $recolte->setHeuresSupp30($supp30);
                $recolte->setHeuresSuppNuit75($supp75);
                $recolte->setHeuresSuppDimanche100($supp100);

                $recolte->setTotalHeuresSupp($supp30 + $supp75 + $supp100);

                $recolte->setHeuresMajores($heureMajores);
            }
        }

        $form = $this->createForm(RecolteHeureType::class, $recolte, [
            "method" => "POST",
            "action" => $this->generateUrl('recolte_save'),

        ]);

        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {

        }


        return $this->render('paie/form-recolte.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function addJourFeries($paramDefaults = 0, Request $request, EntityManagerInterface $manager)
    {
        $feries = new JourFeries();
        $feries->setDate(new \DateTime());
        $message = "Donnée enregistrée";
        $newInsert = true;
        /**
         * si modification
         */
        if ($paramDefaults != 0) {
            $id_user = $request->query->get('id');
            $get = $manager->getRepository(JourFeries::class)->find($id_user);
            $feries = $get;
            $message = "Modification enregistrée";
            $newInsert = false;
            if ($feries === null) {
                throw $this->createNotFoundException('une erreur est survenue');
            }
        }

        $formBuilder = $this->createFormBuilder($feries);
        $formBuilder->add('date', DateType::class, [
            'required' => false,
            'widget' => 'single_text',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            //il faut préciser la valeur de la champ date ici
            'attr' => ['value' => $feries->getDate()->format('d/m/Y')],
            'constraints' => [new NotBlank(['message' => 'Date obligatoire'])]
        ])
            ->add('motif', TextareaType::class, [
                'required' => false,
                'constraints' => [new NotBlank(['message' => 'Motif obligatoire'])],
            ]);

        /**
         * transformer la date en string
         */
        // $formBuilder->get('date')
        //     ->addModelTransformer(new CallbackTransformer(
        //         function ($stringToDate) {

        //         },
        //         function ($dateToString) {
        //             // transform the object date to  string
        //             $dateToString = str_replace('/', '-', $dateToString);
        //             return (new \DateTime($dateToString))->format('Y-m-d');
        //         }
        //     ));

        /**
         * transform le string en date
         */
        // $formBuilder->get('date')
        //     ->addModelTransformer(new CallbackTransformer(
        //         function ($stringToDate) {

        //         },
        //         function ($stringToDate) {
        //             return new \DateTime($stringToDate);
        //         }
        //     ));

        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() and $form->isValid()) {

            $data = $form->getData();
            $feries = new JourFeries();

            if ($newInsert) {
                /**
                 * eviter d'entrer le même jour feriér lors d'un ajout
                 */
                $feries_bdd = $manager->getRepository(JourFeries::class)->findBy(['date' => $data->getDate()]);
                if (count($feries_bdd) > 0) {
                    $this->addFlash('warning', 'Ce jour feriér existe déjà');
                    return $this->redirectToRoute('add_Jour_feries');
                }
                $feries->setDate($data->getDate());
                $feries->setMotif($data->getMotif());

            } else {
                $feries = $data;
            }

            $manager->persist($feries);
            $manager->flush();

            $this->addFlash('success', $message);

            return $this->redirectToRoute('add_Jour_feries');
        }

        $list_jour_feries = $manager->getRepository(JourFeries::class)->findAllFerie();

        return $this->render('paie/jour_feries.html.twig', [
            'form' => $form->createView(),
            'jourFeries' => $list_jour_feries
        ]);
    }

    /**
     * @Security("is_granted('ROLE_RH')")
     * @param Request $request
     */
    public function removeJourFeries($id, Request $request, EntityManagerInterface $manager)
    {
        $id = (int)$id;
        $feries = $manager->getRepository(JourFeries::class)->find($id);
        if ($feries === null) {
            return new Response("Une erreur est survenue!");
        }
        $manager->remove($feries);
        $manager->flush();
        $this->addFlash('success', "Suppression effectuée avec succès");
        return $this->redirectToRoute('add_Jour_feries');

    }

    /**
     * @Security("is_granted('ROLE_RH')")
     * @param Request $request
     */
    public function lettres(Request $request, EntityManagerInterface $manager, Connection $cnx, SessionInterface $session)
    {
        $nameTemplate = null;
        $word = null;
        $operateurs = [];
        $personnel_get = null;
        $donner_pouvoir = [];
        
        $pers = new \App\Model\GPAOModels\Personnel($cnx);
        
        /**
         * personnel form select formulaire
         */
        $personnels = $pers->Get([
            "id_personnel",
            "nom",
            "prenom",
            "nom_fonction",
            "actif"
        ])->where('personnel.id_personnel > 0')
            //->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\',\'ACP 1\',\'Transmission\',\'TECH\',\'CP 1\',\'CP 2\')')
            ->orderBy('personnel.id_personnel', 'ASC')
            ->execute()->fetchAll();
        
        foreach($personnels as $data){
            $operateurs[$data["id_personnel"]." - ".$data["nom"]." ".$data["prenom"]] = $data["id_personnel"];
            if(preg_match('/Directeur/', $data["nom_fonction"])){
                if($data["actif"] == 'Oui' && $data["nom"] != "NUMERIZE"){
                    $donner_pouvoir[$data["id_personnel"]." - ".$data["nom"]." ".$data["prenom"]] = $data["id_personnel"];
                }
            }
        }
        
        $infos = [];
        
        
        $builder = $this->createFormBuilder();
        $form = $builder->add('Matricule', ChoiceType::class, [
                            'required' => false,
                            "choices" => $operateurs,
                            "placeholder" => "-Selectionnez-",
                            'constraints' => [new NotBlank(['message' => 'Matricule obligatoire'])]
                        ])
                        ->add('Choisissez', ChoiceType::class, [
                            'placeholder' => '-Selectionnez-',
                            'required' => false,
                            'constraints' => [new NotBlank(['message' => 'Type document obligatoire'])],
                            'choices' => [
                                strtoupper('Attestation d\'emploi') => 'attestation-d-emploi',
                                strtoupper('Certificat de travail') => 'certificat-de-travail',
                                strtoupper('Avertissement ecrit') => 'Avertissement ecrit',
                                strtoupper('Mise a pied') => 'mise-a-pied',
                                'PROCURATION' => 'PROCURATION',
                                strtoupper('Lettre de licenciement') => 'lettre-de-licenciement',
                                strtoupper('Rupture d\'essai') => 'rupture-d-essai',
                                //'CONTRAT DE TRAVAIL' => 'CONTRAT DE TRAVAIL',
                                //'AVENANT CONTRAT' => 'AVENANT CONTRAT',
                                'LETTRES D\'ENGAGEMENT FONCTION' => 'LETTRES D\'ENGAGEMENT FONCTION',
                                //'JUSTIFICATION DE DEPLACEMENT PROFESSIONNEL' => 'JUSTIFICATION DE DEPLACEMENT PROFESSIONNEL',
                                
                                
                            ]
                        ])
                        ->add('Date', DateType::class, [
                            'required' => false,
                            'widget' => 'single_text',
                            'html5' => false,
                            'format' => 'dd/MM/yyyy',
                            'label' => 'Date fin contrat',
                        ])
                        ->add('motif', TextareaType::class, [
                            "required" => false,
                            
                                ])
                        ->add('nbJour', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                                "required" => false,
                                ])
                        ->add('dateFinM', DateType::class, [
                            "required" => false,
                            'widget' => 'single_text',
                            'html5' => false,
                            'format' => 'dd/MM/yyyy',
                            'label' => 'Date Fin Mise à pied',
                        ])
                        ->add('sanctionAvertissement', TextareaType::class, [
                            "required" => false,
                        ])
                        ->add('intervalDateMiseAPied', TextType::class, [
                            "required" => false,
                            
                        ])
                        ->add('dateLicenciement', DateType::class, [
                            "required" => false,
                            'widget' => 'single_text',
                            'html5' => false,
                            'format' => 'dd/MM/yyyy',
                            'label' => 'Date de licenciement',
                        ])
                        ->add('donnerPouvoir', ChoiceType::class, [
                            "placeholder" => '-Selectionnez-',
                            "required" => false,
                            "choices" => $donner_pouvoir
                        ])
                        
                ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $matricule = $data['Matricule'];
            $dateFinContrat = $data['Date'];
            $donner_pouvoir = $data["donnerPouvoir"];
            /**
             * mise à pied
             */
            $motif = $data["motif"];//avertissement et licenciement
            $nbJourMiseAPied = null;
            $dateFinMiseAPied = null;
            $infos['intervalDateMiseAPied'] = null;
            
            $sanctionAvertissementEcrit = $data["sanctionAvertissement"];
            //$intervalDateMiseAPied = str_replace(' - ',' jusqu\'au ', $data["intervalDateMiseAPied"]);
            
            if(!empty($data["intervalDateMiseAPied"])){
                $intervalDateMiseAPied = str_replace(' - ',' jusqu\'au ', $data["intervalDateMiseAPied"]);
                $intervalD = explode(' - ',$data["intervalDateMiseAPied"]);
                $dateDebut = new \DateTime(implode('-',array_reverse(explode('/', $intervalD[0]))));
                $dateFin = new \DateTime(implode('-',array_reverse(explode('/', $intervalD[1]))));
                
                $nbJourMiseAPied =  $dateFin->diff($dateDebut)->format('%a') + 1;
                
                $day_next = '+ 1 day';
                //dump($intervalD[1]);
                //dd(date('w',strtotime($intervalD[1])));
                //if(date('w',strtotime($intervalD[1])) == 6){
                //    $day_next = '+ 2 days';
                //}
                $dateFinMiseAPied = new \DateTime(date('Y/m/d', strtotime(implode('-',array_reverse(explode('/', $intervalD[1]))).''.$day_next)));
                $infos["intervalDateMiseAPied"] = $intervalDateMiseAPied;
                        
            }
            /**
             * licenciement
             */
            $dateLicenciement = $data["dateLicenciement"];
            
            $word = $data['Choisissez'] . '.docx';
            /**
             * procuration si l'authentification n'est pas directeur
             */
            
            if($data["Choisissez"] == "PROCURATION" && !preg_match('/Directeur/',$this->getUser()->getUserDetails()["nom_fonction"])){
                $this->addFlash("danger", "Vous n'avez pas d'accèss");
                return $this->redirectToRoute("lettres_user");
            }
            
            $nameTemplate = $data['Choisissez'].'.html.twig';

            $pers = new Personnel($cnx);
            $personnel_get = $pers->Get(array())
                //->where('actif = :a')
                ->where('id_personnel = :b')
                //->setParameter('a', 'Oui')
                ->setParameter('b', $matricule)
                ->execute()->fetch();
            
            /**
             * procuration
             */
            $donner_pouvoir = $pers->Get(["nom","prenom","nom_fonction"])
                             ->where('id_personnel = :id')
                             ->setParameter('id', $donner_pouvoir)
                             ->execute()->fetch();
            /**
            if(!$personnel_get)
            {
                $this->addFlash('warning','Personnel introuvable ou inactif');
                return $this->redirectToRoute('lettres_user');
            }
            /**
            if(!file_exists(__DIR__.'/../../templates/paie/'.$nameTemplate)){
                $this->addFlash('warning','fichier introuvable '.$nameTemplate.". Veuillez contacter l'administrateur");
                return $this->redirectToRoute('lettres_user');
            }
             * 
             */

            $infos['template'] = $nameTemplate;
            $infos['user'] = $personnel_get;
            $infos['date'] = new \DateTime();
            $infos["dateSuppl"] = new \DateTime();
            $infos["motif"] = $motif;
            $infos["nbJourMiseAPiedOuAvertissement"] = $nbJourMiseAPied . ' jour(s)';
            $infos["dateFinMiseAPied"] = $dateFinMiseAPied;
            $infos["sanctionAvertissementEcrit"] = $sanctionAvertissementEcrit;
            $infos["dateLicenciement"] = $dateLicenciement;
            $infos["donnerPouvoir"] = $donner_pouvoir;
            
            if(!empty($dateFinContrat)){
                $infos['dateSuppl'] = $dateFinContrat;
            }
            $infos["fileName"] = $data["Choisissez"];
            
            
            $session->set('infos', $infos);
            
        }

        return $this->render('paie/lettre.html.twig', [
            'form' => $form->createView(),
            'infos' => $infos,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_RH')")
     * @param Request $request
     * @param SessionInterface $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
    public function htmlToPdf(Request $request, Pdf $pdf, SessionInterface $session){
        $infos = $session->get('infos');
        if(is_null($infos) || empty($infos)){
            $this->addFlash('warning', "Désolé ! Vous n'avez pas le droit d'accès à cette page.");
            return $this->redirectToRoute('lettres_user');
        }

        $fileName = $infos['template'];
        $fileNameTemp = explode(".", $fileName)[0];

        if(!file_exists(__DIR__.'/../../templates/paie/'.$fileName)){
            $this->addFlash('warning', "Template introuvable ". $fileName.". Veuillez contactez le responsable");
            return $this->redirectToRoute('lettres_user');
        }

        $html = $this->renderView('paie/'.$fileName, [
            'infos' => $infos
        ]);

        $pdfView = $pdf->getOutputFromHtml($html,[
            'encoding' => 'utf-8',
            'margin-top'    => 0,
            'margin-right'  => 0,
            'margin-bottom' => 0,
            'margin-left'   => 0,
        ]);
        // return new Response($pdf);
        // return new PdfResponse(
        //     $pdfView,
        //     'file.pdf'
        // );
        
        // return new Response($pdfView,200, array(
        //         'Content-Type'          => 'application/pdf',
        //         'Content-Disposition'   => 'inline; filename="'.$fileNameTemp.'.pdf"'
        //     )
        // );

        return new Response($pdfView,200, array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'attachment; filename="'.$fileNameTemp.'.pdf"'
            )
        );

        // Instantiate Dompdf with our options
        // $dompdf = new Dompdf();
        // $html = $this->renderView('paie/'.$fileName, ['user' => $session->get('utilisateur'),'date'=>$session->get('date')]);
        // Load HTML to Dompdf
        // $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        // $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        // $dompdf->render();
        /**
         * on vide tous les session
         *
        // $session->set('utilisateur', null);
        // $session->set('date', null);

        // Output the generated PDF to Browser (force download)
        // $dompdf->stream($fN.'.pdf', [
        //     "Attachment" => true
        // ]);
    }
    
    /**
     * @Route("/html/to/word", name="html_word")
     * @Security("is_granted('ROLE_RH')")
     * @param Request $request
     * @param SessionInterface $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function htmlToWord(Request $request, SessionInterface $session){
        
        /**
        $infos['template'] = $nameTemplate;
        $infos['user'] = $personnel_get;
        $infos['date'] = new \DateTime();
        $infos['dateSuppl'] = $dateSuppl;
        $session->set('infos', $infos);
         * 
         */
        
        $infos = $session->get('infos');
        if(is_null($infos) || empty($infos)){
            $this->addFlash('warning', "Désolé ! Vous n'avez pas le droit d'accès à cette page.");
            return $this->redirectToRoute('lettres_user');
        }
        
        
        $fileName = $infos['fileName'];
        /**
        strtoupper('Certificat de travail') => 'certificat-de-travail',
                                strtoupper('Attestation d\'emploi') => 'attestation-d-emploi',
                                strtoupper('Rupture d\'essai') => 'rupture-d-essai',
                                strtoupper('Lettre de licenciement') => 'lettre-de-licenciement',
                                strtoupper('Mise a pied') => 'mise-a-pied',
         * 
         */
        
        
        $fileHtmlToWord = $this->getHtml($infos, $fileName);
        
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        
        $html = $fileHtmlToWord;
        $fileNameSave = $fileName.'_'.$infos["user"]["id_personnel"];
        
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
        
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save(__DIR__.'/../../public/lettres/'.$fileNameSave.'docx');
        
        
        return $this->file(__DIR__.'/../../public/lettres/'.$fileNameSave.'docx',$fileNameSave.'.docx');
            
       

    }
   
    private function getHtml($infos, $fileName){
        
        $logoEline = __DIR__.'/../../public/images/logo-eline-small-archives.png'; //logo
        
        $fonctions =[
        "Directeur" => "Directeur",
        "Directeur Général" => "Directeur Général",
        "Directeur de Plateau" => "Directeur de Plateau",
        "Adjoint Directeur de Plateau" => "Adjoint Directeur de Plateau",
        "Responsable Personnel" =>"Responsable Personnel",
        "Developpeur Web" => "Développeur Web",
        "Transmission" => "Transmission",
        "WebDesigner" => "WebDesigner",
        "TECH" => "Technicien",
        "CP 2" => "Chef de projet",
        "CP 1" => "chef de projet", 
        "ACP 1" => "Assitant chef de projet",
        "CQ" => "CONTRELE QUALITE", 
        "CORE 1" => "Correcteur",
        "CORE 2" => "Correcteur",
        "OP 1" =>"Opérateur de saisie",
        "OP 2" => "Opérateur de saisie",
        "Logistique"=> "Logistique",
        "Securite" =>"Securite",
        "Comptable" => "Comptable"
        ];
        
        $fonction = $fonctions[$infos["user"]["nom_fonction"]];
        /**
         * civilite
         */
        $civilite = "Mr";
        if($infos["user"]["sexe"] == "FEMININ"){
            $civilite = "Mlle/Mme";
        }
        
        //formatage des variables caractere
        $newDate = date_format($infos['date'],"d/m/Y");
       //$newUser = implode(", ", $infos['user']);
       $resultNom[] = $infos['user']['nom'];
       $resultPrenom[] = $infos['user']['prenom'];
       $resultMatricule[] = $infos['user']['id_personnel'];
       $resultFonction[] = $infos['user']['nom_fonction'];
       
       $resultDateEmbauche[] = $infos['user']['date_embauche'];
       $newUserE = implode(",", $resultDateEmbauche);
       $newDateEmbauche = date("d/m/Y", strtotime($newUserE));
       
       $resultDateEmbauche[] = $infos['user']['date_embauche'];
       $resultDelivrance[] = $infos['user']['datecin'];
       $newUserDelivrance = implode(",", $resultDelivrance);
       $newDateDelivrance = date("d/m/Y", strtotime($newUserDelivrance));
       
       $resultCin[] = $infos['user']['cin'];
       
       $resultTypeContrat[] = $infos['user']['type_contrat'];
       $resultSituation[] = $infos['user']['situation_familiale'];
       $resultNomPere[] = $infos['user']['nom_pere'];
       $resultPrenomPere[] = $infos['user']['prenom_pere'];
       $resultNomMere[] = $infos['user']['nom_mere'];
       $resultPrenomMere[] = $infos['user']['prenom_mere'];
       $resultDomicile[] = $infos['user']['adresse'];
       
	   $newdonnerNomPouvoir = null; 
       $newdonnerPrenomPouvoir = null;
       $newdonnerFonctionPouvoir = null;

       if($infos['donnerPouvoir']){
            $resultdonnerNomPouvoir[] = $infos['donnerPouvoir']['nom'];
            $newdonnerNomPouvoir = implode(",", $resultdonnerNomPouvoir);
            
            $resultdonnerPrenomPouvoir[] = $infos['donnerPouvoir']['prenom'];
            $newdonnerPrenomPouvoir = implode(",", $resultdonnerPrenomPouvoir);
            
            $resultdonnerFonctionPouvoir[] = $infos['donnerPouvoir']['nom_fonction'];
            $newdonnerFonctionPouvoir = implode(",", $resultdonnerFonctionPouvoir);
       }
       /**
       $resultdonnerNomPouvoir[] = $infos['donnerPouvoir']['nom'];
       $newdonnerNomPouvoir = implode(",", $resultdonnerNomPouvoir);
       
       $resultdonnerPrenomPouvoir[] = $infos['donnerPouvoir']['prenom'];
       $newdonnerPrenomPouvoir = implode(",", $resultdonnerPrenomPouvoir);
       
       $resultdonnerFonctionPouvoir[] = $infos['donnerPouvoir']['nom_fonction'];
       $newdonnerFonctionPouvoir = implode(",", $resultdonnerFonctionPouvoir);
       **/
      
       
       $resultmotif[] = $infos['motif'];
       $resultsanctionAvertissementEcrit[] = $infos['sanctionAvertissementEcrit'];
       $newsanctionAvertissementEcrit = implode(",", $resultsanctionAvertissementEcrit);
       
       $resultnbJourMiseAPiedOuAvertissement[] = $infos['nbJourMiseAPiedOuAvertissement'];
       
       $newMotif = implode(",", $resultmotif);
       $newdateFinMiseAPiedF = null;
       if(!is_null($infos["dateFinMiseAPied"])){
            $newdateFinMiseAPiedF = $infos['dateFinMiseAPied']->format('d/m/Y');
       }
        //$newdateFinMiseAPied = implode(",", $resultdateFinMiseAPied);
        //$newdateFinMiseAPiedF = date("d/m/Y", strtotime($resultdateFinMiseAPied));
       //$result = $date->format($newdateFinMiseAPied);
       
     
       //$newdateFinMiseAPiedN = implode(",", $resultdateFinMiseAPied);
       //$newdateFinMiseAPiedF = date("d/m/Y", strtotime($newdateFinMiseAPiedN));
       
       
       $newnbJourMiseAPiedOuAvertissement = implode(",", $resultnbJourMiseAPiedOuAvertissement);
       
       
       
       
       //Contrat de travail
       $resultDateNaissance[] = $infos['user']['date_naissance'];
       $newUserN = implode(",", $resultDateNaissance);
       $newDateNaissanceF = date("d/m/Y", strtotime($newUserN));
       
       $resultDateLicencement[] = $infos['dateLicenciement']->format("d/m/Y");
       $newdaDateLicencement = implode(",", $resultDateLicencement);
       
        //$newDateLicencementF = date("d/m/Y", strtotime($newdaDateLicencement));
        //dd($newDateLicencementF);
       
        
        $resultintervalDateMiseAPied[] = $infos['intervalDateMiseAPied'];
       $newintervalDateMiseAPied = implode(",", $resultintervalDateMiseAPied);
        //$newintervalDateMiseAPiedF = date("d/m/Y", strtotime($newintervalDateMiseAPied));
       
       
       
      

       $newUserNom = implode(",", $resultNom);
       $newUserPrenom = implode(",", $resultPrenom);
       $newUserMatricule = implode(",", $resultMatricule);
       $newUserFonction = implode(",", $resultFonction);
       
       
       $newTypeContrat = implode(",",$resultTypeContrat);
       $newSituation_familiale = implode(",",$resultSituation);
       
       $newNomPere = implode(",",$resultNomPere);
       $newPrenomPere = implode(",",$resultPrenomPere);
       
       $newNomMere = implode(",",$resultNomMere);
       $newPrenomMere = implode(",",$resultPrenomMere);
       
       $newDomicile = implode(",",$resultDomicile);
       $newCin = implode(",",$resultCin);
       
       $newDelivreCin = implode(",",$resultDelivrance);
       //dd($infos);


       //attestation josé
       //$resultFinContrat[] = $infos['user']['date_debauche'];
       //$newFinContrat = implode(",",$resultFinContrat);
       $newFinContrat = $infos["dateSuppl"]->format("d/m/Y");
       
        $choiceFile = [
            "attestation-d-emploi" => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>

                                            <div>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            </div>
                                            <div>
                                            <p>
                                                <span><strong>ATTESTATION D’EMPLOI</strong></span>
                                            </p>
                                            </div><p>
                                            <br/>               Nous, soussignés, ELINE SOFT AND DATA attestons que :<br/><br/>'.$civilite.' <strong>'.$newUserNom.' '.$newUserPrenom.'</strong> matricule <strong>'.$newUserMatricule.'</strong> est employé(e) dans notre société comme <strong>'.$fonction.'</strong> depuis le <strong>'.$newDateEmbauche.'</strong> jusqu’à ce jour dans le cadre d’un <strong>contrat à durée indéterminée</strong>.                                            
                                            </p><p>
                                            <br/>               En foi de quoi, cette attestation lui est délivrée pour servir et faire valoir ce que de droit.                                            
                                            </p>
                                            <div>
                                            <p style="text-align:right;">
                                                        <span style="text-align:right;"><strong>La Direction</strong></span>
                                            </p>
                                            </div>
                                        </body>
                                    </html>',

            "certificat-de-travail" => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>

                                            <div>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            </div>
                                            <div>
                                            <p>
                                                <span><strong>CERTIFICAT DE TRAVAIL</strong></span>
                                            </p>
                                            </div><p>
                                            <br/>            Nous soussignés ELINE SOFT AND DATA certifions que :<br/><br/>            '.$civilite.' <strong>'.$newUserNom.' '.$newUserPrenom.'</strong>
 matricule <strong>'.$newUserMatricule.'</strong> a été employé(e) dans notre société comme <strong>'.$fonction.'</strong> depuis le <strong>'.$newDateEmbauche.'</strong> jusqu’au <strong>'.$newFinContrat.'</strong> dans le cadre d’un <strong>contrat à durée indéterminée</strong>.                                            
                                            </p><p>
                                            <br/>            En foi de quoi, ce certificat lui est délivré pour servir et faire valoir ce que de droit.                                             
                                            </p>
                                            <div>
                                            <p style="text-align:right;">
                                                <span style="text-align:right;"><strong>La Direction</strong></span>
                                            </p>
                                            </div>
                                        </body>
                                    </html>',
            "Avertissement ecrit" => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            <div>
                                            <p>
                                                <span><strong>AVERTISSEMENT ECRIT </strong></span><br/>
                                                au matricule <strong>'.$newUserMatricule.' '.$newUserNom.' '.$newUserPrenom.'</strong>
                                            </p>
                                            </div><p>
                                            <br/><br/>              Vous avez été surpris par un de nos chefs hiérarchiques pour les motifs suivants : 
                                            <br/><br/>- <strong>'.$newMotif.'</strong>
                                            <br/><br/>              Nous avons donc le regret de vous informer que nous vous infligeons un <strong>avertissement écrit</strong> valable pendant <strong>6 mois</strong>. 
                                            <br/><br/>              Nous espérons que vous prendrez la juste mesure de cet avertissement écrit et que nous n’aurons plus à exprimer des plaintes à votre égard.                                            
                                            <br/><br/>              A la prochaine faute, une mesure plus lourde sera prise.
                                            <br/><br/>              Veuillez agréer, Madame  Monsieur, l\'expression de nos salutations distinguées.<br/><br/><br/><br/>               <span><strong>L’employé(e)</strong></span><span style="margin-left:1500px">                                                                            <strong>La Direction</strong></span> 
                                            </p>
                                            <div>
                                            </div>
                                        </body>
                                    </html>',
            'lettre-de-licenciement' => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            <div>
                                            <p>
                                                <span><strong>LETTRE DE LICENCIEMENT </strong></span><br/>
                                                au matricule <strong>'.$newUserMatricule.' '.$newUserNom.' '.$newUserPrenom.'</strong>
                                            </p>
                                            </div><p>
                                            <br/><br/>                  Après les évaluations de vos comportements et vos compétences au sein de la société, nous sommes obligés de rompre votre contrat et vos engagements envers la société les motifs suivants :  
                                            <br/><br/> <strong>'.$newMotif.'</strong> en date du <strong>'.$newdaDateLicencement.'</strong>
                                            <br/><br/>                  Vue que vous ne respectez plus les règlements internes de la société, nous sommes obligés de procéder à ce rupture de contrat.
                                            <br/><br/>                  Ce licenciement sans préavis prend effet à partir de la date de réception de cette lettre et le paiement de votre solde de tout compte dépendra du délai de transfert d’argent en provenance de notre siège à l’étranger. La Direction vous contactera pour le paiement.  
                                            <br/><br/>                  Veuillez agréer, Madame  Monsieur, l\'expression de nos salutations distinguées.<br/><br/><br/><br/>             <span><strong>L’employé(e)</strong></span><span style="margin-left:500px">                                                                           <strong>La Direction</strong></span> 
                                            </p>
                                            <div>
                                            </div>
                                        </body>
                                    </html>',
            'rupture-d-essai' => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            <div>
                                            <p>
                                                <span><strong>RUPTURE PERIODE D’ESSAI NON CONCLUANT </strong></span><br/>
                                                Matricule <strong>'.$newUserMatricule.'</strong><br/>
                                                NOM ET PRENOMS  <strong>'.$newUserNom.' '.$newUserPrenom.'</strong>
                                            </p>
                                            </div><p>
                                            <br/><br/>              Après l’évaluation effectuée sur vous, nous sommes obligés de rompre votre engagement avec la société pour le motif suivant : 
                                            <br/><br/> - <strong>'.$newMotif.'</strong>
                                            <br/><br/>              Ce licenciement sans préavis prend effet à partir de la date de réception de cette lettre et le paiement de votre solde de tout compte par virement bancaire dépendra du délai de transfert d’argent en provenance de notre siège  à l’étranger. La Direction vous contactera pour le paiement.  
                                            <br/><br/>              Veuillez agréer, Madame  Monsieur, l\'expression de nos salutations distinguées.<br/><br/><br/><br/>              <span><strong>L’employé(e)</strong></span><span style="margin-left:700px">                                                                   <strong>La Direction</strong></span> 
                                            </p>
                                            <div>
                                            </div>
                                        </body>
                                    </html>',
            'mise-a-pied' => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            <div>
                                            <p style="text-align:center;">
                                            <br/>
                                            <strong><br/> MISE A PIED au matricule: '.$newUserMatricule.' '.$newUserNom.' '.$newUserPrenom.'</strong>
                                            </p>
                                            </div><p>
                                            <br/><br/>              Vous avez été constaté et remarqué par un de vos chefs hiérarchiques sur vos fautes dans le travail :  
                                            <br/><br/> <strong>'.$newMotif.'</strong>
                                            <br/><br/>              Nous avons donc le regret de vous informer que nous vous infligeons une mise à pied <strong>'.$newnbJourMiseAPiedOuAvertissement.'</strong> qui sera déduit de votre salaire le <strong>'.$newintervalDateMiseAPied.'</strong>  
                                            <br/><br/>              Vous reprendrez votre poste le <strong>'.$newdateFinMiseAPiedF.'</strong> 
                                            <br/><br/>              À la prochaine faute, une mesure de licenciement sera prise.
                                            <br/><br/>              Nous souhaitons une amélioration de votre comportement et travail au sein de la société.
                                            <br/><br/>              Veuillez agréer, Madame  Monsieur, l\'expression de nos salutations distinguées.<br/><br/><br/><br/>              <span><strong>L’employé(e)</strong></span><span style="margin-left:700px">                                                                   <strong>La Direction</strong></span> 
                                            </p>
                                            <div>
                                            </div>
                                        </body>
                                    </html>',
            'CONTRAT DE TRAVAIL' => '<html>
                                        <body>
                                            <p>
                                                <span><strong>CONTRAT DE TRAVAIL N° '.$newUserMatricule.'  </strong></span><br/>
                                            </p>
                                            <p>
                                            <br/><strong>ENTRE LES SOUSSIGNES</strong><br/><br/> La Société Eline Soft and Data, dont le siège social est situé Lot IAD 64C Ambohidrapeto, représentée par Monsieur Tolotra ANDRIATSIOHARAMANA Gérant et Monsieur Rija RAKOTOMALALA Co-gérant, désigné ci-après comme :  l’EMPLOYEUR ,<br/><br/><strong>D’UNE PART,</strong><br/><br/>ET :<br/><br/>Civilité : Mme / Mr<br/>Nom :'.$newUserNom.'<br/>Prénom usuel : '.$newUserPrenom.'<br/>Né(e) le : '.$newDateNaissanceF.'<br/>Situation familiale  : '.$newSituation_familiale.'<br/>Fils (fille) de  : '.$newNomPere.' '.$newPrenomPere.' et de '.$newNomMere.' '.$newPrenomMere.'<br/>Domicile  : '.$newDomicile.'<br/>CIN  : '.$newCin.'<br/>Délivré le  : '.$newDateDelivrance.'<br/><br/>Désigné comme “Le salarié(e)”<br/><br/><strong>D’AUTRE PART,</strong><br/><br/>IL A ETE CONVENU ET ETABLI COMME SUIT :<br/><br/><strong><u><i>ARTICLE 1</i></u> : ENGAGEMENT</strong><br/><br/>La Société ELINE SOFT AND DATA recrute  Mme / Mr '.$newUserNom.' '.$newUserPrenom.' Tsy Resy qui accepte et déclare libre de tout engagement en qualité '.$fonction.', catégorie socio professionnelle OP1A, à son siège Social à Antananarivo.
                                            </p>
                                            <p>
                                            <br/><strong>ARTICLE 2 : NATURE</strong><br/><br/> Le présent contrat est établi pour une période indéterminée et prend effet à compter du11/04/2019 
                                            </p>
                                            <div>
                                            </div>
                                            <p>
                                            <span><strong>L’employé(e)</strong></span>
                                                    <span style="margin-left:100px"><strong>        La Direction</strong></span>
                                            </p>
                                            <div>
                                            </div>
                                        </body>
                                    </html>',
            'AVENANT CONTRAT' => '',
            'LETTRES D\'ENGAGEMENT FONCTION' => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            <div>
                                            <p>
                                                <span><strong>LETTRE D’ENGAGEMENT</strong></span>
                                            </p>
                                            </div><p>
                                            <br/><br/>          Je, soussigné(e),<br/><br/>Nom et prénom <strong>'.$newUserNom.' '.$newUserPrenom.'</strong>
                                            <br/><br/>Matricule <strong>'.$newUserMatricule.'</strong>
                                            
                                            <br/><br/>          M’engage à respecter les règles et procédures mentionnées ci-dessous pour la prise de fonction <strong>« '.$fonction.' »</strong>, à savoir :<br/>
-	De respecter le secret professionnel liant à la tâche exercée au sein de la société.<br/>
-	A bien respecter les consignes données lors de la formation.<br/>
-	De ne divulguer sous aucun prétexte, les contacts ni les contenus des courriels ni toute autre information en dehors du cadre qui a été indiqué lors de la formation.<br/>
-	De ne pas utiliser l’adresse de messagerie en dehors de l’enceinte de la société, ni les autres applications ou site web relative à l’exercice de la tâche.<br/><br/>           Le non respect de cette clause de confidentialité peut entrainer une sanction ou une rupture de contrat.
                                            <br/><br/><br/><br/>        <span><strong>L’employé(e)</strong></span><span style="margin-left:500px">                                                                           <strong>La Direction</strong></span> 
                                            </p>
                                            <div>
                                            </div>
                                        </body>
                                    </html>',
            
            'JUSTIFICATION DE DEPLACEMENT PROFESSIONNEL' => '',
            'PROCURATION' => '<html>
                                        <body>
                                            <img src="'.$logoEline.'"/><p>
                                            <br/> 21 Rue Andriambelomasina<br/> Immeuble Akany Rainimamonjy<br/> Amparibe Antananarivo 101<br/> NIF : 4001693087<br/> STAT : 63113120114010603<br/> RCS : 2014B00553<br/> Tél : +261 34 90 746 05<br/>
                                            </p>
                                            <p style="text-align:right;">
                                                Antananarivo, le <strong>'.$newDate.'</strong>
                                            </p>
                                            <div>
                                            </div><p>
                                            <br/><br/>      <strong>Objet: Procuration</strong><br/><br/>Madame, Monsieur 
                                            <br/><br/>              Nous soussignés, ELINE SOFT AND DATA, autorise <strong>'.$civilite.' '.$newUserNom.' '.$newUserPrenom.'</strong>, '.$fonction.' au sein de notre société, à se présenter en notre nom auprès de votre service.
                                            <br/><br/>              Veuillez agréer, Madame  Monsieur, nos sincères salutations.<br/><br/>
                                            <br/><br/><span><strong> Bon pour pouvoir </strong></span><span style="margin-left:1500px">                                                     <strong>Lu et accepte le pouvoir</strong></span> 
                                            </p>
                                            <br/><br/>              <strong>'.$newdonnerNomPouvoir.' '.$newdonnerPrenomPouvoir.'  </strong><strong>'.$newdonnerFonctionPouvoir.'</strong>
                                            <div>
                                            </div>
                                        </body>
                                    </html>',
        ];
      
        $fileHtmlToWord = $choiceFile[$fileName];
        return $fileHtmlToWord;
        
    }
}
