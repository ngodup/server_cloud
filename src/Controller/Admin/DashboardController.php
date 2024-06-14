<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderProduct;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(ProductCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Produits', 'fa-brands fa-product-hunt', Product::class);
        yield MenuItem::linkToCrud('Commandes', 'fa-brands fa-first-order-alt', Order::class);
        yield MenuItem::linkToCrud('Commander des produits', 'fa-brands fa-jedi-order', OrderProduct::class);
        yield MenuItem::linkToCrud('Commentaire', 'fa-regular fa-comment', Comment::class);
    }
}
