<?php

namespace App\Controller;

use App\Entity\DocumentDescriptor;
use App\Model\Document;
use App\Service\DocumentService;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator;

class DocumentController extends Controller
{
    /**
     * Delete a single DocumentDescriptor.
     *
     * @ ApiDoc(
     *      section="Document Descriptor",
     *      resource = true,
     *      authentication = true,
     *      description = "Deletes a Document Descriptor by the given pair <InstitutionID, DocumentDescriptorID> and its <visibility>",
     *      statusCodes = {
     *          204 = "Returned when successful",
     *          404 = "Returned when the DocumentDescriptor is not found"
     *      }
     * )
     *
     * @param string $visibility      The Document visibility
     * @param string $institutionId   The InstitutionID
     * @param string $localDocumentId The LocalDocumentId
     *
     * @throws NotFoundHttpException when the DocumentDescriptor does not exist
     * @throws \RuntimeException     when something went wrong with SOLR
     *
     * @return Response
     */
    public function deleteDocumentDescriptorAction($visibility, $institutionId, $localDocumentId)
    {
        /** @var DocumentService $documentService */
        $documentService = $this->get('ksearch.document.service');

        try {
            $documentDescriptor = $documentService->getDocumentDescriptor($institutionId, $localDocumentId, $visibility);
        } catch (WrongCoreException $e) {
            throw new BadRequestHttpException('This instance does not support the given visibility', $e);
        }

        if (!$documentDescriptor) {
            throw new NotFoundHttpException(sprintf('Document "%s:%s:%s" not found', $institutionId, $visibility, $localDocumentId));
        }

        // authorization
        if (false === $this->isGranted('delete', $documentDescriptor)) {
            throw new AccessDeniedException();
        }

        $result = $documentService->deleteDocumentDescriptor($documentDescriptor);

        if (0 !== $result->getStatus() || 200 !== (int) $result->getResponse()->getStatusCode()) {
            throw new \RuntimeException($result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode());
        }

        $view = $this->view(null, 204);

        return $this->handleView($view);
    }

    /**
     * Get a single DocumentDescriptor.
     *
     * @ ApiDoc(
     *      section="Document Descriptor",
     *      resource = true,
     *      authentication = true,
     *      description = "Retrieves a Document Descriptor by the given pair <InstitutionID, DocumentDescriptorID> and the its <visibility>",
     *      output = "App\Entity\DocumentDescriptor",
     *      statusCodes = {
     *          200 = "Returned when successful",
     *          404 = "Returned when the document is not found"
     *      }
     * )
     *
     * @param string $visibility      The Document visibility
     * @param string $institutionId   The InstitutionID
     * @param string $localDocumentId The LocalDocumentId
     *
     * @throws NotFoundHttpException when the DocumentDescriptor is not found
     *
     * @return Response
     */
    public function getDocumentDescriptorAction($visibility, $institutionId, $localDocumentId)
    {
        /** @var DocumentService $documentService */
        $documentService = $this->get('ksearch.document.service');

        try {
            $documentDescriptor = $documentService->getDocumentDescriptor($institutionId, $localDocumentId, $visibility);
        } catch (WrongCoreException $e) {
            throw new BadRequestHttpException('This instance does not support the given visibility.', $e);
        }

        if (!$documentDescriptor) {
            throw new NotFoundHttpException(sprintf('The resource \'%s:%s\' was not found.', $institutionId, $localDocumentId));
        }

        // authorization
        if (false === $this->isGranted('get', $documentDescriptor)) {
            throw new AccessDeniedException();
        }

        $view = $this->view($documentDescriptor);

        return $this->handleView($view);
    }

    /**
     * Post a new Document.
     *
     * @ ApiDoc(
     *      section="Document Descriptor",
     *      description = "Creates a new Document",
     *      authentication = true,
     *      input = "App\Entity\Document",
     *      statusCodes = {
     *          201 = "Returned when successfully created",
     *          401 = "Returned when a wrong data format has been received",
     *          400 = "Returned when the invocation is Not Authorized",
     *      }
     * )
     *
     * @param Request $request
     *
     * @throws \RuntimeException
     *
     * @return Response
     */
    public function postDocumentAction(Request $request)
    {
        $content = $request->getContent();

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        try {
            /** @var Document $document */
            $document = $serializer->deserialize($content, Document::class, 'json');
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        /** @var DocumentDescriptor $documentDescriptor */
        $documentDescriptor = $document->getDocumentDescriptor();

        if (!$documentDescriptor) {
            throw new BadRequestHttpException('Wrong format: Empty Contents');
        }

        /** @var Validator\RecursiveValidator $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($documentDescriptor);

        if (count($errors) > 0) {
            $message = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $message[] = $error->getPropertyPath();
            }
            throw new BadRequestHttpException('Wrong format in the following fields: '.implode('|', $message));
        }

        /** @var DocumentService $documentService */
        $documentService = $this->get('ksearch.document.service');

        if ($documentService->getEnabledCoreVisibility() !== $documentDescriptor->getVisibility()) {
            throw new BadRequestHttpException(sprintf('This instance does not support the given "%s" visibility', $documentDescriptor->getVisibility()));
        }

        // authorization
        if (false === $this->isGranted('post', $documentDescriptor)) {
            throw new AccessDeniedException();
        }

        $fileContents = base64_decode($document->getDocumentData(), true);
        $file = null;

        $tempFileName = null;

        if ('text/plain' !== $documentDescriptor->getMimeType()) {
            $tempFileName = $this->getTemporaryFile();
            file_put_contents($tempFileName, $fileContents);
            $file = new File($tempFileName);
        } else {
            $documentDescriptor->setContents($fileContents);
        }

        $result = null;
        try {
            $result = $documentService->indexDocumentDescriptor($documentDescriptor, $file);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException('Wrong data:'.$e->getMessage(), $e);
        } catch (\Exception $e) {
            // Remove the temporary file, if any
            $this->removeTemporaryFile($tempFileName);
            throw new \RuntimeException($e->getMessage());
        }
        if (0 !== (int) $result->getStatus()) {
            // Remove the temporary file, if any
            $this->removeTemporaryFile($tempFileName);
            throw new \RuntimeException($result->getResponse()->getStatusMessage(), $result->getStatus());
        }

        // Remove the temporary file
        $this->removeTemporaryFile($tempFileName);

        $newDocumentDescriptor = $documentService->getDocumentDescriptorById(
            $documentDescriptor->getId(),
            $documentDescriptor->getVisibility()
        );

        // @todo The DocumentDescriptor may be not available in the Index due to an error. A check must be added.
        $view = $this->view($newDocumentDescriptor, 201);
        $view->setLocation($this->generateUrl('document_api_descriptor_get', [
            'visibility' => $newDocumentDescriptor->getVisibility(),
            'institutionId' => $newDocumentDescriptor->getInstitutionId(),
            'localDocumentId' => $newDocumentDescriptor->getLocalDocumentId(),
        ]));

        return $this->handleView($view);
    }

    /**
     * Generates a temporary filename.
     *
     * @return string
     */
    protected function getTemporaryFile()
    {
        $fs = new Filesystem();

        return $fs->tempnam(sys_get_temp_dir(), 'document');
    }

    /**
     * Removes, if exists and is writable, the given filename.
     *
     * @param string $filename The filename to remove
     *
     * @return bool
     */
    protected function removeTemporaryFile($filename)
    {
        if (empty($filename) || !file_exists($filename) || !is_writable($filename)) {
            return false;
        }

        return unlink($filename);
    }
}
