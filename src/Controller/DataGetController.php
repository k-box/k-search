<?php

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\GetRequest;
use App\Model\Data\GetResponse;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataGetController extends AbstractRpcController
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
    public function postDataGetV3(Request $request, string $version): JsonResponse
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_VIEW);

        /** @var GetRequest $get */
        $getRequest = $this->buildRpcRequestModelFromJson($request, GetRequest::class, $version);

        $data = $this->dataService->getData($getRequest->params->uuid);

        $getResponse = new GetResponse($data, $getRequest->id);

        return $this->buildRpcJsonResponse($getResponse, $version);
    }
}
