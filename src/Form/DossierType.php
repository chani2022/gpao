<?php

namespace App\Form;

use App\Entity\Dossier;
use App\Entity\CDC;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\ORM\EntityRepository;

class DossierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('cdc', EntityType::class, [
                "required"=>true,
                "label"=>"CDC",
                "class"=>CDC::class,
                'query_builder'=>function(EntityRepository $er){
                            return $er->createQueryBuilder('c')
                            ->orderBy('c.nom_cdc');
                },
                'choice_label'=> function(CDC $u){
                    return $u->getNomCdc();
                },
                "attr"=>[
                    "class"=>"form-control",
                    
                ],
                'placeholder' => '- Séléctionner -',
                "constraints"=>[
                ]
            ])
            
            ->add('nom_dossier', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required"=>true,
                "label"=>"Nom du Dossier",
                "attr"=>[
                    "class"=>"form-control"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\Length(["min"=>4,
                        "minMessage"=>'Ce champ doit contenir aux moins {{ limit }} caractères'])
                ]
            ])
            ->add('nom_mairie', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required"=>false,
                "label"=>"Nom de la mairie",
                "attr"=>[
                    "class"=>"form-control"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\Length(["min"=>3,
                        "minMessage"=>'Ce champ doit contenir aux moins {{ limit }} caractères'])
                ]
            ])
        ;
       
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
        ]);
    }
}
