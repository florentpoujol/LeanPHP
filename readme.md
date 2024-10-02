# LeanPHP

TL;DR: this is my "build your own framework for fun" project.

---

This is a pet/educational project not unlike to my much older [PHP Standard components](https://github.com/florentpoujol/PHP-Standard-Components) where I implement a library of various small and straightforward components, and use it with to build a full-featured application (a simple CMS).

The main points of the project are:
- build an app with the library but try not to create a reusable framework, at least for now
- build components that makes sens to me, that are simple to use, implemented in a straightforward way
- introduce new components when I need them to build the app 
- have as few dependencies as possible: implement almost everything myself from scratch (but I still haven't made my mind on caring about PSR interfaces or not) 
- have code that run with maximum strictness (PHPStan level max + strict types)

The library, which would be a separate composer package in "real-life" is in the `library` folder.  
It's like so to have both the app and the library in the same repo to simplify things.

The documentation of the components can be found in the `docs` folder.

All other folders are ones you could find in an application.