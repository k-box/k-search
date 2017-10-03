<?php

namespace App\Controller;

use App\Model\Data\AddRequest;
use App\Model\Data\AddResponse;
use App\Model\Data\DataStatus;
use App\Model\Data\DataStatusRequest;
use App\Model\Data\DataStatusResponse;
use App\Model\Data\DeleteRequest;
use App\Model\Data\GetRequest;
use App\Model\Data\GetResponse;
use App\Model\Data\SearchRequest;
use App\Model\Data\SearchResponse;
use App\Model\Status\StatusResponse;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataController extends AbstractRpcController
{
    /**
     * @var DataService
     */
    private $dataService;

    public function __construct(DataService $searchService, ValidatorInterface $validator, SerializerInterface $serializer)
    {
        parent::__construct($validator, $serializer);
        $this->dataService = $searchService;
    }

    /**
     * Delete piece of data from the search index.
     *
     * @Route(
     *     path="api/{version}/data.delete",
     *     methods={"POST"},
     *     requirements={
     *        "version":"0.0"
     *     }
     * )
     *
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
     *         response="200",
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
     * @param Request $request
     * @param string  $version
     *
     * @return Response
     */
    public function postDataDelete(Request $request, string $version)
    {
        /** @var DeleteRequest $deleteRequest */
        $deleteRequest = $this->getRequestModelFromJson($request, DeleteRequest::class);

        $success = $this->dataService->deleteData($deleteRequest->params->uuid);

        if ($success) {
            $statusResponse = StatusResponse::withStatusMessage(200, 'Ok', $deleteRequest->id);
        } else {
            $statusResponse = StatusResponse::withStatusMessage(400, 'Error', $deleteRequest->id);
        }

        return $this->getJsonResponse($statusResponse);
    }

    /**
     * Get detailed information of piece of data in the search index.
     *
     * @Route(
     *     path="api/{version}/data.get",
     *     methods={"POST"},
     *     requirements={
     *        "version":"0.0"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/0.0/data.get",
     *     description="Get detailed information of piece of data in the search index",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\GetRequest")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data\GetResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse"),
     *         examples={
     *
     *         }
     *     ),
     * )
     *
     * @param Request $request
     * @param string  $version
     *
     * @return JsonResponse
     */
    public function postDataGet(Request $request, string $version)
    {
        /** @var GetRequest $get */
        $getRequest = $this->getRequestModelFromJson($request, GetRequest::class);

        $data = $this->dataService->getData($getRequest->params->uuid);

        $getResponse = new GetResponse($data, $getRequest->id);

        return $this->getJsonResponse($getResponse);
    }

    /**
     * Get the status information of a Data piece in the search index.
     *
     * @Route(
     *     path="api/{version}/data.status",
     *     methods={"POST"},
     *     requirements={
     *        "version":"0.0"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/0.0/data.status",
     *     description="Get the status information of a Data piece in the search index.",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\DataStatusRequest")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data\DataStatusResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse"),
     *         examples={}
     *     ),
     * )
     *
     * @param Request $request
     * @param string  $version
     *
     * @return JsonResponse
     */
    public function postDataStatus(Request $request, string $version)
    {
        /** @var DataStatusRequest $dataStatusRequest */
        $dataStatusRequest = $this->getRequestModelFromJson($request, DataStatusRequest::class);

        $data = $this->dataService->getData($dataStatusRequest->params->uuid);

        $status = new DataStatus($data->status);
        $statusResponse = new DataStatusResponse($status, $dataStatusRequest->id);

        return $this->getJsonResponse($statusResponse);
    }

    /**
     * Add piece of data to the search index.
     *
     * @Route(
     *     path="api/{version}/data.add",
     *     methods={"POST"},
     *     requirements={
     *        "version":"0.0"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/0.0/data.add",
     *     description="Add piece of data to the search index",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\AddRequest")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data\AddResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse"),
     *         examples={
     *
     *         }
     *     ),
     * )
     *
     * @param Request $request
     * @param string  $version
     *
     * @return JsonResponse
     */
    public function postDataAdd(Request $request, string $version)
    {
        /** @var AddRequest $addRequest */
        $addRequest = $this->getRequestModelFromJson($request, AddRequest::class);

        $res = $this->dataService->addData($addRequest->params->data, $addRequest->params->dataTextualContents);

        $data = $this->dataService->getData($addRequest->params->data->uuid);
        $addResponse = new AddResponse($data, $addRequest->id);

        return $this->getJsonResponse($addResponse);
    }

    /**
     * Allows to query the index and returns search results.
     *
     * @Route(
     *     path="api/{version}/data.search",
     *     methods={"POST"},
     *     requirements={
     *        "version":"0.0"
     *     }
     * )
     * @SWG\Post(
     *     path="/api/0.0/data.search",
     *     description="Allows to query the index and returns search results.",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\SearchRequest")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data\SearchResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the request is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse")
     *     ),
     * )
     *
     * @param Request $request
     * @param string  $version
     *
     * @return JsonResponse
     */
    public function postDataSearch(Request $request, string $version)
    {
        /** @var SearchRequest $searchRequest */
        $searchRequest = $this->getRequestModelFromJson($request, SearchRequest::class);

        $searchResult = $this->dataService->queryData($searchRequest->params);

        $searchResponse = new SearchResponse($searchResult, $searchRequest->id);

        return $this->getJsonResponse($searchResponse);
    }
}
