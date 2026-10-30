<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace LONGTEXT columns with properly sized VARCHAR/TEXT columns and add lookup indexes';
    }

    public function up(Schema $schema): void
    {
        $stringType = Type::getType(Types::STRING);
        $textType   = Type::getType(Types::TEXT);

        // VARCHAR conversions (type: 'string', length: N) — nullability preserved.
        $this->modifyColumn($schema, 'address_group', 'name', $stringType, 255, true);
        $this->modifyColumn($schema, 'address_group', 'indexer', $stringType, 255, false);
        $this->modifyColumn($schema, 'api_keys', 'client_id', $stringType, 255, true);
        $this->modifyColumn($schema, 'api_keys', 'client_secret', $stringType, 255, true);
        $this->modifyColumn($schema, 'caller_id', 'caller_id', $stringType, 32, true);
        $this->modifyColumn($schema, 'caller_room', 'caller_id', $stringType, 32, true);
        $this->modifyColumn($schema, 'caller_session', 'session_id', $stringType, 36, true);
        $this->modifyColumn($schema, 'caller_session', 'caller_id', $stringType, 32, false);
        $this->modifyColumn($schema, 'caller_session', 'show_name', $stringType, 255, false);
        $this->modifyColumn($schema, 'callout_session', 'uid', $stringType, 36, true);
        $this->modifyColumn($schema, 'fos_user', 'email', $stringType, 255, true);
        $this->modifyColumn($schema, 'fos_user', 'keycloak_id', $stringType, 36, false);
        $this->modifyColumn($schema, 'fos_user', 'username', $stringType, 255, false);
        $this->modifyColumn($schema, 'fos_user', 'first_name', $stringType, 255, false);
        $this->modifyColumn($schema, 'fos_user', 'last_name', $stringType, 255, false);
        $this->modifyColumn($schema, 'fos_user', 'register_id', $stringType, 36, false);
        $this->modifyColumn($schema, 'fos_user', 'uid', $stringType, 36, false);
        $this->modifyColumn($schema, 'fos_user', 'own_room_uid', $stringType, 36, false);
        $this->modifyColumn($schema, 'fos_user', 'indexer', $stringType, 2048, false);
        $this->modifyColumn($schema, 'fos_user', 'second_email', $stringType, 255, false);
        $this->modifyColumn($schema, 'ldap_user_properties', 'ldap_host', $stringType, 255, true);
        $this->modifyColumn($schema, 'ldap_user_properties', 'rdn', $stringType, 255, false);
        $this->modifyColumn($schema, 'ldap_user_properties', 'ldap_number', $stringType, 64, true);
        $this->modifyColumn($schema, 'license', 'license_key', $stringType, 255, true);
        $this->modifyColumn($schema, 'license', 'url', $stringType, 255, true);
        $this->modifyColumn($schema, 'lobby_waitung_user', 'uid', $stringType, 36, true);
        $this->modifyColumn($schema, 'lobby_waitung_user', 'show_name', $stringType, 255, true);
        $this->modifyColumn($schema, 'log', 'user_name', $stringType, 255, true);
        $this->modifyColumn($schema, 'notification', 'title', $stringType, 255, true);
        $this->modifyColumn($schema, 'notification', 'text', $stringType, 1024, true);
        $this->modifyColumn($schema, 'notification', 'url', $stringType, 255, false);
        $this->modifyColumn($schema, 'predefined_lobby_messages', 'text', $stringType, 255, true);
        $this->modifyColumn($schema, '`repeat`', 'uid', $stringType, 36, false);
        $this->modifyColumn($schema, 'rooms', 'name', $stringType, 255, true);
        $this->modifyColumn($schema, 'rooms', 'uid', $stringType, 255, true);
        $this->modifyColumn($schema, 'rooms', 'uid_real', $stringType, 36, false);
        $this->modifyColumn($schema, 'rooms', 'agenda', $stringType, 3000, false);
        $this->modifyColumn($schema, 'rooms', 'uid_participant', $stringType, 36, false);
        $this->modifyColumn($schema, 'rooms', 'uid_moderator', $stringType, 36, false);
        $this->modifyColumn($schema, 'rooms', 'slug', $stringType, 255, false);
        $this->modifyColumn($schema, 'rooms', 'host_url', $stringType, 255, false);
        $this->modifyColumn($schema, 'rooms', 'secondary_name', $stringType, 255, false);
        $this->modifyColumn($schema, 'room_status', 'jitsi_room_id', $stringType, 255, true);
        $this->modifyColumn($schema, 'room_status_participant', 'participant_id', $stringType, 255, true);
        $this->modifyColumn($schema, 'room_status_participant', 'participant_name', $stringType, 255, true);
        $this->modifyColumn($schema, 'scheduling', 'uid', $stringType, 36, true);
        $this->modifyColumn($schema, 'scheduling', 'description', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'url', $stringType, 255, true);
        $this->modifyColumn($schema, 'server', 'app_id', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'app_secret', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'logo_url', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'smtp_host', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'smtp_password', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'smtp_username', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'smtp_encryption', $stringType, 16, false);
        $this->modifyColumn($schema, 'server', 'smtp_email', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'smtp_sender_name', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'slug', $stringType, 255, true);
        $this->modifyColumn($schema, 'server', 'privacy_policy', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'license_key', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'api_key', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'server_name', $stringType, 255, true);
        $this->modifyColumn($schema, 'server', 'jigasi_api_url', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'jigasi_prosody_domain', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'star_url', $stringType, 255, false);
        $this->modifyColumn($schema, 'server', 'allow_ip', $stringType, 1024, false);
        $this->modifyColumn($schema, 'star', 'comment', $stringType, 1024, false);
        $this->modifyColumn($schema, 'subscriber', 'uid', $stringType, 36, true);
        $this->modifyColumn($schema, 'tag', 'title', $stringType, 255, true);
        $this->modifyColumn($schema, 'tag', 'color', $stringType, 32, false);
        $this->modifyColumn($schema, 'tag', 'background_color', $stringType, 32, false);

        // Remaining TEXT columns: shrink LONGTEXT to TEXT/MEDIUMTEXT by declaring an explicit length.
        $this->modifyColumn($schema, 'ldap_user_properties', 'ldap_dn', $textType, 65535, true);
        $this->modifyColumn($schema, 'license', 'license', $textType, 65535, true);
        $this->modifyColumn($schema, 'log', 'message', $textType, 65535, true);
        $this->modifyColumn($schema, 'server', 'jigasi_number_url', $textType, 65535, false);
        $this->modifyColumn($schema, 'server', 'livekit_background_images', $textType, 65535, false);
        $this->modifyColumn($schema, 'server', 'server_email_header', $textType, 65535, false);
        $this->modifyColumn($schema, 'server', 'server_email_body', $textType, 65535, false);
        $this->modifyColumn($schema, 'star', 'browser', $textType, 65535, false);
        $this->modifyColumn($schema, 'star', 'os', $textType, 65535, false);
        $this->modifyColumn($schema, 'transcription', 'text', $textType, 16777215, true);

        // Lookup indexes (all on columns converted to VARCHAR above).
        $this->addIndex($schema, 'rooms', 'uid', 'idx_rooms_uid');
        $this->addIndex($schema, 'rooms', 'uid_real', 'idx_rooms_uid_real');
        $this->addIndex($schema, 'rooms', 'uid_participant', 'idx_rooms_uid_participant');
        $this->addIndex($schema, 'rooms', 'uid_moderator', 'idx_rooms_uid_moderator');
        $this->addIndex($schema, 'server', 'slug', 'idx_server_slug');
        $this->addIndex($schema, 'server', 'api_key', 'idx_server_api_key');
        $this->addIndex($schema, 'server', 'license_key', 'idx_server_license_key');
        $this->addIndex($schema, 'fos_user', 'email', 'idx_fos_user_email');
        $this->addIndex($schema, 'fos_user', 'username', 'idx_fos_user_username');
        $this->addIndex($schema, 'fos_user', 'uid', 'idx_fos_user_uid');
        $this->addIndex($schema, 'fos_user', 'keycloak_id', 'idx_fos_user_keycloak_id');
        $this->addIndex($schema, 'caller_room', 'caller_id', 'idx_caller_room_caller_id');
        $this->addIndex($schema, 'caller_session', 'session_id', 'idx_caller_session_session_id');
        $this->addIndex($schema, 'lobby_waitung_user', 'uid', 'idx_lobby_waitung_user_uid');
        $this->addIndex($schema, 'callout_session', 'uid', 'idx_callout_session_uid');
        $this->addIndex($schema, 'subscriber', 'uid', 'idx_subscriber_uid');
        $this->addIndex($schema, 'room_status', 'jitsi_room_id', 'idx_room_status_jitsi_room_id');
        $this->addIndex($schema, 'room_status_participant', 'participant_id', 'idx_room_status_participant_participant_id');
        $this->addIndex($schema, 'license', 'license_key', 'idx_license_license_key');
    }

    public function down(Schema $schema): void
    {
        $textType = Type::getType(Types::TEXT);

        foreach ($this->varcharColumns() as [$table, $column, $notNull]) {
            $this->modifyColumn($schema, $table, $column, $textType, null, $notNull);
        }
        foreach ($this->textColumns() as [$table, $column, $notNull]) {
            $this->modifyColumn($schema, $table, $column, $textType, null, $notNull);
        }
        foreach ($this->indexes() as [$table, $column, $indexName]) {
            $schema->getTable($table)->dropIndex($indexName);
        }
    }

    private function modifyColumn(
        Schema $schema,
        string $table,
        string $column,
        Type $type,
        ?int $length,
        bool $notNull,
    ): void {
        $options = ['Type' => $type, 'NotNull' => $notNull, 'Length' => $length];

        $tableSchema = $schema->getTable($table);
        $tableSchema->modifyColumn($column, $options);
    }

    private function addIndex(Schema $schema, string $table, string $column, string $indexName): void
    {
        $schema->getTable($table)->addIndex([$column], $indexName);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: bool}>
     */
    private function varcharColumns(): array
    {
        return [
            ['address_group', 'name', true],
            ['address_group', 'indexer', false],
            ['api_keys', 'client_id', true],
            ['api_keys', 'client_secret', true],
            ['caller_id', 'caller_id', true],
            ['caller_room', 'caller_id', true],
            ['caller_session', 'session_id', true],
            ['caller_session', 'caller_id', false],
            ['caller_session', 'show_name', false],
            ['callout_session', 'uid', true],
            ['fos_user', 'email', true],
            ['fos_user', 'keycloak_id', false],
            ['fos_user', 'username', false],
            ['fos_user', 'first_name', false],
            ['fos_user', 'last_name', false],
            ['fos_user', 'register_id', false],
            ['fos_user', 'uid', false],
            ['fos_user', 'own_room_uid', false],
            ['fos_user', 'indexer', false],
            ['fos_user', 'second_email', false],
            ['ldap_user_properties', 'ldap_host', true],
            ['ldap_user_properties', 'rdn', false],
            ['ldap_user_properties', 'ldap_number', true],
            ['license', 'license_key', true],
            ['license', 'url', true],
            ['lobby_waitung_user', 'uid', true],
            ['lobby_waitung_user', 'show_name', true],
            ['log', 'user_name', true],
            ['notification', 'title', true],
            ['notification', 'text', true],
            ['notification', 'url', false],
            ['predefined_lobby_messages', 'text', true],
            ['`repeat`', 'uid', false],
            ['rooms', 'name', true],
            ['rooms', 'uid', true],
            ['rooms', 'uid_real', false],
            ['rooms', 'agenda', false],
            ['rooms', 'uid_participant', false],
            ['rooms', 'uid_moderator', false],
            ['rooms', 'slug', false],
            ['rooms', 'host_url', false],
            ['rooms', 'secondary_name', false],
            ['room_status', 'jitsi_room_id', true],
            ['room_status_participant', 'participant_id', true],
            ['room_status_participant', 'participant_name', true],
            ['scheduling', 'uid', true],
            ['scheduling', 'description', false],
            ['server', 'url', true],
            ['server', 'app_id', false],
            ['server', 'app_secret', false],
            ['server', 'logo_url', false],
            ['server', 'smtp_host', false],
            ['server', 'smtp_password', false],
            ['server', 'smtp_username', false],
            ['server', 'smtp_encryption', false],
            ['server', 'smtp_email', false],
            ['server', 'smtp_sender_name', false],
            ['server', 'slug', true],
            ['server', 'privacy_policy', false],
            ['server', 'license_key', false],
            ['server', 'api_key', false],
            ['server', 'server_name', true],
            ['server', 'jigasi_api_url', false],
            ['server', 'jigasi_prosody_domain', false],
            ['server', 'star_url', false],
            ['server', 'allow_ip', false],
            ['star', 'comment', false],
            ['subscriber', 'uid', true],
            ['tag', 'title', true],
            ['tag', 'color', false],
            ['tag', 'background_color', false],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: bool}>
     */
    private function textColumns(): array
    {
        return [
            ['ldap_user_properties', 'ldap_dn', true],
            ['license', 'license', true],
            ['log', 'message', true],
            ['server', 'jigasi_number_url', false],
            ['server', 'livekit_background_images', false],
            ['server', 'server_email_header', false],
            ['server', 'server_email_body', false],
            ['star', 'browser', false],
            ['star', 'os', false],
            ['transcription', 'text', true],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function indexes(): array
    {
        return [
            ['rooms', 'uid', 'idx_rooms_uid'],
            ['rooms', 'uid_real', 'idx_rooms_uid_real'],
            ['rooms', 'uid_participant', 'idx_rooms_uid_participant'],
            ['rooms', 'uid_moderator', 'idx_rooms_uid_moderator'],
            ['server', 'slug', 'idx_server_slug'],
            ['server', 'api_key', 'idx_server_api_key'],
            ['server', 'license_key', 'idx_server_license_key'],
            ['fos_user', 'email', 'idx_fos_user_email'],
            ['fos_user', 'username', 'idx_fos_user_username'],
            ['fos_user', 'uid', 'idx_fos_user_uid'],
            ['fos_user', 'keycloak_id', 'idx_fos_user_keycloak_id'],
            ['caller_room', 'caller_id', 'idx_caller_room_caller_id'],
            ['caller_session', 'session_id', 'idx_caller_session_session_id'],
            ['lobby_waitung_user', 'uid', 'idx_lobby_waitung_user_uid'],
            ['callout_session', 'uid', 'idx_callout_session_uid'],
            ['subscriber', 'uid', 'idx_subscriber_uid'],
            ['room_status', 'jitsi_room_id', 'idx_room_status_jitsi_room_id'],
            ['room_status_participant', 'participant_id', 'idx_room_status_participant_participant_id'],
            ['license', 'license_key', 'idx_license_license_key'],
        ];
    }
}
