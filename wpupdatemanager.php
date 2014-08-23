<?php
/*
	Plugin Name: WordPress Hosted Update Manager
	Plugin URI: http://dtbaker.com.au/
	Description: Allow a developer to host their own WordPress Theme/Plugin update server
	Version: 1.0
	Author:  David Baker
	Author URI: http://dtbaker.com.au
    Date: Nov 30th 2011
    Text Domain: wpupdatemanager
*/


define('_TMP_DIR',plugin_dir_path(__FILE__).'temp/');
if((!is_dir(_TMP_DIR) || !is_writable(_TMP_DIR)) && current_user_can('manage_options')){
    echo 'Please make sure the folder "'.plugin_dir_url(__FILE__).'temp/'.'" has PHP write permissions. 
    Please delete this plugin and install it via WordPress upload zip feature.';
    exit;
}


add_action('init','wpupdatemanager_session');
add_action('admin_menu', 'wpupdatemanager_menu');

if(!load_plugin_textdomain('wpupdatemanager','/wp-content/languages/')){
    load_plugin_textdomain('wpupdatemanager',plugin_dir_path(__FILE__).'i8n/');
}

if(!function_exists('array_to_object')){
    function array_to_object($array = array()) {
        if (empty($array) || !is_array($array))
            return false;

        $data = new stdClass;
        foreach ($array as $akey => $aval)
            $data->{$akey} = $aval;
        return $data;
    }
}

function wpupdatemanager_stripslashes_deep($array){
    foreach($array as $key=>$val){
        if(is_array($val)){
            $array[$key] = wpupdatemanager_stripslashes_deep($val);
        }else{
            $array[$key] = stripslashes($val);
        }
    }
    return $array;
}

function wpupdatemanager_session(){


    //check for posted args to see if the user is requesting a plugin update.
    if(isset($_REQUEST['wpupdatemanager']) && isset($_REQUEST['item_id'])){
        global $wpdb;
        @ob_end_clean();
        // do something
        $tp = array();
        $id = (int)$_REQUEST['item_id'];
        if($id<=0)exit;
        // grab this item.
        $table_name = $wpdb->prefix . "wpupdatemanager";
        $sql = "SELECT * FROM `$table_name` WHERE `id` = ".(int)$id;
        $tp = array_shift($wpdb->get_results($sql,ARRAY_A));
        if($tp && $tp['id'] == $id){
            // success! we have this item in stock.
            // do we upgrade?
            $args = isset($_REQUEST['args']) ? @unserialize($_REQUEST['args']) : array();
            if(!$args){
                $_POST['args'] = $_REQUEST['args'] = stripslashes($_REQUEST['args']);
                $args = isset($_REQUEST['args']) ? @unserialize($_REQUEST['args']) : array();
            }
            if (is_array($args)){
                $args = array_to_object($args);
            }
            $installed_version = isset($args->version) ? $args->version : false;
            $slug = isset($args->name) ? $args->name : false;
            if($slug && $installed_version > 0){
                // they already have a version isntalled. woot
                $sql = "SELECT v.*";
                $sql .= ", COUNT(i.install_id) AS install_count";
                $from = " FROM `".$wpdb->prefix."wpupdatemanager_version` v ";
                $from .= " LEFT JOIN `".$wpdb->prefix."wpupdatemanager_install` i ON v.version_id = i.version_id";
                $where = " WHERE 1 ";
                $where .= " AND v.id = ".(int)$id;
                $group_by = ' GROUP BY v.version_id ';
                $order_by = ' ORDER BY v.version_number DESC';
                $sql = $sql . $from . $where . $group_by . $order_by;
                $latest_version = array_shift($wpdb->get_results($sql,ARRAY_A));


                $sql = "SELECT v.*";
                $from = " FROM `".$wpdb->prefix."wpupdatemanager_version` v ";
                $where = " WHERE 1 ";
                $where .= " AND v.version_number = '".mysql_real_escape_string($installed_version)."'";
                $where .= " AND v.id = '".$id."'";
                $sql = $sql . $from . $where;
                $installed_version_in_db = array_shift($wpdb->get_results($sql,ARRAY_A));

                if($latest_version && $latest_version['id'] == $id){
                    //we have a latest version!
                    // we have to check envato now. if it's enabled.
                    $data = array();
                    if($tp['envato']==1){
                        $users_licence_code = '';
                        $envato_success = false;
                        // time to check envato purchase code.
                        $users_licence_code = $_REQUEST['envatolicence'];
                        $envato_item_id = (int)$_REQUEST['envatoitem'];
                        if($envato_item_id && $envato_item_id = $tp['envato_item']){
                            // format code correclty (ie: users enter it without dashes for some reason)
                            $users_licence_code = preg_replace('#([a-z0-9]{8})-?([a-z0-9]{4})-?([a-z0-9]{4})-?([a-z0-9]{4})-?([a-z0-9]{12})#','$1-$2-$3-$4-$5',$users_licence_code);
                            if($users_licence_code){
                                $api_url = "http://marketplace.envato.com/api/edge/".$tp['envato_username']."/".
                                    $tp['envato_api_key']."/verify-purchase:$users_licence_code.json";
                                //$data = '{"verify-purchase":{"buyer":"buyer_id_here","created_at":"Wed Dec 01 05:00:47 +1100 2010","licence":"Regular Licence","item_name":"Your Item Name Here","item_id":"116430"}}';
                                $ch = curl_init($api_url);
                                curl_setopt($ch,CURLOPT_HEADER,false);
                                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                                $data = json_decode(curl_exec($ch),true);
                                if($data && $data['verify-purchase'] && $data['verify-purchase']['item_id'] == $tp['envato_item']){
                                    // valid purchase!
                                    $envato_success = true;

                                }
                            }
                        }
                        if(!$envato_success){
                            // return a nice error message if the user is just validating the plugin.
                            echo 'invalid envato';
                            exit;
                        }
                    }
                    // add this as an installation - if it doesn't already exist.
                    if($installed_version_in_db && $installed_version_in_db['version_id']){
                        $install_url = trim($_REQUEST['install']);
                        $sql = "SELECT * FROM `".$wpdb->prefix."wpupdatemanager_install` i ";
                        $sql .= " WHERE id = $id ";
                        $sql .= " AND `ip` = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."'";
                        $sql .= " AND `version_id` = '".$installed_version_in_db['version_id']."'";
                        if($tp['envato']==1){
                            $sql .= " AND `licence_code` = '".mysql_real_escape_string($users_licence_code)."'";
                        }
                        // sometimes its not provided.
                        $sql .= " AND `url` = '".mysql_real_escape_string($install_url)."'";
                        $previous_install = array_shift($wpdb->get_results($sql,ARRAY_A));
                        if($previous_install && $previous_install['install_id']){
                            $sql =  "UPDATE `".$wpdb->prefix."wpupdatemanager_install` SET `time` = '".time()."' WHERE install_id = ".(int)$previous_install['install_id']." LIMIT 1";
                            $wpdb->query($sql);
                        }else{
                            $sql =  "INSERT INTO `".$wpdb->prefix."wpupdatemanager_install` SET ";
                            $sql .= " `time` = '".time()."'";
                            $sql .= ", `id` = '".$id."'";
                            if($tp['envato']==1){
                                $sql .= " , `licence_code` = '".mysql_real_escape_string($users_licence_code)."'";
                                $sql .= " , `data` = '".mysql_real_escape_string(serialize($data))."'";
                            }
                            $sql .= ", `version_id` = '".$installed_version_in_db['version_id']."'";
                            $sql .= ", `ip` = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."'";
                            $sql .= ", `url` = '".mysql_real_escape_string($install_url)."'";
                            $wpdb->query($sql);
                        }
                    }
                    file_put_contents('/tmp/wpupdate1.txt',file_get_contents('/tmp/wpupdate1.txt')."\n".var_export($installed_version_in_db,true).var_export($latest_version,true));
                    // installation recorded! woo.

                    switch(isset($_REQUEST['action']) ? $_REQUEST['action'] : false){
                        case 'check_for_updates':
                            // check if latest version is bigger than installed version.
                            if (version_compare($installed_version, $latest_version['version_number'], '<')){
                                // we have a new version! woo!
                                $update_info = new stdClass();
                                $update_info->description = '';
                                $update_info->last_updated = $latest_version['date_created'];
                                $update_info->version = $latest_version['version_number'];
                                $update_info->new_version = $latest_version['version_number'];
                                $update_info->slug = $slug;
                                $url = get_home_url();
                                if(!preg_match('#/$#',$url)){
                                    $url.='/';
                                }
                                $url .= '?wpupdatemanager=true&item_id='.$id.'&';
                                foreach($_POST as $key=>$val){
                                    if($key=='action')$val='download_update';
                                    $url .= $key.'='.($val).'&';
                                }
                                $update_info->package = $url;
                                print serialize($update_info);
                                exit;
                            }
                            break;
                        case 'plugin_information':
                            $data = new stdClass;
                            $data->slug = $slug;
                            $data->version = $latest_version['version_number'];
                            $data->last_updated = $latest_version['date_created'];
                            $url = get_home_url();
                            if(!preg_match('#/$#',$url)){
                                $url.='/';
                            }
                            $url .= '?wpupdatemanager=true&item_id='.$id.'&';
                            foreach($_POST as $key=>$val){
                                if($key=='action')$val='download_update';
                                $url .= $key.'='.($val).'&';
                            }
                            $data->download_link = $url;

                            $data->author = $tp['author'];
                            $data->requires = $latest_version['requires'];
                            $data->tested = $latest_version['tested'];
                            $data->homepage = $tp['homepage'];
                            $data->downloaded = (int)$latest_version['install_count'];

                            $data->sections = array(
                                'description' => '<h2>Update To: '.$tp['name'].' </h2>'. '<p>Slug: '.$slug.'</p>'
                                // todo: more description here
                            );
                            print serialize($data);
                            break;
                        case 'download_update':
                            // grab the zip file, and install away.

                            $install_url = trim($_REQUEST['install']);
                            $sql = "SELECT * FROM `".$wpdb->prefix."wpupdatemanager_install` i ";
                            $sql .= " WHERE id = $id ";
                            $sql .= " AND `ip` = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."'";
                            $sql .= " AND `version_id` = '".$latest_version['version_id']."'";
                            if($tp['envato']==1){
                                $sql .= " AND `licence_code` = '".mysql_real_escape_string($users_licence_code)."'";
                            }
                            // sometimes its not provided.
                            $sql .= " AND `url` = '".mysql_real_escape_string($install_url)."'";
                            $previous_install = array_shift($wpdb->get_results($sql,ARRAY_A));
                            if($previous_install && $previous_install['install_id']){
                                $sql =  "UPDATE `".$wpdb->prefix."wpupdatemanager_install` SET `time` = '".time()."' WHERE install_id = ".(int)$previous_install['install_id']." LIMIT 1";
                                $wpdb->query($sql);
                            }else{
                                $sql =  "INSERT INTO `".$wpdb->prefix."wpupdatemanager_install` SET ";
                                $sql .= " `time` = '".time()."'";
                                $sql .= ", `id` = '".$id."'";
                                if($tp['envato']==1){
                                    $sql .= " , `licence_code` = '".mysql_real_escape_string($users_licence_code)."'";
                                    $sql .= " , `data` = '".mysql_real_escape_string(serialize($data))."'";
                                }
                                $sql .= ", `version_id` = '".$latest_version['version_id']."'";
                                $sql .= ", `ip` = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."'";
                                $sql .= ", `url` = '".mysql_real_escape_string($install_url)."'";
                                $wpdb->query($sql);
                            }

                            $zip_url = $latest_version['zip_url'];
                            if($zip_url){
                                $ch = curl_init($zip_url);
                                curl_setopt($ch,CURLOPT_HEADER,false);
                                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                                $zip_contents = curl_exec($ch);
                                // todo: unzip this file locally, modify contents for a per-installation basis.
                                // eg: $new_file_contents = preg_replace('#^<\?php#',"<?php \n /* new header comment */", $file_contents);
                                // for now we just pass the zip file on.
                                if($zip_contents){
                                    header("Content-type: application/octet-stream");
                                    header('Content-Disposition: attachment; filename="plugin_update.zip";');
                                    echo $zip_contents;
                                    exit;
                                }
                            }

                            break;
                    }
                }
            }
        }
        exit;
    }

    if(isset($_REQUEST['_wpupdatemanager_process']) && current_user_can('manage_options')){
        $_POST = wpupdatemanager_stripslashes_deep($_POST);
        $_GET = wpupdatemanager_stripslashes_deep($_GET);
        $_REQUEST = $_GET + $_POST;
        switch($_REQUEST['_wpupdatemanager_process']){
            case 'save_item':
                if(isset($_REQUEST['butt_del']) && $_REQUEST['butt_del']){
                    echo 'todo: deleting items';
                    //header("Location: admin.php?page=wpupdatemanager-dashboard&msg=2");
                    exit;
                }
                global $wpdb;
                $table_name = $wpdb->prefix . "wpupdatemanager";
                $foo = $wpdb->get_results("SELECT * FROM `$table_name` LIMIT 1");
                $columns = $wpdb->get_col_info();
                if($columns){ // incase it's not installed?
                    $id = (int)$_REQUEST['id'];
                    if($id>0){
                        $sql = "UPDATE `$table_name` SET ";
                    }else{
                        $sql = "INSERT INTO `$table_name` SET ";
                    }
                    array_shift($columns); // dont update the primary key.
                    foreach($columns as $column){
                        if(isset($_REQUEST[$column])){
                            $sql .= " `".mysql_real_escape_string($column)."` = '".mysql_real_escape_string($_REQUEST[$column])."', ";
                        }
                    }
                    $sql = rtrim($sql,', ');
                    if($id>0){
                        $sql .= " WHERE `id` = ".(int)$id." LIMIT 1";
                    }
                    $wpdb->query($sql);
                    if($id<=0){
                        $id = $wpdb->insert_id;
                    }
                    header("Location: admin.php?page=wpupdatemanager-dashboard&id=$id&msg=2");
                    exit;
                }
                // todo - meh.
                echo 'save failed';exit;
                break;
            case 'save_version':
                if(isset($_REQUEST['butt_del']) && $_REQUEST['butt_del']){
                    echo 'todo: deleting items';
                    //header("Location: admin.php?page=wpupdatemanager-dashboard&msg=2");
                    exit;
                }
                global $wpdb;
                $table_name = $wpdb->prefix . "wpupdatemanager_version";
                $foo = $wpdb->get_results("SELECT * FROM `$table_name` LIMIT 1");
                $columns = $wpdb->get_col_info();
                if($columns){ // incase it's not installed?
                    $id = (int)$_REQUEST['id'];
                    $version_id = (int)$_REQUEST['version_id'];
                    if($version_id>0){
                        $sql = "UPDATE `$table_name` SET ";
                    }else{
                        $sql = "INSERT INTO `$table_name` SET ";
                    }
                    array_shift($columns); // dont update the primary key.
                    foreach($columns as $column){
                        if(isset($_REQUEST[$column])){
                            $sql .= " `".mysql_real_escape_string($column)."` = '".mysql_real_escape_string($_REQUEST[$column])."', ";
                        }
                    }
                    $sql = rtrim($sql,', ');
                    if($version_id>0){
                        $sql .= " WHERE `version_id` = ".(int)$version_id." LIMIT 1";
                    }
                    $wpdb->query($sql);
                    if($version_id<=0){
                        $version_id = $wpdb->insert_id;
                    }
                    header("Location: admin.php?page=wpupdatemanager-dashboard&id=$id&version_id=$version_id&msg=2");
                    exit;
                }
                // todo - meh.
                echo 'save failed';exit;
                break;
        }
    }
}

function wpupdatemanager_menu() {
    // todo: work out the user permissions as set by the administrator, which users can see which menu items.
    if(current_user_can('manage_options')){
        add_menu_page('Update Manager', 'Update Manager', 'manage_options', 'wpupdatemanager-dashboard', 'wpupdatemanager_menu_init');
    }
}

function wpupdatemanager_menu_init(){
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 'wpupdatemanager-dashboard';
    switch($page){
        case 'wpupdatemanager-dashboard':
        default:
            if(isset($_REQUEST['id'])){
                if(isset($_REQUEST['version_id'])){
                    include('layout_plugin_theme_version_edit.php');
                }else{
                    include('layout_plugin_theme_edit.php');
                }
            }else{
                include('layout_plugin_theme_list.php');
            }
            break;
    }
}


/** DATABSE STUFF  **/

function wpupdatemanager_install () {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . "wpupdatemanager";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $sql = "CREATE TABLE " . $table_name . " (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL DEFAULT '',
          `type` varchar(255) NOT NULL DEFAULT 'plugin',
          `author` varchar(255) NOT NULL DEFAULT '',
          `homepage` varchar(255) NOT NULL DEFAULT '',
          `envato` tinyint(1) NOT NULL DEFAULT '1',
          `envato_item` int(11) NOT NULL DEFAULT '0',
          `envato_username` varchar(255) NOT NULL DEFAULT '',
          `envato_api_key` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY prid (id)
        );";
        dbDelta($sql);
    }
    $table_name = $wpdb->prefix . "wpupdatemanager_install";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $sql = "CREATE TABLE " . $table_name . " (
        `install_id` mediumint(9) NOT NULL AUTO_INCREMENT,
          `version_id` int(11) NOT NULL DEFAULT '0',
          `id` int(11) NOT NULL DEFAULT '0',
          `url` varchar(255) NOT NULL DEFAULT '',
          `licence_code` varchar(255) NOT NULL DEFAULT '',
          `ip` varchar(20) NOT NULL DEFAULT '',
          `data` longtext NOT NULL DEFAULT '',
          `time` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY `install_id` (install_id)
        );";
        dbDelta($sql);
    }
    $table_name = $wpdb->prefix . "wpupdatemanager_version";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $sql = "CREATE TABLE " . $table_name . " (
        `version_id` mediumint(9) NOT NULL AUTO_INCREMENT,
          `id` int(11) NOT NULL DEFAULT '0',
          `date_created` DATE NULL,
          `version_number` varchar(10) NOT NULL DEFAULT '0',
          `requires` varchar(10) NOT NULL DEFAULT '3.2',
          `tested` varchar(10) NOT NULL DEFAULT '3.2',
          `zip_url` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY version_id (version_id)
        );";
        dbDelta($sql);
    }


    // some simple code to handle database upgrades.
    // it simply checks if the column exists and if not, create it.
    $table_name = $wpdb->prefix . "wpupdatemanager_version";
    if ( !$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'zip_url'")  ) {
        $mysql = "ALTER TABLE $table_name ADD `zip_url` varchar(255) NOT NULL DEFAULT '';";
        $wpdb->query($mysql);
    }
    $table_name = $wpdb->prefix . "wpupdatemanager_install";
    if ( !$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'ip'")  ) {
        $mysql = "ALTER TABLE $table_name ADD `ip` varchar(20) NOT NULL DEFAULT '';";
        $wpdb->query($mysql);
    }


}
register_activation_hook(__FILE__,'wpupdatemanager_install');


include_once('plugin_update.php');

