<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ViewAllArticleController extends AbstractController
{
    /**
     * @Route("/view/all/article", name="view_all_article")
     */
    public function index()
    {
        return $this->render('view_all_article/index.html.twig', [
            'controller_name' => 'ViewAllArticleController',
        ]);
    }
}
