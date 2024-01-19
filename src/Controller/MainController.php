<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\HttpFoundation\Response;


use Doctrine\DBAL\Driver\Connection;
use App\Model\GPAOModels\Personnel;

class MainController extends AbstractController
{
    private $conex;

    public function index()
    {
        return $this->redirectToRoute('login');
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function homeMainIndex(Connection $cnx)
    {
        $data = [];

        $pers = new Personnel($cnx);


        $data = $pers->Get(array('id_personnel', 'nom', 'prenom'))
            ->where('actif = :a')
            ->setParameter('a', 'Oui')
            ->orderBy('id_personnel', 'DESC')
            ->setMaxResults(5)
            ->execute()->fetchAll();


        //        $msg = new \App\Model\GPAOModels\Messagerie($cnx);
        //        
        //        $data = $msg->Get()
        //                ->where('messagerie.destinataire = :d')
        //                ->andWhere('messagerie.date_envoie > :dt')
        //                ->setParameter('d', 46)
        //                ->setParameter('dt', '2019-10-09')
        //                ->addOrderBy('messagerie.id_messagerie','DESC')
        //                ->setMaxResults(15)
        //                ->execute()
        //                ->fetchAll();

        //var_dump($data);

        //return new Response("TEST");

        return $this->render('main/home.html.twig', [
            "data" => $data
        ]);
    }

    /**
     * generation menu accueil
     * @return type
     */
    public function getMenus(\Symfony\Component\HttpFoundation\Session\SessionInterface $session, $activeRoutes = NULL)
    {

        $BASE_MENU = array(
            [
                "name" => "Transmission",
                "routes" => "",
                "roles" => [],
                "icon" => "fas fa-desktop",
                "child" => [
                    [
                        "name" => "Rédiger",
                        "routes" => "transmission_envoie",
                        "roles" => [],
                        "icon" => "fas fa-edit"
                    ],

                    [
                        "name" => "Réception",
                        "routes" => "transmission_index",
                        "roles" => [],
                        "icon" => "fas fa-envelope"
                    ],
                    [
                        "name" => "Envoyés",
                        "routes" => "transmission_boite_envoie",
                        "roles" => [],
                        "icon" => "fa fa-send"
                    ],
                ]
            ],
            [
                "name" => "R.H",
                "routes" => "",
                "roles" => ['ROLE_RH', 'ROLE_ARH', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'],
                "icon" => "zmdi zmdi-account-circle",
                "child" => [
                    /*["name" => "Gestion",
                        "routes" => "",
                        "roles" => [],
                        "icon" => "zmdi zmdi-view-dashboard"
                    ],
                    ["name" => "Récolte Heures",
                        "routes" => "recolte_index",
                        "roles" => [],
                        "icon" => "zmdi zmdi-time-restore"
                    ],**/

                    [
                        "name" => "Communiqué",
                        "routes" => "add_communique",
                        "roles" => ['ROLE_SUPER_ADMIN', "ROLE_ADMIN"],
                        "icon" => "fas fa-comment"
                    ],
                    [
                        "name" => "Limitation ACCES",
                        "routes" => "index_limitation_access",
                        "roles" => ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN'],
                        "icon" => "fas fa-check"
                    ],
                    [
                        "name" => "Stats. présence",
                        "routes" => "index_statistique_rh",
                        "roles" => ['ROLE_ADMIN'],
                        "icon" => "fas fa-chart-line"
                    ],
                    [
                        "name" => "Exception connection",
                        "routes" => "rh_exceptionConnexion",
                        "roles" => [],
                        "icon" => "fas fa-users"
                    ],
                    [
                        "name" => "Régularisation absence",
                        "routes" => "regularisation_absence",
                        "roles" => [],
                        "icon" => "zmdi zmdi-eye-off"
                    ],
                    [
                        "name" => "Tableau de bord",
                        "routes" => "statistique_generale",
                        "roles" => [],
                        "icon" => "fas fa-chart-bar"
                    ],
                    [
                        "name" => "Absence Imprevu",
                        "routes" => "absence_imprevu",
                        "roles" => [],
                        "icon" => "zmdi zmdi-alert-triangle"
                    ],
                    [
                        "name" => "Jour fériés",
                        "routes" => "add_Jour_feries",
                        "roles" => [],
                        "icon" => "zmdi zmdi-calendar-close"
                    ],
                    [
                        "name" => "Lettres",
                        "routes" => "lettres_user",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    [
                        "name" => "Retard",
                        "routes" => "retard_user",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    [
                        "name" => "Sortie avant l'heure",
                        "routes" => "heure_manquant",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    [
                        "name" => "Importation et exportation Rib",
                        "routes" => "rh_importation_exportation_rib",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    [
                        "name" => "Gestion demande supplementaire",
                        "routes" => "app_gestion_demande",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    [
                        "name" => "Fraude",
                        "routes" => "app_rh_fraude",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    [
                        "name" => "Allaitement",
                        "routes" => "app_gestion_allaitement_conge_maternite",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    [
                        "name" => "Congé de maternité",
                        "routes" => "app_gestion_conge_maternite",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
					[
                        "name" => "Recolte",
                        "routes" => "recolte_route",
                        "roles" => [],
                        "icon" => "zmdi zmdi-format-color-text"
                    ],
                    


                ]
            ],
            [
                "name" => "Dossiers",
                "routes" => "",
                "roles" => [],
                "icon" => "fas fa-folder",
                "child" => [

                    [
                        "name" => "Dossier",
                        "routes" => "dossier_gestion",
                        "roles" => [],
                        "icon" => "fas fa-folder-open"
                    ],
                    [
                        "name" => "CDC",
                        "routes" => "cdc_gestion",
                        "roles" => [],
                        "icon" => "zmdi zmdi-file-text"
                    ],
                    [
                        "name" => "Planning",
                        "routes" => "dossier_planning",
                        "roles" => [],
                        "icon" => "zmdi zmdi-calendar"
                    ],

                    [
                        "name" => "Suivi",
                        "routes" => "suivi_prod",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Incident général",
                        "routes" => "incident_generale",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Evolution travail",
                        "routes" => "evolution_travail",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Evolution opérateur",
                        "routes" => "evolution_operateur",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Suivi rejet",
                        "routes" => "suivi_rejet",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],

                    [
                        "name" => "Extra géneral",
                        "routes" => "dossier_extra_general",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],

                    [
                        "name" => "Suivi Extra (ABSENCE EXTRA)",
                        "routes" => "dossier_suivi_extra_absence",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Suivi Extra (HEURE MANQUANTE EXTRA)",
                        "routes" => "dossier_suivi_extra_heure_manquant",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Suivi Extra (EXTRA EFFECTUE PAR SEMAINE)",
                        "routes" => "dossier_suivi_extra_extra_effectuez",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Comparaison (Production normal heure normal et extra)",
                        "routes" => "dossier_statistique_extra_et_production",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Progression dossier",
                        "routes" => "progression_dossier",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Analyse dossier",
                        "routes" => "analyse_dossier",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Heure réel",
                        "routes" => "heure_reel",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Historique des fichiers",
                        "routes" => "historique_fichiers",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Attribution dossier",
                        "routes" => "attribution_dossier",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],
                    [
                        "name" => "Livraison",
                        "routes" => "app_dossier_livraison",
                        "roles" => [],
                        "icon" => "zmdi zmdi-chart"
                    ],

                ]
            ],
            [
                "name" => "Sécurité",
                "routes" => "",
                "roles" => [],
                "icon" => "fas fa-shield-alt",
                "child" => [

                    [
                        "name" => "Sortie avant l'heure",
                        "routes" => "interne",
                        "roles" => [],
                        "icon" => "fas fa-sign-out-alt"
                    ],
                    [
                        "name" => "Visiteur",
                        "routes" => "visiteur",
                        "roles" => [],
                        "icon" => "zmdi zmdi-walk"
                    ],
                    [
                        "name" => "Identification",
                        "routes" => "app_securite_identification",
                        "roles" => [],
                        "icon" => "zmdi zmdi-walk"
                    ],

                ]
            ],
            [
                "name" => "Gestion",
                "routes" => "",
                "roles" => [],
                "icon" => "zmdi zmdi-collection-text",
                "child" => [

                    [
                        "name" => "Comptabilité",
                        "routes" => "app_gestion",
                        "roles" => [],
                        "icon" => "fas fa-sign-out-alt"
                    ],
                ]
            ],

        );
        //$session->set('menus', $BASE_MENU);
        //$session->set('ROUTE_ACTIVE', )
        return $this->render('base.menu-v2.html.twig', [
            'menus' => $BASE_MENU,
            'ROUTES_ACTIVE' => $activeRoutes
        ]);
    }
}
