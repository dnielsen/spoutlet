/*
 Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.html or http://ckeditor.com/license
 */

var ckeditorInitialized = false;

CKEDITOR.editorConfig = function (config) {
    config.toolbar = 'Basic';

    // this prevents extra buttons from being added on subsequent editors
    if (ckeditorInitialized) {
        return;
    }

    //config.toolbar_Basic[0].push('Source');
    //config.toolbar_Basic[0].push('PasteFromWord');

    ckeditorInitialized = true;
};

CKEDITOR.on('dialogDefinition', function (ev) {
    // Take the dialog name and its definition from the event data.
    var dialogName = ev.data.name;
    var dialogDefinition = ev.data.definition;

    // Check if the definition is from the dialog we're
    // interested on (the Link dialog).
    if (dialogName == 'link') {
        // FCKConfig.LinkDlgHideAdvanced = true
        dialogDefinition.removeContents('advanced');

        // FCKConfig.LinkDlgHideTarget = true
        dialogDefinition.removeContents('target');


        // Get a reference to the 'Link Info' tab.
        var infoTab = dialogDefinition.getContents('info');

        // Remove unnecessary widgets from the 'Link Info' tab.
        infoTab.remove('linkType');
        infoTab.remove('protocol');
        /*
         Enable this part only if you don't remove the 'target' tab in the previous block.

         // FCKConfig.DefaultLinkTarget = '_blank'
         // Get a reference to the "Target" tab.
         var targetTab = dialogDefinition.getContents( 'target' );
         // Set the default value for the URL field.
         var targetField = targetTab.get( 'linkTargetType' );
         targetField[ 'default' ] = '_blank';
         */
    }

//		if ( dialogName == 'image' )
//		{
//			// FCKConfig.ImageDlgHideAdvanced = true
//			dialogDefinition.removeContents( 'advanced' );
//			// FCKConfig.ImageDlgHideLink = true
//			dialogDefinition.removeContents( 'Link' );
//		}
//
//		if ( dialogName == 'flash' )
//		{
//			// FCKConfig.FlashDlgHideAdvanced = true
//			dialogDefinition.removeContents( 'advanced' );
//		}

});
