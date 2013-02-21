/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

var ckeditorInitialized = false;

CKEDITOR.editorConfig = function( config )
{
    config.toolbar = 'AdminToolbar';

    config.toolbar_AdminToolbar =
    [
        { name: 'styles', items : [ 'Format','Font','FontSize' ] },
        { name: 'colors', items : [ 'TextColor','BGColor' ] },
        { name: 'tools', items : [ 'ShowBlocks' ] },
        { name: 'document', items : [ 'Source','-', 'Preview' ] },
        { name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
        { name: 'editing', items : [ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ] },
        '/',
        { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
        { name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote',
        '-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ] },
        { name: 'links', items : [ 'Link','Unlink' ] },
        { name: 'insert', items : [ 'Image','Table','HorizontalRule' ] }
    ];
};
