DoublePlus aims to be the Wordpress of imageboards that's easily installable on shared hosting
with the features of LynxChan. While designed for small communities it can be used for larger ones.

Can use:
- apache or nginx
- MySQL or PostgreSQL

Goals
- Works on shared hosting and is easy to install as any PHP/MySQL web app
- all similar functionality grouped into modules, allowing endpoints to only load from disk what they need
- documentation before code
- aim for the lynxchan feature set
- try to make the styling and maintainence of features easy
- try to make adding new features easy
- 2-3 distinct pieces (backend REST API that mobile clients can talk to directly)

Frontend Layer

Backend Layer (storage layer exists here now)
REST API

Storage Layer (to be built)
REST API
Handles storage in a NoSQL fashion

[Discussion](https://gitgud.io/odilitime/lynxphp/-/issues)
