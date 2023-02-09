## About

This module integrates the mmenu jquery plugin with Drupal's menu system with the aim of having an off-canvas mobile menu and a horizontal menu at wider widths. It integrates with your theme's breakpoints to allow you to trigger the display of the horizontal menu at your desired breakpoint. The mobile off-canvas menu is displayed through a toggle icon or with the optional integration of swipe gestures.

## Install

### Bower method:

If you have bower installed you can change directory into the responsive_menu module directory and run `bower install`. This will create a `libraries` directory which you _must_ move to your Drupal root.

### Manual method:

The only library required by this module is the [mmenu](http://mmenu.frebsite.nl) plugin. You need to download the jQuery version and place it in `/libraries` in your docroot (create the directory if you need to). Rename your newly placed download to mmenu, so the resulting path is `/libraries/mmenu`. This module will look in `/libraries/mmenu/dist/js` for the javascript files so ensure you have the correct file structure.

The other two libraries which add functionality (if desired) are the [superfish](https://github.com/joeldbirch/superfish) plugin and the [hammerjs](http://hammerjs.github.io) library. Place those in `/libraries` and rename them to superfish and hammerjs. For superfish you should have a structure like `/libraries/dist/js`, while hammerjs should be simply `/libraries/hammerjs`.

## Configuration

As an administrator visit `/admin/config/user-interface/responsive-menu`

You can set the various options. Some of the options will require the libraries to be present before allowing configuration.

## Block placement

The module provides two blocks, one for the horizontal menu, labeled in the block UI as "Horizontal menu". The other is labeled as "Responsive menu mobile icon" and is the 'burger' menu icon and text which allows the user to toggle the mobile menu open and closed. Both blocks should be placed in an appropriate region, like the header region. The horizontal menu is designed to replace any existing main menu block you might already have in your theme.

## Licenses

The licenses for the libraries used by this module are:

hammerjs: MIT
mmenu: Creative Commons Attribution-NonCommercial
superfish: MIT

The mmenu plugin used to have an MIT license but has changed to the CC NonCommercial license. So you'll need to pay the developer a fee to use it in a commercial web site. Alternatively you can download the earlier MIT licensed version which should be compatible. This module will track the latest stable version.