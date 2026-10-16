<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226083925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'inserts the cronjob for provisioner cleanup';
    }

    public function up(Schema $schema): void
    {
        $exists = (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM cron_job WHERE name = :name',
            ['name' => 'provisionerCleanup']
        );

        if ($exists) {
            return;
        }

        $data = [
            'name'        => 'provisionerCleanup',
            'command'     => 'app:provisioner:cleanup',
            'schedule'    => '* * * * *',
            'description' => 'remove unused provisioned servers',
            'enabled'     => true,
        ];

        if (str_contains(strtolower(get_class($this->connection->getDatabasePlatform())), 'postgresql')) {
            $data['id'] = (int) $this->connection->fetchOne("SELECT nextval('cron_job_id_seq')");
        }

        $this->connection->insert('cron_job', $data, ['enabled' => Types::BOOLEAN]);
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('cron_job', ['name' => 'provisionerCleanup']);
    }
}
