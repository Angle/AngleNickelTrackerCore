<?php

namespace Angle\NickelTracker\AdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

use Angle\NickelTracker\CoreBundle\Entity\User;

class UserType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Angle\NickelTracker\CoreBundle\Entity\User',
            'mode' => null
        ));
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('generalInformation', FieldsetType::class, [
                'label' => false,
                'legend' => 'General Information',
                'fields' => function(FormBuilderInterface $builder) {
                    $builder
                        ->add('fullName', TextType::class, array(
                            'label'     => 'Name',
                            'required'  => true
                        ))
                        ->add('email', EmailType::class, array(
                            'label'     => 'E-Mail',
                            'required'  => true,
                        ))
                    ;
                }
            ])
            ->add('loginInformation', FieldsetType::class, [
                'label' => false,
                'legend' => 'Login Information',
                'fields' => function(FormBuilderInterface $builder) {
                    $builder
                        ->add('username', TextType::class, array(
                            'label'     => 'Username',
                            'required'  => true,
                        ))
                        ->add('password', PasswordType::class, array(
                            'label'     => 'Password',
                            'required'  => true,
                        ))
                        ->add('role', ChoiceType::class, array(
                            'label'     => 'Role',
                            'required'  => true,
                            'choices'   => User::getAvailableRoles()
                        ))
                    ;
                }
            ])
            ->add('save', SubmitType::class, array('label' => 'Save'));
    }
}