<?php

namespace App\Controller;

use App\Entity\Moderation;
use App\Model\CommentsLogSource;
use App\Service\AuthService;
use App\Service\CommentLogService;
use App\Service\RepoRefereeGPTService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GitHubController extends AbstractController
{
    #[Route('/github/hook/{auth}', name: 'app_git_hub')]
    public function index(
		RepoRefereeGPTService $gpt,
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

			// add all comments to the context comments by performing a request to the comment url
			try {
				$commentsRequest = file_get_contents($REQUESTDATA['issue']['comments_url'] ?? '');
				$commentsJSON = json_decode($commentsRequest, true) ?? [];
				foreach($commentsJSON as $comment) {
					if($comment['id'] != $REQUESTDATA['comment']['id']) {
						$contextComments[] = $comment['body'] ?? '';
					}
				}
			} catch(\Exception $e) {}

			// remove all empty strings from contextComments
			$contextComments = array_filter($contextComments, function($comment) {
				return !empty(trim($comment));
			});

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

		// * Request to ChatGPT
		$response = $gpt->request(
			$title,
			$comment,
			$contextComments
		);

		// * Log the request
		$commentLogService = new CommentLogService();
		$commentLog = $commentLogService->log(
			$em,
			$url,
			$title,
			$comment,
			$contextComments,
			$response['TEXT_TOXICITY'] ?? false,
			$response['TOXICITY_REASONS'] ?? '',
			$response['VIOLATED_GUIDELINE'] ?? '',
			$response['REPHRASED_TEXT_OPTIONS'] ?? [],
			CommentsLogSource::from(strtoupper(explode(':', $auth)[0]))
		);

		// * If toxic: add Comment & Response Moderation DB
		if($response['TEXT_TOXICITY'] ?? false)
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
