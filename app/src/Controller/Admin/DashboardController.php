<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Music;
use App\Entity\Playlist;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\MusicRepository;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly MusicRepository $musicRepo,
        private readonly CategoryRepository $categoryRepo,
        private readonly PlaylistRepository $playlistRepo,
        private readonly UserRepository $userRepo,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/my-dashboard.html.twig', [
            'stats' => [
                'Morceaux' => $this->musicRepo->count([]),
                'Catégories' => $this->categoryRepo->count([]),
                'Playlists' => $this->playlistRepo->count([]),
                'Utilisateurs' => $this->userRepo->count([]),
            ],
            'categories' => $this->categoryRepo->findAllWithCount(),
            'mostLiked' => $this->musicRepo->findMostLiked(5),
            'latest' => $this->musicRepo->findLatest(5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('YouMusic — Administration')
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');
        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Morceaux', 'fa fa-music', Music::class);
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Playlists', 'fa fa-list', Playlist::class);
        yield MenuItem::section('Comptes');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::section();
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'app_home');
    }
}
