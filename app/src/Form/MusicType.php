<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Music;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MusicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Ex : Bohemian Rhapsody'],
            ])
            ->add('auteur', TextType::class, [
                'label' => 'Artiste',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : Queen'],
            ])
            ->add('url', UrlType::class, [
                'label' => 'Lien',
                'default_protocol' => 'https',
                'help' => 'Un lien YouTube affiche directement le lecteur vidéo.',
                'attr' => ['placeholder' => 'https://www.youtube.com/watch?v=…'],
            ])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => '— Aucune —',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Music::class,
        ]);
    }
}
