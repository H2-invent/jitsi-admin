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
        // 0 on MySQL/MariaDB, false on PostgreSQL (boolean column)
        $false = $this->connection->getDatabasePlatform()->convertBooleans(false);
        $this->addSql(sprintf('UPDATE rooms SET enable_transcription = %s WHERE enable_transcription IS NULL', $false));
        $this->addSql(sprintf('UPDATE server SET enable_transcription = %s WHERE enable_transcription IS NULL', $false));

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
