<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GalleryMediaType extends AbstractType
{
    private $user;
    private $currentSite;
    private $galleryRepo;
    private $groupRepo;
    private $galleryMedia;
    private $tagManager;

    public function __construct($user, $currentSite, $galleryRepo, $groupRepo, $galleryMedia, $tagManager) {
        $this->user         = $user;
        $this->currentSite  = $currentSite;
        $this->galleryRepo  = $galleryRepo;
        $this->groupRepo    = $groupRepo;
        $this->galleryMedia = $galleryMedia;
        $this->tagManager   = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array(
            'max_length' => 255,
            'label'      => 'galleries.edit_photo.name',
            'attr'       => array('class' => 'photo-title')
        ));
        $builder->add('description', TextareaType::class, array(
            'max_length' => 512,
            'label'      => 'galleries.edit_photo.desc',
            'attr'       => array('class' => 'photo-description')
        ));

        $builder->add('galleries', ChoiceType::class, array(
            'label'         => 'galleries.index_page_title',
            'required'      => true,
            'expanded'      => true,
            'multiple'      => true,
            'choices'       => $this->getCategoryChoices(),
            'choices_as_values' => true,
        ));

        if ($this->currentSite->getSiteFeatures()->getHasGroups()) {
            $builder->add('groups', ChoiceType::class, array(
                'label'         => 'Groups',
                'required'      => true,
                'expanded'      => true,
                'multiple'      => true,
                'choices'       => $this->getGroupChoices(),
                'choices_as_values' => true,
            ));
        }

        if ($this->user instanceof User && $this->user->hasRole('ROLE_SUPER_ADMIN')) {
            $builder->add('featured', CheckboxType::class, array(
                'label'     => 'Featured',
                'help'      => 'Check this checkbox to make this media item featured on the gallery front page.',
                'required'  => false,
            ));
        }

        $builder->add('tags', TextType::class, array(
            'label' => 'tags.forms.tags',
            'help' => "tags.forms.enter_keywords_help",
            'property_path' => false,
            'data' => $this->galleryMedia ? $this->tagManager->getConcatenatedTagNames($this->galleryMedia) : null,
            'required' => false,
        ));
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_gallery_media';
    }

    private function getCategoryChoices()
    {
        $choices = [];
        $site = $this->currentSite;

        $results = $this->galleryRepo->findAllGalleriesByCategoryForSiteSortedByPosition($site, 'image');

        foreach ($results as $gallery) {
            $choices[$gallery->getName($site->getId())] = $gallery->getId();
        }

        return $choices;
    }

    private function getGroupChoices()
    {
        $choices = [];

        if ($this->galleryMedia->getAuthor() == $this->user) {
            $results = $this->groupRepo->getAllGroupsForUserAndSite($this->user, $this->currentSite);
            foreach ($results as $group) {
                $choices[$group[0]->getName()] = $group[0]->getId();
            }
        } elseif ($this->user->getAdminLevel() == 'ROLE_SUPER_ADMIN') {
            $results = $this->groupRepo->findGroupsForImage($this->galleryMedia);
            foreach ($results as $group) {
                $choices[$group->getName()] = $group->getId();
            }
        }

        return $choices;
    }
}
