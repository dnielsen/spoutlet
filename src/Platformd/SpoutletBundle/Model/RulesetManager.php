<?php

namespace Platformd\SpoutletBundle\Model;

use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Model\RulesetInterface;

class RulesetManager
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function updateRules(RulesetInterface $entity, $andFlush=false)
    {
        $ruleset = $entity->getRuleset();
        $rules   = $ruleset->getRules();

        $newRulesArray = array();

        $defaultAllow = true;

        foreach ($rules as $rule) {
            if ($rule->getMinAge() || $rule->getMaxAge() || $rule->getCountry()) {
                $rule->setRuleset($ruleset);
                $newRulesArray[] = $rule;

                $defaultAllow = $rule->getRuleType() == "allow" ? false : $defaultAllow;
            }
        }

        $oldRules = $em->getRepository('SpoutletBundle:CountryAgeRestrictionRule')->findBy(array('ruleset' => $ruleset->getId()));

        if ($oldRules) {
            foreach ($oldRules as $oldRule) {
                if (!in_array($oldRule, $newRulesArray)) {
                    $oldRule->setRuleset(null);
                }
            }
        }

        $entity->getRuleset()->setParentType($entity->getRulesetParentType());
        $entity->getRuleset()->setDefaultAllow($defaultAllow);

        if ($andFlush) {
            $em->persist($entity);
            $em->flush();
        }
    }
}
