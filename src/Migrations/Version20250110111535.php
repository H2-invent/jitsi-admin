<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250110111535 extends AbstractMigration
{

    private const TABLE_NAME = 'fos_user';
    private const COLUMN_NAME_calendly_token = 'calendly_token';
    private const COLUMN_NAME_ORG_UID = 'calendly_org_uri';
    private const COLUMN_NAME_USER_UID= 'calendly_user_uri';
    private const COLUMN_NAME_CONNECTED= 'calendly_sucessfully_added';
    private const COLUMN_SECRET= 'calendly_secret';
    private const COLUMN_CALENDLY_ID= 'calendly_webhook_id';
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME_calendly_token,Types::STRING, ['length' => 1000])
            ->setNotnull(false);
        $table->addColumn(self::COLUMN_NAME_ORG_UID,Types::STRING, ['length' => 1000])
            ->setNotnull(false);
        $table->addColumn(self::COLUMN_NAME_USER_UID,Types::STRING, ['length' => 1000])
            ->setNotnull(false);
        $table->addColumn(self::COLUMN_CALENDLY_ID,Types::STRING, ['length' => 1000])
            ->setNotnull(false);
        $table->addColumn(self::COLUMN_SECRET,Types::STRING, ['length' => 255])
            ->setNotnull(false);
        $table->addColumn(self::COLUMN_NAME_CONNECTED,Types::BOOLEAN)
            ->setNotnull(false)
            ->setDefault(null);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropColumn(self::COLUMN_NAME_calendly_token);
        $table->dropColumn(self::COLUMN_NAME_CONNECTED);
        $table->dropColumn(self::COLUMN_NAME_USER_UID);
        $table->dropColumn(self::COLUMN_NAME_ORG_UID);
        $table->dropColumn(self::COLUMN_SECRET);
        $table->dropColumn(self::COLUMN_CALENDLY_ID);
    }

}
