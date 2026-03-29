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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

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
    public function reserve(int $id, Request $request, EventRepository $repo, EntityManagerInterface $em, MailerInterface $mailer): Response
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

            // Envoi de l'email de confirmation
            try {
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@eventreservation.com', 'Event Reservation Team'))
                    ->to($reservation->getEmail())
                    ->subject('Confirmation de votre réservation : ' . $event->getTitle())
                    ->htmlTemplate('emails/reservation_confirmation.html.twig')
                    ->context([
                        'reservation' => $reservation,
                        'event' => $event,
                    ]);

                $mailer->send($email);
            } catch (\Exception $e) {
                // On log l'erreur ou on gère silencieusement (pour ne pas bloquer l'utilisateur)
                $this->addFlash('warning', 'La réservation est confirmée, mais l\'envoi du mail a échoué.');
            }

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