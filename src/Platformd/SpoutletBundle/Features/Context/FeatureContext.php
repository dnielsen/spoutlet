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
    public function iAmAuthenticatedAs($user)
    {
        return array(
            new When('I am on "/login"'),
            new When(sprintf('I fill in "Email:" with "%s"', $user)),
            new When(sprintf('I fill in "Password:" with "%s"', $user)),
            new When('I press "Login"'),
        );
    }
}
