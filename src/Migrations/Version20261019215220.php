<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261019215220 extends AbstractMigration
{
    private const TABLE_NAME = 'fos_user';
    private const COLUMN_NAME = 'outlook_plugin_token';
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        $table->addColumn(self::COLUMN_NAME, Types::STRING, [
            'length' => 255,
            'default' => null,
            'notnull' => false,
        ]);

        $table->addUniqueIndex(
            [self::COLUMN_NAME],
            'UNIQ_957A6479543336F9'
        );
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        $table->dropIndex('UNIQ_957A6479543336F9');
        $table->dropColumn(self::COLUMN_NAME);
    }
}
