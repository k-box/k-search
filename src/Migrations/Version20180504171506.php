<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create the DataProcessingStatus table.
 */
final class Version20180504171506 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE data_processing_status ('.
            'id CHAR(36) NOT NULL --(DC2Type:uuid)
            , request_id VARCHAR(255) NOT NULL,'.
            ' added_at DATETIME NOT NULL,'.
            ' status VARCHAR(50) NOT NULL,'.
            ' data BLOB NOT NULL,'.
            ' message VARCHAR(255) DEFAULT NULL,'.
            ' PRIMARY KEY(id)'.
            ')');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE data_processing_status');
    }
}
