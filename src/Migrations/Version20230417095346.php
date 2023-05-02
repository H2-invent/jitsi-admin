<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230417095346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('caller_session');

        $messageUid = $table->addColumn('message_uid', Types::STRING);
        $messageText = $table->addColumn('message_text', Types::STRING);
        $messageUid->setDefault(null)->setLength(255)->setNotnull(false);
        $messageText->setDefault(null)->setLength(3000)->setNotnull(false);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('caller_session');

        $table->dropColumn('message_uid');
        $table->dropColumn('message_text');
    }
}
