<?php

namespace App\Controller;

use App\Model\Data\AddRequest;
use App\Model\Data\AddResponse;
use App\Model\Data\Data;
use App\Model\Data\DeleteRequest;
use App\Model\Data\GetRequest;
use App\Model\Data\GetResponse;
use App\Model\Data\SearchRequest;
use App\Model\Data\SearchResponse;
use App\Model\Error\ErrorResponse;
use App\Model\Status\StatusResponse;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataController extends Controller
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * DataController constructor.
     *
     * @param ValidatorInterface  $validator
     * @param SerializerInterface $serializer
     */
    public function __construct(ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * Delete piece of data from the search index.
     *
     * @Route(
     *     path="api/{version}/data.delete",
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
     * @param Request $request
     * @param string  $version
     *
     * @return Response
     */
    public function postDataDelete(Request $request, string $version)
    {
        /** @var DeleteRequest $deleteRequest */
        $deleteRequest = $this->serializer->deserialize($request->getContent(), DeleteRequest::class, 'json');

        $errors = $this->validator->validate($deleteRequest);
        if (count($errors) > 0) {
            $errorResponse = ErrorResponse::withErrorMessage(400, 'Wrong data!'.(string) $errors, $deleteRequest->id);

            return new JsonResponse($errorResponse);
        }

        // @todo Implement the logic here
        $statusResponse = StatusResponse::withStatusMessage(200, 'Ok', $deleteRequest->id);

        return new JsonResponse($statusResponse);
    }

    /**
     * Get detailed information of piece of data in the search index.
     *
     * @Route(
     *     path="api/{version}/data.delete",
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
     *         response="201",
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
        /** @var GetRequest $getRequest */
        $getRequest = $this->serializer->deserialize($request->getContent(), GetRequest::class, 'json');

        $errors = $this->validator->validate($getRequest);
        if (count($errors) > 0) {
            $errorResponse = ErrorResponse::withErrorMessage(400, 'Wrong data!'.(string) $errors, $getRequest->id);

            return new JsonResponse($errorResponse);
        }

        // @todo Implement the logic here
        $data = new Data();

        $getResponse = new GetResponse($data, $getRequest->id);

        return new JsonResponse($getResponse);
    }

    /**
     * Add piece of data to the search index.
     *
     * @Route(
     *     path="api/{version}/data.add",
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
     *         response="201",
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
        $addRequest = $this->serializer->deserialize($request->getContent(), AddRequest::class, 'json');

        $errors = $this->validator->validate($addRequest);
        if (count($errors) > 0) {
            $errorResponse = ErrorResponse::withErrorMessage(400, 'Wrong data!'.(string) $errors, $addRequest->id);

            return new JsonResponse($errorResponse);
        }

        // @todo Implement the logic here
        $data = new Data();

        $addResponse = new AddResponse($data, $addRequest->id);

        return new JsonResponse($addResponse);
    }

    /**
     * Allows to query the index and returns search results.
     *
     * @Route(
     *     path="api/{version}/data.search",
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
     *         response="201",
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
        /** @var SearchRequest $addRequest */
        $addRequest = $this->serializer->deserialize($request->getContent(), SearchRequest::class, 'json');

        $errors = $this->validator->validate($addRequest);
        if (count($errors) > 0) {
            $errorResponse = ErrorResponse::withErrorMessage(400, 'Wrong data!'.(string) $errors, $addRequest->id);

            return new JsonResponse($errorResponse);
        }

        // @todo Implement the logic here
        $statusResponse = new SearchResponse(null, $addRequest->id);

        return new JsonResponse($statusResponse);
    }
}
