<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GitHubController extends AbstractController
{
    #[Route('/github/hook/{auth}', name: 'app_git_hub')]
    public function index($auth): Response
    {
		// TODO: Check authentication

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
			$contextComments = [];
			$comment = $REQUESTDATA['comment']['body'] ?? '';
		} else if(isset($REQUESTDATA['issue'])) {
			// ! 'issue' is set aswell in 'issue_comment'
			// dealing with an issue
			$url = $REQUESTDATA['issue']['html_url'] ?? '';
			$title = $REQUESTDATA['issue']['title'] ?? '';
			$contextComments = [];
			$comment = $REQUESTDATA['issue']['body'] ?? '';
		} else {
			return new Response('Corresponding data not found in request.', 400);
		}

		return new Response(print_r([
			'url' => $url,
			'title' => $title,
			'contextComments' => $contextComments,
			'comment' => $comment,
		], true));
    }
}
