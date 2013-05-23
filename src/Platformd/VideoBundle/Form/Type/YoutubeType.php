<?php

namespace Platformd\VideoBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Platformd\SpoutletBundle\Entity\GalleryRepository;
use Platformd\GroupBundle\Entity\GroupRepository;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

class YoutubeType extends AbstractType
{
    private $siteUtil;
    private $galleryRepo;
    private $groupRepo;
    private $securityContext;
    private $request;

    function __construct(SiteUtil $siteUtil, EntityRepository $galleryRepo, GroupRepository $groupRepo, SecurityContext $securityContext, Request $request) {
        $this->siteUtil         = $siteUtil;
        $this->galleryRepo      = $galleryRepo;
        $this->groupRepo        = $groupRepo;
        $this->securityContext  = $securityContext;
        $this->request          = $request;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $youtube = $builder->getData();
        $disableYoutubeId = $youtube->getYoutubeLink() ? 'readonly' : '';
        $referer = $this->request->headers->get('referer');

        $builder
            ->add('youtubeLink', 'text', array(
                'label'     => 'youtube.form.youtube_link',
                'required'  => true,
                'help'      => 'youtube.form.youtube_link_help',
                'attr'      => array($disableYoutubeId => ''),
            ))
            ->add('youtubeId', 'hidden', array(
                'label'     => 'youtube.form.youtube_link',
                'required'  => true
            ))
            ->add('title', 'text', array(
                'label'     => 'youtube.form.title',
                'required'  => true,
            ))
            ->add('description', 'textarea', array(
                'label'     => 'youtube.form.description',
                'required'  => true,
            ))
            ->add('galleries', 'choice', array(
                'label'         => 'youtube.form.category',
                'required'      => true,
                'expanded'      => true,
                'multiple'      => true,
                'choices'       => $this->getCategoryChoices(),
            ))
            ->add('duration', 'hidden', array(
                'property_path' => false,
                'data' => $youtube ? $youtube->getDuration() : 0
            ))
            ->add('referer', 'hidden', array(
                'property_path' => false,
                'data' => $referer,

            ))
        ;

        if($this->siteUtil->getCurrentSite()->getSiteFeatures()->getHasGroups()) {
            $builder->add('groups', 'choice', array(
                'label'         => 'youtube.form.optional',
                'empty_value'   => 'youtube.form.select_category',
                'required'      => false,
                'expanded'      => true,
                'multiple'      => true,
                'choices'       => $this->getGroupChoices(),
            ));
        }
    }

    public function getName()
    {
        return 'youtube';
    }

    private function getCategoryChoices()
    {
        $choices    = array();
        $site       = $this->siteUtil->getCurrentSite();

        $results = $this->galleryRepo->findAllGalleriesByCategoryForSiteSortedByPosition($site, 'video');

        foreach ($results as $gallery) {
            $choices[$gallery->getId()] = $gallery->getName($site->getId());
        }

        return $choices;
    }

    private function getGroupChoices()
    {
        $choices    = array();
        $user       = $this->securityContext->getToken()->getUser();
        $results    = $this->groupRepo->getAllGroupsForUserAndSite($user, $this->siteUtil->getCurrentSite());

        foreach ($results as $group) {
            $choices[$group[0]->getId()] = $group[0]->getName();
        }

        return $choices;
    }
}
