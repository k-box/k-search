<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class DocsController extends Controller
{
    /**
     * @SWG\Swagger(
     *     security={
     *         {"apiSecret":{}, "apiOrigin": {}}
     *     },
     *     @SWG\Info(
     *         title="K-Search API",
     *         version="3.7",
     *         description="The K-Search API definition",
     *         @SWG\Contact(
     *             email="info@klink.asia"
     *         ),
     *         termsOfService="https://klink.asia/privacy/",
     *     ),
     *     @SWG\SecurityScheme(
     *       securityDefinition="apiSecret",
     *       type="apiKey",
     *       in="header",
     *       name="Authorization"
     *     ),
     *     @SWG\SecurityScheme(
     *       securityDefinition="apiOrigin",
     *       type="apiKey",
     *       in="header",
     *       name="Origin"
     *     )
     * )
     *
     * @Route(
     *     "/docs",
     *     methods={"GET"}
     * )
     */
    public function getDocs()
    {
        return $this->render('swagger.html.twig');
    }
}
