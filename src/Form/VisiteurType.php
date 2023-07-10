<?php

namespace App\Form;

use App\Entity\Visiteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class VisiteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                "required" => false
            ])
            ->add('prenom', TextType::class, [
                "required" => false
            ])
            ->add('cin', TextType::class, [
                "required" => false
            ])
            ->add("motif", TextareaType::class, [
                "required" => false
            ])
            ->add('fileName', HiddenType::class, [
                "required" => false
            ])
            ->add('heureEntrer', HiddenType::class)
            ->add('heureSortie', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Visiteur::class,
        ]);
    }
}