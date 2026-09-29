<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert array columns (PHP serialized) to JSON in fos_user and repeat tables';
    }

    public function up(Schema $schema): void
    {
        $this->migrateColumn('fos_user', 'keycloakGroup');
        $this->migrateColumn('fos_user', 'spezial_properties');
        $this->migrateColumn('repeat', 'weekday');
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty: the columns are still JSON at this point and would reject PHP-serialized values.
        // Version20260727000000::postDown() converts the values back once the columns are TEXT again.
    }

    private function migrateColumn(string $table, string $column): void
    {
        foreach ($this->fetchValues($table, $column) as $id => $value) {
            if ($value === '') {
                continue;
            }

            $unserialized = @unserialize($value, ['allowed_classes' => false]);
            if ($unserialized === false && $value !== 'b:0;') {
                continue;
            }

            $this->updateValue($table, $column, $id, json_encode($unserialized, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Reads the raw column values through DBAL, the ORM would already (de)serialize them.
     * Column names stay unquoted so PostgreSQL folds them to lower case like the original CREATE TABLE did.
     *
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
