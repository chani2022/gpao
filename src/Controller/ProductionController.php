<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Driver\Connection;


class ProductionController extends AbstractController
{
    public function anomalye(Connection $connexion, Request $request) {
        dump("ok");
        return $this->render('production/production.html.twig');
    }
}