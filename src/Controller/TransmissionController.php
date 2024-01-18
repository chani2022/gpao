<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\Transmission;
use App\Entity\LectureTransmission;
use App\Entity\TransmissionPieceJointe;
use App\Entity\Dossier;
use App\Model\GPAOModels\Personnel;
use App\Form\TransmissionType;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TransmissionController extends AbstractController
{

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function index(Connection $cnx, Request $req, PaginatorInterface $paginator, $BoiteEnvoieOnly = FALSE)
    {
        $dateDebutQuery = "";
        $dateFinQuery = "";

        //$session = new Session();
        //$session->set("keys_rapide", "");

        $pers = new Personnel($cnx);
        $id_user = $this->getUser()->getUserDetails()['id_personnel'];

        $personnel_get = $pers->Get(array("id_personnel, nom, prenom,login, photo, nom_fonction"))
            ->where('actif = :a and id_personnel> :id and id_personnel != :id_user')
            ->setParameter('a', 'Oui')
            ->setParameter('id', 0)
            ->setParameter('id_user', $id_user)
            ->orderBy('id_personnel', 'ASC')
            ->execute()
            ->fetchAll();

        $session = new Session();
        $session->set('list_user', $personnel_get);
        $session->set('me', $id_user);
        $session->set('keys_rapide', "");

        $Trans = new Transmission();

        $em = $this->getDoctrine()->getManager();

        $messages = [];

        $keywords = $req->request->get('keywords');
        $date = $req->request->get('date');

        $sansReponses = $req->request->get('sansReponses');

        $expediteur_id = NULL;

        //recherche boite envoie seulement
        if ($BoiteEnvoieOnly === TRUE) {
            $expediteur_id = $this->getUser()->getUserDetails()['id_personnel'];
        }

        if (is_null($keywords) && is_null($date)) {

            $messages = $em->getRepository(Transmission::class)
                ->getMessageEntrant($this->getUser()->getUserDetails()['id_personnel'], NULL, NULL, NULL, NULL, $expediteur_id, $sansReponses);
        } else {
            $dt = explode(" - ", $date);
            $dateDebut = NULL;
            $dateFin = NULL;

            if (count($dt) > 1) {
                $dateDebutQuery = $dt[0];
                $dateFinQuery = $dt[1];

                try {

                    $dD = new \DateTime(str_replace("/", "-", $dt[0]));
                    $dateDebut = $dD->format("Y-m-d");
                } catch (\Exception $ex) {
                }

                try {

                    $dD = new \DateTime(str_replace("/", "-", $dt[1]));
                    $dateFin = $dD->format("Y-m-d");
                } catch (\Exception $ex) {
                }
            }
            $messages = $em->getRepository(Transmission::class)
                ->getMessageEntrant($this->getUser()->getUserDetails()['id_personnel'], NULL, $dateDebut, $dateFin, $keywords, $expediteur_id);
        }




        $messagePaginated = $paginator->paginate(
            $messages,
            $req->query->getInt('page', 1),
            50
        );

        // si boite d'envoie
        if ($BoiteEnvoieOnly === TRUE) {
            return $this->render('transmission/boite_envoie.html.twig', [
                "messages" => $messages,
                "allUsers" => $pers->getTransmissionUsers(),
                "dateDebut" => $dateDebutQuery,
                "dateFin" => $dateFinQuery,
                "messagePaginated" => $messagePaginated,
            ]);
        }

        /**
         * message non lu
         */
        $messagesNonLu = $em->getRepository(Transmission::class)
            ->getMessageEntrant($this->getUser()->getUserDetails()['id_personnel'], FALSE);

        // dd($pers->getTransmissionUsers());
        return $this->render('transmission/index.html.twig', [
            "messages" => $messages,
            "allUsers" => $pers->getTransmissionUsers(),
            "dateDebut" => $dateDebutQuery,
            "dateFin" => $dateFinQuery,
            "messagePaginated" => $messagePaginated,
            "nbMsgNonLu" => count($messagesNonLu),
            "boiteEnvoie" => $BoiteEnvoieOnly,
            //"me"=>$id_user
        ]);
    }



    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function boiteEnvoie(Connection $cnx, Request $req, PaginatorInterface $paginator)
    {
        return $this->index($cnx, $req, $paginator, TRUE);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function readMessage(Transmission $Trans, Connection $cnx)
    {

        $pers = new Personnel($cnx);
        $em = $this->getDoctrine()->getManager();

        $getLecture = $em->getRepository(LectureTransmission::class)->findOneBy([
            "destinataire" => $this->getUser()->getUserDetails()['id_personnel'],
            "transmission" => $Trans->getId()
        ]);

        if (!$getLecture) {
            $lecture = new LectureTransmission();
            $lecture->setDestinataire($this->getUser()->getUserDetails()['id_personnel']);
            $lecture->setTransmission($Trans);
            //$Trans->setLu(TRUE);

            $em->persist($lecture);
            $em->flush();
        }

        return $this->render('transmission/read.html.twig', [
            "messages" => $Trans,
            "allUsers" => $pers->getTransmissionUsers(),
            "dirPiece" => $this->getParameter('app.piece_dir')
        ]);
    }

    /**
     * téléchargement piece
     * @param TransmissionPieceJointe $piece
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function downloadPiece(TransmissionPieceJointe $piece)
    {
        $dirPiece = $this->getParameter('app.piece_dir');

        return $this->file($dirPiece . "/" . $piece->getNomPiece(), $piece->getNomOrigine());
    }

    /**
     * Suppression
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function deleteTransmission(Transmission $Trans)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($Trans);
        $em->flush();

        $this->addFlash('success', "Message suprimé");
        return $this->redirectToRoute('transmission_index');
    }


    public function getMyUnreadMessage()
    {
        $em = $this->getDoctrine()->getManager();
        $messages = [];

        $messages = $em->getRepository(Transmission::class)
            ->getMessageEntrant($this->getUser()->getUserDetails()['id_personnel'], FALSE);

        $msg = "Vous n'avez aucun nouveau message";

        if (count($messages) > 0) {
            $msg = "Vous avez " . count($messages) . " nouveaux messages";
            if (count($messages) == 1) {
                $msg = str_replace("nouveaux", "nouveau", $msg);
                $msg = str_replace("messages", "message", $msg);
            }
        }

        return $this->render('transmission/notif.html.twig', [
            "msg" => $msg,
            "messages" => $messages
        ]);
    }

    public function receivePiece(Request $req)
    {
        $fichier = $req->files->get('piece');


        if (!is_null($fichier)) {
            $dirDest = $this->getParameter('app.piece_dir');

            $fileName = time() . '-' . uniqid() . '.' . $fichier->guessExtension();

            try {
                $fichier->move($dirDest, $fileName);
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $ex) {
                return new Response("KO");
            }

            $piece = new TransmissionPieceJointe();

            $piece->setNomPiece($fileName);
            $piece->setNomOrigine($fichier->getClientOriginalName());

            $em = $this->getDoctrine()->getManager();
            $em->persist($piece);
            $em->flush();

            $rs = '<input type="hidden" name="pieces[]" value="' . $piece->getId() . '">';

            return new Response($rs);
        }

        return new Response("KO");
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function envoie(Request $req, $writeForFolder = 0, $idTransmission = 0)
    {

        $Trans = new Transmission();


        $em = $this->getDoctrine()->getManager();

        $dossierForReply = "";
        $jsDossiers = "";
        $jsPredfSelect = "";

        //maka an'ilay transmission ho modifiena
        if ($idTransmission != 0) {
            $getIt = $em->getRepository(Transmission::class)->find($idTransmission);
            if ($getIt) {
                $Trans = $getIt;

                $allDest = $getIt->getDestinataires();


                for ($i = 0; $i < count($allDest); $i++) {
                    $allDest[$i] = "'" . $allDest[$i] . "'";
                }

                $jsPredfSelect = '$("select.destinataire").val([' .  implode(",", $allDest) . ']).trigger("change");';

                if (!is_null($Trans->getDossier())) {
                    $dossierForReply = $Trans->getDossier();
                }
            }
        }


        $replyTo = $req->query->get('replyTo');

        $msgToReply = NULL;

        $listeDossier = $em->getRepository(Dossier::class)->findBy([], ["nom_dossier" => "ASC"]);

        $action = $this->generateUrl('transmission_envoie', [
            "writeForFolder" => $writeForFolder,
            "idTransmission" => $idTransmission,
        ]);

        if (!is_null($replyTo)) {
            $msg = $em->getRepository(Transmission::class)->find($replyTo);

            if ($msg) {
                $Trans->setDestinataires($msg->getDestinataires());
                $Trans->setObjet("Re : " . $msg->getObjet());

                $Trans->setDossier($msg->getDossier());

                $dossierForReply = $msg->getDossier();


                $allDest = ["|" . $msg->getExpediteur() . "|"];
                //$allDest = $msg->getDestinataires();


                for ($i = 0; $i < count($allDest); $i++) {
                    $allDest[$i] = "'" . $allDest[$i] . "'";
                }

                $jsPredfSelect = '$("select.destinataire").val([' . implode(",", $allDest) . ']).trigger("change");';
            }

            $action = $this->generateUrl('transmission_envoie', [
                "writeForFolder" => $writeForFolder,
                "idTransmission" => $idTransmission

            ]) . "?replyTo=" . $replyTo;
        } else {
            //si un dossier a été spécifié pour rédiger
            if ($writeForFolder != 0) {
                $doss = $em->getRepository(Dossier::class)->find($writeForFolder);
                if ($doss) {
                    $dossierForReply = $doss;
                }
            }
        }


        $form = $this->createForm(TransmissionType::class, $Trans, [
            "method" => "POST",
            "action" => $action,
            "attr" => [
                "enctype" => "multipart/form-data"
            ]
        ]);



        $dossier = $req->request->get('dossiers');

        if ($dossier != "") {
            $jsDossiers = '$("select.dossiers").val(["' . $dossier . '"]).trigger("change");';
        }
        //form handling
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {

            $dest = $req->request->get("destinataires");

            $pieces = $req->request->get('pieces');

            $replyTo = $req->request->get('replyTo');

            $mailClient = $req->request->get('mailClient');

            $mailNavette = $req->request->get('mailNavette');

            $dateReel = $req->request->get('dateReel');

            try {
                $Trans->setDateReelReception(new \DateTime(date($dateReel)));
            } catch (\Exception $ex) {
            }

            if (!is_null($mailNavette)) {
                $Trans->setMailNavette($mailNavette);
            } else {
                $Trans->setMailNavette(0);
            }

            if (!is_null($mailClient)) {
                $Trans->setMailClient($mailClient);
                //les mails venant de l'exterieur sont toujours a sauvegarder
                $Trans->setMailNavette($mailClient);
            } else {
                $Trans->setMailClient(0);
            }


            if (is_null($dest)) {
                $this->addFlash("danger", "Veuillez séléctionner le ou les destinataires");
            } else {

                date_default_timezone_set("Indian/Antananarivo");


                //on ne change pas les destinataires et l'expediteur si modif
                if ($idTransmission == 0) {
                    $Trans->setDateEnvoie(new \DateTime(date("Y-m-d H:i:s")));
                    $Trans->setDestinataires($dest);
                    $Trans->setExpediteur($this->getUser()->getUserDetails()['id_personnel']);
                }

                //ajout des pieces relies
                if (!is_null($pieces) && is_array($pieces)) {
                    $emP = $em->getRepository(TransmissionPieceJointe::class);

                    foreach ($pieces as $p) {
                        $obj = $emP->find($p);
                        if ($obj) {
                            $Trans->addPiece($obj);
                        }
                    }
                }

                // si réponse
                if (!is_null($replyTo) && $replyTo != "") {
                    $msg = $em->getRepository(Transmission::class)->find($replyTo);
                    if ($msg) {
                        $msg->addReponse($Trans);
                    }
                } else {
                    if (!is_null($dossier) && $dossier !== "") {
                        $doss = $em->getRepository(Dossier::class)->find($dossier);
                        if ($doss) {
                            $Trans->setDossier($doss);
                        }
                    }
                }
                //si modification
                if ($idTransmission == 0) {
                    $em->persist($Trans);
                    $this->addFlash("success", "Votre message a été bien envoyé");
                } else {
                    $this->addFlash("success", "Modification effectuée");
                }

                $em->flush();


                return $this->redirectToRoute('transmission_envoie');
            }
        }
        //chargement destinataire par javascript après chargement page suite de non validation du formulaire
        $dest = $req->request->get("destinataires");

        if (is_array($dest)) {
            if (count($dest) > 0) {
                $allDest = $dest;
                for ($i = 0; $i < count($allDest); $i++) {
                    $allDest[$i] = "'" . $allDest[$i] . "'";
                }

                $jsPredfSelect = '$("select.destinataire").val([' .  implode(",", $allDest) . ']).trigger("change");';
            }
        }




        return $this->render('transmission/envoie.html.twig', [
            "form" => $form->createView(),
            "replyTo" => $replyTo,
            "jsPredefSelect" => $jsPredfSelect,
            "jsDossiers" => $jsDossiers,
            "liste_dossier" => $listeDossier,
            "dossierForReply" => $dossierForReply,
            "idTransmission" => $idTransmission,
            "transObj" => $Trans
        ]);
    }

    /**
     * Chargement du message à répondre
     * @param Connection $cnx
     * @param type $replyTo
     * @return Response
     */
    public function loadMessageToReply(Connection $cnx, $replyTo)
    {
        $pers = new Personnel($cnx);

        $em = $this->getDoctrine()->getManager();

        $msg = $em->getRepository(Transmission::class)->find($replyTo);

        if (!$msg) {
            return new Response("");
        } else {
            return $this->render('transmission/msg_to_reply.twig', [
                "messages" => $msg,
                "allUsers" => $pers->getTransmissionUsers()
            ]);
        }
    }

    public function loadDestinataires(Connection $cnx)
    {
        $pers = new Personnel($cnx);

        $data = $pers->Get(array('id_personnel', 'nom', 'prenom', 'nom_fonction', 'login'))
            ->where('actif = :a and id_personnel> :id')
            ->setParameter('a', 'Oui')
            ->setParameter('id', 0)

            ->andWhere('id_personnel != :me')
            ->setParameter('me', $this->getUser()->getUserDetails()['id_personnel'])

            //liste des fonctions qui ne sont pas concernees pour les navettes
            ->andWhere('nom_fonction NOT LIKE :fonc')
            ->setParameter('fonc', 'ACP%')
            ->andWhere('nom_fonction NOT LIKE :fonc2')
            ->setParameter('fonc2', 'TECH%')
            ->andWhere('nom_fonction NOT LIKE :fonc3')
            ->setParameter('fonc3', '%Web%')
            ->andWhere('nom_fonction NOT LIKE :fonc4')
            ->setParameter('fonc4', 'Compt%')
            ->andWhere('nom_fonction NOT LIKE :fonc5')
            ->setParameter('fonc5', 'COR%')
            ->andWhere('nom_fonction NOT LIKE :fonc6')
            ->setParameter('fonc6', 'OP%')

            ->andWhere('nom_privilege IN (:p,:ps)')
            ->setParameter('p', 'admin')
            ->setParameter('ps', 'superadmin')
            ->orderBy('id_personnel', 'ASC')
            ->execute()->fetchAll();

        if (count($data) == 0) {
            return new Response("0");
        } else {
            $rs = [];
            foreach ($data as $v) {
                $rs[] = '<option value="|' . $v['id_personnel'] . '|">' . ucfirst($v['login']) . ' (' . $v['nom_fonction'] . ')</option>';
            }
            return new Response(implode("", $rs));
        }
    }


    public function markRead(Request $request)
    {
        $ids = $request->request->get('ids');

        $em = $this->getDoctrine()->getManager();

        $Ok = [];
        foreach ($ids as $id) {
            $Trans = $em->getRepository(Transmission::class)->find($id);

            if ($Trans) {
                $getLecture = $em->getRepository(LectureTransmission::class)->findOneBy([
                    "destinataire" => $this->getUser()->getUserDetails()['id_personnel'],
                    "transmission" => $Trans->getId()
                ]);

                if (!$getLecture) {
                    $lecture = new LectureTransmission();
                    $lecture->setDestinataire($this->getUser()->getUserDetails()['id_personnel']);
                    $lecture->setTransmission($Trans);
                    $em->persist($lecture);

                    $Ok[] = $id;
                }
            }
        }

        if (count($Ok) > 0) {
            $em->flush();
            $this->addFlash("success", count($Ok) . " messages ont été marqués comme lu");
        }

        return new JsonResponse($Ok);
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     * @param Request $req
     */
    public function rechercheRapide(Request $req)
    {

        $kwd = $req->request->get('kwd_recherche_rapide');

        $listeDossier = [];
        $listeTrans = [];
        if (!is_null($kwd) && $kwd != "") {

            $kwd = trim($kwd);
            $em = $this->getDoctrine()->getManager();

            $listeDossier = $em->getRepository(Dossier::class)->search($kwd);
            $listeTrans = $em->getRepository(Transmission::class)->getMessagesNavette(["keywords" => $kwd]);
        } else {
            $this->addFlash('primary', "Veuillez écrire ce que vous voulez chercher !!");
        }

        return $this->render('transmission/recherche_rapide.html.twig', [
            "liste_dossier" => $listeDossier,
            "liste_trans" => $listeTrans
        ]);
    }

    public function fast_search(Request $request, \Doctrine\ORM\EntityManagerInterface $entityManager, PaginatorInterface $paginator, \Symfony\Component\HttpFoundation\Session\SessionInterface $session): Response
    {

        $isTextSearch = false; //ty miala @le vao2
        if ($request->request->get("fast_search") == "") {
            $this->addFlash("danger", "Veuillez remplir le champ de recherche");
            return $this->redirectToRoute("transmission_index");
        }

        if ($session->get("keys_rapide") == "") {
            $session->set("keys_rapide", $request->request->get("fast_search"));
        } else {
            if ($session->get("keys_rapide") != $request->request->get("fast_search")) {
                $session->set("keys_rapide", $request->request->get("fast_search"));
            }
        }
        $keys = $session->get("keys_rapide");
        $checkText = $request->request->get("check_text"); //ty miala @le vao2
        /**
         * reto ku miala @le vao2
         */
        if (!is_null($checkText)) {
            $isTextSearch = true;
        }
        //--farany--
        $qb = $entityManager->createQueryBuilder();
        $qb->select(["trans", "dos"])
            ->from(Dossier::class, "dos")
            //->from(Transmission::class, "trans")
            //->innerJoin("trans.dossier", "dos");
            //->leftJoin("trans.dossier", "dos");
            ->leftJoin("dos.transmissions", "trans");
        //->addSelect("dos");

        $whereQuery = "trans.contenu LIKE :contenu OR trans.objet LIKE :contenu"; //query where
        //$whereQuery = "trans.contenu LIKE :contenu OR trans.objet LIKE :contenu OR dos.nom_dossier LIKE :contenu OR dos.nom_mairie LIKE :contenu" ito le vao2
        /**
         * si PAS coche, on recherche les nom du dossier, nom mairie
         * ty miala @le vao2
         */
        if (!$isTextSearch) {
            $whereQuery = "dos.nom_dossier LIKE :contenu OR dos.nom_mairie LIKE :contenu";
            $keys = strtoupper($keys);
            //$session->set("keys_rapide", strtoupper($keys));
        }
        $query = $qb->where($whereQuery)
            ->setParameter("contenu", "%" . $keys . "%")
            //->setParameter("contenu", strtoupper($session->get("keys_rapide")))
            ->orderBy("dos.nom_dossier", "ASC")
            ->getQuery();
        $dossiers = $query->getResult();
        //dump($dossiers);
        $data = [];
        //$data_dossiers = []; ty vao2
        //$data_email = []; ty vao2
        $total = 0;
        //$total_dossiers = 0;ito ny vao2
        //$total_email = 0;ito ny vao2
        //dd($transmissions);

        foreach ($dossiers as $dossier) {
            if (!$isTextSearch) { //ty miala @le vao2
                //if(!array_key_exists($dossier->getNomDossier(), $data)){
                //$total_dossiers += 1;
                $data[$dossier->getNomDossier()] = [
                    "cdc" => $dossier->getCdc()->getNomCdc(),
                    "date_ajout" => $dossier->getDateAjout(),
                    "nom_mairie" => $dossier->getNomMairie(),
                    "nombre_echanges" => count($dossier->getTransmissions()->getValues()),
                    "id_dossier" => $dossier->getId()
                ];
                /**
                 * ty vao2 raha ita ao @le nom du dossier ilay recherche
                 * dia ze vo apetrak ao anatin le dossier
                 *
                    if(preg_match("#".$keys."#", $dossier->getNomDossier())){
                      $total_dossiers += 1;
                        $data_dossiers[] = [
                          "nom_dossier"  => $dossier->getNomDossier(),
                          "cdc" => $dossier->getCdc()->getNomCdc(),
                          "date_ajout" => $dossier->getDateAjout(),
                          "nom_mairie" => $dossier->getNomMairie(),
                          "nombre_echanges" => count($dossier->getTransmissions()->getValues()),
                          "id_dossier" => $dossier->getId()
                        ];
                    }
                 *ato ndre ny mail misy ilay recherche
                 * 
                    foreach($dossier->getTransmissions()->getValues() as $transmission){
                        $data_email[] = [
                                "nom_dossier" => $dossier->getNomDossier(),
                                "nom_mairie" => $dossier->getNomMairie(),
                                "objet" => $transmission->getObjet(),
                                "insere" => $dossier->getDateAjout(),
                                "reception" => $transmission->getDateReelReception(),
                                "contenu" => $transmission->getContenu(),
                                "transmission"=> $transmission];
                    }
                 * --farany---
                 **/
                //}else{
                //   $data[$transmission->getDossier()->getNomDossier()]["nombre_echanges"] = $data[$transmission->getDossier()->getNomDossier()]["nombre_echanges"] + 1;
                //}
            } else {
                //$total += 1;
                foreach ($dossier->getTransmissions()->getValues() as $transmission) {
                    $data[] = [
                        "nom_dossier" => $dossier->getNomDossier(),
                        "nom_mairie" => $dossier->getNomMairie(),
                        "objet" => $transmission->getObjet(),
                        "insere" => $dossier->getDateAjout(),
                        "reception" => $transmission->getDateReelReception(),
                        "contenu" => $transmission->getContenu(),
                        "transmission" => $transmission
                    ];
                }
            }
        }
        //dump($query);
        //$resultat = $query->getResult();
        //$nombre_echange = count($resultat);

        dump($data);
        dump($isTextSearch);
        //dump($qb->getResult());
        return $this->render("transmission/fast_search.html.twig", [
            //"pagination" => $pagination,
            "data" => $data,
            "textSearch" => $isTextSearch,
            "total" => $total,
            //"data_dossiers" => $data_dossiers,
            //"data_email" => $data_email
            //"total_dossier" => $total_dossiers
            //"total_email" => $total_email
        ]);
    }
}
