<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * When there's a SIP caller in the meeting, we change the Tag to make that obvious
 */
class CallerTagService
{
    private const TAG_TITLE = 'sip.caller.tag';
    private const TAG_COLOR = '#000';
    private const TAG_BG_COLOR = '#ff5959';
    private const TAG_PRIORITY = 100_000; // set high because it means lower priority and we want this out of the way of normal tags

    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function addCallerTagToRoom(Rooms $room): void
    {
        $callerTag = $this->getOrCreateCallerTag();
        $room->setTag($callerTag);

        $this->entityManager->flush();
    }

    private function getOrCreateCallerTag(): Tag
    {
        $callerTag = $this->tagRepository->findOneBy([
            'title' => self::TAG_TITLE,
            'color' => self::TAG_COLOR,
            'backgroundColor' => self::TAG_BG_COLOR,
            'priority' => self::TAG_PRIORITY,
        ]);

        return $callerTag ?? $this->createCallerTag();
    }

    private function createCallerTag(): Tag
    {
        $callerTag = (new Tag())
            ->setTitle(self::TAG_TITLE)
            ->setColor(self::TAG_COLOR) // pastel red
            ->setBackgroundColor(self::TAG_BG_COLOR) // black
            ->setDisabled(true) // FIXME does the rest still work? would be cool to have it be "hidden" for normal use
            ->setPriority(self::TAG_PRIORITY)
        ;

        $this->entityManager->persist($callerTag);
        $this->entityManager->flush();

        return $callerTag;
    }
}
