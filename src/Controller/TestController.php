<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function test(): Response
    {
        return $this->json([
            'name' => 'iancu',
            'surname' => 'rasta'
        ]);
    }

    #[Route('/twig', name: 'app_twig')]
    public function twig(): Response
    {
        return $this->render('login.html.twig');
    }
}
