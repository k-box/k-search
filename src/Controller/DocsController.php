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
     *         version="3.0",
     *         description="The K-Search API definition",
     *         contact="api@klink.asia",
     *         termsOfService="https://klink.asia/terms/",
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
     * @Route("/docs")
     */
    public function getDocs()
    {
        return $this->render('swagger.html.twig');
    }
}
