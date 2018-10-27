jQuery(document).ready( function($)
{

  //================= helper=====================================
  function GetURLParameter(sParam){
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for( var i = 0; i < sURLVariables.length; i++ ){
      var sParameterName = sURLVariables[i].split('=');
      if (sParameterName[0] == sParam){
         //console.log(sParameterName[1]);
        return sParameterName[1];
      }
    }
  }
//================================================================
  // declare global variables
  var ajax_url = ajax_data.ajax_url;
  var assetId = GetURLParameter('course_id');
  var assetSlug = GetURLParameter('course_slug').toString();
  //var filelist = [];
  //var data = { 'action': 'upload_files',  'assetid': assetId, 'assetslug': assetSlug, 'filelist':filelist, 'nonce': ajax_data.nonce };

// upload full directory to s3.
  $('#videoform').submit( function(e){
    e.preventDefault();

     var files = $('#vidfiles')[0].files;
     var inputName = $('#vidfiles').attr('name');
     var fileData = { 'action':'upload_directory',
                    'contentType':false,
                    'processData':false,
                    'assetid':assetId,
                    'assetslug':assetSlug,
                    'nonce': ajax_data.nonce };

     //make files available
     $(files).each( function(i, file){
       // get file data
       fr = new FileReader();
       fr.readAsDataURL(file);
       $.extend(file,{ "base64":fr.result }); // add the files data string to the file object.
     });
     $.extend(fileData, {'filelist':files }); // add file list to the ajax post data set

     $.post( ajax_url, fileData, function(response){ // send data to server for s3 upload
          alert(response);
     });
  });

  // $('#upload-files').on('click', function(e){
  //   // images
  //   imgs=[]; docs=[]; vids=[]; aud=[];
  //
  //   $('.imgfile').each(function(e){
  //     ival = $(this).val();
  //     imgs.push(ival);
  //   });
  //   // documents
  //   $('.docfile').each(function(e){
  //     dval = $(this).val();
  //     docs.push(dval);
  //   });
  //   // audio
  //   $('.audfile').each(function(e){
  //     aval = $(this).val();
  //     aud.push(aval);
  //   });
  //   // video
  //   $('.vidfile').each(function(e){
  //     vidval = $(this).val();
  //     vids.push(vidval);
  //   });
  //
  //   filelist.push({ 'images':imgs, 'docs':docs,'audio':aud,'videos':vids });
  //   // add object to data variable for bulk upload
  //   $.extend( data['filelist'],filelist);
  //   console.log($(data));
  //   $.post( ajax_url,data,function(response){
  //       alert(response);
  //    });
  // });

// The Uploader
  // $.event.props.push('dataTransfer');
  // $('.dropzone div').on(
  //   {
  //     dragover: function(e) {
  //       e.stopPropagation();
  //       e.preventDefault();
  //       $(this).addClass('highlight');
  //       return false; //crucial for 'drop' event to fire
  //     },
  //     dragleave: function(e) {
  //       e.stopPropagation();
  //       e.preventDefault();
  //       $(this).removeClass('highlight');
  //       return false;
  //     },
  //     drop: function(e) {
  //      e.stopPropagation();
  //      e.preventDefault();
  //
  //      var this_obj = $(this);
  //      var id = $(this).attr('id');
  //      var files = e.dataTransfer.files;
  //
  //      if(files == undefined || files.length == 0) return;
  //
  //      var fileReader = new FileReader();
  //
  //       for (i = 0; i < files.length; i++) {
  //         var file = files[i];
  //         fileReader.onload = (function(event){
  //
  //            return function(event) {
  //              var newUL = $('<ul>').appendTo(this_obj);
  //
  //              alert(file.path);
  //
  //              filelist.push({ 'filename':file.name, 'filedata': event.target.result });
  //
  //              // add object to data variable for bulk upload
  //              $.extend( data['filelist'],filelist);
  //
  //              if( id == 'img-leftCol'){
  //                $('<li><img class="imgdata" id="'+file.name+'" style="max-width: 200px; max-height: 200px;" src="' + event.target.result + '">'+file.name+'</li>').appendTo(newUL);
  //              }else{
  //                 $('<li class="file" id="'+file.name+'" data="'+file+'">'+file.name+'</li>').appendTo(newUL);
  //              }
  //            }
  //          })(file);
  //          fileReader.readAsDataURL(file);
  //      }
  //      console.log("FileLIST::"+$(filelist));
  //      console.log($(data));
  //      // Upload file
  //      $('#upload-files').on('click', function(e){
  //        console.log($(data));
  //
  //         $.post( ajax_url,data,function(response){
  //           alert(response);
  //         });
  //    });
  //      return false;
  //    }
  //  });
});
