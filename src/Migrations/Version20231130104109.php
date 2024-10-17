<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231130104109 extends AbstractMigration
{
    private const TABLE_NAME = 'caller_session';
    private const COLUMN_NAME = 'is_sip_video_user';
    public function getDescription(): string
    {
        return 'Adds the is sip video user attibute to a caller session';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME,Types::BOOLEAN)
            ->setDefault(false)
            ->setNotnull(false);

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropColumn(self::COLUMN_NAME);

    }

}
