<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226083925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'inserts the cronjob for provisioner cleanup';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
        INSERT INTO cron_job (name, command, schedule, description, enabled)
        SELECT 'provisionerCleanup', 'app:provisioner:cleanup', '* * * * *', 'remove unused provisioned servers', 1
        WHERE NOT EXISTS (
            SELECT 1 FROM cron_job WHERE name = 'provisionerCleanup'
        )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
        DELETE FROM cron_job
        WHERE name = 'provisionerCleanup'
        SQL);
    }
}
