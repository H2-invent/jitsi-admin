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

        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE server RENAME COLUMN api_key_open_ai TO api_key_transcription');
        } elseif ($platform instanceof MySQLPlatform) {
            $this->addSql('ALTER TABLE server CHANGE api_key_open_ai api_key_transcription VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('server')
            ->dropColumn('transcription_provider')
        ;

        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE server RENAME COLUMN api_key_transcription TO api_key_open_ai');
        } elseif ($platform instanceof MySQLPlatform) {
            $this->addSql('ALTER TABLE server CHANGE api_key_transcription api_key_open_ai VARCHAR(255) DEFAULT NULL');
        }
    }
}
