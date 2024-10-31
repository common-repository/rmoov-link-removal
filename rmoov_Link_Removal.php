<?php
/*
	Plugin Name: rmoov Link Removal
	Plugin URI: http://rmoov.com/wordpress-plugin-rmoov-link-removal.php
	Description: rmoov Connector for automatic link removal processing
	Author: Support at rmoov
	Version: 3.2
	Author URI: http://www.rmoov.com
*/

// if str_getcsv does not exist on the client then make one
if (!function_exists('str_getcsv')) { 
  
function str_getcsv($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) { 
  $temp=fopen("php://memory", "rw"); 
  fwrite($temp, $input); 
  fseek($temp, 0); 
  $r = array(); 
  while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) { 
    $r[] = $data; 
  } 
  fclose($temp); 
  return $r; 
} 

}

function rmoov_admin() 
{ 
   global $wpdb;

   if( $_POST['rmoov_update'] == 'Update Key' )
   {
      $rmoov_apiuser = $_POST['rmoov_apiuser'];  
      $rmoov_apikey = $_POST['rmoov_apikey'];  
      update_option('rmoov_apiuser', $rmoov_apiuser);
      update_option('rmoov_apikey', $rmoov_apikey);
?>  
      <div class="updated"><p><strong><?php _e('rmoov User and Key saved' ); ?></strong></p></div>  
<?
   }
   else if( $_POST['rmoov_check'] == 'Check Key' )
   {
      $rmoov_apiuser = get_option('rmoov_apiuser');
      $rmoov_apikey = get_option('rmoov_apikey');
      $url    = 'https://www.rmoov.com/webmasterapi.php';

      // Get all data in SOAP package
      $fields = array(
                      'user'=>$rmoov_apiuser,
                      'key'=>$rmoov_apikey,
                      'domain'=>$_SERVER['HTTP_HOST'],
                      'check'=>1,
      );

      // url-ify the data for the POST
      foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      rtrim($fields_string,'&');

      //open connection
      $ch = curl_init();
   
      //set the url, number of POST vars, POST data
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER, true );
      curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true );
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt($ch,CURLOPT_POST,count($fields));
      curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
         
      //execute post
      $result = curl_exec($ch);

?>  
      <div class="updated"><p><strong><?php _e($result); ?></strong></p></div>  
<?
   }
   else if( $_POST['rmoov_list'] == 'List Links' )
   {
      $rmoov_apiuser = get_option('rmoov_apiuser');
      $rmoov_apikey = get_option('rmoov_apikey');
      $url    = 'https://www.rmoov.com/webmasterapi.php';
      $domain = $_SERVER['HTTP_HOST'];
      $domain = str_ireplace('www.', '', $domain);

      // Get all data in SOAP package
      $fields = array(
                      'user'=>$rmoov_apiuser,
                      'key'=>$rmoov_apikey,
                      'domain'=>$domain,
                      'output'=>csv,
      );

      // url-ify the data for the POST
      foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      rtrim($fields_string,'&');

      //open connection
      $ch = curl_init();
  
      //set the url, number of POST vars, POST data
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER, true );
      curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true );
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt($ch,CURLOPT_POST,count($fields));
      curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

      //execute post
      $result = curl_exec($ch);
      $Data = str_getcsv($result, "\n"); //parse the rows 
?><div class="updated"><p><?
      if(( sizeof($Data) > 0 )&&($Data[0] != ''))
      {  foreach($Data as &$Row)
         {  $Row = str_getcsv($Row, ","); //parse the items in rows 

            if( stristr( $Row[1], $domain ) ) { 
               echo "Erase: <strong>".$Row[0]."</strong> from <strong>".$Row[1]."</strong> last checked (".$Row[2].")<br />";
               $deletework = 1;
            }
            else 
            {  print_r($Row[0]); }
         }
      }
      else
      {  print "No links to cleanup"; }
?></p></div><?

   }
   else if( $_POST['rmoov_delete'] == 'Remove Links' )
   {
      $rmoov_apiuser = get_option('rmoov_apiuser');
      $rmoov_apikey = get_option('rmoov_apikey');
      $url    = 'https://www.rmoov.com/webmasterapi.php';
      $domain = $_SERVER['HTTP_HOST'];
      $domain = str_ireplace('www.', '', $domain);

      // Get all data in SOAP package
      $fields = array(
                      'user'=>$rmoov_apiuser,
                      'key'=>$rmoov_apikey,
                      'domain'=>$domain,
                      'output'=>csv,
      );

      // url-ify the data for the POST
      foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      rtrim($fields_string,'&');

      //open connection
      $ch = curl_init();
 
      //set the url, number of POST vars, POST data
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER, true );
      curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true );
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt($ch,CURLOPT_POST,count($fields));
      curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

      //execute post
      $result = curl_exec($ch);
      $Data = str_getcsv($result, "\n"); //parse the rows
?><div class="updated"><p><strong><?
      if(( sizeof($Data) > 0 )&&($Data[0] != ''))
      {  foreach($Data as &$Row)
         {  $Row = str_getcsv($Row, ","); //parse the items in rows

            if( stristr( $Row[1], $domain ) ) {    
               $cleanup = $Row[0];

               // Delete Comments
               $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_content like '%$cleanup%' or comment_author_email like '%$cleanup%' or comment_author_url like '%$cleanup%' ");
               if( $wpdb->last_error == '' ) 
               { ?> <p style="color:green">All comments for <? echo $cleanup; ?> have now been removed.</p> <? }
               else 
               { ?> <p style="color:red">There was an error, please try again.</p> <? }

               // Reset the counts on the pages
               $entries = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type IN ('post', 'page')");

               foreach($entries as $entry)
               {
                  $post_id = $entry->ID;
                  $comment_count = $wpdb->get_var("SELECT COUNT(*) AS comment_cnt FROM $wpdb->comments WHERE comment_post_ID = '$post_id' AND comment_approved = '1'");
                  $wpdb->query("UPDATE $wpdb->posts SET comment_count = '$comment_count' WHERE ID = '$post_id'");
               }
            }
            else 
            {  print_r($Row[0]); }
         }
         // now tell rmoov it was all cleanedup
         // Get all data in SOAP package
         $fields = array(
                         'user'=>$rmoov_apiuser,
                         'key'=>$rmoov_apikey,
                         'domain'=>$domain,
                         'clean'=>'1',
         );
   
         // url-ify the data for the POST
         foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
         rtrim($fields_string,'&');

         //open connection
         $ch = curl_init();

         //set the url, number of POST vars, POST data
         curl_setopt($ch,CURLOPT_URL,$url);
         curl_setopt($ch,CURLOPT_RETURNTRANSFER, true );
         curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true );
         curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false );
         curl_setopt($ch,CURLOPT_POST,count($fields));
         curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

         //execute post
         $result = curl_exec($ch);
?>  
      <div class="updated"><p><strong><?php _e($result); ?></strong></p></div>  
<?
      }
      else
      {  print "No links to cleanup"; }
?></strong></p></div><?

   }
   else
   {
      $rmoov_apiuser = get_option('rmoov_apiuser');
      $rmoov_apikey = get_option('rmoov_apikey');
   }

?>

<div class="wrap">  
    <?php    echo "<h2>" . __( 'rmoov Link Removal Settings', 'rmoov_trdom' ) . "</h2>"; ?>  
      
    <form name="rmoov_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">  
        <input type="hidden" name="rmoov_hidden" value="Y">  
        <p>
           <table>
           <tr><td><?php _e("API User: " ); ?></td><td><input type="text" name="rmoov_apiuser" value="<?php echo $rmoov_apiuser; ?>" size="50"></td></tr>
           <tr><td><?php _e("API Key: " ); ?></td><td><input type="text" name="rmoov_apikey" value="<?php echo $rmoov_apikey; ?>" size="50"></td></tr>
           </table>
        </p>  
        <p class="submit">  
<? if( $rmoov_apikey != '' ) { ?>
   <? if( $deletework ) { ?>
        <input type="submit" name="rmoov_delete" value="<?php _e('Remove Links', 'rmoov_trdom' ) ?>" />  
   <? } else { ?>
        <input type="submit" name="rmoov_list" value="<?php _e('List Links', 'rmoov_trdom' ) ?>" />  
   <? } ?>
        <input type="submit" name="rmoov_check" value="<?php _e('Check Key', 'rmoov_trdom' ) ?>" />  
<? } ?>
        <input type="submit" name="rmoov_update" value="<?php _e('Update Key', 'rmoov_trdom' ) ?>" />  
        </p>  
    </form>  
    <a href="http://www.rmoov.com/index.php">Login to rmoov</a> &nbsp;&nbsp; <a href="http://www.rmoov.com/rmoov-webmaster-pricing.php">API Pricing</a> &nbsp;&nbsp; <a href="http://www.rmoov.com/webmaster_register.php">Register for an account</a>
</div>  

<?

}

function rmoov_admin_actions() 
{
   add_management_page("rmoov Link Removal", "rmoov Link Removal", 1, "rmoov_Link_Removal", "rmoov_admin");
}

add_action('admin_menu', 'rmoov_admin_actions');
?>
