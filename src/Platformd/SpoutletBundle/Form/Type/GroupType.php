<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Group;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\LocationType;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Group Name',
            ))
            ->add('category', 'choice', array(
                'choices' => self::getCategoryChoices(),
                'label' => 'Group Category',
            ))
            ->add('groupAvatar', new MediaType(), array(
                'image_label'   => 'Group Avatar',
                'image_help'    => 'Recommended size: 150x150',
            ))
            ->add('backgroundImage', new MediaType(), array(
                'image_label'   => 'Background Image',
                'image_help'    => 'Recommended size: 2001x1496 with the center being 970 pixels wide and black.',
            ))
            ->add('description', null, array(
                'label' => 'Information about Group',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('howToJoin', null, array(
                'label' => 'How to Join?',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('isPublic', 'checkbox', array('required' => true, 'label' => 'Make this Group Public?'))
            ->add('location', new LocationType(), array(
                'label' => 'Location',
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_grouptype';
    }

    /**
     * Returns the choices for category
     *
     * Labels are platformd.admin.groups.category.KEY
     *
     * @static
     * @return array
     */
    private static function getCategoryChoices()
    {
        $values = Group::getValidCategories();
        $choices = array('' => 'Choose a Category');
        foreach ($values as $value) {
            $choices[$value]  = Group::GROUP_CATEGORY_LABEL_PREFIX.$value;
        }

        return $choices;
    }
}
