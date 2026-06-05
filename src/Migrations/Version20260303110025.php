<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303110025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(<<<SQL
            INSERT INTO cron_job (id, name, command, schedule, description, enabled)
            SELECT nextval('cron_job_id_seq'), 'provisionerScheduleCheck', 'app:provisioner:schedule-check', '* * * * *', 'provision servers for meetings starting soon', TRUE
            WHERE NOT EXISTS (
                SELECT 1 FROM cron_job WHERE name = 'provisionerScheduleCheck'
            )
            SQL);
        } else {
            $this->addSql(<<<SQL
            INSERT INTO cron_job (name, command, schedule, description, enabled)
            SELECT 'provisionerScheduleCheck', 'app:provisioner:schedule-check', '* * * * *', 'provision servers for meetings starting soon', TRUE
            WHERE NOT EXISTS (
                SELECT 1 FROM cron_job WHERE name = 'provisionerScheduleCheck'
            )
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
        DELETE FROM cron_job
        WHERE name = 'provisionerScheduleCheck'
        SQL);
    }
}
