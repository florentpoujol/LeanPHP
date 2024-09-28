# Smoll PHP

## What is Smol

Smol is two pieces:
- **a library** (smolPHP/library) that contain many standard components useful to build almost full-featured web applications 
- **a starter project** (smolPHP/skeleton) that is the starting point of what will become your app, that show you some example of how to piece together these components to build a working app

The code of the library has these main characteristics:
- it is small, and straightforward. The components are on purpose not as full-featured as Symfony components for instance, but are enough for the 80% use case and are much smaller and thus simpler to use.
- it is highly strictly typed, most of the components pass PHPStan at the highest strictness without shenanigans

There is no framework, inside, or separate from the library.

The skeleton project show you one way to structure an app that uses these components, but everything is your code, you can change and reorganize any part of it as long as you know how to do things.

This design choice has a few interesting consequences that in our opinion make this project stand apart from framework-based ones:
- you have full freedom and control over the code in your project and how you structure it, both for the business code but also for the infrastructure/glue code that is usually mandated by the framework
- since you are in control of the code and most components are straightforward, your project will be significantly simpler to understand, setup, maintain, evolve, for every member of the team no matter their level of experience
- out of that, you actually get good developer experience and somewhat speed of runtime

------------------------------------
What is SmolPHP

## Philosophies

- less code overall
- less production dependencies
- fully strictly typed
- simple code and project

## A library

SmolPHP/Library is a collection of many familiar components useful to build an almost full-featured web application.

By design the component's code is small and straightforward.   
Their feature set is on purpose limited to strike a good balance between the size and the complexity of the code, while still filling 80% of the use cases.

The whole library also fully strictly typed, pass PHPStan at the highest level without shenanigans.

One of the philosophies of Smol is to reduce the amount of production dependencies to a minimum, by providing a bare-bone-but-enough implementation for as many components and tools as possible.    
But the documentation and the skeleton don't shy away to point to more full-featured alternatives, which are often Symfony components, or widely used libraries like PHP dotenv or Monolog for instance.
The documentation, where applicable, also show you how to do the equivalent of features that the components do not provide directly.

Also, some components are inherently sensitive, complex or simple enough that we can not meaningfully improve on what already exists. As such, Smol do not provide an implementation for PSR-7 objects, an HTTP router, a full-fledged ORM or view templates for instance.


## A skeleton

SmallPHP/Skeleton is a familiar-looking starter project for a web application built mostly on the library's components. 

But the skeleton shows only one way to structure a project. A key characteristics of Smol that sets it apart from other projects is that **there is no framework**.

You have absolutely full control over how you structure the project or bootstrap your app for instance.  
You can create your own starter project if you want to and know what to do.







