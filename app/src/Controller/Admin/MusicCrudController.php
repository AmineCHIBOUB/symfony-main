<?php

namespace App\Controller\Admin;

use App\Entity\Music;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class MusicCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Music::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Morceau')
            ->setEntityLabelInPlural('Morceaux')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['name', 'auteur']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('category')->add('owner');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Titre');
        yield TextField::new('auteur', 'Artiste');
        yield UrlField::new('url', 'Lien')->hideOnIndex();
        yield AssociationField::new('category', 'Catégorie');
        yield AssociationField::new('owner', 'Ajouté par');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail();
    }
}
