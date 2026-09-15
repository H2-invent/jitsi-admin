<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810200000 extends AbstractMigration
{
    private const TABLE_NAME = 'keycloak_groups_to_servers';
    private const COLUMN_NAME = 'keycloak_group';

    public function getDescription(): string
    {
        return 'Change keycloak_groups_to_servers.keycloak_group from TEXT to VARCHAR(255)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'ALTER TABLE %s MODIFY %s VARCHAR(255) NOT NULL',
                self::TABLE_NAME,
                self::COLUMN_NAME
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'ALTER TABLE %s MODIFY %s TEXT NOT NULL',
                self::TABLE_NAME,
                self::COLUMN_NAME
            )
        );
    }
}
