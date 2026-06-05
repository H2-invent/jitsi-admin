<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303110025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'inserts the cronjob for provisioner schedule check';
    }

    public function up(Schema $schema): void
    {
        $exists = (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM cron_job WHERE name = :name',
            ['name' => 'provisionerScheduleCheck']
        );

        if ($exists) {
            return;
        }

        $data = [
            'name'        => 'provisionerScheduleCheck',
            'command'     => 'app:provisioner:schedule-check',
            'schedule'    => '* * * * *',
            'description' => 'provision servers for meetings starting soon',
            'enabled'     => true,
        ];

        if (str_contains(strtolower(get_class($this->connection->getDatabasePlatform())), 'postgresql')) {
            $data['id'] = (int) $this->connection->fetchOne("SELECT nextval('cron_job_id_seq')");
        }

        $this->connection->insert('cron_job', $data, ['enabled' => Types::BOOLEAN]);
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('cron_job', ['name' => 'provisionerScheduleCheck']);
    }
}
