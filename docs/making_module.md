
// parsed in common/lib.loader.php::registerPackage and lib.package.php::addResource
module
  name
  version
  portals
    name
      fePipelines
      requires
  settings
    level - (bo,)
    location - tab or group
    addFields
      name
        label
        type
  resources
    resource
      name
      params // parsed in backend/router.php::fromResource & lib.backend.php::consume_beRsrc & lib.package.php::useResource
        endpoint - route cond, NEEDS a prefix to work: opt/lynx/4chan (does not start with a /)
        method - HTTP method
        handlerFile - automatically generated, only set if you need to
        sendSession - send auth if we have it
        requireSession - requires auth
        sendIP - expose user's ip
        unwrapData - meta/data wrapper
        requires - array of params needed
        params - querystring/postdata or map type (querystring/formData) =>name
        cacheSettings
        formData
