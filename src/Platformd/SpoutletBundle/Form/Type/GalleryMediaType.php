<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Doctrine\ORM\EntityRepository;
use Platformd\UserBundle\Entity\User;

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

    public function buildForm(FormBuilder $builder, array $options)
    {
        $currentSite = $this->currentSite;

        $builder->add('title', 'text', array(
            'max_length' => 255,
            'label'      => 'galleries.edit_photo.name',
            'attr'       => array('class' => 'photo-title')
        ));
        $builder->add('description', 'textarea', array(
            'max_length' => 512,
            'label'      => 'galleries.edit_photo.desc',
            'attr'       => array('class' => 'photo-description')
        ));

        $builder->add('galleries', 'choice', array(
            'label'         => 'galleries.index_page_title',
            'required'      => true,
            'expanded'      => true,
            'multiple'      => true,
            'choices'       => $this->getCategoryChoices(),
        ));

        if ($this->currentSite->getSiteFeatures()->getHasGroups()) {
            $builder->add('groups', 'choice', array(
                'label'         => 'Groups',
                'required'      => true,
                'expanded'      => true,
                'multiple'      => true,
                'choices'       => $this->getGroupChoices(),
            ));
        }

        if ($this->user instanceof User && $this->user->hasRole('ROLE_SUPER_ADMIN')) {
            $builder->add('featured', 'checkbox', array(
                'label'     => 'Featured',
                'help'      => 'Check this checkbox to make this media item featured on the gallery front page.',
                'required'  => false,
            ));
        }

        $builder->add('tags', 'text', array(
            'label' => 'tags.forms.tags',
            'help' => "tags.forms.enter_keywords_help",
            'property_path' => false,
            'data' => $this->galleryMedia ? $this->tagManager->getConcatenatedTagNames($this->galleryMedia) : null,
            'required' => false,
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallery_media';
    }

    private function getCategoryChoices()
    {
        $choices = array();
        $site = $this->currentSite;

        $results = $this->galleryRepo->findAllGalleriesByCategoryForSiteSortedByPosition($site, 'image');

        foreach ($results as $gallery) {
            $choices[$gallery->getId()] = $gallery->getName($site->getId());
        }

        return $choices;
    }

    private function getGroupChoices()
    {
        $choices = array();

        if ($this->galleryMedia->getAuthor() == $this->user) {
            $results = $this->groupRepo->getAllGroupsForUserAndSite($this->user, $this->currentSite);
            foreach ($results as $group) {
                $choices[$group[0]->getId()] = $group[0]->getName();
            }
        } elseif ($this->user->getAdminLevel() == 'ROLE_SUPER_ADMIN') {
            $results = $this->groupRepo->findGroupsForImage($this->galleryMedia);
            foreach ($results as $group) {
                $choices[$group->getId()] = $group->getName();
            }
        } else {
            return array();
        }

        return $choices;
    }
}
