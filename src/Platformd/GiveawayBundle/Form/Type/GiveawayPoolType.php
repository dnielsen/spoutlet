<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\Type\CountryRestrictionRulesetType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GiveawayPoolType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // this is a long field, but it's mean for users not to put much here
            ->add('description', TextType::class, array(
                'required' => false
            ))
            ->add('maxKeysPerIp', IntegerType::class, array(
                'required' => false,
                'label' => 'Max Keys Per IP',
                ))
            ->add('upperLimit', IntegerType::class, array(
                'required' => false,
                'label' => 'Upper Limit',
                ))
            ->add('lowerLimit', IntegerType::class, array(
                'required' => false,
                'label' => 'Lower Limit',
                ))
            ->add('isActive', CheckboxType::class, array(
                'required' => false,
                'label' => 'Active?',
                ))
            ->add('keysfile', FileType::class, array(
                'label' => 'Keys File',
            ))
            ->add('ruleset', CountryRestrictionRulesetType::class, array(
                'label' => 'Restrictions',
            ))
            ->add('regions', EntityType::class, array(
                'class' => 'SpoutletBundle:Region',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->andWhere('r.site IS NOT NULL');
                },
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
//                'help' => 'You can choose from the above list of predefined regions, and add additional countries below',
            ));
    }

    public function getBlockPrefix()
    {
        return 'giveway_pool';
    }
}
