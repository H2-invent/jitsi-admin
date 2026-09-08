<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717083200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->addColumn('enable_transcription', 'boolean')
            ->setNotnull(false)
            ->setDefault(null)
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->dropColumn('enable_transcription')
        ;
    }
}
