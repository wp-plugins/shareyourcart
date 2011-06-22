var $j = jQuery.noConflict();

$j(document).ready(function(){
   $j('#account-recovery').click(function(){
       call_recovery_api();
       return false;
   });
    $j('#configure-button').click(function(){
       $j('#configure-form').submit();
});
});

function call_recovery_api(email)
{
  $j.post(MyAjax.ajaxurl,{

            action : 'shareyourcart_call_recovery_api',
            new_email : email
        },
        function(data)
        {   
            var html = '<div class="updated settings-error"><p><strong>'+ data +'</strong></p></div>';
            if($j('.settings-error').length > 0)
            {
                $j('.settings-error').remove();
                $j('a.shareyourcart-logo').after(html);
            }
            else
            {
                $j('a.shareyourcart-logo').after(html);
            }
            if(data == 'The account has been registered.')
                window.location.href = window.location.href;
            
            $j('#account_retry').click(function(){
               call_recovery_api($j('#new-email').val());
               return false;
            });          
        }
    );
}
