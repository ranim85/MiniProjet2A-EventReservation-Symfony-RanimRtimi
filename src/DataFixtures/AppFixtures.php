<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Event;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $eventsData = [
            [
                'title' => 'Concert de Rock: Thunderstorm',
                'description' => 'Un grand concert de rock de plein air pour enflammer la nuit.',
                'date' => new \DateTime('+2 weeks'),
                'location' => 'Stade de France, Paris',
                'seats' => 50000,
                'image' => 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?q=80&w=1000&auto=format&fit=crop',
            ],
            [
                'title' => 'Tech Innovate 2026',
                'description' => 'Les plus grands experts de la technologie discutent de l\'avenir de l\'IA et de la cybersécurité.',
                'date' => new \DateTime('+1 month'),
                'location' => 'Palais des Congrès, Montréal',
                'seats' => 2000,
                'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=1000&auto=format&fit=crop',
            ],
            [
                'title' => 'Exposition d\'Art Abstrait',
                'description' => 'Découvrez une panoplie d\'œuvres d\'art moderniste créées par les artistes émergents.',
                'date' => new \DateTime('+5 days'),
                'location' => 'Musée du Louvre, Paris',
                'seats' => 500,
                'image' => 'https://images.unsplash.com/photo-1543857778-c4a1a3e0b2eb?q=80&w=1000&auto=format&fit=crop',
            ],
            [
                'title' => 'Pièce de Théâtre: Le songe',
                'description' => 'Une représentation exclusive et dramatique qui vous tiendra en haleine du début à la fin.',
                'date' => new \DateTime('+3 weeks'),
                'location' => 'Opéra Garnier, Paris',
                'seats' => 1200,
                'image' => 'https://images.unsplash.com/photo-1507676184212-d0c30a3c2288?q=80&w=1000&auto=format&fit=crop',
            ],
            [
                'title' => 'Festival Gastronomique d\'Été',
                'description' => 'Plein de stands de nourriture internationale, dégustation de vins et musique en direct.',
                'date' => new \DateTime('+2 months'),
                'location' => 'Parc de la Villette, Paris',
                'seats' => 10000,
                'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=1000&auto=format&fit=crop',
            ]
        ];

        foreach ($eventsData as $data) {
            $event = new Event();
            $event->setTitle($data['title'])
                  ->setDescription($data['description'])
                  ->setDate($data['date'])
                  ->setLocation($data['location'])
                  ->setSeats($data['seats'])
                  ->setImage($data['image']);
            
            $manager->persist($event);
        }

        $manager->flush();
    }
}
