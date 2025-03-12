<?php

namespace App\Entity;

use App\Model\CommentsLogSource;
use App\Repository\CommentsLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentsLogRepository::class)]
class CommentsLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1000)]
    private ?string $url = null;

    #[ORM\Column(length: 1000)]
    private ?string $title = null;

    #[ORM\Column(length: 100000)]
    private ?string $comment = null;

    #[ORM\Column(length: 1000000)]
    private ?string $contextComments = null;

    #[ORM\Column]
    private ?bool $toxic = null;

    #[ORM\Column(length: 100000)]
    private ?string $toxicity_reasons = null;

    #[ORM\Column(length: 100000)]
    private ?string $violated_guideline = null;

    #[ORM\Column(length: 1000000)]
    private ?string $rephrased_text_options = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(enumType: CommentsLogSource::class)]
    private ?CommentsLogSource $source = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getContextComments(): ?string
    {
        return $this->contextComments;
    }

    public function setContextComments(string $contextComments): static
    {
        $this->contextComments = $contextComments;

        return $this;
    }

    public function isToxic(): ?bool
    {
        return $this->toxic;
    }

    public function setToxic(bool $toxic): static
    {
        $this->toxic = $toxic;

        return $this;
    }

    public function getToxicityReasons(): ?string
    {
        return $this->toxicity_reasons;
    }

    public function setToxicityReasons(string $toxicity_reasons): static
    {
        $this->toxicity_reasons = $toxicity_reasons;

        return $this;
    }

    public function getViolatedGuideline(): ?string
    {
        return $this->violated_guideline;
    }

    public function setViolatedGuideline(string $violated_guideline): static
    {
        $this->violated_guideline = $violated_guideline;

        return $this;
    }

    public function getRephrasedTextOptions(): ?string
    {
        return $this->rephrased_text_options;
    }

    public function setRephrasedTextOptions(string $rephrased_text_options): static
    {
        $this->rephrased_text_options = $rephrased_text_options;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getSource(): ?CommentsLogSource
    {
        return $this->source;
    }

    public function setSource(CommentsLogSource $source): static
    {
        $this->source = $source;

        return $this;
    }
}
