<?php

namespace App\Service;

use App\Entity\BaseSolrEntity;
use App\Entity\SolrEntity;
use App\Exception\InternalSearchException;
use App\Exception\ResourceNotFoundException;
use Solarium\Client;
use Solarium\QueryType\Select\Query\FilterQuery;
use Symfony\Component\Finder\SplFileInfo;

class SolrService
{
    const DATA_TEXTUAL_DYNAMIC_FIELD_NAME = 'str_ss_file_content';

    /**
     * @var Client
     */
    private $solrClient;

    public function __construct(Client $solrClient)
    {
        $this->solrClient = $solrClient;
    }

    public function add(SolrEntity $solrEntity)
    {
        $update = $this->solrClient->createUpdate();
        $update->addDocument($solrEntity->getSolrDocument());
        $update->addCommit();

        return $this->solrClient->update($update);
    }

    public function get(string $entityType, string $id, string $solrEntityClass)
    {
        if (!is_a($solrEntityClass, SolrEntity::class, true)) {
            throw new \RuntimeException(sprintf('Wrong class name for Solr entity fetching, %s given', $solrEntityClass));
        }

        $select = $this->solrClient->createSelect();
        $select
            ->setStart(0)
            ->setRows(1)
            ->setQuery(BaseSolrEntity::FIELD_ENTITY_ID.':"'.$id.'"');

        $filterQuery = new FilterQuery(['key' => 'entity-filter']);
        $filterQuery->setQuery(BaseSolrEntity::FIELD_ENTITY_TYPE.':"'.$entityType.'"');
        $select->addFilterQueries([$filterQuery]);

        $resultSet = $this->solrClient->select($select);

        if ($resultSet->count() !== 1) {
            throw new ResourceNotFoundException(sprintf('Resource %s::%s not found!', $entityType, $id));
        }

        // Building the required SolrEntity object
        return new $solrEntityClass($id, $resultSet->getIterator()[0]);
    }

    /**
     * Deletes an entity from the index given the type and the ID.
     *
     * @param string $entityType The entity type
     * @param string $id         The entity id
     *
     * @throws InternalSearchException
     *
     * @return bool
     */
    public function delete(string $entityType, string $id): bool
    {
        $update = $this->solrClient->createUpdate();
        $query = ' +'.BaseSolrEntity::FIELD_ENTITY_TYPE.':"'.$entityType.'"'.
                 ' +'.BaseSolrEntity::FIELD_ENTITY_ID.':"'.$id.'"'
        ;

        $update->addDeleteQuery($query);
        $update->addCommit();

        try {
            $result = $this->solrClient->update($update);

            return 0 === $result->getStatus();
        } catch (\Throwable $e) {
            throw new InternalSearchException(
                sprintf('Error while deleting from Index, type=%s, id=%s', $entityType, $id),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Add an entity to the index, with text extraction from a file.
     *
     * @param SolrEntity  $entity   The entity to add to the index
     * @param SplFileInfo $fileInfo The file to be used to extract the contents from
     *
     * @throws \Exception
     * @throws \Throwable
     *
     * @return bool
     */
    public function addWithTextExtraction(SolrEntity $entity, \SplFileInfo $fileInfo)
    {
        $extract = $this->solrClient->createExtract();
        $extract->setFile($fileInfo->getRealPath());
        $extract->setFieldMappings(['content' => $entity->getContentsField()]);
        $extract->setDocument($entity->getSolrDocument());

        // Adding extra attributes to store the extracted data
        // $extract->setUprefix('str_sm_doc_attributes_');
        $extract->setCommit(true);

        try {
            $this->solrClient->update($extract);

            return true;
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), '.PDFParser') !== false) {
                throw new \Exception('PDF Parsing Exception', 500, $e);
            }
            throw $e;
        }
    }
}
