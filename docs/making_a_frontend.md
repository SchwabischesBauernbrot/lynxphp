## Where to look

all the HTML/CSS/JS is only in the frontend, the backend has no effect on look/feel, backend is supposed to be purely data.

The templates are in frontend/templates, they're using a tag style called mustache that uses double brace for tags, i.e. {{title}}

## Loops and sections

Then we have called loops (or sections), that let you stuff similar templates together:

```
Any content before a loop is marked as "header"
<!-- loop -->
any content here is called "loop0"
<!-- end -->
<!-- loop -->
any content here is called "loop1" and so forth
<!-- end -->
Any content after a loop is called "footer"
```
