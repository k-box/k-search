<?php

namespace KCore\ThumbnailsAPIBundle\Controller;

use JMS\Serializer\Serializer;
use KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest;
use KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorResponse;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use KCore\ThumbnailsAPIBundle\Services\ThumbnailsService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

//TODO: use File instead of readfile?
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class DefaultController extends FOSRestController
{

    /**
     * Get the thumbnail for the specified document.
     *
     * @ApiDoc(
     *      resource = true,
     *      description = "Retrieves a thumbnail for the document identified by the given pair <InstitutionID, DocumentDescriptorID>",
     *      output = "image/png",
     *      statusCodes = {
     *          200 = "Returned when successful",
     *          404 = "Returned when the document is not found"
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param $institutionId
     * @param $localDocumentId
     *
     * @return image
     *
     * @throws NotFoundHttpException when the DocumentDescriptor is not found
     *
     * @todo Implement the HTTP-401 Response for "Not Authorized"
     */
    public function getThumbnailAction($institutionId, $localDocumentId) {

        /** @var ThumbnailsService $thumbnailsService */
        $thumbnailsService = $this->get('klink.thumbnails.service');
        
        if ($institutionId == NULL || trim($institutionId) == "") {            
            throw new BadRequestHttpException('Bad request: InstitutionID has a wrong format: '. $institutionId);
            
        } else if ($localDocumentId == NULL || trim($localDocumentId) == "") {
            throw new BadRequestHttpException('Bad request: DocumentID has a wrong format: '. $localDocumentId);
            
        } else if ( ! $thumbnailsService->thumbnailExists($localDocumentId, $institutionId) ) {
            if ($thumbnailsService->thumbnailIsProcessing($localDocumentId, $institutionId)) {
                throw new HttpException(102, sprintf('The thumbnail for resource \'%s:%s\' is being generated.', $institutionId, $localDocumentId));
            } else {   
                throw new NotFoundHttpException(sprintf('The thumbnail for resource with InstitutionID: \'%s\' and DocumentID:\'%s\' was not found.', $institutionId, $localDocumentId));
            }
        } else {
            $fileNameWithPath = $thumbnailsService->getThumbnailFilenameByIDs($localDocumentId, $institutionId);
            $thumbnailName = $localDocumentId . "_" . $institutionId . "_thumbnail.png";
            $base64Content = base64_encode(file_get_contents($fileNameWithPath));
                                   
            $result = new ThumbnailGeneratorResponse();
            $result->setName($thumbnailName);
            $result->setDataUri("data:image/png;base64," . $base64Content);

            $thumbnailsService->deleteThumbnail($fileNameWithPath);
                
            $view = $this->view($result, 200);
            return $this->handleView($view);
        }
    }
    
    /**
     * Generate a thumbnail for the specified document.
     *
     * @ApiDoc(
     *      description = "Generates a thumbnail for the specified document",
     *      input = "KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest",
     *      statusCodes = {
     *          201 = "Returned when successfully created",
     *          400 = "Returned when a wrong data format has been received",
     *          401 = "Returned when the invocation is Not Authorized",
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws InternalErrorException
     *
     * @todo: implement the "401 - Not Authorized" check and response
     */
     
    public function generateThumbnailAction(Request $request)
    {       
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

        if (count($errors) >0) {
            $message = array();
            /** @var ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $message[] = $error->getPropertyPath();
            }
            throw new BadRequestHttpException('Wrong format: '.implode('|', $message));
        }

        /** @var ThumbnailsService $thumbnailsService */
        $thumbnailsService = $this->get('klink.thumbnails.service');

        $fileNameWithPath = $thumbnailsService->generateThumbnailFromGeneratorRequest($thumbnailGeneratorRequest);
        $thumbnailName = "thumbnail.png";
        $base64Content = base64_encode(file_get_contents($fileNameWithPath));

        $result = new ThumbnailGeneratorResponse();
        $result->setName($thumbnailName);
        $result->setDataUri("data:image/png;base64," . $base64Content);
    
        $thumbnailsService->deleteThumbnail($fileNameWithPath);
    
        $view = $this->view($result, 201);
        return $this->handleView($view);
    }
}
