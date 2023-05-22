<?php

declare(strict_types=1);

namespace App\Command;

use App\Command\Installer\DbConfig;
use App\Command\Installer\ConvertToEnvironmentInterface;
use App\Command\Installer\KeycloakConfig;
use App\Command\Installer\SmtpConfig;
use App\Command\Installer\BasicConfig;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:install', description: 'Jitsi admin installer')]
class InstallerCommand extends Command
{
    # region properties
    private string $projectDir;

    private QuestionHelper $helper;

    private InputInterface $input;

    private OutputInterface $output;
    # endregion properties

    # region command
    public function __construct(ParameterBagInterface $parameterBag, string $name = null)
    {
        $this->projectDir = $parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->helper = $this->getHelper('question');
        $this->input = $input;
        $this->output = $output;

        try {
            $baseConfig = $this->getBasicConfig();
            $dbConfig = $this->getDBConfig();
            $smtpConfig = $this->getSmtpConfig();
            $keycloakConfig = $this->getKeycloakConfig();

            $this->writeWebsocketConfFile($baseConfig);
            $this->writeEnvFile($baseConfig, $dbConfig, $smtpConfig, $keycloakConfig);
            $this->removeKeycloakProdConfig($baseConfig);
        } catch (InvalidArgumentException $e) {
            return Command::FAILURE;
        }

        $output->writeln('<info>Configuration done!</info>');

        return Command::SUCCESS;
    }
    # endregion command

    # region get_configs
    private function getBasicConfig(): BasicConfig
    {
        $currentConfig = BasicConfig::createFromParameters(
            baseUrl: $_ENV['laF_baseUrl'],
            secret: $_ENV['WEBSOCKET_SECRET'],
        );

        if (
            $currentConfig->baseUrl() === 'https://dummy.com'
            || $currentConfig->secret() === 'DUMMY'
        ) {
            return $this->createBasicConfig();
        }

        return $this->updateBasicConfig($currentConfig);
    }

    private function getKeycloakConfig(): KeycloakConfig
    {
        $currentConfig = KeycloakConfig::createFromParameters(
            url: $_ENV['OAUTH_KEYCLOAK_SERVER'],
            realm: $_ENV['OAUTH_KEYCLOAK_REALM'],
            version: str_ends_with($_ENV['OAUTH_KEYCLOAK_SERVER'], '/auth') ? 18 : 19,
            clientId: $_ENV['OAUTH_KEYCLOAK_CLIENT_ID'],
            clientSecret: $_ENV['OAUTH_KEYCLOAK_CLIENT_SECRET'],
        );

        if (
            $currentConfig->url() === 'http://dummy'
            || $currentConfig->realm() === 'dummy'
            || $currentConfig->clientId() === 'dummy'
            || $currentConfig->clientSecret() === 'dummy'
        ) {
            return $this->createKeycloakConfig();
        }

        return $this->updateKeycloakConfig($currentConfig);
    }

    private function getSmtpConfig(): SmtpConfig
    {
        $currentConfig = SmtpConfig::createFromDsnAndEmail($_ENV['MAILER_DSN'], $_ENV['DEFAULT_EMAIL']);

        if (
            $currentConfig->dsn() === 'smtp://dummy_user:dummy_password@dummy_host:465'
            || $currentConfig->sender() === 'dummy@dummy.dummy'
        ) {
            return $this->createSmtpConfig();
        }

        return $this->updateSmtpConfig($currentConfig);
    }

    private function getDBConfig(): DbConfig
    {
        $currentDbConfig = DbConfig::createFromDsn($_ENV['DATABASE_URL']);;
        if ($currentDbConfig->dsn() === 'mysql://dummy_user:dummy_password@dummy_host:3306/dummy_dbName?serverVersion=5.7') {
            return $this->createDbConfig();
        }

        return $this->updateDbConfig($currentDbConfig);
    }
    # endregion get_configs

    # region updater
    private function updateBasicConfig(BasicConfig $currentConfig): BasicConfig
    {
        $reconfigureQuestion = $this->getConfirmationQuestion('Do you want to reconfigure your base url', false);

        if ($this->ask($reconfigureQuestion)) {
            return $this->askForBasicConfig(
                $currentConfig->baseUrl(),
                $currentConfig->secret()
            );
        }

        return $currentConfig;
    }

    private function updateKeycloakConfig(KeycloakConfig $currentConfig): KeycloakConfig
    {
        $reconfigureQuestion = $this->getConfirmationQuestion('Do you want to reconfigure your Keycloak', true);

        if ($this->ask($reconfigureQuestion)) {
            return $this->askForKeycloakConfig(
                defaultVersion: $currentConfig->version(),
                defaultRealm: $currentConfig->realm(),
                defaultUrl: $currentConfig->url(),
                defaultClientId: $currentConfig->clientId(),
                defaultClientSecret: $currentConfig->clientSecret(),
            );
        }

        return $currentConfig;
    }

    private function updateSmtpConfig(SmtpConfig $currentConfig): SmtpConfig
    {
        $reconfigureQuestion = $this->getConfirmationQuestion('Do you want to reconfigure your SMTP', true);

        if ($this->ask($reconfigureQuestion)) {
            return $this->askForSmtpConfig(
                defaultHost: $currentConfig->host(),
                defaultPort: $currentConfig->port(),
                defaultUsername: $currentConfig->username(),
                defaultPassword: $currentConfig->password(),
                defaultEmail: $currentConfig->sender(),
            );
        }

        return $currentConfig;
    }

    private function updateDbConfig(DbConfig $currentConfig): DbConfig
    {
        $reconfigureQuestion = $this->getConfirmationQuestion('Do you want to reconfigure your database connection', true);

        if ($this->ask($reconfigureQuestion)) {
            return $this->askForDbConfig(
                defaultServerVersion: $currentConfig->serverVersion(),
                defaultHost: $currentConfig->host(),
                defaultPort: $currentConfig->port(),
                defaultDatabase: $currentConfig->database(),
                defaultUsername: $currentConfig->username(),
                defaultPassword: $currentConfig->password(),
            );
        }

        return $currentConfig;
    }
    # endregion updater

    # region creator
    private function createBasicConfig(): BasicConfig
    {
        return $this->askForBasicConfig(
            defaultBaseUrl: null,
            defaultSecret: null,
        );
    }

    private function createKeycloakConfig(): KeycloakConfig
    {
        return $this->askForKeycloakConfig(
            defaultVersion: 21,
            defaultRealm: 'jitsi-admin',
            defaultUrl: null,
            defaultClientId: 'clientId',
            defaultClientSecret: 'clientSecret',
        );
    }

    private function createSmtpConfig(): SmtpConfig
    {
        return $this->askForSmtpConfig(
            defaultHost: 'localhost',
            defaultPort: 587,
            defaultUsername: 'root',
            defaultPassword: 'root',
            defaultEmail: null,
        );
    }

    private function createDbConfig(): DbConfig
    {
        $dbExistsQuestion = $this->getConfirmationQuestion('Do you want to use an external database? If you don\'t have a database and want the internal database please select N ', false);

        if ($this->ask($dbExistsQuestion)) {
            return $this->askForDbConfig(
                defaultServerVersion: '5.7',
                defaultHost: 'localhost',
                defaultPort: 3306,
                defaultDatabase: 'jitsi-admin',
                defaultUsername: 'root',
                defaultPassword: 'root',
            );
        }

        return DbConfig::createFromDefault();
    }
    # endregion creator

    # region helper
    private function getQuestion(string $question, string|int|null $default = null): Question
    {
        if ($default === null) {
            $question = sprintf('<question>%s: </question>' . PHP_EOL, $question);
        } else {
            $question = sprintf('<question>%s: [%s]</question>' . PHP_EOL, $question, $default);
        }


        return new Question($question, $default);
    }

    private function getQuestionWithInfo(string $info, string $question, string|int|null $default = null): Question
    {
        $info = sprintf('<info>%s</info>' . PHP_EOL, $info);

        if ($default === null) {
            $question = sprintf('<info>%s</info><question>%s: </question>' . PHP_EOL, $info, $question);
        } else {
            $question = sprintf('<info>%s</info><question>%s: [%s]</question>' . PHP_EOL, $info, $question, $default);
        }

        return new Question($question, $default);
    }

    private function getConfirmationQuestion(string $question, ?bool $default = null): ConfirmationQuestion
    {
        if ($default === null) {
            $format = '<question>%s: </question>';
        } elseif ($default === true) {
            $format = '<question>%s: [Y/n]</question>';
        } else {
            $format = '<question>%s: [y/N]</question>';
        }

        $question = sprintf($format . PHP_EOL, $question);

        return new ConfirmationQuestion($question, $default, '/^(y|j)/i');
    }
    # endregion helper

    # region ask_for_configs
    private function askForKeycloakConfig(
        int     $defaultVersion,
        string  $defaultRealm,
        ?string $defaultUrl,
        ?string $defaultClientId,
        ?string $defaultClientSecret,
    ): KeycloakConfig
    {
        $versionQuestion = $this->getQuestion('Enter the keycloak version', $defaultVersion);
        $realmQuestion = $this->getQuestion('Enter the keycloak realm', $defaultRealm);
        $urlQuestion = $this->getQuestionWithInfo(
            'Your URL could look somewhat like [http://keycloak.domain.de]',
            'Enter the keycloak URL',
            $defaultUrl,
        );
        $clientIdQuestion = $this->getQuestion('Enter the keycloak client id', $defaultClientId);
        $clientSecretQuestion = $this->getQuestion('Enter the keycloak client secret', $defaultClientSecret);

        return KeycloakConfig::createFromParameters(
            url: $this->askForUrl($urlQuestion),
            realm: $this->ask($realmQuestion),
            version: $this->askForNumeric($versionQuestion),
            clientId: $this->ask($clientIdQuestion),
            clientSecret: $this->ask($clientSecretQuestion),
        );
    }

    private function askForSmtpConfig(
        string  $defaultHost,
        int     $defaultPort,
        string  $defaultUsername,
        string  $defaultPassword,
        ?string $defaultEmail,
    ): SmtpConfig
    {
        $hostQuestion = $this->getQuestion('Enter the smtp host', $defaultHost);
        $portQuestion = $this->getQuestion('Enter the smtp port', $defaultPort);
        $usernameQuestion = $this->getQuestion('Enter the smtp username', $defaultUsername);
        $passwordQuestion = $this->getQuestion('Enter the smtp password', $defaultPassword);
        $senderQuestion = $this->getQuestion('Enter the default sender email', $defaultEmail);

        return SmtpConfig::createFromParameters(
            host: $this->ask($hostQuestion),
            port: $this->askForNumeric($portQuestion),
            username: $this->ask($usernameQuestion),
            password: $this->ask($passwordQuestion),
            sender: $this->askForEmail($senderQuestion),
        );
    }

    private function askForDbConfig(
        string $defaultServerVersion,
        string $defaultHost,
        int    $defaultPort,
        string $defaultDatabase,
        string $defaultUsername,
        string $defaultPassword,
    ): DbConfig
    {
        $serverVersionQuestion = $this->getQuestion('Enter the mysql server version', $defaultServerVersion);
        $hostQuestion = $this->getQuestion('Enter the database host', $defaultHost);
        $portQuestion = $this->getQuestion('Enter the database port', $defaultPort);
        $databaseQuestion = $this->getQuestion('Enter the database name', $defaultDatabase);
        $usernameQuestion = $this->getQuestion('Enter the database username', $defaultUsername);
        $passwordQuestion = $this->getQuestion('Enter the database password', $defaultPassword);

        return DbConfig::createFromParameters(
            engine: 'mysql',
            serverVersion: $this->ask($serverVersionQuestion),
            host: $this->ask($hostQuestion),
            port: $this->askForNumeric($portQuestion),
            database: $this->ask($databaseQuestion),
            username: $this->ask($usernameQuestion),
            password: $this->ask($passwordQuestion),
        );
    }

    private function askForBasicConfig(
        ?string $defaultBaseUrl,
        ?string $defaultSecret,
    ): BasicConfig
    {
        $baseUrlQuestion = $this->getQuestion('Enter the base url of the Jitsi-Admin', $defaultBaseUrl);

        return BasicConfig::createFromParameters(
            baseUrl: $this->askForUrl($baseUrlQuestion),
            secret: $defaultSecret,
        );
    }
    # endregion ask_for_configs

    # region ask_types
    private function ask(Question $question): string|bool|int
    {
        return $this->helper->ask($this->input, $this->output, $question);
    }

    private function askForNumeric(
        Question $question,
        int      $attempt = 1
    ): int
    {
        $numeric = $this->ask($question);

        if (is_numeric($numeric)) {
            return (int)$numeric;
        }

        if ($attempt >= 3) {
            throw new InvalidArgumentException('Invalid number: ' . $numeric);
        }

        $this->output->writeln('<error>Invalid number</error>' . PHP_EOL);

        return $this->askForNumeric($question, ++$attempt);
    }

    private function askForEmail(
        Question $question,
        int      $attempt = 1
    ): string
    {
        $email = $this->ask($question);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        if ($attempt >= 3) {
            throw new InvalidArgumentException('Invalid email: ' . $email);
        }

        $this->output->writeln('<error>Invalid email</error>' . PHP_EOL);

        return $this->askForEmail($question, ++$attempt);
    }

    private function askForUrl(
        Question $question,
        int      $attempt = 1
    ): string
    {
        $url = $this->ask($question);

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if ($attempt >= 3) {
            throw new InvalidArgumentException('Invalid URL: ' . $url);
        }

        $this->output->writeln('<error>Invalid URL</error>' . PHP_EOL);
        $this->output->writeln('<comment>A valid format would be:</comment>' . PHP_EOL);
        $this->output->writeln('<href=https://jitsi-admin.de>https://jitsi-admin.de</>' . PHP_EOL);

        return $this->askForUrl($question, ++$attempt);
    }
    # endregion ask_types

    # region file_editing
    private function removeKeycloakProdConfig(BasicConfig $basicConfig): void
    {
        $prodKeycloakConfigPath = $this->projectDir . 'config' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'prod' . DIRECTORY_SEPARATOR . 'keycloak.yml';
        $devKeycloakConfigPath = $this->projectDir . 'config' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'keycloak.yml';
        if (
            str_starts_with($basicConfig->baseUrl(), 'http://')
            && file_exists($prodKeycloakConfigPath)
            && file_exists($devKeycloakConfigPath)
        ) {
            unlink($prodKeycloakConfigPath);
            rename($devKeycloakConfigPath, $prodKeycloakConfigPath);
        }
    }

    /** @param ConvertToEnvironmentInterface[] $convertibles */
    private function writeEnvFile(...$convertibles): void
    {
        $envVars = [];

        foreach ($convertibles as $convertible) {
            $envVars[] = '### Start: ' . $convertible::class . ' ###' . PHP_EOL;
            $envVars = array_merge($envVars, $convertible->getAsEnvironment());
            $envVars[] = '### End: ' . $convertible::class . ' ###' . PHP_EOL . PHP_EOL;
        }

        file_put_contents(filename: $this->projectDir . '.env.prod.local', data: $envVars);
    }

    private function writeWebsocketConfFile(BasicConfig $basicConfig): void
    {
        $lines = [
            'WEBSOCKET_SECRET=' . $basicConfig->secret() . PHP_EOL,
            'PORT=3000' . PHP_EOL,
            'AWAY_TIME=5' . PHP_EOL,
        ];

        file_put_contents(filename: $this->projectDir . 'installer' . DIRECTORY_SEPARATOR . 'jitsi-admin.conf', data: $lines);
    }
    # endregion file_editing
}
