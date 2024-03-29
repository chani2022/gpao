<?php

namespace App\Form;

use App\Entity\Interne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterneType extends AbstractType
{
    protected $id;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->id = $options['id'];

        $builder
            ->add('dates', HiddenType::class)
            ->add('matricule', TextType::class, [
                'attr' => [
                    'value' => $this->id
                ],
                "required" => false
            ])
            ->add('motifs', TextareaType::class, [
                "required" => false
            ])
            ->add('heuresortie', HiddenType::class)
            ->add('donneurOrdre', TextType::class, [
                "required" => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Interne::class,
            'id' => null
        ]);
    }
}