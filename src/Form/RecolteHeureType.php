<?php

namespace App\Form;

use App\Entity\RecolteHeure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\CallbackTransformer;

class RecolteHeureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('matricule', \Symfony\Component\Form\Extension\Core\Type\TextType::class,[
                "required"=>true,
                "label"=>"Matricule",
                "attr"=>[
                    "class"=>"form-control",
                    "readonly"=>"readonly"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class,[
                "required"=>true,
                "label"=>"Nom",
                "attr"=>[
                    "class"=>"form-control",
                    "readonly"=>"readonly"
                ],
                 "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('prenom', \Symfony\Component\Form\Extension\Core\Type\TextType::class,[
                "required"=>false,
                "label"=>"Prénom",
                "attr"=>[
                    "class"=>"form-control",
                    "readonly"=>"readonly"
                ],
            ])
            ->add('fonction', \Symfony\Component\Form\Extension\Core\Type\TextType::class,[
                "required"=>true,
                "label"=>"Fonction",
                "attr"=>[
                    "class"=>"form-control",
                    "readonly"=>"readonly"
                ],
                 "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('date_embauche', \Symfony\Component\Form\Extension\Core\Type\DateType::class,[
                "required"=>true,
                "label"=>"Date d'embauche",
                "attr"=>[
                    "class"=>"form-control",
                    "readonly"=>"readonly"
                ],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                 "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('debut_compte', \Symfony\Component\Form\Extension\Core\Type\DateType::class,[
                "required"=>true,
                "label"=>"Début compte",
                "attr"=>[
                    "class"=>"form-control",
                    "readonly"=>"readonly"
                ],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                 "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('fin_compte', \Symfony\Component\Form\Extension\Core\Type\DateType::class,[
                "required"=>true,
                "label"=>"Fin compte",
                "attr"=>[
                    "class"=>"form-control",
                    "readonly"=>"readonly"
                ],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                 "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('heure_references', \Symfony\Component\Form\Extension\Core\Type\TextType::class,[
                "required"=>true,
                "label"=>"Heure références",
                "attr"=>[
                    "class"=>"form-control",
                ],
                 "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('nb_jour_references', \Symfony\Component\Form\Extension\Core\Type\TextType::class,[
                "required"=>true,
                "label"=>"Nombre de Jour références",
                "attr"=>[
                    "class"=>"form-control",
                ],
                 "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\NotBlank()
                ]
            ])
            ->add('horaire', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Horaire",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('heures_travailles', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Heures travaillées",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('heures_majores', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Heures majorées",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('heures_recuperes', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Heures récupérées",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('heures_supp_30', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Heures supp. 30%",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('heures_supp_nuit_75', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Heures supp. nuit 75%",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('heures_supp_dimanche_100', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Heures supp. dimanche 100%",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('total_heures_supp', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Total heures supp.",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('avantage_en_nature', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Avantage en nature",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('absence_conge_paye', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Absence congé payé",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('absence_reguliere', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Absence régulière",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('absence_irreguliere', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Absence irrégulière",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('indemnite_conge_paye', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Indemnité congé payé",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
            ->add('indemnite_repas_transport', \Symfony\Component\Form\Extension\Core\Type\NumberType::class,[
                "required"=>false,
                "label"=>"Indemnité de repas/transport",
                "attr"=>[
                    "class"=>"form-control",
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\GreaterThan(0)
                ]
            ])
        ;
        
//        $builder->get('date_embauche')
//            ->addModelTransformer(new CallbackTransformer(
//                function ($tagsAsArray) {
//                    // transform date to string
//                    return $tagsAsArray->format("Y-m-d");
//                },
//                function ($tagsAsString) {
//                    // transform the string back to a date
//                    return new \DateTime($tagsAsString);
//                }
//            ));
//        $builder->get('debut_compte')
//                ->addModelTransformer(new CallbackTransformer(
//                    function ($tagsAsArray) {
//                        // transform date to string
//                        return $tagsAsArray->format("Y-m-d");
//                    },
//                    function ($tagsAsString) {
//                        // transform the string back to an array
//                        return new \DateTime($tagsAsString);
//                    }
//                ));
//        $builder->get('fin_compte')
//                ->addModelTransformer(new CallbackTransformer(
//                    function ($tagsAsArray) {
//                        // transform date to string
//                        return $tagsAsArray->format("Y-m-d");
//                    },
//                    function ($tagsAsString) {
//                        // transform the string back to an array
//                        return new \DateTime($tagsAsString);
//                    }
//                ))
//        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RecolteHeure::class,
        ]);
    }
}
