<?php

namespace App\Command;

use App\Service\SetupInitialService;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:setup:initial',
    description: 'Imports initial setup data from initial-setup.json',
)]
class SetupInitialCommand extends Command
{
    private const JSON_FILE_NAME = 'initial-setup.json';

    private string $jsonFileLocation;

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
        private Filesystem $filesystem,
        private ValidatorInterface $validator,
        private SetupInitialService $setupInitialService,
    )
    {
        parent::__construct();
        $this->jsonFileLocation = $projectDir . DIRECTORY_SEPARATOR . self::JSON_FILE_NAME;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->filesystem->exists($this->jsonFileLocation)) {
            $io->error("Could not find setup file at `{$this->jsonFileLocation}`");

            return Command::SUCCESS;
        }

        try {
            $jsonContent = $this->filesystem->readFile($this->jsonFileLocation);
            $content = json_decode($jsonContent, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException|IOException $e) {
            $io->error("Could not read JSON: {$e->getMessage()}");

            return Command::FAILURE;
        }

        if (!$this->validateJsonStructure($content, $io)) {
            return Command::FAILURE;
        }

        $this->setupInitialService->import($content);

        $io->success('Successfully imported user and server!');

        return Command::SUCCESS;
    }

    private function validateJsonStructure(array $json, SymfonyStyle $io): bool
    {
        $Constraints = new Constraints\Collection([
            'username' => [new Constraints\NotBlank(), new Constraints\Email()],
            'server' => new Constraints\Collection([
                'name' => new Constraints\NotBlank(),
                'url' => new Constraints\NotBlank(),
                'app_id' => new Constraints\NotBlank(),
                'app_secret' => new Constraints\NotBlank(),
                'keycloak_groups' => [new Constraints\NotBlank(), new Constraints\Type('array')],
                'middleware' => new Constraints\NotBlank(),
            ])
        ]);

        $violations = $this->validator->validate($json, $Constraints);

        if (count($violations) > 0) {
            $io->error('JSON structure is invalid:');
            foreach ($violations as $violation) {
                $io->writeln("- {$violation->getPropertyPath()}: {$violation->getMessage()}");
            }

            return false;
        }

        return true;
    }
}
