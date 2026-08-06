<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260724132645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('server');
        $table->addColumn('transcription_provider', Types::STRING)
            ->setNotnull(false)
            ->setDefault(null)
        ;

        $this->addSql('ALTER TABLE server RENAME COLUMN api_key_open_ai TO api_key_transcription');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('server')
            ->dropColumn('transcription_provider')
        ;

        $this->addSql('ALTER TABLE server RENAME COLUMN api_key_transcription TO api_key_open_ai');
    }
}
