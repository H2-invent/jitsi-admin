<?php

declare(strict_types=1);

namespace DoctrineMigrations;

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
        $this->migrateColumn('`repeat`', 'weekday');
    }

    public function down(Schema $schema): void
    {
        $this->reverseMigrateColumn('fos_user', 'keycloakGroup');
        $this->reverseMigrateColumn('fos_user', 'spezial_properties');
        $this->reverseMigrateColumn('`repeat`', 'weekday');
    }

    private function migrateColumn(string $table, string $column): void
    {
        $rows = $this->connection->executeQuery(
            sprintf('SELECT id, `%s` FROM %s WHERE `%s` IS NOT NULL', $column, $table, $column)
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $value = $row[$column];
            if ($value === null || $value === '') {
                continue;
            }
            $unserialized = @unserialize($value);
            if ($unserialized !== false || $value === 'b:0;') {
                $json = json_encode($unserialized, JSON_UNESCAPED_UNICODE);
                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET `%s` = :json WHERE id = :id', $table, $column),
                    ['json' => $json, 'id' => $row['id']]
                );
            }
        }
    }

    private function reverseMigrateColumn(string $table, string $column): void
    {
        $rows = $this->connection->executeQuery(
            sprintf('SELECT id, `%s` FROM %s WHERE `%s` IS NOT NULL', $column, $table, $column)
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $value = $row[$column];
            if ($value === null || $value === '') {
                continue;
            }
            $decoded = json_decode($value, true);
            if ($decoded !== null || $value === 'null') {
                $serialized = serialize($decoded);
                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET `%s` = :serialized WHERE id = :id', $table, $column),
                    ['serialized' => $serialized, 'id' => $row['id']]
                );
            }
        }
    }
}
