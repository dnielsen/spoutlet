<?php

namespace Platformd\SpoutletBundle\Features\Context;

use Behat\BehatBundle\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\Behat\Context\Step\When;

/**
 * Feature context.
 */
class FeatureContext extends MinkContext
{
    /**
     * @Given /^I am authenticated as "([^"]*)"$/
     */
    public function iAmAuthenticatedAs($username)
    {
        /** @var $user \Platformd\UserBundle\Entity\User */
        $user = $this->getEntityManager()
            ->getRepository('UserBundle:User')
            ->findOneBy(array('username' => $username))
        ;

        if (!$user) {
            throw new \Exception('Cannot find user with username '.$username);
        }

        return array(
            new When(sprintf('I am on "/?username=%s"', $user->getId())),
            new When('print last response'),
        );
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getEntityManager()
        ;
    }
}
