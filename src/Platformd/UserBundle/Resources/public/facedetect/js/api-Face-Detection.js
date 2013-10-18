function setFaceDetectionURL() {
    var urls = [];
   // urls.push('<font class="url_base">http://' + this.location.host + '/facedetect?</font>');
    urls.push('<font class="url_base">http://map-api.hpl.hp.com/facedetect?</font>');
    urls.push('<font class="url_key">url_pic</font>=<font class="url_value">' + $(".txt_source").val() + '</font>');
    $("#requestUrl").html(urls.join(""));
}

function faceDetectionSubmit() {
    var sourcetext = $.trim($(".txt_source").val());
    
	var type;
    var sources = sourcetext.split(";");
	if(sources==''){
		type='post';
	}else{
		type='get';
	}
    //var content = "url_pic=" + sources;
   // http://h5926e0c7296f55bd19c9ce2d388c71a3.cdn.hpcloudsvc.com/44d2b96e72d297f3676e608aefd7bf50dee6a4a6
    //var url_pic = "url_pic=https://region-a.geo-1.objects.hpcloudsvc.com:443/v1/10873218563681/cloudcamp/44d2b96e72d297f3676e608aefd7bf50dee6a4a6";
	  //var content = "url_pic=" + url_pic;
   // var content= url_pic;
    var content = "?url_pic=" + sources;
    //var content = "url_pic=https://region-a.geo-1.objects.hpcloudsvc.com:443/v1/10873218563681/cloudcamp/images/avatars/0820afb3-c4ce-437f-8056-e0fb76ed739e";
    
    var url = "";
//    var url = "http://"+ window.location.host+"/account/profile/avatars/facedetect/";
    var url = document.URL;
  //  alert(url);
    //var url = "http://15.185.97.217/facedetect?";
    //var url =   "/facedetect?";

    
    ajaxRequest(url, content, type, faceDetectionShow, faildcallback);
    
    
}

function faceDetectionShow(responseText) {
	
  if(responseText.body){
//  alert('sdsin'); 
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
        response.face = response.face.face_item;
    }
    else {
        response = responseText;
    }
  
    // var base_url = "http://h52f1fb19ff2faf32caa0e791c47c1b6e.cdn.hpcloudsvc.com/";
    $(".content_images").append('<div class="topimgbox" ></div><div class="bottomimgbox"></div>');

    //draw source pic
    var sourceurl = base_url + response.pic.id_pic;
    var $img = $('<img onload="imgload(this)" >');
    $img.attr("pid", response.pic.id_pic);
    $img.attr("src", sourceurl + '_s');
    var $div = $('<div class="sourceimgbox" style="border: 1px solid rgb(68, 68, 68);"></div>');
    $div.append($img);
    $(".topimgbox").append($div);

    //draw source face box
    var rate = 1;
    var imgwidth = 240;
    var imgheight = 240;

    if (response.pic.width / imgwidth > response.pic.height / imgheight) {
        if (imgheight > 240 && imgwidth > 320) {
            imgwidth = 320;
        }
        rate = imgwidth / response.pic.width;
        if (rate > 1) {
            rate = 1;
            imgwidth = response.pic.width;
            imgheight = response.pic.height
        } else {
            imgheight = response.pic.height * rate;
        }


    }
    else {
        rate = imgheight / response.pic.height;
        if (imgheight > 240 && imgwidth > 320) {
            imgheight = 240;
        }
        if (rate > 1) {
            rate = 1;
            imgwidth = response.pic.width;
            imgheight = response.pic.height
        } else {
            imgwidth = response.pic.width * rate;
        }
    }

    var $sourceFaceData = $(response.face);
    var $sourceImg = $(".topimgbox .sourceimgbox img");
    var box = $sourceImg.parent();
    var faceId;
    var faceprintImage = '';
    $sourceFaceData.each(function (index, item) {
        var top = item.bb_top * rate + (parseInt($sourceImg.css("max-height")) - imgheight) / 2
        var left = item.bb_left * rate + (parseInt($sourceImg.css("max-width")) - imgwidth) / 2
        var height = (item.bb_bottom - item.bb_top) * rate;
        var width = (item.bb_right - item.bb_left) * rate;
        // var group = item.bb_top + "_" + item.bb_left;
        box.append('<div  class="sourcefacebox"  fid="' + item.id_face + '" style="left:' + (left + 1) + 'px;top:' + (top + 1) + 'px;width:' + width + 'px; height:' + height + 'px;border-color:white;border-style:solid;border-width:2px"></div>')
        box.append('<div  class="sourcefacebox"  fid="' + item.id_face + '" style="left:' + left + 'px;top:' + top + 'px;width:' + width + 'px; height:' + height + 'px;border-color:red;border-style:solid;border-width:2px"></div>')
     faceId = item.id_face;
     faceprintImage = item.id_pic;
    });
    
    if(faceId == 'undefined' || faceId == null) {
    
      $('.tip').html('No face Found');
    	$(":button[value=submit]").css('display','none');
    }else {
      $('.tip').html('Is this Your Face?');
     }
    $("#parameters").append('<input type="hidden" id="facePrintId" name="" value="' +faceId +'" ><input type="hidden" id="facePrintImage" name="" value="' +faceprintImage+'" >');
   
    $(".content_images img").hover(function () {
        $(".syntaxhighlighter .container").find('div[pid="' + $(this).attr("pid") + '"]').css({ "background-color": "#bbb" });
       // $(this).parent().css({ "border": "2px solid red", "padding": "0px" });
    }, function () {
        $(".syntaxhighlighter .container").find('div[pid="' + $(this).attr("pid") + '"]').css({ "background-color": "whiteSmoke" });
        //$(this).parent().css({ "border": "0px solid red", "padding": "2px" });
    });
    $sourcecontainer = $(".topimgbox");
    $(".topimgbox .sourcefacebox").hover(function () {

        $(".syntaxhighlighter .container").find('div[fid="' + $(this).attr("fid") + '"]').css({ "background-color": "#bbb" });
        //source
        $sourcecontainer.find("div[fid=" + $(this).attr("fid") + "]").css("border-width", "2px");
    }, function () {
  //      $(".syntaxhighlighter .container").find('div[fid="' + $(this).attr("fid") + '"]').css({ "background-color": "whiteSmoke" });
        //source
//      $sourcecontainer.find("div[fid=" + $(this).attr("fid") + "]").css("border-width", "0px");

    });

    remarkHighlightFaceDetection(response, $("#response_format").val());

}


function remarkHighlightFaceDetection(object, type) {
    var $items = $(".container .line");
    var pic_line = [];
    var face_line = [];
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
            }
            tmpline && tmpline.push($item);
        }
        //pic
        $(pic_line).each(function (index, item) {
            if ($(item).find(".plain").html() == ":{ ") {
                var $div_c = $('<div class="pic">');
                $(item).before($div_c);
            }
            if ($(item).find(".plain").html() == "}, ") {
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

    }
    else {
        var itemcount = 0;
        for (var i = 0; i < $items.length; i++) {
            var $item = $items.eq(i);
            var key = null;
            switch ($item.find(".keyword").html()) {
                case "pic":
                    key = "pic"
                    break;
                case "face_item":
                    key = "face"
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

    //add id to the code box
    //pic
    var $pics = $(".container .pic");
    $pics.attr("pid", object.pic.id_pic);

    //face
    var $faces = $(".container .face");
    if ($.isArray(object.face)) {
        for (var i = 0; i < object.face.length; i++) {
            $faces.eq(i).attr("fid", object.face[i].id_face);
        }
    } else {
        $faces.eq(0).attr("fid", object.face.id_face);
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

}
