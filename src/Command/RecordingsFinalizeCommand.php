<?php

namespace App\Command;

use App\Service\RecordingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recordings:finalize',
    description: 'Finalizes upload of a certain recordings chunks after it failed after the upload.',
)]
class RecordingsFinalizeCommand extends Command
{
    public function __construct(
        private readonly RecordingService $recordingService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('recording_uid', InputArgument::REQUIRED, 'Recording UID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uidRecording = $input->getArgument('recording_uid');

        $result = $this->recordingService->finalizeUpload($uidRecording);

        if ($result->isFailure()) {
            $io->error("{$result->getErrorType()->value} \nrecording_uid: {$uidRecording}");

            return Command::FAILURE;
        }

        $io->success('Successfully joined the chunks, saved in Gaufrette filesystem and sent mail!');

        return Command::SUCCESS;
    }
}
