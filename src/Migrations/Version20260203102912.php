<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203102912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $rooms = $schema->getTable('rooms');
        $rooms->addColumn('original_server_id', 'integer', ['notnull' => false, 'default' => null]);
        $rooms->addIndex(['original_server_id'], 'IDX_7CA11A9672D799A2');
        $rooms->addForeignKeyConstraint('server', ['original_server_id'], ['id'], [], 'FK_7CA11A9672D799A2');
    }

    public function down(Schema $schema): void
    {
        $rooms = $schema->getTable('rooms');
        $rooms->removeForeignKey('FK_7CA11A9672D799A2');
        $rooms->dropIndex('IDX_7CA11A9672D799A2');
        $rooms->dropColumn('original_server_id');
    }
}
