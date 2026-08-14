<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
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
        $this->addSql('UPDATE rooms SET enable_transcription = 0 WHERE enable_transcription IS NULL');

        $schema->getTable('rooms')
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
    }
}
