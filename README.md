## Vision

DoublePlus aims to be the Wordpress of imageboards, meaning that it's easily installable on shared hosting
with the features of LynxChan. While designed for small communities it can be used for larger ones.

We are currently using the Double Plus engine on [Wrongthink.net](https://wrongthink.net) to experiment with a lot of new ideas. [More info about Wrongthink](https://snerx.com/wrongthink)

## Features
- [x] no javascript is required, old browsers work fine
- [x] admin management: manage users and boards
- [x] various themes
- [x] CAPTCHA support
- [x] hidden service support (including onion address header)
- [x] user-created boards
- [x] post queue
- [x] post reacts
- [x] can delete OP without losing replies
- [x] Threads can be made into playlists, pulling all media links and embeds together.
- [x] Game-theoretically secure distributed moderation options, let your community moderate itself
- [x] works on shared hosting
- [x] modular / well organized code base
- [x] 4chan compatible API for reading content
- [x] Lynxchan compatible API for posting content
- [x] preview system to help build SEO

Can use:
- Apache or NGINX
- MySQL or PostgreSQL

## Goals
- Works on shared hosting and is easy to install as any PHP/MySQL web app
- all similar functionality grouped into modules, allowing endpoints to only load from disk what they need
- documentation before code
- aim for the lynxchan feature set
- try to make the styling and maintainence of features easy
- try to make adding new features easy
- 2-3 distinct pieces (backend REST API that mobile clients can talk to directly)

## Architecture

Frontend Layer

Backend Layer (storage layer exists here now)
REST API

Storage Layer (to be built)
REST API
Handles storage in a NoSQL fashion

## Ideas / Feedback

[Discussion](https://gitgud.io/odilitime/lynxphp/-/issues)

## Support us

- [Patreon](https://www.patreon.com/join/odilitime/checkout?rid=7884395)
- Bitcoin (BTC): [14xWX1rHJPqhsy8APTUFoV4MfApZXKqSwv](bitcoin:14xWX1rHJPqhsy8APTUFoV4MfApZXKqSwv)
- Monero (XMR): [898HFHZyhiXKC26CVoKGZac7wJ3aL8ncSgi6EaP39zLFPphM1EMzMTP7LFmbR9n8B2BmXoL21mtVCQ1vjQGnvfosCuNjyY9](monero:898HFHZyhiXKC26CVoKGZac7wJ3aL8ncSgi6EaP39zLFPphM1EMzMTP7LFmbR9n8B2BmXoL21mtVCQ1vjQGnvfosCuNjyY9)
- Oxen (OXEN): [LT7Dd9WxxqoBAxC6rxzYTpCy8mmvVNDbiQjEe8UcXge5MvhfYEWi6Ttdho78vsYYgWADT9P8pu813Ckdw3mkZdfBS133CPi](oxen:LT7Dd9WxxqoBAxC6rxzYTpCy8mmvVNDbiQjEe8UcXge5MvhfYEWi6Ttdho78vsYYgWADT9P8pu813Ckdw3mkZdfBS133CPi)
