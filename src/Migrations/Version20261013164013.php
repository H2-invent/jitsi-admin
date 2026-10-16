<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261013164013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $table = $schema->createTable('provisioner_record');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('room_id', 'integer');
        $table->addColumn('provisioned_server_id', 'integer', ['notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 255]);
        $table->addColumn('retry_attempts', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['room_id'], 'IDX_165074BF54177093');
        $table->addUniqueIndex(['provisioned_server_id'], 'UNIQ_165074BF9B810C3E');
        $table->addForeignKeyConstraint('rooms', ['room_id'], ['id'], [], 'FK_165074BF54177093');
        $table->addForeignKeyConstraint('server', ['provisioned_server_id'], ['id'], [], 'FK_165074BF9B810C3E');

        $schema->dropTable('messenger_messages');

        $rooms = $schema->getTable('rooms');
        $rooms->dropIndex('uid_real_idx');
        $rooms->addIndex(['uid_real'], 'uid_real_idx');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $table = $schema->createTable('messenger_messages');
        $table->addColumn('id', 'bigint', ['autoincrement' => true]);
        $table->addColumn('body', 'text');
        $table->addColumn('headers', 'text');
        $table->addColumn('queue_name', 'string', ['length' => 190]);
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addColumn('available_at', 'datetime_immutable');
        $table->addColumn('delivered_at', 'datetime_immutable', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['available_at'], 'IDX_75EA56E0E3BD61CE');
        $table->addIndex(['delivered_at'], 'IDX_75EA56E016BA31DB');
        $table->addIndex(['queue_name'], 'IDX_75EA56E0FB7336F0');

        $provisionerRecord = $schema->getTable('provisioner_record');
        $provisionerRecord->removeForeignKey('FK_165074BF54177093');
        $provisionerRecord->removeForeignKey('FK_165074BF9B810C3E');
        $schema->dropTable('provisioner_record');

        $rooms = $schema->getTable('rooms');
        $rooms->dropIndex('uid_real_idx');
        $rooms->addIndex(['uid_real'], 'uid_real_idx', [], ['lengths' => [768]]);
    }
}
