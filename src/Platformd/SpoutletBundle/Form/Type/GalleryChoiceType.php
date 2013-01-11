<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Doctrine\ORM\EntityRepository;

/**
 * Special form type for a drop-down menu of all available "galleries"
 */
class GalleryChoiceType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('galleries', 'entity', array(
            'class' => 'SpoutletBundle:Gallery',
            'property' => 'name',
            'multiple' => true,
            'expanded' => true,
            'query_builder' => function(EntityRepository $er) {
                $qb = $er->createQueryBuilder('ga')
                    ->leftJoin('ga.categories', 'cat')
                    ->where('cat.name = :category')
                    ->orderBy('ga.name', 'ASC')
                    ->setParameter('category', 'image');
                return $qb;
            },
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallery_choice';
    }
}
