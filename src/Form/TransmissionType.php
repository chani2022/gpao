<?php

namespace App\Form;

use App\Entity\Transmission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class TransmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('destinataires', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
//                "label"=>"Destinataires",
//                "required"=>true,
//                "attr"=>[
//                    "multiple"=>"multiple",
//                    "class"=>"form-control destinataire"
//                ]
//            ])
            ->add('objet', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                "required"=>true,
                "label"=>"Objet",
                "attr"=>[
                    "class"=>"form-control"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\Length(["min"=>4,
                        "minMessage"=>'Ce champ doit contenir aux moins {{ limit }} caractères'])
                ]
            ])
            //->add('date_envoie', \Symfony\Component\Form\Extension\Core\Type\DateType::class,)
            ->add('contenu', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                "label"=>"Contenu",
                "required"=>true,
                "attr"=>[
                    "class"=>"form-control message-transmission"
                ],
                "constraints"=>[
                    new \Symfony\Component\Validator\Constraints\Length(["min"=>10,
                        "minMessage"=>"Ce champ doit contenir aux moins {{ limit }} caractères"])
                ]
            ])
        ;
        
//        $builder->get('destinataires')
//            ->addModelTransformer(new CallbackTransformer(
//                function ($tagsAsArray) {
//                    // transform the array to a string
//                    return implode(', ', $tagsAsArray);
//                },
//                function ($tagsAsString) {
//                    // transform the string back to an array
//                    return explode(', ', $tagsAsString);
//                }
//            ))
//        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Transmission::class,
        ]);
    }
}
