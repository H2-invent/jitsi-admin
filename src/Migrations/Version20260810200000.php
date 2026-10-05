<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
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
        // The schema API lets Doctrine generate the platform specific ALTER statement (MySQL, MariaDB, PostgreSQL)
        $schema->getTable(self::TABLE_NAME)
            ->modifyColumn(self::COLUMN_NAME, [
                'type' => Type::getType(Types::STRING),
                'length' => 255,
                'notnull' => true,
            ])
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable(self::TABLE_NAME)
            ->modifyColumn(self::COLUMN_NAME, [
                'type' => Type::getType(Types::TEXT),
                'length' => null,
                'notnull' => true,
            ])
        ;
    }
}
