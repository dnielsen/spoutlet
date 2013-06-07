probe healthcheck {
    .request = 
        "GET /healthCheck HTTP/1.1"
        "Host: demo.alienwarearena.com"
        "Connection: close";
}

backend awa1  { .host = "ec2-75-101-139-101.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }
backend awa2  { .host = "ec2-54-224-7-205.compute-1.amazonaws.com";   .port = "http"; .probe = healthcheck; }
backend awa3  { .host = "ec2-54-224-5-214.compute-1.amazonaws.com";   .port = "http"; .probe = healthcheck; }
backend awa4  { .host = "ec2-23-20-55-80.compute-1.amazonaws.com";    .port = "http"; .probe = healthcheck; }
backend awa5  { .host = "ec2-174-129-62-95.compute-1.amazonaws.com";  .port = "http"; .probe = healthcheck; }

director awa random {
    { .backend = awa1; .weight = 1; }
    { .backend = awa2; .weight = 2; }
    { .backend = awa3; .weight = 2; }
    { .backend = awa4; .weight = 2; }
    { .backend = awa5; .weight = 2; }
}

sub vcl_recv {

    #req.http.host ~ "^.*staging.alienwarearena.com"

    set req.backend = awa;

    if (req.url ~ "^/healthCheck$") {
        error 403 "Access Denied.";
    }

    if (req.esi_level == 0 && req.url ~ "^(/app_dev.php)?/esi/") { # an external client is requesting an esi
        error 403 "Access Denied.";
    }

    if (req.esi_level > 0) {

        if (req.url ~ "/https://" && req.url ~ "esi") { # this if block is a fix for symfony outputing the esi src with https... when it does this varnish thinks it is a relative link
            set req.url = regsub(req.url, "^[/]?.*/https://.*?/", "/");
        }

        if (!req.url ~ "^(/app_dev.php)?/esi/") { # varnish is being asked to process an esi that doesnt have /esi/ in the path
            error 404 "Incorrect ESI path.";
        }

        if (!req.url ~ "^(/app_dev.php)?/esi/USER_SPECIFIC/") { # drop the cookie for any non user specific esi
            remove req.http.Cookie;
        }
    }

    if (!req.backend.healthy) {
        unset req.http.Cookie;
    }

    if (req.request == "PURGE") {
        error 405 "Not Allowed.";
    }

    set req.http.Surrogate-Capability = "abc=ESI/1.0";

    set req.grace = 6h;

    if (req.url ~ "^/(bundles|css|js|images|plugins)/" || req.url ~ "\.(png|gif|jpg|jpeg|swf|css|js|ico|htm|html)$") {
        unset req.http.cookie;
    }

    if (req.http.Cookie) {
        set req.http.Cookie = ";" + req.http.Cookie;
        set req.http.Cookie = regsuball(req.http.Cookie, "; +", ";");
        set req.http.Cookie = regsuball(req.http.Cookie, ";(PHPSESSID|aw_session|pd_session)=", "; \1=");
        set req.http.Cookie = regsuball(req.http.Cookie, ";[^ ][^;]*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|[; ]+$", "");

        if (req.http.Cookie == "") {
            remove req.http.Cookie;
        }
    }

    if (req.request != "GET" && req.request != "HEAD") {

            if (req.request != "PUT" &&
                    req.request != "POST" &&
                    req.request != "TRACE" &&
                    req.request != "OPTIONS" &&
                    req.request != "DELETE") {
                return (pipe);
            }

        return (pass);
    }

    if (req.url ~ "^(/app_dev.php)?/admin/") {
        return (pass);
    }

    if (req.url ~ "^(/app_dev.php)?/esi/USER_SPECIFIC/") {
        return (lookup);
    }

    if (req.url ~ "^(/app_dev.php)?/(giveaways|deal)[/]?" && !req.url ~ "/(key|redeem)$") {
        remove req.http.Cookie;
        return (lookup);
    }

    if (req.url ~ "^/video/ajax/apjxml$") {
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
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    if (req.url ~ "^/(bundles|css|js|images|plugins)/" || req.url ~ "\.(png|gif|jpg|jpeg|swf|css|js|ico|htm|html)$") {
        set beresp.ttl = 15m;
        set beresp.http.cache-control = "max-age=900";
        unset beresp.http.expires;
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "^/video/ajax/apjxml$") {
        unset beresp.http.set-cookie;
        set beresp.ttl = 1m;
        set beresp.http.cache-control = "max-age=60";
        unset beresp.http.expires;
    }

    if (req.url ~ "^/galleries/featured-feed") {
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "^/videos/feed$") {
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "^/videos/category-tab/") {
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "^/timeline[/]?$") {
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "^/videos/tab/") {
        unset beresp.http.set-cookie;
    }

    if (beresp.http.content-type ~ "text") {
        set beresp.do_gzip = true;
    }

    set beresp.grace = 6h;

    if (!req.url ~ "^/esi/USER_SPECIFIC/.*$" && req.url ~ "^/(esi|giveaways|deal)[/]?"){
        unset beresp.http.set-cookie;
    }
}

sub vcl_deliver {
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT (TIMES: "+obj.hits+")";
    } else {
        set resp.http.X-Cache = "MISS";
    }

    # before we pass the final response back to the user, make sure that all shared
    set resp.http.Cache-Control = regsub(resp.http.Cache-Control, "s-maxage=[0-9]+", "s-maxage=0");
    set resp.http.Cache-Control = regsub(resp.http.Cache-Control, "public", "private");
}

sub vcl_hash {

    hash_data(req.url);
    hash_data(req.http.host);

    if(req.url ~ "/esi/USER_SPECIFIC/" && req.http.cookie) {
        hash_data(req.http.cookie);
    }

    return (hash);
}
