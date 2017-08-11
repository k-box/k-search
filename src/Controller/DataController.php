<?php

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Model\Data\AddRequest;
use App\Model\Data\AddResponse;
use App\Model\Data\DeleteRequest;
use App\Model\Data\GetRequest;
use App\Model\Data\GetResponse;
use App\Model\Data\SearchRequest;
use App\Model\Data\SearchResponse;
use App\Model\RPCRequest;
use App\Model\Status\StatusResponse;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataController extends Controller
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DataService
     */
    private $searchService;

    public function __construct(DataService $searchService, ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->searchService = $searchService;
        $this->validator = $validator;
        $this->serializer = $serializer;
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

        $success = $this->searchService->deleteData($deleteRequest->params->uuid);

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

        $data = $this->searchService->getData($getRequest->params->uuid);

        $getResponse = new GetResponse($data, $getRequest->id);

        return $this->getJsonResponse($getResponse);
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

        $res = $this->searchService->addData($addRequest->params->data, $addRequest->params->dataTextualContents);

        $data = $this->searchService->getData($addRequest->params->data->uuid);
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
        /** @var SearchRequest $addRequest */
        $addRequest = $this->getRequestModelFromJson($request, SearchRequest::class);

        // @todo Implement the logic here
        $statusResponse = new SearchResponse(null, $addRequest->id);

        return $this->getJsonResponse($statusResponse);
    }

    /**
     * Returns the given RequestModel from the request.
     *
     * @param Request $request
     * @param string  $class
     *
     * @throws BadRequestException
     *
     * @return mixed
     */
    private function getRequestModelFromJson(Request $request, string $class)
    {
        $requestModel = $this->serializer->deserialize($request->getContent(), $class, 'json');

        // We handle the request-id as a HTTP header, it will be used in the KSearchExceptionListener
        // to correctly set the "response->id" if available.
        if ($requestModel instanceof  RPCRequest && $requestModel->id) {
            $request->headers->set(RPCRequest::REQUEST_ID_HEADER, $requestModel->id);
        }

        $validationErrors = $this->validator->validate($requestModel);
        if (count($validationErrors) > 0) {
            $errors = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($validationErrors as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new BadRequestException($errors);
        }

        return $requestModel;
    }

    /**
     * @param mixed $model
     *
     * @return JsonResponse
     */
    private function getJsonResponse($model): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($model, 'json'), 200, [], true);
    }
}
