<?php

namespace KCore\ThumbnailsAPIBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\Serializer;
use KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest;
use KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorResponse;
use KCore\ThumbnailsAPIBundle\Services\ThumbnailsService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class DefaultController extends FOSRestController
{
    /**
     * Get the thumbnail for the specified document.
     *
     * @ApiDoc(
     *      resource = true,
     *      authentication = true,
     *      section="Thumbnails",
     *      description = "Retrieves a thumbnail for the document identified by the given pair <InstitutionID, DocumentDescriptorID>",
     *      output = "image/png",
     *      statusCodes = {
     *          200 = "Returned when successful",
     *          403 = "Returned when the invocation is Denied",
     *          404 = "Returned when the document is not found",
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param $institutionId
     * @param $localDocumentId
     *
     * @throws NotFoundHttpException when the DocumentDescriptor is not found
     *
     * @return image
     *
     * @todo this API has been disabled, due to an improper implementation and a missing endpoint to post thumbnail
     *       generation request with <InstitutionID, localDocumentId> pairs
     */
    private function getThumbnailAction($institutionId, $localDocumentId)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY', null, 'Unable to access this page!');

        /** @var ThumbnailsService $thumbnailsService */
        $thumbnailsService = $this->get('klink.thumbnails.service');

        if ($institutionId == null || trim($institutionId) == '') {
            throw new BadRequestHttpException('Bad request: InstitutionID has a wrong format: '.$institutionId);
        } elseif ($localDocumentId == null || trim($localDocumentId) == '') {
            throw new BadRequestHttpException('Bad request: DocumentID has a wrong format: '.$localDocumentId);
        } elseif (!$thumbnailsService->thumbnailExists($localDocumentId, $institutionId)) {
            if ($thumbnailsService->thumbnailIsProcessing($localDocumentId, $institutionId)) {
                throw new HttpException(102, sprintf('The thumbnail for resource \'%s:%s\' is being generated.', $institutionId, $localDocumentId));
            } else {
                throw new NotFoundHttpException(sprintf('The thumbnail for resource with InstitutionID: \'%s\' and DocumentID:\'%s\' was not found.', $institutionId, $localDocumentId));
            }
        } else {
            $fileNameWithPath = $thumbnailsService->getThumbnailFilenameByIDs($localDocumentId, $institutionId);
            $thumbnailName = $localDocumentId.'_'.$institutionId.'_thumbnail.png';
            $base64Content = base64_encode(file_get_contents($fileNameWithPath));

            $result = new ThumbnailGeneratorResponse();
            $result->setName($thumbnailName);
            $result->setDataUri('data:image/png;base64,'.$base64Content);

            $thumbnailsService->deleteThumbnail($fileNameWithPath);

            $view = $this->view($result, 200);

            return $this->handleView($view);
        }
    }

    /**
     * Generate a thumbnail for the specified document.
     *
     * @ApiDoc(
     *      section="Thumbnails",
     *      authentication = true,
     *      description = "Generates a thumbnail for the specified document",
     *      input = "KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest",
     *      output = "KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorResponse",
     *      statusCodes = {
     *          201 = "Returned when the thumbnail has been successfully generated",
     *          400 = "Returned when a wrong data format has been sent",
     *          401 = "Returned when the invocation is Not Authorized",
     *          403 = "Returned when the invocation is Denied",
     *          502 = "Returned when the URL thumbnail generation raised a generic error",
     *          504 = "Returned when the URL thumbnail generation raised a timeout",
     *          509 = "Returned when the URL thumbnail generation raised a max-redirect error (URL is redirecting too many times)",
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function postThumbnailAction(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY', null, 'Unable to access this page!');

        $content = $request->getContent();

        if (empty($content)) {
            throw new BadRequestHttpException('Wrong format: Empty Contents');
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        /** @var ThumbnailGeneratorRequest $thumbnailGeneratorRequest */
        $thumbnailGeneratorRequest = $serializer->deserialize($content, 'KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest', 'json');

        /** @var RecursiveValidator $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($thumbnailGeneratorRequest);

        if (count($errors) > 0) {
            $message = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $message[] = $error->getPropertyPath();
            }
            throw new BadRequestHttpException('Wrong format for fields: '.implode('|', $message));
        }

        /** @var ThumbnailsService $thumbnailsService */
        $thumbnailsService = $this->get('klink.thumbnails.service');

        $fileNameWithPath = $thumbnailsService->generateThumbnailFromGeneratorRequest($thumbnailGeneratorRequest);

        if (!$fileNameWithPath) {
            throw new InternalErrorException('Error during thumbnail creation.');
        }
        $thumbnailName = 'thumbnail.png';
        $base64Content = base64_encode(file_get_contents($fileNameWithPath));

        $result = new ThumbnailGeneratorResponse();
        $result->setName($thumbnailName);
        $result->setDataUri('data:image/png;base64,'.$base64Content);

        $thumbnailsService->deleteThumbnail($fileNameWithPath);

        $view = $this->view($result, Response::HTTP_CREATED);

        return $this->handleView($view);
    }
}
