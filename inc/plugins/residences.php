<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

// Backend Hooks
$plugins->add_hook("admin_formcontainer_end", "residences_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "residences_usergroup_permission_commit");

function residences_info()
{
    return array(
        "name"			=> "Residenzen Verwaltung",
        "description"	=> "Hier kannst du die Residenz der Charakter verwalten. Hierbei können User selbstständig Wohnungen einreichen und sich in diese dann eintragen. ",
        "website"		=> "",
        "author"		=> "Ales",
        "authorsite"	=> "",
        "version"		=> "1.0",
        "guid" 			=> "",
        "codename"		=> "",
        "compatibility" => "*"
    );
}

function residences_install()
{
    global  $db, $cache;

    //Datenbank erstellen
    if($db->engine=='mysql'||$db->engine=='mysqli')
    {
        $db->query("CREATE TABLE `".TABLE_PREFIX."places` (
          `place_id` int(10) NOT NULL auto_increment,
          `country` varchar(500) CHARACTER SET utf8 NOT NULL,
          `place` varchar(500) CHARACTER SET utf8 NOT NULL,
                      `accepted` int(10) NOT NULL,
                 `uid` int(10) NOT NULL,
          PRIMARY KEY (`place_id`)
        ) ENGINE=MyISAM".$db->build_create_table_collation());

        $db->query("CREATE TABLE `".TABLE_PREFIX."residence` (
          `res_id` int(10) NOT NULL auto_increment,
          `place_id` int(11) NOT NULL,
          `residence` varchar(500) CHARACTER SET utf8 NOT NULL,
          `description` varchar(500) CHARACTER SET utf8 NOT NULL,
          `kind` varchar(500) CHARACTER SET utf8 NOT NULL,
          `personcount` varchar(500) CHARACTER SET utf8 NOT NULL,
            `accepted` int(10) NOT NULL,
                 `uid` int(10) NOT NULL,
          PRIMARY KEY (`res_id`)
        ) ENGINE=MyISAM".$db->build_create_table_collation());
    }

    $db->query("ALTER TABLE `".TABLE_PREFIX."users` ADD `res_id` int(10) NOT NULL;");

    $db->add_column("usergroups", "canaddplace", "tinyint NOT NULL default '1'");
    $db->add_column("usergroups", "canjoinplace", "tinyint NOT NULL default '1'");
    $cache->update_usergroups();

    /*
 * nun kommen die Einstellungen
 */
    $setting_group = array(
        'name' => 'residences',
        'title' => 'Einstellungen für die Wer wohnt wo?',
        'description' => 'Hier kannst du die Einstellungen für den wer wohnt wo? Plugin machen.',
        'disporder' => 2,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'name' => 'reseidences_countrys',
        'title' => 'Länder',
        'description' => 'Welche Länder gibt es zur Auswahl?',
        'optionscode' => 'textarea',
        'value' => 'England, Schottland, Wales, Nordirland, Irland',
        'disporder' => 1,
        "gid" => (int)$gid
    );
    $db->insert_query('settings', $setting_array);



    rebuild_settings();

    //Templates

    $insert_array = array(
        'title'        => 'residences',
        'template'    => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->wholiveswho}</title>
{$headerinclude}
</head>
<body>
{$header}
	{$menu}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->wholiveswho}</strong></td>
</tr>
<tr>
<td class="trow1" align="center">
	{$residence_alert}
{$residences_formplace}
	{$residences_formresidence}
</td>
</tr>
	<tr>
		<td>
			{$residences_country}
		</td>
	</tr>
</table>
{$footer}
</body>
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_country',
        'template'    => $db->escape_string('<table width="100%">
	<tr><td class="tcat" colspan="2"><strong>{$country}</strong></td></tr>
	{$residences_place}
</table>
'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title'        => 'residences_place',
        'template'    => $db->escape_string('<tr><td class="tcat"><div class="headline3">{$placename}</div></td></tr>
<tr><td><div class="flex">
{$residences_home}
	</div>
	</td></tr>
'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


    $insert_array = array(
        'title'        => 'residences_edit',
        'template'    => $db->escape_string('<form action="misc.php?action=residences" id="residences" method="post">
	  <input type="hidden" name="res_id" id="res_id" value="{$res_id}" class="textbox" />
<table style="width: 80%;"  border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr><td class="thead" colspan="2"><strong>Wohnung editieren</strong></td></tr>
<tr><td class="tcat" width="50%"><strong>{$lang->wlw_country}</strong></td>
<td class="tcat"  width="50%"><strong>{$lang->wlw_place}</strong></td>
</tr>
<tr><td class="trow1"align="center">
		<select name="place_id">
			<option value="%">Stadt wählen</option>
			{$places_editselect}
		</select>
</td>
<td class="trow2" align="center">
<input type="text" name="residence" id="residence" value="{$homes[\'residence\']}" class="textbox" style="width: 70%;" />
	</td></tr>
	<tr>
		<tr><td class="tcat" colspan="2"><strong>{$lang->wlw_desc}</strong></td>
	</tr><tr>
<td class="trow1" align="center" colspan="2">
<textarea name="description" id="description" style="width: 500px; height:100px;">{$homes[\'description\']}</textarea>
	</td></tr>
	<tr><td class="tcat"><strong>{$lang->wlw_residents}</strong></td>
		<td class="tcat"><strong>{$lang->wlw_kind}</strong></td></tr>
	<tr>
	<td class="trow2" align="center">
<input type="number" name="personcount" id="personcount" value="{$homes[\'personcount\']}" class="textbox" />
</td>
	<td class="trow1" align="center">
		<select name="kind">
			<option value="{$homes[\'kind\']}">{$homes[\'kind\']}</option>
			<option value="Apartment">Apartment</option>
			<option value="Haus">Haus</option>
			<option value="Heimtlos">Heimtlos</option>
				<option value="Farm">Farm</option>
			<option value="Loft">Loft</option>
			<option value="Villa">Villa</option>
			<option value="Wohngemeinschaft">WG</option>
			<option value="Wohnung">Wohnung</option>
		</select>
		</td>
	</tr>
	
	<tr>
<td colspan="5" class="trow1" align="center"><input type="submit" name="edithome" value="Wohnort editieren" id="submit" class="button"></td></tr>
</table>
</form>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_edit_place',
        'template'    => $db->escape_string('<form action="modcp.php?action=residences" id="places" method="post">
	  <input type="hidden" name="place_id" id="place_id" value="{$place_id}" class="textbox" />
<table width="80%">
<tr><td class="thead" colspan="4"><strong>{$lang->wlw_edit_place}</strong></td></tr>
<tr><td><strong>{$lang->wlw_country}</strong></td><td><strong>{$lang->wlw_place}</strong></td><td><strong>{$lang->wlw_edit}</strong></td></tr>
<tr>
<td class="trow1" align="center">
	<select name="country" id="country">
{$country_select}
	</select>
</td>
<td class="trow2" align="center">
<input type="text" name="place" id="place" value="{$place}" class="textbox" />
</td>
<td colspan="2" class="trow1" align="center"><input type="submit" name="editplace" value="Ort bearbeiten" id="submit" class="button"></td></tr>
</table>
</form>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_formplace',
        'template'    => $db->escape_string('<form action="misc.php?action=residences" id="places" method="post">
<table width="100%">
<tr><td class="thead" colspan="4"><strong>{$lang->wlw_add_new_world}</strong></td></tr>
<tr><td class="tcat"><strong>{$lang->wlw_country}</strong></td>
<td class="tcat"><strong>{$lang->wlw_place}</strong></td>
<td class="tcat"><strong>{$lang->wlw_add}</strong></td></tr>
<tr>
<td class="trow1" align="center">
	<select name="country" id="country">
{$country_select}
	</select>
</td>
<td class="trow2" align="center">
<input type="text" name="place" id="place" value="" class="textbox" />
</td>
<td colspan="2" class="trow1" align="center">	
	<input type="submit" name="addplace" value="Ort hinzufügen" id="submit" class="button"></td></tr>
</table>
</form><br />'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_formresidence',
        'template'    => $db->escape_string('<form action="misc.php?action=residences" id="residences" method="post">
<table width="100%">
<tr><td class="thead" colspan="5"><strong>{$lang->wlw_add_home}</strong></td></tr>
<tr><td class="tcat"><strong>{$lang->wlw_homeplace}</strong></td>
<td class="tcat"><strong>{$lang->wlw_placename}</strong></td>
<td class="tcat"><strong>{$lang->wlw_desc}</strong></td>
<td class="tcat"><strong>{$lang->wlw_residents}</strong></td>
<td class="tcat"><strong>{$lang->wlw_kind}</strong></td></tr>
<tr><td class="trow1"align="center">
		<select name="place_id">
			<option value="%">Ort wählen</option>
			{$places_select}
		</select>
</td>
<td class="trow2" align="center">
<input type="text" name="residence" id="residence" value="" class="textbox" />
</td>
<td class="trow1" align="center">
<textarea name="description" id="description" style="width: 200px; height: 50px;"></textarea>
	</td>
	<td class="trow2" align="center">
<input type="number" name="personcount" id="personcount" value="0" class="textbox" />
</td>
	<td class="trow1" align="center">
		<select name="kind">
			<option value="#">{$lang->wlw_kind} wählen</option>
			<option value="Apartment">Apartment</option>
				<option value="Farm">Farm</option>
			<option value="Haus">Haus</option>
			<option value="Loft">Loft</option>
				<option value="Penthouse">Penthouse</option>
			<option value="Villa">Villa</option>
			<option value="Wohngemeinschaft">WG</option>
			<option value="Wohnung">Wohnung</option>
		</select>
		</td>
	</tr>
	
	<tr>
<td colspan="5" class="trow1" align="center"><input type="submit" name="addhome" value="Ort hinzufügen" id="submit" class="button"></td></tr>
</table>
</form><br />'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_home',
        'template'    => $db->escape_string('<div class="residences_home">
	<div class="home_title">{$residence}</div>
	<div class="home_kind">{$kind}</div>
	<div class="home_desc">{$description}</div>
	<div class="home_info">{$personcount}
		{$residences_resident}
{$move_in}
	</div>	
{$home_options}
</div>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_modcp',
        'template'    => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->wlw_modcp}</title>
{$headerinclude}

</head>
<body>
	{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$modcp_nav}
				<td valign="top">
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr><td class="thead"><div class="headline">{$lang->wlw_modcp}</div></td> </tr>
						<tr>
							<td width="100%" class="trow1" valign="top">
					<table width="80%" style="margin: auto;">
					<tr><td class="thead" align="center" colspan="3">
						<div class="modcp_name">{$lang->wlw_places}</div>		</td></tr>	<tr><td class="trow1" colspan="3" align="center">{$accept_all_place}</td></tr>
					<tr><td><div class="modcp_cat">{$lang->wlw_country}</div></td><td><div class="modcp_cat">{$lang->wlw_place}</div></td><td><div class="modcp_cat">{$lang->wlw_modcp_options}</div></td></tr>
					{$residences_modcp_country}
						
					
						</table>
					<br />
					<br />
						<table width="80%" style="margin: auto;">
					<tr><td class="thead" align="center" colspan="2">
					<div class="modcp_name">{$lang->wlw_homes}</div>		</td></tr>
<tr><td class="trow1" colspan="2" align="center">{$accept_all_home}</td></tr>
					{$residences_modcp_home}
											
						</table>
										<br />
					<br />
						<table width="80%" style="margin: auto;">
					<tr><td class="thead" align="center" colspan="3">
						<div class="modcp_name">{$lang->wlw_edit_place}</div>	</td></tr>
					<tr><td><div class="modcp_cat">{$lang->wlw_country}</div></td><td><div class="modcp_cat">{$lang->wlw_place}</div></td><td><div class="modcp_cat">{$lang->wlw_modcp_options}</div></td></tr>
					{$residences_modcp_places}
						</table>
</td>
</tr>
							
					</table>

				</td>
			</tr>
		</table>
	</form>
	{$footer}
</body>
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_modcp_country',
        'template'    => $db->escape_string('<tr><td class="trow1">{$country}</td><td class="trow2">{$place}</td><td class="trow1">{$accept} {$refuse}</td></tr>
'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title'        => 'residences_modcp_home',
        'template'    => $db->escape_string('<tr><td  class="tcat" colspan="2"><div class="modcp_name">({$kind}) {$residence}</td></tr>
<tr><td  class="trow2" colspan="2"> in {$place} ({$country})</td>
<tr><td class="trow1" valign="top"><div style="text-align: center;">{$personcount} | eingereicht von {$user}</div>
	<div>{$area} {$description}</div></td>
	<td class="trow2" style="text-align: center;">
		{$accept_home}
		{$refuse_home}
	</td></tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title'        => 'residences_modcp_home_refuse',
        'template'    => $db->escape_string('<form action="modcp.php?action=residences" id="home_refuse" method="post">
<table width="150px">
<tr>
<td class="trow2" align="center">
<input type="hidden" name="res_id" id="res_id" value="{$row[\'res_id\']}" class="textbox" />
<textarea class="textarea" name="refuse_reason" id="refuse_reason" rows="2" cols="15" style="width: 100%">Ablehnungsgrund angeben.</textarea>
	</td></tr>
	<tr>
<td class="trow1" align="center">	
	<input type="submit" name="refuse_home" value="Wohnort ablehnen" id="submit" class="button"></td></tr>
</table>
</form>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_modcp_places',
        'template'    => $db->escape_string('<tr><td class="trow1" align="center">{$country}</td><td class="trow2" align="center">{$place}</td><td class="trow1" align="center"><div style="font-size: 20px;">{$edit_place}<div class="modal" id="edit_{$place_id}" style="display: none;">{$edit_place_res}</div> {$delete} </div></td></tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'residences_resident',
        'template'    => $db->escape_string('<div>{$user} {$move_out}</div>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


    //CSS einfügen
    $css = array(
        'name' => 'residences.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" =>    '.home_title{
font-family: Tahoma, Verdana, Arial, Sans-Serif;
font-size: 15px;
text-transform: uppercase;
color: #333;
text-align: center;
font-weight: 200;
	letter-spacing: 3px;
}

.home_desc{
width: 100%;
	padding: 2px;
	box-sizing: border-box;
 font-family: Tahoma, Verdana, Arial, Sans-Serif;
font-size: 12px;
color: #333;
text-align: justify;
}

.home_desc a{
    font-family: Tahoma, Verdana, Arial, Sans-Serif;
    color: #333;
    font-size: 12px;
    text-decoration: none;
    text-transform: uppercase;
    font-weight: 200;
	letter-spacing: 2px;
}

.home_info{
  font-family: Tahoma, Verdana, Arial, Sans-Serif;
font-size: 12px;
color: #333;
	text-transform: uppercase;
text-align: center;
}
        ',
        'cachefile' => $db->escape_string(str_replace('/', '', 'residences.css')),
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }
}

function residences_is_installed()
{
    global $db;
    if($db->table_exists("residence"))
    {
        return true;
    }
    return false;
}

function residences_uninstall()
{
    global $db, $cache;
    if ($db->table_exists("residence")) {
        $db->drop_table("residence");
    }

    if ($db->table_exists("places")) {
        $db->drop_table("places");
    }

    if ($db->field_exists("res_id", "users")) {
        $db->drop_column("users", "res_id");
    }

    if ($db->field_exists("canaddplace", "usergroups")) {
        $db->drop_column("usergroups", "canaddplace");
    }
    if ($db->field_exists("canjoinplace", "usergroups")) {
        $db->drop_column("usergroups", "canjoinplace");
    }
    $cache->update_usergroups();


    $db->query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name='residences'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='reseidences_countrys'");

    $db->delete_query("templates", "title LIKE '%residences%'");
    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'residence.css'");
    $query = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
        rebuild_settings();
    }
}
function residences_activate()
{

    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("modcp_nav", "#" . preg_quote('{$modcp_nav_users}') . "#i", '	{$modcp_nav_users}<tr>
		<td class="tcat tcat_menu tcat_collapse{$collapsedimg[\'modcpusers\']}">
			<div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse{$collapsedimg[\'modcpusers\']}.png" id="modcpusers_img" class="expander" alt="{$expaltext}" title="{$expaltext}" /></div>
			<div><span class="smalltext"><strong>Sonstiges</strong></span></div>
		</td>
	</tr>
	<tbody style="{$collapsed[\'modcpusers_e\']}" id="modcpusers_e">
{$residence_modcp}
	</tbody>');
    find_replace_templatesets("header", "#".preg_quote('{$awaitingusers}')."#i", '{$awaitingusers}{$residence_alert_place} {$residence_alert_home}');

}

function residences_deactivate()
{
    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("modcp_nav", "#" . preg_quote('<tr>
		<td class="tcat tcat_menu tcat_collapse{$collapsedimg[\'modcpusers\']}">
			<div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse{$collapsedimg[\'modcpusers\']}.png" id="modcpusers_img" class="expander" alt="{$expaltext}" title="{$expaltext}" /></div>
			<div><span class="smalltext"><strong>Sonstiges</strong></span></div>
		</td>
	</tr>
	<tbody style="{$collapsed[\'modcpusers_e\']}" id="modcpusers_e">
{$residence_modcp}
	</tbody>') . "#i", '', 0);
    find_replace_templatesets("header", "#".preg_quote('{$residence_alert_place} {$residence_alert_home}')."#i", '', 0);
}


// Usergruppen-Berechtigungen
function residences_usergroup_permission()
{
    global $mybb, $lang, $form, $form_container, $run_module;

    if($run_module == 'user' && !empty($form_container->_title) & !empty($lang->misc) & $form_container->_title == $lang->misc)
    {
        $residences_options = array(
            $form->generate_check_box('canaddplace', 1, "Kann Ort hinzufügen?", array("checked" => $mybb->input['canaddplace'])),
            $form->generate_check_box('canjoinplace', 1, "Kann Wohnung hinzufügen?", array("checked" => $mybb->input['canjoinplace'])),
        );
        $form_container->output_row("Residenzen Verwaltung", "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $residences_options)."</div>");
    }
}

function residences_usergroup_permission_commit()
{
    global $db, $mybb, $updated_group;
    $updated_group['canaddplace'] = $mybb->get_input('canaddplace', MyBB::INPUT_INT);
    $updated_group['canjoinplace'] = $mybb->get_input('canjoinplace', MyBB::INPUT_INT);
}



$plugins->add_hook('misc_start', 'residences_misc');


function residences_misc(){
    global $mybb, $templates, $lang, $header, $headerinclude, $residence_alert, $footer, $page, $db, $places_select, $edit_resi, $edit, $place_check, $country_select, $place_delete, $lang, $home_options;
    //Die Sprachdatei
    $lang->load('residences');
    require_once MYBB_ROOT."inc/class_parser.php";;
    $parser = new postParser;

    if($mybb->get_input('action') == 'residences') {
        // Do something, for example I'll create a page using the hello_world_template
        $options = array(
            "allow_html" => 1,
            "allow_mycode" => 1,
            "allow_smilies" => 1,
            "allow_imgcode" => 1,
            "filter_badwords" => 0,
            "nl2br" => 1,
            "allow_videocode" => 0
        );
        // Add a breadcrumb
        add_breadcrumb('Wer wohnt wo?', "misc.php?action=residences");
        $uid = $mybb->user['uid'];

        $alertquery = $db->fetch_array($db->query("select *
        from ".TABLE_PREFIX."residence
        WHERE accepted = 0
        "));

        if($alertquery['uid'] == $uid){
            $residence_alert ="<div class=\"red_alert\">Dein Wohnort muss nun vom Team freigeschaltet werden.</div>";
        } else{
            $residence_alert = "";
        }

        $country_array = $mybb->settings['reseidences_countrys'];

        $countrys = explode(", ", $country_array);

        if ($mybb->usergroup['canaddplace'] == 1) {

            foreach ($countrys as $country){
                $country_select .= "<option value='{$country}'>{$country}</option>";
            }
            eval("\$residences_formplace = \"" . $templates->get("residences_formplace") . "\";");
        }

        if ($mybb->usergroup['canjoinplace'] == 1) {

            $select_place = $db->query("SELECT *
            FROM ".TABLE_PREFIX."places
            ORDER BY country ASC, place ASC
            ");

            while($places = $db->fetch_array($select_place)){
                $places_select .= "<option value='{$places['place_id']}'>{$places['place']} ({$places['country']})</option>";
            }
            eval("\$residences_formresidence= \"" . $templates->get("residences_formresidence") . "\";");
        }



        foreach ($countrys as $country){
            $residences_place = "";
            $residences_place = "";

            $query_places = $db->query("SELECT *
        from ".TABLE_PREFIX."places
        WHERE country = '".$country."'
        AND accepted = 1
        ORDER BY place ASC
        ");

            while($places = $db->fetch_array($query_places)){
                $placename = "";

                $placename = $places['place'];
                $place_id = $places['place_id'];

                $residences_home = "";

                $query_home = $db->query("SELECT *
            FROM ".TABLE_PREFIX."residence
            WHERE place_id = '".$place_id."'
            AND accepted = 1
            ORDER BY residence ASC
        
            ");

                while($homes = $db->fetch_array($query_home)){

                    $kind = "";

                    $residence = $homes['residence'];
                    $description = $parser->parse_message($homes['description'], $options);
                    $kind = $homes['kind'];



                    $res_id = $homes['res_id'];
                    $place_id = $homes['place_id'];
                    $personcount = "";
                    $residences_resident = "";
                    $move_in ="";
                    $move_out = "";
                    $house_check= "";
                    $flat_check = "";
                    $flat_share_check = "";
                    $place_check ="";
                    $places_editselect = "";
                    $home_options  = "";

                    $count = 0;
                    $resident_query = $db->query("SELECT *
                FROM ".TABLE_PREFIX."users
                WHERE res_id = '".$res_id."'
                ORDER BY username ASC
                ");

                    while($resident = $db->fetch_array($resident_query)){
                        $count++;

                        $username = format_name($resident['username'], $resident['usergroup'], $resident['displaygroup']);
                        $user = build_profile_link($username, $resident['uid']);


                        if($mybb->user['uid'] == $resident['uid']){
                             $move_out = "<a href='misc.php?action=residences&moveout={$res_id}&uid=$resident[uid]' title='Ausziehen'><i class=\"fas fa-sign-out-alt\"></i></a>";
                        } elseif($mybb->usergroup['canmodcp'] == 1){
                            $move_out = "<a href='misc.php?action=residences&moveout={$res_id}&uid=$resident[uid]' title='Ausziehen'><i class=\"fas fa-sign-out-alt\"></i></a>";
                        }

                        eval("\$residences_resident .= \"" . $templates->get("residences_resident") . "\";");

                    }


                    if($homes['personcount'] != 0){
                        $personcount = "<div style='font-size:10px;'>".$count." von ".$homes['personcount']." offenen Plätzen besetzt </div>";
                    }

                    if($mybb->usergroup['canmodcp'] == 1 OR $mybb->user['uid'] == $homes['uid']){

                        if($kind == 'houses'){
                            $house_check= "selected=\"selected\"";
                            $flat_check = "";
                            $flat_share_check = "";
                        }elseif($kind == 'flat'){
                            $house_check= "";
                            $flat_check = "selected=\"selected\"";
                            $flat_share_check = "";
                        }elseif($kind == 'flat_share'){
                            $house_check= "";
                            $flat_check = "";
                            $flat_share_check = "selected=\"selected\"";
                        }

                        $select_place = $db->query("SELECT *
            FROM ".TABLE_PREFIX."places
            ORDER BY place ASC
            ");

                        while($places = $db->fetch_array($select_place)){
                            $place_check = "";
                            $place_id_check = $places['place_id'];
                            if($place_id ==  $place_id_check){
                                $place_check = "selected";

                            }
                            $places_editselect .= "<option value='{$places['place_id']}' {$place_check}>{$places['place']} test</option>";
                        }


                        $edit = "<a onclick=\"$('#edit_{$res_id}').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== 'undefined' ? modal_zindex : 9999) }); return false;\" style=\"cursor: pointer;\">Wohnort Editieren</a> ";

                        eval("\$edit_resi = \"" . $templates->get ("residences_edit") . "\";");
                        $place_delete = "| <a href='misc.php?action=residences&place_delete=$res_id' title='Wohnort löschen'>Wohnort Löschen</a>";
                        $home_options = "<div class=\"home_control\">
                                           {$edit}<div class=\"modal\" id=\"edit_{$res_id}\" style=\"display: none;\">{$edit_resi}</div>
                                            {$place_delete}</div>";
                    }



                    if($mybb->usergroup['canjoinplace'] == 1 ) {
                        if ($mybb->user['res_id'] != $res_id) {
                            if ($homes['personcount'] != $count) {
                                $move_in = "<a href='misc.php?action=residences&movein={$res_id}' title='Einziehen'><i class=\"fas fa-truck-moving\"></i> Einziehen</a>";

                            } elseif ($homes['personcount'] == 0) {
                                $move_in = "<a href='misc.php?action=residences&movein={$res_id}' title='Einziehen'><i class=\"fas fa-truck-moving\"></i> Einziehen</a>";
                            } else {
                                $move_in = "";
                            }
                        }
                    }
                    eval("\$residences_home .= \"" . $templates->get("residences_home") . "\";");
                }

                eval("\$residences_place .= \"" . $templates->get("residences_place") . "\";");
            }

            eval("\$residences_country .= \"" . $templates->get("residences_country") . "\";");
        }



        //Den Ort hinzufügen
        if(isset($_POST['addplace'])) {
            $country  = $_POST['country'];
            $place = $_POST['place'];


            if($mybb->usergroup['canmodcp'] == 1){
                $accepted = 1;
            } else{
                $accepted = 0;
            }

            $new_record = array(
                "country" => $db->escape_string($country),
                "place" => $db->escape_string($place),
                "uid" => (int)$mybb->user['uid'],
                "accepted" => (int)$accepted
            );

            $db->insert_query("places", $new_record);
            redirect("misc.php?action=residences");
        }

        //neuen Wohnung einfügen
        if(isset($_POST['addhome'])) {
            $place_id = $_POST['place_id'];
            $residence = $_POST['residence'];
            $description = $_POST['description'];
            $kind = $_POST['kind'];
            $personcount = $_POST['personcount'];


            if($mybb->usergroup['canmodcp'] == 1){
                $accepted = 1;
            } else{
                $accepted = 0;
            }

            $new_record = array(
                "place_id" => $db->escape_string($place_id),
                "residence" => $db->escape_string($residence),
                "description" => $db->escape_string($description),
                "kind" => $db->escape_string($kind),
                "personcount" => $db->escape_string($personcount),
                "accepted" => (int) $accepted,
                "uid" => (int)$mybb->user['uid'],

            );

            $db->insert_query("residence", $new_record);
            redirect("misc.php?action=residences");
        }

        //Wohnung editieren

        if(isset($_POST['edithome'])) {
            $res_id = (int) $mybb->get_input('res_id');
            $place_id = (int) $mybb->get_input('place_id');
            $residence = $db->escape_string($mybb->get_input('residence'));
            $description = $db->escape_string($mybb->get_input('description'));
            $kind = $db->escape_string($mybb->get_input('kind'));
            $personcount = (int)$mybb->get_input('personcount');


            $db->query("UPDATE ".TABLE_PREFIX."residence SET place_id ='".$place_id."', residence = '".$residence."', description = '".$description."', kind = '".$kind."',  personcount = '".$personcount."' WHERE res_id = '".$res_id."'");
            redirect("misc.php?action=residences");
        }

        //Einziehen
        $movein = $mybb->input['movein'];
        if($movein){
            $uid = $mybb->user['uid'];

            $db->query("UPDATE ".TABLE_PREFIX."users SET res_id ='".$movein."' WHERE uid = '".$uid."'");
            redirect("misc.php?action=residences");

        }

        //Auziehen
        $moveout = $mybb->input['moveout'];
        if($moveout){
            $uid = $mybb->input['uid'];

            $db->query("UPDATE ".TABLE_PREFIX."users SET res_id = 0 WHERE uid = '".$uid."'");
            redirect("misc.php?action=residences");

        }
        //wohnort ablehnen
        $refuse_home = $mybb->input['place_delete'];
        if($refuse_home){

            $db->delete_query("residence", "res_id = '$refuse_home'");
            redirect("misc.php?action=residences");

        }

        eval("\$menu = \"".$templates->get("listen_nav")."\";");
        eval('$page = "'.$templates->get('residences').'";'); // Hier wird das erstellte Template geladen
        output_page($page);

    }
}


$plugins->add_hook("modcp_nav", "residences_modcp_nav");


function residences_modcp_nav(){
    global $residence_modcp, $lang;
    //Die Sprachdatei
    $lang->load('residences');
    $residence_modcp = "<tr><td class=\"trow1 smalltext\"><a href=\"modcp.php?action=residences\" class=\"modcp_nav_item modcp_nav_banning\">{$lang->wlw_modcp}</a></td></tr>";
}

/*
 * Hier kannst du die Orte bearbeiten
 */
$plugins->add_hook("modcp_start", "residences_modcp");
function residences_modcp() {

    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $application, $db, $page, $accept_all_place, $accept_all_home, $edit_place, $country_select, $delete, $modcp_nav, $lang;
    //Die Sprachdatei
    $lang->load('residences');
    require_once MYBB_ROOT."inc/datahandlers/pm.php";
    $pmhandler = new PMDataHandler();
    require_once MYBB_ROOT."inc/class_parser.php";;
    $parser = new postParser;

    if($mybb->get_input('action') == 'residences') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb('Wohnorte', "modcp.php?action=residences");
        $options = array(
            "allow_html" => 1,
            "allow_mycode" => 1,
            "allow_smilies" => 1,
            "allow_imgcode" => 1,
            "filter_badwords" => 0,
            "nl2br" => 1,
            "allow_videocode" => 0
        );


        //alle einegangenen Orte, welche noch vom team bearbeitet werden müssen.
        $new_places = $db->query("select *
        from ".TABLE_PREFIX."places
        where accepted = 0
        ");

        while($row = $db->fetch_array($new_places)){

            $country = $row['country'];
            $place = $row['place'];

            $accept = "<a href='modcp.php?action=residences&accept=$row[place_id]'>Ort Akzeptieren</a>";
            $refuse = "<a href='modcp.php?action=residences&refuse=$row[place_id]'>Ort Ablehnen</a>";


            eval("\$residences_modcp_country .= \"" . $templates->get("residences_modcp_country") . "\";");

        }





        //Anfragen der Wohnorte, um diese entweder abzulehnen oder anzunehmen
        $new_home = $db->query("select *, r.uid
        from ".TABLE_PREFIX."residence r
        left join ".TABLE_PREFIX."places p
        on (r.place_id = p.place_id)
        where r.accepted = 0
        ");

        while($row = $db->fetch_array($new_home)){
            $refuse_home = "";
            $residence = $row['residence'];
            $description = $parser->parse_message($row['description'], $options);
            $kind = $row['kind'];
            $uid = $row['uid'];
            $country = $row['country'];


            $chara_query = $db->query("SELECT *
            FROM ".TABLE_PREFIX."users
            WHERE uid = '".$uid."'
            ");
            $chara = $db->fetch_array($chara_query);

            $username = format_name($chara['username'], $chara['usergroup'], $chara['displaygroup']);
            $user = build_profile_link($username, $chara['uid']);

            $place = $row['place'];
            if($row['personcount'] != 0){
                $personcount = $row['personcount']." Person(en) können einziehen.";
            }

            $accept_home = "<a href='modcp.php?action=residences&accept_home=$row[res_id]'>Wohnung Akzeptieren</a>";
            eval("\$refuse_home .= \"" . $templates->get("residences_modcp_home_refuse") . "\";");

            eval("\$residences_modcp_home .= \"" . $templates->get("residences_modcp_home") . "\";");

        }

        $accept_all_place = "<a href='modcp.php?action=residences&accept_all_places=all'>Alle Orte Akzeptieren</a>";
        $accept_all_home = "<a href='modcp.php?action=residences&accept_all_home=all'>Alle Wohnorte Akzeptieren</a>";

        $country_array = $mybb->settings['reseidences_countrys'];
        $countrys = explode(", ", $country_array);

        foreach ($countrys as $country_all){

            $select_country = "";
            if($country == $country_all){
                $select_country = "selected=\"selected\"";
            }

            $country_select .= "<option value='{$country_all}' {$select_country}>{$country_all}</option>";
        }
//Gebe alle Orte aus, um sie löschen oder bearbeiten zu können
        $select = $db->query("SELECT *
        FROM ".TABLE_PREFIX."places
        order by country ASC, place ASC
        ");

        while($row = $db->fetch_array($select)){
            $place_id = $row['place_id'];

            $country = $row['country'];
            $place = $row['place'];




            $delete = "<a href='modcp.php?action=residences&delete_place=$place_id' title='Ort löschen'><i class=\"fas fa-trash-alt\"></i></a>";
            $edit_place = "<a onclick=\"$('#edit_{$place_id}').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== 'undefined' ? modal_zindex : 9999) }); return false;\" style=\"cursor: pointer;\"><i class=\"fas fa-edit\" title=\"Wohnort editieren\"></i></a>";

            eval("\$edit_place_res = \"" . $templates->get ("residences_edit_place") . "\";");
            eval("\$residences_modcp_places .= \"" . $templates->get("residences_modcp_places") . "\";");
        }

        $accept_uid = $mybb->user['uid'];
        //Ort annehmen
        $accept = $mybb->input['accept'];
        if($accept){

            $place_owner = $db->query("SELECT uid
            from ".TABLE_PREFIX."places
           WHERE place_id = '".$accept."'
            ");

            $owner_uid = $db->fetch_array($place_owner);

            $uid = $owner_uid['uid'];


            $pm_change = array(
                "subject" => "Wohnort wurde angenommen",
                "message" => "Herzlichen Glückwunsch, dein Wohnort wurde angenommen. Du kannst nun einziehen. <a href='misc.php?action=residences'>Zur Wer wohnt wo? Übersicht.</a>",
                //to: wer muss die anfrage bestätigen
                "fromid" => $accept_uid,
                //from: wer hat die anfrage gestellt
                "toid" => $uid
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }

            $db->query("UPDATE ".TABLE_PREFIX."places SET accepted =1 WHERE place_id = '".$accept."'");
            redirect("modcp.php?action=residences");
        }

        //alle Ort annehmen
        $accept_all_places = $mybb->input['accept_all_places'];
        if($accept_all_places){

            $place_owner = $db->query("SELECT uid
            from ".TABLE_PREFIX."places
           WHERE accepted = 0
            ");

            $owner_uid = $db->fetch_array($place_owner);

            $uid = $owner_uid['uid'];


            $pm_change = array(
                "subject" => "Wohnort wurde angenommen",
                "message" => "Herzlichen Glückwunsch, dein Wohnort wurde angenommen. Du kannst nun einziehen. <a href='misc.php?action=residences'>Zur Wer wohnt wo? Übersicht.</a>",
                //to: wer muss die anfrage bestätigen
                "fromid" => $accept_uid,
                //from: wer hat die anfrage gestellt
                "toid" => $uid
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }
            $db->query("UPDATE ".TABLE_PREFIX."places SET accepted = 1");
            redirect("modcp.php?action=residences");
        }

        //Ort ablehnen
        $refuse_place = $mybb->input['refuse'];
        if($refuse_place){

            $place_owner = $db->query("SELECT *
            from ".TABLE_PREFIX."places
           WHERE place_id = '".$refuse_place."'
            ");

            $owner_uid = $db->fetch_array($place_owner);

            $uid = $owner_uid['uid'];

            $place = $owner_uid['place'];


            $pm_change = array(
                "subject" => "Dein Ort wurde abgelehnt",
                "message" => "Leider wurde dein Ort <b>{$place}</b> abgelehnt.  Wende dich gerne ans Team für mehr informationen.<br /> <a href='misc.php?action=residences'>Zur Wer wohnt wo? Übersicht.</a>",
                //to: wer muss die anfrage bestätigen
                "fromid" => $accept_uid,
                //from: wer hat die anfrage gestellt
                "toid" => $uid
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }

            $db->delete_query("places", "place_id = '$refuse'");
            redirect("modcp.php?action=residences");

        }


        //wohnort annehmen
        $accept_home = $mybb->input['accept_home'];
        if($accept_home){

            $home_owner = $db->query("SELECT uid
            from ".TABLE_PREFIX."residence
           WHERE res_id = '".$accept_home."'
            ");

            $owner_uid = $db->fetch_array($home_owner);

            $uid = $owner_uid['uid'];


            $pm_change = array(
                "subject" => "Wohnort wurde angenommen",
                "message" => "Herzlichen Glückwunsch, dein Wohnort wurde angenommen. Du kannst nun einziehen. <a href='misc.php?action=residences'>Zur Wer wohnt wo? Übersicht.</a>",
                //to: wer muss die anfrage bestätigen
                "fromid" => $accept_uid,
                //from: wer hat die anfrage gestellt
                "toid" => $uid
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }

            $db->query("UPDATE ".TABLE_PREFIX."residence SET accepted =1 WHERE res_id = '".$accept_home."'");
            redirect("modcp.php?action=residences");
        }

        //alle wohnorte annehmen
        $accept_all_residence = $mybb->input['accept_all_home'];
        if($accept_all_residence){

            $home_owner = $db->query("SELECT uid
            from ".TABLE_PREFIX."residence
           WHERE accepted = 0
            ");

            $owner_uid = $db->fetch_array($home_owner);

            $uid = $owner_uid['uid'];


            $pm_change = array(
                "subject" => "Wohnort wurde angenommen",
                "message" => "Herzlichen Glückwunsch, dein Wohnort wurde angenommen. Du kannst nun einziehen. <a href='misc.php?action=residences'>Zur Wer wohnt wo? Übersicht.</a>",
                //to: wer muss die anfrage bestätigen
                "fromid" => $accept_uid,
                //from: wer hat die anfrage gestellt
                "toid" => $uid
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }

            $db->query("UPDATE ".TABLE_PREFIX."residence SET accepted = 1");
            redirect("modcp.php?action=residences");
        }

        //wohnort ablehnen
        //wohnort ablehnen
        if(isset($_POST['refuse_home'])) {

            $refuse_home = $_POST['res_id'];
            $refuse_reason = $_POST['refuse_reason'];

            $home_owner = $db->query("SELECT *
            from ".TABLE_PREFIX."residence
           WHERE res_id = '".$refuse_home."'
          
            ");

            $owner_uid = $db->fetch_array($home_owner);

            $uid = $owner_uid['uid'];
            $residence = $owner_uid['residence'];
            $description = $parser->parse_message($owner_uid['description'], $options);
            $kind = $owner_uid['kind'];



            $pm_change = array(
                "subject" => "Wohnort wurde abgelehnt",
                "message" => "Leider wurde dein Wohnort abgelehnt. <br /> Als Grund wurde folgendes angegeben: {$refuse_reason} <br />
                    Das sind die Informationen, die du angegeben hast:<br />
                   <b>Wohnort</b> {$residence} ({$kind})<br />
                   <b>Beschreibung</b> {$description}<br />
                   <a href='misc.php?action=residences'>Zur Wer wohnt wo? Übersicht.</a>",
                //to: wer muss die anfrage bestätigen
                "fromid" => $accept_uid,
                //from: wer hat die anfrage gestellt
                "toid" => $uid
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }

            $db->delete_query("residence", "res_id = '$refuse_home'");
            redirect("modcp.php?action=residences");

        }

        //Wohnung editieren

        if(isset($_POST['editplace'])) {
            $place_id = (int) $mybb->get_input('place_id');
            $country = $db->escape_string($mybb->get_input('country'));
            $place = $db->escape_string($mybb->get_input('place'));


            $db->query("UPDATE ".TABLE_PREFIX."places SET country ='".$country."', place = '".$place."' WHERE place_id = '".$place_id."'");
            redirect("modcp.php?action=residences");
        }


        $delete_place = $mybb->input['delete_place'];

        if($delete_place){
            $db->delete_query("places", "place_id = '$delete_place'");
            redirect("modcp.php?action=residences");
        }


        eval("\$page = \"".$templates->get("residences_modcp")."\";");
        output_page($page);
    }
}

$plugins->add_hook('global_intermediate', 'global_residence_alert');

function global_residence_alert(){
    global $db, $mybb, $residence_alert_place, $residence_alert_home, $lang;
    //Die Sprachdatei
    $lang->load('residences');

    $select = $db->query("SELECT *
        FROM ".TABLE_PREFIX."places
        where accepted = 0
        ");

    $count = mysqli_num_rows ($select);

    if($count > 0){
        if($mybb->usergroup['canmodcp'] == 1){
            $residence_alert_place = "<div class=\"red_alert\"><a href='modcp.php?action=residences'>Aktuell sind {$count} offene Orte vorhanden. </a>
</div>";
        }
    }


    $select2 = $db->query("SELECT *
        FROM ".TABLE_PREFIX."residence
        where accepted = 0
        ");

    $count = mysqli_num_rows ($select2);

    if($count > 0){
        if($mybb->usergroup['canmodcp'] == 1){
            $residence_alert_home = "<div class=\"red_alert\"><a href='modcp.php?action=residences'>Aktuell sind {$count} offene Wohnorte vorhanden. </a>
</div>";
        }
    }

}


//wer ist wo
$plugins->add_hook('fetch_wol_activity_end', 'residences_user_activity');
$plugins->add_hook('build_friendly_wol_location_end', 'residences_location_activity');

function residences_user_activity($user_activity){
    global $user;

    if(my_strpos($user['location'], "misc.php?action=residences") !== false) {
        $user_activity['activity'] = "residences";
    }

    return $user_activity;
}

function residences_location_activity($plugin_array) {
    global $db, $mybb, $lang;

    if($plugin_array['user_activity']['activity'] == "residences")
    {
        $plugin_array['location_name'] = "<b><a href='misc.php?action=residences'>Wer wohnt wo?</a></b>";
    }


    return $plugin_array;
}
