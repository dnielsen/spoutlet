<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class CommentsController extends Controller
{

    public function threadAction($threadId)
    {
        return $this->render('SpoutletBundle:Comments:_thread.html.twig', array(
            'thread' => $this->getThread(),
        ));
    }

    public function repliesAction($commentId)
    {
        return $this->render('SpoutletBundle:Comments:_replies.html.twig', array(
            'replies' => $this->getReplies(),
        ));
    }

    private function getReplies()
    {
        $replies  = array(
            array(
                    'id' => 6,
                    'parent_id' => 1,
                    'author' => $this->getUser(),
                    'body' => "The path of the righteous man is beset on all sides by the iniquities of the selfish and the tyranny of evil men. Blessed is he who, in the name of charity and good will, shepherds the weak through the valley of darkness, for he is truly his brother's keeper and the finder of lost children. And I will strike down upon thee with great vengeance and furious anger those who would attempt to poison and destroy My brothers. And you will know My name is the Lord when I lay My vengeance upon thee.",
                    'depth' => 1,
                    'created_at' => '2012-12-14 14:27:00',
                    'votes' => 0,
                    'replies' => array(),
                ),
            array(
                    'id' => 7,
                    'parent_id' => 1,
                    'author' => $this->getUser(),
                    'body' => "Well, the way they make shows is, they make one show. That show's called a pilot. Then they show that show to the people who make shows, and on the strength of that one show they decide if they're going to make more shows. Some pilots get picked and become television programs. Some don't, become nothing. She starred in one of the ones that became nothing.",
                    'depth' => 1,
                    'created_at' => '2012-12-14 14:29:00',
                    'votes' => 0,
                    'replies' => array(),
                )
            );

        return $replies;
    }

    private function getThread()
    {
        $thread = array(
            'id' => 1,
            'can_comment' => true,
            'last_comment_at' => '2012-12-10 11:59:00',
            'permalink' => 'http://www.example.com/news/some-artcle#comments',
            'comments' => array(
                    array(
                            'id'            => 1,
                            'parent_id'     => 0,
                            'author'        => $this->getUser(),
                            'body'          => "Now that there is the Tec-9, a crappy spray gun from South Miami. This gun is advertised as the most popular gun in American crime. Do you believe that shit? It actually says that in the little book that comes with it: the most popular gun in American crime. Like they're actually proud of that shit.",
                            'depth'         => 0,
                            'created_at'    => '2012-12-10 11:44:00',
                            'votes'         => 0,
                            'replies'       => array(
                                    array(
                                            'id' => 3,
                                            'parent_id' => 1,
                                            'author' => $this->getUser(),
                                            'body' => "Like you, I used to think the world was this great place where everybody lived by the same standards I did, then some kid with a nail showed me I was living in his world, a world where chaos rules not order, a world where righteousness is not rewarded. That's Cesar's world, and if you're not willing to play by his rules, then you're gonna have to pay the price.",
                                            'depth' => 1,
                                            'created_at' => '2012-12-10 11:50:00',
                                            'votes' => 0,
                                            'replies' => array(),
                                        ),
                                    array(
                                            'id' => 4,
                                            'parent_id' => 1,
                                            'author' => $this->getUser(),
                                            'body' => "Your bones don't break, mine do. That's clear. Your cells react to bacteria and viruses differently than mine. You don't get sick, I do. That's also clear. But for some reason, you and I react the exact same way to water. We swallow it too fast, we choke. We get some in our lungs, we drown. However unreal it may seem, we are connected, you and I. We're on the same curve, just on opposite ends.",
                                            'depth' => 1,
                                            'created_at' => '2012-12-10 11:51:00',
                                            'votes' => 0,
                                            'replies' => array(),
                                        ),
                                    array(
                                            'id' => 5,
                                            'parent_id' => 1,
                                            'author' => $this->getUser(),
                                            'body' => "Duis accumsan velit quis lorem ultricies vestibulum. Vestibulum velit diam, interdum nec suscipit sit amet, ultrices sit amet dui. Donec sapien urna, pretium eget fringilla vel, posuere ut metus. Duis tempor lacus ultrices quam ultrices sagittis. Ut adipiscing, sapien sodales tincidunt fringilla, risus justo facilisis lacus, ac luctus leo dolor eu neque. Donec egestas, orci at egestas congue, libero tellus consectetur ante, vitae varius leo massa euismod dolor. Quisque vel arcu nisl. Curabitur ac ipsum tincidunt ante egestas tristique id quis lacus. Donec volutpat tincidunt quam in fringilla. Praesent condimentum dapibus sodales. Fusce quis odio elit, sit amet tristique nibh. Pellentesque vehicula venenatis leo in rutrum.",
                                            'depth' => 1,
                                            'created_at' => '2012-12-14 14:10:00',
                                            'votes' => 0,
                                            'replies' => array(),
                                        )
                                )
                        ),
                    array(
                            'id'            => 2,
                            'parent_id'     => 0,
                            'author'        => $this->getUser(),
                            'body'          => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras gravida est semper velit ultrices eget tempus purus cursus. Nulla dignissim posuere tristique. In id purus in sapien aliquet dictum quis eget nisl. Fusce non orci est, ut sagittis mi. Curabitur euismod dui vitae massa pharetra vel vestibulum eros sodales. Vivamus scelerisque dictum orci, nec rhoncus risus hendrerit eu. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam erat volutpat.",
                            'depth'         => 0,
                            'created_at'    => '2012-12-18 9:45:00',
                            'votes'         => 0,
                            'replies'       => array()
                        )
                )
        );


        return $thread;
    }

}
