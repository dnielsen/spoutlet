<?php

namespace Platformd\VideoBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Platformd\GroupBundle\Entity\GroupRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Platformd\TagBundle\Model\TagManager;

class YoutubeType extends AbstractType
{
    private $siteUtil;
    private $galleryRepo;
    private $groupRepo;
    private $tokenStorage;
    private $request;
    private $tagManager;
    private $video;

    public function __construct(
        SiteUtil $siteUtil,
        EntityRepository $galleryRepo,
        GroupRepository $groupRepo,
        TokenStorageInterface $tokenStorage,
        Request $request,
        TagManager $tagManager
    ) {
        $this->siteUtil         = $siteUtil;
        $this->galleryRepo      = $galleryRepo;
        $this->groupRepo        = $groupRepo;
        $this->tokenStorage  = $tokenStorage;
        $this->request          = $request;
        $this->tagManager       = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $youtube = $builder->getData();
        $this->video = $youtube;
        $disableYoutubeId = $youtube->getYoutubeLink() ? 'readonly' : '';
        $referer = $this->request->headers->get('referer');

        $builder
            ->add('youtubeLink', TextType::class, array(
                'label'     => 'youtube.form.youtube_link',
                'help'      => 'youtube.form.youtube_link_help',
                'attr'      => array($disableYoutubeId => ''),
            ))
            ->add('youtubeId', HiddenType::class, array(
                'label'     => 'youtube.form.youtube_link',
            ))
            ->add('title', TextType::class, array(
                'label'     => 'youtube.form.title',
            ))
            ->add('description', TextareaType::class, array(
                'label'     => 'youtube.form.description',
            ))
            ->add('galleries', ChoiceType::class, array(
                'label'         => 'youtube.form.category',
                'choices_as_values' => true,
                'required'      => false,
                'expanded'      => true,
                'multiple'      => true,
                'choices'       => $this->getCategoryChoices(),
            ))
            ->add('duration', HiddenType::class, array(
                'mapped' => false,
                'data' => $youtube ? $youtube->getDuration() : 0
            ))
            ->add('referer', HiddenType::class, array(
                'mapped' => false,
                'data' => $referer,
            ))
        ;

        if($this->siteUtil->getCurrentSite()->getSiteFeatures()->getHasGroups()) {
            $builder->add('groups', ChoiceType::class, array(
                'label'         => 'youtube.form.optional',
                'placeholder'   => 'youtube.form.select_category',
                'required'      => false,
                'expanded'      => true,
                'multiple'      => true,
                'choices'       => $this->getGroupChoices(),
                'choices_as_values' => true,
            ));
        }

        $builder->add('tags', TextType::class, array(
            'label'         => 'youtube.form.tags',
            'help'          => "youtube.form.tags_help",
            'mapped' => false,
            'data'          => $builder->getData() ? $this->tagManager->getConcatenatedTagNames($builder->getData()) : null,
            'required' => false,
        ));
    }

    public function getBlockPrefix()
    {
        return 'youtube';
    }

    private function getCategoryChoices()
    {
        $choices    = array();
        $site       = $this->siteUtil->getCurrentSite();

        $results = $this->galleryRepo->findAllGalleriesByCategoryForSiteSortedByPosition($site, 'video');

        foreach ($results as $gallery) {
            $choices[$gallery->getName($site->getId())] = $gallery->getId();
        }

        return $choices;
    }

    private function getGroupChoices()
    {
        $choices    = array();
        $user       = $this->tokenStorage->getToken()->getUser();

        if ($this->video->getAuthor() == $user) {
            $results = $this->groupRepo->getAllGroupsForUserAndSite($user, $this->siteUtil->getCurrentSite());
            foreach ($results as $group) {
                $choices[$group[0]->getName()] = $group[0]->getId();
            }
        } elseif ($user->getAdminLevel() == 'ROLE_SUPER_ADMIN') {
            $results = $this->groupRepo->findGroupsForVideo($this->video);
            foreach ($results as $group) {
                $choices[$group->getName()] = $group->getId();
            }
        }

        return $choices;
    }
}
