<?php

namespace App\Repository;

use App\Entity\DataProcessingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DataProcessingStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method DataProcessingStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method DataProcessingStatus[]    findAll()
 * @method DataProcessingStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DataProcessingStatusRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DataProcessingStatus::class);
    }

    public function updateStatusByCriteria(array $criteria, string $status, string $message): ?DataProcessingStatus
    {
        return $this->_em->transactional(function (EntityManagerInterface $em) use ($criteria, $status, $message) {
            $statusEntity = $this->findOneBy($criteria);

            if (!$statusEntity) {
                return false;
            }

            // Lock the entity for updating
            $em->lock($statusEntity, LockMode::PESSIMISTIC_WRITE);
            $statusEntity
                ->setStatus($status)
                ->setMessage($message);
            $em->persist($statusEntity);

            return $statusEntity;
        });
    }

    public function createOrUpdate(DataProcessingStatus $dataProcessingStatus): void
    {
        $this->_em->transactional(function (EntityManagerInterface $em) use ($dataProcessingStatus) {
            // Lock the entity for updating
            $status = $this->find($dataProcessingStatus->getDataUuid(), LockMode::PESSIMISTIC_WRITE);

            if ($status) {
                $status
                    ->setData($dataProcessingStatus->getData())
                    ->setStatus($dataProcessingStatus->getStatus())
                    ->setMessage($dataProcessingStatus->getMessage())
                    ->setAddedAt($dataProcessingStatus->getAddedAt())
                    ->setRequestId($dataProcessingStatus->getRequestId())
                ;
            } else {
                $status = $dataProcessingStatus;
            }

            $em->persist($status);
        });
    }

    public function delete($uuid): void
    {
        $this->_em->transactional(function (EntityManagerInterface $em) use ($uuid) {
            // Lock the entity for deletion, do not let other workers to read the stale status
            $status = $this->find($uuid, LockMode::PESSIMISTIC_WRITE);
            if (!$status) {
                return;
            }

            $em->remove($status);
        });
    }

    public function deleteOneByCriteria(array $criteria)
    {
        return $this->_em->transactional(function (EntityManagerInterface $em) use ($criteria) {
            $statusEntity = $this->findOneBy($criteria);

            if (!$statusEntity) {
                return false;
            }

            // Lock the entity for updating
            $em->lock($statusEntity, LockMode::PESSIMISTIC_WRITE);
            $em->remove($statusEntity);
        });
    }
}
