<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230503130704 extends AbstractMigration
{
    private const TABLE_NAME = 'rooms';
    private const COLUMN_NAME = 'allow_maybe_option';

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        $table->addColumn(self::COLUMN_NAME, Types::BOOLEAN)
            ->setDefault(true)
            ->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable(self::TABLE_NAME)->dropColumn(self::COLUMN_NAME);
    }
}
