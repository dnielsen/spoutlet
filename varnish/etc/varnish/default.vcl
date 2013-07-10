import geoip;

probe healthcheck {
    .request =
        "GET /healthCheck HTTP/1.1"
        "Host: demo.alienwarearena.com"
        "Connection: close";
}

backend awaWeb1  { .host = "ec2-54-224-7-205.compute-1.amazonaws.com";   .port = "http"; .probe = healthcheck; }
backend awaWeb2  { .host = "ec2-54-224-5-214.compute-1.amazonaws.com";   .port = "http"; .probe = healthcheck; }
backend awaWeb3  { .host = "ec2-23-20-55-80.compute-1.amazonaws.com";    .port = "http"; .probe = healthcheck; }
backend awaWeb4  { .host = "ec2-23-22-250-200.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb5  { .host = "ec2-54-235-31-61.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb6  { .host = "ec2-54-234-5-79.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb7  { .host = "ec2-54-242-222-180.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awaWeb8  { .host = "ec2-107-22-135-194.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }

director awaWeb random {
    { .backend = awaWeb1; .weight = 1; }
    { .backend = awaWeb2; .weight = 1; }
    { .backend = awaWeb3; .weight = 1; }
    { .backend = awaWeb4; .weight = 1; }
    { .backend = awaWeb5; .weight = 1; }
    { .backend = awaWeb6; .weight = 1; }
    { .backend = awaWeb7; .weight = 1; }
    { .backend = awaWeb8; .weight = 1; }
}

sub vcl_recv {

    if (req.http.X-Forwarded-For) {
        set req.http.X-Client-IP = regsuball(req.http.X-Forwarded-For, "^.*(, |,| )(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$", "\2");
        #set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
    } else {
        set req.http.X-Client-IP = client.ip;
        #set req.http.X-Forwarded-For = client.ip;
    }

    set req.http.X-Country-Code = geoip.country_code(req.http.X-Client-IP);

    if (req.http.host !~ ".*.alienwarearena.(com|local:8080)$") {
        error 404 "Page Not Found.";
    }

    if (req.http.host ~ "china.alienwarearena.(com|local:8080)$") {
        error 404 "Page Not Found.";
    }

    if (req.request != "GET" && req.request != "POST" && req.request != "PUT" && req.request != "DELETE") {
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

    if (req.esi_level == 0 && req.url ~ "^/esi/") { # an external client is requesting an esi
        error 404 "Page Not Found.";
    }

    set req.backend = awaWeb;

    if (req.esi_level > 0) {

        if (req.url ~ "/https://" && req.url ~ "esi") { # this if block is a fix for symfony outputing the esi src with https... when it does this varnish thinks it is a relative link
            set req.url = regsub(req.url, "^[/]?.*/https://.*?/", "/");
        }

        if (req.url !~ "^/esi/") { # varnish is being asked to process an esi that doesnt have /esi/ in the path
            error 404 "Page Not Found.";
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
        set req.http.Cookie = regsuball(req.http.Cookie, ";(PHPSESSID|aw_session)=", "; \1=");
        set req.http.Cookie = regsuball(req.http.Cookie, ";[^ ][^;]*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|[; ]+$", "");

        if (req.http.Cookie == "") {
            remove req.http.Cookie;
        }
    }

    if (req.request != "GET") {
        return (pass);
    }

    if (req.url ~ "^/admin/") {
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

    if (req.http.Cookie) {
        return (pass);
    }

    return (lookup);
}

sub vcl_fetch {

    if (req.url !~ "^/age/verify$" && req.url !~ "^/login(_check)?$") { # the only exceptions to the "remove all set-cookies rule"
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

    if(req.url ~ "^/esi/USER_SPECIFIC/" && req.http.cookie) {
        hash_data(req.http.cookie);
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
