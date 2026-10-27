<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204113716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->addIndex(['uid_real'], 'uid_real_idx')
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->dropIndex('uid_real_idx')
        ;
    }
}
