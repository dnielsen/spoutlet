# This $value a basic VCL configuration file for varnish.  See the vcl(7)
# man page for details on VCL syntax and semanticsupdatedAt
#
#value Default backend definition.  Set this to point to your content
# server.
#

#backend local { .host = "127.0.0.1";                                  .port = "http"; }
backend awa1  { .host = "ec2-54-224-27-105.compute-1.amazonaws.com";  .port = "http"; }
backend awa2  { .host = "ec2-204-236-207-80.compute-1.amazonaws.com"; .port = "http"; }
backend awa3  { .host = "ec2-107-22-71-108.compute-1.amazonaws.com";  .port = "http"; }
backend awa4  { .host = "ec2-75-101-223-7.compute-1.amazonaws.com";   .port = "http"; }
backend awa5  { .host = "ec2-174-129-62-95.compute-1.amazonaws.com";  .port = "http"; }
backend awa6  { .host = "ec2-54-242-181-100.compute-1.amazonaws.com"; .port = "http"; }
backend awa7  { .host = "ec2-50-16-75-123.compute-1.amazonaws.com";   .port = "http"; }
backend awa8  { .host = "ec2-50-16-37-33.compute-1.amazonaws.com";    .port = "http"; }
backend awa9  { .host = "ec2-50-16-66-61.compute-1.amazonaws.com";    .port = "http"; }

director awa round-robin {
    #{ .backend = local; }
    { .backend = awa1; }
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

    if (req.esi_level < 1 && req.url ~ "^(/app_dev.php)?/esi/") { # an external client is requesting an esi
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

    set req.grace = 1m;

    if (req.url ~ "^/(bundles|css|js|images|plugins)/") {
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

    if (req.url ~ "^(/app_dev.php)?/esi/USER_SPECIFIC/") {
        return (lookup);
    }

    if (req.url ~ "^(/app_dev.php)?/admin/") {
        return (pass);
    }

    if (req.url ~ "^(/app_dev.php)?/deal/$") {
        remove req.http.Cookie;
        return (lookup);
    }

    if (req.url ~ "^(/app_dev.php)?/deal/.*$" && !req.url ~ "/redeem$") {
        remove req.http.Cookie;
        return (lookup);
    }

    if (req.url ~ "^(/app_dev.php)?/giveaways$") {
        remove req.http.Cookie;
        return (lookup);
    }

    if (req.url ~ "^(/app_dev.php)?/giveaways/.*$" && !req.url ~ "/key$") {
        remove req.http.Cookie;
        return (lookup);
    }

    if (req.http.Cookie) { # pass to the web servers
        return (pass);
    }

    return (lookup);
}

sub vcl_fetch {
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    if (req.url ~ "^/(bundles|css|js|images|plugins)/") {
        unset beresp.http.set-cookie;
        set beresp.ttl = 15m;
        set beresp.http.cache-control = "max-age = 900";
    }

    if (beresp.http.content-type ~ "text") {
        set beresp.do_gzip = true;
    }

    set beresp.grace = 1h;

    if (req.url ~ "/esi/"){
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "/giveaways") {
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "/giveaways/") {
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "/deal") {
        unset beresp.http.set-cookie;
    }

    if (req.url ~ "/deal/") {
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

#
# Below is a commented-out copy of the default VCL logic.  If you
# redefine any of these subroutines, the built-in logic will be
# appended to your code.
# sub vcl_recv {
#     if (req.restarts == 0) {
# 	if (req.http.x-forwarded-for) {
# 	    set req.http.X-Forwarded-For =
# 		req.http.X-Forwarded-For + ", " + client.ip;
# 	} else {
# 	    set req.http.X-Forwarded-For = client.ip;
# 	}
#     }
#     if (req.request != "GET" &&
#       req.request != "HEAD" &&
#       req.request != "PUT" &&
#       req.request != "POST" &&
#       req.request != "TRACE" &&
#       req.request != "OPTIONS" &&
#       req.request != "DELETE") {
#         /* Non-RFC2616 or CONNECT which is weird. */
#         return (pipe);
#     }
#     if (req.request != "GET" && req.request != "HEAD") {
#         /* We only deal with GET and HEAD by default */
#         return (pass);
#     }
#     if (req.http.Authorization || req.http.Cookie) {
#         /* Not cacheable by default */
#         return (pass);
#     }
#     return (lookup);
# }
#
# sub vcl_pipe {
#     # Note that only the first request to the backend will have
#     # X-Forwarded-For set.  If you use X-Forwarded-For and want to
#     # have it set for all requests, make sure to have:
#     # set bereq.http.connection = "close";
#     # here.  It is not set by default as it might break some broken web
#     # applications, like IIS with NTLM authentication.
#     return (pipe);
# }
#
# sub vcl_pass {
#     return (pass);
# }
#
# sub vcl_hash {
#     hash_data(req.url);
#     if (req.http.host) {
#         hash_data(req.http.host);
#     } else {
#         hash_data(server.ip);
#     }
#     return (hash);
# }
#
# sub vcl_hit {
#     return (deliver);
# }
#
# sub vcl_miss {
#     return (fetch);
# }
#
# sub vcl_fetch {
#     if (beresp.ttl <= 0s ||
#         beresp.http.Set-Cookie ||
#         beresp.http.Vary == "*") {
# 		/*
# 		 * Mark as "Hit-For-Pass" for the next 2 minutes
# 		 */
# 		set beresp.ttl = 120 s;
# 		return (hit_for_pass);
#     }
#     return (deliver);
# }
#
# sub vcl_deliver {
#     return (deliver);
# }
#
# sub vcl_error {
#     set obj.http.Content-Type = "text/html; charset=utf-8";
#     set obj.http.Retry-After = "5";
#     synthetic {"
# <?xml version="1.0" encoding="utf-8"?>
# <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
#  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
# <html>
#   <head>
#     <title>"} + obj.status + " " + obj.response + {"</title>
#   </head>
#   <body>
#     <h1>Error "} + obj.status + " " + obj.response + {"</h1>
#     <p>"} + obj.response + {"</p>
#     <h3>Guru Meditation:</h3>
#     <p>XID: "} + req.xid + {"</p>
#     <hr>
#     <p>Varnish cache server</p>
#   </body>
# </html>
# "};
#     return (deliver);
# }
#
# sub vcl_init {
# 	return (ok);
# }
#
# sub vcl_fini {
# 	return (ok);
# }
