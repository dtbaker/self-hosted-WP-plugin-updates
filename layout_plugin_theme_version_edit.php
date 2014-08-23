<?php

global $wpdb;
$table_name = $wpdb->prefix . "wpupdatemanager";
$table_name_version = $wpdb->prefix . "wpupdatemanager_version";

$id = (int)$_REQUEST['id'];
$version_id = (int)$_REQUEST['version_id'];
if($id<=0){
    die('failed to load');
}else{
    // get the item.
    $sql = "SELECT * FROM `$table_name` WHERE `id` = ".(int)$id;
    $tp = array_shift($wpdb->get_results($sql,ARRAY_A));
    $tp_name = $tp['name'];
    $sql = "SELECT * FROM `$table_name_version` WHERE `version_id` = ".(int)$version_id;
    $version  = array_shift($wpdb->get_results($sql,ARRAY_A));
    if(!$version){
        $version = array();
    }
}
?>

<p>
<a href="admin.php?page=wpupdatemanager-dashboard&id=<?php echo $tp['id'];?>&view=foo">&laquo; Return</a>
</p>
<h2>
    <?php printf(__('%s Version:', 'wpupdatemanager'),$tp_name); ?>
</h2>

<form action="" method="post" >
<input type="hidden" name="_wpupdatemanager_process" value="save_version">
<input type="hidden" name="id" value="<?php echo (int)$id;?>">
<input type="hidden" name="version_id" value="<?php echo (int)$version_id;?>">
<table width="100%" class="form-table">
<tbody>
<tr>
    <td>
        Version Number:
    </td>
    <td>
        <input type="text" name="version_number" value="<?php echo htmlspecialchars($version['version_number']);?>" size="10">
    </td>
</tr>
<tr>
    <td>
        Date Created:
    </td>
    <td>
        <input type="text" name="date_created" value="<?php echo htmlspecialchars($version['date_created']);?>" size="30"> (eg: YYYY-MM-DD)
    </td>
</tr>
<tr>
    <td>
        Requires at least WP Version:
    </td>
    <td>
        <input type="text" name="requires" value="<?php echo htmlspecialchars($version['requires']);?>" size="10"> (eg: 3.1)
    </td>
</tr>
<tr>
    <td>
        Latest Tested WP Version:
    </td>
    <td>
        <input type="text" name="tested" value="<?php echo htmlspecialchars($version['tested']);?>" size="10"> (eg: 3.2.1)
    </td>
</tr>
<tr>
    <td>
        Full URL to ZIP file:<br>
        (include http://)
    </td>
    <td>
        <input type="text" name="zip_url" value="<?php echo htmlspecialchars($version['zip_url']);?>" size="60">
    </td>
</tr>
<tr>
    <td>
        Plugin Header Code
    </td>
    <td>
        Important! Make sure you put the correct <strong>version number</strong> in your item zip file. <br>
        Otherwise the update wont go through correctly. Here is an example:
        <br><br>
        <div style="border:1px solid #CCC;"><pre><?php
            echo '/***
   * Plu'.'gin N'.'ame: '.htmlspecialchars($tp['name']).'
   * Plug'.'in URI: '.htmlspecialchars($tp['homepage']).'
   * Description:
   * Ve'.'rsion: '.htmlspecialchars($version['version_number']).'
   * Author:  '.htmlspecialchars($tp['author']).'
   * Author URI: '.htmlspecialchars($tp['homepage']).'
   * Date: '.date(get_option('date_format','Y-m-d'),strtotime($version['date_created'])).'
   */';
            ?></pre></div>
    </td>
</tr>
</tbody>
</table>
<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes');?>"></p>
</form>

