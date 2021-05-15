# Model View Controller (MVC)
If you're new to web development, let me explains some common patterns. One is MVC:

## Models
are like database tables, they define what tables have which fields. These are one main source of input to the program

## Views
are like web page templates, they handle the presentation to the user. It's important that only User Interface (UI) logic is contained here, no "business" logic (major decision making). These are related to the program's output.

## Controllers
This is where the "business" logic resides (major decision making). It marries the Models to the Views. It's the program's processing phase, takes the input (maybe models or input from the browser), processes it for the output (using views)

# Other common web terms

## Handler
This is a function that takes input from a route and processes it. Similar to (if not the same as) a controller. This functions decides what templates to load and what backend calls to data download to make (if any). Most frontend handlers end with a `wrapContent` which wraps the content you pass to it with the site's header and footer.

## Route
This defines what URL on your web application goes to which handler and possible some parameters to extract from the URL to pass to the handler. A route looks like `/:uri/catalog`, this means the first level can be anything and is store in a parameter called `uri` but the 2nd level directory must match `catalog`.

## Endpoint
This is basically inclusive of a specific route and it's handler.

## Form
Is usually a pair of endpoints, one GET endpoint that displays a form to the user, and one POST endpoint that processes the data from that from and either displays validation problems/errors, a success page or redirects them to an appropriate place if processing the form is successful.

## Middleware
These are common functions that you may want to use on multiple handlers. This code can be refactored out into a middleware and then applied to multiple routes.

## Router
Manages the routes, adding them from code as well as determining which route to run on a request.

## Frontend
Usually code that takes a data source and renders it into HTML, CSS and JS

## Backend
Usually code that takes a data source, handles the application logic and provides a final data source output for mobile and HTML (frontend) clients

## Plugin
Plugins are pieces of software that extend functionality of a site.

# Uncommon terms used

## Package
Packages are part of our modular plug-in system. A plugin module has a list of backend pacakges and a list of frontend packages. Each of these packages provide additional functionality, if it's a new form, endpoint, or pipeline module.  

## Pipeline 

Pipelines are a list of pipeline modules loaded from a package. These modules are called one by one in a determined order and their results collated into a singlular outcome.

## Pipeline Module

Pipeline modules are like handlers but they usually are a utility and do no output to the browser. Used to add items to navigations or a field to a form.
