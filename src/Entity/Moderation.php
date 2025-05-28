<?php

namespace App\Entity;

use App\Model\Satisfaction;
use App\Model\TimeSelector;
use App\Model\User;
use App\Repository\ModerationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use \App\Model\ToxicityLevel;

#[ORM\Entity(repositoryClass: ModerationRepository::class)]
class Moderation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'Moderations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CommentsLog $Comment = null;

	#[ORM\Column(nullable: true, enumType: ToxicityLevel::class)]
    private ?ToxicityLevel $ToxicityLevel = null;

    #[ORM\Column(nullable: true, enumType: TimeSelector::class)]
    private ?TimeSelector $TimeUsed = null;

    #[ORM\Column(nullable: true, enumType: Satisfaction::class)]
    private ?Satisfaction $SatisfactionToxicityExplanation = null;

    #[ORM\Column(nullable: true, enumType: Satisfaction::class)]
    private ?Satisfaction $SatisfactionGuidelinesReference = null;

    #[ORM\Column(nullable: true, enumType: Satisfaction::class)]
    private ?Satisfaction $SatisfactionRephrasingOptions = null;

    #[ORM\Column(length: 10000)]
    private ?string $Remarks = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $Timestamp = null;

    #[ORM\Column(nullable: true, enumType: User::class)]
    private ?User $User = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?CommentsLog
    {
        return $this->Comment;
    }

    public function setComment(?CommentsLog $Comment): static
    {
        $this->Comment = $Comment;

        return $this;
    }

	public function getToxicityLevel(): ?ToxicityLevel
    {
        return $this->ToxicityLevel;
    }

    public function setToxicityLevel(?ToxicityLevel $ToxicityLevel): static
    {
        $this->ToxicityLevel = $ToxicityLevel;

        return $this;
    }

    public function getTimeUsed(): ?TimeSelector
    {
        return $this->TimeUsed;
    }

    public function setTimeUsed(?TimeSelector $TimeUsed): static
    {
        $this->TimeUsed = $TimeUsed;

        return $this;
    }

    public function getSatisfactionToxicityExplanation(): ?Satisfaction
    {
        return $this->SatisfactionToxicityExplanation;
    }

    public function setSatisfactionToxicityExplanation(?Satisfaction $SatisfactionToxicityExplanation): static
    {
        $this->SatisfactionToxicityExplanation = $SatisfactionToxicityExplanation;

        return $this;
    }

    public function getSatisfactionGuidelinesReference(): ?Satisfaction
    {
        return $this->SatisfactionGuidelinesReference;
    }

    public function setSatisfactionGuidelinesReference(?Satisfaction $SatisfactionGuidelinesReference): static
    {
        $this->SatisfactionGuidelinesReference = $SatisfactionGuidelinesReference;

        return $this;
    }

    public function getSatisfactionRephrasingOptions(): ?Satisfaction
    {
        return $this->SatisfactionRephrasingOptions;
    }

    public function setSatisfactionRephrasingOptions(?Satisfaction $SatisfactionRephrasingOptions): static
    {
        $this->SatisfactionRephrasingOptions = $SatisfactionRephrasingOptions;

        return $this;
    }

    public function getRemarks(): ?string
    {
        return $this->Remarks;
    }

    public function setRemarks(string $Remarks): static
    {
        $this->Remarks = $Remarks;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->Timestamp;
    }

    public function setTimestamp(\DateTimeInterface $Timestamp): static
    {
        $this->Timestamp = $Timestamp;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $user): static
    {
        $this->User = $user;

        return $this;
    }
}
