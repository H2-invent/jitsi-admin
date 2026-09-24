<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260727000000 extends AbstractMigration
{
    private const string TABLE_USER = 'fos_user';
    private const string TABLE_REPEAT = '`repeat`';

    public function getDescription(): string
    {
        return 'Change keycloakGroup, spezial_properties, and weekday columns from TEXT to JSON';
    }

    public function up(Schema $schema): void
    {
        $jsonType = Type::getType(Types::JSON);

        $userTable = $schema->getTable(self::TABLE_USER);
        $userTable->modifyColumn('keycloakGroup', ['Type' => $jsonType, 'NotNull' => false]);
        $userTable->modifyColumn('spezial_properties', ['Type' => $jsonType, 'NotNull' => false]);

        $repeatTable = $schema->getTable(self::TABLE_REPEAT);
        $repeatTable->modifyColumn('weekday', ['Type' => $jsonType, 'NotNull' => true]);
    }

    public function down(Schema $schema): void
    {
        $textType = Type::getType(Types::TEXT);

        $userTable = $schema->getTable(self::TABLE_USER);
        $userTable->modifyColumn('keycloakGroup', ['Type' => $textType, 'NotNull' => false]);
        $userTable->modifyColumn('spezial_properties', ['Type' => $textType, 'NotNull' => false]);

        $repeatTable = $schema->getTable(self::TABLE_REPEAT);
        $repeatTable->modifyColumn('weekday', ['Type' => $textType, 'NotNull' => true]);
    }
}
