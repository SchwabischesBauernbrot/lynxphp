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
This is a function that takes input from a route and processes it. Similar to (if not the same as) a controller.

## Route
This defines what URL on your web application goes to which handler and possible some parameters to extract from the URL to pass to the handler.

## Endpoint
This is basically inclusive of a specific route and it's handler.

## Middleware
These are common functions that you may want to use on multiple handlers. This code can be refactored out into a middleware and then applied to multiple routes.

## Router
Manages the routes

# Frontend
Usually code that takes a data source and renders it into HTML, CSS and JS

# Backend
Usually code that takes a data source, handles the application logic and provides a final data source output for mobile and HTML (frontend) clients
