<?php

global $wpdb;
$table_name = $wpdb->prefix . "wpupdatemanager";

$id = (int)$_REQUEST['id'];
if($id<=0){
    $tp_name = __('New','wpupdatemanager');
    $tp_type = __('Theme or Plugin','wpupdatemanager');
    $tp = array(
        'name' => '',
        'type' => 'plugin',
        'envato' => '1',
        'envato_username' => '',
        'envato_api_key' => '',
    );
}else{
    // get the item.

    $sql = "SELECT * FROM `$table_name` WHERE `id` = ".(int)$id;
    $tp = array_shift($wpdb->get_results($sql,ARRAY_A));
    $tp_name = htmlspecialchars($tp['name']);
    switch($tp['type']){
        case 'plugin':
            $tp_type = __('Plugin','wpupdatemanager');
            break;
        case 'theme':
            $tp_type = __('Theme','wpupdatemanager');
            break;
        default:
            $tp_type = __('Item','wpupdatemanager');
            break;
    }

}


?>


<?php if($id>0){ ?>

<h2>
    <?php printf(__('%s Versions:', 'wpupdatemanager'),$tp_type); ?>
    <?php if($id>0){ ?>
    <a href="admin.php?page=wpupdatemanager-dashboard&id=<?php echo $id;?>&version_id=new" class="button add-new-h2"><?php _e('Add New Version', 'wpupdatemanager');?></a>
    <?php } ?>
</h2>

<?php
    $columns = array(
        'version_number' => 'Version Number',
        'date_created' => 'Date Created',
        'requires' => 'Requires WP Version',
        'tested' => 'Tested up to WP Version',
        'install_count' => 'Install Count',
        'last_install' => 'Last Install',
    );
    register_column_headers('wpupdatemanager-list-versions', $columns);


    $sql = "SELECT v.*";
    $sql .= ", COUNT(i.install_id) AS install_count";
    $sql .= ", (SELECT i2.time FROM `".$wpdb->prefix."wpupdatemanager_install` i2 WHERE v.version_id = i2.version_id ORDER BY i2.time DESC LIMIT 1) AS last_install";
    $from = " FROM `".$wpdb->prefix."wpupdatemanager_version` v ";
    $from .= " LEFT JOIN `".$wpdb->prefix."wpupdatemanager_install` i ON v.version_id = i.version_id";
    $where = " WHERE 1 ";
    $where .= " AND v.id = ".(int)$id;
    $group_by = ' GROUP BY v.version_id ';
    $order_by = ' ORDER BY v.version_number DESC';
    $sql = $sql . $from . $where . $group_by . $order_by;
    $wpdb->show_errors();
    $all_versions = $wpdb->get_results($sql,ARRAY_A);

    if(count($all_versions)){
    ?>


        <table class="widefat page fixed" cellspacing="0">
            <thead>
            <tr>
                <?php print_column_headers('wpupdatemanager-list-versions'); ?>
            </tr>
            </thead>
            <tbody>
                <?php foreach ($all_versions as $version){
                $version=(array)$version;
                ?>
            <tr>
                <td class="row_action" nowrap="">
                    <a href="admin.php?page=wpupdatemanager-dashboard&id=<?php echo $id;?>&version_id=<?php echo $version['version_id'];?>&view=foo"><?php
                        echo trim($version['version_number']) ? htmlspecialchars('Version '.$version['version_number']) : 'N/A' ;?></a>
                </td>
                <td>
                    <?php
                    if($version['date_created']){
                        echo date(get_option('date_format','Y-m-d'),strtotime($version['date_created']));
                    } ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($version['requires']); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($version['tested']); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($version['install_count']); ?>
                </td>
                <td>
                    <?php
                    if($version['last_install']){
                        echo date(get_option('date_format','Y-m-d').' '.get_option('time_format','H:i:s'),($version['last_install']));
                    } ?>
                </td>
            </tr>
                <?php  } ?>
            </tbody>
        </table>
    <?php } ?>
<?php } ?>


<h2>
    <?php printf(__('%s Details:', 'wpupdatemanager'),$tp_type); ?>
</h2>

<form action="" method="post" >
    <input type="hidden" name="_wpupdatemanager_process" value="save_item">
    <input type="hidden" name="id" value="<?php echo (int)$id;?>">
    <table width="100%" class="form-table">
        <tbody>
        <tr>
            <td>
                Item Name:
            </td>
            <td>
                <input type="text" name="name" value="<?php echo htmlspecialchars($tp['name']);?>" size="60">
            </td>
        </tr>
        <tr>
            <td>
                Item Type:
            </td>
            <td>
                <input type="radio" name="type" value="plugin"<?php echo $tp['type']=='plugin' ? ' checked':'';?>><?php _e('Plugin');?>
                <input type="radio" name="type" value="theme"<?php echo $tp['type']=='theme' ? ' checked':'';?>><?php _e('Theme');?>
            </td>
        </tr>
        <tr>
            <td>
                Author:
            </td>
            <td>
                <input type="text" name="author" value="<?php echo htmlspecialchars($tp['author']);?>" size="60">
            </td>
        </tr>
        <tr>
            <td>
                Home Page:
            </td>
            <td>
                <input type="text" name="homepage" value="<?php echo htmlspecialchars($tp['homepage']);?>" size="60">
            </td>
        </tr>
        <tr>
            <td>
                Envato Integration:
            </td>
            <td>
                <input type="radio" name="envato" value="1"<?php echo $tp['envato']=='1' ? ' checked':'';?>><?php _e('Yes');?>
                <input type="radio" name="envato" value="0"<?php echo $tp['envato']=='0' ? ' checked':'';?>><?php _e('No');?>
            </td>
        </tr>
        <?php if($tp['envato']=='1'){ ?>
        <tr>
            <td>
                Envato Item ID:
            </td>
            <td>
                <input type="text" name="envato_item" value="<?php echo htmlspecialchars($tp['envato_item']);?>" size="60">
            </td>
        </tr>
        <tr>
            <td>
                Envato Username:
            </td>
            <td>
                <input type="text" name="envato_username" value="<?php echo htmlspecialchars($tp['envato_username']);?>" size="60">
            </td>
        </tr>
        <tr>
            <td>
                Envato API Key:
            </td>
            <td>
                <input type="text" name="envato_api_key" value="<?php echo htmlspecialchars($tp['envato_api_key']);?>" size="60">
            </td>
        </tr>
        <?php if($id>0){ ?>
        <tr>
            <td>
                This PHP code will ask <br>
                the user for their licence code <br>
                (it just saves in the "_envato_licence<?php echo htmlspecialchars($tp['envato_item']);?>" option) <br>
                Put this in your <?php echo $tp['type'];?> settings somewhere <br>
                so the user can enter their licence key.<br><br>
                Automatic updates will simply not work if <br>
                the buyer enters an incorrect licence key.
            </td>
            <td>
                <div style="border:1px solid #CCC;"><pre><?php
                    ob_start();

                    echo '<?php
$licence_code = get_option("_envato_licence'.htmlspecialchars($tp['envato_item']).'","");
if(isset($_REQUEST["save_envato_licence"])){
    $licence_code = $_REQUEST["save_envato_licence"];
    // todo: validate code before saving, and display error.
    update_option("_envato_licence'.htmlspecialchars($tp['envato_item']).'",$licence_code);
}
?>
<form action="" method="POST">
    <input type="text" name="save_envato_licence" value="<?php echo htmlspecialchars($licence_code);?>">
    <input type="submit" name="save" value="Save Envato Licence Code">
</form>
';

                    echo (htmlspecialchars(ob_get_clean()));
                    ?></pre></div>
            </td>
        </tr>
        <?php } ?>
        <?php } ?>
        <?php if($id>0){ ?>

        <?php if($tp['type'] == 'plugin'){ ?>
        <tr>
            <td>
                This PHP code will add <br>
                automatic updates to your WP plugin: <br><br>

                Simply copy and paste this code<br>
                into your plugin functions file.
            </td>
            <td>
                <div style="border:1px solid #CCC;"><pre><?php
ob_start();
                    $url = get_home_url();
                    if(!preg_match('#/$#',$url)){
                        $url.='/';
                    }
                    $url .= '?wpupdatemanager=true&item_id='.$id;
                    echo '<?php
/***** BEGIN AUTOMATIC UPDATE CODE *******/
if(!function_exists("dtbaker_prepare_request'.$id.'")){
    function dtbaker_prepare_request'.$id.'($action, $args) {
        global $wp_version;
        return array(
            "body" => array(
                "action" => $action,
                "args" => serialize($args),
                ';
                if($tp['envato']){
                    echo '"envatolicence" => get_option("_envato_licence'.htmlspecialchars($tp['envato_item']).'",""),
                "envatoitem" => "'.htmlspecialchars($tp['envato_item']).'",
                ';
                }
                //"client" => $_SERVER["REMOTE_ADDR"],

                echo '"install" => get_bloginfo("url"),
            ),
            "user-agent" => "WordPress/" . $wp_version . "; " . get_bloginfo("url")
        );
    }
}
';
if($tp['envato']){
    echo 'if(get_option("_envato_licence'.htmlspecialchars($tp['envato_item']).'","")){
        add_filter("pre_set_site_transient_update_plugins", "dtbaker_check_for_plugin_update'.$id.'");
}';
}else{
    echo 'add_filter("pre_set_site_transient_update_plugins", "dtbaker_check_for_plugin_update'.$id.'");';
}
echo '
if(!function_exists("dtbaker_check_for_plugin_update'.$id.'")){
function dtbaker_check_for_plugin_update'.$id.'($checked_data) {
    $plugin_slug = basename(dirname(__FILE__));
    if (empty($checked_data->checked))
        return $checked_data;
    $request_args = array(
        "name" => $plugin_slug,
        "version" => $checked_data->checked[$plugin_slug ."/". $plugin_slug .".php"],
    );
    $request_string = dtbaker_prepare_request'.$id.'("check_for_updates", $request_args);
    $raw_response = wp_remote_post("'.$url.'", $request_string);
    if (!is_wp_error($raw_response) && ($raw_response["response"]["code"] == 200))
        $response = unserialize($raw_response["body"]);
    if (is_object($response) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$plugin_slug ."/". $plugin_slug .".php"] = $response;
    return $checked_data;
}
}
';
if($tp['envato']){
    echo 'if(get_option("_envato_licence'.htmlspecialchars($tp['envato_item']).'","")){
        add_filter("plugins_api", "dtbaker_plugin_api_call'.$id.'", 10, 3);
}';
}else{
    echo 'add_filter("plugins_api", "dtbaker_plugin_api_call'.$id.'", 10, 3);';
}
echo '
if(!function_exists("dtbaker_plugin_api_call'.$id.'")){
function dtbaker_plugin_api_call'.$id.'($def, $action, $args) {
    $plugin_slug = basename(dirname(__FILE__));
    if ($args->slug != $plugin_slug)
        return false;
    // Get the current version
    $plugin_info = get_site_transient("update_plugins");
    $current_version = $plugin_info->checked[$plugin_slug ."/". $plugin_slug .".php"];
    $args->version = $current_version;
    $request_args = array(
        "name" => $plugin_slug,
        "version" => $current_version,
    );
    $request_string = dtbaker_prepare_request'.$id.'($action, $request_args);
    $request = wp_remote_post("'.$url.'", $request_string);
    if (is_wp_error($request)) {
        $res = new WP_Error("plugins_api_failed", __("An Unexpected HTTP Error occurred during the API request.</p>"), $request->get_error_message());
    } else {
        $res = unserialize($request["body"]);
        if ($res === false)
            $res = new WP_Error("plugins_api_failed", __("An unknown error occurred"), $request["body"]);
    }
    return $res;
}
}
/***** END AUTOMATIC UPDATE CODE *******/
?>';
echo (htmlspecialchars(ob_get_clean()));
                    ?></pre></div>
            </td>
        </tr>
        <?php }else{ ?>
                <tr>
                    <td>Automatic Updates for Themes</td>
                    <td>
                        Sorry I haven't tested the theme
                        automatic update feature enough - I will be releasing the code for it soon.
                        :)
                    </td>
                </tr>
        <?php } ?>
        <?php } ?>
        </tbody>
    </table>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes');?>"></p>
</form>

