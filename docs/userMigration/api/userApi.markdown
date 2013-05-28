# Introduction
This API is designed to allow CEVO and other 3rd parties to retrieve information about users (post user migration).  The User API will provide readonly access to user specific data as well as allowing users to be *banned / un-banned*.
## API Type, Hostname, Protocol and API Version
All calls to this RESTful API must be made via HTTPS, must include the `/v1/` root path and must be directed to the following host:
```
https://api.alienwarearena.com/v1/
```
## API Response Information
API responses follow these rules:
- All response will contain a `metaData` section will include any out of band information.  It can typically can be ignored on successful requests.
- HTTP content type will be *"application/json"*.
- All datetimes will be UTC and ISO 8601.
- All valid resources will have an `href` value indicating their absolute URL.
- If the request was **successful**:
 - It will contain two sections (`metaData` and `data`).
 - It will return both an HTTP status code of 200 (OK) and have a `"status": 200` in the `metaData` section.  The only exception to this is when your request contains an *etag* that the server deems to be fresh and in that case a HTTP status code of 304 (Not Modified) status code will be returned.
 - The `data` section of the response will include all available information for the requested resource.
- If the request was **not successful**:
 - It will only contain one section (`metaData`).  The `metaData` section will contain information about the cause of the failure as well as troubleshooting suggestions.

## Retrieving a User's Data
To retrieve a user's data (with UUID = *2b6abec7-c0a7-4f9d-ac1f-f038660a9635*):
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
    "href":      "https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "uuid":      "2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "username":  "Flash Gordon",
    "email":     "flashgordon@example.com",
    "banned":    false
  }
}
```
## Ban or Unban a User
To ban a user (with UUID = *2b6abec7-c0a7-4f9d-ac1f-f038660a9635*):
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
    "generatedAt":  "2013-05-15T13:59:59Z"
  },
  "data": {
    "href":      "https://api.alienwarearena.com/v1/users/2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "uuid":      "2b6abec7-c0a7-4f9d-ac1f-f038660a9635",
    "username":  "Flash Gordon",
    "email":     "flashgordon@example.com",
    "banned":    true
  }
}
```
To unban a user, just follow the process to ban a user but change `"action": "ban"` to `"action": "unban"`.
