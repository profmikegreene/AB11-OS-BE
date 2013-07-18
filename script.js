jQuery(document).ready(function($){


  var _custom_media = true,
      _orig_send_attachment = wp.media.editor.send.attachment;

  $('#ab11-os-import-form .button').click(function(e) {
    var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
    var id = button.attr('id').replace('_button', '');
    var $attachment_url = false;
    _custom_media = true;
    wp.media.editor.send.attachment = function(props, attachment){
      if ( _custom_media ) {
        $("#"+id).val(attachment.url);
        attachment.semester_id = $('#ab11_os_semester_id').val();
        $.ajax({
          url: '/wp-content/plugins/ab11-online-schedule/import.php',
          type: 'POST',
          data: ({
            'attachment': attachment,
            'action': 'add'
          }),
          success: function(returnData){
            $('#ab11-os-import-wrap').prepend(returnData);
          }
        });
      } else {

        return _orig_send_attachment.apply( this, [props, attachment] );
      }
    };

    wp.media.editor.open(button);

    return false;
  });

  $('.add_media').on('click', function(){
    _custom_media = false;
  });

});