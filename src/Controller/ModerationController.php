<?php

namespace App\Controller;

use App\Entity\Moderation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModerationController extends AbstractController
{
    #[Route('/moderation', name: 'app_moderation_without_auth')]
    public function indexWithoutAuth(): Response
    {
        return $this->redirectToRoute('app_moderation_comments', ['auth' => '-']);
    }

	#[Route('/moderation/{auth}', name: 'app_moderation')]
    public function index($auth): Response
    {
        return $this->redirectToRoute('app_moderation_comments', ['auth' => $auth]);
    }

	#[Route('/moderation/{auth}/comments', name: 'app_moderation_comments')]
    public function comments($auth): Response
    {
        return $this->render('moderation/comments.html.twig', [
			'auth' => $auth,
        ]);
    }

	#[Route('/moderation/{auth}/api/comments/{params}', name: 'api_moderation_comments')]
    public function commentsApi(EntityManagerInterface $em, $auth, $params): Response
    {
		// separate params
		list($param_filter, $param_order) = explode('-', $params);

		$criteria = [];
		if($param_filter == 'open') $criteria = ['Accepted' => null];
		else if($param_filter == 'accepted') $criteria = ['Accepted' => true];
		else if($param_filter == 'rejected') $criteria = ['Accepted' => false];

		$moderations = $em->getRepository(Moderation::class)->findBy(
			$criteria,
			['Timestamp' => $param_order == 'newest' ? 'DESC' : 'ASC'],
		);

		$return = [];

		foreach($moderations as $moderation) {
			$return[] = [
				'id' => $moderation->getId(),
				'accepted' => $moderation->isAccepted(),
				'title' => $moderation->getComment()->getTitle(),
				'url' => $moderation->getComment()->getUrl(),
				'timestamp' => $moderation->getTimestamp()->format('Y-m-d H:i:s'),
			];
		}

		return new Response(json_encode($return));
    }

	#[Route('/moderation/{auth}/comment/{comment_id}', name: 'app_moderation_comment')]
    public function comment($auth, $comment_id): Response
    {
        return $this->render('moderation/detail.html.twig', [
			'auth' => $auth,
        ]);
    }
}
