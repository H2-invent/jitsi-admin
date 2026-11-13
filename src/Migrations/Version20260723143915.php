<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723143915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE rooms SET created_at = NOW() WHERE created_at IS NULL');

        $schema->getTable('rooms')
            ->modifyColumn('created_at', [
            'notnull' => true,
        ]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->modifyColumn('created_at', [
                'notnull' => false,
            ]);
    }
}
