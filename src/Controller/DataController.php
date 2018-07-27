<?php

namespace App\Controller;

use App\Entity\ApiUser;
use App\Exception\BadRequestException;
use App\Exception\DataDownloadErrorException;
use App\Exception\OutdatedDataRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\AddRequest;
use App\Model\Data\AddResponse;
use App\Model\Data\Data;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataController extends AbstractRpcController
{
    /**
     * @var DataService
     */
    private $dataService;

    public function __construct(
        DataService $searchService,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        parent::__construct($validator, $serializer);
        $this->dataService = $searchService;
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
     *     path="/api/3.5/data.add",
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
     *     ),
     * )
     *
     * @throws BadRequestException
     * @throws SolrEntityNotFoundException
     * @throws DataDownloadErrorException
     * @throws OutdatedDataRequestException
     */
    public function postDataAddV3(Request $request, string $version): JsonResponse
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_ADD);

        /** @var AddRequest $addRequest */
        $addRequest = $this->buildRpcRequestModelFromJson($request, AddRequest::class, $version);

        $data = $addRequest->params->data;
        $this->updateDataWithAPIUser($data);

        $this->dataService->addData($data, $addRequest->params->dataTextualContents, $addRequest->id);

        //$data = $this->dataService->getData($addRequest->params->data->uuid);
        $addResponse = new AddResponse($data, $addRequest->id);

        return $this->buildRpcJsonResponse($addResponse, $version);
    }

    private function updateDataWithAPIUser(Data $data): void
    {
        // Updating Data with the current API user
        /** @var ApiUser $apiUser */
        $apiUser = $this->getUser();
        $data->uploader->appUrl = $apiUser->getUsername();
        $data->uploader->email = $apiUser->getEmail();
    }
}
