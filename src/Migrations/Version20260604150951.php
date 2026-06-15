<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604150951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add hotstandby_id to ldap_user_properties';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('ldap_user_properties')
            ->addColumn('hotstandby_id', 'string', [
                'length'  => 255,
                'notnull' => false,
            ]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('ldap_user_properties')->dropColumn('hotstandby_id');
    }
}
