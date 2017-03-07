<?php

namespace Platformd\GroupBundle\Form\Type;

use Platformd\GroupBundle\Entity\Group;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\LocationType;
use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GroupType extends AbstractType
{
    const YES_NO = [
        'Yes' => 1,
        'No' => 0,
    ];

    const GROUP_VISIBILITY = [
        'Public Group' => 1,
        'Private Group' => 0,
    ];

    private $user;
    private $group;
    private $tagManager;
    private $hasMultiSiteGroups;
    private $currentSite;

    public function __construct($user, Group $group, $tagManager, $hasMultiSiteGroups, $currentSite = null)
    {
        $this->user = $user;
        $this->group = $group;
        $this->tagManager = $tagManager;
        $this->hasMultiSiteGroups = $hasMultiSiteGroups;
        $this->currentSite = $currentSite;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Name',
            ))
            ->add('slug', SlugType::class, array(
                'label' => 'URL Text',
            ))
            ->add('relativeSlug', TextType::class, array(
                'label' => 'Community URL Slug',
            ))
            ->add('category', ChoiceType::class, array(
                'choices' => self::getCategoryChoices(),
                'label' => 'Group Category',
                'choices_as_values' => true,
            ))
            ->add('groupAvatar', MediaType::class, array(
                'image_label' => 'Group Logo',
                'image_help' => 'Maximum width: 830px, maximum height: 72px. Please use JPEG or PNG.',
                'with_remove_checkbox' => $this->group->getId() == null ? false : true
            ))
            ->add('backgroundImage', MediaType::class, array(
                'image_label' => 'Background Image',
                'image_help' => 'Recommended width: 2000px. File formats accepted: JPEG and PNG.',
                'with_remove_checkbox' => $this->group->getId() == 0 ? false : true
            ))
            ->add('thumbNail', MediaType::class, array(
                'image_label' => 'Thumbnail Image',
                'image_help' => 'Recommended size: 135x80. File formats accepted: JPEG and PNG.',
                'with_remove_checkbox' => $this->group->getId() == 0 ? false : true
            ))
            ->add('description', PurifiedTextareaType::class, array(
                'label' => 'Description',
                'attr' => array('class' => 'ckeditor')
            ))
            ->add('isPublic', ChoiceType::class, array(
                'label' => 'Group Visibility',
                'expanded' => true,
                'choices' => self::GROUP_VISIBILITY,
                'choices_as_values' => true,
            ))
            ->add('location', LocationType::class, array(
                'label' => 'Location',
            ))
            ->add('external', ChoiceType::class, array(
                'label' => 'Would you like this group to be listed on Campsite?',
                'choices' => self::YES_NO,
                'choices_as_values' => true,
            ))
            ->add('externalUrl', TextType::class, array(
                'attr' => array(
                    'size' => '60%',
                    'placeholder' => 'http://'
                ),
                'label' => 'Link to Group Website',
                'required' => 0,
            ));

        if ($this->user instanceof User && $this->user->hasRole('ROLE_SUPER_ADMIN')) {

            if ($this->hasMultiSiteGroups) {
                $builder
                    ->add('sites', EntityType::class, array(
                        'class' => 'SpoutletBundle:Site',
                        'multiple' => true,
                        'expanded' => true,
                        'choice_label' => 'name'
                    ))
                    ->add('allLocales', CheckboxType::class, array(
                        'label' => 'Enable for all Locales',
//                        'help' => 'If set to true this overrides the "locales" setting and sets this group to be visible to all sites',
                    ));
            }

            if ($this->group->getId() > 0) {
                $builder->add('deleted', CheckboxType::class, array(
                    'label' => 'Delete Group', 'help' => 'Administratively disable this group.',
                ));
            }

            $builder->add('featured', CheckboxType::class, array(
                'label' => 'Featured',
//                'help' => 'Make this group featured on the homepage.',
            ));

            $builder->add('discussionsEnabled', CheckboxType::class, array(
                'label' => 'Discussions',
//                'help' => 'Enable discussions for this group.',
            ));
        }

        $builder->add('parent', EntityType::class, array(
            'class' => Group::class,
            'choice_label' => 'name',
            'placeholder' => '<None>',
            'label' => 'Parent Group',
            'attr' => array(
                'class' => 'formRowWidth',
                'size' => 6
            ),
            'query_builder' => function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('g')
                    ->leftJoin('g.sites', 's')
                    ->andWhere('(s = :site OR g.allLocales = true)')
                    ->andWhere('g.deleted = false')
                    ->setParameter('site', $this->currentSite);
                if ($groupId = $this->group->getId()) {
                    // Where the group is not the current group
                    $qb->andWhere('g.id != :thisGroupId')
                        // Where the current group is not the parent of the group
                        ->andWhere('g.parentGroup is NULL or g.parentGroup != :thisGroupId')
                        ->setParameter('thisGroupId', $groupId);
                }
                return $qb;
            },
        ));

        $builder->add('children', EntityType::class, array(
            'class' => 'Platformd\GroupBundle\Entity\Group',
            'choice_label' => 'name',
            'label' => 'Sub Groups (Ctrl + Click to select more than one)',
            'multiple' => true,
            'attr' => array(
                'class' => 'formRowWidth',
                'size' => 6
            ),
            'query_builder' => function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('g')
                    ->leftJoin('g.sites', 's')
                    ->andWhere('(s = :site OR g.allLocales = true)')
                    ->andWhere('g.deleted = false')
                    ->setParameter('site', $this->currentSite);

                if ($groupId = $this->group->getId()) {
                    // Where the group is not already parented by another group
                    $qb->andWhere('g.parentGroup is NULL or g.parentGroup = :thisGroupId')
                        // Where the group is not the current group
                        ->andWhere('g.id != :thisGroupId')
                        ->setParameter('thisGroupId', $groupId);

                    if ($parent = $this->group->getParent()) {
                        // Where the group is not our own parent
                        $qb->andWhere('g.id != :thisGroupsParentId')
                            ->setParameter('thisGroupsParentId', $parent->getId());
                    }
                }
                return $qb;
            },
        ));

        $builder->add('tags', TextType::class, array(
            'mapped' => false,
            'label' => 'tags.forms.tags',
//                'help' => "tags.forms.enter_keywords_help",
            'data' => $this->group ? $this->tagManager->getConcatenatedTagNames($this->group) : null,
            'required' => false,
        ));
    }

    public function getBlockPrefix()
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
    private function getCategoryChoices()
    {
        $values = Group::getValidCategories();

        if ($parentGroup = $this->group->getParent()) {
            if ($parentGroup->getCategory() === Group::CAT_TOPIC) {
                return [
                    Group::GROUP_CATEGORY_LABEL_PREFIX . Group::CAT_LOCATION => Group::CAT_LOCATION,
                ];
            } elseif ($parentGroup->getCategory() === Group::CAT_COMPANY) {
                return [
                    Group::GROUP_CATEGORY_LABEL_PREFIX . Group::CAT_DEPARTMENT => Group::CAT_DEPARTMENT,
                ];
            }

        }

        // If we get here, this is not a subgroup form, don't want to show department
        foreach ($values as $value) {
            if ($value === Group::CAT_DEPARTMENT) {
                continue;
            }

            $choices[Group::GROUP_CATEGORY_LABEL_PREFIX . $value] = $value ;
        }

        return $choices;
    }
}
