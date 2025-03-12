<?php

namespace App\Service;

use App\Entity\CommentsLog;
use App\Model\CommentsLogSource;
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
		array $rephrasedTextOptions,
		CommentsLogSource $source
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
		$commentLog->setSource($source);

		$entityManager->persist($commentLog);
		$entityManager->flush();
	}
}