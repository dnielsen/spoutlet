/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

var ckeditorInitialized = false;

CKEDITOR.editorConfig = function( config )
{
    config.toolbar = 'Basic';

    // this prevents extra buttons from being added on subsequent editors
    if (ckeditorInitialized) {
        return;
    }

    //config.toolbar_Basic[0].push('Source');
    //config.toolbar_Basic[0].push('PasteFromWord');

    ckeditorInitialized = true;
};
