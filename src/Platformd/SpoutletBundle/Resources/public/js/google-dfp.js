
var googletag = googletag || {};
googletag.cmd = googletag.cmd || [];
(function() {
var gads = document.createElement('script');
gads.async = true;
gads.type = 'text/javascript';
var useSSL = 'https:' == document.location.protocol;
gads.src = (useSSL ? 'https:' : 'http:') + 
'//www.googletagservices.com/tag/js/gpt.js';
var node = document.getElementsByTagName('script')[0];
node.parentNode.insertBefore(gads, node);
})();


googletag.cmd.push(function() {
	googletag.defineSlot('/8969129/300x250_JP', [300, 250], 'div-gpt-ad-1330376602908-0').addService(googletag.pubads());
	googletag.defineSlot('/8969129/728x90_JP', [728, 90], 'div-gpt-ad-1330376602908-1').addService(googletag.pubads());
	googletag.pubads().enableSingleRequest();
	googletag.enableServices();
});
