<?php

namespace App\Controller;

use App\Model\GPAOModels\CategorieComptabilite;
use App\Model\GPAOModels\MouvementComptabilite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GestionController extends AbstractController
{
    /**
     * @Route("/gestion/{action}", name="app_gestion", defaults={"action":null})
     */
    public function index(Request $request, Connection $connexion, $action, SessionInterface $session)
    {
        $statistiques = [];
        $dates_search = null;
        $categorie_search = null;
        $total_entree=0;
        $total_sortie=0;
        $diff = 0;

        $conn_categories = new CategorieComptabilite($connexion);
        $categorie_comptabilite = $conn_categories->Get([
            "id_categorie_comptabilite",
            "nom_categorie"
        ])->execute()->fetchAll();
        $choice_categories = [];
        $data_form = [
            "date_mouvement" => null,
            "type_mouvement" => null,
            "observation" => null,
            "nom_categorie" => null,
            "pu" => null,
            "quantite" => null
        ];
        $id = null;
        foreach($categorie_comptabilite as $categorie){
            $choice_categories[$categorie["nom_categorie"]] = $categorie["id_categorie_comptabilite"];
        }
        /**
         * liste mouvements
         */
        $conn_mouv = new MouvementComptabilite($connexion);
        $sqlMouvement = $conn_mouv->Get()
                                ->where("date_mouvement BETWEEN :dateD AND :dateF");
        /**
         * search
         */
        if($request->request->get("dates")){
            $session->set('search_active', true);
            $dates = $request->request->get("dates");
            $dates_search = $dates;
            $categorie = $request->request->get("categorie");
            $categorie_search = $categorie;
            $dates = explode(" - ", $dates);
            $session->set('dates', $dates);

            $sqlMouvement->setParameter("dateD", $dates[0])
                         ->setParameter("dateF", $dates[1]);
            if($categorie != ""){
                $session->set('categorie', $categorie);
                $sqlMouvement->andWhere("nom_categorie = :cat")
                            ->setParameter("cat", $categorie);
            }else{
                $session->set("categorie", "");
            }
        }else{
            if($session->get('search_active')){
                $sqlMouvement->setParameter("dateD", $session->get('dates')[0])
                             ->setParameter("dateF", $session->get('dates')[1]);
                if($session->get('categorie') && $session->get('categorie') != ""){
                    $sqlMouvement->andWhere("nom_categorie = :cat")
                                ->setParameter("cat", $session->get('categorie'));
                }
            }else{
                $sqlMouvement->setParameter('dateD', (new \DateTime())->sub(new \DateInterval("P7D"))->format("Y-m-d"))
                            ->setParameter("dateF", (new \DateTime())->format("Y-m-d"));
            }
        }
        $mouvements = $sqlMouvement->execute()->fetchAll();
        
        /**
         * statistiques
         **/
        foreach($mouvements as $mouvement){
            $montant = $mouvement["pu"]*$mouvement["quantite"];
            (int)$month = explode("-", $mouvement["date_mouvement"])[1];
            if(!array_key_exists((int)$month, $statistiques)){
                $statistiques[(int)$month] = [
                                                        "montant" => $montant,
                                                        "entree" => $mouvement["type_mouvement"] == 1? $mouvement["pu"]*$mouvement["quantite"]:0,
                                                        "sortie" => $mouvement["type_mouvement"] == 2? $mouvement["pu"]*$mouvement["quantite"]:0
                                                    ];
            }else{
                $statistiques[(int)$month]["montant"] = $statistiques[(int)$month]["montant"] + $montant;
                $statistiques[(int)$month]["entree"] = $mouvement["type_mouvement"] == 1? $statistiques[(int)$month]["entree"]+($mouvement["pu"]*$mouvement["quantite"]):$statistiques[(int)$month]["entree"];
                $statistiques[(int)$month]["sortie"] = $mouvement["type_mouvement"] == 2? $statistiques[(int)$month]["sortie"]+($mouvement["pu"]*$mouvement["quantite"]):$statistiques[(int)$month]["sortie"];  
            }
        }
        /**
         * update or remove
         */
        if($action){
            $id = $request->query->get('id');
            $sql = null;
            $isDelete = false;
            if($action == "delete"){
                $sql = $conn_mouv->deleteData();
                $isDelete = true;
            }else{
                $sql = $conn_mouv->Get();
            }
            $sql->where("id_mouvement_comptabilite = :id_mouvement")
                                ->setParameter('id_mouvement', $id);
            /**
             * update
             */                   
            if(!$isDelete){
                $mouv = $sql->execute()->fetch();
                $data_form["date_mouvement"] = $mouv["date_mouvement"];
                $data_form["type_mouvement"] = (string)$mouv["type_mouvement"];
                $data_form["observation"] = $mouv["observation"];
                $data_form["nom_categorie"] = (string)$mouv["id_categorie_comptabilite"];
                $data_form["pu"] = $mouv["pu"];
                $data_form["quantite"] = $mouv["quantite"];
            }else{
                $sql->execute();
                $this->addFlash("success", "Suppression effectuée avec success!");
                return $this->redirectToRoute("app_gestion");
            }
        }
        foreach($mouvements as $key=>$mouv){
            $montant = (float)$mouv["pu"]*(int)$mouv["quantite"];
            $mouvements[$key]["montant"] = $montant;
        }
                                
        $form = $this->createFormBuilder()
                     ->add("date", TextType::class,[
                        "attr" => [
                            "value" => $data_form["date_mouvement"]
                        ]
                     ])
                     ->add("type", ChoiceType::class, [
                        "placeholder" => "-Selectionnez-",
                        "choices" => [
                            "Entrée" => 1,
                            "Sortie" => 2
                        ],
                        "data" => $data_form["type_mouvement"]
                     ])
                     ->add("observation", TextareaType::class, [
                        "data" => $data_form["observation"]
                     ])
                     ->add('categorie', ChoiceType::class, [
                        "placeholder" => "-Selectionnez-",
                        "choices" => $choice_categories,
                        "data" => $data_form["nom_categorie"]
                     ])
                     ->add("prix_u", TextType::class,[
                        "attr" => [
                            "value" => $data_form["pu"]
                        ]
                     ])
                     ->add('quantite', TextType::class, [
                        "attr" => [
                            "value" => $data_form["quantite"]
                        ]
                     ])
                     ->add('piece', FileType::class,[
                        "required" => false
                     ])
                     ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted()){
            $data = $form->getData();
            $date = (new \DateTime($data["date"]))->format("Y-m-d");
            $type = $data["type"];
            $observation = $data["observation"];
            $cat = $data["categorie"];
            $prix_u= $data["prix_u"];
            $quantite = $data["quantite"];
            $montant = $prix_u*$quantite;
            $piece = $data["piece"];
            $isUpdate = false;
            $message = "";
            if($action){
                if($action == "update"){
                    $isUpdate = true;
                }
            }
            if(!$isUpdate){
                $session->set('search_active', false);
                $conn_mouv->insertData([
                    "date_mouvement" => $date,
                    "type_mouvement" => $type,
                    "observation" => $observation,
                    "id_categorie_comptabilite" => $cat,
                    "pu" => $prix_u,
                    "quantite" => $quantite
                ])->execute();
                
                if($piece){
                    $mouv = $conn_mouv->Get(["id_mouvement_comptabilite"])
                                    ->where("date_mouvement = :date")
                                    ->andWhere("type_mouvement = :type_mouvement")
                                    ->andWhere("observation = :obs")
                                    ->andWhere("categorie_comptabilite.id_categorie_comptabilite = :cat")
                                    ->andWhere("pu = :pu")
                                    ->andWhere("quantite = :quantite")
                                    ->setParameter("date", $date)
                                    ->setParameter("type_mouvement", $type)
                                    ->setParameter("obs", $observation)
                                    ->setParameter('cat', $cat)
                                    ->setParameter("pu", $prix_u)
                                    ->setParameter("quantite", $quantite)
                                    ->execute()->fetch();
                    $path = $this->getParameter("app.piece_dir");
                    $ext = $piece->getClientOriginalExtension();
                    $piece->move($path, $mouv["id_mouvement_comptabilite"].".".$ext);
                }
                $message = "insertion ";
            }else{
                $conn_mouv->updateData([
                        "date_mouvement" => $date,
                        "type_mouvement" => $type,
                        "observation" => $observation,
                        "id_categorie_comptabilite" => $cat,
                        "pu" => $prix_u,
                        "quantite" => $quantite
                ],["id_mouvement_comptabilite" => $id])->execute();
                $message = "modification ";
            }
            $this->addFlash("success", $message."éffectuée avec success!");
            return $this->redirectToRoute("app_gestion");
        }
        
        foreach($statistiques as $statistique){
            $total_entree += $statistique["entree"];
            $total_sortie += $statistique["sortie"];
        }
        $diff = $total_entree - $total_sortie;
        dump($statistiques, $session->get('categorie'));
        return $this->render('gestion/comptabilite.html.twig', [
            'form' => $form->createView(),
            'mouvements' => $mouvements,
			"categories" => $choice_categories,
            "statistiques" => $statistiques,
            "dates" => $dates_search,
            "categorie" => $categorie_search,
            "total_entree" => $total_entree,
            "total_sortie" => $total_sortie,
            "difference" => $diff
        ]);
        
        
    }
}
