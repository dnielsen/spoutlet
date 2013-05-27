# Introduction
The original goal of the Avatar API Endpoint was to provide CEVO’s platform with a service it could call to retrieve any user’s avatar (at a number of different sizes).  The anticipation was then that they would cache it at their end and check it periodically or on an event sent by us.  This workflow was deemed too much effort on both sides.  The new technique simply involves CEVO using a naming convention to load the avatars that have been pre-generated.  If a user changes their avatar it is up to the new platform to update the pre-generated images to match.
## Hostname
All avatars will be hosted on:
```
http://media.alienwarearena.com
```
It is recommended that you stick to using http where possible, but if you need to provide the same image through https please use the following hostname:
```
https://d2ssnvre2e87xh.cloudfront.net
```
## Naming convention
All avatars will be located inside:
```
/images/avatars/
```
A particular user’s avatars can be found using their user’s UUID (which will be accessible via the user API).  So for the user with the UUID of *2b6abec7-c0a7-4f9d-ac1f-f038660a9635*, you would find all the different avatars in:
```
/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/
```
The pre-generated avatars will be found in:
```
/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/
```
The avatar filenames will follow this convention:
```
{size_profile}.{file_extension}
```
## Size profiles
Different size profiles can be added where needed but for this document we will assume that the agreed upon sizes are:
- 32x32
- 48x48
- 64x64
- 128x128
- 256x256

## File extensions
All avatars will be saved as PNGs.
## Other rules
- Also note that the entire URL will always be lowercase.
- Underscores will be used between words where required.

Some examples:
```
/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/32x32.png
/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/48x48.png
/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/64x64.png
/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/128x128.png
/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/256x256.png
```
## Samples
A number of samples have been uploaded:
### HTTP - User1 (UUID = *2b6abec7-c0a7-4f9d-ac1f-f038660a9635*):
```
[http://media.alienwarearena.com/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/32x32.png]
[http://media.alienwarearena.com/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/48x48.png]
[http://media.alienwarearena.com/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/64x64.png]
[http://media.alienwarearena.com/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/128x128.png]
[http://media.alienwarearena.com/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/256x256.png]
```
### HTTPS - User1 (UUID = *2b6abec7-c0a7-4f9d-ac1f-f038660a9635*):
```
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/32x32.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/48x48.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/64x64.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/128x128.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/2b6abec7-c0a7-4f9d-ac1f-f038660a9635/by_size/256x256.png]
```
### HTTP - User2 (UUID = *f10ab486-9e65-4b81-9da6-27e6fc485260*):
```
[http://media.alienwarearena.com/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/32x32.png]
[http://media.alienwarearena.com/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/48x48.png]
[http://media.alienwarearena.com/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/64x64.png]
[http://media.alienwarearena.com/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/128x128.png]
[http://media.alienwarearena.com/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/256x256.png]
```
### HTTPS - User2 (UUID = *f10ab486-9e65-4b81-9da6-27e6fc485260*):
```
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/32x32.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/48x48.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/64x64.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/128x128.png]
[https://d2ssnvre2e87xh.cloudfront.net/images/avatars/f10ab486-9e65-4b81-9da6-27e6fc485260/by_size/256x256.png]
```
