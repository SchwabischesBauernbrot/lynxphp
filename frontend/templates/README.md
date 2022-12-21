# templates scope and definition

First we need to define the split between CSS and HTML

## CSS scope

CSS should control padding/margins, spacing

## CSS structural

These are CSS elements that are structural to make the layouts work: floats, flex, display, visibility, content, overflow, position

## CSS Theme scope

CSS should control colors and fonts, maybe embed a image of background for flavor

## Template scope

Templates allow control over the HTML so an admin can control the look. But we have to retain an element of NO-js support, it is possible for templates to insert certain tags like </form> that could breaks NO-js.

We want to allow editors to be able to edit the style of links without affecting what the link links to, or how many links there are.


