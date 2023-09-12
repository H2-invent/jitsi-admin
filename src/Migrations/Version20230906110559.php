<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230906110559 extends AbstractMigration
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
        $table->modifyColumn(self::COLUMN_NAME,[ 'default'=>null,'NotNull'=>true]);
        // this up() migration is auto-generated, please modify it to your needs

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->modifyColumn(self::COLUMN_NAME,['default'=>true,'NotNull'=>true]);
        // this down() migration is auto-generated, please modify it to your needs

    }
}
