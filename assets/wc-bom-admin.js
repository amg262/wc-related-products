/*
 * Copyright (c) 2017  |  Netraa, LLC
 * netraa414@gmail.com  |  https://netraa.us
 *
 * Andrew Gunn  |  Owner
 * https://andrewgunn.org
 */

/**
 * Created by andy on 2/24/17.
 */
/**
 * Created by andy on 2/9/17.
 */

var product = null;
var data = null;
var val = null;
var id = null;

jQuery(document).ready(function($) {


  //$("#commentForm").validate();
  //var ProgressBar = require('progressbar.js');

  //var bar = new ProgressBar.Line('#container', {easing: 'easeInOut'});
  //bar.animate(1);  // Value from 0.0 to 1.0
  //$('#wcrp-options').css('display', 'none');
  //$('#wcrp-support').css('display', 'none');
  //alert('hi');

  $('#wcrp-nav-related').click(function() {
    //alert('hi');
    $('#wcrp-related').css('display', 'block');
    $('#wcrp-upsells').css('display', 'none');
    $('#wcrp-crosssells').css('display', 'none');
    $('#wcrp-settings').css('display', 'none');

    $(this).attr('class', 'nav-tab nav-tab-active', 'nav-tab nav-tab-active');
    $('#wcrp-nav-related').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-upsells').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-crosssells').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-settings').attr('class', 'nav-tab', 'nav-tab');
  });

  $('#wcrp-nav-upsells').click(function() {
    //alert('hi');
    $('#wcrp-related').css('display', 'none');
    $('#wcrp-upsells').css('display', 'block');
    $('#wcrp-crosssells').css('display', 'none');
    $('#wcrp-settings').css('display', 'none');

    $(this).attr('class', 'nav-tab nav-tab-active', 'nav-tab nav-tab-active');
    $('#wcrp-nav-related').attr('class', 'nav-tab', 'nav-tab');
    // $('#wcrp-nav-upsells').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-crosssells').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-settings').attr('class', 'nav-tab', 'nav-tab');
  });

  $('#wcrp-nav-crosssells').click(function() {
    //alert('hi');
    $('#wcrp-related').css('display', 'none');
    $('#wcrp-upsells').css('display', 'none');
    $('#wcrp-crosssells').css('display', 'block');
    $('#wcrp-settings').css('display', 'none');

    $(this).attr('class', 'nav-tab nav-tab-active', 'nav-tab nav-tab-active');
    $('#wcrp-nav-related').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-upsells').attr('class', 'nav-tab', 'nav-tab');
    // $('#wcrp-nav-crosssells').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-settings').attr('class', 'nav-tab', 'nav-tab');
  });

  $('#wcrp-nav-settings').click(function() {
    //alert('hi');
    $('#wcrp-related').css('display', 'none');
    $('#wcrp-upsells').css('display', 'none');
    $('#wcrp-crosssells').css('display', 'none');
    $('#wcrp-settings').css('display', 'block');

    $(this).attr('class', 'nav-tab nav-tab-active', 'nav-tab nav-tab-active');
    $('#wcrp-nav-related').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-upsells').attr('class', 'nav-tab', 'nav-tab');
    $('#wcrp-nav-crosssells').attr('class', 'nav-tab', 'nav-tab');
    //$('#wcrp-nav-settings').attr('class', 'nav-tab', 'nav-tab');
  });

  $('.chosen-select').chosen();

  //$("#form_field").chosen().change( … );
  //$("#form_field").trigger("chosen:updated");

  $('#button_hit').click(function() {
    var data = {
      'url': ajax_object.ajax_url,
      'action': 'wco_ajax',
      'security': ajax_object.nonce,
      'product': val,
    };

    console.log(data);

    sweetAlert({
          title: 'Export Product\'s BOM?',
          text: 'Submit to run ajax request',
          type: 'info',
          showCancelButton: true,
          closeOnConfirm: false,
          showLoaderOnConfirm: true,
        },
        function() {

          // We can also pass the url value separately from ajaxurl for front end AJAX implementations
          jQuery.post(ajax_object.ajax_url, data, function(response) {

            $('#prod_output').html(response);
            setTimeout(function() {
              swal('Finished');
            });
            //alert('seRespon ' + response);
          });
        });

  });
});

jQuery(function($) {

});

/*
 * Plugins that insert posts via Ajax, such as infinite scroll plugins, should trigger the
 * post-load event on document.body after posts are inserted. Other scripts that depend on
 * a JavaScript interaction after posts are loaded
 *
 * JavaScript triggering the post-load event after posts have been inserted via Ajax:
 */
//jQuery(document.body).trigger('post-load');

/*
 *JavaScript listening to the post-load event:
 */
jQuery(document.body).trigger('post-load');
jQuery(document.body).on('post-load', function() {
  // New posts have been added to the page.
  console.log('posts');
});