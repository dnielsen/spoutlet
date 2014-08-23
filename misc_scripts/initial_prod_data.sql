INSERT INTO entry_set_registry VALUES 
(1,'SpoutletBundle:Site',1),
(2,'SpoutletBundle:Site',2),
(3, 'GroupBundle:Group',1);

INSERT INTO pd_groups (
id,name,category,isPublic,owner_id,description,slug,relativeSlug,entrySetRegistration_id) VALUES 
(1, 'MarketingCamp', 'topic', 1, 1, '<h1>About MarketingCamp</h1><p>MarketingCamp is a gathering of marketing thought-leaders who get together to dialogue and brainstorm about marketing topics, tools, trends, and technology they share as passions.</p>','marketingcamp', 'marketingcamp', 3);

INSERT INTO pd_site (
id, name, defaultLocale, fullDomain, theme, entrySetRegistration_id, communityGroup_id) VALUES 
(1, 'Campsite','en_campsite','www.campsite.org','default', 1, NULL),
(2, 'MarketingCamp','en_campsite','www.marketingcamp.org','default', 2, 1);

INSERT INTO pd_site_config (
id,site_id,supportEmailAddress,automatedEmailAddress,emailFromName,birthdateRequired,forward_base_url,min_age_requirement) VALUES 
(1,1,'support@campsite.org','noreply@campsite.org','Campsite.org',0,'www.campsite.org',0),
(2,2,'support@campsite.org','noreply@campsite.org','MarketingCamp.org',0,'www.marketingcamp.org',0);

INSERT INTO pd_site_features (
id, site_id,has_video,has_steam_xfire_communities,has_sweepstakes,has_forums,has_arp,has_news,has_deals,has_games,has_games_nav_drop_down,has_messages,has_groups,has_wallpapers,has_microsoft,has_photos,has_contests,has_comments,has_events,has_giveaways,has_html_widgets,has_facebook,has_google_analytics,has_profile,has_tournaments,has_match_client,has_forward_on_404,has_index,has_about,has_contact,has_search,has_polls,has_static_photo_widget,has_multi_site_groups) VALUES
('1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '1', '1', '0', '1', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0', '0', '0'), 
('2', '2', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '1', '1', '0', '1', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0', '0', '0');

INSERT INTO pd_group_site VALUES (1, 2);
