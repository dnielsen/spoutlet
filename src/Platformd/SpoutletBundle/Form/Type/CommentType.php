<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class CommentType extends AbstractType
{
    /**
     * Configures a Comment form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('body', TextareaType::class);
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_commenttype';
    }
}
