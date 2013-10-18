
var roicache = {
    _array: [],
    add: function (roi_n) {

        var roi = this.get(roi_n.id);
        if (!roi) {
            this._array.push(roi_n);
        } else {
            roi.roi = roi_n.roi;
        }

    },
    get: function (id) {
        var rois = $(this._array).filter(function (i, o) {
            return o.id == id;
        });
        if (rois.length > 0) {
            return rois[0];
        }
        else {
            return null;
        }
    },
    getPids: function () {
        var pids = [];
        for (var i = 0; i < this._array.length; i++) {
            pids.push(this._array[i].id);
        }
        return pids;
    }
};

var weightcache = {
    _array: [],
    add: function (data) {
        var id = data.id;
        var weight = this.get(id);
        if (!weight) {
            this._array.push(data);
        } else {
            weight.weight = data.weight;
        }

    },
    get: function (id) {
        var weights = $(this._array).filter(function (i, o) {
            return o.id == id;
        });
        if (weights.length > 0) {
            return weights[0];
        }
        else {
            return null;
        }
    },
    getPids: function () {
        var pids = [];
        for (var i = 0; i < this._array.length; i++) {
            pids.push(this._array[i].id);
        }
        return pids;
    }
};



function photocollage() {
    var _widget = {
        points: null,
        data: null,
        canvas: null,
        container: null,
        bordercontainer: null,
        image: null,
        silder: null,
        isselect: false, // flag of select the end point of the line.
        selectIndex: -1, // point index of the points array
        imagewidth: 300,
        imageheight: 300,
        imageloaded: false,
        roiChanged: false,
        pid: null,
        create: function ($container, width, height, loadingimgurl, hasSilder,
				canEdit) {
            var self = this;
            // self.points = points;

            $($container).empty();
            var container = self.bordercontainer = $('<div style="width:'
					+ width
					+ 'px;height:'
					+ height
					+ 'px; border:1px solid #333;" />');
            $($container).append(container);
            if (hasSilder) {
                height = height - 32;
            }
            self.createWidget(container, width - 4, height - 4, loadingimgurl);
            if (hasSilder) {
                // add slider to the conainer
                var htmltxt = [];
                htmltxt.push('<div style="width:' + Number(width - 40)
						+ 'px; height: 30px;margin: auto 10px;">');
                htmltxt
						.push('<div style="width: 50px; height: 30px; line-height: 30px; float:left;text-align:center;">weight:</div>');
                htmltxt
						.push('<div id="EditImage_silderamount" style="height:20px;  line-height:20px;margin-left:80px;text-align:center;"></div>');
                htmltxt
						.push('  <div id="EditImage_silder" style="height: 5px; margin-left:80px;"></div></div>');
                $(container).append(htmltxt.join(""));
                var slider = self.silder = $("#EditImage_silder");
                slider.slider({
                    range: "min",
                    value: 50,
                    min: 1,
                    max: 100,
                    slide: function (event, ui) {
                        $("#EditImage_silderamount").html(ui.value / 100);
                    }
                });
                $("#EditImage_silderamount").html(slider.slider("value") / 100);
                $("#EditImage_silder a").css("height", "8px");
                $("#EditImage_silder a").css("margin-top", "1px");
            }
            if (canEdit) {
                self.container.bind("mousedown", function (e) {
                    self.mousedown(e);
                });
                self.container.bind("mousemove", function (e) {
                    self.mousemove(e);
                });
                $(document).bind("mouseup", function (e) {
                    self.mouseup(e);
                });
            }
        },
        createWidget: function (container, width, height, loadingimgurl) {
            var self = this;
            var containerex = $('<div style="position:relative;z-index:1;width:'
					+ width
					+ 'px;height:'
					+ height
					+ 'px;background:url('
					+ loadingimgurl + ') center center no-repeat;"/>');
            self.container = $('<div style="position:absolute;z-index:1;"/>');
            containerex.append(self.container);
            self.image = $('<img onload="$(this).parent().parent().css(\'background\',\'none\');" style="position:absolute;max-height:' + height
					+ 'px; max-width:' + width + 'px">');
            self.container.append(self.image);
            self.canvas = $('<canvas style="position:absolute;z-index:2;top:0;left:0;"></canvas>');
            self.container.append(self.canvas);

            self.image.bind("load", function () {
                // move the image to the center of container
                self.image.show();
                self.imagewidth = $(this).width();
                self.imageheight = $(this).height();
                self.canvas.attr("width", self.imagewidth);
                self.canvas.attr("height", self.imageheight);
                var offsetx = (width - self.imagewidth) / 2;
                var offsety = (height - self.imageheight) / 2;
                $(self.container).css("left", offsetx + 2);
                $(self.container).css("top", offsety + 2);
                self.imageloaded = true;
                if (self.data && self.data.id == self.pid) {
                    self.modifyPoints(self.data);
                    self.drawShape();
                } else {
                    self.data = null;
                    self.points = null;
                }
            });

            $(container).append(containerex);
            // self.image.get(0).src = loadingimgurl;
        },
        mousedown: function (e) {
            var self = this;
            if (!self.points) {
                return;
            }
            var list = $(self.points).filter(function (i, o) {
                var x = e.clientX - $(self.container).offset().left;
                var y = e.clientY - $(self.container).offset().top;
                if ((o.x - x) * (o.x - x) + (o.y - y) * (o.y - y) <= 25) {
                    self.selectIndex = i;
                    return true;
                } else {
                    return false;
                }
            });

            if (list.length == 0) {
                self.selectIndex = -1;
                return;
            } else {
                self.isselect = true;
            }
        },
        mousemove: function (e) {
            var self = this;
            if (!self.points) {
                return;
            }
            if (self.isselect && self.selectIndex >= 0) {
                var x = e.clientX - $(self.container).offset().left;
                var y = e.clientY - $(self.container).offset().top;
                self.points[self.selectIndex] = {
                    x: x,
                    y: y
                };
                var rate = self.data.size.width / self.imagewidth;
                self.data.roi[self.selectIndex] = {
                    x: x * rate,
                    y: y * rate
                };
                if ($.browser.msie && Number($.browser.version) < 9) {
                    self.movePoint(self.selectIndex);
                } else {
                    self.drawShape();
                }
                self.roiChanged = true;
            }
        },
        mouseup: function () {
            var self = this;
            self.isselect = false;
        },
        showImage: function (imageurl, data) {
            var self = this;
            self.data = data;
            if (self.image.get(0).src != imageurl) {
                self.clearROI();
            }
            self.roiChanged = false;
            self.image.hide();
            self.image.get(0).src = imageurl;

        },
        drawShape: function (data) {
            var self = this;

            if (data) {
                self.modifyPoints(data);
            } else if (!self.points) {
                self.modifyPoints(self.data);
            }
            if ($.browser.msie && Number($.browser.version) < 9) {
                self.drawVML(self.points);
                // self.drawDiv(self.points);
            } else {
                self.drawCanvas(self.points);
            }
        },
        setPoints: function (data) {
            var self = this;
            self.data = data;
        },
        modifyPoints: function (data) {
            if (data.roi.length < 3) {
                return;
            }
            var self = this;
            var width = data.size.width;
            var height = data.size.height;
            var ratex = self.imagewidth * 1.0 / width;
            var ratey = self.imageheight * 1.0 / height;
            var ps = [];
            for (var i = 0; i < data.roi.length; i++) {
                ps.push({
                    x: data.roi[i].x * ratex,
                    y: data.roi[i].y * ratey
                });
            }
            self.points = ps;
            return self.points;
        },
        setSliderValue: function (value) {
            var slider = $("#EditImage_silder").slider({
                value: value
            });
            $("#EditImage_silderamount").html(value / 100);
        },
        getSliderValue: function () {
            var self = this;
            if (self.silder) {
                return self.silder.slider("value");
            } else {
                return 0;
            }
        },
        setImage: function (pid, url) {
            var self = this;
            self.pid = pid;
            self.imageloaded = false;
            self.roiChanged = false;
            if (self.image.get(0).src != url) {
                self.clearROI();
            }
            self.image.hide();
            self.image.get(0).src = url;
        },
        drawCanvas: function (points) {
            var self = this;
            var canvas = self.canvas.get(0);
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);

            var x = points[0].x;
            var y = points[0].y;
            ctx.strokeStyle = "#FF0000";
            ctx.fillStyle = "#FFFF00";
            // draw line
            ctx.beginPath();
            ctx.moveTo(x, y);
            for (var i = 1; i < points.length; i++) {
                ctx.lineTo(points[i].x, points[i].y);

            }
            ctx.closePath();
            ctx.stroke();

            // draw peak point
            ctx.beginPath();
            for (var j = 0; j < points.length; j++) {
                ctx.moveTo(points[j].x, points[j].y);
                ctx.arc(points[j].x, points[j].y, 5, 0, Math.PI * 2, true);
            }
            ctx.fill();
        },
        drawDiv: function (points) {
            var self = this;
            this.canvas.hide();
            $("div", self.container).remove();
            for (var i = 0; i < points.length - 1; i++) {
                self.drawLineDIV(points[i], points[i + 1]);
            }
            self.drawLineDIV(points[points.length - 1], points[0]);
        },
        drawLineDIV: function (p1, p2) {
            var self = this;
            var parent = self.container;

            var rate = Math.abs((p1.x - p2.x) / (p1.y - p2.y));
            var x0 = p1.x;
            var y0 = p1.y;
            var stepx = 0;
            var stepy = 0;
            var htmltxt = [];
            if (rate > 1) {
                stepy = ((p1.y - p2.y) > 0 ? -1 : 1) / rate;
                stepx = ((p1.x - p2.x) > 0 ? -1 : 1);
                htmltxt.push('<div class="peak" style="left:' + parseInt(x0 - 4) + 'px; top:' + parseInt(y0 - 4) + 'px" />');
                for (var i = 0; i < Math.abs(p1.x - p2.x) ; i++) {
                    htmltxt.push('<div class="point" style="left:' + parseInt(x0 + i * stepx) + 'px; top:' + parseInt(y0 + i * stepy) + 'px" />');
                }
            } else {
                stepy = ((p1.y - p2.y) > 0 ? -1 : 1);
                stepx = rate * ((p1.x - p2.x) > 0 ? -1 : 1);
                htmltxt.push('<div class="peak" style="left:' + parseInt(x0 - 4) + 'px; top:' + parseInt(y0 - 4) + 'px" />');
                for (var j = 1; j < Math.abs(p1.y - p2.y) ; j++) {
                    htmltxt.push('<div class="point" style="left:' + parseInt(x0 + j * stepx) + 'px; top:' + parseInt(y0 + j * stepy) + 'px" />');
                }
            }
            parent.append(htmltxt.join(''));
        },
        drawVML: function (points) {
            var self = this;
            var parent = self.container;
            this.canvas.hide();
            $("div", parent).remove();
            $("line", parent).remove();
            $("oval", parent).remove();
            $("stroke", parent).remove();
            document.namespaces.add('v', 'urn:schemas-microsoft-com:vml');
            for (var i = 0; i < points.length - 1; i++) {
                self.drawLineVML(i, i + 1);
            }
            self.drawLineVML(points.length - 1, 0);

            //            $("line", self.container).css("behavior", "url(#default#VML)");
            //            $("stroke", self.container).css("behavior", "url(#default#VML)");
            //            $("oval", self.container).css("behavior", "url(#default#VML)");


            $("line").css("behavior", "url(#default#VML)");
            $("stroke").css("behavior", "url(#default#VML)");
            $("oval").css("behavior", "url(#default#VML)");

            setTimeout(function () {
                $("line").each(function (index, item) {

                    item.from = $(item).attr("_from");
                    item.to = $(item).attr("_to");
                });
            }, 10);
        },
        drawLineVML: function (pointAindex, pointBindex) {
            var self = this;
            var i = pointAindex;
            var j = pointBindex;
            var p1 = self.points[i];
            var p2 = self.points[j];
            var parent = self.container;
            var htmltxt = [];
            htmltxt.push('<v:line style="position:absolute;z-index:2;left:0;top:0;" f="' + i + '" t="' + j + '" strokecolor = "Red" _from="' + p1.x + "," + p1.y + '" _to="' + p2.x + "," + p2.y + '"></v:line>');
            //            var line = $('<v:line style="position:absolute;z-index:2;left:0;top:0;" f="' + i + '" t="' + j + '" strokecolor = "Red"></v:line>');
            //            line.attr("from" ,p1.x + "px," + p1.y + "px");
            //            line.attr("to", p2.x + "px," + p2.y+"px");
            //            parent.append(line);
            //            line.get(0).from = p1.x + "," + p1.y + "";
            //            line.get(0).to = p2.x + "," + p2.y + "";
            //            line.get(0).from.value = p1.x + "," + p1.y + "";
            //            line.get(0).to.value = p2.x + "," + p2.y + "";

            htmltxt.push('<v:oval   did="oval_' + i + '" style="position:absolute;z-index:3;left:' + Number(p1.x - 5) + 'px;top:' + Number(p1.y - 5) + 'px;width:10px;height:10px;" fillcolor="yellow"/>');
            parent.append(htmltxt.join(""));

        },
        movePoint: function (pointindex) {
            var self = this;
            var list1 = $("line").filter(function (i, o) {
                return o.f == self.selectIndex;
            });
            list1.get(0).from = self.points[pointindex].x + ","
					+ self.points[pointindex].y;
            // list1.attr("from", "'" + self.points[pointindex].x + "," +
            // self.points[pointindex].y + "'");
            var list2 = $("line").filter(function (i, o) {
                return o.t == self.selectIndex;
            });
            // list2.attr("to", "'" + self.points[pointindex].x + "," +
            // self.points[pointindex].y + "'");
            list2.get(0).to = self.points[pointindex].x + ","
					+ self.points[pointindex].y;
            var list3 = $("oval").filter(function (i, o) {
                return o.did == "oval_" + self.selectIndex;
            });
            list3.css("left", self.points[pointindex].x - 5);
            list3.css("top", self.points[pointindex].y - 5);

            // $('line[f=' + pointindex + ']').attr("from",
            // self.points[pointindex].x + "," + self.points[pointindex].y);
            // $('line[t=' + pointindex + ']').attr("to",
            // self.points[pointindex].x + "," + self.points[pointindex].y);

            // var oval = $('oval[did="oval_' + pointindex + '"]');
            // oval.css("left", self.points[pointindex].x - 5);
            // oval.css("top", self.points[pointindex].y - 5);
        },
        clearROI: function () {
            var self = this;
            if ($.browser.msie && Number($.browser.version) < 9) {
                var parent = self.container;
                $("div", parent).remove();
                $("line", parent).remove();
                $("oval", parent).remove();
                $("stroke", parent).remove();
            } else {
                var canvas = self.canvas.get(0);
                var ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
            }

        },
        getPid: function () {
            var self = this;
            return self.pid;
        },
        getROIChanged: function () {
            var self = this;
            return self.roiChanged;
        },
        getROI: function () {
            var self = this;
            // var rate = self.data.size.width / self.imagewidth;
            // for ( var i = 0; i < self.points.lenght; i++) {
            // self.data.roi[i] = {
            // x : self.points[i].x * rate,
            // y : self.points[i].y * rate
            // }
            // }
            return self.data;
        },
        clear: function () {
            var self = this;
            self.image.hide();
            self.clearROI();
            self.data = null;
            self.points = null;
            roicache._array.length = 0;
            weightcache._array.length = 0;

        },
        show: function () {
            var self = this;
            $(self.bordercontainer).css("visibility", "visible");
        },
        hide: function () {
            var self = this;
            $(self.bordercontainer).css("visibility", "hidden");
        },
        clearCurrentinfo: function () {
            this.pid = null;
            this.image.get(0).src = "";
        }

    };
    this.EditImage = {

        create: function ($container, width, height, loadingimgurl,
					hasSilder, canEdit) {
            _widget.create($container, width, height, loadingimgurl,
						hasSilder, canEdit);
        },
        setSliderValue: function (value) {
            _widget.setSliderValue(value * 100);
        },
        getSliderValue: function () {
            return _widget.getSliderValue() / 100.0;
        },
        setImage: function (pid, url) {
            _widget.setImage(pid, url);
        },
        drawShape: function (data) {
            _widget.drawShape(data);
        },
        showImage: function (imageurl, data) {
            _widget.showImage(imageurl, data);
        },
        setPoints: function (data) {
            _widget.setPoints(data);
        },
        getImageLoadedStatue: function () {
            return _widget.imageloaded;
        },
        clearROI: function () {
            _widget.clearROI();
        },
        getPid: function () {
            return _widget.getPid();
        },
        getROIChanged: function () {
            return _widget.getROIChanged();
        },
        getROI: function () {
            return _widget.getROI();
        },
        clear: function () {
            _widget.clear();
        },
        hide: function () {
            _widget.hide();
        },
        show: function () {
            _widget.show();
        },
        clearCurrentinfo: function () {
            _widget.clearCurrentinfo();
        }
    };

}
