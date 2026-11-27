<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261104164100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create protocol_template table (per-server header, footer and logo for protocol PDFs)';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->platform->getCreateTableSQL($this->getProtocolTemplateTable()) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->platform->getDropTablesSQL([$this->getProtocolTemplateTable()]) as $sql) {
            $this->addSql($sql);
        }
    }

    private function getProtocolTemplateTable(): Table
    {
        return Table::editor()
            ->setUnquotedName('protocol_template')
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('id')
                    ->setTypeName('integer')
                    ->setAutoincrement(true)
                    ->create()
            )
            ->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()
                    ->setUnquotedColumnNames('id')
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('server_id')
                    ->setTypeName('integer')
                    ->create()
            )
            ->addForeignKeyConstraint(
                ForeignKeyConstraint::editor()
                    ->setUnquotedName('FK_31F96A4A1844E6B7')
                    ->setUnquotedReferencingColumnNames('server_id')
                    ->setUnquotedReferencedTableName('server')
                    ->setUnquotedReferencedColumnNames('id')
                    ->setOnDeleteAction(ForeignKeyConstraint\ReferentialAction::CASCADE)
                    ->create()
            )
            ->addIndex(
                Index::editor()
                    ->setUnquotedName('UNIQ_31F96A4A1844E6B7')
                    ->setUnquotedColumnNames('server_id')
                    ->setType(Index\IndexType::UNIQUE)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('header')
                    ->setTypeName('text')
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('footer')
                    ->setTypeName('text')
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('logo_name')
                    ->setTypeName('string')
                    ->setLength(255)
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('logo_original_name')
                    ->setTypeName('string')
                    ->setLength(255)
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('logo_mime_type')
                    ->setTypeName('string')
                    ->setLength(255)
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('logo_size')
                    ->setTypeName('integer')
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('logo_dimensions')
                    ->setTypeName('text')
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->addColumn(
                Column::editor()
                    ->setUnquotedName('updated_at')
                    ->setTypeName('datetime_immutable')
                    ->setDefaultValue(null)
                    ->setNotNull(false)
                    ->create()
            )
            ->create()
        ;
    }
}
