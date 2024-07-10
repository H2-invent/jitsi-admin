<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use function Aws\flatmap;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240510092733 extends AbstractMigration
{
    private const TABLE_NAME = 'caller_session';
    private const COLUMN_NAME = 'is_sip_video_user';
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->getColumn(self::COLUMN_NAME)
            ->setNotnull(false)
            ->setDefault(null);

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->getColumn(self::COLUMN_NAME)
            ->setNotnull(false)
            ->setDefault(0);

    }
}
