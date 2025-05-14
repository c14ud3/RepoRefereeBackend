<?php

namespace App\Controller;

use App\Entity\CommentsLog;
use App\Entity\Moderation;
use App\Model\CommentsLogSource;
use App\Service\AuthService;
use App\Service\CommentLogService;
use App\Service\RepoRefereeGPTService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OpenAI\Exceptions\TransporterException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    #[Route('/comment/{auth}', name: 'comment')]
    public function comment(
		RepoRefereeGPTService $gpt,
		EntityManagerInterface $em,
		$auth
	): Response
    {
		// * Check authentication
		if (!AuthService::checker($auth))
			return new Response('Unauthorized', 401);

		try
		{
			// * Load request data
			$REQUESTDATA = json_decode(file_get_contents('php://input') ?? '{}', true) ?? [];

			// * Check for required attributes
			if(!isset($REQUESTDATA['url']) || empty($REQUESTDATA['url']))
				return new Response('Attribute \'url\' (STRING) must be set.', 400);

			if(!isset($REQUESTDATA['title']) || empty($REQUESTDATA['title']))
				return new Response('Attribute \'title\' (STRING) must be set.', 400);

			if(!isset($REQUESTDATA['comment']) || empty($REQUESTDATA['comment']))
				return new Response('Attribute \'comment\' (STRING) must be set.', 400);

			if(!isset($REQUESTDATA['contextComments']) || !is_array($REQUESTDATA['contextComments']))
				return new Response('Attribute \'contextComments\' (ARRAY) must be set.', 400);

			// * If the comment has already been processed earlier, return the previous values
			$previousCommentData = $em->getRepository(CommentsLog::class)->findBy([
				'url' => $REQUESTDATA['url'],
				'comment' => $REQUESTDATA['comment'],
				'source' => CommentsLogSource::BUGZILLA
			]);

			if(count($previousCommentData) > 0)
				return new Response(json_encode([
					'TEXT_TOXICITY' => $previousCommentData[0]->isToxic(),
					'TOXICITY_REASONS' => $previousCommentData[0]->getToxicityReasons(),
					'VIOLATED_GUIDELINE' => $previousCommentData[0]->getViolatedGuideline(),
					'REPHRASED_TEXT_OPTIONS' => json_decode($previousCommentData[0]->getRephrasedTextOptions()),
				]));
			
			// * Request to ChatGPT
			$response = $gpt->request(
				strval($REQUESTDATA['title']),
				strval($REQUESTDATA['comment']),
				$REQUESTDATA['contextComments']
			);

			// * Log the request
			$commentLogService = new CommentLogService();
			$commentLog = $commentLogService->log(
				$em,
				$REQUESTDATA['url'] ?? '',
				$REQUESTDATA['title'] ?? '',
				$REQUESTDATA['comment'] ?? '',
				$contextComments ?? [],
				$response['TEXT_TOXICITY'] ?? false,
				$response['TOXICITY_REASONS'] ?? '',
				$response['VIOLATED_GUIDELINE'] ?? '',
				$response['REPHRASED_TEXT_OPTIONS'] ?? [],
				CommentsLogSource::BUGZILLA
			);

			// * If toxic: add Comment & Response to Moderation DB
			if($response['TEXT_TOXICITY'] ?? false)
			{
				$moderation = new Moderation();
				$moderation->setComment($commentLog);
				$moderation->setRemarks('');
				$moderation->setTimestamp(new \DateTime('now', new \DateTimeZone('Europe/Zurich')));
				$em->persist($moderation);
				$em->flush();
			}

			return new Response(json_encode($response));
		}
		catch(TransporterException $e)
		{
			// check for Timeout
			if (str_contains($e->getMessage(), 'cURL error 28'))
				return new Response('The request to ChatGPT took too long.', 408);
			return new Response($e->getMessage(), 500);
		}
		catch(Exception $e)
		{
			return new Response($e->getMessage(), 500);
		}

    }
}
