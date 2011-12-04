<?php

namespace Platformd\NewsBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Platformd\NewsBundle\Entity\News;

class LoadNews implements FixtureInterface
{
    public function load($manager)
    {
        $news = new News();
        $news->setBody(<<<EOF
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam nec scelerisque lacus. Curabitur nec ante a augue tincidunt luctus. Vivamus sed pellentesque nulla. Nunc id velit enim. Nullam vestibulum aliquet dui, in tempus nisl pretium ut. Cras nulla tortor, feugiat sed pharetra at, ultrices varius enim. Vestibulum augue neque, fringilla ut egestas in, vehicula quis augue. In ante ante, tincidunt quis vestibulum tempus, dictum sit amet quam. Sed aliquam sagittis erat nec sollicitudin. Nunc augue diam, malesuada quis aliquet eget, ullamcorper eu lacus. Curabitur egestas ante non nulla vehicula ultrices. Aenean aliquet ante eros, quis vestibulum justo. Aliquam interdum tempus dui, ut convallis lacus ullamcorper sit amet. Vestibulum in tortor elit. Nullam eget erat nisl, id consectetur sem. Ut ut dui nunc, et ullamcorper metus.</p>
EOF
        );
        $news->setTitle('News title #1');
        $manager->persist($news);

        $news = new News();
        $news->setBody(<<<EOF
<p>Integer egestas, neque non facilisis pellentesque, ipsum risus consectetur erat, ut blandit lacus quam nec arcu. Aenean ligula urna, viverra sed pharetra a, tempor sit amet augue. Quisque egestas blandit dolor, quis commodo diam adipiscing id. Aenean tellus arcu, tempus id congue non, placerat nec quam. Nulla tincidunt adipiscing felis at rhoncus. Maecenas et est ac odio dapibus gravida eu vel sapien. Integer hendrerit sodales dolor eget eleifend. Aliquam eleifend leo non urna vestibulum eu posuere lorem bibendum. Quisque eget mauris lacus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed vel tortor nec ligula posuere iaculis. Integer vestibulum fermentum ultrices. Aliquam pharetra, nisl nec aliquam aliquam, lorem arcu euismod nunc, quis condimentum nulla dui pharetra enim.</p>
EOF
        );
        $news->setTitle('News title #2');
        $manager->persist($news);

        $manager->flush();
    }
}