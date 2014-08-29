<?php

namespace KCore\DocumentAPIBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\Serializer;
use KCore\DocumentAPIBundle\Entity\Document;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use KCore\CoreBundle\Services\DocumentService;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator;


class DefaultController extends FOSRestController
{
    /**
     * Get single DocumentDescriptor.
     *
     * @param $institutionId
     * @param $localDocumentId
     *
     * @ApiDoc(
     *      resource = true,
     *      description = "Retrieves a Document Descriptor by the given pair <InstitutionID, DocumentDescriptorID>",
     *      output = "KCore\CoreBundle\Entity\DocumentDescriptor",
     *      statusCodes = {
     *          200 = "Returned when successful",
     *          404 = "Returned when the document is not found"
     *      }
     * )
     *
     * @Rest\View()
     *
     * @return array
     *
     */
    public function getDocumentDescriptorAction($institutionId, $localDocumentId) {

        /** @var DocumentService $documentService */
        $documentService = $this->get('klink.document.service');

        $documentDesc = $documentService->getDocumentDescriptor($institutionId, $localDocumentId);

        if (!$documentDesc) {
            throw new NotFoundHttpException(sprintf('The resource \'%s:%s\' was not found.', $institutionId, $localDocumentId));
        }

        $view = $this->view($documentDesc);
        return $this->handleView($view);
    }

    /**
     * Delete a single DocumentDescriptor.
     *
     * @ApiDoc(
     *      resource = true,
     *      description = "Deletes a Document Descriptor by the given pair <InstitutionID, DocumentDescriptorID>",
     *      statusCodes = {
     *          204 = "Returned when successful",
     *          401 = "Returned when the invocation is Not Authorized",
     *          404 = "Returned when the DocumentDescriptor is not found"
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param string $institutionId the InstitutionID
     * @param string $localDocumentId the LocalDocumentId
     *
     * @return array
     *
     * @throws NotFoundHttpException when institution does not exist
     * @throws InternalErrorException
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function deleteDocumentDescriptorAction($institutionId, $localDocumentId) {

        /** @var DocumentService $documentService */
        $documentService = $this->get('klink.document.service');

        $documentDesc = $documentService->getDocumentDescriptor($institutionId, $localDocumentId);

        if (!$documentDesc) {
            throw new NotFoundHttpException();
        }

        $result = $documentService->deleteDocumentDescriptor($documentDesc);

        if ($result->getStatus() != 0 || $result->getResponse()->getStatusCode() != 200) {
            throw new InternalErrorException($result->getResponse()->getStatusMessage());
        }

        $view = $this->view(null, 204);
        return $this->handleView($view);
    }
    
    /**
     * Post a new Document.
     *
     * @ApiDoc(
     *      description = "Creates a new Document",
     *      input = "KCore\DocumentAPIBundle\Entity\Document",
     *      statusCodes = {
     *          201 = "Returned when successfully created",
     *          401 = "Returned when a wrong data format has been received",
     *          400 = "Returned when the invocation is Not Authorized",
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param Request $request
     *
     * @throws InternalErrorException
     * @return array
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function postDocumentAction(Request $request)
    {
        $content = $request->getContent();

        if (empty($content)) {
            throw new BadRequestHttpException('Wrong format: Empty Contents');
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        /** @var Document $document */
        $document = $serializer->deserialize($content, 'KCore\DocumentAPIBundle\Entity\Document', 'json');

        $documentDescriptor = $document->getDocumentDescriptor();

        /** @var Validator\RecursiveValidator $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($documentDescriptor);

        if (count($errors) >0) {
            $message = array();
            /** @var ConstraintViolationInterface $error */
            foreach($errors as $error) {
                $message[] = $error->getPropertyPath();
            }
            throw new BadRequestHttpException('Wrong format: ' . implode('|' , $message));
        }

        /** @var DocumentService $documentService */
        $documentService = $this->get('klink.document.service');

        $fileContents = base64_decode($document->getDocumentData());
        $file = null;

        if ($documentDescriptor->getMimeType() != 'text/plain') {
            $tFs = TemporaryFilesystem::create();
            $tmpFile = $tFs->createTemporaryFile();
            file_put_contents($tmpFile, $fileContents);
            $file = new File($tmpFile);
        }
        else {
            $documentDescriptor->setContents($fileContents);
        }

        $result = $documentService->indexDocumentDescriptor($documentDescriptor, $file);

        if ($result->getStatus() != 0) {
            throw new InternalErrorException($result->getResponse()->getStatusMessage());
        }

        $newDocumentDescriptor = $documentService->getDocumentDescriptorById($documentDescriptor->getId());

        $view = $this->view($newDocumentDescriptor, 201);
        $view->setLocation($this->generateUrl('document_api_descriptor_get', array(
            'institutionId' => $newDocumentDescriptor->getInstitutionId(),
            'localDocumentId' => $newDocumentDescriptor->getLocalDocumentId()
        )));

        return $this->handleView($view);
    }
}
