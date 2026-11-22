<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027064527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add hotstandby_enabled to fos_user';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('fos_user')
            ->addColumn('hotstandby_enabled', Types::BOOLEAN, [
                'notnull' => true,
                'default' => false,
            ])
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('fos_user')->dropColumn('hotstandby_enabled');
    }
}
