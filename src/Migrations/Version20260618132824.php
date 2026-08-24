<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618132824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('server');
        $table->addColumn('api_key_open_ai', Types::STRING)
            ->setLength(255)
            ->setNotnull(false)
        ;
        $table->addColumn('enable_transcription', Types::BOOLEAN)
            ->setNotnull(false)
        ;
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('server');
        $table->dropColumn('api_key_open_ai');
        $table->dropColumn('enable_transcription');
    }
}
