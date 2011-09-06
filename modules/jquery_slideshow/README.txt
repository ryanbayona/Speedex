// $Id: README.txt,v 1.2.2.1 2009/06/24 18:08:34 spydor Exp $

DESCRIPTION
-----------

This module creates a javascript slide show out of multiple CCK imagefield images. It has some settings to change the speed and transition preferences. 

REQUIREMENTS
------------

 * Drupal 6
 * jquery_plugin
 * imagecache
 * imagefield 
 * jquery_update


INSTALLATION
------------

Install the module.
Create a Content Type using Imagefield, be sure to allow multiple imagefields per node.
Create an Image cache preset with the desired settings.
Go to the Jquery Slideshows settings page (/admin/content/jquery-slideshows) and pick your settings.
Go to the CCK Display settings (/admin/content/types/<content_type_name>/display) and select "JQuery Slideshow : image cache preset" substituting 'image cache preset' for the desired preset.

Now you can create that content type. 


Troubleshooting
---------------

If it doesn't create a slideshow check to make sure you have the file:
modules/jquery_update/jquery.cycle.min.js

If not you can grab it from this location.
http://drupal.org/node/328641



CREDITS
-------

Originally written by Shane Stillwell (spydor).
Ported to Drupal 6 by Lee Rowlands (larowlan).
