<?php

namespace KCore\DocumentAPIBundle\Tests\Controller;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\DocumentAPIBundle\Entity\Document;

/**
 * @group solr
 */
class DefaultControllerACLTest extends BaseDocumentAPITestClass
{
    /**
     * Clear all documents from the Index.
     */
    public function setUp()
    {
        self::$documentService->deleteAllDocumentDescriptors();
    }

    public static function generateDocumentWithPrivateVisibility($institutionId, $alternate = true)
    {
        self::init();
        $fileDocs = self::getphysicalDocs('text/plain');
        if ($alternate) {
            return self::generateAlteredDocument($institutionId, self::$PRIVATE, $fileDocs);
        }

        return self::generateDocument($institutionId, self::$PRIVATE, $fileDocs);
    }
    public static function generateDocumentWithPublicVisibility($institutionId, $alternate = true)
    {
        self::init();
        $fileDocs = self::getphysicalDocs('text/plain');
        if ($alternate) {
            return self::generateAlteredDocument($institutionId, self::$PUBLIC, $fileDocs);
        }

        return self::generateDocument($institutionId, self::$PUBLIC, $fileDocs);
    }

    public static function generateAlteredDocument($institutionId, $visibility, $fileDocs) {
        $document = self::generateDocument($institutionId, $visibility, $fileDocs);

        $descriptor = $document->getDocumentDescriptor();

        // The document are created with actual files and the HASH is computed accordingly.
        // We must ensure that the hash is computed on the current "contents"
        $originalHash = hash(self::$hashAlgorithm, $descriptor->getContents());
        $descriptor->setHash($originalHash);

        $document->setDocumentDescriptor($descriptor);
        return $document;
    }

    /**
     * @return array
     */
    public function dataProviderPost()
    {
        return [
            // Admin
            [201, self::generateDocumentWithPublicVisibility('fakeAnyInstitution', false),  'admin@test.org'],
            [201, self::generateDocumentWithPrivateVisibility('fakeAnyInstitution', false), 'admin@test.org'],
            [201, self::generateDocumentWithPublicVisibility('institutionTestLocal', false), 'admin@test.org'],
            [201, self::generateDocumentWithPrivateVisibility('institutionTestLocal', false), 'admin@test.org'],
            [201, self::generateDocumentWithPublicVisibility('institutionTestExt', false), 'admin@test.org'],
            [201, self::generateDocumentWithPrivateVisibility('institutionTestExt', false), 'admin@test.org'],

            // Local DMS
            [201, self::generateDocumentWithPublicVisibility('institutionTestLocal', false),  'dms@local.org'],
            [201, self::generateDocumentWithPrivateVisibility('institutionTestLocal', false), 'dms@local.org'],
            [403, self::generateDocumentWithPublicVisibility('institutionTestExt', false),    'dms@local.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt', false),   'dms@local.org'],

            // Local Adapter
            [201, self::generateDocumentWithPublicVisibility('institutionTestLocal', false),  'adapter@local.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal', false), 'adapter@local.org'],
            [403, self::generateDocumentWithPublicVisibility('institutionTestExt', false),    'adapter@local.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt', false),   'adapter@local.org'],

            // External DMS
            [403, self::generateDocumentWithPublicVisibility('institutionTestLocal', false),  'dms@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal', false), 'dms@ext.org'],
            [201, self::generateDocumentWithPublicVisibility('institutionTestExt', false),  'dms@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt', false), 'dms@ext.org'],

            // External Adapter
            [403, self::generateDocumentWithPublicVisibility('institutionTestLocal', false),  'adapter@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal', false), 'adapter@ext.org'],
            [201, self::generateDocumentWithPublicVisibility('institutionTestExt', false),  'adapter@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt', false), 'adapter@ext.org'],
        ];
    }

    /**
     * @dataProvider dataProviderPost
     * @group post
     *
     * @param $statusCode
     * @param \KCore\DocumentAPIBundle\Entity\Document $document
     * @param $user
     * @param string $password
     */
    public function testACLPostDocument($statusCode, Document $document, $user, $password = 'test')
    {
        $response = $this->doPostDocument($document, $user, $password);
        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function dataProviderGet()
    {
        return [
            // Admin
          [200, self::generateDocumentWithPublicVisibility('fakeAnyInstitution'),  'admin@test.org'],
          [200, self::generateDocumentWithPrivateVisibility('fakeAnyInstitution'), 'admin@test.org'],
          [200, self::generateDocumentWithPublicVisibility('institutionTestLocal'), 'admin@test.org'],
          [200, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'admin@test.org'],
          [200, self::generateDocumentWithPublicVisibility('institutionTestExt'), 'admin@test.org'],
          [200, self::generateDocumentWithPrivateVisibility('institutionTestExt'), 'admin@test.org'],

            // Local DMS
          [200, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'dms@local.org'],
          [200, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'dms@local.org'],
          [200, self::generateDocumentWithPublicVisibility('institutionTestExt'),    'dms@local.org'],
          [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'),   'dms@local.org'],

            // Local Adapter
          [200, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'adapter@local.org'],
          [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'adapter@local.org'],
          [200, self::generateDocumentWithPublicVisibility('institutionTestExt'),    'adapter@local.org'],
          [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'),   'adapter@local.org'],

            // External DMS
          [200, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'dms@ext.org'],
          [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'dms@ext.org'],
          [200, self::generateDocumentWithPublicVisibility('institutionTestExt'),  'dms@ext.org'],
          [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'), 'dms@ext.org'],

            // External Adapter
          [200, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'adapter@ext.org'],
          [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'adapter@ext.org'],
          [200, self::generateDocumentWithPublicVisibility('institutionTestExt'),  'adapter@ext.org'],
          [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'), 'adapter@ext.org'],
        ];
    }

    /**
     * @dataProvider dataProviderGet
     * @group get
     *
     * @param $statusCode
     * @param Document $document
     * @param $user
     * @param string $password
     */
    public function testACLGetDocument($statusCode, Document $document, $user, $password = 'test') {
        // First: add the document, as if its already in the index (don't check for permissions)
        self::$documentService->indexDocumentDescriptor(
          $document->getDocumentDescriptor()
        );

        $descriptor = $document->getDocumentDescriptor();

        $response = $this->doGetDocumentDescriptor(
          $descriptor->getVisibility(),
          $descriptor->getInstitutionId(),
          $descriptor->getLocalDocumentId(),
          $user,
          $password
        );

        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function dataProviderDelete()
    {
        return [
            // Admin
            [204, self::generateDocumentWithPublicVisibility('fakeAnyInstitution'), 'admin@test.org'],
            [204, self::generateDocumentWithPrivateVisibility('fakeAnyInstitution'), 'admin@test.org'],
            [204, self::generateDocumentWithPublicVisibility('institutionTestLocal'), 'admin@test.org'],
            [204, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'admin@test.org'],
            [204, self::generateDocumentWithPublicVisibility('institutionTestExt'), 'admin@test.org'],
            [204, self::generateDocumentWithPrivateVisibility('institutionTestExt'), 'admin@test.org'],

            // Local DMS
            [204, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'dms@local.org'],
            [204, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'dms@local.org'],
            [403, self::generateDocumentWithPublicVisibility('institutionTestExt'),    'dms@local.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'),   'dms@local.org'],

            // Local Adapter
            [204, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'adapter@local.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'adapter@local.org'],
            [403, self::generateDocumentWithPublicVisibility('institutionTestExt'),    'adapter@local.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'),   'adapter@local.org'],

            // External DMS
            [403, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'dms@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'dms@ext.org'],
            [204, self::generateDocumentWithPublicVisibility('institutionTestExt'),  'dms@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'), 'dms@ext.org'],

            // External Adapter
            [403, self::generateDocumentWithPublicVisibility('institutionTestLocal'),  'adapter@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestLocal'), 'adapter@ext.org'],
            [204, self::generateDocumentWithPublicVisibility('institutionTestExt'),  'adapter@ext.org'],
            [403, self::generateDocumentWithPrivateVisibility('institutionTestExt'), 'adapter@ext.org'],
        ];
    }

    /**
     * @dataProvider dataProviderDelete
     * @group get
     *
     * @param $statusCode
     * @param Document $document
     * @param $user
     * @param string $password
     */
    public function testACLDeleteDocument($statusCode, Document $document, $user, $password = 'test') {
        // First: add the document, as if its already in the index (don't check for permissions)
        self::$documentService->indexDocumentDescriptor(
            $document->getDocumentDescriptor()
        );

        $descriptor = $document->getDocumentDescriptor();

        $response = $this->doDeleteDocumentDescriptor(
            $descriptor->getVisibility(),
            $descriptor->getInstitutionId(),
            $descriptor->getLocalDocumentId(),
            $user,
            $password
        );

        $this->assertEquals($statusCode, $response->getStatusCode());
    }
}
