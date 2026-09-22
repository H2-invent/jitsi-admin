<?php

namespace App\Entity;

use App\Enums\RepeatMonthEnum;
use App\Enums\RepeatNumberEnum;
use App\Enums\RepeatTypeEnum;
use App\Enums\RepeatWeekdayEnum;
use App\Repository\RepeatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RepeatRepository::class)]
#[ORM\Table(name: '`repeat`')]
class Repeat
{
    public function __toString()
    {
        return (string) $this->id;
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $repetation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $repeatUntil = null;

    /**
     * @var Collection<int, Rooms>
     */
    #[ORM\OneToMany(targetEntity: Rooms::class, mappedBy: 'repeater')]
    private Collection $rooms;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'repeaterUsers')]
    private Collection $participants;

    /**
     * @var array<int, mixed>
     */
    #[ORM\Column(type: 'array')]
    private array $weekday = [];

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $weeks = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $months = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $days = null;

    #[ORM\Column(type: 'integer', enumType: RepeatTypeEnum::class)]
    private ?RepeatTypeEnum $repeatType = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $repeaterDays = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $repeaterWeeks = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $RepeatMontly = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $RepeatYearly = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startDate;

    #[ORM\OneToOne(targetEntity: Rooms::class, inversedBy: 'repeaterProtoype', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Rooms $prototyp = null;

    #[ORM\Column(type: 'integer', nullable: true, enumType: RepeatNumberEnum::class)]
    private ?RepeatNumberEnum $repatMonthRelativNumber = null;

    #[ORM\Column(type: 'integer', nullable: true, enumType: RepeatWeekdayEnum::class)]
    private ?RepeatWeekdayEnum $repatMonthRelativWeekday = null;

    #[ORM\Column(type: 'integer', nullable: true, enumType: RepeatNumberEnum::class)]
    private ?RepeatNumberEnum $repeatYearlyRelativeNumber = null;

    #[ORM\Column(type: 'integer', nullable: true, enumType: RepeatMonthEnum::class)]
    private ?RepeatMonthEnum $repeatYearlyRelativeMonth = null;

    #[ORM\Column(type: 'integer', nullable: true, enumType: RepeatWeekdayEnum::class)]
    private ?RepeatWeekdayEnum $repeatYearlyRelativeWeekday = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $repeatMonthlyRelativeHowOften = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $repeatYearlyRelativeHowOften = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $uid = null;
    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->participants = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getRepetation(): ?int
    {
        return $this->repetation;
    }
    public function setRepetation(?int $repetation): self
    {
        $this->repetation = $repetation;

        return $this;
    }
    public function getRepeatUntil(): ?\DateTimeImmutable
    {
        return $this->repeatUntil;
    }
    public function setRepeatUntil(?\DateTimeImmutable $repeatUntil): self
    {
        $this->repeatUntil = $repeatUntil;

        return $this;
    }
    /**
     * @return Collection<int, Rooms>
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }
    public function addRoom(Rooms $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms[] = $room;
            $room->setRepeater($this);
        }

        return $this;
    }
    public function removeRoom(Rooms $room): self
    {
        if ($this->rooms->removeElement($room)) {
            // set the owning side to null (unless already changed)
            if ($room->getRepeater() === $this) {
                $room->setRepeater(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }
    public function addParticipant(User $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
        }

        return $this;
    }
    public function removeParticipant(User $participant): self
    {
        $this->participants->removeElement($participant);

        return $this;
    }
    /**
     * @return array<int, mixed>|null
     */
    public function getWeekday(): ?array
    {
        return $this->weekday;
    }
    /**
     * @param array<int, mixed> $weekday
     */
    public function setWeekday(array $weekday): self
    {
        $this->weekday = $weekday;

        return $this;
    }
    public function getWeeks(): ?int
    {
        return $this->weeks;
    }
    public function setWeeks(?int $weeks): self
    {
        $this->weeks = $weeks;

        return $this;
    }
    public function getMonths(): ?int
    {
        return $this->months;
    }
    public function setMonths(?int $months): self
    {
        $this->months = $months;

        return $this;
    }
    public function getDays(): ?int
    {
        return $this->days;
    }
    public function setDays(?int $days): self
    {
        $this->days = $days;

        return $this;
    }
    public function getRepeatType(): ?RepeatTypeEnum
    {
        return $this->repeatType;
    }
    public function setRepeatType(RepeatTypeEnum $repeatType): self
    {
        $this->repeatType = $repeatType;

        return $this;
    }
    public function getRepeaterDays(): ?int
    {
        return $this->repeaterDays;
    }
    public function setRepeaterDays(?int $repeaterDays): self
    {
        $this->repeaterDays = $repeaterDays;

        return $this;
    }
    public function getRepeaterWeeks(): ?int
    {
        return $this->repeaterWeeks;
    }
    public function setRepeaterWeeks(?int $repeaterWeeks): self
    {
        $this->repeaterWeeks = $repeaterWeeks;

        return $this;
    }
    public function getRepeatMontly(): ?int
    {
        return $this->RepeatMontly;
    }
    public function setRepeatMontly(?int $RepeatMontly): self
    {
        $this->RepeatMontly = $RepeatMontly;

        return $this;
    }
    public function getRepeatYearly(): ?int
    {
        return $this->RepeatYearly;
    }
    public function setRepeatYearly(?int $RepeatYearly): self
    {
        $this->RepeatYearly = $RepeatYearly;

        return $this;
    }
    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }
    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }
    public function getPrototyp(): ?Rooms
    {
        return $this->prototyp;
    }
    public function setPrototyp(?Rooms $prototyp): self
    {
        $this->prototyp = $prototyp;

        return $this;
    }
    public function getRepatMonthRelativNumber(): ?RepeatNumberEnum
    {
        return $this->repatMonthRelativNumber;
    }
    public function setRepatMonthRelativNumber(?RepeatNumberEnum $repatMonthRelativNumber): self
    {
        $this->repatMonthRelativNumber = $repatMonthRelativNumber;

        return $this;
    }
    public function getRepatMonthRelativWeekday(): ?RepeatWeekdayEnum
    {
        return $this->repatMonthRelativWeekday;
    }
    public function setRepatMonthRelativWeekday(?RepeatWeekdayEnum $repatMonthRelativWeekday): self
    {
        $this->repatMonthRelativWeekday = $repatMonthRelativWeekday;

        return $this;
    }
    public function getRepeatYearlyRelativeNumber(): ?RepeatNumberEnum
    {
        return $this->repeatYearlyRelativeNumber;
    }
    public function setRepeatYearlyRelativeNumber(?RepeatNumberEnum $repeatYearlyRelativeNumber): self
    {
        $this->repeatYearlyRelativeNumber = $repeatYearlyRelativeNumber;

        return $this;
    }
    public function getRepeatYearlyRelativeMonth(): ?RepeatMonthEnum
    {
        return $this->repeatYearlyRelativeMonth;
    }
    public function setRepeatYearlyRelativeMonth(?RepeatMonthEnum $repeatYearlyRelativeMonth): self
    {
        $this->repeatYearlyRelativeMonth = $repeatYearlyRelativeMonth;

        return $this;
    }
    public function getRepeatYearlyRelativeWeekday(): ?RepeatWeekdayEnum
    {
        return $this->repeatYearlyRelativeWeekday;
    }
    public function setRepeatYearlyRelativeWeekday(?RepeatWeekdayEnum $repeatYearlyRelativeWeekday): self
    {
        $this->repeatYearlyRelativeWeekday = $repeatYearlyRelativeWeekday;

        return $this;
    }
    public function getRepeatMonthlyRelativeHowOften(): ?int
    {
        return $this->repeatMonthlyRelativeHowOften;
    }
    public function setRepeatMonthlyRelativeHowOften(?int $repeatMonthlyRelativeHowOften): self
    {
        $this->repeatMonthlyRelativeHowOften = $repeatMonthlyRelativeHowOften;

        return $this;
    }
    public function getRepeatYearlyRelativeHowOften(): ?int
    {
        return $this->repeatYearlyRelativeHowOften;
    }
    public function setRepeatYearlyRelativeHowOften(?int $repeatYearlyRelativeHowOften): self
    {
        $this->repeatYearlyRelativeHowOften = $repeatYearlyRelativeHowOften;

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }
}
