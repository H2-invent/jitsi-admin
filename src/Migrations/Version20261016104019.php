<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261016104019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'adds timestamps to ProvisionerRecord';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('provisioner_record');
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE)
            ->setNotnull(true)
        ;
        $table->addColumn('updated_at', Types::DATETIME_IMMUTABLE)
            ->setNotnull(true)
        ;
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('provisioner_record');
        $table->dropColumn('created_at');
        $table->dropColumn('updated_at');
    }
}
