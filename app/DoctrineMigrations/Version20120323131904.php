<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120323131904 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs

        $japan1 = <<<EOF

<p>
    「ALIENWARE ARENA」は、日本のオンラインゲームのメッカである東京・秋葉原にある「アイ・カフェAKIBA PLACE 」へ進出します。
</p>

<p>
    2012年2月18日午前11時にオープンする「ALIENWARE ARENA in アイ・カフェ AKIBA PLACE」では、フラッグシップモデルの「ALIENWARE Aurora」を40台以上導入し、これ以上ないハイパフォーマンスな環境でゲームプレイを体感できます。
</p>

<p>
    さらに、オープンを記念して様々なイベント企画しておりますのでぜひご来店ください。
</p>

<h4>
    「1時間ゲーム無料プレイ」キャンペーン
</h4>

<p>
    場所：アイ・カフェAKIBA PLACE、東京都千代田区外神田3-15-1 8F
    <br/>
    期間：2012年2月18日～24日
</p>

<h4>
    「ALIENWARE特製カード型USBメモリ」プレゼントキャンペーン（先着250名様）
</h4>

<p>
    場所：アイ・カフェAKIBA PLACE、東京都千代田区外神田3-15-1 8F
    <br/>
    期間：2012年2月18日～　(景品が無くなり次第終了)
    <br/>
    参加条件：施設をご利用の上、アンケートにお答えいただきます。
</p>

<h4>
    「ALIENWARE M14x　ホワイト・リミテッド・エディション」プレゼントキャンペーン(1名様)
</h4>

<p>
    場所：ALIENWARE ARENA
    <br/>
    期間：2012年2月18日～3月31日
    <br/>
    対象：<a href="http://www.alienwarearena.com/japan">www.alienwarearena.com/japan</a> でアカウント作成しログインされた方
</p>

<p>
    その他の情報は以下のサイトにアクセスしてご確認ください。
    <br/>
    <a href="http://ja.community.dell.com/dell-blogs/direct2dell/b/direct2dell/archive/2012/02/07/alienware-arena-in-akiba-place.aspx">
        http://ja.community.dell.com/dell-blogs/direct2dell/b/direct2dell/archive/2012/02/07/alienware-arena-in-akiba-place.aspx
    </a>
</p>

EOF;

        $japan2 = <<< EOF
<p>
遂にAlienware Arenaが公式に日本で展開されることになりました。Alienware Arenaは、これまで北米、南米、ヨーロッパ、アジアとサービス地域を拡大して来ており、日本は7番目の地域になります。
</p>

<p>
Alienware Arenaは、主にオンラインゲームトーナメント、各種キャンペーン、新作ゲームの無料体験など、PCゲーマーのためのイベントや情報を提供します。これらはすべて日本のプレイヤー向けに特別に用意されるものです。
</p>

<p>
また、現在Alienware Arenaには世界中から100万アカウントの登録があります。
</p>

<p>
「ゲームとは単に遊ぶためのシステムではなく、世界とつながり、関わり合うことができるコミュニティでもあるのです。」と、デルのAlienware担当ジェネラルマネージャであるArthur Lewis氏はコメントしています。
</p>

<p>
新登場の日本サイトを今すぐご覧ください。
</p>
EOF;

        $china1 = <<< EOF

<h3>Alienware Arena入侵中国</h3>

<p>
    戴尔的游戏社区网站扩展至中国地区，在线以及在人气场馆开展新活动
</p>

<p>
    我们非常激动地宣布Alienware Arena已经正式延伸至中国地区！中国是Alienware Arena第八个扩展区域的代表，Alienware Arena之前一直在北美、亚太地区、欧洲和拉丁美洲举办活动。现在你可以在线和在知名游戏集聚地体验专为中国会员打造的个人电脑游戏、邀请码活动和更多精彩内容了。
</p>

<p>
    Alienware Arena将在世界范围内20多个国家举办电脑游戏推广和社区活动，涵盖所有大型个人电脑游戏市场。
</p>

<p>
    据统计，Alienware Arena在世界上拥有100万注册会员。
</p>

<p>
    “游戏不仅仅是你所使用的操作系统，它是一个你参与的与世界相通的社区”，戴尔Alienware总经理亚瑟·刘易斯表示。
</p>

EOF;

        $this->addSql("
            INSERT INTO sp_news
                (title, body, slug, locale, published, created, updated, postedAt, blurb)
                VALUES
                ('Akiba Place', '$japan1', 'akiba-place', 'ja', 1, '2012-03-23 12:00:00', '2012-03-23 12:00:00', '2012-02-17', '2012年2月18日午前11時にオープンする「ALIENWARE ARENA in アイ・カフェ AKIBA PLACE」では、フラッグシップモデルの「ALIENWARE Aurora」を40台以上導入し、これ以上ないハイパフォーマンスな環境でゲームプレイを体感できます。')
        ");

        $this->addSql("
            INSERT INTO sp_news
                (title, body, slug, locale, published, created, updated, postedAt, blurb)
                VALUES
                ('Alienware Arenaが公式に日本で展開されることになりました', '$japan2', 'launch', 'ja', 1, '2011-03-23 12:00:00', '2012-03-23 12:00:00', '2011-11-15', '遂にAlienware Arenaが公式に日本で展開されることになりました。Alienware Arenaは、これまで北米、南米、ヨーロッパ、アジアとサービス地域を拡大して来ており、日本は7番目の地域になります。')
        ");

        $this->addSql("
            INSERT INTO sp_news
                (title, body, slug, locale, published, created, updated, postedAt, blurb)
                VALUES
                ('Alienware Arena入侵中国', '$china1', 'launch-china', 'zh', 1, '2011-03-23 12:00:00', '2012-03-23 12:00:00', '2011-11-15', '我们非常激动地宣布Alienware Arena已经正式延伸至中国地区！中国是Alienware Arena第八个扩展区域的代表，Alienware Arena之前一直在北美、亚太地区、欧洲和拉丁美洲举办活动。现在你可以在线和在知名游戏集聚地体验专为中国会员打造的个人电脑游戏、邀请码活动和更多精彩内容了。')
        ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs

    }
}
