<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DataController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/api/0.0/data.delete",
     *     description="Delete piece of data from the search index.",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\DeleteRequest")
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Status\StatusResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse"),
     *     ),
     * )
     *
     * @Route(
     *     path="api/{version}/data.delete",
     *     requirements={
     *        "version":"\d++\.\d++"
     *     }
     * )
     *
     * @param string $version
     */
    public function postDataDelete(Request $request, string $version)
    {
        var_dump($version);
        var_dump($request->getContent());
        die();
    }
}
