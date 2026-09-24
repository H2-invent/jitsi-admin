<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * When there's a SIP caller in the meeting, we change the Tag to make that obvious
 */
class CallerTagService
{
    private const TAG_TITLE = 'sip.caller.tag';
    private const TAG_COLOR = '#000'; // black
    private const TAG_BG_COLOR = '#ff5959'; // pastel red
    private const TAG_PRIORITY = 100_000; // set high because it means lower priority and we want this out of the way of normal tags

    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(param: 'app.sip.caller_tag.id')]
        private readonly ?string $idSipCallerTag,
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
        if ($this->idSipCallerTag === null || $this->idSipCallerTag === '') {
            $callerTag = $this->tagRepository->findOneBy(['title' => self::TAG_TITLE]);
        } else {
            $callerTag = $this->tagRepository->find((int)$this->idSipCallerTag);
        }

        if ($callerTag === null) {
            return $this->createCallerTag();
        }

        return $this->updateCallerTag($callerTag);
    }

    private function updateCallerTag(Tag $callerTag): Tag
    {
        $callerTag
            ->setColor(self::TAG_COLOR)
            ->setBackgroundColor(self::TAG_BG_COLOR)
            ->setDisabled(true)
            ->setPriority(self::TAG_PRIORITY)
        ;
        // this only writes to DB if something has changed, so no unnecessary I/O
        $this->entityManager->flush();

        return $callerTag;
    }

    private function createCallerTag(): Tag
    {
        $callerTag = (new Tag())
            ->setTitle(self::TAG_TITLE)
            ->setColor(self::TAG_COLOR)
            ->setBackgroundColor(self::TAG_BG_COLOR)
            ->setDisabled(true)
            ->setPriority(self::TAG_PRIORITY)
        ;

        $this->entityManager->persist($callerTag);
        $this->entityManager->flush();

        return $callerTag;
    }
}
