<?php
namespace App\Controller\User;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EventRepository $repo): Response
    {
        return $this->render('user/event/index.html.twig', [
            'events' => $repo->findAll(),
        ]);
    }

    #[Route('/event/{id}', name: 'app_event_show')]
    public function show(int $id, EventRepository $repo): Response
    {
        $event = $repo->find($id);
        if (!$event) throw $this->createNotFoundException('Événement introuvable');
        return $this->render('user/event/show.html.twig', ['event' => $event]);
    }

    #[Route('/event/{id}/reserve', name: 'app_event_reserve', methods: ['GET', 'POST'])]
    public function reserve(int $id, Request $request, EventRepository $repo, EntityManagerInterface $em): Response
    {
        $event = $repo->find($id);
        if (!$event) throw $this->createNotFoundException();

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reservation);
            $em->flush();
            return $this->render('user/event/confirmation.html.twig', [
                'reservation' => $reservation,
                'event' => $event,
            ]);
        }

        return $this->render('user/event/reserve.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}