<?php

namespace App\Command;

use App\Repository\UploadedRecordingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\FilesystemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recordings:cleanup',
    description: 'Deletes recordings older than a specified number of days',
)]
class CleanUpRecordingCommand extends Command
{


    public function __construct(
        private FilesystemInterface         $recordingFilesystem,
        private EntityManagerInterface      $entityManager,
        private UploadedRecordingRepository $uploadedRecordingRepository
    )
    {
        parent::__construct();

    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Age of recordings to delete (in days)',10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get the number of days from the argument
        $days = (int)$input->getArgument('days');
        if ($days <= 0) {
            $io->error('The number of days must be greater than 0.');
            return Command::FAILURE;
        }

        $io->info("Cleaning up recordings older than {$days} days...");

        // Calculate the cutoff date
        $cutoffDate = new \DateTimeImmutable("-{$days} days");

        // Fetch recordings older than the cutoff date
        $recordings = $this->uploadedRecordingRepository->createQueryBuilder('r')
            ->where('r.createdAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->getResult();

        if (empty($recordings)) {
            $io->success('No recordings to delete.');
            return Command::SUCCESS;
        }

        foreach ($recordings as $recording) {
            try {
                // Delete the file from the filesystem
                if ($this->recordingFilesystem->has($recording->getFilename())) {
                    $this->recordingFilesystem->delete($recording->getFilename());
                }

                // Remove the entity from the database
                $this->entityManager->remove($recording);
                $io->success(sprintf('Deleted recording: %s', $recording->getFilename()));
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Failed to delete recording "%s": %s',
                    $recording->getFilename(),
                    $e->getMessage()
                ));
            }
        }

        // Commit the changes to the database
        $this->entityManager->flush();

        $io->success('Cleanup complete.');
        return Command::SUCCESS;
    }
}
