<?php

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Model\Data\Klink;
use App\Model\Data\ListRequest;
use App\Model\Data\ListResponse;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\KlinkService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class KlinkListController extends AbstractRpcController
{
    /**
     * @var KlinkService
     */
    private $klinkService;

    public function __construct(
        KlinkService $klinkService,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        parent::__construct($validator, $serializer);
        $this->klinkService = $klinkService;
    }

    /**
     * Get the list of K-Links and application can connect to.
     *
     * @Route(
     *     name="api.v3.klink.list",
     *     path="api/{version}/klink.list",
     *     methods={"POST"},
     *     requirements={
     *        "version":"3.\d++"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/3.7/klink.list",
     *     description="Retrieve the list of K-Links the application can access. This API requires the `data-view` permission.",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\ListRequest")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data\ListResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse"),
     *     ),
     * )
     *
     * @throws BadRequestException
     */
    public function postKlinkList(Request $request, string $version): JsonResponse
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_VIEW);

        /** @var ListRequest $get */
        $listRequest = $this->buildRpcRequestModelFromJson($request, ListRequest::class, $version);

        $klinks = $this->klinkService->getKlinks();

        $data = array_map(function ($k) {
            $klink = new Klink();
            $klink->id = $k->getId();
            $klink->name = $k->getName();

            return $klink;
        }, $klinks);

        $listResponse = new ListResponse($data, $listRequest->id);

        return $this->buildRpcJsonResponse($listResponse, $version);
    }
}
