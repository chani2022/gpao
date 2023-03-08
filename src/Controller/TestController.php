<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\DBAL\Driver\Connection;
use App\Model\GPAOModels\Personnel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Model\GPAOModels\AbsencePersonnel;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;
use SQLite3;

class TestController extends AbstractController
{
    private $cnx;
    private $client;
    
    public function __construct(Connection $c, HttpClientInterface $client) {
        $this->cnx = $c;
        $this->client = $client;
    }
    
    /**
     * @Route("/testspout", name="testspout")
     * @param Request $req
     */
    public function testspout(Request $req){
        $dirPiece = $this->getParameter('app.temp_dir');
        
        $nomFichier = $dirPiece."/test.xlsx";
        
        $writer = WriterEntityFactory::createXLSXWriter();
        
        $writer->openToFile($nomFichier);
        
        $entete = [
            WriterEntityFactory::createCell("NOM"),
            WriterEntityFactory::createCell("PRENOM"),
        ];
        $singleRow = WriterEntityFactory::createRow($entete);
        $writer->addRow($singleRow);
        
        
        $writer->close();
        
        $fichierExcel = new \Symfony\Component\HttpFoundation\File\File($nomFichier);
        
        return $this->file($fichierExcel, 'FICHIER EXCEL.xlsx');
        //return new Response("OK");
    }
    
    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function index()
    {
        $response = $this->client->request(
            'GET',
            'http://192.168.8.6:8000/testapi/tsyfatatro'
        );

        $statusCode = $response->getStatusCode();
        // $statusCode = 200
        $contentType = $response->getHeaders()['content-type'][0];
        // $contentType = 'application/json'
        $content = $response->getContent();
        // $content = '{"id":521583, "name":"symfony-docs", ...}'
        $content = $response->toArray();
        // $content = ['id' => 521583, 'name' => 'symfony-docs', ...]

        return new JsonResponse(["dd"=>$content]);
        /**
        $pers = new Personnel($this->cnx);
        
        
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
            'menus'=>[]
        ]);**/
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testReact(){

        return $this->render('test/test_react.html.twig');
    }
    
    /**
     *
     * @return JsonResponse
     */
    public function testApi(Request $request){
        $nom = $request->query->get('nom');
        return new JsonResponse(["nom" => $nom,"Prenom" =>"Potencier"]);
    }
    
    public function testStatistique(Connection $cnx, Request $request){
        
        
        if($request->isXmlHttpRequest()){
            $absence = new AbsencePersonnel($cnx);
            $sql = $absence->Get([
                "DISTINCT date_debut_absence",
                "COUNT(absence_personnel.id_absence_personnel) AS nb",
                
            ]);
            $date = explode(' - ',$request->query->get('date'));
            $result = $sql->where('date_debut_absence BETWEEN :deb and :fin')
                        ->setParameter('deb', $date[0])
                        ->setParameter('fin', $date[1])
                        
                        ->groupBy('date_debut_absence')
                        ->orderBy("date_debut_absence", "DESC")->execute()->fetchAll();
            
            $x = [];
            $y = [];
            foreach($result as $tab_res){
                $x[] = date('d-m-Y', strtotime($tab_res["date_debut_absence"]));
                $y[] = $tab_res["nb"];
            }
            return new JsonResponse([
                "x" => $x,
                "y" => $y
            ]);
        }
        return $this->render('test/abs_statistique.html.twig');
    }
    /**
     * @Route("/template", name="app_template")
     * @return type
     */
    public function testTab(){
        return $this->render('test/tab.html.twig');
    }
    
    public function apiWord(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        

        $params = ["dossier"=>284, "mail_navette"=>1 ];

        $datas = [];

        //$navette = $em->getRepository(Transmission::class)->findBy($params,["date_envoie"=>"DESC"]);
        $navette = $em->getRepository(Transmission::class)->findBy($params,["date_reel_reception"=>"DESC"]);
        
        $nomDossier = "TEST";

        foreach($navette as $n){
            //on n'exporte que les mails qui ont été indiqué à archiver
            if ($n->getMailNavette() == TRUE){
                
                $entete = "[ENVOIE";
                if ($n->getMailClient() == TRUE || $n->getMailClient() == 1) $entete = "[RECEPTION";

                if (!is_null($n->getDateReelReception())){
                    $entete = $entete." ".$n->getDateReelReception()->format("d/m/Y")."]";
                }else{
                    $entete = $entete."]";
                }

                $datas[] = $entete;
                //$datas[] = strip_tags(html_entity_decode($n->getContenu()));
                $contenu = $n->getContenu();
                $contenu = preg_replace('/<[^>]*>/','', $contenu);
                $contenu = html_entity_decode($contenu);

                $datas[] = $contenu;

            }
        }
        
        return new JsonResponse(["data" =>$datas, "nomD" => $nomDossier]);
    }
    
    public function sqlite(Request $request){
        
        $db = new \PDO('sqlite:var/www/gpao_adds_dev/public/bdd_base_esd_traitement/Masque_Eline.esd', '', '', array(
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ));
         
        
        return new Response('Ok');
    }
}
