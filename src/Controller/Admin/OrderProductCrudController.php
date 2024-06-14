<?php

namespace App\Controller\Admin;

use App\Entity\OrderProduct;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class OrderProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderProduct::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Order product')
            ->setEntityLabelInPlural('Order products')
            ->setPageTitle('index', 'Lister le produit de la commande')
            ->setPageTitle('new', 'Créer un produit de commande')
            ->setPageTitle('edit', 'Modifier la commande de produits')
            ->setPageTitle('detail', 'Détail de la commande Produit');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('product')
            ->setLabel('Product')
            ->setFormTypeOption('choice_label', 'name');
        yield AssociationField::new('orderReference')
            ->setLabel('Order reference');
        yield IntegerField::new('quantity')
            ->setLabel('Quantity');
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
