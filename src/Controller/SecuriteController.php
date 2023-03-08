<?php

namespace App\Controller;

use App\Entity\Interne;
use App\Entity\Visiteur;
use App\Form\InterneType;
use App\Form\VisiteurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class SecuriteController extends AbstractController
{

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function visiteur($paramDefaults = 0, Request $request, EntityManagerInterface $manager)
    {
        $isSearchClicked = false;
        $isSearchDate = false;
        $list_visiteur = [];
        $visiteur = new Visiteur();
        $form = $this->createForm(VisiteurType::class, $visiteur);

        $builder = $this->createFormBuilder();
        $form_search = $builder->add("search", TextType::class, [
            'required' => false
        ])->getForm();

        $builder_search = $this->createFormBuilder();
        $form_search_date = $builder_search->add('date_search', TextType::class, [
            "required"=>false
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
        if($form_search->isSubmitted() && $form_search->isValid()) {
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
        if($form_search_date->isSubmitted() and $form_search_date->isValid()){
            $data = $form_search_date->getData();
            $data = $data["date_search"];

            if(stristr($data, " - ")){
                $debut = explode("-", $data)[0];
                $debut = str_replace("/","-", $debut);
                $fin = explode("-", $data)[1];
                $fin = str_replace("/","-", $fin);
            }else{
                $debut = str_replace("/","-", $data);
                $fin = $debut;
            }

            /**
             * on attribue a true s'il fait de recherche
             */
            $isSearchDate = true;
            $isSearchClicked = true;

        }
        if ($isSearchClicked) {
            if($isSearchDate){
                if($debut == "" and $fin == ""){
                    $list_visiteur = $manager->getRepository(Visiteur::class)->findDataHierAndToDate();
                }else {
                    $list_visiteur = $manager->getRepository(Visiteur::class)->searchByDate($debut, $fin);
                }
                //on reinitialise le boolea
                $isSearchDate = false;
            }else {
                $list_visiteur = $manager->getRepository(Visiteur::class)->searchVisiteur($data);
            }
        }else{
            $list_visiteur = $manager->getRepository(Visiteur::class)->findDataHierAndToDate();
            //$list_visiteur = $manager->getRepository(Visiteur::class)->findAll();

        }
        return $this->render('visiteur/visiteur.html.twig', [
            'form' => $form->createView(),
            'list_visiteur' => $list_visiteur,
            "form_search" => $form_search->createView(),
            'form_search_by_date'=> $form_search_date->createView()
        ]);
    }

    /**
     * @Security("is_granted('ROLE_RH')")
     */
    public function interne($paramDefaults = 0, Request $request, EntityManagerInterface $manager)
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
            "required"=>false
        ])->getForm();


        $form = $this->createForm(InterneType::class, $interne);
        /**
         * suppression
         */
        if ($paramDefaults == 2) {
            $id_user = $request->query->get('id');
            $get = $manager->getRepository(Interne::class)->find($id_user);

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
        $debut="";
        $fin="";
        $form_search_date->handleRequest($request);
        if($form_search_date->isSubmitted() and $form_search_date->isValid()){
            $data = $form_search_date->getData();
            $data = $data["date_search"];
            /**
             * si interval de date, on la scie
             */
            if(stristr($data, " - ")){
                $debut = explode("-", $data)[0];
                $debut = str_replace("/","-", $debut);
                $fin = explode("-", $data)[1];
                $fin = str_replace("/","-", $fin);
            }else{
                $debut = str_replace("/","-", $data);
                $fin = $debut;
            }
            /**
             * on attribue a true s'il fait de recherche
             */
            $isSearchClicked = true;
            $isSearchDate = true;
        }

        if ($isSearchClicked) {
            if($isSearchDate){
                $list_visiteur = $manager->getRepository(Interne::class)->searchByDate($debut, $fin);
                //on reinitialise le boolean
                $isSearchDate = false;
            }else {
                $list_visiteur = $manager->getRepository(Interne::class)->searchInterne($data);
            }
        }else{
            //$list_visiteur = $manager->getRepository(Interne::class)->findAll();
            $list_visiteur = $manager->getRepository(Interne::class)->findDataHierAndToDate();

        }

        return $this->render('visiteur/interne.html.twig', [
            "form" => $form->createView(),
            'list_visiteur' => $list_visiteur,
            'form_search' => $form_search->createView(),
            'form_search_by_date'=> $form_search_date->createView()
        ]);
    }

}
