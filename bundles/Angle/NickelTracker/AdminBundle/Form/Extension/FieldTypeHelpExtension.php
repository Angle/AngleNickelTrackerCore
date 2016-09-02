<?php

namespace Angle\NickelTracker\AdminBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldTypeHelpExtension extends AbstractTypeExtension
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('help', $options['help'])
            ->setAttribute('suffix', $options['suffix'])
            ->setAttribute('prefix', $options['prefix']);
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['help'] = $options['help'];
        $view->vars['suffix'] = $options['suffix'];
        $view->vars['prefix'] = $options['prefix'];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'help' => null,
            'suffix' => null,
            'prefix' => null,
        ));
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}