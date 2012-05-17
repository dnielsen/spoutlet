<?php

namespace Platformd\NewsBundle\Features\Context;

use Behat\BehatBundle\Context\BehatContext,
    Behat\BehatBundle\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Platformd\SpoutletBundle\Features\Context\AbstractFeatureContext;
use Platformd\SpoutletBundle\Entity\Game as Game;
use Platformd\NewsBundle\Entity\News as News;
/**
 * Feature context.
 */
class FeatureContext extends AbstractFeatureContext
{

    /**
     * @var News
     */
    private $currentNewsArticle;

    /**
     * @Given /^I am on the edit page for the news story$/
     */
    public function iAmOnTheEditPageForTheNewsStory()
    {
        $this->NavigateTo('NewsBundle_admin_edit', array('id' => $this->currentNewsArticle->getId()));
    }

    /**
     * @Given /^there is a news item called "([^"]*)"$/
     */
    public function thereIsANewsItemCalled($articleTitle)
    {
        $em = $this->getEntityManager();

        $article = new News();

        $article->setTitle($articleTitle);

        $article->setBlurb("blurb");
        $article->setLocale("en");
        $article->setBody("body");
        $article->setGame(NULL);
        $article->setPostedAt(new \DateTime("2001-01-01"));

        $em->persist($article);
        $em->flush();

        $this->currentNewsArticle = $article;
    }

}
