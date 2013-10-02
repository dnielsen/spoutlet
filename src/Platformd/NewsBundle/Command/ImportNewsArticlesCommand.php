<?php

namespace Platformd\NewsBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\HttpFoundation\File\UploadedFile
;

use
    DateTime,
    DateTimeZone,
    DOMDocument
;

use Doctrine\Common\Collections\ArrayCollection;
use Knp\MediaBundle\Util\MediaUtil;

use
    Platformd\NewsBundle\Entity\News,
    Platformd\MediaBundle\Entity\Media
;

class ImportNewsArticlesCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $mediaExposer;
    private $mediaRepo;
    private $em;
    private $user;
    private $badPaths = array();
    private $badLinks = array();

    const NEWS_DATA_FILE        = '/home/ubuntu/news_import/news_data.csv';
    const NEWS_BADGE_PATH       = '/home/ubuntu/news_import/badges/';
    const NEWS_THUMBNAIL_PATH   = '/home/ubuntu/news_import/images/';
    const NEWS_THUMBNAIL_PATH_2 = '/home/ubuntu/news_import/images/_thumbs/';
    const NEWS_IMAGE_PATH       = '/home/ubuntu/news_import/images/';

    protected function configure()
    {
        $this
            ->setName('pd:news:import')
            ->setDescription('Imports news data from a .csv file')
            ->setHelp(<<<EOT
The <info>pd:news:import</info> command imports data from a .csv file located in /home/ubuntu/news_import/news_data.csv:

  <info>php app/console pd:news:import</info>
EOT
            );
    }

    protected function output($indentationLevel = 0, $message = null, $withNewLine = true) {

        if ($message === null) {
            $message = '';
        }

        if ($withNewLine) {
            $this->stdOutput->writeLn(str_repeat(' ', $indentationLevel).$message);
        } else {
            $this->stdOutput->write(str_repeat(' ', $indentationLevel).$message);
        }
    }

    protected function tick()
    {
        $this->output(0, '<info>✔</info>');
    }

    protected function extractThumbnailFile($html)
    {
        if (false !== strpos($html, 'src')) {
            $DOM = new DOMDocument;
            $DOM->loadHTML($html);

            $thumb = $DOM->getElementsByTagName('img')->item(0)->getAttribute('src');

        } else {
            $thumb = $html;
        }

        $thumbParts = explode('/', $thumb);
        $thumbFile = end($thumbParts);

        return $thumbFile;
    }

    protected function processBody($html)
    {
        // Hack to avoid extra HTML tags being wrapped around out HTML snippet.
        $html = '<div>' . $html . '</div>';
        $DOM = new DOMDocument;
        @$DOM->loadHTML($html);

        $images = $DOM->getElementsByTagName('img');

        foreach ($images as $image) {
            $imgSrc = $image->getAttribute('src');

            $imgParts = explode('/', $imgSrc);
            $imgFile = end($imgParts);

            if (false !== strpos($imgSrc, '/cdn/uploads/alienwarearena/images')) {

                $path = null;

                $testPath   = self::NEWS_IMAGE_PATH.$imgFile;
                $backupPath = self::NEWS_THUMBNAIL_PATH_2.$imgFile;

                if (file_exists($testPath)) {
                    $path = $testPath;
                } elseif (file_exists($backupPath)) {
                    $path = $backupPath;
                }

            } elseif (false !== strpos($imgSrc, '/aw-cdn/article-badges')) {
                $path = self::NEWS_BADGE_PATH.$imgFile;
            } elseif (false !== strpos($imgSrc, 'http://')) {
                continue;
            } else {
                $this->badPaths[] = $imgSrc;
                continue;
            }

            $media = $this->mediaRepo->findOneByFilename($imgFile);

            if (!$media && file_exists($path)) {
                $media = $this->uploadMedia($path);
            }

            if ($media && $media->getFilename()) {
                $image->setAttribute('src', $this->mediaExposer->getPath($media));
            }
        }

        $links = $DOM->getElementsByTagName('a');

        foreach ($links as $link) {
            $linkHref = $link->getAttribute('href');

            if ($linkHref == '' || $linkHref == '/' || substr($linkHref, 0, 1) == '#') {
                continue;
            }

            if (false !== strpos($linkHref, 'http://') || false !== strpos($linkHref, 'https://') || false !== strpos($linkHref, 'mailto:')) {
                continue;
            }

            if (false !== strpos($linkHref, '/articles/view/')) {
                $linkHref = str_replace('/articles/view/', '/news/', $linkHref);
                $link->setAttribute('href', $linkHref);
            } elseif (false !== strpos($linkHref, '/article/add')) {
                $link->setAttribute('href', '#');
            } elseif (false !== strpos($linkHref, '/aw-cdn/article-badges')) {

                $imgParts = explode('/', $linkHref);
                $imgFile = end($imgParts);

                $path = self::NEWS_BADGE_PATH.$imgFile;

                $media = $this->mediaRepo->findOneByFilename($imgFile);

                if (!$media && file_exists($path)) {
                    $media = $this->uploadMedia($path);
                }

                if ($media && $media->getFilename()) {
                    $link->setAttribute('href', $this->mediaExposer->getPath($media));
                }

            } elseif (false !== strpos($linkHref, '/pictures/') || false !== strpos($linkHref, '/downloads/')) {
                $link->setAttribute('href', 'http://www.alienwarearena.com'.$linkHref);
            } else {
                $this->badLinks[] = $linkHref;
                continue;
            }
        }

        return substr($DOM->saveXML($DOM->getElementsByTagName('div')->item(0)), 5, -6);
    }

    protected function uploadMedia($path)
    {
        $parts      = explode('/', $path);
        $file       = end($parts);

        $this->output(8, 'Media upload of [ '.$file.' ] begun...', false);

        $media = new Media();
        $media->setOwner($this->user);
        $media->setLocale('en');

        $mimeType   = mime_content_type($path);
        $size       = filesize($path);

        $file       = new UploadedFile($path, $file, $mimeType, $size);

        $media->setFileObject($file);

        $persisted = false;

        while ($persisted == false) {
            try {
                $this->em->persist($media);
                $this->em->flush();

                $persisted = true;
            } catch (\Exception $e) {
                $this->output(10, 'Error in upload - message was [ '.$e->getMessage().' ] - retrying...');
            }
        }

        $this->tick();

        return $media;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container          = $this->getContainer();
        $em                 = $container->get('doctrine')->getEntityManager();
        $mUtil              = new MediaUtil($em);
        $newsRepo           = $em->getRepository('NewsBundle:News');
        $siteRepo           = $em->getRepository('SpoutletBundle:Site');
        $mediaRepo          = $em->getRepository('MediaBundle:Media');
        $userManager        = $container->get('fos_user.user_manager');
        $user               = $userManager->findUserByUsername('admin');
        $mediaExposer       = $container->get('media_exposer');

        $this->stdOutput    = $output;
        $this->mediaExposer = $mediaExposer;
        $this->mediaRepo    = $mediaRepo;
        $this->em           = $em;
        $this->user         = $user;

        $sites['demo']      = $siteRepo->find(1);
        $sites['na']        = $siteRepo->find(4);
        $sites['euro']      = $siteRepo->find(5);
        $sites['latam']     = $siteRepo->find(6);
        $sites['in']        = $siteRepo->find(7);
        $sites['mysg']      = $siteRepo->find(8);
        $sites['anz']       = $siteRepo->find(9);

        $this->output(0);
        $this->output(0, 'News Import Script');
        $this->output(0);

        $this->output(2, 'Importing articles...');
        $this->output(0);

        if (($handle = fopen(self::NEWS_DATA_FILE, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                // Unused fields
                $category       = $data[1];
                $stamp          = $data[8];

                // Used fields
                $cevoArticleId  = $data[0];
                $title          = html_entity_decode(trim($data[2]), ENT_QUOTES);
                $slug           = trim($data[3]);
                $body           = $data[4];
                $thumbnailUrl   = $data[5];
                $thumbnailHtml  = html_entity_decode($data[6], ENT_QUOTES);
                $image          = $data[7];
                $blurb          = html_entity_decode($data[8], ENT_QUOTES);
                $status         = $data[10];
                $regions        = explode(', ', str_replace('/', '', strtolower($data[11])));

                $published      = $status == 'active';

                $sitesArr       = array();

                foreach ($regions as $siteKey) {
                    if (isset($sites[$siteKey])) {
                        $sitesArr[] = $sites[$siteKey];
                    }

                }

                if (count($sitesArr) < 1) {
                    $sitesArr[] = $sites['demo'];
                }

                $articleSites = new ArrayCollection($sitesArr);

                if (!$title) {
                    $this->output(4, 'No title for article id [ '.$cevoArticleId.' ] - skipping.');
                    $this->output(0);
                    continue;
                }

                $this->output(4, 'Importing news article - [ '.$title.' ]...');
                $this->output(6, 'Article - { Slug => "'.$slug.'", Status => "'.$status.'", Regions => "'.implode(', ', $regions).'" }');

                $articleExists = $newsRepo->findOneBySlug($slug);

                if (!$articleExists) {

                    $this->output(6, 'Parsing body HTML for images and links...');

                    // Strip out HTML comments, most of which were inserted by a Microsoft editor and massively increase the data size
                    $body = html_entity_decode(preg_replace('/<!--(.*)-->/Uis', '', $body), ENT_QUOTES);
                    $body = html_entity_decode($this->processBody($body), ENT_QUOTES);

                    $article = new News();

                    if ($image) {

                        $path = self::NEWS_BADGE_PATH.$image;

                        if (file_exists($path)) {

                            $this->output(6, 'Uploading listing image [ '.$image.' ]...');

                            $imageMedia = $this->uploadMedia($path);

                            $article->setImage($imageMedia);

                            if (!$mUtil->persistRelatedMedia($article->getImage())) {
                                $article->setImage(null);
                            }

                        } else {
                            $this->output(6, 'Image file does not exist in "'.self::NEWS_BADGE_PATH.'" - skipping.');
                        }
                    }

                    if ($thumbnailUrl || $thumbnailHtml) {

                        if ($thumbnailUrl && $thumbnailUrl != '') {
                            $thumbParts = explode('/', $thumbnailUrl);
                            $thumbnail = end($thumbParts);
                        } else {
                            $thumbnail = $this->extractThumbnailFile($thumbnailHtml);
                        }

                        $path = self::NEWS_THUMBNAIL_PATH.$thumbnail;

                        if (file_exists($path)) {

                            $this->output(6, 'Uploading thumbnail [ '.$thumbnail.' ]...');

                            $thumbMedia = $this->uploadMedia($path);

                            $article->setThumbnail($thumbMedia);

                            if (!$mUtil->persistRelatedMedia($article->getThumbnail())) {
                                $article->setThumbnail(null);
                            }

                        } else {
                            $this->output(6, 'Thumbnail file does not exist in "'.self::NEWS_THUMBNAIL_PATH.'" - skipping.');
                        }
                    }

                    $article->setTitle($title);
                    $article->setBody($body);
                    $article->setSites($articleSites);
                    $article->setPublished($published);
                    $article->setBlurb($blurb);
                    $article->setType(News::NEWS_TYPE_ARTICLE);
                    $article->setCevoArticleId($cevoArticleId);

                    $em->persist($article);
                    $em->flush();

                    // Done separately to override Gedmo\Slug
                    if ($slug) {
                        $article->setSlug($slug);
                        $em->persist($article);
                        $em->flush();
                    }

                    $this->output(0);

                } else {
                    $this->output(8, 'Article exists. Skipping.');
                    $this->output(0);
                    continue;
                }
            }
        }

        if (count($this->badPaths) > 0) {

            $this->output(0, 'Several files could not be uploaded:');

            foreach ($this->badPaths as $badPath) {
                $this->output(2, $badPath);
            }

            $this->output(0);
        }

        if (count($this->badLinks) > 0) {

            $this->output(0, 'Several links could not be converted:');

            foreach ($this->badLinks as $badLink) {
                $this->output(2, $badLink);
            }

            $this->output(0);
        }

    }
}
