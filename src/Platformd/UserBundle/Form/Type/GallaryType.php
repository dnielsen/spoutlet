<?php

namespace Platformd\UserBundle\Form\Type;

use Platformd\UserBundle\Entity\Gallary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GallaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', 'file', array(
                'label' => 'platformd.user.avatars.file_label',
                'required' => false,
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Gallary::class,
        ]);
    }

    public function getName()
    {
        return 'platformd_userbundle_gallarytype';
    }
}
