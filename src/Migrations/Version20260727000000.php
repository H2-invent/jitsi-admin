<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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
        $this->convertColumnToJson('fos_user', 'keycloakGroup', true);
        $this->convertColumnToJson('fos_user', 'spezial_properties', true);
        $this->convertColumnToJson('repeat', 'weekday', false);

        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            // DBAL generates "ALTER ... TYPE JSON" without USING, which PostgreSQL rejects for TEXT columns
            $this->addSql('ALTER TABLE fos_user ALTER keycloakGroup TYPE JSON USING keycloakGroup::json');
            $this->addSql('ALTER TABLE fos_user ALTER spezial_properties TYPE JSON USING spezial_properties::json');
            $this->addSql('ALTER TABLE "repeat" ALTER weekday TYPE JSON USING weekday::json');

            return;
        }

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

    public function postDown(Schema $schema): void
    {
        // Runs after the schema changes of down(): only a TEXT column accepts the PHP-serialized values again.
        $this->convertColumnToSerialized('fos_user', 'keycloakGroup');
        $this->convertColumnToSerialized('fos_user', 'spezial_properties');
        $this->convertColumnToSerialized('repeat', 'weekday');
    }

    /**
     * Converts PHP-serialized values (DC2Type:array) to JSON. Values that are already
     * valid JSON are left untouched, empty strings become NULL (or [] if not nullable).
     */
    private function convertColumnToJson(string $table, string $column, bool $nullable): void
    {
        foreach ($this->fetchValues($table, $column) as $id => $value) {
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

            $this->updateValue($table, $column, $id, $json);
        }
    }

    /**
     * Converts JSON values back to PHP-serialized values (DC2Type:array).
     */
    private function convertColumnToSerialized(string $table, string $column): void
    {
        foreach ($this->fetchValues($table, $column) as $id => $value) {
            if ($value === '') {
                continue;
            }

            $decoded = json_decode($value, true);
            if ($decoded === null && $value !== 'null') {
                continue;
            }

            $this->updateValue($table, $column, $id, serialize($decoded));
        }
    }

    /**
     * Reads the raw column values through DBAL, the ORM would already (de)serialize them.
     * @return array<int, string>
     */
    private function fetchValues(string $table, string $column): array
    {
        return $this->connection->createQueryBuilder()
            ->select('id', $column)
            ->from($this->connection->quoteSingleIdentifier($table))
            ->where($column . ' IS NOT NULL')
            ->executeQuery()
            ->fetchAllKeyValue()
        ;
    }

    private function updateValue(string $table, string $column, int|string $id, ?string $value): void
    {
        $this->connection->createQueryBuilder()
            ->update($this->connection->quoteSingleIdentifier($table))
            ->set($column, ':value')
            ->where('id = :id')
            ->setParameter('value', $value)
            ->setParameter('id', $id, ParameterType::INTEGER)
            ->executeStatement()
        ;
    }
}
