$(function() {
    var $form       = $('form[name="wr-cover"]'),
        $fileInput  = $form.find('input[type="file"]'),
        $fileSubmit = $form.find('input[type="submit"]');

    $fileSubmit.hide();

    $fileInput.on('change', function() {
        var fileName = $(this).val();

        if ( fileName !== '' ) {
            $fileSubmit.show();
        }
    });

});

// only run this code for when the canvas is present
if ( $('#canvas').length ) {

    $(window).on( 'load', function() {

        canvas = window.__canvas = new fabric.Canvas('canvas');
        canvas.controlsAboveOverlay  = true;
        canvas.backgroundColor = '#fff';

        // make the canvas a responsive width
        var windowWidth     = $(window).width(),
            mainWidth       = $('#container .main').width(), // width of our left hand main container
            sidebarWidth    = $('#sidebar').width();

        var responsiveScale = 1;

        // if the mainWidth is less than our default canvas size, lets make some changes
        if ( mainWidth < mask.width ) {
            responsiveScale   = mainWidth / mask.width;
            canvas.setWidth( mainWidth - 10 );
            canvas.setHeight( responsiveScale * mask.height );
        } else {
            canvas.setWidth( mask.width );
            canvas.setHeight( mask.height );
        }
        // lets make the editor controls solid for all
        fabric.Object.prototype.transparentCorners = false;

        var maskImage   = new Image();

        maskImage.crossOrigin   = 'anonymous';
        maskImage.onload    = function () {

            // add frame/cover
            fabric.Image.fromURL( mask.src, function( img ) {
                mask = img.set({
                    lockScalingFlip: true,
                    lockUniScaling: true,
                    left: ( canvas.width / 2 ) - ( (img.width / 2) * responsiveScale ),
                    top: ( canvas.height / 2 ) - ( (img.height / 2) * responsiveScale ),
                    width: ( responsiveScale !== 1 ) ? ( responsiveScale * mask.width ) : mask.width,
                    height: ( responsiveScale !== 1 ) ? ( responsiveScale * mask.height ) : mask.height,
                    angle: 0,
                    evented: true,
                    selectable: false,
                    opacity: mask.opacity,
                    scale: responsiveScale
                });

                canvas.setOverlayImage( mask, canvas.renderAll.bind(canvas) );
            });
        };
        maskImage.src   = mask.src;

        // add user generated image
        fabric.Image.fromURL( userImage.src, function( img ) {

            var scale = (img.height > img.width) ? userImage.height / img.height : userImage.width / img.width;

            var imgWidth    = img.width;
            var imgHeight   = img.height;

            if ( imgWidth > ( canvas.width - 75 ) ) {
                img.scaleToWidth( canvas.width - 75 );
            } else if ( imgHeight > ( canvas.height - 75 ) ) {
                img.scaleToHeight( canvas.height - 75 );
            }

            img.set({
                lockScalingFlip: true,
                lockUniScaling: true,
                lockScalingFlip: userImage.lockScalingFlip,
                lockUniScaling: userImage.lockUniScaling,
                angle: userImage.angle,
                opacity: userImage.opacity,
                scale: userImage.scale,
                evented: userImage.evented,
                hasControls: userImage.hasControls,
                selectable: userImage.selectable,
                rotationIncrement: userImage.rotationIncrement,
                movementIncrement: userImage.movementIncrement,
                scaleIncrement: userImage.scaleIncrement,
            });

            // highlight the user image so the controls show up
            canvas.add(img).setActiveObject(img).centerObject(img);
        }, {crossOrigin:''} );

        canvas.renderAll();

        // handle download button
        document.getElementById('btn-download').addEventListener( 'click', function() {
            canvas.deactivateAll().renderAll();
            this.href       = document.getElementById('canvas').toDataURL();
            this.download   = 'wr-cover-custom.jpg';
        }, false );

        $('.color-field').wpColorPicker({
            defaultColor: colorPickerPalette[0],
            palettes: colorPickerPalette,
            change: function ( event, ui ) {
                var newColor = ui.color.toString();
                canvas.setBackgroundColor( newColor, canvas.renderAll.bind(canvas) );
            }
        });

        // handle invert color of headlines text with checkbox
        $('input[type=checkbox][name=inverttext]').change( function() {

            // Check status
            var checkStatus     = this.checked;

            // get the current image being used from the radio input
            var image_source    = $('input[type=radio][name=canvasmask]:checked').val();

            // manipulate the image
            var maskImage   = new Image();

            maskImage.crossOrigin   = 'anonymous';
            maskImage.onload    = function () {

                // add frame/cover
                fabric.Image.fromURL( image_source, function( img ) {

                    if ( checkStatus ) {

                        img.filters.push( new fabric.Image.filters.InvertHeadlines() );

                        img.applyFilters( canvas.renderAll.bind(canvas) );

                    }

                    img.set({
                        lockScalingFlip: true,
                        lockUniScaling: true,
                        left: ( canvas.getWidth() / 2 ) - ( (img.width / 2) * responsiveScale ),
                        top: ( canvas.getHeight() / 2 ) - ( (img.height / 2) * responsiveScale ),
                        width: ( responsiveScale !== 1 ) ? ( responsiveScale * img.width ) : img.width,
                        height: ( responsiveScale !== 1 ) ? ( responsiveScale * img.height ) : img.height,
                        angle: 0,
                        evented: true,
                        selectable: false,
                        opacity: 1,
                        scale: responsiveScale
                    });

                    canvas.setOverlayImage( img, canvas.renderAll.bind(canvas) );

                }, {crossOrigin:''} );

            };
            maskImage.src   = image_source;

        } );

        // handle mask change based on radio button
        $('input[type=radio][name=canvasmask]').change( function() {
            // reset inverted colors checkbox
            $('input[type=checkbox][name=inverttext]').removeAttr('checked');

            // grab image src
            var image_source   = this.value;

            // manipulate the image
            var maskImage   = new Image();

            maskImage.crossOrigin   = 'anonymous';
            maskImage.onload    = function () {

                // add frame/cover
                fabric.Image.fromURL( image_source, function( img ) {

                    img.set({
                        lockScalingFlip: true,
                        lockUniScaling: true,
                        left: ( canvas.getWidth() / 2 ) - ( (img.width / 2) * responsiveScale ),
                        top: ( canvas.getHeight() / 2 ) - ( (img.height / 2) * responsiveScale ),
                        width: ( responsiveScale !== 1 ) ? ( responsiveScale * img.width ) : img.width,
                        height: ( responsiveScale !== 1 ) ? ( responsiveScale * img.height ) : img.height,
                        angle: 0,
                        evented: true,
                        selectable: false,
                        opacity: mask.opacity,
                        scale: responsiveScale
                    });

                    canvas.setOverlayImage( img, canvas.renderAll.bind(canvas) );

                }, {crossOrigin:''} );

            };
            maskImage.src   = image_source;

        } );

    } );


    (function(global) {

        'use strict';

        var fabric  = global.fabric || (global.fabric = { }),
            filters = fabric.Image.filters,
            createClass = fabric.util.createClass;

        /**
         * InvertHeadlines filter class
         * @class fabric.Image.filters.InvertHeadlines
         * @memberOf fabric.Image.filters
         * @extends fabric.Image.filters.BaseFilter
         * @see {@link http://fabricjs.com/image-filters|ImageFilters demo}
         * @example
         * var filter = new fabric.Image.filters.InvertHeadlines();
         * object.filters.push(filter);
         * object.applyFilters(canvas.renderAll.bind(canvas));
         */
        filters.InvertHeadlines = createClass(filters.BaseFilter, /** @lends fabric.Image.filters.InvertHeadlines.prototype */ {

            /**
             * Filter type
             * @param {String} type
             * @default
             */
            type: 'InvertHeadlines',

            /**
             * Applies filter to canvas element
             * @memberOf fabric.Image.filters.InvertHeadlines.prototype
             * @param {Object} canvasEl Canvas element to apply filter to
             */
            applyTo: function(canvasEl) {
                var context     = canvasEl.getContext('2d'),
                    imageData   = context.getImageData(0, 0, canvasEl.width, canvasEl.height),
                    data        = imageData.data,
                    iLen        = data.length,
                    red, blue , green, alpha;

                /**
                 * We only want to do a certain part of the images
                 * ( work around since the InvertBW filter wasn't accurate enough )
                 */
                // Declare from what point on the Y axis we want to change
                // Y axis limit
                for ( var y = 165; y <= canvasEl.height; y++ ) {
                    // X axis limit
                    for ( var x = 0; x <= canvasEl.width; x++ ) {
                       // get pixel origin
                        var offset  = ( ( canvasEl.width * y ) + x ) * 4;

                        data[offset]     = 255 - data[offset];
                        data[offset + 1] = 255 - data[offset + 1];
                        data[offset + 2] = 255 - data[offset + 2];
                    }
                }
                context.putImageData( imageData, 0, 0 );
            }
        });

        /**
        * Returns filter instance from an object representation
        * @static
        * @param {Object} object Object to create an instance from
        * @param {function} [callback] to be invoked after filter creation
        * @return {fabric.Image.filters.InvertHeadlines} Instance of fabric.Image.filters.InvertHeadlines
        */
        fabric.Image.filters.InvertHeadlines.fromObject = function(object, callback) {
            object = object || { };
            object.type = 'InvertHeadlines';
            return fabric.Image.filters.BaseFilter.fromObject(object, callback);
        };

    })(typeof exports !== 'undefined' ? exports : this);

}
