# BE config

BACKEND_KEY string does not have a default, must be set
A string that's used to secure your instance

DB_DRIVER string defaults to: 'mysql'

DB_HOST string defaults to: 'localhost'
DB_USER string defaults to: 'root'
DB_PWD string defaults to: ''
DB_NAME string defaults to: 'doubleplus'

DISABLE_MODULES array defaults to: array()
an array of modules names (site/userbar) to disable

USER string
Which user does your webserver run as

# FE config

BACKEND_BASE_URL url defaults to: http://localhost/backend/
BACKEND_PUBLIC_URL url defaults to: https://FRONTEND_HOST/backend/

BASE_HREF string defaults to: what it detects
Where's the app is located on the domain (in / or like /imageboard)

BASE_HOST string defaults to: FRONTEND_HOST
What domain and port the frontend is located

CANONICAL_BASE string defaults: false (off)

SCRATCH_DRIVER string deaults to: 'auto' (auto-detect)

FILE_SCRATCH_DIRECTORY string defaults to: ../frontend_storage/
Where the webserver has access to write temporary data (outside of the webroot)

REDIS_HOST
REDIS_PORT
REDIS_SOCKET
REDIS_FORCE_HOST

USER string
Which user does your webserver run as

DISABLE_MODULES array defaults to: array()
an array of modules names (site/userbar) to disable

DEV_MODE boolean defaults to: false
turn on development helpers