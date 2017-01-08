<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DealPoolType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // this is a long field, but it's mean for users not to put much here
            ->add('description', TextType::class, array('required' => false))
            ->add('maxKeysPerIp', IntegerType::class, array('required' => false))
            ->add('isActive', CheckboxType::class, array('required' => false))
            ->add('keysAreUrls', CheckboxType::class, array('required' => false, 'label' => 'Keys are URLS?'))
            ->add('allowedCountries', EntityType::class, array(
                'multiple' => true,
                'expanded' => false,
                'class' => 'Platformd\SpoutletBundle\Entity\Country',
                'choice_label' => 'name'
            ))
            ->add('keysfile', FileType::class);
    }

    public function getBlockPrefix()
    {
        return 'deal_pool';
    }
}
