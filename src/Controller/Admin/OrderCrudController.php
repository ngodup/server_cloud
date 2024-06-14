<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderProduct;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Order')
            ->setEntityLabelInPlural('Orders')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Liste de commande')
            ->setPageTitle('new', 'Créer une nouvelle commande')
            ->setPageTitle('edit', 'Editer lordre')
            ->setPageTitle('detail', 'Détails de la commande');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield DateTimeField::new('createdAt')->setLabel('Created at');
        yield DateTimeField::new('updatedAt')->setLabel('Updated at')->onlyOnDetail();
        yield ChoiceField::new('status')
            ->setChoices([
                'En attente de paiement' => 'En attente',
                'Paiement accepté' => 'Paiement accepté',
                'Prêt pour expédition' => "Prêt pour l'expédition ",
                'Expédié' => 'Expédié',
                'Livré' => 'Livré',
                'Annulé' => 'Annulé',
                'Remboursé' => 'Remboursé',
            ])
            ->setRequired(true);
        yield IntegerField::new('totalPrice')->setLabel('Total price');
        yield ChoiceField::new('paymentMethod')->setChoices([
            'Carte de crédit' => 'credit card',
            'PayPal' => 'paypal',
            'Paiement à la livraison' => 'cash on delivery',
        ])->setLabel('Méthode de paiement');
        yield AssociationField::new('customer')
            ->setLabel('Customer')
            ->setFormTypeOption('choice_label', 'email'); // Assuming you want to display the user's email
        yield AssociationField::new('orderProducts')
            ->setLabel('Order products')
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('choice_label', function (OrderProduct $orderProduct) {
                return sprintf('%s (ID: %d)', $orderProduct->getProduct()->getName(), $orderProduct->getId());
            });
    }


    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Ajouter');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Editer');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            })
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('Voir');
            });;
    }
}
