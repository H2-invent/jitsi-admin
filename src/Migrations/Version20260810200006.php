<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810200006 extends AbstractMigration
{
    private const TABLE_NAME = 'keycloak_groups_to_servers';
    private const INDEX_NAME = 'idx_kctg_keycloak_group';

    public function getDescription(): string
    {
        return 'Create index on keycloak_groups_to_servers.keycloak_group for ServerUserManagment queries';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addIndex(['keycloak_group'], self::INDEX_NAME);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropIndex(self::INDEX_NAME);
    }
}
