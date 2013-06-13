var jCrop = null;

var h = null;
var w = null;
var x = null;
var y = null;

$(window).load(function(){
    var oh = $('#crop-target').height();
    var ow = $('#crop-target').width();

    var s = 150;

    if ( oh < s || ow < s )
        s = Math.min(oh, ow);

    var x = ((ow - s) / 2);
    var y = ((oh - s) / 2);

    var initialH = $('#crop-target').attr('data-oh');
    var initialW = $('#crop-target').attr('data-ow');

    jCrop = $.Jcrop('#crop-target',{
        aspectRatio: 1,
        onChange: preview,
        onSelect: preview,
        boxWidth: 512,
        boxHeight: 512
    });

    jCrop.setSelect([ 0, 0, s, s ]);
    useallofimage();
});

function useallofimage() {
    var h = $('#crop-target').height();
    var w = $('#crop-target').width();
    if ( h > w )
        var minside = w;
    else
        var minside = h;

    h1 = ( h - minside ) / 2; w1 = ( w - minside ) / 2;
    jCrop.animateTo([ w1, h1, (w1+w), (h1+h)]);
}

function preview(c) {
    if ( c.w == 0 || c.h == 0 ) {
        jCrop.setSelect([ x, y, x+w, y+h ]);
        jCrop.animateTo([ x, y, x+w, y+h ]);
        return;
    }
    x = c.x;
    y = c.y;
    w = c.w;
    h = c.h;
    preview1(c);
    preview2(c);
}

function preview1 (coords) {
    var rx = 84 / coords.w;
    var ry = 84 / coords.h;
    $('#preview1').css({
        width: Math.round(rx * $('#crop-target').width() ) + 'px',
        height: Math.round(ry *  $('#crop-target').height()) + 'px',
        marginLeft: '-' + Math.round(rx * coords.x) + 'px',
        marginTop: '-' + Math.round(ry * coords.y) + 'px'
    });
}

function preview2 (coords) {
    var rx = 184 / coords.w;
    var ry = 184 / coords.h;
    $('#preview2').css({
        width: Math.round(rx * $('#crop-target').width() ) + 'px',
        height: Math.round(ry *  $('#crop-target').height()) + 'px',
        marginLeft: '-' + Math.round(rx * coords.x) + 'px',
        marginTop: '-' + Math.round(ry * coords.y) + 'px'
    });
}

function getCoords() {
    var selection = jCrop.tellSelect();
    return Math.round(selection.w) + ',' + Math.round(Math.round(selection.h)) + ',' + Math.round(selection.x) + ',' + Math.round(selection.y);
}
