<?php

namespace App\Form;

use App\Entity\CDC;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class CDCType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nom_cdc', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required"=>true,
                "label"=>"Nom du CDC",
                "attr"=>[
                    "class"=>"form-control"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\Length(["min"=>3,
                        "minMessage"=>'Ce champ doit contenir aux moins {{ limit }} caractères'])
                ]
            ])
            
            ->add('version', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required"=>false,
                "label"=>"Version",
                "attr"=>[
                    "class"=>"form-control"
                ],
                "constraints"=>[
                ]
            ])
            ->add('observations', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                "label"=>"Observations",
                "required"=>false,
                "attr"=>[
                    "class"=>"form-control"
                ],
                "constraints"=>[
                ]
            ])
        ;
       
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CDC::class,
        ]);
    }
}
