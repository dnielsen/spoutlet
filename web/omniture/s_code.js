// SiteCatalyst code version: H.22.1
// SUPPORTED ENVIRONMENTS: Dell Storm, Dell Nextgen, Dell Third-party sites
// UPDATED: 25-Apr-2011


var s_account='dellglobalonline';
var s_dell=s_gi(s_account);

/************************** CONFIG SECTION **************************/

/* Dell Config */
if(!s_dell.localDoms)s_dell.localDoms='javascript:,dell.,dellcomputer.,dellcomputers.,dellcustomerservice.,delldirect.,delldrivers.,dellfinancialservices.,dellideas.,dellnet.,dellstore.,dellsupport.,delltalk.,dellteam.,dellvistaupgrade.,dfsdirectsales.,inspiron.';
if(!s_dell.supportDoms)s_dell.supportDoms='docs.,dellcustomerservice.';
s_dell.isPageLoad=true;

/* Conversion Config */
s_dell.charSet='UTF-8';

/* Link Tracking Config */
s_dell.trackDownloadLinks=true;
s_dell.trackExternalLinks=true;
s_dell.trackInlineStats=true;
s_dell.linkDownloadFileTypes='exe,zip,wav,mp3,mov,mpg,avi,wmv,pdf,doc,docx,xls,xlsx,ppt,pptx';
s_dell.linkInternalFilters=s_dell.localDoms+',alienware.,dell-ins,easy2.,ideastorm.,livelook.,sellpoint.,syndication.intel.,triaddigital.,webcollage.,boomi.,flixmedia.,dellxps15z.,flixfacts.,thepowertodomore.,kace.,secureworks.,equallogic.,dell-brand.,dellcampaignbuilder.,dellnewscentre.,netexam.,dell-virtualisation.,alienwarearena.';
s_dell.linkLeaveQueryString=false;
s_dell.linkTrackVars='None';
s_dell.linkTrackEvents='None';
s_dell.ActionDepthTest=true

/* Plugin Config */
s_dell.usePlugins=true;



/***************************** doPlugins ****************************/



function s_dell_doPlugins(s){

	if(!s.server)s.server=parseUri(document.location.href).host.replace(/^www[0-9]*\./i,'');

	s.processLWP();

	s.events=s.events?s.events:'';

	s.setupDynamicObjectIDs();

	/* Begin Page Name Logic */

	if(s.onDellCMS()){

		var pn=s.getHTMLtag('meta','metricspath').toLowerCase(),n='';
		if(pn.indexOf('&amp;eiwatch=')&gt;-1)pn=s.repl(pn,'&amp;eiwatch=','');

		if(!s.pageName||s.pageName.indexOf('dellstore^')&gt;-1){
			s.pageName='';
			if(document.location.href=='http://www.dell.com/'||pn=='www1.us.dell.com/us/en/gen//content^default/'){
				s.pageName='dell.com homepage'; //handle dell.com homepages (static and dynamic should have same name)
			}
			var pna=s.split(pn,'/'); //split on / delimiter
			if(pna.length&gt;0&amp;&amp;pna.length&lt;6){ //handle page cloaking
				if(!s.pageName){
					if(pn.indexOf('//')&gt;-1)pn=pn.substring(pn.indexOf('//')+2);
					pn=pn.replace(/^www[0-9]*\./i,'');
					if(pn.indexOf('?')&gt;-1){
						s.pageName=pn.substring(0,pn.indexOf('?'));
					}else{
						s.pageName=pn;
					}
				}
			}

			/*
			 *	Storm Page Name Logic
			 *
			 *	default storm format:
			 *	domain/country/lauguage/segment/customerset/uristem^info/   /[optionalparameters]
			 *	0   1   2    3     4    5   6   7
			 */
			if(s.determineCMS()=='storm'&amp;&amp;pn&amp;&amp;!s.pageName){
				s.prop14=pn; //set original page key
				var a7=pna[7],a6=pna[6]; //querystring value (optional parameters)
				var ovf=af=false; //other value flag, appended flag (for else case)
				var pn=dpn=n=''; //clear page name
				for(var i=1;i&lt;8;i++){
					if(i==4&amp;&amp;pna[0].indexOf('premier')&gt;-1){pn=s.apl(pn,'',':',0);af=true;}
					if(i==4&amp;&amp;pna[4].indexOf('rc')==0&amp;&amp;!af){pn=s.apl(pn,'',':',0);af=true;}
					if(i==6&amp;&amp;a6&amp;&amp;a7){
						if(pna[6].indexOf('[')&gt;-1){
							pn=s.apl(pn,'',':',0);
							af=true;
						}
					} else if(i==6&amp;&amp;a6){
						if(pna[6].indexOf('[')&gt;-1)af=true;
					} else if(i==6){
						pn=s.apl(pn,pna[i],':',0);
						af=true;
					}
					/* values to include in page name AND details page name */
					if(i==7&amp;&amp;a7){
						if(a7.indexOf('category_id=')&gt;-1){
							n=a7.substring(a7.indexOf('category_id=')+12);
							n=n.substring(0,n.indexOf(']'));
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('categoryid=')&gt;-1){
							n=a7.substring(a7.indexOf('categoryid=')+11);
							n=n.substring(0,n.indexOf(']'));
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('sku=')&gt;-1&amp;&amp;pn.indexOf('addedtocart')==-1){
							n=a7.substring(a7.indexOf('sku='));
							n='['+n.substring(0, n.indexOf(']')+1);
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('oc=')&gt;-1&amp;&amp;pna[0].indexOf('premier')==-1&amp;&amp;pn.indexOf('dellstore^config')&gt;-1){
							n=a7.substring(a7.indexOf('oc='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('product_id=')&gt;-1){
							n=a7.substring(a7.indexOf('product_id='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('productid=')&gt;-1){
							n=a7.substring(a7.indexOf('productid='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('[~id=')&gt;-1&amp;&amp;pn.indexOf('imagedirect')==-1){
							n=a7.substring(a7.indexOf('id='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('[id=')&gt;-1&amp;&amp;pn.indexOf('imagedirect')==-1){
							n=a7.substring(a7.indexOf('id='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('topic=')&gt;-1){
							n=a7.substring(a7.indexOf('topic='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){pn=s.apl(pn,n,'',0);af=true;}else{pn=s.apl(pn,n,':',0);ovf=true;af=true;}
						}
						dpn=pn;
						/* values to include in details page name */
						if(a7.indexOf('section=')&gt;-1){
							n=a7.substring(a7.indexOf('section='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){dpn=s.apl(dpn,n,'',0);af=true;}else{dpn=s.apl(dpn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('tab=')&gt;-1){
							n=a7.substring(a7.indexOf('tab='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){dpn=s.apl(dpn,n,'',0);af=true;}else{dpn=s.apl(dpn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('page=')&gt;-1){
							n=a7.substring(a7.indexOf('page='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){dpn=s.apl(dpn,n,'',0);af=true;}else{dpn=s.apl(dpn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('brandid=')&gt;-1){
							n=a7.substring(a7.indexOf('brandid='));
							n='['+n.substring(0,n.indexOf(']')+1);
							if(ovf){dpn=s.apl(dpn,n,'',0);af=true;}else{dpn=s.apl(dpn,n,':',0);ovf=true;af=true;}
						}
						if(a7.indexOf('cat=')&gt;-1){ //values to variable only
							n=a7.substring(a7.indexOf('cat=')+4);
							n=n.substring(0,n.indexOf(']'));
							if(pna[0].indexOf('search')&gt;-1)s.eVar9=n;
							af=true;
						}
					}
					if(!af&amp;&amp;i!=7)pn=s.apl(pn,pna[i],':',0);
					af=false;
				}

				/* cleanup - remove trailing colon and undefined */
				if(pn.length-1==pn.lastIndexOf(':'))pn=pn.substring(0,pn.length-1);
				if(pn.indexOf(':undefined')&gt;-1)pn=pn.substring(0,pn.indexOf(':undefined'));
				if(dpn.length-1==dpn.lastIndexOf(':'))dpn=dpn.substring(0,dpn.length-1);

				/* cleanup - remove dellstore: from the beginning of the string for ecomm */
				if(pn.indexOf('dellstore:')==0)pn=pn.substring(10,pn.length);
				if(dpn.indexOf('dellstore:')==0)dpn=dpn.substring(10,dpn.length);

				s.pageName=pn;
				dpn=dpn?s.prop13=dpn:s.prop13=pn;
			}
		}

		if(!s.pageName)s.pageName=s_dell.getPNfromURL();
		if(!s.prop13)s.prop13=s.pageName;
		if(!s.prop14)s.prop14=s.pageName;

		/* Set prop29 to CMS name. Modified 06/12/10 */
		s.prop29=s.determineCMS();
		if(s.prop29=='unknown'||!s.prop29)s.prop29='unknown:'+s.server;

		/* updated 10/29/08: handling of AJAX pages */
		if(s.pageName.indexOf('ajax')&gt;-1){
			s.prop14=s.pageName;
			if(s.prop13.indexOf(':ajax')&gt;-1){
				s.pageName=s.prop13.substring(0,s.prop13.indexOf(':ajax'));
			}else{
				s.pageName=s.prop13;
				s.prop13=s.prop13+':ajax';
			}
			if(s.prop14.indexOf('&amp;')&gt;-1){
				s.prop14=s.prop14.substring(0,s.prop14.indexOf('&amp;'));
			}
		}

	}else{ //not on dell.com

		if(!s.pageName)s.pageName=s.getPNfromURL();
		if(!s.prop13)s.prop13=s.pageName;

	}

	/* END Page Name Logic */

	/* Added 10/24/08: getPreviousValue of pageName */
	if (typeof(s.linkType)=='undefined'||s.linkType=='e'){
		s.gpv_pn=s.getPreviousValue(s.pageName,'gpv_pn','');
		if(s.gpv_pn=='no value')s.gpv_pn='';
	}

	/* Classify page as Support (event22) or Segment (event23) */
	var spg=false;
	if(!spg&amp;&amp;(s.server.indexOf('dell')&gt;=0)&amp;&amp;s.server.indexOf('support')&gt;=0)spg=true; //check domain for 'dell' and 'support' in any order
	if(!spg&amp;&amp;s.server.match('('+s.supportDoms.replace(/,/gi,'|').replace(/\./gi,'\\.')+')'))spg=true; //Check domain for specific support domains
	if(!spg&amp;&amp;s.determineCMS()=='nextgen'){ //Logic to determine if a Nextgen page is a Support page follows...
		var urlpn=document.location.pathname.toLowerCase();
		if(urlpn){
			if(!spg&amp;&amp;urlpn.indexOf('/order-support')&gt;=0)spg=true;
			if(!spg&amp;&amp;urlpn.indexOf('/support')&gt;=0)spg=true;
		}
	}
	s.events=s.apl(s.events,(spg?'event22':'event23'),',',2);

	/** added by Jason Case 25 Apr 2011 to include these events in tl calls**/
	if(typeof(s.linkType)!='undefined'){
	    s.linkTrackVars=s.apl(s.linkTrackVars,'events',',',2);
        s.linkTrackEvents=s.apl(s.linkTrackEvents,'event22',',',2);
        s.linkTrackEvents=s.apl(s.linkTrackEvents,'event23',',',2);
    }

	/* BEGIN dell.com Only Logic */

	if(s.onDellCMS()){

		/* Utility script - check inputs for Dell values */
		s.ss_kw=s.getHTMLtag('input','kw','id','value');
		s.ss_rff=s.getHTMLtag('input','rff','id','value');
		s.es_on=s.getHTMLtag('input','order_number','name','value');
		s.ss_dkw=s.getQueryParam('sk'); //Check for direct search result keyword. Added 12/06/10

		/* Order Number (uses value from HTML input) */
		if(s.es_on)s.prop22=s.es_on;

		/* Site Search (uses values from HTML inputs) */
		if(s.ss_kw&amp;&amp;s.ss_rff){
			if(s.ss_rff=='1')s.prop7=s.ss_kw;
			else if(s.ss_rff=='2')s.prop7='reclink:'+s.ss_kw;
			else if(s.ss_rff=='3')s.prop7='othercat:'+s.ss_kw;
			else if(s.ss_rff=='0')s.prop7='null:'+s.ss_kw;
		}else if(s.ss_dkw){
			s.prop7='redirect:'+s.ss_dkw; //Save direct search result keyword. Added 12/06/10
		}
		if(s.prop7){
			s.prop7=s.prop7.toLowerCase();
			s.eVar36=s.prop7;
			var t_search=s.getValOnce(s.eVar36,'v36',0);
			if(t_search){
				s.events=s.apl(s.events,'event6',',',2);
				s.prop42=s.gpv_pn;
			}
		}

		s.prop43=s.getQueryParam('ID','','?'+gC('SITESERVER')); //Save ID parameter from SITESERVER cookie

		/* ServiceTag presented on page */
		if(!s.prop17)s.prop17=s.getHTMLtag('input','servicetagmetricsid','id','value');

		/* Set events based on pageName */
		if(s.pageName.indexOf('order^recentorders')&gt;-1||s.pageName.indexOf('order^details')&gt;-1||s.pageName.indexOf('order^singlestatus')&gt;-1||s.pageName.indexOf('order^multiplestatus')&gt;-1)s.events=s.apl(s.events,'event11',',',2);
		if(s.pageName.indexOf('dellstore^basket')&gt;-1)s.events=s.apl(s.events,'scView',',',2); //set cart visit (config in SC once per visit)
		if(s.pageName.indexOf('chkout')&gt;-1)s.events=s.apl(s.events,'scCheckout',',',2); //set checkout start (config in SC once per visit)

		/* Products/Events Handling */
		if(s.pageName.indexOf('sna^productdetail')&gt;-1||s.pageName.indexOf('content^products^productdetails')&gt;-1){
			var prod=s.getQueryParam('sku,oc');
			s.events=s.events?s.events:'';
			if(s.events.indexOf('prodView,event2')&gt;-1)s.events=s.repl(s.events,'prodView,event2','');
			if(prod&amp;&amp;s.events.indexOf('event3')&gt;-1){
				if(prod.indexOf(','))prod=s.repl(prod,',',',;');
				s.products=s.apl(s.products,';'+prod,',',2);
			}else{
				if(prod){
					prod=s.dedupVal('sku_oc',prod);
					if(prod){
						if(prod.indexOf(','))prod=s.repl(prod,',',',;');
						s.products=s.apl(s.products,';'+prod,',',2);
						s.events=s.apl(s.events,'prodView',',',2);
						s.events=s.apl(s.events,'event2',',',2);
					}
				}
			}
		}

		/* Manage config starts by unique order code - most recent unique value per session will set the event */
		if(s.pageName.indexOf('dellstore^config')&gt;-1){
			var oc=s.getQueryParam('oc');
			if(oc)oc=s.dedupVal('ocstart',oc);
			if(oc){
				s.products=s.apl(s.products,';'+oc,',',2);
				s.events=s.apl(s.events,'event10',',',2);
				s.events=s.apl(s.events,'prodView',',',2);
				s.events=s.apl(s.events,'event2',',',2);
			}
		}

		/* Final check to see if we have products with no events, clear products if we do */
		if(s.products){
			s.products=s.events?s.products:'';
			s.events=s.events?s.events:'';
			/* Check for semicolon in products */
			if(s.products&amp;&amp;s.products.indexOf(';')!=0&amp;&amp;s.events.indexOf('scAdd')&gt;-1){
				var p=s.products;
				if(p.indexOf(';')&gt;-1&amp;&amp;p.indexOf(',;')&gt;-1){
					s.products=';'+p;
				}else if(p.indexOf(';')&gt;-1){
					var pa=s.split(p,';');
					p=';'+pa[0];
					for(var i=1;i&lt;pa.length;i++)p+=',;'+pa[i];
					s.products=p;
				}else{
					s.products=';'+p;
				}
			}
		}

		/* Set events based on page URL */
		var loc=document.location.href;
		if(loc.indexOf('/financing/app.aspx')&gt;-1||loc.indexOf('/financing/us_ca/app.aspx')&gt;-1||loc.indexOf('/financing/process.aspx')&gt;-1)s.events=s.apl(s.events,'event8',',',2); //set application start
		if(loc.indexOf('/financing/approved.aspx')&gt;-1||loc.indexOf('/financing/us_ca/approved.aspx')&gt;-1||loc.indexOf('/financing/declined.aspx')&gt;-1 ||loc.indexOf('/financing/reviewed.aspx')&gt;-1||loc.indexOf('/financing/us_ca/reviewed.aspx')&gt;-1)s.events=s.apl(s.events,'event9',',',2); //set application complete

		/* Set S&amp;P Visits */
		if(loc.indexOf('accessories')&gt;-1||s.pageName.indexOf('accessories')&gt;-1)s.events=s.apl(s.events,'event12',',',2);

		/* Read and format cookie values */
		s.prop45=s.c_r('GAAuth');
		if(!s.prop45){
			var cookieArray=document.cookie.split(';');
			for(var i=0;i&lt;cookieArray.length;i++){
				var cookie=cookieArray[i];
				while (cookie.charAt(0)==' ')cookie=cookie.substring(1,cookie.length);
				if(cookie.match(/gahot=/i))s.prop45=cookie.substring(6,cookie.length);
			}
		}
		s.prop46=s.c_r('Profile')?s.c_r('Profile'):s.c_r('profile');
		s.prop48=s.parseCookie('prt:Prof','cnm,sid,cs',','); //issues with this cookie read for prt:Prof?
		s.prop16=s.parseCookie('StormPCookie','penv',',');

		/* Read and store Baynote cookies */
		var bn_search=s.c_r('search_bn'); //Added 9/9/10: Get Baynote Search cookie
		if(bn_search)s.eVar11=bn_search;
		var bn_snp=s.c_r('snp_bn'); //Added 9/9/10: Get Baynote SNP cookie
		if(bn_snp)s.eVar13=bn_snp;

		s.prop16=s.getQueryParam('penv','',s.prop16);

		s.prop12=(s.prop45)?'logged in':'not logged in';

		/* Added 07/09/10: special processing for error pages */
		if((s.pageName.indexOf('content^public^notfound')&gt;-1)||(s.pageName.indexOf('content^public^error')&gt;-1)){
			if(!s.prop44){ //put URL that resulted in error page in prop44
				var errQP=s.getQueryParam('searched');
				if(!errQP)errQP=s.getQueryParam('aspxerrorpath');
				s.prop44=errQP?errQP.replace(':80',''):document.location.href;
			}
			//Added 07/09/10: prevent error pages from being misclassified as "public" if referrer external or no LWP cookie
			var refdom=parseUri(document.referrer).host.toLowerCase();
			if(refdom.indexOf('dell.')==-1||!gC('lwp'))s.prop5='not set';
		}

		/* FIX: blank eVar30 value when pagename contains "ecomm" */
		loc=document.location.href;
		if(loc.indexOf('ecomm')&gt;-1&amp;&amp;s.eVar30){
			s.eVar30='';
			if(s.events.indexOf('event10')&gt;-1){
				var eventlist=s.split(s.events,',');
				for(i in eventlist){
					if(eventlist[i]=='event10')eventlist[i]='';
				}
				s.events='';
				for(i in eventlist){
					if(eventlist[i])s.events=s.apl(s.events,eventlist[i],',',2);
				}
			}
		}

		/* FIX: prefix ANAV caption */
		loc=document.location.href;
		loc=((loc&amp;&amp;loc.indexOf('?')&gt;-1)?loc.substring(0,loc.indexOf('?')):loc).toLowerCase();

		/* T&amp;T integration */
		s.tnt=s.trackTNT();

		/* iPerceptions integration */
		s.iPerceptionsURL =
				((window.location.protocol=='https:')?'https://si.cdn':'http://i')
			+ '.dell.com/images/global/omniture/ipge'
			+	((s_account.substring(s_account.length-3)=='dev')?'_sit':'')
			+	'.htm';
		s.GenesisExchange.setExchangePageURL('iPerceptions',s.iPerceptionsURL);

		/* navigation method - merchandising eVar */
		/* Added 07/09/10: add check for ~ck=gzilla or ref=gzilla to set eVar40 to 'banner' */
		if(s.inList('event6',s.events,',')){
			s.eVar40='site search';
		}else if(s.eVar30&amp;&amp;s.eVar31){
			s.eVar40='anav';
			if(s.p_gh())s.linkTrackVars=s.apl(s.linkTrackVars,'eVar40',',',2);
		}else if(s.getQueryParam('~ck').toLowerCase()=='mn'){
			s.eVar40='masthead';
		}else if(s.getQueryParam('~ck').toLowerCase()=='hbn'||s.getQueryParam('ref').toLowerCase()=='hbn'||s.getQueryParam('~ck').toLowerCase()=='bnn'||s.getQueryParam('ref').toLowerCase()=='bnn'||s.getQueryParam('ref').toLowerCase()=='gzilla'||s.getQueryParam('~ck').toLowerCase()=='gzilla'){
			s.eVar40='banner';
		}else if(s.pageName){
			if(s.pageName.indexOf('advisorweb')&gt;-1){
				s.eVar40='advisor';
			}
		}

		/* Added 07/09/10 release, changed on 10/01/10: substitute local domain for referrer if referrer starts with *nicos.co.jp */
		if(getDomainLevels(document.referrer,3)=='nicos.co.jp'){
			s.referrer=document.location.protocol+'//'+document.location.host.toString()+'/nicos-payment-processing';
		}
	}

	/* END dell.com Only Logic */

	/* Added 07/09/10: Get percent of page viewed */
	var ppv_c=s.getPercentPageViewed(s.pageName);	//Get values for prior page, pass this page's identifier
	if(ppv_c&amp;&amp;ppv_c.length&gt;=4){	//Were values for the prior page returned?
		var ppv_pn=(ppv_c.length&gt;0)?(ppv_c[0]):(''); //Extract last page's identifier
		var ppv_v=((ppv_c.length&gt;0)?(ppv_c[1]):('')) //Extract last page's total % viewed
			+((ppv_c.length&gt;2)?('|'+ppv_c[2]):(''));	//Extract last page's initial % viewed, separated by '|'
		if(ppv_pn&amp;&amp;ppv_v){	//Was pageName and percent % viewed values found?
			s.prop34=ppv_pn;	//Store percent page viewed values in the variable of your choice
			s.prop31=ppv_v;	//Store the page identifier in the variable of your choice
		}
	}

	/* Campaign tracking */
	if(!s.campaign)s.campaign=s.getQueryParam('cid'); //landing_page_cid
	s.campaign=s.getValOnce(s.campaign,'cid',0);
	if(!s.eVar1)s.eVar1=s.getQueryParam('lid'); //landing_page_lid
	s.eVar1=s.getValOnce(s.eVar1,'lid',0);
	if(!s.eVar2){var dgc=s.getQueryParam('dgc');s.eVar2=dgc} //landing_page_dgv
	s.eVar2=s.getValOnce(s.eVar2,'dgc',0);
	if(!s.eVar3)s.eVar3=s.getQueryParam('st'); //external_search_keyword
	s.eVar3=s.getValOnce(s.eVar3,'st',0);
	if(!s.eVar28)s.eVar28=s.getQueryParam('acd'); //affiliate code
	s.eVar28=s.getValOnce(s.eVar28,'acd',0);
	if(!s.eVar43)s.eVar43=s.getQueryParam('mid'); //aprimo message id
	s.eVar43=s.getValOnce(s.eVar43,'mid',0);
	if(!s.eVar44)s.eVar44=s.getQueryParam('rid'); //aprimo recipient id
	s.eVar44=s.getValOnce(s.eVar44,'rid',0);

	/* ODG visits */
	if(typeof(s.linkType)=='undefined'){
		s.odgValues='|af|ba|bf|cj|co|db|dc|ds|ec|em|ls|mb|ms|mt|rs|sm|ss|st|';
		var countrySegment='';
		if(s.prop2&amp;&amp;s.eVar32)countrySegment=s.prop2+'-'+s.eVar32;
		if(countrySegment){
			var d=new Date(),valueNotDeleted=true;
			if(s.c_r('e21')&amp;&amp;s.c_r('e21').indexOf(countrySegment)&gt;-1){
				var e21Array=s.split(s.c_r('e21'),'::');
				for (i in e21Array){
					if(e21Array[i].toString().indexOf(countrySegment)&gt;-1){
						var e21Array2=s.split(e21Array[i],':');
						if(d.getTime()&gt;e21Array2[1]){
							if(e21Array.length==1){
								d.setTime(d.getTime()-86400000); //one day ago
								s.c_w('e21','',d);
							}else{
								e21Array.splice(i,1);
								d.setTime(d.getTime()+30*86400000); //30 days from now
								s.c_w('e21',e21Array,d);
							}
							valueNotDeleted=false;
						}
						if(valueNotDeleted){
							var tempReferrer=s.d.referrer.substring(0,s.d.referrer.indexOf('?'));
							if((s.eVar2&amp;&amp;s.odgValues.indexOf(s.eVar2.toLowerCase()+'|')==-1&amp;&amp;s.eVar2!='ir')||(!s.getQueryParam('dgc')&amp;&amp;tempReferrer&amp;&amp;!s.isInternal(tempReferrer))){
								if(e21Array.length==1){
									d.setTime(d.getTime()-86400000); //24 hours ago
									s.c_w('e21','',d);
								}else{
									e21Array.splice(i,1);
									d.setTime(d.getTime()+30*86400000); //30 days from now
									s.c_w('e21',e21Array,d);
								}
							}else{
								s.events=s.apl(s.events,'event21',',',1);
							}
						}
					}
				}
			}else{
				if((s.eVar2&amp;&amp;s.odgValues.indexOf(s.eVar2.toLowerCase()+'|')&gt;-1)){
					s.events=s.apl(s.events,'event21',',',1);
					d.setTime(d.getTime()+30*86400000); //thirty days from now
					var e21Cookie=s.c_r('e21');
					e21Cookie=e21Cookie?e21Cookie+'::'+countrySegment+':'+d.getTime():countrySegment+':'+d.getTime();
					s.c_w('e21',e21Cookie,d);
				}
			}
		}
	}

	/* Internal Promos tracking */
	if(dgc&amp;&amp;dgc.toLowerCase()=='ir'){ //if a IR code is present, clear other campaign variables
		s.eVar29=s.getQueryParam('cid')+':'+s.getQueryParam('lid');
		s.eVar29=s.getValOnce(s.eVar29,'ir',0);
		s.campaign=s.eVar1=s.eVar2=s.eVar3=s.eVar28='';
	}

	if(s.tCall()){
		/* Calculate bounce rate for paid searches (for the Search Center POC) */
		s.SEMvar=s.getQueryParam('s_kwcid');
		s.SEMvar=s.getValOnce(s.SEMvar,'SEM_var',0);
		s.clickPast(s.SEMvar,'event46','event47','br_psearch');
		if(s.isInternal(document.location.href)){
            /** if the action depth is 1, then fire event44 and if it is 2 then fire event45 - (removed clickpast code) implemented by Jason Case on 25 April 2011 **/
        	if(s.ActionDepthTest){
                if(typeof s.gpv_pn != 'undefined' &amp;&amp; s.gpv_pn != s.pageName){
                    s.pdvalue=s.getActionDepth("s_depth");
                    if(s.pdvalue == 1) s.events=s.apl(s.events,'event44',',',2);
                    if(s.pdvalue == 2) s.events=s.apl(s.events,'event45',',',2);
                    s.ActionDepthTest=false;
                }

            }
		}
	}

	/* File downloads */
	s.downloadURL=s.downloadLinkHandler();
	if(s.downloadURL){
		s.prop33=s.downloadURL;
		s.prop33=s.prop33.indexOf('//')?s.prop33.substring(s.prop33.indexOf('//')+2):s.prop33;
		s.eVar23=s.prop33;
		s.prop32=s.pageName;
		s.events=s.apl(s.events,'event24',',',2);
		s.linkTrackVars=s.apl(s.linkTrackVars,'prop32',',',2);
		s.linkTrackVars=s.apl(s.linkTrackVars,'prop33',',',2);
		s.linkTrackVars=s.apl(s.linkTrackVars,'eVar23',',',2);
		s.linkTrackVars=s.apl(s.linkTrackVars,'events',',',2);
		s.linkTrackEvents=s.apl(s.linkTrackEvents,'event24',',',2);
	}

	s.manageVars('lowercaseVars','events',2); //force all variables to lowercase

	s.linkTrackVars=s.apl(s.linkTrackVars,'prop49',',',2);
	s.linkTrackVars=s.apl(s.linkTrackVars,'prop46',',',2);
	s.linkTrackVars=s.apl(s.linkTrackVars,'server',',',2); //Add 'server' to link track vars


    /**
     * Tracking Site Search for KW on 3rd party sites - implemented by Jason Case 25 Apr 2011
     */
     if(s.isInternal(document.location.href) &amp;&amp; !s.onDellCMS()){
        s.prop7 = s.getQueryParam('sk,k,q','::');
        if(s.prop7){
			s.prop7=s.prop7.toLowerCase();
			s.eVar36=s.prop7;
			var t_search=s.getValOnce(s.eVar36,'v36',0);
			if(t_search){
				s.events=s.apl(s.events,'event6',',',2);
				s.prop42=s.gpv_pn;
			}
		}
     }

     /** This is for reading the EQuoteID cookie into an eVar - implemented by Jason Case 25 Apr 2011 */
        s.eVar7 = s.c_r('EQuoteID');
     /** Read the link_number parameter for EPP info  - implemented by Jason Case 25 Apr 2011 **/
        s.eVar45 = s.getQueryParam('link_number');
     /** Capture the Release ID for a driver download - implemented by Jason Case 25 Apr 2011 **/
        s.prop24 = s.getQueryParam('releaseid');
     /** Capture the Doc ID - implemented by Jason Case 25 Apr 2011 **/
        s.prop51 = s.getQueryParam('docid');
     /** Custom Pageview Metric - implemented by Jason Case 25 Apr 2011 **/
        s.events = s.events=s.apl(s.events,'event37',',',2);
     /** View by Usage tabs and sub tabs - implemented by Jason Case 27 Apr 2011 **/
        s.eVar14 = s.getQueryParam('avt,avtsub');



    /** Grab s_vi value, strip prefix and suffix, and store in prop47 **/
	s.prop47='D=s_vi';


}
s_dell.doPlugins=s_dell_doPlugins

/* ************************ PLUGINS SECTION *********************** */
/* You may insert any plugins you wish to use here. */

/*
 * Plugin: getQueryParam 2.3
 */
s_dell.getQueryParam=new Function("p","d","u",""
+"var s=this,v='',i,t;d=d?d:'';u=u?u:(s.pageURL?s.pageURL:s.wd.locati"
+"on);if(u=='f')u=s.gtfs().location;while(p){i=p.indexOf(',');i=i&lt;0?p"
+".length:i;t=s.p_gpv(p.substring(0,i),u+'');if(t){t=t.indexOf('#')&gt;-"
+"1?t.substring(0,t.indexOf('#')):t;}if(t)v+=v?d+t:t;p=p.substring(i="
+"=p.length?i:i+1)}return v");
s_dell.p_gpv=new Function("k","u",""
+"var s=this,v='',i=u.indexOf('?'),q;if(k&amp;&amp;i&gt;-1){q=u.substring(i+1);v"
+"=s.pt(q,'&amp;','p_gvf',k)}return v");
s_dell.p_gvf=new Function("t","k",""
+"if(t){var s=this,i=t.indexOf('='),p=i&lt;0?t:t.substring(0,i),v=i&lt;0?'T"
+"rue':t.substring(i+1);if(p.toLowerCase()==k.toLowerCase())return s."
+"epa(v)}return ''");

/*
 * Plugin: getValOnce 0.2 - get a value once per session or number of days
 */
s_dell.getValOnce=new Function("v","c","e",""
+"var s=this,k=s.c_r(c),a=new Date;e=e?e:0;if(v){a.setTime(a.getTime("
+")+e*86400000);s.c_w(c,v,e?a:0);}return v==k?'':v");

/*
 * Plugin: getPreviousValue_v1.0 - return previous value of designated
 *  variable (requires split utility)
 */
s_dell.getPreviousValue=new Function("v","c","el",""
+"var s=this,t=new Date,i,j,r='';t.setTime(t.getTime()+1800000);if(el"
+"){if(s.events){i=s.split(el,',');j=s.split(s.events,',');for(x in i"
+"){for(y in j){if(i[x]==j[y]){if(s.c_r(c)) r=s.c_r(c);v?s.c_w(c,v,t)"
+":s.c_w(c,'no value',t);return r}}}}}else{if(s.c_r(c)) r=s.c_r(c);v?"
+"s.c_w(c,v,t):s.c_w(c,'no value',t);return r}");

/*
 * DynamicObjectIDs v1.4: Setup Dynamic Object IDs based on URL
 */
s_dell.setupDynamicObjectIDs=new Function(""
+"var s=this;if(!s.doi){s.doi=1;if(s.apv&gt;3&amp;&amp;(!s.isie||!s.ismac||s.apv"
+"&gt;=5)){if(s.wd.attachEvent)s.wd.attachEvent('onload',s.setOIDs);else"
+" if(s.wd.addEventListener)s.wd.addEventListener('load',s.setOIDs,fa"
+"lse);else{s.doiol=s.wd.onload;s.wd.onload=s.setOIDs}}s.wd.s_semapho"
+"re=1}");
s_dell.setOIDs=new Function("e",""
+"var s=s_c_il["+s_dell._in+"],b=s.eh(s.wd,'onload'),o='onclick',x,l,u,c,i"
+",a=new Array;if(s.doiol){if(b)s[b]=s.wd[b];s.doiol(e)}if(s.d.links)"
+"{for(i=0;i&lt;s.d.links.length;i++){l=s.d.links[i];c=l[o]?''+l[o]:'';b"
+"=s.eh(l,o);z=l[b]?''+l[b]:'';u=s.getObjectID(l);if(u&amp;&amp;c.indexOf('s_"
+"objectID')&lt;0&amp;&amp;z.indexOf('s_objectID')&lt;0){u=s.repl(u,'\"','');u=s.re"
+"pl(u,'\\n','').substring(0,97);l.s_oc=l[o];a[u]=a[u]?a[u]+1:1;x='';"
+"if(c.indexOf('.t(')&gt;=0||c.indexOf('.tl(')&gt;=0||c.indexOf('s_gs(')&gt;=0"
+")x='var x=\".tl(\";';x+='s_objectID=\"'+u+'_'+a[u]+'\";return this."
+"s_oc?this.s_oc(e):true';if(s.isns&amp;&amp;s.apv&gt;=5)l.setAttribute(o,x);l[o"
+"]=new Function('e',x)}}}s.wd.s_semaphore=0;return true");

/*
 * Plugin: downloadLinkHandler 0.5 - identify and report download links
 */
s_dell.downloadLinkHandler=new Function("p",""
+"var s=this,h=s.p_gh(),n='linkDownloadFileTypes',i,t;if(!h||(s.linkT"
+"ype&amp;&amp;(h||s.linkName)))return '';i=h.indexOf('?');t=s[n];s[n]=p?p:t;"
+"if(s.lt(h)=='d')s.linkType='d';else h='';s[n]=t;return h;");

/*
 * Utility Function: p_gh
 */
s_dell.p_gh=new Function(""
+"var s=this;if(!s.eo&amp;&amp;!s.lnk)return '';var o=s.eo?s.eo:s.lnk,y=s.ot("
+"o),n=s.oid(o),x=o.s_oidt;if(s.eo&amp;&amp;o==s.eo){while(o&amp;&amp;!n&amp;&amp;y!='BODY'){"
+"o=o.parentElement?o.parentElement:o.parentNode;if(!o)return '';y=s."
+"ot(o);n=s.oid(o);x=o.s_oidt}}return o.href?o.href:'';");

/*
 * Utility clearVars v0.1 - clear variable values (requires split 1.5)
 */
s_dell.clearVars=new Function("l","f",""
+"var s=this,vl,la,vla;l=l?l:'';f=f?f:'';vl='pageName,purchaseID,chan"
+"nel,server,pageType,campaign,state,zip,events,products';for(var n=1"
+";n&lt;51;n++)vl+=',prop'+n+',eVar'+n+',hier'+n;if(l&amp;&amp;(f==1||f==2)){if("
+"f==1){vl=l}if(f==2){la=s.split(l,',');vla=s.split(vl,',');vl='';for"
+"(x in la){for(y in vla){if(la[x]==vla[y]){vla[y]=''}}}for(y in vla)"
+"{vl+=vla[y]?','+vla[y]:'';}}s.pt(vl,',','p_clr',0);return true}else"
+" if(l==''&amp;&amp;f==''){s.pt(vl,',','p_clr',0);return true}else{return fa"
+"lse}");
s_dell.p_clr=new Function("t","var s=this;s[t]=''");

/*
 * Plugin Utility: apl v1.1
 */
s_dell.apl=new Function("l","v","d","u",""
+"var s=this,m=0;if(!l)l='';if(u){var i,n,a=s.split(l,d);for(i=0;i&lt;a."
+"length;i++){n=a[i];m=m||(u==1?(n==v):(n.toLowerCase()==v.toLowerCas"
+"e()));}}if(!m)l=l?l+d+v:v;return l");

/*
 * Utility: inList v1.0 - find out if a value is in a list
 */
s_dell.inList=new Function("v","l","d",""
+"var s=this,ar=Array(),i=0,d=(d)?d:',';if(typeof(l)=='string'){if(s."
+"split)ar=s.split(l,d);else if(l.split)ar=l.split(d);else return-1}e"
+"lse ar=l;while(i&lt;ar.length){if(v==ar[i])return true;i++}return fals"
+"e;");

/*
 * Plugin Utility: split v1.5 (JS 1.0 compatible)
 */
s_dell.split=new Function("l","d",""
+"var i,x=0,a=new Array;while(l){i=l.indexOf(d);i=i&gt;-1?i:l.length;a[x"
+"++]=l.substring(0,i);l=l.substring(i+d.length);}return a");

/*
 * Plugin Utility: replace v1.0
 */
s_dell.repl=new Function("x","o","n",""
+"var i=x.indexOf(o),l=n.length;while(x&amp;&amp;i&gt;=0){x=x.substring(0,i)+n+x."
+"substring(i+o.length);i=x.indexOf(o,i+l)}return x");

/*
 * Utility manageVars v0.2 - clear variable values (requires split 1.5)
 */
s_dell.manageVars=new Function("c","l","f",""
+"var s=this,vl,la,vla;l=l?l:'';f=f?f:1 ;if(!s[c])return false;vl='pa"
+"geName,purchaseID,channel,server,pageType,campaign,state,zip,events"
+",products,transactionID';for(var n=1;n&lt;51;n++){vl+=',prop'+n+',eVar"
+"'+n+',hier'+n;}if(l&amp;&amp;(f==1||f==2)){if(f==1){vl=l;}if(f==2){la=s.spl"
+"it(l,',');vla=s.split(vl,',');vl='';for(x in la){for(y in vla){if(l"
+"a[x]==vla[y]){vla[y]='';}}}for(y in vla){vl+=vla[y]?','+vla[y]:'';}"
+"}s.pt(vl,',',c,0);return true;}else if(l==''&amp;&amp;f==1){s.pt(vl,',',c,0"
+");return true;}else{return false;}");
s_dell.clearVars=new Function("t","var s=this;s[t]='';");
s_dell.lowercaseVars=new Function("t",""
+"var s=this;if(s[t]){s[t]=s[t].toLowerCase();}");

/*
 * Custom Dell Plugin: parseCookie for desired params, format as query string
 * (requires s.split, s.apl)
 */
s_dell.parseCookie=new Function("c","pl","d",""
+"var s=this,pla,ca,o='',j,l;c=s.c_r(c);if(c){pla=s.split(pl,d);ca=s.s"
+"plit(c,'&amp;');for(x in pla){for(y in ca){j=pla[x]+'=';l=''+ca[y];l=l.t"
+"oLowerCase();l=l.indexOf(j.toLowerCase());if(l&gt;-1)o=s.apl(o,ca[y],'&amp;"
+"',0)}}if(o)o='?'+o;}return o");

/*
 * Custom Dell Plugin: dedupVal
 */
s_dell.dedupVal=new Function("c","v",""
+"var s=this,r;if(s.c_r(c)){r=s.c_r(c);if(v==r)return '';else s.c_w(c,"
+"v)}else{s.c_w(c,v)}return v");

/*
 * TNT Integration Plugin v1.0
 */
s_dell.trackTNT =new Function("v","p","b",""
+"var s=this,n='s_tnt',p=p?p:n,v=v?v:n,r='',pm=false,b=b?b:true;if(s."
+"getQueryParam){pm=s.getQueryParam(p);}if(pm){r+=(pm+',');}if(s.wd[v"
+"]!=undefined){r+=s.wd[v];}if(b){s.wd[v]='';}return r;");

/*
 * GenesisExchange v0.3.1 (compact)
 */
s_dell.createGEObject=new Function("s",""
+"var _g=new Object;_g.s=s;_g.p=new Object;_g.setPartnerEventHandler="
+"function(pId,eh){if(!this.p[pId]){this.p[pId]=new Object;}this.p[pI"
+"d].__eh=eh;};_g.firePartnerEvent=function(pId,eId,ePm){this.p[pId]."
+"__eh(eId,ePm);};_g.setExchangePageURL=function(pId,url){if(!this.p["
+"pId]){this.p[pId]=new Object;}this.p[pId].__ep=url;};_g.getPageData"
+"=function(pId){var q='';if(this.p[pId]&amp;&amp;this.p[pId].__ep){q+='ge_pI"
+"d='+this._euc(pId)+'&amp;ge_url='+this._euc(this.p[pId].__ep)+'&amp;pageURL"
+"='+this._euc(document.location.href);}var v='pageName,server,channe"
+"l,pageType,products,events,campaign,purchaseID,hier1,hier2,hier3,hi"
+"er4,hier5';for(var i=1;i&lt;=50;i++){v+=',prop'+i+',eVar'+i;}var a=thi"
+"s._split(v,',');for(var i=0;i&lt;a.length;i++){if(this.s[a[i]]){q+=(q?"
+"'&amp;':'')+a[i]+'='+this._euc(this.s[a[i]]);}}return q;};_g.getObjectF"
+"romQueryString=function(qsParam){var v=this._getQParam(qsParam);var"
+" r=new Object;if(v){v=this._duc(v);l=this._split(v,'&amp;');for(i=0;i&lt;l"
+".length;i++){kv=this._split(l[i],'=');r[kv[0]]=this._duc(kv[1]);}}r"
+"eturn r;};_g.productsInsert=function(p,e,v){var i=0,j=0,r='',pd=thi"
+"s._split(p,',');for(i=0;i&lt;pd.length;i++){if(i&gt;0){r+=',';}var el=thi"
+"s._split(pd[i],';');for(j=0;j&lt;6;j++){if(j&lt;4){r+=(el.length&gt;j?el[j]:"
+"'')+';';}else if(j==4){r+=(el[j]?el[j]+'|':'')+e+';';}else if(j==5)"
+"{r+=(el[j]?el[j]+'|':'')+v;}}}return r;};_g._getQParam=function(k,q"
+"){var m=this,l,i,kv;if(q==undefined||!q){q=window.location.href;}if"
+"(q){i=q.indexOf('?');if(i&gt;=0){q=q.substring(i+1);}l=this._split(q,'"
+"&amp;');for(i=0;i&lt;l.length;i++){kv=this._split(l[i],'=');if(kv[0]==k){r"
+"eturn kv[1];}}}return '';};_g._euc=function(str){return typeof enco"
+"deURIComponent=='function'?encodeURIComponent(str):escape(str);};_g"
+"._duc=function(str){return typeof decodeURIComponent=='function'?de"
+"codeURIComponent(str):unescape(str);};_g._split=function(l,d){var i"
+",x=0,a=new Array;while(l){i=l.indexOf(d);i=i&gt;-1?i:l.length;a[x++]=l"
+".substring(0,i);l=l.substring(i+d.length);}return a;};return _g;");
s_dell.GenesisExchange=s_dell.createGEObject(s_dell);

/*
 * Function - read combined cookies v 0.3
 */
if(!s_dell.__ccucr){s_dell.c_rr=s_dell.c_r;s_dell.__ccucr=true;
s_dell.c_r=new Function("k",""
+"var s=this,d=new Date,v=s.c_rr(k),c=s.c_rr('s_pers'),i,m,e;if(v)ret"
+"urn v;k=s.ape(k);i=c.indexOf(' '+k+'=');c=i&lt;0?s.c_rr('s_sess'):c;i="
+"c.indexOf(' '+k+'=');m=i&lt;0?i:c.indexOf('|',i);e=i&lt;0?i:c.indexOf(';'"
+",i);m=m&gt;0?m:e;v=i&lt;0?'':s.epa(c.substring(i+2+k.length,m&lt;0?c.length:"
+"m));if(m&gt;0&amp;&amp;m!=e)if(parseInt(c.substring(m+1,e&lt;0?c.length:e))&lt;d.get"
+"Time()){d.setTime(d.getTime()-60000);s.c_w(s.epa(k),'',d);v='';}ret"
+"urn v;");}

/*
 * Function - write combined cookies v 0.3
 */
if(!s_dell.__ccucw){s_dell.c_wr=s_dell.c_w;s_dell.__ccucw=true;
s_dell.c_w=new Function("k","v","e",""
+"this.new2 = true;"
+"var s=this,d=new Date,ht=0,pn='s_pers',sn='s_sess',pc=0,sc=0,pv,sv,"
+"c,i,t;d.setTime(d.getTime()-60000);if(s.c_rr(k)) s.c_wr(k,'',d);k=s"
+".ape(k);pv=s.c_rr(pn);i=pv.indexOf(' '+k+'=');if(i&gt;-1){pv=pv.substr"
+"ing(0,i)+pv.substring(pv.indexOf(';',i)+1);pc=1;}sv=s.c_rr(sn);i=sv"
+".indexOf(' '+k+'=');if(i&gt;-1){sv=sv.substring(0,i)+sv.substring(sv.i"
+"ndexOf(';',i)+1);sc=1;}d=new Date;if(e){if(e.getTime()&gt;d.getTime())"
+"{pv+=' '+k+'='+s.ape(v)+'|'+e.getTime()+';';pc=1;}}else{sv+=' '+k+'"
+"='+s.ape(v)+';';sc=1;}if(sc) s.c_wr(sn,sv,0);if(pc){t=pv;while(t&amp;&amp;t"
+".indexOf(';')!=-1){var t1=parseInt(t.substring(t.indexOf('|')+1,t.i"
+"ndexOf(';')));t=t.substring(t.indexOf(';')+1);ht=ht&lt;t1?t1:ht;}d.set"
+"Time(ht);s.c_wr(pn,pv,d);}return v==s.c_r(s.epa(k));");}

/*
 * Plugin getPercentPageViewed v1.4 - determine percent of page viewed
 */
s_dell.handlePPVevents=new Function (""
+"var s=s_c_il["+s_dell._in+"];"
+"if(!s.getPPVid)return;var dh=Math.max(Math.max(s.d.body.scrollHeigh"
+"t,s.d.documentElement.scrollHeight),Math.max(s.d.body.offsetHeight,"
+"s.d.documentElement.offsetHeight),Math.max(s.d.body.clientHeight,s."
+"d.documentElement.clientHeight));var vph=s.wd.innerHeight||(s.d.doc"
+"umentElement.clientHeight||s.d.body.clientHeight),st=s.wd.pageYOffs"
+"et||(s.wd.document.documentElement.scrollTop||s.wd.document.body.sc"
+"rollTop),vh=st+vph,pv=Math.min(Math.round(vh/dh*100),100),c=s.c_r('"
+"s_ppv'),a=(c.indexOf(',')&gt;-1)?c.split(',',4):[],id=(a.length&gt;0)?(a["
+"0]):escape(s.getPPVid),cv=(a.length&gt;1)?parseInt(a[1]):(0),p0=(a.len"
+"gth&gt;2)?parseInt(a[2]):(pv),cy=(a.length&gt;3)?parseInt(a[3]):(0),cn=(p"
+"v&gt;0)?(id+','+((pv&gt;cv)?pv:cv)+','+p0+','+((vh&gt;cy)?vh:cy)):('');s.c_w"
+"('s_ppv',cn);");

s_dell.getPercentPageViewed=new Function("pgid",""
+"var s=this,pgid=(arguments.length&gt;0)?(arguments[0]):('-'),ist=(!s.ge"
+"tPPVid)?(true):(false);if(typeof(s.linkType)!='undefined'&amp;&amp;s.linkTy"
+"pe!='e')return'';var v=s.c_r('s_ppv'),a=(v.indexOf(',')&gt;-1)?v.split"
+"(',',4):[];if(a.length&lt;4){for(var i=3;i&gt;0;i--)a[i]=(i&lt;a.length)?(a["
+"i-1]):('');a[0]='';}a[0]=unescape(a[0]);s.getPPVpid=pgid;s.c_w('s_p"
+"pv',escape(pgid));if(ist){s.getPPVid=(pgid)?(pgid):(s.pageName?s.pa"
+"geName:document.location.href);s.c_w('s_ppv',escape(s.getPPVid));if"
+"(s.wd.addEventListener){s.wd.addEventListener('load',s.handlePPVeve"
+"nts,false);s.wd.addEventListener('scroll',s.handlePPVevents,false);"
+"s.wd.addEventListener('resize',s.handlePPVevents,false);}else if(s."
+"wd.attachEvent){s.wd.attachEvent('onload',s.handlePPVevents);s.wd.a"
+"ttachEvent('onscroll',s.handlePPVevents);s.wd.attachEvent('onresize"
+"',s.handlePPVevents);}}return(pgid!='-')?(a):(a[1]);");

/*
 * Plugin: clickPast v1.0
 */
s_dell.clickPast=new Function("scp","ct_ev","cp_ev","cpc",""
+"var s=this,scp,ct_ev,cp_ev,cpc,ev,tct;if(s.p_fo(ct_ev)==1){if(!cpc)"
+"{cpc='s_cpc';}ev=s.events?s.events+',':'';if(scp){s.events=ev+ct_ev"
+";s.c_w(cpc,1,0);}else{if(s.c_r(cpc)&gt;=1){s.events=ev+cp_ev;s.c_w(cpc"
+",0,0);}}}");
s_dell.p_fo=new Function("n",""
+"var s=this;if(!s.__fo){s.__fo=new Object;}if(!s.__fo[n]){s.__fo[n]="
+"new Object;return 1;}else {return 0;}");

/*
 * Function: parseUri v1.0 - Parse URI components
 */
parseUri=new Function("u",""
+"var l={strictMode:false,key:['source','protocol','authority','userI"
+"nfo','user','password','host','port','relative','path','directory',"
+"'file','query','anchor'],U:{name:'queryKey',c:/(?:^|\&amp;)([^\&amp;=]*)=?("
+"[^\&amp;]*)/g},c:{strict:/^(?:([^:\\/?#]+):)?(?:\\/\\/((?:(([^:@]*)(?::"
+"([^:@]*))?)?@)?([^:\\/?#]*)(?::(\\d*))?))?((((?:[^?#\\/]*\\/)*)([^?"
+"#]*))(?:\\?([^#]*))?(?:#(.*))?)/,loose:/^(?:(?![^:@]+:[^:@\\/]*@)(["
+"^:\\/?#.]+):)?(?:\\/\\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\\/?#]"
+"*)(?::(\\d*))?)(((\\/(?:[^?#](?![^?#\\/]*\\.[^?#\\/.]+(?:[?#]|$)))*"
+"\\/?)?([^?#\\/]*))(?:\\?([^#]*))?(?:#(.*))?)/}},t=l.c[l.strictMode?"
+"'strict':'loose'].exec(u),p={},b=14;while(b--)p[l.key[b]]=t[b]||'';"
+"p[l.U.name]={};p[l.key[12]].replace(l.U.c,function($0,$1,$2){if($1)"
+"{p[l.U.name][$1]=$2}});return p");

/*
 * Function: Vanilla cookies v1.0
 */
sC=new Function("b","s",""
+"document.cookie=b+'='+escape(s)+'; '+' expires='+(arguments.length&gt;"
+"=3?arguments[2].toGMTString():'')+'; path=/; domain=.'+getDomainLev"
+"els()+';'");
gC=new Function("b",""
+"var q=document.cookie,v=b+'=',m=q.indexOf(v);if(m!=0)m=q.indexOf(';"
+" '+v);if(m==-1)return '';v=q.substring(m);v=v.substring(v.indexOf('"
+"=')+1);m=v.indexOf(';');if(m!=-1)v=v.substring(0,m);return unescape"
+"(v)");

/*
 * Function: getDomainLevels([domain][,levels]) v1.0
 * (Requires parseUri)
 */
getDomainLevels=new Function(""
+"var z;if(arguments.length&gt;0){z=arguments[0]}else{if(typeof document"
+".location.href=='undefined')return'';z=document.location.href}var r"
+"=parseUri(z).host.toLowerCase();var c=r.split('.'.toString());if(ar"
+"guments.length&gt;=2){var w=arguments[1];for(var b=1,i='';b&lt;=w;b++){if"
+"(c.length&gt;=b){i=c[c.length-b]+(b&gt;1?'.':'')+i}}}else{var i=c.length&gt;"
+"=1?c[c.length-1]:'';var n=c.length&gt;=2?c[c.length-2]:'';var w=i.leng"
+"th==2&amp;&amp;(n.length==2||n=='com')?3:2;for(var b=2;b&lt;=w;b++){if(c.lengt"
+"h&gt;=b){i=c[c.length-b]+(b&gt;1?'.':'')+i}}}return i");
/*
 * Plugin: getActionDepth v1.0 - Returns the current
 * page number of the visit
 */
s_dell.getActionDepth=new Function("c",""
+ "var s=this,v=1,t=new Date;t.setTime(t.getTime()+1800000);"
+ "if(!s.c_r(c)){v=1}if(s.c_r(c)){v=s.c_r(c);v++}"
+ "if(!s.c_w(c,v,t)){s.c_w(c,v,0)}return v;");
/*
 * Plugin: tCall v1.0 - Is a t() call in progress?
 */
s_dell.tCall=new Function("",
"var t=this.linkType;return typeof(t)=='undefined'||typeof(t)==''");

/*
 * Plugin: isInternal(url) v1.0 - Url internal per linkInternalFilters?
 * (Requires matchList)
 */
s_dell.isInternal=new Function("v",""
+"return matchList(((!v)?document.location.href:v.toString().toLowerC"
+"ase()),s_dell.linkInternalFilters)");

/*
 * Plugin: hostedLocally(url) v1.0 - Url local per localDoms?
 * (Requires matchList)
 */
s_dell.hostedLocally=new Function("v",""
+"return matchList(((!v)?document.location.href:v.toString().toLowerC"
+"ase()),s_dell.localDoms)");

/*
 * Plugin: matchList v1.0 - Does a url match a regex pattern in a list?
 * (Requires parseUri)
 */
matchList=new Function("v","l",""
+"v=v.toString().toLowerCase();if(typeof(v)!=\'string\'||typeof(l)!="
+"\'string\')return 0;var m=parseUri(v).protocol,h=parseUri(v).host;i"
+"f(m.indexOf(\'http\')!=0\&amp;\&amp;m.indexOf(\'ftp\')!=0)return 1;return h"
+".match(\'(\'+l.toLowerCase().replace(\x2F\\.(?![*+?])\x2Fgi,\'\\\\."
+"\').replace(\x2F,(?![*+?])\x2Fgi,\'|\')+\')\')?2:0");

/* ******************** DELL CUSTOM PLUGINS ********************** */

/*
 * determineCMS() - Determine CMS (Storm, NextGen, or OLR)
 */
function s_dell_determineCMS(){
	var s=s_dell;
	if(!s.CMS){
		s.CMS='unknown';
		var gen=s.getHTMLtag('meta','generator').toLowerCase();
		if(gen.indexOf(' ')&gt;0)gen=gen.substring(0,gen.indexOf(' '));
		if(gen.indexOf('ng')==0)s.CMS='nextgen';
		if(gen.indexOf('build:')==0||gen.indexOf('mshtml')==0)s.CMS='olr';
		if(gen.indexOf('storm')==0)s.CMS='storm';
		if(gen.indexOf('telligent')==0)s.CMS='telligent';
		if(s.CMS=='unknown'&amp;&amp;s.getHTMLtag('meta','waapplicationname'))s.CMS='olr';
	}
	return s.CMS;
}
s_dell.determineCMS=s_dell_determineCMS;

/*
 * onDellCMS() - Determine if on a page created by a Dell CMS
 */
function s_dell_onDellCMS(){
	return (s_dell.determineCMS()=='storm')||(s_dell.determineCMS()=='nextgen')||(s_dell.determineCMS()=='olr')||(s_dell.determineCMS()=='telligent');
}
s_dell.onDellCMS=s_dell_onDellCMS;

/*
 * processLWP() - "Consolidated LWP variable processing"
 * Set LWP variables by looking in URL, the LWP cookie, and referrer for third-party sites
 */
function s_dell_processLWP(){
	var s=s_dell;
	if(document.location.search)s.setLWPvarsFromStr(document.location.search);
	s.setLWPvarsFromMetaTags();
	if(!s.onDellCMS()){
		if(s.prop49)s.setLWPvarsFromStr(s.prop49);
		if(s.hostedLocally(document.referrer))s.setLWPvarsFromStr(parseUri(document.referrer).query);
	}
	var lwpc=s.readLWPcookie();
	if(lwpc){
		s.setLWPvarsFromStr(lwpc);
	}else{
		s.setLWPvarsFromStr(s.readProp49cookie());
	}
	s.setCCfromURL();
	var lv=s.getLWPvariables();
	if(lv){
		s.prop49='?'+lv;
		s.writeProp49cookie(lv);
	}
}
s_dell.processLWP=s_dell_processLWP;

/*
 * setLWPvarsFromMetaTags() - Try to assign LWP variables from META TAGs
 */
function s_dell_setLWPvarsFromMetaTags(){
	var s=s_dell;
	//prop2: 2 letter country code
	if(!s.prop2)s.prop2=s.getHTMLtag('meta','country');
	if(!s.prop2)s.prop2=s.getHTMLtag('meta','documentcountrycode');
	//prop3: 2 letter language code
	if(!s.prop3)s.prop3=s.getHTMLtag('meta','language');
	//evar32: segment
	if(!s.eVar32)s.eVar32=s.getHTMLtag('meta','segment');
	//evar6: customer set
	if(!s.prop6)s.prop6=s.getHTMLtag('meta','customerset');
}
s_dell.setLWPvarsFromMetaTags=s_dell_setLWPvarsFromMetaTags;

/*
 * getHTMLtag(name) - Get the specified META TAG's value
 */
function s_dell_getHTMLtag(tg,nm){
	var k=(arguments.length&gt;2)?arguments[2]:'NAME',
		v=(arguments.length&gt;3)?arguments[3]:'CONTENT',
		metas=document.getElementsByTagName?document.getElementsByTagName(tg):'';
	for(var i=metas.length-1;i&gt;=0;i--){
		var n=metas[i].getAttribute(k);
		n=n?n.toLowerCase():'';
		if(n==nm)return metas[i].getAttribute(v).toLowerCase();
	}
	return '';
}
s_dell.getHTMLtag=s_dell_getHTMLtag;

/*
 * setLWPvarsFromStr(str) - Try to assign LWP variables from formatted string
 */
function s_dell_setLWPvarsFromStr(v){
	var s=s_dell;
	if(!v)return;
	v=v.toString().toLowerCase();
	if(v.substring(0,1)=='&amp;')v='?'+v.substring(1);
	if(v.substring(0,1)!='?')v='?'+v;
	//prop2: 2 letter country code
	if(!s.prop2)s.prop2=s.getQueryParam('shopper_country','',v);
	if(!s.prop2)s.prop2=s.getQueryParam('ctry_id','',v);
	if(!s.prop2)s.prop2=s.getQueryParam('c','',v);
	//prop3: 2 letter language code
	if(!s.prop3)s.prop3=s.getQueryParam('l','',v);
	//evar32: segment
	if(!s.eVar32)s.eVar32=s.getQueryParam('s','',v);
	if(!s.eVar32)s.eVar32=s.getQueryParam('shopper_segment','',v);
	//prop6: customer set
	if(!s.prop6)s.prop6=s.getQueryParam('customer_id','',v);
	if(!s.prop6)s.prop6=s.getQueryParam('cs','',v);
	//prop17: service tag
	if(!s.prop17)s.prop17=s.getQueryParam('svctag','',v);
	if(!s.prop17)s.prop17=s.getQueryParam('servicetag','',v);
	if(!s.prop17)s.prop17=s.getQueryParam('st55','',v);
	if(!s.prop17)s.prop17=s.getQueryParam('tag','',v);
	//prop18: systemid
	if(!s.prop18)s.prop18=s.getQueryParam('systemid','',v);
}
s_dell.setLWPvarsFromStr=s_dell_setLWPvarsFromStr;

/*
 * getLWPvariables() - Return LWP variables as formatted string
 */
function s_dell_getLWPvariables(){
	var v='',s=this;
	if(s.prop2)v+='&amp;c='+s.prop2;
	if(s.prop3)v+='&amp;l='+s.prop3;
	if(s.eVar32)v+='&amp;s='+s.eVar32;
	if(s.prop6)v+='&amp;cs='+s.prop6;
	if(s.prop17)v+='&amp;servicetag='+s.prop17;
	if(s.prop18)v+='&amp;systemid='+s.prop18;
	if(v)return v.substring(1);
	return '';
}
s_dell.getLWPvariables=s_dell_getLWPvariables;

/*
 * setCCfromURL() - Added 07/09/10: Try to get country code from domain or path
 */
s_dell.cCodes=[
'ae','ag','ai','al','am','an','ao','ar','at','au',
'aw','az','ba','bb','bd','be','bg','bh','bm','bo',
'br','bs','bw','by','bz','ca','ch','cl','cm','cn',
'co','cr','cy','cz','de','dk','dm','do','dz','ec',
'ed','ee','eg','es','et','eu','fi','fj','fr','gb',
'gd','ge','gh','gr','gt','gy','hk','hn','hr','ht',
'hu','id','ie','il','in','ir','is','it','jm','jo',
'jp','ke','kn','kr','kw','ky','kz','lb','lc','li',
'lk','lt','lu','lv','ma','md','me','mk','ml','mq',
'ms','mt','mu','mx','my','mz','na','ng','ni','nl',
'no','nz','om','pa','pe','ph','pk','pl','pr','pt',
'py','qa','ro','rs','ru','ru','rw','sa','se','sg',
'si','sk','sn','sr','sv','sy','tc','td','th','tm',
'tn','tr','tt','tw','tz','ua','ug','uk','us','uy',
'uz','vc','ve','vg','vi','vn','ye','yu','za','zm',
'zw'];
function s_dell_setCCfromURL(){
	var s=s_dell;
	if(s.prop2)return;
	if(arguments.length&gt;0){
		var r=arguments[0];
	}else{
		if(typeof(document.location.href)=='undefined')return;
		var r=document.location.href;
	}
	var h=parseUri(r).host.split('.');
	var d=(h.length&gt;=3)?h[h.length-1]:'';
	if(d.length==2&amp;&amp;s.inList(d,s.cCodes)){s.prop2=d;return;}
	for(var i=1;i&lt;h.length;i++){
		if(h[i]=='dell'){
			d=h[i-1];
			if(s.inList(d,s.cCodes)){s.prop2=d;return;}
		}
	}
	var p=parseUri(r).directory;
	if(p.length&lt;4||p[3]!='/')return;
	var p1=p.substring(1,3);
	if(s.inList(p1,s.cCodes)){s.prop2=p1;return;}
}
s_dell.setCCfromURL=s_dell_setCCfromURL;

/*
 * readLWPcookie() - Get value of the Dell LWP cookie
 */
function s_dell_readLWPcookie(){
	return gC('lwp');
}
s_dell.readLWPcookie=s_dell_readLWPcookie;

/*
 * readProp49cookie() - Get value of the SiteCatalyst prop49 cookie
 */
function s_dell_readProp49cookie(){
	return gC('s_c49');
}
s_dell.readProp49cookie=s_dell_readProp49cookie;

/*
 * writeProp49cookie() - Write value of the SiteCatalyst prop49 cookie
 */
function s_dell_writeProp49cookie(){
	var v=s_dell.getLWPvariables();
	if(v)sC('s_c49',v);
}
s_dell.writeProp49cookie=s_dell_writeProp49cookie;

/*
 * getPNfromURL() - Construct pageName from URL host and path, removing index page name, page extension, anchor tag
 */
function s_dell_getPNfromURL(){
	var s=s_dell,p=document.location.protocol;
	if(p.indexOf('http')==0){
		var pn=parseUri(document.location.href).host.replace(/^www[0-9]*\./i,'') +parseUri(document.location.href).path.replace(/\.(aspx?|s?html?|cgi|php[0-9]|wml)/i,'').replace(/\/(default|home|index|welcome)/i,'');
		if(pn.indexOf('/')==-1)pn=pn+'/';
		sku=s.getQueryParam('sku','',document.location.search);
		if(!sku)sku=s.getQueryParam('channel-product-id','',document.location.search);
		if(sku)pn+='[sku='+sku+']';
	}else{
		pn=p;
	}
	return pn.toLowerCase();
}
s_dell.getPNfromURL=s_dell_getPNfromURL;

/*
 * getObjectID(o)
 */
function s_dell_getObjectID(o){
	return o.href;
}
s_dell.getObjectID=s_dell_getObjectID;

/*
* SiteCatalyst Ad Track
*/

function adTrackClickThroughs() {
    var s = s_dell;
    var q = (s.determineCMS() == 'nextgen') ? '.omnitureADTrack[omnitureadid]' : '.omnitureADTrack[@omnitureadid]'; //Added 07/09/10: removed "@" for nextgen pages only
    try {
        jQuery(q).each(function () {
            jQuery(this).click(function () {
                try {
                    s.eVar6 = jQuery(this).attr('omnitureadid');
                    s.prop28 = '';
                    s.linkTrackVars = s.apl(s.linkTrackVars, 'eVar6', ',', 2);
                    s.tl(this, 'o', 'ADTrack');
                    s.eVar6 = '';
                }
                catch (e) { }
            });
        });
    }
    catch (e) { }
}

function adTrackImpressions() {
    var s = s_dell;
    var q = (s.determineCMS() == 'nextgen') ? '.omnitureADTrack[omnitureadid]' : '.omnitureADTrack[@omnitureadid]'; //Added 07/09/10: removed "@" for nextgen pages only
    try {
        var adImpressionsArray = new Array();

        jQuery(q).each(function () {
            if (adImpressionsArray != null) {
                var omnitureadid = jQuery(this).attr('omnitureadid');
                // Only insert ad id into array if it doesn't already exists. We don't want duplicates.
                // Also, can only allow maximum of 11 ad ids so that we don't overrun 100 char limit
                // for omniture property once we join together and report to prop28.
                if ((adImpressionsArray.indexOf(omnitureadid) == -1) &amp;&amp; adImpressionsArray.length &lt; 11) {
                    adImpressionsArray.push(omnitureadid);
                }
            }
        });

        s.prop28 = adImpressionsArray.join('|');
    }
    catch (e) { }
}

/* WARNING: Changing any of the below variables will cause drastic
changes to how your visitor data is collected. Changes should only be
made when instructed to do so by your account manager.*/
s_dell.visitorNamespace='dell';
if(s_account.substring(s_account.length-3)!='dev'){
	s_dell.trackingServer='nsm.dell.com';
	s_dell.trackingServerSecure='sm.dell.com';
}
s_dell.dc=112;

/* Load the Survey Module (except on third party sites) */
s_dell.lwpParams='http://www.dell.com';
s_dell.processLWP();
var q=s_dell.getLWPvariables();
if(q)s_dell.lwpParams+='?'+q;
//don't load survey for these cookie IDs
if(s_dell.prop6!='rc1047167'&amp;&amp;s_dell.prop6!='rc1193519'&amp;&amp;s_dell.prop6!='rc1193518'){
	s_dell.loadModule('Survey');
	s_dell.Survey.suites=s_account;
}

/* Load the Media Module */
s_dell.loadModule('Media')
s_dell.Media.autoTrack=false;
s_dell.Media.trackWhilePlaying=false;
s_dell.Media.trackVars='None';
s_dell.Media.trackEvents='None';

/* **************************** MODULES *************************** */

/* Module: Media */
s_dell.m_Media_c="var m=s.m_i('Media');m.cn=function(n){var m=this;return m.s.rep(m.s.rep(m.s.rep(n,\"\\n\",''),\"\\r\",''),'--**--','')};m.open=function(n,l,p,b){var m=this,i=new Object,tm=new Date,"
+"a='',x;n=m.cn(n);l=parseInt(l);if(!l)l=1;if(n&amp;&amp;p){if(!m.l)m.l=new Object;if(m.l[n])m.close(n);if(b&amp;&amp;b.id)a=b.id;for (x in m.l)if(m.l[x]&amp;&amp;m.l[x].a==a)m.close(m.l[x].n);i.n=n;i.l=l;i.p=m.cn(p);i.a=a;"
+"i.t=0;i.ts=0;i.s=Math.floor(tm.getTime()/1000);i.lx=0;i.lt=i.s;i.lo=0;i.e='';i.to=-1;m.l[n]=i}};m.close=function(n){this.e(n,0,-1)};m.play=function(n,o){var m=this,i;i=m.e(n,1,o);i.m=new Function('"
+"var m=s_c_il['+m._in+'],i;if(m.l){i=m.l[\"'+m.s.rep(i.n,'\"','\\\\\"')+'\"];if(i){if(i.lx==1)m.e(i.n,3,-1);i.mt=setTimeout(i.m,5000)}}');i.m()};m.stop=function(n,o){this.e(n,2,o)};m.track=function("
+"n){var m=this;if (m.trackWhilePlaying) {m.e(n,4,-1)}};m.e=function(n,x,o){var m=this,i,tm=new Date,ts=Math.floor(tm.getTime()/1000),ti=m.trackSeconds,tp=m.trackMilestones,z=new Array,j,d='--**--',t"
+"=1,b,v=m.trackVars,e=m.trackEvents,pe='media',pev3,w=new Object,vo=new Object;n=m.cn(n);i=n&amp;&amp;m.l&amp;&amp;m.l[n]?m.l[n]:0;if(i){w.name=n;w.length=i.l;w.playerName=i.p;if(i.to&lt;0)w.event=\"OPEN\";else w.even"
+"t=(x==1?\"PLAY\":(x==2?\"STOP\":(x==3?\"MONITOR\":\"CLOSE\")));w.openTime=new Date();w.openTime.setTime(i.s*1000);if(x&gt;2||(x!=i.lx&amp;&amp;(x!=2||i.lx==1))) {b=\"Media.\"+name;pev3 = m.s.ape(i.n)+d+i.l+d+"
+"m.s.ape(i.p)+d;if(x){if(o&lt;0&amp;&amp;i.lt&gt;0){o=(ts-i.lt)+i.lo;o=o&lt;i.l?o:i.l-1}o=Math.floor(o);if(x&gt;=2&amp;&amp;i.lo&lt;o){i.t+=o-i.lo;i.ts+=o-i.lo;}if(x&lt;=2){i.e+=(x==1?'S':'E')+o;i.lx=x;}else if(i.lx!=1)m.e(n,1,o);i."
+"lt=ts;i.lo=o;pev3+=i.t+d+i.s+d+(m.trackWhilePlaying&amp;&amp;i.to&gt;=0?'L'+i.to:'')+i.e+(x!=2?(m.trackWhilePlaying?'L':'E')+o:'');if(m.trackWhilePlaying){b=0;pe='m_o';if(x!=4){w.offset=o;w.percent=((w.offset"
+"+1)/w.length)*100;w.percent=w.percent&gt;100?100:Math.floor(w.percent);w.timePlayed=i.t;if(m.monitor)m.monitor(m.s,w)}if(i.to&lt;0)pe='m_s';else if(x==4)pe='m_i';else{t=0;v=e='None';ti=ti?parseInt(ti):0;"
+"z=tp?m.s.sp(tp,','):0;if(ti&amp;&amp;i.ts&gt;=ti)t=1;else if(z){if(o&lt;i.to)i.to=o;else{for(j=0;j&lt;z.length;j++){ti=z[j]?parseInt(z[j]):0;if(ti&amp;&amp;((i.to+1)/i.l&lt;ti/100)&amp;&amp;((o+1)/i.l&gt;=ti/100)){t=1;j=z.length}}}}}}}e"
+"lse{m.e(n,2,-1);if(m.trackWhilePlaying){w.offset=i.lo;w.percent=((w.offset+1)/w.length)*100;w.percent=w.percent&gt;100?100:Math.floor(w.percent);w.timePlayed=i.t;if(m.monitor)m.monitor(m.s,w)}m.l[n]=0"
+";if(i.e){pev3+=i.t+d+i.s+d+(m.trackWhilePlaying&amp;&amp;i.to&gt;=0?'L'+i.to:'')+i.e;if(m.trackWhilePlaying){v=e='None';pe='m_o'}else{t=0;m.s.fbr(b)}}else t=0;b=0}if(t){vo.linkTrackVars=v;vo.linkTrackEvents=e"
+";vo.pe=pe;vo.pev3=pev3;m.s.t(vo,b);if(m.trackWhilePlaying){i.ts=0;i.to=o;i.e=''}}}}return i};m.ae=function(n,l,p,x,o,b){if(n&amp;&amp;p){var m=this;if(!m.l||!m.l[n])m.open(n,l,p,b);m.e(n,x,o)}};m.a=functio"
+"n(o,t){var m=this,i=o.id?o.id:o.name,n=o.name,p=0,v,c,c1,c2,xc=m.s.h,x,e,f1,f2='s_media_'+m._in+'_oc',f3='s_media_'+m._in+'_t',f4='s_media_'+m._in+'_s',f5='s_media_'+m._in+'_l',f6='s_media_'+m._in+"
+"'_m',f7='s_media_'+m._in+'_c',tcf,w;if(!i){if(!m.c)m.c=0;i='s_media_'+m._in+'_'+m.c;m.c++}if(!o.id)o.id=i;if(!o.name)o.name=n=i;if(!m.ol)m.ol=new Object;if(m.ol[i])return;m.ol[i]=o;if(!xc)xc=m.s.b;"
+"tcf=new Function('o','var e,p=0;try{if(o.versionInfo&amp;&amp;o.currentMedia&amp;&amp;o.controls)p=1}catch(e){p=0}return p');p=tcf(o);if(!p){tcf=new Function('o','var e,p=0,t;try{t=o.GetQuickTimeVersion();if(t)p=2"
+"}catch(e){p=0}return p');p=tcf(o);if(!p){tcf=new Function('o','var e,p=0,t;try{t=o.GetVersionInfo();if(t)p=3}catch(e){p=0}return p');p=tcf(o)}}v=\"var m=s_c_il[\"+m._in+\"],o=m.ol['\"+i+\"']\";if(p"
+"==1){p='Windows Media Player '+o.versionInfo;c1=v+',n,p,l,x=-1,cm,c,mn;if(o){cm=o.currentMedia;c=o.controls;if(cm&amp;&amp;c){mn=cm.name?cm.name:c.URL;l=cm.duration;p=c.currentPosition;n=o.playState;if(n){"
+"if(n==8)x=0;if(n==3)x=1;if(n==1||n==2||n==4||n==5||n==6)x=2;}';c2='if(x&gt;=0)m.ae(mn,l,\"'+p+'\",x,x!=2?p:-1,o)}}';c=c1+c2;if(m.s.isie&amp;&amp;xc){x=m.s.d.createElement('script');x.language='jscript';x.type"
+"='text/javascript';x.htmlFor=i;x.event='PlayStateChange(NewState)';x.defer=true;x.text=c;xc.appendChild(x);o[f6]=new Function(c1+'if(n==3){x=3;'+c2+'}setTimeout(o.'+f6+',5000)');o[f6]()}}if(p==2){p"
+"='QuickTime Player '+(o.GetIsQuickTimeRegistered()?'Pro ':'')+o.GetQuickTimeVersion();f1=f2;c=v+',n,x,t,l,p,p2,mn;if(o){mn=o.GetMovieName()?o.GetMovieName():o.GetURL();n=o.GetRate();t=o.GetTimeScal"
+"e();l=o.GetDuration()/t;p=o.GetTime()/t;p2=o.'+f5+';if(n!=o.'+f4+'||p&lt;p2||p-p2&gt;5){x=2;if(n!=0)x=1;else if(p&gt;=l)x=0;if(p&lt;p2||p-p2&gt;5)m.ae(mn,l,\"'+p+'\",2,p2,o);m.ae(mn,l,\"'+p+'\",x,x!=2?p:-1,o)}if("
+"n&gt;0&amp;&amp;o.'+f7+'&gt;=10){m.ae(mn,l,\"'+p+'\",3,p,o);o.'+f7+'=0}o.'+f7+'++;o.'+f4+'=n;o.'+f5+'=p;setTimeout(\"'+v+';o.'+f2+'(0,0)\",500)}';o[f1]=new Function('a','b',c);o[f4]=-1;o[f7]=0;o[f1](0,0)}if(p==3"
+"){p='RealPlayer '+o.GetVersionInfo();f1=n+'_OnPlayStateChange';c1=v+',n,x=-1,l,p,mn;if(o){mn=o.GetTitle()?o.GetTitle():o.GetSource();n=o.GetPlayState();l=o.GetLength()/1000;p=o.GetPosition()/1000;i"
+"f(n!=o.'+f4+'){if(n==3)x=1;if(n==0||n==2||n==4||n==5)x=2;if(n==0&amp;&amp;(p&gt;=l||p==0))x=0;if(x&gt;=0)m.ae(mn,l,\"'+p+'\",x,x!=2?p:-1,o)}if(n==3&amp;&amp;(o.'+f7+'&gt;=10||!o.'+f3+')){m.ae(mn,l,\"'+p+'\",3,p,o);o.'+f7+'"
+"=0}o.'+f7+'++;o.'+f4+'=n;';c2='if(o.'+f2+')o.'+f2+'(o,n)}';if(m.s.wd[f1])o[f2]=m.s.wd[f1];m.s.wd[f1]=new Function('a','b',c1+c2);o[f1]=new Function('a','b',c1+'setTimeout(\"'+v+';o.'+f1+'(0,0)\",o."
+"'+f3+'?500:5000);'+c2);o[f4]=-1;if(m.s.isie)o[f3]=1;o[f7]=0;o[f1](0,0)}};m.as=new Function('e','var m=s_c_il['+m._in+'],l,n;if(m.autoTrack&amp;&amp;m.s.d.getElementsByTagName){l=m.s.d.getElementsByTagName("
+"m.s.isie?\"OBJECT\":\"EMBED\");if(l)for(n=0;n&lt;l.length;n++)m.a(l[n]);}');if(s.wd.attachEvent)s.wd.attachEvent('onload',m.as);else if(s.wd.addEventListener)s.wd.addEventListener('load',m.as,false)";
s_dell.m_i("Media");

/* Module: Survey */
s_dell.m_Survey_c="var m=s.m_i(\"Survey\");m.launch=function(i,e,c,o,f){this._boot();var m=this,g=window.s_sv_globals||{},l,j;if(g.unloaded||m._blocked())return 0;i=i&amp;&amp;i.constructor&amp;&amp;i.constructor==A"
+"rray?i:[i];l=g.manualTriggers;for(j=0;j&lt;i.length;++j)l[l.length]={l:m._suites,i:i[j],e:e||0,c:c||0,o:o||0,f:f||0};m._execute();return 1;};m._t=function(){this._boot();var m=this,s=m.s,g=window.s_sv"
+"_globals||{},l;if(m._blocked())return;l=g.pageImpressions;l[l.length]={l:m._suites,n:s.pageName||\"\",u:s.pageURL||\"\",r:s.referrer||\"\",c:s.campaign||\"\"};m._execute();};m._rr=function(){var g="
+"window.s_sv_globals||{},f=g.onScQueueEmpty||0;if(f)f();};m._blocked=function(){var m=this,g=window.s_sv_globals||{};return !m._booted||g.stop||!g.pending&amp;&amp;!g.triggerRequested;};m._execute=function("
+"){if(s_sv_globals.execute)setTimeout(\"s_sv_globals.execute();\",0);};m._boot=function(){var m=this,s=m.s,w=window,g,c,d=s.dc,e=s.visitorNamespace,n=navigator.appName.toLowerCase(),a=navigator.user"
+"Agent,v=navigator.appVersion,h,i,j,k,l,b;if(w.s_sv_globals)return;if(!((b=v.match(/AppleWebKit\\/([0-9]+)/))?521&lt;b[1]:n==\"netscape\"?a.match(/gecko\\//i):(b=a.match(/opera[ \\/]?([0-9]+).[0-9]+/i)"
+")?7&lt;b[1]:n==\"microsoft internet explorer\"&amp;&amp;!v.match(/macintosh/i)&amp;&amp;(b=v.match(/msie ([0-9]+).([0-9]+)/i))&amp;&amp;(5&lt;b[1]||b[1]==5&amp;&amp;4&lt;b[2])))return;g=w.s_sv_globals={};g.module=m;g.pending=0;g.incomingL"
+"ists=[];g.pageImpressions=[];g.manualTriggers=[];e=\"survey\";c=g.config={};m._param(c,\"dynamic_root\",(e?e+\".\":\"\")+d+\".2o7.net/survey/dynamic\");m._param(c,\"gather_root\",(e?e+\".\":\"\")+d"
+"+\".2o7.net/survey/gather\");g.url=location.protocol+\"//\"+c.dynamic_root;g.gatherUrl=location.protocol+\"//\"+c.gather_root;g.dataCenter=d;g.onListLoaded=new Function(\"r\",\"b\",\"d\",\"i\",\"l"
+"\",\"s_sv_globals.module._loaded(r,b,d,i,l);\");m._suites=(m.suites||s.un).toLowerCase().split(\",\");l=m._suites;b={};for(j=0;j&lt;l.length;++j){i=l[j];if(i&amp;&amp;!b[i]){h=i.length;for(k=0;k&lt;i.length;++k)"
+"h=(h&amp;0x03ffffff)&lt;&lt;5^h&gt;&gt;26^i.charCodeAt(k);b[i]={url:g.url+\"/suites/\"+(h%251+100)+\"/\"+encodeURIComponent(i.replace(/\\|/,\"||\").replace(/\\//,\"|-\"))};++g.pending;}}g.suites=b;setTimeout(\"s_s"
+"v_globals.module._load();\",0);m._booted=1;};m._param=function(c,n,v){var p=\"s_sv_\",w=window,u=\"undefined\";if(typeof c[n]==u)c[n]=typeof w[p+n]==u?v:w[p+n];};m._load=function(){var m=this,g=s_s"
+"v_globals,q=g.suites,r,i,n=\"s_sv_sid\",b=m.s.c_r(n);if(!b){b=parseInt((new Date()).getTime()*Math.random());m.s.c_w(n,b);}for(i in q){r=q[i];if(!r.requested){r.requested=1;m._script(r.url+\"/list."
+"js?\"+b);}}};m._loaded=function(r,b,d,i,l){var m=this,g=s_sv_globals,n=g.incomingLists;--g.pending;if(!g.commonRevision){g.bulkRevision=b;g.commonRevision=r;g.commonUrl=g.url+\"/common/\"+b;}else i"
+"f(g.commonRevision!=r)return;if(!l.length)return;n[n.length]={r:i,l:l};if(g.execute)g.execute();else if(!g.triggerRequested){g.triggerRequested=1;m._script(g.commonUrl+\"/trigger.js\");}};m._script"
+"=function(u){var d=document,e=d.createElement(\"script\");e.type=\"text/javascript\";e.src=u;d.getElementsByTagName(\"head\")[0].appendChild(e);};if(m.onLoad)m.onLoad(s,m)";
s_dell.m_i("Survey");

/************* DO NOT ALTER ANYTHING BELOW THIS LINE ! **************/
var s_code='',s_objectID;function s_gi(un,pg,ss){var c="s._c='s_c';s.wd=window;if(!s.wd.s_c_in){s.wd.s_c_il=new Array;s.wd.s_c_in=0;}s._il=s.wd.s_c_il;s._in=s.wd.s_c_in;s._il[s._in]=s;s.wd.s_c_in++;s"
+".an=s_an;s.cls=function(x,c){var i,y='';if(!c)c=this.an;for(i=0;i&lt;x.length;i++){n=x.substring(i,i+1);if(c.indexOf(n)&gt;=0)y+=n}return y};s.fl=function(x,l){return x?(''+x).substring(0,l):x};s.co=func"
+"tion(o){if(!o)return o;var n=new Object,x;for(x in o)if(x.indexOf('select')&lt;0&amp;&amp;x.indexOf('filter')&lt;0)n[x]=o[x];return n};s.num=function(x){x=''+x;for(var p=0;p&lt;x.length;p++)if(('0123456789').indexO"
+"f(x.substring(p,p+1))&lt;0)return 0;return 1};s.rep=s_rep;s.sp=s_sp;s.jn=s_jn;s.ape=function(x){var s=this,h='0123456789ABCDEF',i,c=s.charSet,n,l,e,y='';c=c?c.toUpperCase():'';if(x){x=''+x;if(s.em==3)"
+"return encodeURIComponent(x);else if(c=='AUTO'&amp;&amp;('').charCodeAt){for(i=0;i&lt;x.length;i++){c=x.substring(i,i+1);n=x.charCodeAt(i);if(n&gt;127){l=0;e='';while(n||l&lt;4){e=h.substring(n%16,n%16+1)+e;n=(n-n%"
+"16)/16;l++}y+='%u'+e}else if(c=='+')y+='%2B';else y+=escape(c)}return y}else{x=s.rep(escape(''+x),'+','%2B');if(c&amp;&amp;s.em==1&amp;&amp;x.indexOf('%u')&lt;0&amp;&amp;x.indexOf('%U')&lt;0){i=x.indexOf('%');while(i&gt;=0){i++;if"
+"(h.substring(8).indexOf(x.substring(i,i+1).toUpperCase())&gt;=0)return x.substring(0,i)+'u00'+x.substring(i);i=x.indexOf('%',i)}}}}return x};s.epa=function(x){var s=this;if(x){x=''+x;return s.em==3?de"
+"codeURIComponent(x):unescape(s.rep(x,'+',' '))}return x};s.pt=function(x,d,f,a){var s=this,t=x,z=0,y,r;while(t){y=t.indexOf(d);y=y&lt;0?t.length:y;t=t.substring(0,y);r=s[f](t,a);if(r)return r;z+=y+d.l"
+"ength;t=x.substring(z,x.length);t=z&lt;x.length?t:''}return ''};s.isf=function(t,a){var c=a.indexOf(':');if(c&gt;=0)a=a.substring(0,c);if(t.substring(0,2)=='s_')t=t.substring(2);return (t!=''&amp;&amp;t==a)};s.f"
+"sf=function(t,a){var s=this;if(s.pt(a,',','isf',t))s.fsg+=(s.fsg!=''?',':'')+t;return 0};s.fs=function(x,f){var s=this;s.fsg='';s.pt(x,',','fsf',f);return s.fsg};s.si=function(){var s=this,i,k,v,c="
+"s_gi+'var s=s_gi(\"'+s.oun+'\");s.sa(\"'+s.un+'\");';for(i=0;i&lt;s.va_g.length;i++){k=s.va_g[i];v=s[k];if(v!=undefined){if(typeof(v)=='string')c+='s.'+k+'=\"'+s_fe(v)+'\";';else c+='s.'+k+'='+v+';'}}"
+"c+=\"s.lnk=s.eo=s.linkName=s.linkType=s.wd.s_objectID=s.ppu=s.pe=s.pev1=s.pev2=s.pev3='';\";return c};s.c_d='';s.c_gdf=function(t,a){var s=this;if(!s.num(t))return 1;return 0};s.c_gd=function(){var"
+" s=this,d=s.wd.location.hostname,n=s.fpCookieDomainPeriods,p;if(!n)n=s.cookieDomainPeriods;if(d&amp;&amp;!s.c_d){n=n?parseInt(n):2;n=n&gt;2?n:2;p=d.lastIndexOf('.');if(p&gt;=0){while(p&gt;=0&amp;&amp;n&gt;1){p=d.lastIndexOf('"
+".',p-1);n--}s.c_d=p&gt;0&amp;&amp;s.pt(d,'.','c_gdf',0)?d.substring(p):d}}return s.c_d};s.c_r=function(k){var s=this;k=s.ape(k);var c=' '+s.d.cookie,i=c.indexOf(' '+k+'='),e=i&lt;0?i:c.indexOf(';',i),v=i&lt;0?'':s."
+"epa(c.substring(i+2+k.length,e&lt;0?c.length:e));return v!='[[B]]'?v:''};s.c_w=function(k,v,e){var s=this,d=s.c_gd(),l=s.cookieLifetime,t;v=''+v;l=l?(''+l).toUpperCase():'';if(e&amp;&amp;l!='SESSION'&amp;&amp;l!='NON"
+"E'){t=(v!=''?parseInt(l?l:0):-60);if(t){e=new Date;e.setTime(e.getTime()+(t*1000))}}if(k&amp;&amp;l!='NONE'){s.d.cookie=k+'='+s.ape(v!=''?v:'[[B]]')+'; path=/;'+(e&amp;&amp;l!='SESSION'?' expires='+e.toGMTString()"
+"+';':'')+(d?' domain='+d+';':'');return s.c_r(k)==v}return 0};s.eh=function(o,e,r,f){var s=this,b='s_'+e+'_'+s._in,n=-1,l,i,x;if(!s.ehl)s.ehl=new Array;l=s.ehl;for(i=0;i&lt;l.length&amp;&amp;n&lt;0;i++){if(l[i]."
+"o==o&amp;&amp;l[i].e==e)n=i}if(n&lt;0){n=i;l[n]=new Object}x=l[n];x.o=o;x.e=e;f=r?x.b:f;if(r||f){x.b=r?0:o[e];x.o[e]=f}if(x.b){x.o[b]=x.b;return b}return 0};s.cet=function(f,a,t,o,b){var s=this,r,tcf;if(s.apv"
+"&gt;=5&amp;&amp;(!s.isopera||s.apv&gt;=7)){tcf=new Function('s','f','a','t','var e,r;try{r=s[f](a)}catch(e){r=s[t](e)}return r');r=tcf(s,f,a,t)}else{if(s.ismac&amp;&amp;s.u.indexOf('MSIE 4')&gt;=0)r=s[b](a);else{s.eh(s.wd,"
+"'onerror',0,o);r=s[f](a);s.eh(s.wd,'onerror',1)}}return r};s.gtfset=function(e){var s=this;return s.tfs};s.gtfsoe=new Function('e','var s=s_c_il['+s._in+'],c;s.eh(window,\"onerror\",1);s.etfs=1;c=s"
+".t();if(c)s.d.write(c);s.etfs=0;return true');s.gtfsfb=function(a){return window};s.gtfsf=function(w){var s=this,p=w.parent,l=w.location;s.tfs=w;if(p&amp;&amp;p.location!=l&amp;&amp;p.location.host==l.host){s.tfs="
+"p;return s.gtfsf(s.tfs)}return s.tfs};s.gtfs=function(){var s=this;if(!s.tfs){s.tfs=s.wd;if(!s.etfs)s.tfs=s.cet('gtfsf',s.tfs,'gtfset',s.gtfsoe,'gtfsfb')}return s.tfs};s.mrq=function(u){var s=this,"
+"l=s.rl[u],n,r;s.rl[u]=0;if(l)for(n=0;n&lt;l.length;n++){r=l[n];s.mr(0,0,r.r,0,r.t,r.u)}};s.br=function(id,rs){var s=this;if(s.disableBufferedRequests||!s.c_w('s_br',rs))s.brl=rs};s.flushBufferedReques"
+"ts=function(){this.fbr(0)};s.fbr=function(id){var s=this,br=s.c_r('s_br');if(!br)br=s.brl;if(br){if(!s.disableBufferedRequests)s.c_w('s_br','');s.mr(0,0,br)}s.brl=0};s.mr=function(sess,q,rs,id,ta,u"
+"){var s=this,dc=s.dc,t1=s.trackingServer,t2=s.trackingServerSecure,tb=s.trackingServerBase,p='.sc',ns=s.visitorNamespace,un=s.cls(u?u:(ns?ns:s.fun)),r=new Object,l,imn='s_i_'+(un),im,b,e;if(!rs){if"
+"(t1){if(t2&amp;&amp;s.ssl)t1=t2}else{if(!tb)tb='2o7.net';if(dc)dc=(''+dc).toLowerCase();else dc='d1';if(tb=='2o7.net'){if(dc=='d1')dc='112';else if(dc=='d2')dc='122';p=''}t1=un+'.'+dc+'.'+p+tb}rs='http'+(s"
+".ssl?'s':'')+'://'+t1+'/b/ss/'+s.un+'/'+(s.mobile?'5.1':'1')+'/H.22/'+sess+'?AQB=1&amp;ndh=1'+(q?q:'')+'&amp;AQE=1';if(s.isie&amp;&amp;!s.ismac){if(s.apv&gt;5.5)rs=s.fl(rs,4095);else rs=s.fl(rs,2047)}if(id){s.br(id,r"
+"s);return}}if(s.d.images&amp;&amp;s.apv&gt;=3&amp;&amp;(!s.isopera||s.apv&gt;=7)&amp;&amp;(s.ns6&lt;0||s.apv&gt;=6.1)){if(!s.rc)s.rc=new Object;if(!s.rc[un]){s.rc[un]=1;if(!s.rl)s.rl=new Object;s.rl[un]=new Array;setTimeout('if(windo"
+"w.s_c_il)window.s_c_il['+s._in+'].mrq(\"'+un+'\")',750)}else{l=s.rl[un];if(l){r.t=ta;r.u=un;r.r=rs;l[l.length]=r;return ''}imn+='_'+s.rc[un];s.rc[un]++}im=s.wd[imn];if(!im)im=s.wd[imn]=new Image;im"
+".s_l=0;im.onload=new Function('e','this.s_l=1;var wd=window,s;if(wd.s_c_il){s=wd.s_c_il['+s._in+'];s.mrq(\"'+un+'\");s.nrs--;if(!s.nrs)s.m_m(\"rr\")}');if(!s.nrs){s.nrs=1;s.m_m('rs')}else s.nrs++;i"
+"m.src=rs;if((!ta||ta=='_self'||ta=='_top'||(s.wd.name&amp;&amp;ta==s.wd.name))&amp;&amp;rs.indexOf('&amp;pe=')&gt;=0){b=e=new Date;while(!im.s_l&amp;&amp;e.getTime()-b.getTime()&lt;500)e=new Date}return ''}return '&lt;im'+'g sr'+'c=\""
+"'+rs+'\" width=1 height=1 border=0 alt=\"\"&gt;'};s.gg=function(v){var s=this;if(!s.wd['s_'+v])s.wd['s_'+v]='';return s.wd['s_'+v]};s.glf=function(t,a){if(t.substring(0,2)=='s_')t=t.substring(2);var s"
+"=this,v=s.gg(t);if(v)s[t]=v};s.gl=function(v){var s=this;if(s.pg)s.pt(v,',','glf',0)};s.rf=function(x){var s=this,y,i,j,h,l,a,b='',c='',t;if(x){y=''+x;i=y.indexOf('?');if(i&gt;0){a=y.substring(i+1);y="
+"y.substring(0,i);h=y.toLowerCase();i=0;if(h.substring(0,7)=='http://')i+=7;else if(h.substring(0,8)=='https://')i+=8;h=h.substring(i);i=h.indexOf(\"/\");if(i&gt;0){h=h.substring(0,i);if(h.indexOf('goo"
+"gle')&gt;=0){a=s.sp(a,'&amp;');if(a.length&gt;1){l=',q,ie,start,search_key,word,kw,cd,';for(j=0;j&lt;a.length;j++){t=a[j];i=t.indexOf('=');if(i&gt;0&amp;&amp;l.indexOf(','+t.substring(0,i)+',')&gt;=0)b+=(b?'&amp;':'')+t;else c+="
+"(c?'&amp;':'')+t}if(b&amp;&amp;c){y+='?'+b+'&amp;'+c;if(''+x!=y)x=y}}}}}}return x};s.hav=function(){var s=this,qs='',fv=s.linkTrackVars,fe=s.linkTrackEvents,mn,i;if(s.pe){mn=s.pe.substring(0,1).toUpperCase()+s.pe."
+"substring(1);if(s[mn]){fv=s[mn].trackVars;fe=s[mn].trackEvents}}fv=fv?fv+','+s.vl_l+','+s.vl_l2:'';for(i=0;i&lt;s.va_t.length;i++){var k=s.va_t[i],v=s[k],b=k.substring(0,4),x=k.substring(4),n=parseInt"
+"(x),q=k;if(v&amp;&amp;k!='linkName'&amp;&amp;k!='linkType'){if(s.pe||s.lnk||s.eo){if(fv&amp;&amp;(','+fv+',').indexOf(','+k+',')&lt;0)v='';if(k=='events'&amp;&amp;fe)v=s.fs(v,fe)}if(v){if(k=='dynamicVariablePrefix')q='D';else if(k=="
+"'visitorID')q='vid';else if(k=='pageURL'){q='g';v=s.fl(v,255)}else if(k=='referrer'){q='r';v=s.fl(s.rf(v),255)}else if(k=='vmk'||k=='visitorMigrationKey')q='vmt';else if(k=='visitorMigrationServer'"
+"){q='vmf';if(s.ssl&amp;&amp;s.visitorMigrationServerSecure)v=''}else if(k=='visitorMigrationServerSecure'){q='vmf';if(!s.ssl&amp;&amp;s.visitorMigrationServer)v=''}else if(k=='charSet'){q='ce';if(v.toUpperCase()=="
+"'AUTO')v='ISO8859-1';else if(s.em==2||s.em==3)v='UTF-8'}else if(k=='visitorNamespace')q='ns';else if(k=='cookieDomainPeriods')q='cdp';else if(k=='cookieLifetime')q='cl';else if(k=='variableProvider"
+"')q='vvp';else if(k=='currencyCode')q='cc';else if(k=='channel')q='ch';else if(k=='transactionID')q='xact';else if(k=='campaign')q='v0';else if(k=='resolution')q='s';else if(k=='colorDepth')q='c';e"
+"lse if(k=='javascriptVersion')q='j';else if(k=='javaEnabled')q='v';else if(k=='cookiesEnabled')q='k';else if(k=='browserWidth')q='bw';else if(k=='browserHeight')q='bh';else if(k=='connectionType')q"
+"='ct';else if(k=='homepage')q='hp';else if(k=='plugins')q='p';else if(s.num(x)){if(b=='prop')q='c'+n;else if(b=='eVar')q='v'+n;else if(b=='list')q='l'+n;else if(b=='hier'){q='h'+n;v=s.fl(v,255)}}if"
+"(v)qs+='&amp;'+q+'='+(k.substring(0,3)!='pev'?s.ape(v):v)}}}return qs};s.ltdf=function(t,h){t=t?t.toLowerCase():'';h=h?h.toLowerCase():'';var qi=h.indexOf('?');h=qi&gt;=0?h.substring(0,qi):h;if(t&amp;&amp;h.subst"
+"ring(h.length-(t.length+1))=='.'+t)return 1;return 0};s.ltef=function(t,h){t=t?t.toLowerCase():'';h=h?h.toLowerCase():'';if(t&amp;&amp;h.indexOf(t)&gt;=0)return 1;return 0};s.lt=function(h){var s=this,lft=s.l"
+"inkDownloadFileTypes,lef=s.linkExternalFilters,lif=s.linkInternalFilters;lif=lif?lif:s.wd.location.hostname;h=h.toLowerCase();if(s.trackDownloadLinks&amp;&amp;lft&amp;&amp;s.pt(lft,',','ltdf',h))return 'd';if(s.tr"
+"ackExternalLinks&amp;&amp;h.substring(0,1)!='#'&amp;&amp;(lef||lif)&amp;&amp;(!lef||s.pt(lef,',','ltef',h))&amp;&amp;(!lif||!s.pt(lif,',','ltef',h)))return 'e';return ''};s.lc=new Function('e','var s=s_c_il['+s._in+'],b=s.eh(this"
+",\"onclick\");s.lnk=s.co(this);s.t();s.lnk=0;if(b)return this[b](e);return true');s.bc=new Function('e','var s=s_c_il['+s._in+'],f,tcf;if(s.d&amp;&amp;s.d.all&amp;&amp;s.d.all.cppXYctnr)return;s.eo=e.srcElement?e."
+"srcElement:e.target;tcf=new Function(\"s\",\"var e;try{if(s.eo&amp;&amp;(s.eo.tagName||s.eo.parentElement||s.eo.parentNode))s.t()}catch(e){}\");tcf(s);s.eo=0');s.oh=function(o){var s=this,l=s.wd.location,h"
+"=o.href?o.href:'',i,j,k,p;i=h.indexOf(':');j=h.indexOf('?');k=h.indexOf('/');if(h&amp;&amp;(i&lt;0||(j&gt;=0&amp;&amp;i&gt;j)||(k&gt;=0&amp;&amp;i&gt;k))){p=o.protocol&amp;&amp;o.protocol.length&gt;1?o.protocol:(l.protocol?l.protocol:'');i=l.pathn"
+"ame.lastIndexOf('/');h=(p?p+'//':'')+(o.host?o.host:(l.host?l.host:''))+(h.substring(0,1)!='/'?l.pathname.substring(0,i&lt;0?0:i)+'/':'')+h}return h};s.ot=function(o){var t=o.tagName;t=t&amp;&amp;t.toUpperCas"
+"e?t.toUpperCase():'';if(t=='SHAPE')t='';if(t){if((t=='INPUT'||t=='BUTTON')&amp;&amp;o.type&amp;&amp;o.type.toUpperCase)t=o.type.toUpperCase();else if(!t&amp;&amp;o.href)t='A';}return t};s.oid=function(o){var s=this,t=s.ot"
+"(o),p,c,n='',x=0;if(t&amp;&amp;!o.s_oid){p=o.protocol;c=o.onclick;if(o.href&amp;&amp;(t=='A'||t=='AREA')&amp;&amp;(!c||!p||p.toLowerCase().indexOf('javascript')&lt;0))n=s.oh(o);else if(c){n=s.rep(s.rep(s.rep(s.rep(''+c,\"\\r"
+"\",''),\"\\n\",''),\"\\t\",''),' ','');x=2}else if(t=='INPUT'||t=='SUBMIT'){if(o.value)n=o.value;else if(o.innerText)n=o.innerText;else if(o.textContent)n=o.textContent;x=3}else if(o.src&amp;&amp;t=='IMAGE"
+"')n=o.src;if(n){o.s_oid=s.fl(n,100);o.s_oidt=x}}return o.s_oid};s.rqf=function(t,un){var s=this,e=t.indexOf('='),u=e&gt;=0?t.substring(0,e):'',q=e&gt;=0?s.epa(t.substring(e+1)):'';if(u&amp;&amp;q&amp;&amp;(','+u+',').in"
+"dexOf(','+un+',')&gt;=0){if(u!=s.un&amp;&amp;s.un.indexOf(',')&gt;=0)q='&amp;u='+u+q+'&amp;u=0';return q}return ''};s.rq=function(un){if(!un)un=this.un;var s=this,c=un.indexOf(','),v=s.c_r('s_sq'),q='';if(c&lt;0)return s.p"
+"t(v,'&amp;','rqf',un);return s.pt(un,',','rq',0)};s.sqp=function(t,a){var s=this,e=t.indexOf('='),q=e&lt;0?'':s.epa(t.substring(e+1));s.sqq[q]='';if(e&gt;=0)s.pt(t.substring(0,e),',','sqs',q);return 0};s.sqs"
+"=function(un,q){var s=this;s.squ[un]=q;return 0};s.sq=function(q){var s=this,k='s_sq',v=s.c_r(k),x,c=0;s.sqq=new Object;s.squ=new Object;s.sqq[q]='';s.pt(v,'&amp;','sqp',0);s.pt(s.un,',','sqs',q);v='';"
+"for(x in s.squ)if(x&amp;&amp;(!Object||!Object.prototype||!Object.prototype[x]))s.sqq[s.squ[x]]+=(s.sqq[s.squ[x]]?',':'')+x;for(x in s.sqq)if(x&amp;&amp;(!Object||!Object.prototype||!Object.prototype[x])&amp;&amp;s.sqq[x]"
+"&amp;&amp;(x==q||c&lt;2)){v+=(v?'&amp;':'')+s.sqq[x]+'='+s.ape(x);c++}return s.c_w(k,v,0)};s.wdl=new Function('e','var s=s_c_il['+s._in+'],r=true,b=s.eh(s.wd,\"onload\"),i,o,oc;if(b)r=this[b](e);for(i=0;i&lt;s.d.lin"
+"ks.length;i++){o=s.d.links[i];oc=o.onclick?\"\"+o.onclick:\"\";if((oc.indexOf(\"s_gs(\")&lt;0||oc.indexOf(\".s_oc(\")&gt;=0)&amp;&amp;oc.indexOf(\".tl(\")&lt;0)s.eh(o,\"onclick\",0,s.lc);}return r');s.wds=function("
+"){var s=this;if(s.apv&gt;3&amp;&amp;(!s.isie||!s.ismac||s.apv&gt;=5)){if(s.b&amp;&amp;s.b.attachEvent)s.b.attachEvent('onclick',s.bc);else if(s.b&amp;&amp;s.b.addEventListener)s.b.addEventListener('click',s.bc,false);else s.eh("
+"s.wd,'onload',0,s.wdl)}};s.vs=function(x){var s=this,v=s.visitorSampling,g=s.visitorSamplingGroup,k='s_vsn_'+s.un+(g?'_'+g:''),n=s.c_r(k),e=new Date,y=e.getYear();e.setYear(y+10+(y&lt;1900?1900:0));if"
+"(v){v*=100;if(!n){if(!s.c_w(k,x,e))return 0;n=x}if(n%10000&gt;v)return 0}return 1};s.dyasmf=function(t,m){if(t&amp;&amp;m&amp;&amp;m.indexOf(t)&gt;=0)return 1;return 0};s.dyasf=function(t,m){var s=this,i=t?t.indexOf('='"
+"):-1,n,x;if(i&gt;=0&amp;&amp;m){var n=t.substring(0,i),x=t.substring(i+1);if(s.pt(x,',','dyasmf',m))return n}return 0};s.uns=function(){var s=this,x=s.dynamicAccountSelection,l=s.dynamicAccountList,m=s.dynami"
+"cAccountMatch,n,i;s.un=s.un.toLowerCase();if(x&amp;&amp;l){if(!m)m=s.wd.location.host;if(!m.toLowerCase)m=''+m;l=l.toLowerCase();m=m.toLowerCase();n=s.pt(l,';','dyasf',m);if(n)s.un=n}i=s.un.indexOf(',');s."
+"fun=i&lt;0?s.un:s.un.substring(0,i)};s.sa=function(un){var s=this;s.un=un;if(!s.oun)s.oun=un;else if((','+s.oun+',').indexOf(','+un+',')&lt;0)s.oun+=','+un;s.uns()};s.m_i=function(n,a){var s=this,m,f=n.s"
+"ubstring(0,1),r,l,i;if(!s.m_l)s.m_l=new Object;if(!s.m_nl)s.m_nl=new Array;m=s.m_l[n];if(!a&amp;&amp;m&amp;&amp;m._e&amp;&amp;!m._i)s.m_a(n);if(!m){m=new Object,m._c='s_m';m._in=s.wd.s_c_in;m._il=s._il;m._il[m._in]=m;s.wd"
+".s_c_in++;m.s=s;m._n=n;m._l=new Array('_c','_in','_il','_i','_e','_d','_dl','s','n','_r','_g','_g1','_t','_t1','_x','_x1','_rs','_rr','_l');s.m_l[n]=m;s.m_nl[s.m_nl.length]=n}else if(m._r&amp;&amp;!m._m){r"
+"=m._r;r._m=m;l=m._l;for(i=0;i&lt;l.length;i++)if(m[l[i]])r[l[i]]=m[l[i]];r._il[r._in]=r;m=s.m_l[n]=r}if(f==f.toUpperCase())s[n]=m;return m};s.m_a=new Function('n','g','e','if(!g)g=\"m_\"+n;var s=s_c_i"
+"l['+s._in+'],c=s[g+\"_c\"],m,x,f=0;if(!c)c=s.wd[\"s_\"+g+\"_c\"];if(c&amp;&amp;s_d)s[g]=new Function(\"s\",s_ft(s_d(c)));x=s[g];if(!x)x=s.wd[\\'s_\\'+g];if(!x)x=s.wd[g];m=s.m_i(n,1);if(x&amp;&amp;(!m._i||g!=\"m_\""
+"+n)){m._i=f=1;if((\"\"+x).indexOf(\"function\")&gt;=0)x(s);else s.m_m(\"x\",n,x,e)}m=s.m_i(n,1);if(m._dl)m._dl=m._d=0;s.dlt();return f');s.m_m=function(t,n,d,e){t='_'+t;var s=this,i,x,m,f='_'+t,r=0,u;"
+"if(s.m_l&amp;&amp;s.m_nl)for(i=0;i&lt;s.m_nl.length;i++){x=s.m_nl[i];if(!n||x==n){m=s.m_i(x);u=m[t];if(u){if((''+u).indexOf('function')&gt;=0){if(d&amp;&amp;e)u=m[t](d,e);else if(d)u=m[t](d);else u=m[t]()}}if(u)r=1;u=m["
+"t+1];if(u&amp;&amp;!m[f]){if((''+u).indexOf('function')&gt;=0){if(d&amp;&amp;e)u=m[t+1](d,e);else if(d)u=m[t+1](d);else u=m[t+1]()}}m[f]=1;if(u)r=1}}return r};s.m_ll=function(){var s=this,g=s.m_dl,i,o;if(g)for(i=0;i&lt;"
+"g.length;i++){o=g[i];if(o)s.loadModule(o.n,o.u,o.d,o.l,o.e,1);g[i]=0}};s.loadModule=function(n,u,d,l,e,ln){var s=this,m=0,i,g,o=0,f1,f2,c=s.h?s.h:s.b,b,tcf;if(n){i=n.indexOf(':');if(i&gt;=0){g=n.subst"
+"ring(i+1);n=n.substring(0,i)}else g=\"m_\"+n;m=s.m_i(n)}if((l||(n&amp;&amp;!s.m_a(n,g)))&amp;&amp;u&amp;&amp;s.d&amp;&amp;c&amp;&amp;s.d.createElement){if(d){m._d=1;m._dl=1}if(ln){if(s.ssl)u=s.rep(u,'http:','https:');i='s_s:'+s._in+':'+n"
+"+':'+g;b='var s=s_c_il['+s._in+'],o=s.d.getElementById(\"'+i+'\");if(s&amp;&amp;o){if(!o.l&amp;&amp;s.wd.'+g+'){o.l=1;if(o.i)clearTimeout(o.i);o.i=0;s.m_a(\"'+n+'\",\"'+g+'\"'+(e?',\"'+e+'\"':'')+')}';f2=b+'o.c++;"
+"if(!s.maxDelay)s.maxDelay=250;if(!o.l&amp;&amp;o.c&lt;(s.maxDelay*2)/100)o.i=setTimeout(o.f2,100)}';f1=new Function('e',b+'}');tcf=new Function('s','c','i','u','f1','f2','var e,o=0;try{o=s.d.createElement(\"s"
+"cript\");if(o){o.type=\"text/javascript\";'+(n?'o.id=i;o.defer=true;o.onload=o.onreadystatechange=f1;o.f2=f2;o.l=0;':'')+'o.src=u;c.appendChild(o);'+(n?'o.c=0;o.i=setTimeout(f2,100)':'')+'}}catch(e"
+"){o=0}return o');o=tcf(s,c,i,u,f1,f2)}else{o=new Object;o.n=n+':'+g;o.u=u;o.d=d;o.l=l;o.e=e;g=s.m_dl;if(!g)g=s.m_dl=new Array;i=0;while(i&lt;g.length&amp;&amp;g[i])i++;g[i]=o}}else if(n){m=s.m_i(n);m._e=1}ret"
+"urn m};s.vo1=function(t,a){if(a[t]||a['!'+t])this[t]=a[t]};s.vo2=function(t,a){if(!a[t]){a[t]=this[t];if(!a[t])a['!'+t]=1}};s.dlt=new Function('var s=s_c_il['+s._in+'],d=new Date,i,vo,f=0;if(s.dll)"
+"for(i=0;i&lt;s.dll.length;i++){vo=s.dll[i];if(vo){if(!s.m_m(\"d\")||d.getTime()-vo._t&gt;=s.maxDelay){s.dll[i]=0;s.t(vo)}else f=1}}if(s.dli)clearTimeout(s.dli);s.dli=0;if(f){if(!s.dli)s.dli=setTimeout(s."
+"dlt,s.maxDelay)}else s.dll=0');s.dl=function(vo){var s=this,d=new Date;if(!vo)vo=new Object;s.pt(s.vl_g,',','vo2',vo);vo._t=d.getTime();if(!s.dll)s.dll=new Array;s.dll[s.dll.length]=vo;if(!s.maxDel"
+"ay)s.maxDelay=250;s.dlt()};s.t=function(vo,id){var s=this,trk=1,tm=new Date,sed=Math&amp;&amp;Math.random?Math.floor(Math.random()*10000000000000):tm.getTime(),sess='s'+Math.floor(tm.getTime()/10800000)%10"
+"+sed,y=tm.getYear(),vt=tm.getDate()+'/'+tm.getMonth()+'/'+(y&lt;1900?y+1900:y)+' '+tm.getHours()+':'+tm.getMinutes()+':'+tm.getSeconds()+' '+tm.getDay()+' '+tm.getTimezoneOffset(),tcf,tfs=s.gtfs(),ta="
+"-1,q='',qs='',code='',vb=new Object;s.gl(s.vl_g);s.uns();s.m_ll();if(!s.td){var tl=tfs.location,a,o,i,x='',c='',v='',p='',bw='',bh='',j='1.0',k=s.c_w('s_cc','true',0)?'Y':'N',hp='',ct='',pn=0,ps;if"
+"(String&amp;&amp;String.prototype){j='1.1';if(j.match){j='1.2';if(tm.setUTCDate){j='1.3';if(s.isie&amp;&amp;s.ismac&amp;&amp;s.apv&gt;=5)j='1.4';if(pn.toPrecision){j='1.5';a=new Array;if(a.forEach){j='1.6';i=0;o=new Object;t"
+"cf=new Function('o','var e,i=0;try{i=new Iterator(o)}catch(e){}return i');i=tcf(o);if(i&amp;&amp;i.next)j='1.7'}}}}}if(s.apv&gt;=4)x=screen.width+'x'+screen.height;if(s.isns||s.isopera){if(s.apv&gt;=3){v=s.n.jav"
+"aEnabled()?'Y':'N';if(s.apv&gt;=4){c=screen.pixelDepth;bw=s.wd.innerWidth;bh=s.wd.innerHeight}}s.pl=s.n.plugins}else if(s.isie){if(s.apv&gt;=4){v=s.n.javaEnabled()?'Y':'N';c=screen.colorDepth;if(s.apv&gt;=5"
+"){bw=s.d.documentElement.offsetWidth;bh=s.d.documentElement.offsetHeight;if(!s.ismac&amp;&amp;s.b){tcf=new Function('s','tl','var e,hp=0;try{s.b.addBehavior(\"#default#homePage\");hp=s.b.isHomePage(tl)?\"Y"
+"\":\"N\"}catch(e){}return hp');hp=tcf(s,tl);tcf=new Function('s','var e,ct=0;try{s.b.addBehavior(\"#default#clientCaps\");ct=s.b.connectionType}catch(e){}return ct');ct=tcf(s)}}}else r=''}if(s.pl)w"
+"hile(pn&lt;s.pl.length&amp;&amp;pn&lt;30){ps=s.fl(s.pl[pn].name,100)+';';if(p.indexOf(ps)&lt;0)p+=ps;pn++}s.resolution=x;s.colorDepth=c;s.javascriptVersion=j;s.javaEnabled=v;s.cookiesEnabled=k;s.browserWidth=bw;s.b"
+"rowserHeight=bh;s.connectionType=ct;s.homepage=hp;s.plugins=p;s.td=1}if(vo){s.pt(s.vl_g,',','vo2',vb);s.pt(s.vl_g,',','vo1',vo)}if((vo&amp;&amp;vo._t)||!s.m_m('d')){if(s.usePlugins)s.doPlugins(s);var l=s.w"
+"d.location,r=tfs.document.referrer;if(!s.pageURL)s.pageURL=l.href?l.href:l;if(!s.referrer&amp;&amp;!s._1_referrer){s.referrer=r;s._1_referrer=1}s.m_m('g');if(s.lnk||s.eo){var o=s.eo?s.eo:s.lnk;if(!o)return"
+" '';var p=s.pageName,w=1,t=s.ot(o),n=s.oid(o),x=o.s_oidt,h,l,i,oc;if(s.eo&amp;&amp;o==s.eo){while(o&amp;&amp;!n&amp;&amp;t!='BODY'){o=o.parentElement?o.parentElement:o.parentNode;if(!o)return '';t=s.ot(o);n=s.oid(o);x=o.s"
+"_oidt}oc=o.onclick?''+o.onclick:'';if((oc.indexOf(\"s_gs(\")&gt;=0&amp;&amp;oc.indexOf(\".s_oc(\")&lt;0)||oc.indexOf(\".tl(\")&gt;=0)return ''}if(n)ta=o.target;h=s.oh(o);i=h.indexOf('?');h=s.linkLeaveQueryString||i"
+"&lt;0?h:h.substring(0,i);l=s.linkName;t=s.linkType?s.linkType.toLowerCase():s.lt(h);if(t&amp;&amp;(h||l))q+='&amp;pe=lnk_'+(t=='d'||t=='e'?s.ape(t):'o')+(h?'&amp;pev1='+s.ape(h):'')+(l?'&amp;pev2='+s.ape(l):'');else trk="
+"0;if(s.trackInlineStats){if(!p){p=s.pageURL;w=0}t=s.ot(o);i=o.sourceIndex;if(s.gg('objectID')){n=s.gg('objectID');x=1;i=1}if(p&amp;&amp;n&amp;&amp;t)qs='&amp;pid='+s.ape(s.fl(p,255))+(w?'&amp;pidt='+w:'')+'&amp;oid='+s.ape(s."
+"fl(n,100))+(x?'&amp;oidt='+x:'')+'&amp;ot='+s.ape(t)+(i?'&amp;oi='+i:'')}}if(!trk&amp;&amp;!qs)return '';s.sampled=s.vs(sed);if(trk){if(s.sampled)code=s.mr(sess,(vt?'&amp;t='+s.ape(vt):'')+s.hav()+q+(qs?qs:s.rq()),0,id,ta"
+");qs='';s.m_m('t');if(s.p_r)s.p_r();s.referrer=''}s.sq(qs);}else{s.dl(vo);}if(vo)s.pt(s.vl_g,',','vo1',vb);s.lnk=s.eo=s.linkName=s.linkType=s.wd.s_objectID=s.ppu=s.pe=s.pev1=s.pev2=s.pev3='';if(s.p"
+"g)s.wd.s_lnk=s.wd.s_eo=s.wd.s_linkName=s.wd.s_linkType='';if(!id&amp;&amp;!s.tc){s.tc=1;s.flushBufferedRequests()}return code};s.tl=function(o,t,n,vo){var s=this;s.lnk=s.co(o);s.linkType=t;s.linkName=n;s.t"
+"(vo)};if(pg){s.wd.s_co=function(o){var s=s_gi(\"_\",1,1);return s.co(o)};s.wd.s_gs=function(un){var s=s_gi(un,1,1);return s.t()};s.wd.s_dc=function(un){var s=s_gi(un,1);return s.t()}}s.ssl=(s.wd.lo"
+"cation.protocol.toLowerCase().indexOf('https')&gt;=0);s.d=document;s.b=s.d.body;if(s.d.getElementsByTagName){s.h=s.d.getElementsByTagName('HEAD');if(s.h)s.h=s.h[0]}s.n=navigator;s.u=s.n.userAgent;s.ns"
+"6=s.u.indexOf('Netscape6/');var apn=s.n.appName,v=s.n.appVersion,ie=v.indexOf('MSIE '),o=s.u.indexOf('Opera '),i;if(v.indexOf('Opera')&gt;=0||o&gt;0)apn='Opera';s.isie=(apn=='Microsoft Internet Explorer'"
+");s.isns=(apn=='Netscape');s.isopera=(apn=='Opera');s.ismac=(s.u.indexOf('Mac')&gt;=0);if(o&gt;0)s.apv=parseFloat(s.u.substring(o+6));else if(ie&gt;0){s.apv=parseInt(i=v.substring(ie+5));if(s.apv&gt;3)s.apv=pa"
+"rseFloat(i)}else if(s.ns6&gt;0)s.apv=parseFloat(s.u.substring(s.ns6+10));else s.apv=parseFloat(v);s.em=0;if(s.em.toPrecision)s.em=3;else if(String.fromCharCode){i=escape(String.fromCharCode(256)).toUp"
+"perCase();s.em=(i=='%C4%80'?2:(i=='%U0100'?1:0))}s.sa(un);s.vl_l='dynamicVariablePrefix,visitorID,vmk,visitorMigrationKey,visitorMigrationServer,visitorMigrationServerSecure,ppu,charSet,visitorName"
+"space,cookieDomainPeriods,cookieLifetime,pageName,pageURL,referrer,currencyCode';s.va_l=s.sp(s.vl_l,',');s.vl_t=s.vl_l+',variableProvider,channel,server,pageType,transactionID,purchaseID,campaign,s"
+"tate,zip,events,products,linkName,linkType';for(var n=1;n&lt;76;n++)s.vl_t+=',prop'+n+',eVar'+n+',hier'+n+',list'+n;s.vl_l2=',tnt,pe,pev1,pev2,pev3,resolution,colorDepth,javascriptVersion,javaEnabled,"
+"cookiesEnabled,browserWidth,browserHeight,connectionType,homepage,plugins';s.vl_t+=s.vl_l2;s.va_t=s.sp(s.vl_t,',');s.vl_g=s.vl_t+',trackingServer,trackingServerSecure,trackingServerBase,fpCookieDom"
+"ainPeriods,disableBufferedRequests,mobile,visitorSampling,visitorSamplingGroup,dynamicAccountSelection,dynamicAccountList,dynamicAccountMatch,trackDownloadLinks,trackExternalLinks,trackInlineStats,"
+"linkLeaveQueryString,linkDownloadFileTypes,linkExternalFilters,linkInternalFilters,linkTrackVars,linkTrackEvents,linkNames,lnk,eo,_1_referrer';s.va_g=s.sp(s.vl_g,',');s.pg=pg;s.gl(s.vl_g);if(!ss)s."
+"wds()",
w=window,l=w.s_c_il,n=navigator,u=n.userAgent,v=n.appVersion,e=v.indexOf('MSIE '),m=u.indexOf('Netscape6/'),a,i,s;if(un){un=un.toLowerCase();if(l)for(i=0;i&lt;l.length;i++){s=l[i];if(!s._c||s._c=='s_c'){if(s.oun==un)return s;else if(s.fs&amp;&amp;s.sa&amp;&amp;s.fs(s.oun,un)){s.sa(un);return s}}}}w.s_an='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
w.s_sp=new Function("x","d","var a=new Array,i=0,j;if(x){if(x.split)a=x.split(d);else if(!d)for(i=0;i&lt;x.length;i++)a[a.length]=x.substring(i,i+1);else while(i&gt;=0){j=x.indexOf(d,i);a[a.length]=x.subst"
+"ring(i,j&lt;0?x.length:j);i=j;if(i&gt;=0)i+=d.length}}return a");
w.s_jn=new Function("a","d","var x='',i,j=a.length;if(a&amp;&amp;j&gt;0){x=a[0];if(j&gt;1){if(a.join)x=a.join(d);else for(i=1;i&lt;j;i++)x+=d+a[i]}}return x");
w.s_rep=new Function("x","o","n","return s_jn(s_sp(x,o),n)");
w.s_d=new Function("x","var t='`^@$#',l=s_an,l2=new Object,x2,d,b=0,k,i=x.lastIndexOf('~~'),j,v,w;if(i&gt;0){d=x.substring(0,i);x=x.substring(i+2);l=s_sp(l,'');for(i=0;i&lt;62;i++)l2[l[i]]=i;t=s_sp(t,'');d"
+"=s_sp(d,'~');i=0;while(i&lt;5){v=0;if(x.indexOf(t[i])&gt;=0) {x2=s_sp(x,t[i]);for(j=1;j&lt;x2.length;j++){k=x2[j].substring(0,1);w=t[i]+k;if(k!=' '){v=1;w=d[b+l2[k]]}x2[j]=w+x2[j].substring(1)}}if(v)x=s_jn("
+"x2,'');else{w=t[i]+' ';if(x.indexOf(w)&gt;=0)x=s_rep(x,w,t[i]);i++;b+=62}}}return x");
w.s_fe=new Function("c","return s_rep(s_rep(s_rep(c,'\\\\','\\\\\\\\'),'\"','\\\\\"'),\"\\n\",\"\\\\n\")");
w.s_fa=new Function("f","var s=f.indexOf('(')+1,e=f.indexOf(')'),a='',c;while(s&gt;=0&amp;&amp;s&lt;e){c=f.substring(s,s+1);if(c==',')a+='\",\"';else if((\"\\n\\r\\t \").indexOf(c)&lt;0)a+=c;s++}return a?'\"'+a+'\"':"
+"a");
w.s_ft=new Function("c","c+='';var s,e,o,a,d,q,f,h,x;s=c.indexOf('=function(');while(s&gt;=0){s++;d=1;q='';x=0;f=c.substring(s);a=s_fa(f);e=o=c.indexOf('{',s);e++;while(d&gt;0){h=c.substring(e,e+1);if(q){i"
+"f(h==q&amp;&amp;!x)q='';if(h=='\\\\')x=x?0:1;else x=0}else{if(h=='\"'||h==\"'\")q=h;if(h=='{')d++;if(h=='}')d--}if(d&gt;0)e++}c=c.substring(0,s)+'new Function('+(a?a+',':'')+'\"'+s_fe(c.substring(o+1,e))+'\")"
+"'+c.substring(e+1);s=c.indexOf('=function(')}return c;");
c=s_d(c);if(e&gt;0){a=parseInt(i=v.substring(e+5));if(a&gt;3)a=parseFloat(i)}else if(m&gt;0)a=parseFloat(u.substring(m+10));else a=parseFloat(v);if(a&gt;=5&amp;&amp;v.indexOf('Opera')&lt;0&amp;&amp;u.indexOf('Opera')&lt;0){w.s_c=new Function("un","pg","ss","var s=this;"+c);return new s_c(un,pg,ss)}else s=new Function("un","pg","ss","var s=new Object;"+s_ft(c)+";return s");return s(un,pg,ss)}
