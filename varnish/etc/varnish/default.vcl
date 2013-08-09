import geoip;
import std;

probe healthcheck {
    .request =
        "GET /healthCheck HTTP/1.1"
        "Host: demo.alienwarearena.com"
        "Connection: close";
    .timeout = 3s;
}

backend awaWeb1  { .host = "ec2-23-22-229-200.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb2  { .host = "ec2-54-226-103-0.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb3  { .host = "ec2-50-19-47-216.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb4  { .host = "ec2-54-227-50-4.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb5  { .host = "ec2-50-16-16-111.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb6  { .host = "ec2-54-227-123-57.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb7  { .host = "ec2-54-227-180-151.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb8  { .host = "ec2-54-227-149-154.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb9  { .host = "ec2-23-22-31-120.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb10  { .host = "ec2-54-227-58-146.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }

director awaWeb random {
    { .backend = awaWeb1; .weight = 1; }
    { .backend = awaWeb2; .weight = 1; }
    { .backend = awaWeb3; .weight = 1; }
    { .backend = awaWeb4; .weight = 1; }
    { .backend = awaWeb5; .weight = 1; }
    { .backend = awaWeb6; .weight = 1; }
    { .backend = awaWeb7; .weight = 1; }
    { .backend = awaWeb8; .weight = 1; }
    { .backend = awaWeb9; .weight = 1; }
    { .backend = awaWeb10; .weight = 1; }
}

acl ban {
    "ec2-75-101-139-101.compute-1.amazonaws.com";
    "ec2-23-22-229-200.compute-1.amazonaws.com";
    "ec2-54-226-103-0.compute-1.amazonaws.com";
    "ec2-50-19-47-216.compute-1.amazonaws.com";
    "ec2-54-227-50-4.compute-1.amazonaws.com";
    "ec2-50-16-16-111.compute-1.amazonaws.com";
    "ec2-54-227-123-57.compute-1.amazonaws.com";
    "ec2-54-227-180-151.compute-1.amazonaws.com";
    "ec2-54-227-149-154.compute-1.amazonaws.com";
    "ec2-23-22-31-120.compute-1.amazonaws.com";
    "ec2-54-227-58-146.compute-1.amazonaws.com";
}

sub vcl_recv {

    /*if (req.url ~ "^/allowMigrationTesting$") {
        error 418 "http://" + req.http.host + "/login";
    }

    if (req.http.Cookie !~ "migration_test_allowed=1") {
        error 888 "Testing error.";
    }*/

    if (req.http.X-Forwarded-For) {
        set req.http.X-Client-IP = regsuball(req.http.X-Forwarded-For, "^.*(, |,| )(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$", "\2");
        #set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
    } else {
        set req.http.X-Client-IP = client.ip;
        #set req.http.X-Forwarded-For = client.ip;
    }

    set req.http.X-Country-Code = geoip.country_code(req.http.X-Client-IP);

    if (req.http.host !~ ".*.alienwarearena.(com|local:8080)$" && client.ip !~ ban) {
        error 404 "Page Not Found.";
    }

    if (req.http.host ~ "china.alienwarearena.(com|local:8080)$") {
        error 404 "Page Not Found.";
    }

    if (req.request != "GET" && req.request != "POST" && req.request != "PUT" && req.request != "DELETE" && req.request != "BAN") {
        error 404 "Page Not Found.";
    }

    if (req.request != "GET" && req.http.referer && req.http.referer !~ ".*.alienwarearena.(com|local:8080)") { # most notably to stop POST requests from non alienwarearena sources
        error 403 "Forbidden (referer).";
    }

    if (req.url ~ "^/video(/.*)?$") {
        error 750 "http://" + req.http.host + "/videos";
    }

    if (req.http.host !~ "^.*migration" && req.url ~ "^/account/register" && req.http.host ~ ".com$") {
        error 750 "https://www.alienwarearena.com/account/register";
    }

    // This one is temporarily here as CEVO didn't implement the link to our gallery page correctly... care needs to be taken as they do require the feed to still work...
    if (req.url ~ "^/galleries/featured-feed" && req.http.referer && req.http.referer ~ "alienwarearena.com") {
        error 750 "http://" + req.http.host  + "/galleries/";
    }

    if (req.url ~ "^/healthCheck$") {
        error 404 "Page Not Found.";
    }

    if (req.esi_level == 0 && req.url ~ "^/esi/" && req.request != "BAN") { # an external client is requesting an esi
        error 404 "Page Not Found.";
    }

    set req.backend = awaWeb;

    if (req.http.host == "api.alienwarearena.com") {
        return (pass);
    }

    if (req.esi_level > 0) {

        if (req.url ~ "/https://" && req.url ~ "esi") { # this if block is a fix for symfony outputing the esi src with https... when it does this varnish thinks it is a relative link
            set req.url = regsub(req.url, "^[/]?.*/https://.*?/", "/");
        }

        if (req.url !~ "^/esi/") { # varnish is being asked to process an esi that doesnt have /esi/ in the path
            error 404 "Page Not Found.";
        }

        if (req.url ~ "^/esi/COUNTRY_SPECIFIC/") {
            remove req.http.Cookie;
            // force request to vary on country header
        }

        if (req.url !~ "^/esi/USER_SPECIFIC/") { # drop the cookie for any non user specific esi
            remove req.http.Cookie;
        }
    }

    if (!req.backend.healthy) {
        unset req.http.Cookie;
    }

    set req.http.Surrogate-Capability = "abc=ESI/1.0";

    set req.grace = 6h;

    if (req.url ~ "^/(bundles|css|js|images|plugins)/" || req.url ~ "\.(png|gif|jpg|jpeg|swf|css|js|ico|htm|html)$") {
        unset req.http.cookie;
    }

    if (req.http.Cookie) {
        set req.http.Cookie = ";" + req.http.Cookie;
        set req.http.Cookie = regsuball(req.http.Cookie, "; +", ";");
        set req.http.Cookie = regsuball(req.http.Cookie, ";(PHPSESSID|aw_session|awa_session_key|migration_test_allowed)=", "; \1=");
        set req.http.Cookie = regsuball(req.http.Cookie, ";[^ ][^;]*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|[; ]+$", "");

        if (req.http.Cookie == "") {
            remove req.http.Cookie;
        }
    }

    if (req.request != "GET" && req.request != "BAN") {
        return (pass);
    }

    if (req.request == "BAN") {
        if (!client.ip ~ ban) {
            error 404 "Page Not Found.";
        }

        set req.http.X-ban-by-user = "";
        set req.http.X-ban-by-country = "";

        if (req.http.x-ban-user-id) {
            set req.http.X-ban-by-user = " && obj.http.x-user-id == " + req.http.x-ban-user-id;
        }

        if (req.http.x-ban-country-code) {
            set req.http.X-ban-by-country = " && obj.http.X-Country-Code == " + req.http.x-ban-country-code;
        }

        std.log("Setting ban [ " + "obj.http.x-url == " + req.url + " " + req.http.X-ban-by-user + " " + req.http.X-ban-by-country + " ]");
        ban("obj.http.x-url == " + req.url + " "  + req.http.X-ban-by-user + " "  + req.http.X-ban-by-country);

        unset req.http.X-ban-by-user;
        unset req.http.X-ban-by-country;

        error 200 "Ban added.";
    }

    if (req.url ~ "^/admin/") {
        return (pass);
    }

    if (req.url ~ "^/login[/]?$" || req.url ~ "^/account/register[/]?$") {
        return (pass);
    }

    if (req.url ~ "^/esi/USER_SPECIFIC/") {
        return (lookup);
    }

    if (req.url ~ "^/(giveaways|deal)[/]?" && req.url !~ "/(key|redeem)$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/galleries/featured-feed") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/videos/feed$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/videos/category-tab/") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/timeline[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/videos/tab/") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/groups[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/events[/]?$" || req.url ~ "^/events/([^/]+)[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/wallpapers[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/microsoft[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/contact[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/about[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/contests[/]?$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/galleries/gallery-data\?") {
        remove req.http.Cookie;
    }

    if ((req.url ~ "^/galleries[/]?$" || req.url ~ "^/galleries/photo/\d$") && req.url !~ "\?vote=\d$") {
        remove req.http.Cookie;
    }

    if (req.url ~ "^/videos[/]?$" || req.url ~ "^/videos/view/" || req.url ~ "^/videos/category/([^/]+)[/]?$") {
        remove req.http.Cookie;
    }



    if (req.http.Cookie) {
        return (pass);
    }

    return (lookup);
}

sub vcl_fetch {

    // set so that we can utilize the ban lurker to test against the url of cached items
    set beresp.http.x-url = req.url;

    // set so that we can utilize the ban lurker to test against the host of cached items
    set beresp.http.x-host = req.http.host;

    if (req.url !~ "^/allowMigrationTesting$" && req.url !~ "^/(set|refresh)ApiSessionCookie/" && req.url !~ "^/age/verify$" && req.url !~ "^/login(_check)?$" && req.url !~ "^/logout$" && req.url !~ "^/sessionCookie$" && req.url !~ "^/account/register[/]?$" && req.url !~ "^/register/confirm/" && req.url !~ "^/reset/") { # the only exceptions to the "remove all set-cookies rule"
        unset beresp.http.set-cookie;
    }

    set beresp.grace = 6h;

    if (beresp.http.content-type ~ "text") {
        set beresp.do_gzip = true;
    }

    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    if (req.url ~ "^/(bundles|css|js|images|plugins)/" || req.url ~ "\.(png|gif|jpg|jpeg|swf|css|js|ico|htm|html)$") {
        unset beresp.http.expires;
        set beresp.ttl = 15m;
        set beresp.http.cache-control = "max-age=900";
    }

    if (!req.http.Cookie && !beresp.http.set-cookie) {
        set beresp.http.X-Anon = "1";
    }
}

sub vcl_deliver {

    // initially set so that we can utilize the ban lurker to test against the url of cached items
    unset resp.http.x-url;

    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT (TIMES: "+obj.hits+")";
    } else {
        set resp.http.X-Cache = "MISS";
    }

    set resp.http.X-Country-Code = req.http.X-Country-Code;
    set resp.http.X-Client-IP = req.http.X-Client-IP;

    # before we pass the final response back to the user, make sure that all shared
    set resp.http.Cache-Control = regsub(resp.http.Cache-Control, "s-maxage=[0-9]+", "s-maxage=0");
    set resp.http.Cache-Control = regsub(resp.http.Cache-Control, "public", "private");

    if (req.url ~ "^/forceLogout/") {
        set resp.http.set-cookie = "aw_session=0; Domain=.alienwarearena.com; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT;";
    }
}

sub vcl_hash {

    hash_data(req.url);
    hash_data(req.http.host);

    if (req.url ~ "^/esi/USER_SPECIFIC/") {
        if (req.http.cookie) {
            hash_data(req.http.cookie);
        } else {
            if (req.url ~ "^/esi/USER_SPECIFIC/(giveawayShowMainActions|dealShowMainActions)/") {
                hash_data(req.http.X-Country-Code);
            }
        }
    }

    if(req.url ~ "^/esi/COUNTRY_SPECIFIC/") {
        hash_data(req.http.X-Country-Code);
    }

    return (hash);
}

sub vcl_error {

    set obj.http.Content-Type = "text/html; charset=utf-8";

    if (obj.status == 750) {
        set obj.http.Location = obj.response;
        set obj.status = 302;
        return(deliver);
    }

    // our generic way to deliver a blank response
    if (obj.status == 999) {
        synthetic "";
        return(deliver);
    }

    if (obj.status == 418) {
        set obj.http.Set-Cookie = "migration_test_allowed=1; Expires: Thu, 08 Aug 2013 23:59:59 GMT; Path=/; Domain=.alienwarearena.com;";
        set obj.http.Location = obj.response;
        set obj.status = 302;
        return(deliver);
    }

    // AWA-themed error page
    if (obj.status == 888) {
        if (req.http.host ~ "(japan|japanstaging|japanmigration).alienwarearena.(com|local:8080)") {
            set obj.http.Location = "http://media.alienwarearena.com/error/maintenance_ja.html";
        } else if (req.http.host ~ "(latam|latamstaging|latammigration).alienwarearena.(com|local:8080)") {
            set obj.http.Location = "http://media.alienwarearena.com/error/maintenance_es.html";
        } else {
            set obj.http.Location = "http://media.alienwarearena.com/error/maintenance.html";
        }

        set obj.status = 302;
        return(deliver);
    }

    synthetic {"
    <?xml version="1.0" encoding="utf-8"?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html>
      <head>
        <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
        <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>

        <style>
            body {
                padding: 30px 60px;
            }
        </style>

        <title>Alienware Arena - "} + obj.status + " " + obj.response + {"</title>
      </head>
      <body>
        <h1>Alienware Arena</h1>
        <h3 class="text-error">Error "} + obj.status + " -  " + obj.response + {"</h3>
        <hr>
        <h5 class="muted">Information:</h5>
        <table class="table table-bordered table-condensed">
            <tr>
                <th style="width: 100px;">Host</th>
                <td>"} + req.http.host + {"</td>
            </tr>
            <tr>
                <th>URL</th>
                <td>"} + req.url + {"</td>
            </tr>
            <tr>
                <th>Request</th>
                <td>"} + req.request + {"</td>
            </tr>
            <tr>
                <th>Referer</th>
                <td>"} + req.http.referer + {"</td>
            </tr>
            <tr>
                <th>Country Code</th>
                <td>"} + req.http.X-Country-Code + {"</td>
            </tr>
            <tr>
                <th>Client IP</th>
                <td>"} + req.http.X-Client-IP + {"</td>
            </tr>
            <tr>
                <th>XID</th>
                <td>"} + req.xid + {"</td>
            </tr>
        </table>
      </body>
    </html>
    "};

    return (deliver);
}
