<?php

namespace Platformd\GroupBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\GroupBundle\Entity\Group;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\LocationType;
use Platformd\SpoutletBundle\Entity\Location;
use Platformd\UserBundle\Entity\User;

class GroupType extends AbstractType
{

    private $user;
    private $group;
    private $tagManager;
    private $hasMultiSiteGroups;
    private $topicGroups;

    public function __construct($user, $group, $tagManager, $hasMultiSiteGroups, $topicGroups=null) {
        $this->user         = $user;
        $this->group        = $group;
        $this->tagManager   = $tagManager;
        $this->hasMultiSiteGroups = $hasMultiSiteGroups;
        $this->topicGroups = $topicGroups;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Name',
            ))
            ->add('slug', new SlugType(), array(
                'label' => 'URL Text',
            ))
            ->add('category', 'choice', array(
                'choices'   => self::getCategoryChoices(),
                'label'     => 'Group Category',
            ))
            ->add('groupAvatar', new MediaType(), array(
                'image_label'   => 'Group Logo',
                'image_help'    => 'Maximum width: 830px, maximum height: 72px. Please use JPEG or PNG.',
                'with_remove_checkbox' => $this->group->getId() == null ? false : true
            ))
            ->add('backgroundImage', new MediaType(), array(
                'image_label'   => 'Background Image',
                'image_help'    => 'Recommended width: 2000px. File formats accepted: JPEG and PNG.',
                'with_remove_checkbox' => $this->group->getId() == 0 ? false : true
            ))
            ->add('thumbNail', new MediaType(), array(
                'image_label'   => 'Thumbnail Image',
                'image_help'    => 'Recommended size: 135x80. File formats accepted: JPEG and PNG.',
                'with_remove_checkbox' => $this->group->getId() == 0 ? false : true
            ))
            ->add('description', 'purifiedTextarea', array(
                'label' => 'Description',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('isPublic', 'choice', array(
                'choices'   => array(
                    1 => 'Public Group',
                    0 => 'Private Group',
                ),
                'expanded'  => true,
                'label'     => 'Group Visibility',
                'help'      => 'Public: Group information is visible to all users and any user can join your group. Private: Group information is visible to approved group members only. Group organizer must approve users to be in group.',
            ))
            ->add('location', new LocationType(), array(
                'label' => 'Location',
            ));

            if ($this->user instanceof User && $this->user->hasRole('ROLE_SUPER_ADMIN')) {

                if ($this->hasMultiSiteGroups) {
                    $builder->add('sites', 'entity', array(
                            'class'    => 'SpoutletBundle:Site',
                            'multiple' => true,
                            'expanded' => true,
                            'property' => 'name'
                        ))
                        ->add('allLocales', 'checkbox', array('label' => 'Enable for all Locales', 'help' => 'If set to true this overrides the "locales" setting and sets this group to be visible to all sites'));
                }

                if ($this->group->getId() > 0) {
                    $builder->add('deleted', 'checkbox', array(
                        'label' => 'Delete Group', 'help' => 'Administratively disable this group.',
                    ));
                }

                $builder->add('featured', 'checkbox', array(
                    'label' => 'Featured',
                    'help'  => 'Make this group featured on the homepage.',
                ));

                $builder->add('discussionsEnabled', 'checkbox', array(
                    'label' => 'Discussions',
                    'help'  => 'Enable discussions for this group.',
                ));


                if ($this->topicGroups){
                    $choices = array();
                    $numChoices = 0;
                    foreach ($this->topicGroups as $group) {
                        $choices[$group->getId()] = $group->getName();
                        $numChoices++;
                    }

                    $formAttributes = array('class'=>"formRowWidth", 'size' => ($numChoices > 6 ? 6 : $numChoices));
                    $builder->add('parent', 'choice', array(
                        'choices' => $choices,
                        'required' => false,
                        'empty_value' => false,
                        'attr' => $formAttributes,
                    ));
                }
            }

            $builder->add('tags', 'text', array(
                'label' => 'tags.forms.tags',
                'help' => "tags.forms.enter_keywords_help",
                'property_path' => false,
                'data' => $this->group ? $this->tagManager->getConcatenatedTagNames($this->group) : null,
                'required' => false,
            ));
    }

    public function getName()
    {
        return 'platformd_groupbundle_grouptype';
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

        foreach ($values as $value) {
            $choices[$value]  = Group::GROUP_CATEGORY_LABEL_PREFIX.$value;
        }

        return $choices;
    }
}
