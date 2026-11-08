<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814092541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Bound as a typed boolean so Doctrine converts it per platform (0 on MySQL/MariaDB, false on PostgreSQL)
        $this->addSql('UPDATE rooms SET enable_transcription = ? WHERE enable_transcription IS NULL', [false], [Types::BOOLEAN]);
        $this->addSql('UPDATE server SET enable_transcription = ? WHERE enable_transcription IS NULL', [false], [Types::BOOLEAN]);

        $schema->getTable('rooms')
            ->modifyColumn('enable_transcription', [
                'default' => false,
                'Notnull' => true,
            ])
        ;
        $schema->getTable('server')
            ->modifyColumn('enable_transcription', [
                'default' => false,
                'Notnull' => true,
            ])
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->modifyColumn('enable_transcription', [
                'default' => null,
                'Notnull' => false,
            ])
        ;
        $schema->getTable('server')
            ->modifyColumn('enable_transcription', [
                'default' => null,
                'Notnull' => false,
            ])
        ;
    }
}
