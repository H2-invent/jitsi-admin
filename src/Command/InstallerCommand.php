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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:install', description: 'Jitsi admin installer')]
class InstallerCommand extends Command
{
    private string $projectDir;

    public function __construct(ParameterBagInterface $parameterBag, string $name = null)
    {
        $this->projectDir = $parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if(file_exists($this->projectDir . '.env.prod.local')){
            return self::SUCCESS;
        }

        $helper = $this->getHelper('question');

        try {
            $baseConfig = $this->getBaseConfig(input: $input, output: $output, helper: $helper);
            $dbConfig = $this->getDBConfig(input: $input, output: $output, helper: $helper);
            $smtpConfig = $this->getSmtpConfig(input: $input, output: $output, helper: $helper);
            $keycloakConfig = $this->getKeycloakConfig(input: $input, output: $output, helper: $helper);
            $this->writeWebsocketConfFile($baseConfig);
            $this->writeEnvFile($baseConfig, $dbConfig, $smtpConfig, $keycloakConfig);
        } catch (InvalidArgumentException $e) {
            return Command::FAILURE;
        }

        $output->writeln('<info>Configuration done!</info>');

        return Command::SUCCESS;
    }

    private function getDBConfig(InputInterface $input, OutputInterface $output, QuestionHelper $helper): DbConfig
    {
        $dbExistsQuestion = new ConfirmationQuestion('<question>Do you already have an existing database? [y/N]</question>' . PHP_EOL, false, '/^(y|j)/i');
        $dbExists = $helper->ask($input, $output, $dbExistsQuestion);

        if ($dbExists) {
            $serverVersionQuestion = new Question('<question>Enter the mysql server version: [5.7]</question>' . PHP_EOL);
            $hostQuestion = new Question('<question>Enter the database host: [localhost]</question>', 'localhost' . PHP_EOL);
            $portQuestion = new Question('<question>Enter the database port: [3306]</question>' . PHP_EOL, 3306);
            $databaseQuestion = new Question('<question>Enter the database name: [jitsi-admin]</question>' . PHP_EOL, 'jitsi-admin');
            $usernameQuestion = new Question('<question>Enter the database username: [root]</question>' . PHP_EOL, 'root');
            $passwordQuestion = new Question('<question>Enter the database password: [root]</question>' . PHP_EOL, 'root');

            return DbConfig::createFromParameters(
                engine: 'mysql',
                serverVersion: $helper->ask($input, $output, $serverVersionQuestion),
                host: $helper->ask($input, $output, $hostQuestion),
                port: $this->askForNumeric($input, $output, $helper, $portQuestion),
                database: $helper->ask($input, $output, $databaseQuestion),
                username: $helper->ask($input, $output, $usernameQuestion),
                password: $helper->ask($input, $output, $passwordQuestion),
            );
        }

        return DbConfig::createFromDefault();
    }

    private function getSmtpConfig(InputInterface $input, OutputInterface $output, QuestionHelper $helper): SmtpConfig
    {
        $hostQuestion = new Question('<question>Enter the smtp host: [localhost]</question>' . PHP_EOL, 'localhost');
        $portQuestion = new Question('<question>Enter the smtp port: [465]</question>' . PHP_EOL, '465');
        $usernameQuestion = new Question('<question>Enter the smtp username: [root]</question>' . PHP_EOL, 'root');
        $passwordQuestion = new Question('<question>Enter the smtp password: [root]</question>' . PHP_EOL, 'root');
        $senderQuestion = new Question('<question>Enter the default sender email: </question>' . PHP_EOL);

        return SmtpConfig::createFromParameters(
            host: $helper->ask($input, $output, $hostQuestion),
            port: $this->askForNumeric($input, $output, $helper, $portQuestion),
            username: $helper->ask($input, $output, $usernameQuestion),
            password: $helper->ask($input, $output, $passwordQuestion),
            sender: $this->askForEmail($input, $output, $helper, $senderQuestion),
        );
    }

    private function getKeycloakConfig(InputInterface $input, OutputInterface $output, QuestionHelper $helper): KeycloakConfig
    {
        $versionQuestion = new Question('<question>Enter the keycloak version: [21]</question>' . PHP_EOL, 21);
        $urlQuestion = new Question('<info>Your URL could look somewhat like [http://keycloak.domain.de]</info>' . PHP_EOL . '<question>Enter the keycloak URL: </question>' . PHP_EOL);
        $realmQuestion = new Question('<question>Enter the keycloak realm: [jitsi-admin]</question>' . PHP_EOL, 'jitsi-admin');
        $clientIdQuestion = new Question('<question>Enter the keycloak client id: </question>' . PHP_EOL);
        $clientSecretQuestion = new Question('<question>Enter the keycloak client secret: </question>' . PHP_EOL);

        return KeycloakConfig::createFromParameters(
            url: $this->askForUrl($input, $output, $helper, $urlQuestion),
            realm: $helper->ask($input, $output, $realmQuestion),
            version: $this->askForNumeric($input, $output, $helper, $versionQuestion),
            clientId: $helper->ask($input, $output, $clientIdQuestion),
            clientSecret: $helper->ask($input, $output, $clientSecretQuestion),
        );
    }

    private function getBaseConfig(InputInterface $input, OutputInterface $output, QuestionHelper $helper): BasicConfig
    {
        $baseUrlQuestion = new Question('<question>Enter the base url of the Jitsi-Admin: </question>' . PHP_EOL);

        return BasicConfig::createFromParameters(
            baseUrl: $this->askForUrl($input, $output, $helper, $baseUrlQuestion),
        );
    }

    /** @param ConvertToEnvironmentInterface[] $convertibles */
    private function writeEnvFile(...$convertibles): void
    {
        $envVars = [];

        foreach ($convertibles as $convertible) {
            $envVars[] = '### Start: '.$convertible::class.' ###'.PHP_EOL;
            $envVars = array_merge($envVars, $convertible->getAsEnvironment());
            $envVars[] = '### End: '.$convertible::class.' ###'.PHP_EOL.PHP_EOL;
        }

        file_put_contents(filename: $this->projectDir . '.env.prod.local', data: $envVars);
    }

    private function writeWebsocketConfFile(BasicConfig $basicConfig): void
    {
        $lines = [
            'WEBSOCKET_SECRET='.$basicConfig->secret().PHP_EOL,
            'PORT=3000'.PHP_EOL,
            'AWAY_TIME=5'.PHP_EOL,
        ];

        file_put_contents(filename: $this->projectDir . 'nodejs' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'websocket.conf', data: $lines);
    }

    private function askForUrl(
        InputInterface  $input,
        OutputInterface $output,
        QuestionHelper  $helper,
        Question        $question,
        int             $attempt = 1
    ): string
    {
        $url = $helper->ask($input, $output, $question);

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if ($attempt >= 3) {
            throw new InvalidArgumentException('Invalid URL: ' . $url);
        }

        $output->writeln('<error>Invalid URL</error>' . PHP_EOL);
        $output->writeln('<comment>A valid format would be:</comment>' . PHP_EOL);
        $output->writeln('<href=https://jitsi-admin.de>https://jitsi-admin.de</>' . PHP_EOL);
        return $this->askForUrl($input, $output, $helper, $question, ++$attempt);
    }

    private function askForEmail(
        InputInterface  $input,
        OutputInterface $output,
        QuestionHelper  $helper,
        Question        $question,
        int             $attempt = 1
    ): string
    {
        $email = $helper->ask($input, $output, $question);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        if ($attempt >= 3) {
            throw new InvalidArgumentException('Invalid email: ' . $email);
        }

        $output->writeln('<error>Invalid email</error>' . PHP_EOL);

        return $this->askForEmail($input, $output, $helper, $question, ++$attempt);
    }

    private function askForNumeric(
        InputInterface  $input,
        OutputInterface $output,
        QuestionHelper  $helper,
        Question        $question,
        int             $attempt = 1
    ): int{
        $numeric = $helper->ask($input, $output, $question);

        if(is_numeric($numeric)){
            return (int) $numeric;
        }

        if ($attempt >= 3) {
            throw new InvalidArgumentException('Invalid number: ' . $numeric);
        }

        $output->writeln('<error>Invalid number</error>' . PHP_EOL);

        return $this->askForNumeric($input, $output, $helper, $question, ++$attempt);
    }
}