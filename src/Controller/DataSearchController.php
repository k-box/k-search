<?php

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Exception\FilterQuery\FilterQueryException;
use App\Exception\FilterQuery\InvalidGeoJsonFilterException;
use App\Model\Data\Search\SearchRequest;
use App\Model\Data\Search\SearchResponse;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataSearchController extends AbstractRpcController
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
     *     path="/api/3.5/data.search",
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
     * @throws BadRequestException
     */
    public function postDataSearchV3(Request $request, string $version): JsonResponse
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_SEARCH);

        /** @var SearchRequest $searchRequest */
        $searchRequest = $this->buildRpcRequestModelFromJson($request, SearchRequest::class, $version);

        try {
            $searchResult = $this->dataService->searchData($searchRequest->params, $version);
        } catch (FilterQueryException $exception) {
            $field = 'params.filters';
            if ($exception instanceof InvalidGeoJsonFilterException) {
                $field = 'params.geo_location_filter.bounding_box';
            }

            throw new BadRequestException([
                $field => $exception->getMessage(),
            ]);
        }

        $searchResponse = new SearchResponse($searchResult, $searchRequest->id);

        return $this->buildRpcJsonResponse($searchResponse, $version);
    }
}
