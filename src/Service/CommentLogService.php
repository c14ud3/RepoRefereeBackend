<?php

namespace App\Service;

use App\Entity\CommentsLog;
use Doctrine\ORM\EntityManagerInterface;

class CommentLogService
{
	public function log(
		EntityManagerInterface $entityManager,
		string $url,
		string $title,
		string $comment,
		array $contextComments,
		bool $toxic,
		string $toxicityReasons,
		string $violatedGuideline,
		array $rephrasedTextOptions
	): void
	{
		$commentLog = new CommentsLog();
		$commentLog->setUrl($url);
		$commentLog->setTitle($title);
		$commentLog->setComment($comment);
		$commentLog->setContextComments(json_encode($contextComments));
		$commentLog->setToxic($toxic);
		$commentLog->setToxicityReasons($toxicityReasons);
		$commentLog->setViolatedGuideline($violatedGuideline);
		$commentLog->setRephrasedTextOptions(json_encode($rephrasedTextOptions));
		$commentLog->setTimestamp(new \DateTime('now', new \DateTimeZone('Europe/Zurich')));

		$entityManager->persist($commentLog);
		$entityManager->flush();
	}
}