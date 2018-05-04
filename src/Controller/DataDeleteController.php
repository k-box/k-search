<?php

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Exception\InternalSearchException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\DeleteRequest;
use App\Model\Status\StatusResponse;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataDeleteController extends AbstractRpcController
{
    /**
     * @var DataService
     */
    private $dataService;

    public function __construct(
        DataService $dataService,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        parent::__construct($validator, $serializer);
        $this->dataService = $dataService;
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
     *     description="Delete piece of data from the search index. This API requires the `data-remove-own` or
     *     `data-remove-all` permission.", tags={"Data"},
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
     * @throws InternalSearchException
     *
     * @return JsonResponse
     */
    public function postDataDeleteV3(Request $request, string $version): JsonResponse
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
}
