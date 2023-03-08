<?php

namespace App\Form;

use App\Entity\Navette;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\ORM\EntityRepository;

class NavetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('objet', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required"=>false,
                "label"=>"Objet",
                "attr"=>[
                    "class"=>"form-control"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\Length(["min"=>3,
                        "minMessage"=>'Ce champ doit contenir aux moins {{ limit }} caractères'])
                ]
            ])  
             ->add('contenu', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                "required"=>true,
                "label"=>"Contenu",
                "attr"=>[
                    "class"=>"form-control navetteNote"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\Length(["min"=>10,
                        "minMessage"=>'Ce champ doit contenir aux moins {{ limit }} caractères'])
                ]
            ])  
            ;
       
       
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Navette::class,
        ]);
    }
}
