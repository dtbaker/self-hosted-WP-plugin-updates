<?php

global $wpdb;
$table_name = $wpdb->prefix . "wpupdatemanager";

$sql = "SELECT tp.*";
$sql .= ", COUNT(i.install_id) AS install_count";
$sql .= ", (SELECT i2.`time` FROM `".$wpdb->prefix."wpupdatemanager_install` i2 WHERE i2.id = tp.id ORDER BY i2.`time` DESC LIMIT 1) AS last_install";
$sql .= ", (SELECT v2.version_number FROM `".$wpdb->prefix."wpupdatemanager_version` v2 WHERE tp.id = v2.id ORDER BY v2.version_number DESC LIMIT 1) AS latest_version";
$from = " FROM `".$table_name."` tp ";
$from .= " LEFT JOIN `".$wpdb->prefix."wpupdatemanager_install` i ON tp.id = i.id";
$where = " WHERE 1 ";
$group_by = ' GROUP BY tp.id ';
$order_by = ' ORDER BY tp.name DESC';
$sql = $sql . $from . $where . $group_by . $order_by;
//$wpdb->show_errors();
$all_plugins = $wpdb->get_results($sql,ARRAY_A);
//echo $sql;

$columns = array(
    'name' => 'Name',
    'type' => 'Plugin/Theme',
    'latest_version' => 'Latest Version',
    'install_count' => 'Install Count',
    'last_install' => 'Last Install',
);
register_column_headers('wpupdatemanager-list', $columns);

?>

<h2>
    <?php _e('Hosted Themes and Plugins', 'wpupdatemanager'); ?>
    <a href="admin.php?page=wpupdatemanager-dashboard&id=new" class="button add-new-h2"><?php _e('Add New', 'wpupdatemanager');?></a>
</h2>

<table class="widefat page fixed" cellspacing="0">
    <thead>
    <tr>
        <?php print_column_headers('wpupdatemanager-list'); ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($all_plugins as $plugin){
        $plugin=(array)$plugin;
        ?>
        <tr>
            <td class="row_action" nowrap="">
                <a href="admin.php?page=wpupdatemanager-dashboard&id=<?php echo $plugin['id'];?>&view=foo"><?php echo trim($plugin['name']) ? htmlspecialchars($plugin['name']) : 'N/A' ;?></a>
            </td>
            <td>
                <?php echo htmlspecialchars($plugin['type']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($plugin['latest_version']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($plugin['install_count']); ?>
            </td>
            <td>
                <?php
                if($plugin['last_install']){
                    echo date(get_option('date_format','Y-m-d').' '.get_option('time_format','H:i:s'),($plugin['last_install']));
                } ?>
            </td>
        </tr>
        <?php  } ?>
    </tbody>
</table>
