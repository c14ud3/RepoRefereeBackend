<?php

namespace App\Controller;

use App\Entity\Moderation;
use App\Model\CommentsLogSource;
use App\Service\AuthService;
use App\Service\CommentLogService;
use App\Service\RepoRefereeGPTService;
use App\Service\RepoRefereeGroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GitHubController extends AbstractController
{
    #[Route('/github/hook/{auth}', name: 'app_git_hub')]
    public function index(
		RepoRefereeGPTService $gpt,
		RepoRefereeGroqService $groq,
		EntityManagerInterface $em,
		$auth
	): Response
    {
		// * Check authentication
		if (!AuthService::github($auth))
			return new Response('Unauthorized', 401);

		// * Load request data
		$REQUESTDATA = json_decode(file_get_contents('php://input') ?? '{}', true) ?? [];
		$githubEvent = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
		$githubAction = $REQUESTDATA['action'] ?? '';

		// * Check if we even have anything to do
		if(!(
			($githubEvent == 'issues' && $githubAction == 'opened') ||
			($githubEvent == 'issues' && $githubAction == 'edited') ||
			($githubEvent == 'issue_comment' && $githubAction == 'created') ||
			($githubEvent == 'issue_comment' && $githubAction == 'edited')
		)) {
			return new Response('Nothing to do.');
		}

		if(isset($REQUESTDATA['comment'])) {
			// dealing with an issue_comment
			$url = $REQUESTDATA['comment']['html_url'] ?? '';
			$title = $REQUESTDATA['issue']['title'] ?? '';
			$contextComments = [
				$REQUESTDATA['issue']['body'] ?? '', // the original issue comment is the first context comment
			];
			$comment = $REQUESTDATA['comment']['body'] ?? '';
		} else if(isset($REQUESTDATA['issue'])) {
			// ! 'issue' is set aswell in 'issue_comment'
			// dealing with an issue
			$url = $REQUESTDATA['issue']['html_url'] ?? '';
			$title = $REQUESTDATA['issue']['title'] ?? '';
			$contextComments = []; // an issue doesn't (yet) have context comments
			$comment = $REQUESTDATA['issue']['body'] ?? '';
		} else {
			return new Response('Corresponding data not found in request.', 400);
		}

		// * Check if the request is from a GitHub Bot -> break
		if(
			str_starts_with($comment, '/botio') || 
			str_contains($comment, '#### From: Bot.io')
		) {
			return new Response('Request from or to Bot detected. Ignoring.');
		}

		$commentIsToxic = false;

		// * Request to ChatGPT
		$gptResponse = $gpt->request(
			$title,
			$comment,
			$contextComments
		);

		// * If toxic: Request to Groq
		if($gptResponse['TEXT_TOXICITY'] ?? false)
		{
			$groqResponse = $groq->request(
				$title,
				$comment,
				$contextComments
			);

			if($groqResponse['TEXT_TOXICITY'] ?? false)
				$commentIsToxic = true;
		}

		// * Log the request
		$commentLogService = new CommentLogService();
		$commentLog = $commentLogService->log(
			$em,
			$url,
			$title,
			$comment,
			$contextComments,
			$commentIsToxic,
			$gptResponse['TOXICITY_REASONS'] ?? '',
			$gptResponse['VIOLATED_GUIDELINE'] ?? '',
			$gptResponse['REPHRASED_TEXT_OPTIONS'] ?? [],
			CommentsLogSource::from(strtoupper(explode(':', $auth)[0]))
		);

		// * If toxic again: add Comment & Response Moderation DB
		if($commentIsToxic)
		{
			// Add comment to Moderation DB
			$moderation = new Moderation();
			$moderation->setComment($commentLog);
			$moderation->setRemarks('');
			$moderation->setTimestamp(new \DateTime('now', new \DateTimeZone('Europe/Zurich')));
			$em->persist($moderation);
			$em->flush();
		}

		return new Response('OK');
    }
}
