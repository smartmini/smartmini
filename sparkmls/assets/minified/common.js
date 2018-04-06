var request_url = "/wp-admin/admin-ajax.php";
$(document).ready(function() {
console.log('only reskjndaksn');
    if ($(".quick-search-dream").length > 0) {
        
        // $(".pro-heading").css({
        //     "float": "none",
        //     "height": "0",            
        //     "margin-top": "-50px"
        // });
        $(".pro-heading").hide();
        $(".pro-heading").addClass('list-page-heading');
        $(".page-menu").css({
            "margin-top": "0px"
        });
    }
    // $("#closest_City").on("input", function(e) {
    //     var val = $(this).val();
    //     if(val === "") return;

    //     var url = "/wp-admin/admin-ajax.php?&val="+val+"&action=city_autosuggestions";
    //     $.get(url, {}, "json").done(function(res) { console.log(res);
    //         var dataList = $("#ClosestUSPSTown");
    //         dataList.empty();
    //         if(res.length) {

    //             var result = $.parseJSON(res);

    //             $( "#closest_City" ).autocomplete({
    //               source: result
    //             });


    //         }
    //     },"json");        
    // }); 

    if($("#accordian_details_maps").length)
    {
        // var map_data =  $("#flexmls_connect__map_group").wrap('<p/>').parent().html();
        // console.log(map_data); 
        // $("#accordian_details_maps").html(map_data);
        // $("#flexmls_connect__map_group").unwrap();
        //$(".tabs_wrapper").remove();
    } 


    $("#dream_PropertyType").change(function(e) {
        var thistext = $(this).find("option:selected").text();
        var thisvalue = $(this).find("option:selected").val();
        if (thisvalue == "C" && thistext == "Vacant Land") {
            window.open("http://www.alaskaland.info/", '_blank');
        }
    });   


    $(".email_send_on").click(function() {
        if ($(this).is(":checked")) {
            $(this).parent().find("label").text("YES");
        } else {
            $(this).parent().find("label").text("NO");
        }
    });

    // $("#modal-login").on("hidden", function () {
    //   if($("#open_saved_search").length) {  
    //      $('#myContactModal').modal('show');
    //   }
    // });

    // $("#myContactModal").on("hidden", function () {
     
    // });

    
    $(".my_saved_listing").click(function() {
        if($("#open_saved_listing").length == "") {
            $( "body" ).append( '<span id="open_saved_listing"></span>' );
        }
    });

    $(".my_saved_search").click(function() {
        if($("#open_saved_search").length == "") {
            $( "body" ).append( '<span id="open_saved_search"></span>' );
        }
    });

    $(".my_saved_search_listing").click(function() { 
        if($("#open_saved_search_listing").length == "") { 
            $( "body" ).append( '<span id="open_saved_search_listing"></span>' );
        }
    });

     $("#save-list-login").click(function() {
        if($("#save_listing_process").length == "") { 
            $( "body" ).append( '<span id="save_listing_process"></span>' );
        }
    });    

    

    $('#myContactModal').on('hide.bs.modal', function (e) {
         var current_url  = window.location.href; 
         window.location.href =  current_url;
    });




    $(".onetime_emailchange").click(function() { 
        if ($('#onceday').is(":checked")) {
            $('#onceday').parent().find("label").text("YES");
        } else {
            $('#onceday').parent().find("label").text("NO");
        }

        if ($('#asap').is(":checked")) {
            $('#asap').parent().find("label").text("YES");
        } else {
            $('#asap').parent().find("label").text("NO");
        }
    });

    // $("#saved_search_button").click(function(e) {
    //     var ajaxurl = '/wp-admin/admin-ajax.php?action=add_saved_search';
    //     $.get(ajaxurl, {}, "").done(function (data) {
    //         if(data==1){
    //             alert('Saved search work in progress');
    //         }else{
    //             alert("Error occured");
    //         }
    //     });
    // });



    $("#save_search").click(function() {
        url = request_url+'?action=add_saved_search';
        //this.preventDefault();
        query = $('form[name="property_form"]').serialize();

        // $("#alaska_content").html('Please wait.. <img src="/wp-content/plugins/alaska/images/loading.gif">');
        var current_url  = window.location.href; 
        $.get(url, {
            search: query,
            name: $("#save_search_name").val(),
            current_urls:current_url,
            frequency_management: $('input[name=frequency_management]:checked').val(),
            emailon_newlisting: $('input[name=emailon_newlisting]:checked').val(),
            emailon_pricechange: $('input[name=emailon_pricechange]:checked').val(),
            emailon_statuschange: $('input[name=emailon_statuschange]:checked').val()
        }).done(function(data) { 
            $('#myContactModal').modal('hide');
            alert('Saved');
            //if($("#open_saved_search").length) {      
                 //var current_url  = window.location.href; 
                 window.location.href =  'http://alaskadreammakers.info/saved-search-2/';
              //}
            
        });

    });



});



$(document).ready(function() {
    $(".onetime_emailchange,.mob_onetime_emailchange").click(function() {
        var searchid = $(this).attr('rel');

        if ($('#onceday' + searchid).is(":checked")) {
            $('#onceday' + searchid).parent().find("label").text("YES");            
        } else {
            $('#onceday' + searchid).parent().find("label").text("NO");
        }

        if ($('#asap' + searchid).is(":checked")) {
            $('#asap' + searchid).parent().find("label").text("YES");
        } else {
            $('#asap' + searchid).parent().find("label").text("NO");
        }
        if($(this).attr('rel') != "" && $(this).attr('rel') !=0 && $(this).attr('rel') != undefined)
        {


            $.ajax({
                url: request_url,
                data: {
                    action: 'savedsearch_emailchange',
                    id: $(this).attr('rel'),
                    checked: $(this).val()
                }
            }).done(function(data) {
                console.log(data);
                try {
                    var getresponse = $.parseJSON(data);
                    if (getresponse.status == 'ok') {
                        $('.response-header').html('<p class="success">Success</p>');
                        $('.response-body').html('<p class="success-body">Record successfully updated</p>');
                    } else {
                        $('.response-header').html('<p class="error">Error!</p>');
                        $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    }
                    $('#showmodalonsendemailchange').modal('show');

                } catch (err) {
                    $('.response-header').html('<p class="error">Error!</p>');
                    $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    $('#showmodalonsendemailchange').modal('show');

                }
            });
         }
    });
  
    $(".email_send_on").click(function() { 
        var optionval;
        if (jQuery(this).is(":checked")) {
            jQuery(this).parent().find("label").text("YES");
            jQuery(this).parent().find("input").val(1);
            optionval = 1;
        } else {
            jQuery(this).parent().find("label").text("NO");
             jQuery(this).parent().find("input").val(0);
            optionval = 0;
        }
        console.log($(this).attr('rel'));
        if($(this).attr('rel') != "" && $(this).attr('rel') !=0 && $(this).attr('rel') != undefined)
        {

            $.ajax({
                url: request_url,
                data: {
                    action: 'savedsearch_emailfrequency',
                    id: $(this).attr('rel'),
                    updateopt: $(this).attr('updateopt'),
                    checked: optionval
                }
            }).done(function(data) {
                console.log(data);
                try {
                    var getresponse = $.parseJSON(data);
                    if (getresponse.status == 'ok') {
                        $('.response-header').html('<p class="success">Success</p>');
                        $('.response-body').html('<p class="success-body">Record successfully updated</p>');
                    } else {
                        $('.response-header').html('<p class="error">Error!</p>');
                        $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    }
                    $('#showmodalonsendemailchange').modal('show');

                } catch (err) {
                    $('.response-header').html('<p class="error">Error!</p>');
                    $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    $('#showmodalonsendemailchange').modal('show');

                }
            });
        }
    });
  
    $("#mytable #checkall").click(function() {
        if ($("#mytable #checkall").is(':checked')) {
            $("#mytable input[type=checkbox]").each(function() {
                $(this).prop("checked", true);
            });

        } else {
            $("#mytable input[type=checkbox]").each(function() {
                $(this).prop("checked", false);
            });
        }
    });
  
    $(".searchdelete").on("click", function() {
        $('.searchdelete-new').attr('data-id', $(this).data("id"));
        $('#deleteconfirmation').modal('show');
    });


  
    $(".searchdelete-new").on("click", function() {
        $('#deleteconfirmation').modal('hide');

        var id = $(this).attr("data-id"); 
        if(id != 0 && id != "" && id != undefined)
        {

            $.ajax({
                url: request_url,
                data: {
                    action: 'savedsearch_delete',
                    id: id
                }
            }).done(function(data) {
                console.log(data);
                try {
                    var getresponse = $.parseJSON(data);
                    if (getresponse.status == 'ok') {
                        $('#savedsearch' + id).hide();
                        $('.response-header').html('<p class="success">Success</p>');
                        $('.response-body').html('<p class="success-body">Record successfully deleted</p>');
                    } else {
                        $('.response-header').html('<p class="error">Error!</p>');
                        $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    }
                    $('#showmodalonsendemailchange').modal('show');

                } catch (err) {
                    $('.response-header').html('<p class="error">Error!</p>');
                    $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    $('#showmodalonsendemailchange').modal('show');

                }
            });
         }
        //$('#savedsearch' + id).hide();
    });


    $("[data-toggle=tooltip]").tooltip();

    $(".toggle").on('click', function() {
        mySearch.changeSubscription($(this).data("change"));
        $(".toggle").show();
        $(this).hide();
    });

    


    if ($(window).width() < 1025) {
        $(".sub-menu").hide();
    }
    $(".alaska_main_menu").on('click', function(){ 
         if ($(window).width() < 1025) {
            //alert(1);
            // if($(this).children('ul.sub-menu').length == 0)
            // {
            //     $(this).children('ul.sub-menu').hide(500); 
            // }else{
                //$(this).children('ul.sub-menu').show(500);
                //alert($(this).attr('class'));
                //$(this).children('ul.sub-menu').addClass('inner-child');
                //alert($(this).hasClass("main_menu_li"));
            if($(this).hasClass("main_menu_li") == false){
                //alert(3);
                if($(this).children('ul.sub-menu').hasClass('inner-child')){
                    $(this).children('ul.sub-menu').addClass('outer-child');
                    $(this).children('ul.sub-menu').removeClass('inner-child');
                    $(this).children('ul.sub-menu').hide();
                }else{
                    $(this).children('ul.sub-menu').addClass('inner-child');
                    $(this).children('ul.sub-menu').removeClass('outer-child');
                    $(this).children('ul.sub-menu').show();
                }
            }else{
                $(this).children('ul.sub-menu').addClass('inner-child');
                $(this).children('ul.sub-menu').show(500);
                $(this).children('ul.sub-menu').removeClass('outer-child');
            }   
            //}
           // return false;
           
         }
     });
    
     $("#menu-top-menu .menu_has_my_child > ul.sub-menu li").on('click', function(e){ 
        //e.preventDefault(); 
        
         if ($(window).width() < 1025) { e.stopPropagation();
            //$(this).children('ul.sub-menu').show(500); 
            // if($(this).children('ul.sub-menu').length == 0)
            // {
            //     $(this).children('ul.sub-menu').hide(500); 
            // }else{
            //     $(this).children('ul.sub-menu').show(500);
            // }
            //alert(2 );
           // alert($(this).attr('class'));
            //$(this).children('ul.sub-menu').show(500);
            if($(this).children('ul.sub-menu').hasClass('inner-child')){
                $(this).children('ul.sub-menu').addClass('outer-child');
                $(this).children('ul.sub-menu').removeClass('inner-child');
                $(this).children('ul.sub-menu').hide();
            }else{
                $(this).children('ul.sub-menu').addClass('inner-child');
                $(this).children('ul.sub-menu').removeClass('outer-child');
                $(this).children('ul.sub-menu').show();
            }
           // return false;
         }
     });

    if ($(window).width() < 1024) { 
        $(".saved_Search_accordian").show();
        $(".saved_Search_table_data").remove();
        $(".saved_listing_accordian").show();
        $("#mytable").remove();
    }
    

    $(".search_active").on("change", function() { 
        if ($(this).prop('checked')) {
            optionval = 1;

        } else {
            optionval = 0;
        }  
        if($(this).attr('data-id') != "" && $(this).attr('data-id') != 0 && $(this).attr('data-id') != undefined)
        { 
            $.ajax({
                url: request_url,
                data: {
                    action: 'savedsearch_status_update',
                    id: $(this).attr('data-id'),
                    checked: optionval
                }
            }).done(function(data) {
                console.log(data);
                try {
                    var getresponse = $.parseJSON(data);
                    if (getresponse.status == 'ok') {
                        $('.response-header').html('<p class="success">Success</p>');
                        $('.response-body').html('<p class="success-body">Record successfully updated</p>');
                    } else {
                        $('.response-header').html('<p class="error">Error!</p>');
                        $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    }
                    $('#showmodalonsendemailchange').modal('show');

                } catch (err) {
                    $('.response-header').html('<p class="error">Error!</p>');
                    $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    $('#showmodalonsendemailchange').modal('show');

                }
            });
        }
    });
});

function saveListing(ListingStatus, ListingPrice, ListingId, ListingTag, UserId) { 
    var send_data = "&action=save_my_listing&ListingStatus="+ListingStatus+"&ListingPrice="+ListingPrice+"&ListingId="+ListingId+"&ListingTag="+ListingTag+"&UserId="+UserId;
    jQuery.ajax({
      url: request_url,
      type: "GET",
      data: send_data,
      dataType: "html",
      success: function(data){ 
        console.log(data);
        if(data){
            alert("Listing saved");
            if($("#save_listing_process").length) {
                var current_url  = window.location.href; 
                window.location.href =  current_url;
            }
        }
      }
    });  
}

$(document).ready(function(){
        $('.menu-toggle, #close_nav').click(function() { 
            $("#nav").slideToggle('2000');
            $("#close_nav").slideToggle('2000');
        });     

        
        var divs = $(".flexmls_connect__sr_result");
        var windowSize = $(window).width();
        if(windowSize < 530)
        {
            for(var i = 0; i < divs.length; i+=1) {
              divs.slice(i, i+1).wrapAll("<div class='list-row-new clearfix'></div>");
            }
        }else if(windowSize < 907)
        {
            for(var i = 0; i < divs.length; i+=2) {
              divs.slice(i, i+2).wrapAll("<div class='list-row-new clearfix'></div>");
            }
        }else 
        {
            for(var i = 0; i < divs.length; i+=3) {
              divs.slice(i, i+3).wrapAll("<div class='list-row-new clearfix'></div>");
            }
        }
});

function toggleIcon(e) {
$(e.target)
    .prev('.panel-heading')
    .find(".more-less")
    .toggleClass('glyphicon-plus glyphicon-minus');
}
jQuery('#accordions').on('hidden.bs.collapse', toggleIcon);
jQuery('#accordions').on('shown.bs.collapse', toggleIcon);
jQuery('#accordion').on('hidden.bs.collapse', toggleIcon);
jQuery('#accordion').on('shown.bs.collapse', toggleIcon);
jQuery('#accordions_mob').on('hidden.bs.collapse', toggleIcon);
jQuery('#accordions_mob').on('shown.bs.collapse', toggleIcon);
jQuery('#accordion_savedlisting').on('hidden.bs.collapse', toggleIcon);
jQuery('#accordion_savedlisting').on('shown.bs.collapse', toggleIcon);




var mySearch = {
    delete: function (id) {
        $.ajax({
            url: "/wp-admin/admin-ajax.php",
            data: {
                action: "saved_listing_delete",
                id: id
            }
        }).done(function (data) { 
            //window.location.reload();
        });
    }
};



$(document).ready(function () {

    $(".search_delete").on("click", function(){ 
        $(".searchdelete-listing").attr("data-id", $(this).attr("data-id"));
        $(".searchdelete-listing").attr("data-key", "saved-listing");
        $("#deleteconfirmation").modal("show");             
    });

    var mySearch = {
        delete: function (id) {
            $.ajax({
                url: request_url,
                data: {
                    action: "saved_listing_delete",
                    id: id
                }
            }).done(function (data) { 
                try {
                    var getresponse = $.parseJSON(data);
                    if (getresponse.status == 'ok') {
                        $('#savedlisting' + id).hide();
                        $('#mobsavedlisting' + id).hide();
                        $('.response-header').html('<p class="success">Success</p>');
                        $('.response-body').html('<p class="success-body">Record successfully deleted</p>');
                    } else {
                        $('.response-header').html('<p class="error">Error!</p>');
                        $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    }
                    $('#showmodalonsendemailchange').modal('show');

                } catch (err) {
                    $('.response-header').html('<p class="error">Error!</p>');
                    $('.response-body').html('<p class="error-body">Sorry! Unable to complete your request. Please Try again.</p>');
                    $('#showmodalonsendemailchange').modal('show');

                }
            });
        }
    };               
                   


    $(".searchdelete-listing").on("click", function() {
        $('#deleteconfirmation').modal('hide');
        var id = $(this).attr("data-id"); 
        mySearch.delete(id);
        $(this).parent().parent().parent().hide();                          
    });                         

    $("[data-toggle=tooltip]").tooltip();

});

function togglelabel(labelclass,labeltext,vals)
{
    // jQuery("this").parent().parent().addClass('Test');
    // alert(jQuery("this").html());
    //alert($(this +'option:selected').val());
    if(vals != 0)
    {  
        $("."+labelclass).html(labeltext);
        $("."+labelclass).fadeIn(2000);
    }else{ 
        $("."+labelclass).html('');
        //$("."+labelclass).fadeOut(2000);
    }
    
}