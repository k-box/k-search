<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class DocsController extends Controller
{
    /**
     * @SWG\Info(
     *     title="K-Search API",
     *     version="0.0",
     *     description="The K-Search API definition",
     *     contact="api@klink.asia",
     *     termsOfService="https://klink.asia/terms/",
     *     contact="api@klink.asia"
     * )
     *
     * @Route("/docs")
     */
    public function getDoc()
    {
        return $this->render('swagger.html.twig');
    }
}
