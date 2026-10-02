<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Call-in users of total open rooms are created during the dial-in and have no user.
 */
final class Version20260921100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the user of a caller_id nullable to allow a SIP dial-in to total open rooms with a lobby';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('caller_id')
            ->modifyColumn('user_id', [
                'Notnull' => false,
            ])
        ;
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caller_id WHERE user_id IS NULL');

        $schema->getTable('caller_id')
            ->modifyColumn('user_id', [
                'Notnull' => true,
            ])
        ;
    }
}
