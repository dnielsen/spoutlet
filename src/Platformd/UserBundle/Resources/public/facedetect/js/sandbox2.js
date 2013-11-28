function getCurrentStyle(node) {
    var style = null;
    if (window.getComputedStyle) {
        style = window.getComputedStyle(node, null);
    } else {
        style = node.currentStyle;
    }
    return style;
}

function imgload(obj) {
    //move the image to the center of the parent box.
    setTimeout(function () {
        if (parseInt($(obj).height()) < parseInt($(obj).css("max-height"))) {
            $(obj).css("margin-top", (parseInt($(obj).css("max-height")) - parseInt($(obj).height())) / 2);
        }else{
			$(obj).css('margin-top',3);
		}  
    }, 50);

}

function formatnum(num) {
    //rounding
    return num >= 1 ? Math.round(num * 100) / 100 :
    "." + Math.round(num * 100)

}

function clearData() {
    //clear the code and image result
    $(".jsonFormat").empty();
    $(".stringFormat").empty();
    $(".content_images").empty();

}

$(document).ready(function () {

    /*************** init method and response type  *******************/
    if (this.location.hash && this.location.hash != "") {
        $('#method').val(replaceAll(this.location.hash.substr(1), "%20", " "));

    } else {
        $('#method').val("Face Verification");
    }
    $('#method').find("option").eq(0).remove();
    $('#response_format').val("JSON");
    $("[name=parameterType]:first").attr("checked", "checked");

    /*************** init the slider  *******************/
    // Comment to load Sample Images
    //loadSampleImage();

    //SetParameters();


    /*************** init method and response type  *******************/

    //format json function
    $('#check').change(function () {
        if (this.checked) {
            $(".jsonFormat").show();
            $(".tip").show();
            $(".stringFormat").hide();
        } else {
            $(".jsonFormat").hide();
            $(".stringFormat").show();
        };
    });

    //when changed method change the ui
    $('#method').change(function () {
        window.location.hash = $('#method').val();
        loadSampleImage();
        SetParameters();
    });

    //when changed method change the ui
    $('input[name=parameterType]').change(function () {
        SetParameters();
    });

    //call method  listener
    $('#call_method').click(function () {
        //$('#form1').submit(function () {
        var method = $('#method option:selected').text();
        var parameterType = $("input[name=parameterType]:checked").val();
        if (parameterType == "url") {
            //validate the parameter when parameter type is url
            var $source = $(".txt_source");
            var source = $.trim($source.val());
            var $target = $(".txt_target");
            var targetstxt = $.trim($target.val());

            if (($source.length && source.length == 0) || ($target.length && targetstxt.length == 0)) {
                alert("ERROR: Please provide values for the input parameters.");
                return;
            }

        } else {
            //validate the parameter when parameter type is file
            var $file_s = $("input[name=pic_source]");
            var flag_s = true && $file_s.length;
            for (var i = 0; i < $file_s.length; i++) {
                if ($file_s[i].value != "") {
                    flag_s = false;
                    break;
                }
            }

            var $file_t = $("input[name=pic]");
            var flag_t = true && $file_t.length;
            for (var i = 0; i < $file_t.length; i++) {
                if ($file_t[i].value != "") {
                    flag_t = false;
                    break;
                }
            }

            if (flag_s || flag_t) {
                alert("please input the parameters firstly.");
                return;
            }
			for(var i=0; i<$('.fileinput').length; i++){
				var value=$($('.fileinput')[i]).val();
				if(value=="[Optional]"){
					continue;
				}
				if(value==""){	
					alert("ERROR: Please provide values for the input parameters.");
					return false;
				}
				var testFlag,noticeText;
				if(method=='Face Detection' || method == 'Face Verification'||method == 'Face Demographic'){
					testFlag=!/\.(gif|jpg|jpeg|GIF|JPG)$/.test(value);
					noticeText="The selected file could not be uploaded. Only JPEG and GIF images are allowed.";
				}else{
					testFlag=!/\.(gif|jpg|jpeg|png|GIF|JPG|PNG)$/.test(value);
					noticeText="The selected file could not be uploaded. Only JPEG, PNG and GIF images are allowed.";
				};
				if(testFlag){
					var box = $(".warning");
					box.find(".warning-box-title").html("Warning");
					box.find(".warning-box-content").html(noticeText);
					box.find("input").click(function () {
						box.hide();
					}).val("OK");
					box.show();
					return false;
				}
			}
			
        }
        //clear the result box.
        clearData();
        // show loading 
        $(".loading").show();
		
        //submit request.

        switch (method) {
            case 'Face Verification':
                faceVerificationValidate() && faceVerifySubmit();
                return false;
            case 'Sharpness':
                sharpnessSubmit();
                return false;
            case 'Auto Crop':
                autoCropSubmit();
                return false;
            case 'Face Detection':
                faceDetectionSubmit();
                return false;
			case 'Face Demographic':
				demographicSubmit();
				return false;
            case 'Time Clustering':
                timeClusteringSubmit();
                return false;
            case 'Duplicate Resource':
                duplicateResourceSubmit();
                return false;
            case 'Feature Extraction':
                //featureExtractionSubmit();
                //return false;
            case 'Image Matching':
               //photoMatchingSubmit();
               // return false;
            case 'Image Collage':
                //photoCollageValidate() && photoCollageSubmit();
                //return false;
            default:
                return false;
        }
    });
  $( "#call_method" ).trigger( "click" );

});

//buid parameters box
function SetParameters() {


    $(".slide_bar .dragIcon").removeClass("selectIcon");
    var method = $("#method").val();
    var targetName = "input";
    var setURL = null;
    //    var input = $("input", "#parameters").val();
    //    var textarea = $("textarea", "#parameters").val();
    var parameterType = $("input[name=parameterType]:checked").val();
    $('#parameters').empty();
    switch (method) {
        case 'Face Verification':

            setFaceVerifyParameter(parameterType);

            //set parameter label txt.
            $(".parameterType label:first").html("Use Image URLs");
            $(".parameterType label:last").html("Upload Files");
            $('.slide_back_temp').remove();

            return;
        case 'Sharpness':
            targetName = "input";
            setURL = setSharpnessURL;
            $('.slide_back_temp').remove();
            break;
        case 'Auto Crop':
            targetName = "input";
            setURL = setAutoCropURL;
            $('.slide_back_temp').remove();
            break;
        case 'Face Detection':
            targetName = "input";
            setURL = setFaceDetectionURL;
            $('.slide_back_temp').remove();
            break;
		case 'Face Demographic':
			targetName = "input";
			setURL=setDemographicURL;
			$('.slide_back_temp').remove();
			break;
        case 'Time Clustering':
            targetName = "textarea";
            setURL = setTimeClusteringURL;
            $('.slide_back_temp').remove();
            break;
        case 'Duplicate Resource':
            targetName = "textarea";
            setURL = setDuplicateResourceURL;
            $('.slide_back_temp').remove();
            break;
        case 'Feature Extraction':
            targetName = "input";
            setURL = setFeatureExtractionURL;
            $('.slide_back_temp').remove();
            break;
        case 'Image Matching':
            $('.slide_back_temp').remove();

            setImageMatchingParameter(parameterType);

            $(".parameterType label:first").html("Use Image URLs");
            $(".parameterType label:last").html("Upload Files");
            return;
        case 'Image Collage':

            setImageCollageParameter(parameterType);
       

            //set parameter label txt.
            $(".parameterType label:first").html("Use Image URLs");
            $(".parameterType label:last").html("Upload Files");

            return;
    }
    //set parameter label txt.
    if (targetName == "input") {
        $(".parameterType label:first").html("Use Image URL");
        $(".parameterType label:last").html("Upload File");
    } else {
        $(".parameterType label:first").html("Use Image URLs");
        $(".parameterType label:last").html("Upload Files");
    }
    if (parameterType == "url") {
        $("#requestUrl").css("background-color", "#fff")
        $('#parameters').append('<div class="content_parameter_right_title">' + (targetName == "textarea" ? " URLs" : "URL") + '</div>');
        var $source = $('<div class="content_parameter_right_content"><' + targetName + ' type="text" class="source_url txt_source textarea"/></div>');
        $('#parameters').append($source);

        //        $("input", "#parameters").val(input);
        //        $("textarea", "#parameters").val(textarea)
        setURL();
        bindDropedEvent($source, setURL);
    }
    else {
        $("#requestUrl").empty();
        $("#requestUrl").css("background-color", "#ddd")
        if (targetName == "input") {
            $('#parameters').append('<div class="content_parameter_right_title">Image:</div>');
            var $source = $('<div class="content_parameter_right_content"><div class="uploadFile_box" style="float:none;position:relative"><input class="fileinput" type="text" readonly /><input type="file" name="pic" onchange="setfilepath(this);" style="cursor:pointer;position:absolute;left:150px; height:25px;width:100px;z-index:10;opacity:0;filter:alpha(opacity=0)"/><input type="button" class="filebutton" value="Browse"  onclick="selectMyPic(this);" /></div></div>');
            $('#parameters').append($source);
        }
        else {
            $('#parameters').append('<div class="content_parameter_right_title">Images:</div>');
            var $target = $('<div class="content_parameter_right_content"><div class="uploadFile_box_container"><div class="uploadFile_box"><input class="fileinput" type="text" readonly /><input type="file" name="pic" onchange="setfilepath(this);"style="cursor:pointer;position:absolute;left:150px;height:25px;width:100px;z-index:10;opacity:0;filter:alpha(opacity=0)"/><input type="button" class="filebutton" value="Browse"  onclick="selectMyPic(this);" /></div></div></div>');
            $('#parameters').append($target);
            var $addmore = $('<div class="addmore"><input type="button"   value="Add More" onclick="addmore(this);" /></div>');
            $('#parameters').append($addmore);
            var $removelast = $('<div class="removelast"><input type="button" disabled="disabled"  value="Remove Last" onclick="removelast(this);" /></div>');
            $('#parameters').append($removelast);
        }

    }
}

$('textarea').live('keyup',function(){
	checkUrlLength(this);
}).live('blur',function(){
	checkUrlLength(this);
});


function checkUrlLength(elem){
	var method = $("#method").val();
	switch(method){
	    case 'Face Verification':
	        if ($(elem).val().split(";").length > faceverifiy_max) {
	            var count = faceverifiy_max;
	            var urlArr = $(elem).val().split(';');
	            var newValue = '';
	            for (var i = 0; i < faceverifiy_max; i++) {
	                newValue += urlArr[i] + ';';
	            }
	            $(elem).val(newValue.substr(0, newValue.length - 1));
	            faceVerificationValidate();
	            //showWarning(count);
	            return;
	        }
	        break;
		case 'Image Collage':
			if($(elem).val().split(";").length>imageCollage_max){
				var count=imageCollage_max;
				var urlArr=$(elem).val().split(';');
				var newValue='';
				for(var i=0;i<imageCollage_max;i++){
					newValue+=urlArr[i]+';';
				}
				$(elem).val(newValue.substr(0,newValue.length-1));
				photoCollageValidate()
                //showWarning(count);
				return;
			} 
			break;
		default:
			break;
	}
}
function bindDropedEvent($obj, setURL) {
    $obj.children().droppable({
        activeClass: "ui-state-active",
        hoverClass: "ui-state-hover",
        drop: function (event, ui) {
            if (this.tagName.toLowerCase() == "input") {
                if ($(this).hasClass("background_url")) {
                    $(this).val(ui.draggable.attr("src"));
                }
                else {
                    $(this).val(ui.draggable.attr("src").substring(0, ui.draggable.attr("src").length - 1) + "m");
                }
            }
            else {
                var $this = $(this);
                var urls = $this.val();
                var addurl = ui.draggable.attr("src").substring(0, ui.draggable.attr("src").length - 1) + "m";
                if (urls.indexOf(addurl) < 0) {
					var method = $("#method").val();
					switch(method){
						case 'Face Verification':
							if($(this).val().split(";").length>=faceverifiy_max){
								var count=faceverifiy_max;
								showWarning(count);
								return;
							}else{
								$(this).val(urls + (urls.length > 0 ? ";" : "") + addurl);
							}; 
							break;
						case 'Sharpness':
							
							break;
						case 'Auto Crop':
							
							break;
						case 'Face Detection':
							
							break;
						case 'Time Clustering':
							
							break;
						case 'Face Demographic':
						
							break;
						case 'Duplicate Resource':
							
							break;
						case 'Feature Extraction':
							
							break;
						case 'Image Matching':
							
						break;
						case 'Image Collage':
							if($(this).val().split(";").length>=imageCollage_max){
								var count=imageCollage_max;
								showWarning(count);
								return;
							}else{
								$(this).val(urls + (urls.length > 0 ? ";" : "") + addurl);
							}; 
							break;
						default:
							break;
				
					}
					
                };
            }

            $(".slide_bar .dragIcon").removeClass("selectIcon");
            // ui.draggable.next().addClass("selectIcon");

            $(".ui-droppable").each(function (index, item) {
                var urls = $(item).val().split(";");
                $(urls).each(function (index, item) {
                    $(".slide_bar img[src='" + item.substring(0, item.length - 1) + "s" + "']").next().addClass("selectIcon");
                });
            })


            setURL();
        }
    }).blur(function () { setURL(); });
}
//show limite warning
function showWarning(count){
	var box = $(".warning");
    box.find(".warning-box-title").html("Warning");
    box.find(".warning-box-content").html("The number of gallery image files shall not be more than " + count + ". Please <a href='https://console.hpcloud.com/signup'>sign up</a> to make service API calls to avoid such restriction.");
    box.find("input").click(function () {
        box.hide();
    }).val("OK");
    box.show();
}
//the real request.
function ajaxRequest(url, para, type, sc, fc) {
    if ($("#response_format").val() == "XML") {
        var datatype = "xml";
    }
    else {
        var datatype = "json";
    }
    type = type || "get";

    var parameterType = $("input[name=parameterType]:checked").val();
	if(type=="post" && $.browser.msie){
		$('#response_format').attr('name','accept_type');
	}
	
    if (parameterType == "url") {
        // submit data as ajax
        $.ajax({
            "type": type,
            "url": url + para,
            // headers:{"X-Auth-Token":"HPAuth_50514e6ce4b0cc37e98d5079"},
            contentType: "application/json",
            // data: JSON.stringify(data),
            dataType: datatype,
            processData: false,
            success: sc,
            error: fc
        });

        //sc(faceverification);
    }
    else {
        var options = {
            //target: '#output2',   // target element(s) to be updated with server response 
            //beforeSubmit: showRequest,  // pre-submit callback 
            "success": sc,  // post-submit callback 
            "error": fc,
            // other available options: 
            "url": url,         // override for form's 'action' attribute 
            "type": type,     // 'get' or 'post', override for form's 'method' attribute 
            "dataType": datatype        // 'xml', 'script', or 'json' (expected server response type) 
            //clearForm: true        // clear all form fields after successful submit 
            //resetForm: true        // reset the form after successful submit 

            // $.ajax options can be used here too, for example: 
            //timeout:   3000 
        };
		
        // inside event callbacks 'this' is the DOM element so we first 
        // wrap it in a jQuery object and then invoke ajaxSubmit 
        $("#form1").attr('method',type).ajaxSubmit(options);

        // !!! Important !!! 
        // always return false to prevent standard browserFile submit and page navigation 
         return false;
    }
}


function faildcallback(ret) {
    if (ret && ret.status == 500) {
        try {
            var obj = JSON.parse(ret.responseText);
            if (obj.code && obj.code == 100) {
                var box = $(".warning");
                box.find(".warning-box-title").html("Warning");
                box.find(".warning-box-content").html("The number of request allowed for one day has reached the limit. Please either try at a different day or <a href='https://console.hpcloud.com/signup'>sign up</a> to make service API calls to avoid such restriction");
                box.find("input").click(function () {
                    box.hide();
                }).val("OK");
                box.show();
            } else {
                alert(ret.status + "--" + obj.message);
            }
        }
        catch (ex) {
            alert(ret.status + "--" + ret.responseText);
        }
    }
    else if (ret && ret.status == 200) {
            alert(ret.status + "--" + ret.responseText);
    }
    else {
        ret && alert(ret.status + "--" + ret.statusText);
    }
    $(".loading").hide();

}

function replaceAll(source, replace, replacewith) {
    var r = new RegExp(replace.replace(/([\(\)\[\]\{\}\^\$\+\-\*\?\.\"\'\|\/\\])/g, "\\$1"), "ig");
    return source.replace(r, replacewith);
}

function selectMyPic(obj) {
    $(obj).prev().trigger("click");
}

function setfilepath(obj) {
    $(obj).prev().val($(obj).val());
}

function addmore(obj) {
    //    
    //    var tmp = $parent.prev().clone();
    var $c = $(".uploadFile_box_container");
    var _max = 200;
    var content = "The number of gallery image files shall not be more than " + _max + ". Please <a href='https://console.hpcloud.com/signup'>sign up</a> to make service API calls to avoid such restriction."
    var method = $('#method option:selected').text();
    switch (method) {
        case 'Face Verification':
            _max = faceverifiy_max || 200;
            content = "The number of gallery image files shall not be more than " + _max + ". Please <a href='https://console.hpcloud.com/signup'>sign up</a> to make service API calls to avoid such restriction."
            break;
        case 'Image Collage':
            _max = imageCollage_max || 200;
            content = "The number of gallery image files shall not be more than " + _max + ". Please <a href='https://console.hpcloud.com/signup'>sign up</a> to make service API calls to avoid such restriction."
            break;
        default:
            break;
    }

    if ($c.children().length >= _max) {
        var box = $(".warning");
        box.find(".warning-box-title").html("Warning");
        box.find(".warning-box-content").html(content);
        box.find("input").click(function () {
            box.hide();
        });
        $(".loading").hide();
        box.show();
        $(obj).attr("disabled", "disabled");
        return;
    }

    var $tmp = $c.find(".uploadFile_box:first").clone();
    $tmp.find("input:[type=text],input[type=file]").val("");


    $c.append($tmp);
    var $parent = $(obj).parent();
    $parent.next().find("input").removeAttr("disabled");
}

function removelast(obj) {
    var $parent = $(obj).parent();

    var $c = $(".uploadFile_box_container");
    $c.find(".uploadFile_box:last").remove();
    if ($c.children().length == 1) {
        $(obj).attr("disabled", "disabled");
    }
    $parent.prev().find("input").removeAttr("disabled");
}

function loadSampleImage() {
  //alert('called sample Image');
    var method = $("#method").val();
    var images = null;
    switch (method) {
        case 'Face Verification':
            images = _imageIds_map;
            break;
        case 'Sharpness':
            images = _imageIds_map;
            break;
        case 'Auto Crop':
            images = _imageIds_map;
            break;
        case 'Face Detection':
            images = _imageIds_map;
            break;
		case 'Face Demographic':
			images=_imageIds_map;
			break;
        case 'Time Clustering':
            images = _imageIds_map;
            break;
        case 'Duplicate Resource':
            images = _imageIds_map;
            break;
        case 'Feature Extraction':
            images = _imageIds_feature_extraction;
            break;
        case 'Image Matching':
            images = _imageIds_image_matching;
            break;
        case 'Image Collage':
            images = _imageIds_map;
            break;
        default:
            images = _imageIds_map;
            break;
    }
    var $ul = $("<ul>");
    $(images).each(function (index, item) {
        $ul.append('<li><img src="' + base_url + item + '_s" class="sampleimg" alt="" onload="imgload(this)"><div class="dragIcon"></div></li>');
    })
    $(".slide_bar").empty().append($ul);
    var count = parseInt($('.content_top').width() / 100) - 1;
    var totalCount = $('.slide_bar li').length;
    for (var i = 0; i < totalCount; i++) {
        $($('.slide_bar li')[i]).attr('id', 'list_' + i);
    }
    if (totalCount <= count) {
        count = totalCount;
        $('.next,.prev').addClass('clickDisable');
    } else {
        $('.prev').addClass('clickDisable');
    }
    $('.slide_bar').jCarouselLite({
        btnNext: ".next",
        btnPrev: ".prev",
        circular: false,
        visible: count,
        beforeStart: function (a) { },
        afterEnd: function (a) {
            if (totalCount <= count) {
                return;
            } else {
                var id = $(a).attr('id');
                var index = id.charAt(id.length - 1)
                if (index > 0) {
                    $('.prev').removeClass('clickDisable');
                    $('.prev').addClass('clickEnable');
                } else if (index == 0) {
                    $('.prev').removeClass('clickEnable');
                    $('.prev').addClass('clickDisable');
                }
                if (totalCount - index == count) {
                    $('.next').removeClass('clickEnable');
                    $('.next').addClass('clickDisable');
                } else {
                    $('.next').removeClass('clickDisable');
                    $('.next').addClass('clickEnable');
                }
            }
        }
    });
    $('.slide_bar').css("z-index", "0");

    //init example image drag event. we should delay this function,otherwise it will be invalid. 
    setTimeout(function () {
        $("li img", ".slide_bar").draggable({
            cancel: "a.ui-icon", // clicking an icon won't initiate dragging
            revert: "invalid", // when not dropped, the item will revert back to its initial position
            containment: "#main-container .content_container", // stick to demo-frame if present
            helper: "clone",
            cursor: "move",
            opacity: 0.5,
            appendTo: "#main-container .content_container"
        });
    }, 100);
}
