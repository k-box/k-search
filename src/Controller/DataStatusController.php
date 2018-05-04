<?php

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Exception\ProcessingStatusNotFoundException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\DataStatusRequest;
use App\Model\Data\DataStatusResponse;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\DataStatusService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataStatusController extends AbstractRpcController
{
    /**
     * @var DataStatusService
     */
    private $dataStatusService;

    public function __construct(
        DataStatusService $dataStatusService,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        parent::__construct($validator, $serializer);
        $this->dataStatusService = $dataStatusService;
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
     *     path="/api/3.4/data.status",
     *     description="Get the status information of a Data piece in the index. This API requires the `data-view`
     *     permission.", tags={"Data"},
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
     *     ),
     * )
     *
     * @param Request $request
     * @param string  $version
     *
     * @throws BadRequestException
     * @throws SolrEntityNotFoundException
     * @throws ProcessingStatusNotFoundException
     *
     * @return JsonResponse
     */
    public function postDataStatusV3(Request $request, string $version): JsonResponse
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_VIEW);

        /** @var DataStatusRequest $dataStatusRequest */
        $dataStatusRequest = $this->buildRpcRequestModelFromJson($request, DataStatusRequest::class, $version);

        $dataStatus = $this->dataStatusService->getDataStatus(
            $dataStatusRequest->params->type,
            $dataStatusRequest->params->uuid
        );
        $statusResponse = new DataStatusResponse($dataStatus, $dataStatusRequest->id);

        return $this->buildRpcJsonResponse($statusResponse, $version);
    }
}
