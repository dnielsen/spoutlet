//build the code result.
function showFormatData(data) {
    var response = null;
    if ($("#response_format").val() == "XML") {
        var str = XMLtoString(data);
        //highlight the code with Sytaxhighlight
        brush = new SyntaxHighlighter.brushes["Xml"]();
        brush.init({ toolbar: false });
        $(".jsonFormat").html(brush.getHtml(formatXml(str)));
        $(".stringFormat").html(replaceAll(str, "<", "&lt"));
    }
    else {
        var str = JSON.stringify(data);
        var strJSON = formatJson(str);
        //highlight the code with Sytaxhighlight
        brush = new SyntaxHighlighter.brushes["JScript"]();
        brush.init({ toolbar: false });
        $(".stringFormat").html(JSON.stringify(str));
        $(".jsonFormat").html(brush.getHtml(strJSON));
    }

    $(".tip").show();
    if ($("#check").is(":checked")) {
        $(".stringFormat").hide();
        $(".jsonFormat").show();
    }
    else {
        $(".stringFormat").show();
        $(".jsonFormat").hide();
    }
}