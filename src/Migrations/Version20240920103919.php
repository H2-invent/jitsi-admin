<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240920103919 extends AbstractMigration
{

    private const TABLE_NAME = 'server';
    private const COLUMN_NAME = 'livekit_background_images';
    public function getDescription(): string
    {
        return 'adds the livekit backgroupd image field. this is a json string field';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME,Types::TEXT)
            ->setDefault(null)
            ->setNotnull(false);

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropColumn(self::COLUMN_NAME);

    }
}
