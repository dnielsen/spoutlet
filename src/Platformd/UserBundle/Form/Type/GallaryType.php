<?php

namespace Platformd\UserBundle\Form\Type;

use Platformd\UserBundle\Entity\Gallary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GallaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, array(
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

    public function getBlockPrefix()
    {
        return 'platformd_userbundle_gallarytype';
    }
}
