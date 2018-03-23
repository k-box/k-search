<?php

namespace App\Controller;

use App\Entity\ApiUser;
use App\Exception\BadRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\AddRequest;
use App\Model\Data\AddResponse;
use App\Model\Data\Data;
use App\Model\Data\DataStatus;
use App\Model\Data\DataStatusRequest;
use App\Model\Data\DataStatusResponse;
use App\Model\Data\DeleteRequest;
use App\Model\Data\GetRequest;
use App\Model\Data\GetResponse;
use App\Model\Data\Search\SearchRequest;
use App\Model\Data\Search\SearchResponse;
use App\Model\Status\StatusResponse;
use App\Security\Authorization\Voter\DataVoter;
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
     *     name="api.v3.data.delete",
     *     path="api/{version}/data.delete",
     *     methods={"POST"},
     *     requirements={
     *        "version":"3.\d++"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/3.0/data.delete",
     *     description="Delete piece of data from the search index. This API requires the `data-remove-own` or `data-remove-all` permission.",
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
     * @throws BadRequestException
     * @throws SolrEntityNotFoundException
     *
     * @return Response
     */
    public function postDataDeleteV3(Request $request, string $version)
    {
        // First we check if the user has at least the needed credentials
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_REMOVE);

        /** @var DeleteRequest $deleteRequest */
        $deleteRequest = $this->buildRpcRequestModelFromJson($request, DeleteRequest::class, $version);

        $data = $this->dataService->getData($deleteRequest->params->uuid);

        // And here we check if it can remove the data given data
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_REMOVE, $data);

        $success = $this->dataService->deleteData($deleteRequest->params->uuid);

        if ($success) {
            $statusResponse = StatusResponse::withStatusMessage(200, 'Ok', $deleteRequest->id);
        } else {
            $statusResponse = StatusResponse::withStatusMessage(500, 'Error', $deleteRequest->id);
        }

        return $this->buildRpcJsonResponse($statusResponse, $version);
    }

    /**
     * Get detailed information of piece of data in the index.
     *
     * @Route(
     *     name="api.v3.data.get",
     *     path="api/{version}/data.get",
     *     methods={"POST"},
     *     requirements={
     *        "version":"3.\d++"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/3.0/data.get",
     *     description="Get detailed information of piece of data in the index. This API requires the `data-view` permission.",
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
     * @throws BadRequestException
     * @throws SolrEntityNotFoundException
     *
     * @return JsonResponse
     */
    public function postDataGetV3(Request $request, string $version)
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_VIEW);

        /** @var GetRequest $get */
        $getRequest = $this->buildRpcRequestModelFromJson($request, GetRequest::class, $version);

        $data = $this->dataService->getData($getRequest->params->uuid, Data::STATUS_OK);

        $getResponse = new GetResponse($data, $getRequest->id);

        return $this->buildRpcJsonResponse($getResponse, $version);
    }

    /**
     * Get the status information of a Data piece in the index.
     *
     * @Route(
     *     name="api.v3.data.status",
     *     path="api/{version}/data.status",
     *     methods={"POST"},
     *     requirements={
     *        "version":"3.\d++"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/3.0/data.status",
     *     description="Get the status information of a Data piece in the index. This API requires the `data-view` permission.",
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
     * @throws BadRequestException
     * @throws SolrEntityNotFoundException
     *
     * @return JsonResponse
     */
    public function postDataStatusV3(Request $request, string $version)
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_VIEW);

        /** @var DataStatusRequest $dataStatusRequest */
        $dataStatusRequest = $this->buildRpcRequestModelFromJson($request, DataStatusRequest::class, $version);

        $data = $this->dataService->getData($dataStatusRequest->params->uuid);

        $status = new DataStatus($data->status, $data->errorStatus);
        $statusResponse = new DataStatusResponse($status, $dataStatusRequest->id);

        return $this->buildRpcJsonResponse($statusResponse, $version);
    }

    /**
     * Add piece of data to the index.
     *
     * @Route(
     *     name="api.v3.data.add",
     *     path="api/{version}/data.add",
     *     methods={"POST"},
     *     requirements={
     *        "version":"3.\d++"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/3.0/data.add",
     *     description="Add piece of data to the index. This API requires the `data-add` permission.",
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
     * @throws BadRequestException
     * @throws SolrEntityNotFoundException
     *
     * @return JsonResponse
     */
    public function postDataAddV3(Request $request, string $version)
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_ADD);

        /** @var AddRequest $addRequest */
        $addRequest = $this->buildRpcRequestModelFromJson($request, AddRequest::class, $version);

        $data = $addRequest->params->data;

        // Updating Data with the current API user
        /** @var ApiUser $apiUser */
        $apiUser = $this->getUser();
        $data->uploader->appUrl = $apiUser->getUsername();
        $data->uploader->email = $apiUser->getEmail();

        $res = $this->dataService->addData($data, $addRequest->params->dataTextualContents);

        $data = $this->dataService->getData($addRequest->params->data->uuid);
        $addResponse = new AddResponse($data, $addRequest->id);

        return $this->buildRpcJsonResponse($addResponse, $version);
    }

    /**
     * Allows to query the index and returns search results.
     *
     * @Route(
     *     name="api.v3.data.search",
     *     path="api/{version}/data.search",
     *     methods={"POST"},
     *     requirements={
     *        "version":"3.\d++"
     *     }
     * )
     * @SWG\Post(
     *     path="/api/3.3/data.search",
     *     description="Allows to query the index and returns search results. This API requires the `data-search` permission.",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\Search\SearchRequest")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data\Search\SearchResponse")
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
     * @throws BadRequestException
     *
     * @return JsonResponse
     */
    public function postDataSearchV3(Request $request, string $version)
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_SEARCH);

        /** @var SearchRequest $searchRequest */
        $searchRequest = $this->buildRpcRequestModelFromJson($request, SearchRequest::class, $version);

        $searchResult = $this->dataService->searchData($searchRequest->params, $version);

        $searchResponse = new SearchResponse($searchResult, $searchRequest->id);

        return $this->buildRpcJsonResponse($searchResponse, $version);
    }
}
