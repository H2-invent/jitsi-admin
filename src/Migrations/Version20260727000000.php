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
        // The data has to be JSON before the column type changes.
        $this->convertColumnToJson(self::TABLE_USER, 'keycloakGroup', true);
        $this->convertColumnToJson(self::TABLE_USER, 'spezial_properties', true);
        $this->convertColumnToJson(self::TABLE_REPEAT, 'weekday', false);

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

    /**
     * Converts PHP-serialized values (DC2Type:array) to JSON. Values that are already
     * valid JSON are left untouched, empty strings become NULL (or [] if not nullable).
     */
    private function convertColumnToJson(string $table, string $column, bool $nullable): void
    {
        // need to use raw SQL for this because the ORM layer would already deserialize the column
        $rows = $this->connection->executeQuery(
            sprintf('SELECT id, `%s` FROM %s WHERE `%s` IS NOT NULL', $column, $table, $column)
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $value = $row[$column];
            if ($value === '') {
                $json = $nullable ? null : '[]';
            } elseif (json_validate($value)) {
                continue;
            } else {
                $unserialized = @unserialize($value, ['allowed_classes' => false]);
                if ($unserialized === false && $value !== 'b:0;') {
                    continue;
                }
                $json = json_encode($unserialized, JSON_UNESCAPED_UNICODE);
            }

            $this->connection->executeStatement(
                sprintf('UPDATE %s SET `%s` = :json WHERE id = :id', $table, $column),
                ['json' => $json, 'id' => $row['id']]
            );
        }
    }
}
