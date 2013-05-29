# Introduction

This API is designed to allow CEVO and other 3rd parties to retrieve information about users (post user migration).  The User API will provide readonly access to user specific data as well as allowing users to be *banned / un-banned*.

## API Type, Hostname, Protocol and API Version

All calls to this RESTful API must be made via HTTPS, must include the `/v1/` root path and must be directed to the following host:

```
https://api.alienwarearena.com/v1/
```

## Restrictions on using the API

API calls will only be allowed from IP addresses that require access.

## API Request Information

API requests should follow these rules:

- All `GET` requests should include an `etag` HTTP header if it is known.  It is worth storing the last known `etag` in your user table so that it can be used for follow up requests.
- All requests must be made with the full `URL` being lowercase.
- Each individual request parameter must have it's value `URL encoded`.
- All requests must have their optional parameters in alphabetical order and directly after the resource identifier.
- All requests must include `AccessKey` as the final query parameter before the `URL` is digitally signed.
- Finally the entire `URL` must be digitally signed using your `SecretKey` using `HMAC-SHA1`.  This signature must be the final query parameter and must have the name `sig`.

Here is a generic example of a fully valid API call:

```
GET https://api.alienwarearena.com/v1/{resource}?{optional_parameters&}accesskey={secret_key}&sig={signature}
```

### Step by Step

First we have to figure out the resource we want to query:

```
https://api.alienwarearena.com/v1/users
```

Then we add on any optional parameters we need to (ensuring that they are alphabetical order, and lowercase):

```
https://api.alienwarearena.com/v1/users?since=2013-01-01&limit=50&offset=100
```

Then we add the `AccessKey` (assuming `AccessKey` = *"c014080d-5109-41a4-b985-66954f1ef7c9"*):

```
https://api.alienwarearena.com/v1/users?since=2013-01-01&limit=50&offset=100&accesskey=c014080d-5109-41a4-b985-66954f1ef7c9
```

We then feed that full `URL` as input into [hash_hmac](http://php.net/manual/en/function.hash-hmac.php)(using "sha1" for $algo) along with the `SecretKey` (assuming `SecretKey` = *"66644588-6573-44f7-9c06-827de2628bbb"*), which produces the following output:

```
fd7667cae5938ba39ce165838448f7da1abc40c9
```

Then we added this to the final `URL` as the `sig` parameters:

```
GET https://api.alienwarearena.com/v1/users?since=2013-01-01&limit=50&offset=100&accesskey=c014080d-5109-41a4-b985-66954f1ef7c9&sig=fd7667cae5938ba39ce165838448f7da1abc40c9
```

## API Response Information

API responses follow these rules:

- All response will contain a `metaData` section that will include any out of band information.
- HTTP content type will be *"application/json"*.
- All datetimes will be in `UTC` and `ISO 8601` format.
- All valid resources will have an `href` value indicating their absolute URL.
- If a `GET` request was **successful**:
 - It will return both an HTTP status code of `200 (OK)` and have a `"status": 200` in the `metaData` section.  The only exception to this is when your request contains an `etag` that the server deems to be fresh and in that case an HTTP status code of `304 (Not Modified)` will be returned instead.
 - For a *single resource*, then the response will contain `metaData` and `data` sections.
 - For a *resource collection*, then the response will contain `metaData` and `items` sections.
 - The `data` section of the response will include all available information for the requested resource.
 - The `items` section of the response will include a list of all the resources contained in the resource collection.
- If the request was **not successful**:
 - It will only contain one section `metaData`.  The `metaData` section will contain information about the cause of the failure as well as troubleshooting suggestions.

## Retrieving a List of Users

To retrieve a list of user:

```
GET https://api.alienwarearena.com/v1/users
```

Which, if successful, will return:

```
{
  "metaData": {
    "status":       200,
    "generatedAt":  "2013-05-15T13:59:59Z",
    "limit":        50,
    "offset":       0,
    "orderBy":      "created",
    "since"         "2001-01-01T00:00:00Z",
  },
  "items": {
    "item": {
      "href":         "https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
      "uuid":         "2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
      "username":     "Flash Gordon",
      "email":        "flashgordon@example.com",
      "created":      "2013-01-01T10:05:05Z",
      "lastUpdated":  "2013-01-01T10:05:05Z",
      "banned":       false,
      "session":      "66c206f6-33d2-49c8-9618-c54d7c01939a"
    },
    "item": {
      "href":         "https://api.alienwarearena.com/v1/users/f10ab486-9e65-4b81-9da6-27e6fc485260",
      "uuid":         "f10ab486-9e65-4b81-9da6-27e6fc485260",
      "username":     "Ming The Merciless",
      "email":        "ming@example.com",
      "created":      "2012-03-03T10:03:03Z",
      "lastUpdated":  "2013-05-02T17:23:41Z",
      "banned":       true,
      "session":      null
    }
  }
}

```

A few notes about retrieving multiple users:

- There are a number of optional parameters you can send as a query string:
 - `limit` - specifies the maximum number of items you want to retrieve.
 - `offset` - specifies the number of items that you want to *skip* (from the start of the collection).
 - `orderBy` - specifies the sorting mode, can be either *"created"* (which sorts by account created date, oldest first) or *"lastUpdated"* (which sorts by the date the account was last modified, most recently modified last).
 - `since` - specifies the date you want to use to filter out users that where *"created"*/*"lastUpdated"* (depending on the `orderBy` value) before this value.  You can only specify the date, not the time with this parameter.  The time will always be set to *"00:00:00"*.  Additionally, any users *"created"*/*"lastUpdated"* at exactly *"00:00:00"* will be included in that date's result set.
- For all optional parameters:
 - If you leave them blank, or pass an invalid value, you can see what they were actually set to while generated the response by looking at the `metaData.{parameterName}` value.
 - If you provide a valid value, the `metaData.{parameterName}` will match your value.
- **IMPORTANT:** To reduce the complexity of `URL` signing and to enable a broader caching strategy the following two rules are mandatory for optional parameters:
 - The order of optional parameters must be alphabetical.
 - The entire `URL` should be lowercase.  For example, when specifying `orderBy`, ensure it appears in the `URL` as `orderby=...` and **not** `orderBy=...`.
- Users are always ordered by the active `orderBy` mode (ascending) and then by their `UUID` (ascending).  This means that with careful use of `since`, `limit`, `offset` and `sortBy` you can keep track of new users as well as recently modified users.

So for example, if the last user you received was created on *"2013-01-01T10:05:05Z"* you could send the following query to identify new users:

```
GET https://api.alienwarearena.com/v1/users?orderby=created&since=2013-01-01
```

For even more power and control you can include all optional parameters:

```
GET https://api.alienwarearena.com/v1/users?limit=50&offset=50&orderby=created&since=2013-01-01
```

Obviously the results from these two example above need to be parsed to ensure that you don't re-add users you have already added.  It is also important to continue the requests until the number of `items` is less than `metaData.limit`, or until you receive a response that has no `item` values in `items`.

If you make a request that returns no users, the response will look like this:

```
{
  "metaData": {
    "status":       200,
    "generatedAt":  "2013-05-15T13:59:59Z",
    "limit":        50,
    "offset":       0,
    "orderBy":      "created",
    "since"         "2001-01-01T00:00:00Z"
  },
  "items": { }
}
```

## Retrieving an Individual User's Data
To retrieve a user's data (with `UUID` = *"2b6abec7-c0a7-4f9d-ac1f-f038660a9635"*):

```
GET https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635
```

Which, if successful, will return:

```
{
  "metaData": {
    "status":       200,
    "generatedAt":  "2013-05-15T13:59:59Z"
  },
  "data": {
    "href":         "https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "uuid":         "2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "username":     "Flash Gordon",
    "email":        "flashgordon@example.com",
    "created":      "2013-01-01T10:05:05Z",
    "lastUpdated":  "2013-01-01T10:05:05Z",
    "banned":       false
  }
}
```

If the user doesn't exist an HTTP status code of `404 (Not Found)` will be returned.

## Session Key Lookup

When someone arrives at the CEVO site, check for the `awa_session_key` cookie.  If set, this will be a `UUID` that will allow you to lookup if it is a valid `SessionKey` or not, and if so, who does it belong to.

To lookup a `SessionKey` (with `UUID` = *"d06d80fc-5324-4e22-8863-5dac707fc5e4"*):

```
GET https://api.alienwarearena.com/v1/sessions/d06d80fc-5324-4e22-8863-5dac707fc5e4
```

Which, if successful, will return:

```
{
  "metaData": {
    "status":       200,
    "generatedAt":  "2013-05-15T13:59:59Z",
  },
  "data": {
    "href":         "https://api.alienwarearena.com/v1/sessions/d06d80fc-5324-4e22-8863-5dac707fc5e4",
    "uuid":         "d06d80fc-5324-4e22-8863-5dac707fc5e4",
    "created":      "2013-06-08T12:19:02Z",
    "expires":      "2013-06-08T18:19:02Z",
    "user": {
      "href":         "https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
      "uuid":         "2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
      "username":     "Flash Gordon",
      "email":        "flashgordon@example.com",
      "created":      "2013-01-01T10:05:05Z",
      "lastUpdated":  "2013-01-01T10:05:05Z",
      "banned":       false
    }
  }
}
```

Every time you query for a `SessionKey` that exists, it will extend the life of the `SessionKey` until the `data.expires` value.  However no `SessionKey` can survive past 24 hours after its original creation.  If you query for a `SessionKey` and it exists, you should update the user's cookie's `Expires` value appropriately.

If the session key doesn't exist an HTTP status code of `404 (Not Found)` will be returned.

## Ban or Unban a User

To ban a user (with `UUID` = *"2b6abec7-c0a7-4f9d-ac1f-f038660a9635"*):

```
POST https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635
{
  "action": "ban"
}
```

Which, if successful, will return:

```
{
  "metaData": {
    "status":       200,
    "generatedAt":  "2013-05-15T13:59:59Z",
    "action":       "ban"
  },
  "data": {
    "href":         "https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "uuid":         "2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "username":     "Flash Gordon",
    "email":        "flashgordon@example.com",
    "created":      "2013-01-01T10:05:05Z",
    "lastUpdated":  "2013-01-01T10:05:05Z",
    "banned":       true
  }
}
```
To unban a user, just follow the process to ban a user but change `"action": "ban"` to `"action": "unban"`.

