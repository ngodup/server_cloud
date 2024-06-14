<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Comment')
            ->setEntityLabelInPlural('Comments')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Liste des commentaires')
            ->setPageTitle('new', 'Créer un nouveau commentaire')
            ->setPageTitle('edit', 'Modifier le commentaire')
            ->setPageTitle('detail', 'Détails du commentaire');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('content')
            ->setLabel('Content');
        yield DateTimeField::new('createdAt')
            ->setLabel('Created at');
        yield AssociationField::new('author')
            ->setLabel('Author')
            ->setFormTypeOption('choice_label', function (User $user) {
                $userProfile = $user->getUserProfile();
                if ($userProfile) {
                    return sprintf('%s %s', $userProfile->getNom(), $userProfile->getPrenom());
                }
                return '';
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
                return $action->setLabel('Modifier');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            })
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('Voir');
            });
    }
}
