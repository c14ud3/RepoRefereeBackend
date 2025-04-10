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

		return new Response(print_r([
			'REQUESTDATA' => $REQUESTDATA,
			'POST' => $_POST,
			'GET' => $_GET,
			'SERVER' => $_SERVER,
		], true));
    }
}
