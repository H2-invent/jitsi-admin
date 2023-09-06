<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230906110060 extends AbstractMigration
{
    private const TABLE_NAME = 'messenger_messages';
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->modifyColumn('created_at',['comment'=>'(DC2Type:datetime_immutable)','NotNull'=>true]);
        $table->modifyColumn('available_at',['comment'=>'(DC2Type:datetime_immutable)','NotNull'=>true]);
        $table->modifyColumn('delivered_at',['comment'=>'(DC2Type:datetime_immutable)','default'=>null, 'NotNull'=>false]);
        // this up() migration is auto-generated, please modify it to your needs
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->modifyColumn('created_at',[ 'comment'=>'(DC2Type:datetime_immutable)','NotNull'=>true]);
        $table->modifyColumn('available_at',[ 'comment'=>'(DC2Type:datetime_immutable)','NotNull'=>true]);
        $table->modifyColumn('delivered_at',['comment'=>'(DC2Type:datetime_immutable)','NotNull'=>true]);
        // this down() migration is auto-generated, please modify it to your needs
    }
}
