<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DocsController extends Controller
{
    /**
     * @SWG\Info(
     *     title="K-Search API",
     *     version="0.1",
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

    /**
     * @SWG\Post(
     *     path="/api/3.0/search",
     *     description="Search for data in the K-Search index",
     *     tags={"Search"},
     *     summary="",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/SearchRequest")
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Search")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the request is not correct",
     *         @SWG\Schema(ref="#/definitions/ErrorResponse")
     *     ),
     * )
     */
    public function postSearch()
    {
    }

    /**
     * @SWG\Post(
     *     path="/api/0.0/data.add",
     *     description="Send a generic data to the K-Search to be added in the index",
     *     tags={"Data"},
     *     summary="",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/DataRequest")
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/ErrorResponse"),
     *         examples={
     *
     *         }
     *     ),
     * )
     */
    public function postDataAdd()
    {
    }
}
