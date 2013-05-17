<?php

namespace Platformd\NewsBundle\Command;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\HttpFoundation\File\UploadedFile,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity,
    Symfony\Component\Security\Acl\Permission\MaskBuilder,
    Symfony\Component\Security\Acl\Exception\NoAceFoundException
;

use
    DateTime
;

use
    Platformd\SpoutletBundle\Entity\Thread,
    Platformd\SpoutletBundle\Entity\Comment
;

class ImportNewsCommentsCommand extends ContainerAwareCommand
{
    private $stdOutput;
    private $em;
    private $linkableManager;
    private $adminAclCreated = false;

    const COMMENT_DATA_FILE = '/home/ubuntu/news_import/news_comments_data.csv';

    protected function configure()
    {
        $this
            ->setName('pd:news:commentsImport')
            ->setDescription('Imports news comments data from a .csv file')
            ->setHelp(<<<EOT
The <info>pd:news:commentsImport</info> command imports data from a .csv file located in /home/ubuntu/news_import/news_comments_data.csv:

  <info>php app/console pd:news:commentsImport</info>
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

    protected function createThread($threadId, $article)
    {
        $thread = new Thread();
        $thread->setId($threadId);
        $thread->setPermalink($this->linkableManager->link($article).'#comments');

        $this->em->persist($thread);
        $this->em->flush();

        return $thread;
    }

    protected function createAcl($comment, $user)
    {
        // creating the ACL
        $aclProvider = $this->getContainer()->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($comment);
        $acl = $aclProvider->createAcl($objectIdentity);

        // grant owner access
        $securityIdentity = UserSecurityIdentity::fromAccount($user);
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);

        $aclProvider->updateAcl($acl);

        if (!$this->adminAclCreated) {
            // grant admins access
            $securityIdentitySuperAdmin = new RoleSecurityIdentity('ROLE_SUPER_ADMIN');

            try {
                $acl->isGranted(array(MaskBuilder::MASK_MASTER), array($securityIdentitySuperAdmin));
            } catch (NoAceFoundException $e) {
                $acl->insertClassAce($securityIdentitySuperAdmin, MaskBuilder::MASK_MASTER);
                $aclProvider->updateAcl($acl);
            }

            $this->adminAclCreated = true;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container          = $this->getContainer();
        $em                 = $container->get('doctrine')->getEntityManager();
        $newsRepo           = $em->getRepository('NewsBundle:News');
        $threadRepo         = $em->getRepository('SpoutletBundle:Thread');
        $commentRepo        = $em->getRepository('SpoutletBundle:Comment');
        $userManager        = $container->get('fos_user.user_manager');
        $linkableManager    = $container->get('platformd.link.linkable_manager');

        $commentCount       = 0;

        $this->stdOutput        = $output;
        $this->em               = $em;
        $this->linkableManager  = $linkableManager;

        $this->output(0);
        $this->output(0, 'News Comment Import Script');
        $this->output(0);

        $this->output(2, 'Importing comments...');
        $this->output(0);

        if (($handle = fopen(self::COMMENT_DATA_FILE, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 5000)) !== FALSE) {

                if (!isset($data[1])) {
                    continue;
                }

                $oldData = $data;

                $id             = $data[0];
                $cevoArticleId  = $data[1];
                $cevoUserId     = $data[2];
                $body           = html_entity_decode(trim($data[3]), ENT_QUOTES);
                $postedAt       = DateTime::createFromFormat('U', $data[4]);
                $deleted        = $data[5] !== 'active';

                if ($deleted) {
                    $this->output(4, 'Comment deleted - skipping.');
                    $this->output(0);
                    continue;
                }

                $user = $userManager->findUserBy(array('cevoUserId' => $cevoUserId));

                if (!$user) {
                    $this->output(4, 'No user for CEVO user ID [ '.$cevoUserId.' ] - skipping.');
                    $this->output(0);
                    continue;
                }

                $article = $newsRepo->findOneByCevoArticleId($cevoArticleId);

                if (!$article) {
                    $this->output(4, 'No article for CEVO article ID [ '.$cevoArticleId.' ] - skipping.');
                    $this->output(0);
                    continue;
                }

                $threadId = $article->getCommentThreadId();
                $thread   = $threadRepo->find($threadId);

                if (!$thread) {
                    $this->output(4, 'No thread for thread ID [ '.$threadId.' ] - creating...', false);
                    $thread = $this->createThread($threadId, $article);
                    $this->tick();
                    $this->output(0);
                }

                $this->output(4, 'Importing comment for article [ '.$article->getTitle().' ]...');
                $this->output(6, 'Comment - { User => "'.$user->getUsername().'", Thread => "'.$threadId.'", Active => "'.($deleted ? 'False' : 'True').'", Posted => "'.$postedAt->format('Y-m-d H:i:s').'" }');

                $commentExists = $commentRepo->findOneBy(array(
                    'thread'    => $threadId,
                    'author'    => $user->getId(),
                    'createdAt' => $postedAt,
                ));

                if (!$commentExists) {

                    $this->output(6, 'Creating comment...', false);

                    $comment = new Comment();

                    $comment->setThread($thread);
                    $comment->setAuthor($user);
                    $comment->setBody($body);
                    $comment->setCreatedAt($postedAt);
                    $comment->setDeleted($deleted);

                    $em->persist($comment);

                    $this->tick();
                    $this->output(6, 'Updating thread count...', false);

                    $thread->incrementCommentCount();
                    $thread->setLastCommentAt($postedAt);
                    $em->persist($thread);

                    $this->tick();

                    $this->output(6, 'Committing changes to database...', false);
                    $em->flush();
                    $this->tick();

                    $this->output(6, 'Creating ACL...', false);
                    $this->createAcl($comment, $user);
                    $this->tick();

                    $this->output(0);

                    $commentCount++;

                } else {
                    $this->output(8, 'Comment exists. Skipping.');
                    $this->output(0);
                    continue;
                }
            }
        }

        $this->output(2, 'Final database commit...', false);
        $em->flush();
        $this->tick();

        $this->output(0, 'Finished importing '.$commentCount.' comments.');
        $this->output(0);
    }
}
