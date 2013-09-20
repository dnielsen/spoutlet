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
use Platformd\TagBundle\Model\TagManager;

class YoutubeType extends AbstractType
{
    private $siteUtil;
    private $galleryRepo;
    private $groupRepo;
    private $securityContext;
    private $request;
    private $tagManager;
    private $video;

    function __construct(SiteUtil $siteUtil, EntityRepository $galleryRepo, GroupRepository $groupRepo, SecurityContext $securityContext, Request $request, TagManager $tagManager) {
        $this->siteUtil         = $siteUtil;
        $this->galleryRepo      = $galleryRepo;
        $this->groupRepo        = $groupRepo;
        $this->securityContext  = $securityContext;
        $this->request          = $request;
        $this->tagManager       = $tagManager;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $youtube = $builder->getData();
        $this->video = $youtube;
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
                'required'      => false,
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

        $builder->add('tags', 'text', array(
            'label'         => 'youtube.form.tags',
            'help'          => "youtube.form.tags_help",
            'property_path' => false,
            'data'          => $builder->getData() ? $this->tagManager->getConcatenatedTagNames($builder->getData()) : null,
            'required' => false,
        ));
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

        if ($this->video->getAuthor() == $user) {
            $results = $this->groupRepo->getAllGroupsForUserAndSite($user, $this->siteUtil->getCurrentSite());
            foreach ($results as $group) {
                $choices[$group[0]->getId()] = $group[0]->getName();
            }
        } elseif ($user->getAdminLevel() == 'ROLE_SUPER_ADMIN') {
            $results = $this->groupRepo->findGroupsForVideo($this->video);
            foreach ($results as $group) {
                $choices[$group->getId()] = $group->getName();
            }
        } else {
            return array();
        }

        return $choices;
    }
}
