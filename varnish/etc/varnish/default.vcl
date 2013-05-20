backend awa2  { .host = "ec2-204-236-207-80.compute-1.amazonaws.com"; .port = "http"; }
backend awa3  { .host = "ec2-107-22-71-108.compute-1.amazonaws.com";  .port = "http"; }
backend awa4  { .host = "ec2-75-101-223-7.compute-1.amazonaws.com";   .port = "http"; }
backend awa5  { .host = "ec2-174-129-62-95.compute-1.amazonaws.com";  .port = "http"; }
backend awa6  { .host = "ec2-54-242-181-100.compute-1.amazonaws.com"; .port = "http"; }
backend awa7  { .host = "ec2-50-16-75-123.compute-1.amazonaws.com";   .port = "http"; }
backend awa8  { .host = "ec2-50-16-37-33.compute-1.amazonaws.com";    .port = "http"; }
backend awa9  { .host = "ec2-50-16-66-61.compute-1.amazonaws.com";    .port = "http"; }

director awa round-robin {
    { .backend = awa2; }
    { .backend = awa2; }
    { .backend = awa3; }
    { .backend = awa4; }
    { .backend = awa5; }
    { .backend = awa6; }
    { .backend = awa7; }
    { .backend = awa8; }
    { .backend = awa9; }
}

sub vcl_recv {

    set req.backend = awa;

    if (req.esi_level == 0 && req.url ~ "^(/app_dev.php)?/esi/") { # an external client is requesting an esi
        error 403 "Access Denied.";
    }

    if (req.esi_level > 0) {
        if (!req.url ~ "^(/app_dev.php)?/esi/") { # varnish is being asked to process an esi that doesn't have /esi/ in the path
            error 404 "Incorrect ESI path.";
        }

        if (!req.url ~ "^(/app_dev.php)?/esi/USER_SPECIFIC/") { # drop the cookie for any non user specific esi
            remove req.http.Cookie;
        }
    }

    if (req.request == "PURGE") {
        error 405 "Not Allowed.";
    }

    set req.http.Surrogate-Capability = "abc=ESI/1.0";

    set req.grace = 1h;

    if (req.url ~ "^/(bundles|css|js|images|plugins)/" || req.url ~ "\.(png|gif|jpg|swf|css|js|htm|html)$") {
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

    if (req.restarts == 0) {
        if (req.http.x-forwarded-for) {
            set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
        } else {
            set req.http.X-Forwarded-For = client.ip;
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

    if (req.http.host ~ "^.*staging.alienwarearena.com") {
        if (req.url ~ "^(/app_dev.php)?/esi/USER_SPECIFIC/") {
            return (lookup);
        }

        if (req.url ~ "^(/app_dev.php)?/(giveaways|deal)[/]" && !req.url ~ "/(key|redeem)$") {
            remove req.http.Cookie;
            return (lookup);
        }
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

    if (req.url ~ "^/(bundles|css|js|images|plugins)/" || req.url ~ "\.(png|gif|jpg|swf|css|js)$") {
        unset beresp.http.set-cookie;
        set beresp.ttl = 15m;
        set beresp.http.cache-control = "max-age=900";
        unset beresp.http.expires;
    }

    if (beresp.http.content-type ~ "text") {
        set beresp.do_gzip = true;
    }

    set beresp.grace = 1h;

    if (req.http.host ~ "^.*staging.alienwarearena.com") {
        if (!req.url ~ "^/esi/USER_SPECIFIC/.*$" && req.url ~ "^/(esi|giveaways|deal)[/]"){
            unset beresp.http.set-cookie;
        }
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
