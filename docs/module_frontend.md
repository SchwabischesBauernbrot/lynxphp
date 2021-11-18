
data.php
  name
    handlers
      method
      route
      handler
      loggedIn - flag as a require a loggin, so generate doesn't make a page
      cacheSettings
        files
          filename
    forms
      route
      handler
      options
        get_options
        post_options
    modules
      pipeline
      module
    pipelines
      name

Files
  handlers
    $params = $getHandler(); // defined in common/lib.packages.php
      - request
        - params
      - action
    $result = $pkg->useResource('NAME', PARAMS k=>v, options);
      options
        addPostFields
        inWrapContent
  forms
    $params = $getHandler();

  modules
    $params = $getModule();

  pipelines
    no file is currently needed

  wrapContent('content', $options)
    options
      noWork
      settings