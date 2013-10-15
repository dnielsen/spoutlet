<?php

namespace Platformd\SweepstakesBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Entity\SweepstakesQuestion;
use Platformd\SweepstakesBundle\Entity\SweepstakesEntry;
use Platformd\SweepstakesBundle\Entity\SweepstakesAnswer;
use DateTime;

class LoadSweepstakes extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;
    private $manager;

    private function createSweepstakes($name, $site, $published=true, $startsAt=null, $endsAt=null)
    {
      $sweeps = new Sweepstakes();

      $sweeps->setName($name);
      $sweeps->getSites()->add($site);
      $sweeps->setPublished($published);
      $sweeps->setStartsAt($startsAt ?: new DateTime('-1 week'));
      $sweeps->setEndsAt($endsAt ?: new DateTime('+1 week'));
      $sweeps->setContent($this->getContent());
      $sweeps->setOfficialRules('Rules Detailed Here...');

      $question = new SweepstakesQuestion();
      $question->setContent('Question 1');
      $sweeps->addSweepstakesQuestion($question);

      $question = new SweepstakesQuestion();
      $question->setContent('Question 2');
      $sweeps->addSweepstakesQuestion($question);

      $this->manager->persist($sweeps);

      return $sweeps;
    }

    private function loadEntries3($sweeps)
    {
      $user = $this->manager->getRepository('UserBundle:User')->find(1);
      $questions = $sweeps->getQuestions();
      $question1 = $questions[0];
      $question2 = $questions[1];
      // 5000 seems to break the metrics so im adding this here for now to help debug the problem.
      for ($i=0; $i < 100; $i++) {
        $entry = new SweepstakesEntry($sweeps);
        $entry->setUser($user);
        $entry->setIpAddress('192.168.1.1');
        $entry->setPhoneNumber(sprintf('(%s)5551212', $i));

        $answer1 = new SweepstakesAnswer($question1, $entry);
        $answer1->setContent(sprintf('no region answer1 content %s', $i));
        $answer2 = new SweepstakesAnswer($question2, $entry);
        $answer2->setContent(sprintf('no region answer2 content %s', $i));

        $this->manager->persist($entry);
        $this->manager->persist($answer1);
        $this->manager->persist($answer2);
      }
      $this->manager->flush();
    }

    private function loadEntries2($sweeps)
    {
      $user = $this->manager->getRepository('UserBundle:User')->find(1);
      $country = $this->manager->getRepository('SpoutletBundle:Country')->find(182);
      $questions = $sweeps->getQuestions();
      $question1 = $questions[0];
      $question2 = $questions[1];
      // 5000 seems to break the metrics so im adding this here for now to help debug the problem.
      for ($i=0; $i < 200; $i++) {
        $entry = new SweepstakesEntry($sweeps);
        $entry->setUser($user);
        $entry->setIpAddress('192.168.1.1');
        $entry->setCountry($country);
        $entry->setPhoneNumber(sprintf('(%s)5551212', $i));

        $answer1 = new SweepstakesAnswer($question1, $entry);
        $answer1->setContent(sprintf('poland answer1 content %s', $i));
        $answer2 = new SweepstakesAnswer($question2, $entry);
        $answer2->setContent(sprintf('poland answer2 content %s', $i));

        $this->manager->persist($entry);
        $this->manager->persist($answer1);
        $this->manager->persist($answer2);
      }
      $this->manager->flush();
    }

    private function loadEntries($sweeps)
    {
      $user = $this->manager->getRepository('UserBundle:User')->find(1);
      $country = $this->manager->getRepository('SpoutletBundle:Country')->find(244);
      $questions = $sweeps->getQuestions();
      $question1 = $questions[0];
      $question2 = $questions[1];
      // 5000 seems to break the metrics so im adding this here for now to help debug the problem.
      for ($i=0; $i < 5000; $i++) {
        $entry = new SweepstakesEntry($sweeps);
        $entry->setUser($user);
        $entry->setIpAddress('192.168.1.1');
        $entry->setCountry($country);
        $entry->setPhoneNumber(sprintf('(%s)5551212', $i));

        $answer1 = new SweepstakesAnswer($question1, $entry);
        $answer1->setContent(sprintf('answer1 content %s', $i));
        $answer2 = new SweepstakesAnswer($question2, $entry);
        $answer2->setContent(sprintf('answer2 content %s', $i));

        $this->manager->persist($entry);
        $this->manager->persist($answer1);
        $this->manager->persist($answer2);
      }
      $this->manager->flush();
    }

    private function resetAutoIncrementId()
    {
        $con = $this->manager->getConnection();

        $con
            ->prepare("ALTER TABLE `pd_sweepstakes` AUTO_INCREMENT = 1")
            ->execute();
    }

    public function load($manager)
    {
        $this->manager = $manager;

        $this->resetAutoIncrementId();

        $site = $this->manager->getRepository('SpoutletBundle:Site')->find(1);
        $user = $this->container->get('fos_user.user_manager')->findUserByUsername('admin');

        $sweeps = $this->createSweepstakes('Sweepola ', $site);


        $this->manager->flush();

        // helps benchmarking metrics reports
        //$this->loadEntries($sweeps);
        //$this->loadEntries2($sweeps);
        //$this->loadEntries3($sweeps);
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getOrder()
    {
        return 3;
    }

    private function getContent()
    {
      return <<<EOF
        <h1>I can do that</h1>
        <p>Your bones don't break, mine do. That's clear. Your cells react to bacteria and viruses differently than mine. You don't get sick, I do. That's also clear. But for some reason, you and I react the exact same way to water. We swallow it too fast, we choke. We get some in our lungs, we drown. However unreal it may seem, we are connected, you and I. We're on the same curve, just on opposite ends. </p>

        <h1>Are you ready for the truth?</h1>
        <p>Your bones don't break, mine do. That's clear. Your cells react to bacteria and viruses differently than mine. You don't get sick, I do. That's also clear. But for some reason, you and I react the exact same way to water. We swallow it too fast, we choke. We get some in our lungs, we drown. However unreal it may seem, we are connected, you and I. We're on the same curve, just on opposite ends. </p>

        <h1>I took lessons</h1>
        <p>The lysine contingency - it's intended to prevent the spread of the animals is case they ever got off the island. Dr. Wu inserted a gene that makes a single faulty enzyme in protein metabolism. The animals can't manufacture the amino acid lysine. Unless they're continually supplied with lysine by us, they'll slip into a coma and die. </p>

        <div style="text-align:center;"><img alt="300" src="http://www.fillmurray.com/300/300" /><img alt="300" src="http://www.stevensegallery.com/300/300" style="margin-left:100px;" /></div>
EOF;
    }
}

?>
