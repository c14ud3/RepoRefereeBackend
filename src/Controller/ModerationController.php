<?php

namespace App\Controller;

use App\Entity\Moderation;
use App\Model\Satisfaction;
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
        // * Check authentication
		// TODO
		
        return $this->render('moderation/comments.html.twig', [
			'auth' => $auth,
        ]);
    }

	#[Route('/moderation/{auth}/api/comments/{params}', name: 'api_moderation_comments')]
    public function commentsApi(EntityManagerInterface $em, $auth, $params): Response
    {
		// * Check authentication
		// TODO
		
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
    public function comment(EntityManagerInterface $em, $auth, $comment_id): Response
    {
		// * Check authentication
		// TODO

        $moderation = $em->getRepository(Moderation::class)->find($comment_id);

		$return_moderation = [];
		$return_comment = [];

		if ($moderation != null) {
			$comment = $moderation->getComment();

			$return_moderation = [
				'id' => $moderation->getId(),
				'accepted' => $moderation->isAccepted(),
				'timeUsed' => $moderation->getTimeUsed(),
				'satisfactionToxicityExplanation' => $moderation->getSatisfactionToxicityExplanation(),
				'satisfactionGuidelinesReference' => $moderation->getSatisfactionGuidelinesReference(),
				'satisfactionRephrasingOptions' => $moderation->getSatisfactionRephrasingOptions(),
				'remarks' => $moderation->getRemarks(),
				'timestamp' => $moderation->getTimestamp()->format('Y-m-d H:i:s'),
			];

			$return_comment = [
				'url' => $comment->getUrl(),
				'title' => $comment->getTitle(),
				'comment' => $comment->getComment(),
				'contextComments' => json_decode($comment->getContextComments()),
				'toxicityReasons' => $comment->getToxicityReasons(),
				'violatedGuideline' => $comment->getViolatedGuideline(),
				'rephrasedTextOptions' => json_decode($comment->getRephrasedTextOptions()),
			];
		}
		
		return $this->render('moderation/detail.html.twig', [
			'auth' => $auth,
			'comment_found' => $moderation != null,
			'moderation' => $return_moderation,
			'comment' => $return_comment,
        ]);
    }

	#[Route('/moderation/{auth}/api/comment/{moderation_id}', name: 'api_moderation_comment')]
    public function commentApi(EntityManagerInterface $em, $auth, $moderation_id): Response
    {
		// * Check authentication
		// TODO

        $moderation = $em->getRepository(Moderation::class)->find($moderation_id);

		$moderation->setAccepted(boolval($_POST['accepted']));
		$moderation->setTimeUsed(intval($_POST['timeUsed']));
		$moderation->setSatisfactionToxicityExplanation(Satisfaction::from($_POST['satisfactionToxicityExplanation']));
		$moderation->setSatisfactionGuidelinesReference(Satisfaction::from($_POST['satisfactionGuidelinesReference']));
		$moderation->setSatisfactionRephrasingOptions(Satisfaction::from($_POST['satisfactionRephrasingOptions']));
		$moderation->setRemarks($_POST['remarks']);
		
		$em->persist($moderation);
		$em->flush();

		return new Response();
    }
}
