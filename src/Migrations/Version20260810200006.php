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
        $this->addSql(
            sprintf(
                'CREATE INDEX %s ON %s (%s(128))',
                self::INDEX_NAME,
                self::TABLE_NAME,
                'keycloak_group'
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'DROP INDEX %s ON %s',
                self::INDEX_NAME,
                self::TABLE_NAME
            )
        );
    }
}
