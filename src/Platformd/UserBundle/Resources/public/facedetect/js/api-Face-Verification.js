function setFaceVerifyURL() {
    var urls = [];

    //urls.push('<font class="url_base">http://' + this.location.host + '/faceverify?</font>');
    urls.push('<font class="url_base">http://map-api.hpl.hp.com/faceverify?</font>');
    urls.push('<font class="url_key">url_pic_source</font>=<font class="url_value">' + $(".txt_source").val() + '</font>');
    var targets = $(".txt_target").val().split(";");
    for (var i = 0; i < targets.length; i++) {
        urls.push('&<font class="url_key">url_pic</font>=<font class="url_value">' + targets[i] + '</font>');
    }

    $("#requestUrl").html(urls.join(""));
}

function faceVerificationValidate() {
    var $source = $(".txt_source");
    var source = $.trim($source.val());
    var $target = $(".txt_target");
    var targetstxt = $.trim($target.val());
    var _max = faceverifiy_max || 200;
    if ($target.length && targetstxt.split(';').length > _max) {
        var box = $(".warning");
        box.find(".warning-box-title").html("Warning");
        box.find(".warning-box-content").html("The number of gallery image files shall not be more than " + _max + ". Please either remove some URLs in the request or <a href='https://console.hpcloud.com/signup'>sign up</a> to make service API calls to avoid such restriction.");
        box.find("input").click(function () {
            box.hide();
        });
        $(".loading").hide();
        box.show();
        return false;
    }
    return true;
}

function faceVerifySubmit() {
    var $source = $(".txt_source");
    var source = $.trim($source.val());
    var $target = $(".txt_target");
    var targetstxt = $.trim($target.val());
    var targets = targetstxt.split(";");
	var type;
	if(targets==''){
		type='post';
	}else{
		type='get';
	}
    //    var content = {
    //        "queryUrl": source,
    //        "targetUrls": {
    //            "url": targets
    //        }
    //    }
    var para = "?url_pic_source=" + source;
    $(targets).each(function (index, item) {
        para += "&url_pic=" + item;

    });

    //var url = "http://15.185.97.217/faceverify";
  //  var url = "/faceverify?";

  //  var url = document.url;
    var url = document.URL;
   //alert(url);   
    ajaxRequest(url, para, type, faceVerifyShow, faildcallback);
}

function setFaceVerifyParameter(parameterType) {
    if (parameterType == "url") {
        $('#parameters').append('<div class="content_parameter_right_title">Query URL:</div>');
        var $source = $('<div class="content_parameter_right_content"><input type="text" class="source_url txt_source"/></div>');
        $('#parameters').append($source);
        $('#parameters').append('<div class="content_parameter_right_title">Gallery URLs:</div>');
        var $target = $('<div class="content_parameter_right_content"><textarea class="textarea txt_target" id="image_url"></textarea></div>');
//        $target.find("textarea").keyup(function (e) {
//            var kcode;
//            if (window.event)// for ie
//            {
//                kcode = e.keyCode;
//            }
//            else if (e.which)// for not ie
//            {
//                kcode = e.which;
//            }
//            if (kcode == "") {
//                if (!faceVerificationValidate()) {
//                    if (event.preventDefault) {
//                        event.preventDefault();
//                        event.stopPropagation();
//                    } else {
//                        event.returnValue = false;
//                        event.cancelBubble = true;
//                    }
//                }
//            }
//        }).blur(function () {
//            return faceVerificationValidate();
//        })


        $('#parameters').append($target);

        //                $("input", "#parameters").val(input);
        //                $("textarea", "#parameters").val(textarea)
        $("#requestUrl").css("background-color", "#fff")

        bindDropedEvent($source, setFaceVerifyURL);
        bindDropedEvent($target, setFaceVerifyURL);
        setFaceVerifyURL();
    }
    else {
        $("#requestUrl").empty();
        $("#requestUrl").css("background-color", "#ddd")
        $('#parameters').append('<div class="content_parameter_right_title">Query Image:</div>');
        var $source = $('<div class="content_parameter_right_content"><div class="uploadFile_box" style="float:none;position:relative"><input class="fileinput" type="text" readonly /><input type="file" name="pic_source" onchange="setfilepath(this);" style="cursor:pointer;position:absolute;left:150px; height:25px;width:100px;z-index:10;opacity:0;filter:alpha(opacity=0)"/><input type="button" class="filebutton" value="Browse"  onclick="selectMyPic(this);" /></div></div>');

        $('#parameters').append($source);
        $('#parameters').append('<div class="content_parameter_right_title">Gallery Images:</div>');
        var $target = $('<div class="content_parameter_right_content"><div class="uploadFile_box_container"><div class="uploadFile_box" style="position:relative"><input class="fileinput" type="text" readonly /><input type="file" name="pic" onchange="setfilepath(this);" style="cursor:pointer;position:absolute;left:150px; height:25px;width:100px;z-index:10;opacity:0;filter:alpha(opacity=0)"/><input type="button" class="filebutton" value="Browse"  onclick="selectMyPic(this);" /></div></div></div>');
        $('#parameters').append($target);
        var $addmore = $('<div class="addmore"><input type="button"   value="Add More" onclick="addmore(this);" /></div>');
        $('#parameters').append($addmore);
        var $removelast = $('<div class="removelast"><input type="button"  disabled="disabled" value="Remove Last" onclick="removelast(this);" /></div>');
        $('#parameters').append($removelast);
    }

}


function remarkHighlightFaceVerification(object, type) {
    var $items = $(".container .line");
    var pic_line = [];
    var face_line = [];
    var matrix_line = [];
    var faceverify_line = [];
    var tmpline = null;

    if (type == "JSON") {
        for (var i = 0; i < $items.length; i++) {
            var $item = $items.eq(i);
            var key = $item.find(".string").html();
            switch (key) {
                case '"pic"':
                    tmpline = pic_line;
                    break;
                case '"face"':
                    tmpline = face_line;
                    break;
                case '"face_matrix"':
                    tmpline = matrix_line;
                    break;
                case '"faceverify"':
                    tmpline = faceverify_line;
                    break;
            }
            tmpline && tmpline.push($item);
        }
        //pic
        $(pic_line).each(function (index, item) {
            if ($(item).find(".plain").html() == "{ ") {
                var $div_c = $('<div class="pic">');
                $(item).before($div_c);
            }
            if ($(item).find(".plain").html() == "} ") {
                var $div_e = $('<div class="pic_end">');
                $(item).after($div_e);
            }
        });

        //face 
        $(face_line).each(function (index, item) {
            if ($(item).find(".plain").html() == "{ ") {
                var $div_c = $('<div class="face">');
                $(item).before($div_c);
            }
            if ($(item).find(".plain").html() == "} ") {
                var $div_e = $('<div class="face_end">');
                $(item).after($div_e);
            }
        });
        //maxtrix 
        $(matrix_line).each(function (index, item) {
            if ($(item).find(".plain").html() == "{ ") {
                var $div_c = $('<div class="matrix">');
                $(item).before($div_c);
            }
            if ($(item).find(".plain").html() == "} ") {
                var $div_e = $('<div class="matrix_end">');
                $(item).after($div_e);
            }
        });
    }
    else {
        var itemcount = 0;
        for (var i = 0; i < $items.length; i++) {
            var $item = $items.eq(i);
            var key = null;
            switch ($item.find(".keyword").html()) {
                case "pic_item":
                    key = "pic"
                    break;
                case "face_item":
                    key = "face"
                    break;
                case "face_matrix_item":
                    key = "matrix"
                    break;
            }
            if (key) {
                if (itemcount++ % 2 == 0) {
                    var $div_c = $('<div class="' + key + '">');
                    $item.before($div_c);
                }
                else {
                    var $div_e = $('<div class="' + key + '_end">');
                    $item.after($div_e);
                }

            }
        }
    }

    $(".container .pic").each(function (index, item) {
        $(item).append($(item).nextUntil(".pic,.pic_end"));
    });

    $(".container .face").each(function (index, item) {
        $(item).append($(item).nextUntil(".face,.face_end"));
    });

    $(".container .matrix").each(function (index, item) {
        $(item).append($(item).nextUntil(".matrix,.matrix_end"));
    });


    //add id to the code box
    //pic
    var $pics = $(".container .pic");
    for (var i = 0; i < object.pic.length; i++) {
        $pics.eq(i).attr("pid", object.pic[i].id_pic);
    }
    //face
    var $faces = $(".container .face");
    for (var i = 0; i < object.face.length; i++) {
        $faces.eq(i).attr("fid", object.face[i].id_face);
    }
    //matrix
    var $matrixes = $(".container .matrix");
    for (var i = 0; i < object.face_matrix.length; i++) {
        $matrixes.eq(i).attr("face1", object.face_matrix[i].id_face1).attr("face2", object.face_matrix[i].id_face2).attr("sim", object.face_matrix[i].sim);
    }
    var $picContainer = $(".content_images");

    $pics.hover(function () {
        $picContainer.find('img[pid="' + $(this).attr("pid") + '"]').parent().css({ "border": "2px solid red", "padding": "0px" });
        $(this).css({ "background-color": "#bbb" });
    }, function () {
        $picContainer.find('img[pid="' + $(this).attr("pid") + '"]').parent().css({ "border": "1px solid #444", "padding": "1px" })
        $(this).css({ "background-color": "whiteSmoke" });
    });

    $faces.hover(function () {
        $picContainer.find('div[fid="' + $(this).attr("fid") + '"]').css("border-width", "2px");
        $(this).css({ "background-color": "#bbb" });
    }, function () {
        $picContainer.find('div[fid="' + $(this).attr("fid") + '"]').css("border-width", "0px");
        $(this).css({ "background-color": "whiteSmoke" });
    });

    $matrixes.hover(function () {
        var $item = $(this);
      
        $(this).css({ "background-color": "#bbb" });
        $picContainer.find('.sourcefacebox[fid="' + $item.attr("face1") + '"]').css("border-width", "2px");
        $picContainer.find('.targetfacebox[fid="' + $item.attr("face2") + '"]').css("border-width", "2px");
        $picContainer.find('.facesimilarity[fid="' + $item.attr("face2") + '"]').html(formatnum($item.attr("sim")));
    }, function () {
        $picContainer.find(".sourcefacebox, .targetfacebox").css("border-width", "2px");
        $picContainer.find('.facesimilarity').html("");
        $(this).css({ "background-color": "whiteSmoke" });
    });
}

function dispname(response){
	$('.tip2').append ("Is this "+response+" ?");
//        return 1;
}

function faceVerifyShow(responseText) {
	if(responseText.body){
		responseText=responseText.body.firstChild.innerHTML;
		responseText=responseText.replace(/\&lt;/g,'<').replace(/\&gt;/g,'>');
		function createXml(str) {
			if ( document.all ) {
				var xmlDom = new ActiveXObject("Microsoft.XMLDOM");
				xmlDom.loadXML(str);
				return xmlDom;
			}
			else {
				return new DOMParser().parseFromString(str,"text/xml");
			}
		}
		responseText = createXml(responseText);
	};
    $(".loading").hide();

    showFormatData(responseText);

    var response = null;
    if ($("#response_format").val() == "XML") {
        response = $.xml2json(responseText);
        response.pic = response.pic.pic_item;
        response.face = response.face.face_item;
        response.face_matrix = response.face_matrix.face_matrix_item;
    }
    else {
        response = responseText;
    }

    // var base_url = "http://h52f1fb19ff2faf32caa0e791c47c1b6e.cdn.hpcloudsvc.com/";
    $(".content_images").append('<div class="topimgbox" ></div><div class="bottomimgbox"></div>');

    //draw source pic
    var sourceurl = base_url + response.faceverify.id_pic_source;
    var $img = $('<img onload="imgload(this)" >');
    var sourcePicId =response.faceverify.id_pic_source;
    $img.attr("pid", response.faceverify.id_pic_source);
    $img.attr("src", sourceurl + '_s');
    var $div = $('<div class="sourceimgbox" style="border: 1px solid rgb(68, 68, 68);"></div>');
    $div.append($img);
    $(".topimgbox").append($div);


    //draw target pic
    var dispImage = 0;facePrint = 0;
    for (var i = 0; i < response.pic.length; i++) {
        var item = response.pic[i];
      
       // if (item.id_pic == response.faceverify.id_pic_source) {
         if(item.id_pic == sourcePicId) {
          
            var $samepic = $(response.face_matrix).filter(function (index, matrix) {
                if (matrix.id_face1 == matrix.id_face2) {
                    dispImage = 1;  
                    facePrint = matrix.id_face1;
                    var sameface = $(response.face).filter(function (index, face) {
                        return face.id_pic == item.id_pic;
                    });
                    return sameface.length;
                }
            });
            if ($samepic.length == 0) {
                continue;
            }
        
        var $img = $("img[src='" + base_url + item.id_pic + "_s']", ".bottomimgbox");
        if ($img.length == 0) {
            $img = $('<img onload="imgload(this)" >');
            $img.attr("src", base_url + item.id_pic + '_s');
            var $div = $('<div class="targetimgbox " style="border: 1px solid rgb(68, 68, 68);"></div>');
            $img.attr("pid", item.id_pic);
            $div.append($img);
            $(".bottomimgbox").append($div);
        } 
     }
       
     }       

    

    if(dispImage == 0){	
            $('.tip').html('No match Found');
            $(":button[value=submit]").css('display','none');
    } else {
        // if match found
       var url1 = document.URL;
       var param = "?url_face="+facePrint;
       //alert(url);
       ajaxRequest(url1, param, "post", dispname, faildcallback);

         $('.tip').hide();
         $('.tip2').show();
     } 
    
    //draw source face box
    var rate = 1;
    var imgwidth = 240;
    var imgheight = 240;
    var $sourcePicData = $(response.pic).filter(function (index, item) {
        return item.id_pic == response.faceverify.id_pic_source;
    });

    if ($sourcePicData[0].width / imgwidth > $sourcePicData[0].height / imgheight) {
        if (imgheight > 240 && imgwidth > 320) {
            imgwidth = 320;
        }
        rate = imgwidth / $sourcePicData[0].width;
        if (rate > 1) {
            rate = 1;
            imgwidth = $sourcePicData[0].width;
            imgheight = $sourcePicData[0].height
        } else {
            imgheight = $sourcePicData[0].height * rate;
        }


    }
    else {
        rate = imgheight / $sourcePicData[0].height;
        if (imgheight > 240 && imgwidth > 320) {
            imgheight = 240;
        }
        if (rate > 1) {
            rate = 1;
            imgwidth = $sourcePicData[0].width;
            imgheight = $sourcePicData[0].height
        } else {
            imgwidth = $sourcePicData[0].width * rate;
        }
    }

    var $sourceFaceData = $(response.face).filter(function (index, item) {
        return item.id_pic == response.faceverify.id_pic_source;
    });
    var $sourceImg = $(".topimgbox .sourceimgbox img");
    var box = $sourceImg.parent();
    var faceId ;
    $sourceFaceData.each(function (index, item) {
        var top = item.bb_top * rate + (parseInt($sourceImg.css("max-height")) - imgheight) / 2
        var left = item.bb_left * rate + (parseInt($sourceImg.css("max-width")) - imgwidth) / 2
        var height = (item.bb_bottom - item.bb_top) * rate;
        var width = (item.bb_right - item.bb_left) * rate;
        faceId = item.id_face;
        
        // var group = item.bb_top + "_" + item.bb_left;
        box.append('<div  class="sourcefacebox"  fid="' + item.id_face + '" style="left:' + (left + 1) + 'px;top:' + (top + 1) + 'px;width:' + width + 'px; height:' + height + 'px;border-color:white;border-style:solid;border-width:2px"></div>')
        box.append('<div  class="sourcefacebox"  fid="' + item.id_face + '" style="left:' + left + 'px;top:' + top + 'px;width:' + width + 'px; height:' + height + 'px;border-color:red;border-style:solid;border-width:2px"></div>')
    });

  
   if(faceId == 'undefined' || faceId == null) {
      $('.tip').html('No face Found');
      $(":button[value=submit]").css('display','none');
      $(".bottomimgbox").css('display','none');

    }
  
    //draw target face box
    $(response.face).each(function (index, item) {
       if (faceId == item.id_face) {
        var $targetImg = $('.bottomimgbox img[pid=' + item.id_pic + ']');
        if ($targetImg.length > 0) {
            var rate = 1;
            var imgwidth = 144;
            var imgheight = 144;
            var $targetPicData = $(response.pic).filter(function (index, item2) {
                return item.id_pic == item2.id_pic;
            });

            if ($targetPicData[0].width / imgwidth > $targetPicData[0].height / imgheight) {
                rate = imgwidth / $targetPicData[0].width;
                imgheight = $targetPicData[0].height * rate;
            }
            else {
                rate = imgheight / $targetPicData[0].height;
                imgwidth = $targetPicData[0].width * rate;
            }

            var top = item.bb_top * rate + (parseInt($targetImg.css("max-height")) - imgheight) / 2
            var left = item.bb_left * rate + (parseInt($targetImg.css("max-width")) - imgwidth) / 2
            var height = (item.bb_bottom - item.bb_top) * rate;
            var width = (item.bb_right - item.bb_left) * rate;
            var box = $targetImg.parent();

            box.append('<div  class="targetfacebox" fid="' + item.id_face + '"  style="left:' + (left + 1) + 'px;top:' + (top + 1) + 'px;width:' + width + 'px; height:' + height + 'px;border-width:2px;border-color:white"></div>')
            box.append('<div  class="targetfacebox" fid="' + item.id_face + '" style="left:' + left + 'px;top:' + top + 'px;width:' + width + 'px; height:' + height + 'px;border-color:red;border-width:2px"></div>')
            box.append('<div   class="facesimilarity" fid="' + item.id_face + '" style="left:' + (left + 1) + 'px;top:' + (top + height + 11) + 'px;width:' + width + 'px; color:white"></div>')
            box.append('<div   class="facesimilarity" fid="' + item.id_face + '" style="left:' + left + 'px;top:' + (top + height + 10) + 'px;width:' + width + 'px;color:red "></div>')
        }
}
    });


    //mark relation
    var $sourcecontainer = $(".topimgbox");
    var $targetcontainer = $(".bottomimgbox");
    $(response.face_matrix).each(function (index, item) {
        var $sourcebox = $sourcecontainer.find('div[fid=' + item.id_face1 + ']');
        var $targetbox = null;
        var sourceface = null;
        var targetface = null;
        if ($sourcebox.length == 0) {
            $sourcebox = $sourcecontainer.find('div[fid=' + item.id_face2 + ']');
            $targetbox = $targetcontainer.find('div[fid=' + item.id_face1 + ']');
            sourceface = item.id_face2;
            targetface = item.id_face1;

        } else {
            $targetbox = $targetcontainer.find('div[fid=' + item.id_face2 + ']');
            sourceface = item.id_face1;
            targetface = item.id_face2;
        }
        var sourcedata = $sourcebox.data("matrix");
        if (!sourcedata) {
            sourcedata = [];
            $sourcebox.data("matrix", sourcedata);
        }
        var targetdata = $targetbox.data("matrix");
        if (!targetdata) {
            targetdata = [];
            $targetbox.data("matrix", targetdata);
        }
        var data = {
            "source": sourceface,
            "target": targetface,
            "sim": item.sim
        }
        //make sure that face1 is the source face and face2 is the targetface.
        item.id_face1 = sourceface;
        item.id_face2 = targetface;

        sourcedata.push(data);
        targetdata.push(data);
    });

   

    // user hovers over a face in a source picturourcecontainer.find("div[fid=" + $(this).attr("fid") + "]").css("border-width", "2px");

  

    $sourcecontainer.find(".sourcefacebox").hover(function () {

        $(".syntaxhighlighter .container").find('div[fid="' + $(this).attr("fid") + '"]').css({ "background-color": "#bbb" });
        //source
        $sourcecontainer.find("div[fid=" + $(this).attr("fid") + "]").css("border-width", "2px");

        if ($(this).data("matrix") && $(this).data("matrix").length) {
            var data = $(this).data("matrix")[0];

            //target
            var $facebox = $targetcontainer.find(".targetfacebox").filter(function (index, item) {
                var targetdata = $(item).data("matrix");
                var flag = false;
                for (var i = 0; targetdata && i < targetdata.length; i++) {
                    if (targetdata[i].source == data.source) {
                        flag = true;
                        break;
                    }
                }
                return flag;
            });
            $facebox.css("border-width", "2px");
            //target facesimilarity
            $targetcontainer.find(".facesimilarity").each(function (index, item) {
                var targetdata = $(item).data("matrix");
                for (var i = 0; targetdata && i < targetdata.length; i++) {
                    if (targetdata[i].source == data.source) {
                        $(item).html(formatnum(targetdata[i].sim));
                        break;
                    }
                }
            });
        }
    }, function () {
        $(".syntaxhighlighter .container").find('div[fid="' + $(this).attr("fid") + '"]').css({ "background-color": "whiteSmoke" });
        //source
        $sourcecontainer.find("div[fid=" + $(this).attr("fid") + "]").css("border-width", "2px");

        if ($(this).data("matrix") && $(this).data("matrix").length) {
            var data = $(this).data("matrix")[0];

            //target
            var $facebox = $targetcontainer.find(".targetfacebox").filter(function (index, item) {

                var targetdata = $(item).data("matrix");
                var flag = false;
                for (var i = 0; targetdata && i < targetdata.length; i++) {
                    if (targetdata[i].source == data.source) {
                        flag = true;
                        break;
                    }
                }
                return flag;
            });
            $facebox.css("border-width", "2px");

            //target  facesimilarity
            $targetcontainer.find(".facesimilarity").html("");
        }
    });

    $targetcontainer.find(".targetfacebox").hover(function () {
        $(this).parent().find('.targetfacebox[fid=' + $(this).attr("fid") + ']').css("border-width", "2px");
        $(".syntaxhighlighter .container").find('div[fid="' + $(this).attr("fid") + '"]').css({ "background-color": "#bbb" });
    }, function () {
        $(this).parent().find('.targetfacebox[fid=' + $(this).attr("fid") + ']').css("border-width", "2px");
        $(".syntaxhighlighter .container").find('div[fid="' + $(this).attr("fid") + '"]').css({ "background-color": "whiteSmoke" });
    });

    $(".content_images img").hover(function () {
        $(".syntaxhighlighter .container").find('div[pid="' + $(this).attr("pid") + '"]').css({ "background-color": "#bbb" });
    }, function () {
        $(".syntaxhighlighter .container").find('div[pid="' + $(this).attr("pid") + '"]').css({ "background-color": "whiteSmoke" });
    });

    remarkHighlightFaceVerification(response, $("#response_format").val());

}
