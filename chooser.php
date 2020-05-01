<?php
///////////
//
// PHP DYNAMIC DROP-DOWN BOX FOR ZABBIX PDF GENERATION
// THE IDEA BEHIND THIS IS TO CREATE A VERSION INDEPENDENT
// ADDON THAT CAN BE ADDED THROUGH SCREENS TO PREVENT BREAKAGE
//
///////////
//
// v0.1 - 1/14/12 - (c) Travis Mathis - travisdmathis@gmail.com
// Change Log: Added Form Selection, Data Gathering, Report Generation w/ basic time period selection
// pdfform.php(selection) / generatereport.php(report building) / pdf.php(report)i
// v0.2 - 2/7/12 
// Change Log: Removed mysql specific calls and replaced with API calls.  Moved config to central file
// v0.5 - 2014/09/05 - Ronny Pettersen <pettersen.ronny @ gmail.com>
//	Rewritten a lot based on original from Travis Mathis. Allows reporting on group.
//      Uses Jquery (javascript) for many of the functions on the index page.
//
///////////

include("config.inc.php");

if ( $user_login == 1 ) {
    session_start();
    $z_user=$_SESSION['username'];
    $z_pass=$_SESSION['password'];

    if ( $z_user == "" ) {
        header("Location: index.php");
    }

    $z_login_data	= "name=" .$z_user ."&password=" .$z_pass ."&autologin=1&enter=Sign+in";
}

global $z_user, $z_pass, $z_login_data;

require_once("inc/ZabbixAPI.class.php");
include("inc/index.functions.php");

header( 'Content-type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
    <title><?php echo _("Zabbix PDF report"); ?></title>
    <meta charset="utf-8" />
    <link rel="shortcut icon" href="images/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/zabbix.default.css" />
    <link rel="stylesheet" type="text/css" href="css/zabbix.color.css" />
    <link rel="stylesheet" type="text/css" href="css/zabbix.report.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery.datetimepicker.css"/ >
    <link rel="stylesheet" type="text/css" href="css/tablesorter.css"/ >
    <link rel="stylesheet" type="text/css" href="css/select2.css"/ >
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/jquery.datetimepicker.min.js"></script>
    <script type="text/javascript" src="js/jquery.validate.min.js"></script>
    <script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>
    <script type="text/javascript" src="js/select2.min.js"></script>
    <script>
        $(function(){
            $('#ReportHost').click(function(){
                $('#s_ReportHost').prop('disabled',false);
                $('#s_ReportHostGroup').prop('disabled',true);
                $('#s_ReportHost').prop('required',true);
                $('#s_ReportHostGroup').prop('required',false);
                $('#p_ReportHostGroup').hide('fast');
                $('#p_ReportHost').show('slow');
            });
            $('#ReportHostGroup').click(function(){
                $('#s_ReportHostGroup').prop('disabled',false);
                $('#s_ReportHost').prop('disabled',true);
                $('#s_ReportHostGroup').prop('required',true);
                $('#s_ReportHost').prop('required',false);
                $('#p_ReportHost').hide('fast');
                $('#p_ReportHostGroup').show('slow');
            });
            $('#RangeLast').click(function(){
                $('#s_RangeLast').prop('disabled',false);
                $('#datepicker_start').prop('disabled',true);
                $('#timepicker_start').prop('disabled',true);
                $('#datepicker_end').prop('disabled',true);
                $('#timepicker_end').prop('disabled',true);
                $('#datepicker_start').prop('required',false);
                $('#p_RangeCustom').hide('fast');
                $('#p_RangeLast').show('slow');
            });
            $('#RangeCustom').click(function(){
                $('#datepicker_start').prop('disabled',false);
                $('#timepicker_start').prop('disabled',false);
                $('#datepicker_end').prop('disabled',false);
                $('#timepicker_end').prop('disabled',false);
                $('#datepicker_start').prop('required',true);
                $('#s_RangeLast').prop('disabled',true);
                $('#s_RangeLast').prop('required',false);
                $('#p_RangeCustom').show('slow');
                $('#p_RangeLast').hide('fast');
            });
            $('#h_OldReports').click(function(){
                $(".d_OldReports").toggleClass('table-hidden');
            });
        });

        $(document).ready(function() {
            $('#s_ReportHostGroup').prop('disabled',true);
            $('#datepicker_start').prop('disabled',true);
            $('#timepicker_start').prop('disabled',true);
            $('#datepicker_end').prop('disabled',true);
            $('#timepicker_end').prop('disabled',true);
            $('#p_ReportHostGroup').hide('fast');
            $('#p_RangeCustom').hide('fast');
            $('#OldReports').tablesorter();
            $("#s_ReportHost").select2({width: "copy"});
            $("#s_ReportHostGroup").select2({width: "copy"});
            $("#s_RangeLast").select2();
        });
    </script>
</head>
<body class="originalblue">
<div id="message-global-wrap"><div id="message-global"></div></div>
<table class="maxwidth page_header" cellspacing="0" cellpadding="5"><tr><td class="page_header_l"><a class="image" href="http://www.zabbix.com/" target="_blank"><div class="zabbix_logo">&nbsp;</div></a></td><td class="maxwidth page_header_r">&nbsp;</td></tr></table>
<br/><br/>
<div style="text-align: center;"><h1><?php echo _("Generate PDF Report"); ?></h1></div>
<br/>
<?php
// ERROR REPORTING
//error_reporting(E_ALL);
set_time_limit(60);

// ZabbixAPI Connection
//ZabbixAPI::debugEnabled(TRUE);
ZabbixAPI::login($z_server,$z_user,$z_pass)
or die('Unable to login: '.print_r(ZabbixAPI::getLastError(),true));
//fetch graph data host
$hosts       = ZabbixAPI::fetch_array('host','get',array('output'=>array('hostid','name'),'sortfield'=>'host','with_graphs'=>'1','sortfield'=>'name'))
or die('Unable to get hosts: '.print_r(ZabbixAPI::getLastError(),true));
$host_groups = ZabbixAPI::fetch_array('hostgroup','get', array('output'=>array('groupid','name'),'real_hosts'=>'1','with_graphs'=>'1','sortfield'=>'name') )
or die('Unable to get hosts: '.print_r(ZabbixAPI::getLastError(),true));
ZabbixAPI::logout($z_server,$z_user,$z_pass)
or die('Unable to logout: '.print_r(ZabbixAPI::getLastError(),true));

// Form dropdown boxes from Zabbix API Data
?>
<div style="text-align: center;">
    <form class="cmxform" id="ReportForm" name="ReportForm" action='createpdf.php' method='GET'>
        <table border="1" rules="NONE" frame="BOX" width="600" cellpadding="10" align="center">
            <tr>
                <td valign="middle" align="left" width="115">
                    <label for="ReportType"><b><?php echo _("Report type"); ?></b></label>
                </td>
                <td valign="center" align="left" height="30">
                    <p>
                        <input id="ReportHost" type="radio" name="ReportType" value="host" title="<?php echo _("Generate report on HOST"); ?>" checked="checked" /><?php echo _("Host"); ?>
                        <input id="ReportHostGroup" type="radio" name="ReportType" value="hostgroup" title="<?php echo _("Generate report on GROUP"); ?>" /><?php echo _("Host Group"); ?>
                    </p>
                </td>
                <td align="center" valign="top" width="110">
                    <div style="text-align: center;"><?php echo $z_user; ?> <a href="logout.php"><?php echo _("Logout"); ?></a></div>
                </td>
            </tr>
            <tr>
                <td valign="middle" align="left">&nbsp;</td>
                <td valign="center" align="left" width="70%" height="30">
                    <p id="p_ReportHost">
                        <label for="s_ReportHost" class="error"><?php echo _("Please select your host"); ?></label>
                        &nbsp;<select id="s_ReportHost" name="HostID" width="350"  style="width: 350px" title="<?php echo _("Please select host"); ?>" required>
                            <option value="">--&nbsp;<?php echo _("Select host"); ?>&nbsp;--</option>
                            <?php ReadArray($hosts); ?>
                        </select>
                    </p>
                    <p id="p_ReportHostGroup">
                        &nbsp;<select id="s_ReportHostGroup" name="GroupID" width="350" style="width: 350px" title="<?php echo _("Please select hostgroup"); ?>" >
                            <option value="">--&nbsp;<?php echo _("Select hostgroup"); ?>&nbsp;--</option>
                            <?php ReadArray($host_groups); ?>
                        </select>
                    </p>
                    <p>
                        <input type="checkbox" name="GraphsOn" value="yes" checked> <?php echo _("Include graphs"); ?><br/>
                        <input type="checkbox" name="ItemGraphsOn" value="yes"> <?php echo _("Include graphed items"); ?><br/>
                        <input type="checkbox" name="TriggersOn" value="yes"> <?php echo _("Show triggers"); ?><br/>
                        <input type="checkbox" name="ItemsOn" value="yes"> <?php echo _("Show configured items status"); ?><br/>
                        <input type="checkbox" name="TrendsOn" value="yes"> <?php echo _("Show configured trends (SLA-ish)"); ?>
                    </p>
                    <p>
                        <input type="text" name="mygraphs2" style="font-size: 10px;"  size=80 value="<?php echo $mygraphs; ?>"> &uarr; <?php echo _("Graphs to show (#.*# = all):"); ?>
                        <input type="text" name="myitems2" style="font-size: 10px;"  size=80 value="<?php echo $myitemgraphs; ?>"> &uarr; <?php echo _("Items to graph (#.*# = all):"); ?>
                    </p>
                </td>
                <td valign="middle">&nbsp;</td>
            </tr>
            <tr>
                <td valign="middle" align="left">
                    <label for="ReportRange"><b><?php echo _("Report range"); ?></b></label>
                </td>
                <td valign="middle" align="left">
                    <p>
                        <input id="RangeLast" type="radio" name="ReportRange" value="last" title="<?php echo _("Report on last activity"); ?>" checked="checked" /><?php echo _("Last"); ?>
                        <input id="RangeCustom" type="radio" name="ReportRange" value="custom" title="<?php echo _("Report using custom report range"); ?>" /><?php echo _("Custom"); ?>
                    </p>
                </td>
                <td valign="middle">&nbsp;</td>
            </tr>
            <tr>
                <td valign="middle" align="left" height="50">&nbsp;</td>
                <td valign="middle" align="left" height="50">
                    <p id=p_RangeLast>
                        &nbsp;<select id="s_RangeLast" name="timePeriod" title="<?php echo _("Please select range"); ?>" required>
                            <option value="Hour"><?php echo _("Hour"); ?></option>
                            <option value="Day"><?php echo _("Day"); ?></option>
                            <option value="Week"><?php echo _("Week"); ?></option>
                            <option value="Month"><?php echo _("Month"); ?></option>
                            <option value="Year"><?php echo _("Year"); ?></option>
                        </select>
                    </p>
                    <p id="p_RangeCustom">
                        &nbsp;<b><?php echo _("Start:"); ?></b>
                        <input name="startdate" id="datepicker_start" type="date" size="8" />&nbsp;
                        <input name="starttime" id="timepicker_start" type="time" size="5" /><br/>
                        &nbsp;<b><?php echo _("End:"); ?></b>&nbsp;&nbsp;&nbsp;
                        <input name="enddate" id="datepicker_end" type="date" size="8" />&nbsp;
                        <input name="endtime" id="timepicker_end" type="time" size="5" />
                    </p>
                </td>
                <td valign="bottom" align="middle">
                    <input type='submit' value='<?php echo _("Generate"); ?>'>
                    <span class="smalltext"><input type='checkbox' name='debug'><?php echo _("Debug"); ?></span>
                    <p><div style="text-align: center;"><?php echo _("Version:")." ".$version; ?></div></p>
                </td>
            </tr>
        </table>
    </form>
    <br/>
    <h2 id="h_OldReports"><?php echo _("Old reports"); ?><br>(<?php echo _("click to show"); ?>)</h2>
</div>

<div class="d_OldReports table-hidden">
    <table id="OldReports" cellpadding="0" class="tablesorter">
        <?php ListOldReports($pdf_report_dir); ?>
    </table>
</div>

</body>
<script>
    jQuery(function(){
        jQuery('#datepicker_start').datetimepicker({
            dayOfWeekStart: 1,
            weeks: true,
            format:'Y/m/d',
            minDate:'-1971/01/01',// One year ago
            maxDate:'+1970/01/01',// Today is maximum date calendar
            onShow:function( ct ){
                this.setOptions({
                    maxDate:jQuery('#datepicker_end').val()?jQuery('#datepicker_end').val():false
                })
            },
            timepicker: false
        });
        jQuery('#datepicker_end').datetimepicker({
            dayOfWeekStart : 1,
            weeks: true,
            format:'Y/m/d',
            minDate:'-1971/01/01',// One year ago
            maxDate:'+1970/01/01',// Today is maximum date calendar
            onShow:function( ct ){
                this.setOptions({
                    minDate:jQuery('#datepicker_start').val()?jQuery('#datepicker_start').val():false
                })
            },
            timepicker: false
        });
    });

    jQuery('#timepicker_start').datetimepicker({ datepicker:false, format:'H:i' });
    jQuery('#timepicker_end').datetimepicker({ datepicker:false, format:'H:i' });
</script>
</html>