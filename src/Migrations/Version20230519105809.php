<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230519105809 extends AbstractMigration
{
    private const TABLE_NAME = 'cron_job';

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {


    }

    public function down(Schema $schema): void
    {

    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema);
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            $this->connection->createQueryBuilder()
                ->insert(self::TABLE_NAME, 'c')
                ->values(
                    [
                        'name' => ':name',
                        'command' => ':command',
                        'schedule' => ':schedule',
                        'description' => ':desc',
                        'enabled' => ':true'
                    ]
                )
                ->setParameter('name', 'themeChecker')
                ->setParameter('command', 'app:check:theme:validDate 10')
                ->setParameter('schedule', '0 0 * * *')
                ->setParameter('desc', 'Checks if a theme is expiring')
                ->setParameter('true', true)
                ->executeQuery();
        }
    }

    public function postDown(Schema $schema): void
    {
        parent::postDown($schema);
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            $this->connection->createQueryBuilder()
                ->delete(self::TABLE_NAME,)
                ->where('name =:name')
                ->setParameter('name', 'themeChecker')
                ->executeQuery();
        }
    }
}
