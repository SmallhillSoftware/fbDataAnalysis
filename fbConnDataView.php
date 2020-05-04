<html>
<head>
<title>Statistikdatenauswertung der FritzBox</title>
<meta name="description" content="Statistikdatenauswertung der FritzBox">
<meta name="author" content="Stefan Kleineberg" >
</head>
<body bgcolor="#000000" text="#ffff00" link="#cccc00" vlink="#99cc00" alink="#993300">
 <!-- <body bgcolor="#ffffff" text="#000000" link="#0D14DE" vlink="#FF3C00" alink="#FF3C00"> Debugging -->
<table>
<tr>
	<td><img src="fbBoxIns.jpg" width="350" height="263" alt="FritzBox-Installation"></td>
	<td><h1>Statistikdatenauswertung der FritzBox</h1></td>
</tr>
</table>
<table>
<tr>
<?php
	include "dbCreds.inc.php";
	include "chart.inc.php";

	define('cValDate',               0);
	define('cValBytesSent',          1);
	define('cValBytesReceived',      2);
	define('cValUpstreamBitRate',    3);
	define('cValDownstreamBitRate',  4);
	define('cValWeekday',            5);
	define('cXAxisCategories',       6);
	define('cValUptime',             7);
	define('cMinsPerHour',           60);
	define('cSecsPerHour',           3600);
	define('trendHalf',              10);
	define('trendFull',              30);
	define('cScaleFactor2Mega',      (1/1048576));
	//define('cScaleFactor2Mega',      (1/1000000));
	define('uiMaxValue',             4294967295);
	
	function checkTime ($chkHour, $chkMin, $chkSecs)
	{
		if(($chkHour >= 0) and ($chkHour <= 23))
		{
			$chk = 1;
		}
		else
		{
			$chk = 0;
		}
		if(($chkMin >= 0) and ($chkMin <= 59))
		{
			$chk += 1;
		}
		else
		{
			$chk += 0;
		}
		if(($chkSecs >= 0) and ($chkSecs <= 59))
		{
			$chk += 1;
		}
		else
		{
			$chk += 0;
		}
		if ($chk == 3)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}


	/* php and gd version check */
	$phpVersionRequired = "7.0.33";
 	$gdVersionRequired = "2.1.1";
 	$versionsCorrect = 0;
 	$currPhpVersion = phpversion();
  	if (strpos($currPhpVersion, $phpVersionRequired) !== false)
 	{
		if (extension_loaded("gd"))
		{
			$gd = gd_info();
			if ( (strpos($gd["GD Version"], $gdVersionRequired) !== false) and $gd["JPEG Support"] and $gd["PNG Support"] )
			{ 
          	$versionsCorrect = 1;
			}
			else
			{
				$versionsCorrect = 0;
			}
		}
	}
	else
	{
		$versionsCorrect = 0;	
	}
	if ($versionsCorrect == 0)
	{
	/* ##########################  HTML-output, wrong versions - start #################################### */
		echo "<tr>";
		echo "<td>PHP-Version required: </td>";
		echo "<td>".$phpVersionRequired."</td>";
		echo "<td>but PHP-version installed: </td>";
		echo "<td>".$currPhpVersion."</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>GD-Version required: </td>";
		echo "<td>".$gdVersionRequired."</td>";
		echo "<td>but GD-version installed: </td>";
		echo "<td>".$gd["GD Version"]."</td>";
		echo "</tr>";
	/* ##########################  HTML-output, wrong versions - end ###################################### */
	}
	else
	{
		/* check if a start date/time is inside the url like fbConnDataView.php?sDate=20200112&sTime=220900 */
		$sDate = "";
		$sTime = "";
		if (isset($_GET['sDate']))
		{
			$sDate = $_GET['sDate'];
			$curYear = intval(substr($sDate,0,4));
			$curMnth = intval(substr($sDate,4,2));
			$cur_Day = intval(substr($sDate,6,2));
		}
		else
		{
			$curYear = 0;
			$curMnth = 0;
			$cur_Day = 0;
		}
		if (isset($_GET['sTime']))
		{
			$sTime = $_GET['sTime'];
			$curHour = intval(substr($sTime,0,2));
			$cur_Min = intval(substr($sTime,2,2));
			$cur_Sec = intval(substr($sTime,4,2));
		}
		else
		{
			$curHour = 0;
			$cur_Min = 0;
			$cur_Sec = 0;
		}
		if (checkdate($curMnth, $cur_Day, $curYear) and checkTime($curHour, $cur_Min, $cur_Sec))
		{
			$curDateTime = mktime($curHour, $cur_Min,0,$curMnth,$cur_Day,$curYear);
			$curWeek = date('W', $curDateTime);		
		}
		else		
		{
			$curWeek = date('W');
			$curYear = date('Y');
			$curMnth = date('m');
			$cur_Day = date('d');
			$curHour = date('H');
			$cur_Min = date('i');
			$curDateTime = mktime($curHour, $cur_Min,0,$curMnth,$cur_Day,$curYear);
		}
		/* Stepping back for the last 24 hours*/	
		$curDateTime24hoursBack = strtotime("-1 day", $curDateTime);
		$curWeek24hb = date('W',$curDateTime24hoursBack);
		$curYear24hb = date('Y',$curDateTime24hoursBack);
		$curMnth24hb = date('m',$curDateTime24hoursBack);
		$cur_Day24hb = date('d',$curDateTime24hoursBack);
		$curHour24hb = date('H',$curDateTime24hoursBack);
		$cur_Min24hb = date('i',$curDateTime24hoursBack);	
		
		/* Start date for last week, last month, last year shall be the last day 23:59 */
		$curWeekStart1wb_1yb = $curWeek24hb;
		$curYearStart1wb_1yb = $curYear24hb;
		$curMnthStart1wb_1yb = $curMnth24hb;
		$cur_DayStart1wb_1yb = $cur_Day24hb;
		$curHourStart1wb_1yb = 23;
		$cur_MinStart1wb_1yb = 59;
		$curDateTimeStart1wb_1yb = mktime($curHourStart1wb_1yb, $cur_MinStart1wb_1yb, 0, $curMnthStart1wb_1yb, $cur_DayStart1wb_1yb, $curYearStart1wb_1yb);
		
		/* Stepping back for the last week (7 days) */	
		$curDateTime1weekBack = strtotime("-1 week", $curDateTimeStart1wb_1yb);
		$curWeek1wb = date('W',$curDateTime1weekBack);
		$curYear1wb = date('Y',$curDateTime1weekBack);
		$curMnth1wb = date('m',$curDateTime1weekBack);
		$cur_Day1wb = date('d',$curDateTime1weekBack);
		$curHour1wb = date('H',$curDateTime1weekBack);
		$cur_Min1wb = date('i',$curDateTime1weekBack);
		
		/* Stepping back for 1 year */
		$curDateTime1yearBack = strtotime("-1 year", $curDateTimeStart1wb_1yb);
		$curWeek1yb = date('W',$curDateTime1yearBack);
		$curYear1yb = date('Y',$curDateTime1yearBack);
		$curMnth1yb = date('m',$curDateTime1yearBack);
		$cur_Day1yb = date('d',$curDateTime1yearBack);
		$curHour1yb = date('H',$curDateTime1yearBack);
		$cur_Min1yb = date('i',$curDateTime1yearBack);	
		
		/* Building date statrings which are comptaible to the MYSQL-date-type YYYY:MM:DD HH:mm:ss */	
		$curDateTimeMySQL = $curYear."-".$curMnth."-".$cur_Day." ".$curHour.":".$cur_Min.":00";
		$curDateTimeMySQLStart1wb_1yb = $curYearStart1wb_1yb."-".$curMnthStart1wb_1yb."-".$cur_DayStart1wb_1yb." ".$curHourStart1wb_1yb.":".$cur_MinStart1wb_1yb.":00";
		$curDateTimeMySQL24hb = $curYear24hb."-".$curMnth24hb."-".$cur_Day24hb." ".$curHour24hb.":".$cur_Min24hb.":00";
		$curDateTimeMySQL1wb = $curYear1wb."-".$curMnth1wb."-".$cur_Day1wb." ".$curHour1wb.":".$cur_Min1wb.":00";		
		$curDateTimeMySQL1yb = $curYear1yb."-".$curMnth1yb."-".$cur_Day1yb." ".$curHour1yb.":".$cur_Min1yb.":00";
		
		/* connect to the MYSQL-DB*/ 
		$link = mysqli_connect("localhost", $dbLogName, $dbPwd, "fb_conn_data");
		
	/* ##########################  calculation, evaluation 24 hours back - start ########################## */	
		
		/* build the MYSQL-DB-query to list all values 24hour backwards from now */ 
		$sql = "SELECT `valDate`, `valBytesSent`, `valBytesReceived`, `valUpstreamBitRate`, `valDownstreamBitRate`, `valUptime`  FROM `dataVals` WHERE (`valDate` > '".$curDateTimeMySQL24hb."' AND `valDate` < '".$curDateTimeMySQL."') ORDER BY `valDate` DESC";
		/* execute the MYSQL-DB-query */
	   $res = mysqli_query($link, $sql);
	   $numOfRes = mysqli_num_rows($res);
	   $numOfFetchedRes = 0;
	   /* copy all MYSQL-DB-contents for 24hours back from now to 2dimensional-array */
	   while ($sqlRecord = mysqli_fetch_assoc($res))
		{
			$dbRes24hBack[$numOfFetchedRes][cValDate]               = $sqlRecord["valDate"];
			$dbRes24hBack[$numOfFetchedRes][cValBytesSent]          = $sqlRecord["valBytesSent"];
			$dbRes24hBack[$numOfFetchedRes][cValBytesReceived]      = $sqlRecord["valBytesReceived"];
			$dbRes24hBack[$numOfFetchedRes][cValUpstreamBitRate]    = $sqlRecord["valUpstreamBitRate"];
			$dbRes24hBack[$numOfFetchedRes][cValDownstreamBitRate]  = $sqlRecord["valDownstreamBitRate"];
			$dbRes24hBack[$numOfFetchedRes][cValUptime]             = $sqlRecord["valUptime"];			
			$numOfFetchedRes++;	
		}
		/* create differential values for bytes sent and bytes received values */
		for($i=0; $i<($numOfFetchedRes-1); $i++)
		{
			/* calculate the time difference as hours with minutes and seconds as decimal places */
			$sql = "SELECT TIMEDIFF('".$dbRes24hBack[$i][cValDate]."','".$dbRes24hBack[$i+1][cValDate]."')";
			/* execute the MYSQL-DB-query, contains the time difference as HH:mm:ss */
	   	$res = mysqli_query($link, $sql);
	   	if ($sqlRecord = mysqli_fetch_array($res))
	   	{
	   		$diffTime = explode(":", $sqlRecord[0]);
	   		$diffTimeInHours  = $diffTime[0] + ($diffTime[1]/cMinsPerHour) + ($diffTime[2]/cSecsPerHour);
	   		$diffValUptime    = ($dbRes24hBack[$i][cValUptime]        - $dbRes24hBack[$i+1][cValUptime]);
	   		$diffValBytesSent = ($dbRes24hBack[$i][cValBytesSent]     - $dbRes24hBack[$i+1][cValBytesSent])/$diffTimeInHours;
	   		$diffValBytesRcvd = ($dbRes24hBack[$i][cValBytesReceived] - $dbRes24hBack[$i+1][cValBytesReceived])/$diffTimeInHours;
	   	}
	   	else
	   	{
	   		$diffTimeInHours = 0;
	   	}
			/* There is no internet reconnection of the Fritz!Box, therefore the later uptimer is bigger than the previous one */			
			if ($diffValUptime > 0)
			{			
				/* the total bytes sent counter overrun at 4.294.967.295 */				
				if ($diffValBytesSent < 0)
				{
					$dbRes24hBack[$i][cValBytesSent] = ($dbRes24hBack[$i][cValBytesSent] + (uiMaxValue - $dbRes24hBack[$i+1][cValBytesSent]))/$diffTimeInHours;
				}
				else
				{
					$dbRes24hBack[$i][cValBytesSent] = $diffValBytesSent;
				}
				/* the total bytes received counter overrun at 4.294.967.295 */				
				if ($diffValBytesRcvd < 0)
				{
					$dbRes24hBack[$i][cValBytesReceived] = ($dbRes24hBack[$i][cValBytesReceived] + (uiMaxValue - $dbRes24hBack[$i+1][cValBytesReceived]))/$diffTimeInHours;
				}
				else
				{
					$dbRes24hBack[$i][cValBytesReceived] = $diffValBytesRcvd;
				}
			}
			else
			{
				/* No difference value at reconnection could be calculated */				
				$dbRes24hBack[$i][cValBytesSent]     = 0;
				$dbRes24hBack[$i][cValBytesReceived] = 0;
			}
			$dbRes24hBack[$i][cValWeekday] = 0;		
			$dbRes24hBack[$i][cXAxisCategories] = substr($dbRes24hBack[$i][cValDate], 10, 6);
		}
		/* remove last line because it is not diff'ed*/	
		array_pop($dbRes24hBack);	
		/* statistical evaluation */
		$avgValBytesSent24hb = 0;
		$avgValBytesReceived24hb = 0;
		$avgValUpstreamBitRate24hb = 0;
		$avgValDownstreamBitRate24hb = 0;	
		for($i=0; $i<count($dbRes24hBack); $i++)
		{
			/* bytes sent */		
			$avgValBytesSent24hb += $dbRes24hBack[$i][cValBytesSent];
			/* bytes received */
			$avgValBytesReceived24hb += $dbRes24hBack[$i][cValBytesReceived];
			/* upstream bit rate */
			$avgValUpstreamBitRate24hb += $dbRes24hBack[$i][cValUpstreamBitRate];
			/* downstream bit rate */
			$avgValDownstreamBitRate24hb += $dbRes24hBack[$i][cValDownstreamBitRate]; 		
			if ($i == 0)
			{
				/* bytes sent */
				$maxValBytesSent24hb = $dbRes24hBack[$i][cValBytesSent];
				$maxValBytesSent24hbPos = $i;
				$minValBytesSent24hb = $dbRes24hBack[$i][cValBytesSent];
				$minValBytesSent24hbPos = $i;
				/* bytes received */
				$maxValBytesReceived24hb = $dbRes24hBack[$i][cValBytesReceived];
				$maxValBytesReceived24hbPos = $i;
				$minValBytesReceived24hb = $dbRes24hBack[$i][cValBytesReceived];
				$minValBytesReceived24hbPos = $i;
				/* upstream bit rate */
				$maxValUpstreamBitRate24hb = $dbRes24hBack[$i][cValUpstreamBitRate];
				$maxValUpstreamBitRate24hbPos = $i;
				$minValUpstreamBitRate24hb = $dbRes24hBack[$i][cValUpstreamBitRate];
				$minValUpstreamBitRate24hbPos = $i;
				/* downstream bit rate */
				$maxValDownstreamBitRate24hb = $dbRes24hBack[$i][cValDownstreamBitRate];
				$maxValDownstreamBitRate24hbPos = $i;
				$minValDownstreamBitRate24hb = $dbRes24hBack[$i][cValDownstreamBitRate];
				$minValDownstreamBitRate24hbPos = $i;
			}
			else
			{
				/* bytes sent */			
				if($maxValBytesSent24hb < $dbRes24hBack[$i][cValBytesSent])
				{
					$maxValBytesSent24hb = $dbRes24hBack[$i][cValBytesSent];
					$maxValBytesSent24hbPos = $i;
				}
				if( ($minValBytesSent24hb > $dbRes24hBack[$i][cValBytesSent]) AND ($dbRes24hBack[$i][cValBytesSent] > 0) )
				{
					$minValBytesSent24hb = $dbRes24hBack[$i][cValBytesSent];
					$minValBytesSent24hbPos = $i;
				}
				/* bytes received */
				if($maxValBytesReceived24hb < $dbRes24hBack[$i][cValBytesReceived])
				{
					$maxValBytesReceived24hb = $dbRes24hBack[$i][cValBytesReceived];
					$maxValBytesReceived24hbPos = $i;
				}
				if( ($minValBytesReceived24hb > $dbRes24hBack[$i][cValBytesReceived]) AND ($dbRes24hBack[$i][cValBytesReceived] > 0) )
				{
					$minValBytesReceived24hb = $dbRes24hBack[$i][cValBytesReceived];
					$minValBytesReceived24hbPos = $i;
				}
				/* upstream bit rate */
				if($maxValUpstreamBitRate24hb < $dbRes24hBack[$i][cValUpstreamBitRate])
				{
					$maxValUpstreamBitRate24hb = $dbRes24hBack[$i][cValUpstreamBitRate];
					$maxValUpstreamBitRate24hbPos = $i;
				}
				if( ($minValUpstreamBitRate24hb > $dbRes24hBack[$i][cValUpstreamBitRate]) AND ($dbRes24hBack[$i][cValUpstreamBitRate] > 0) ) 
				{
					$minValUpstreamBitRate24hb = $dbRes24hBack[$i][cValUpstreamBitRate];
					$minValUpstreamBitRate24hbPos = $i;
				}
				/* downstream bit rate */
				if($maxValDownstreamBitRate24hb < $dbRes24hBack[$i][cValDownstreamBitRate])
				{
					$maxValDownstreamBitRate24hb = $dbRes24hBack[$i][cValDownstreamBitRate];
					$maxValDownstreamBitRate24hbPos = $i;
				}
				if( ($minValDownstreamBitRate24hb > $dbRes24hBack[$i][cValDownstreamBitRate]) AND ($dbRes24hBack[$i][cValDownstreamBitRate] > 0) )
				{
					$minValDownstreamBitRate24hb = $dbRes24hBack[$i][cValDownstreamBitRate];
					$minValDownstreamBitRate24hbPos = $i;
				}
			}
		}
		/* bytes sent */		
		$avgValBytesSent24hb = $avgValBytesSent24hb/($numOfFetchedRes-1);
		/* bytes received */
		$avgValBytesReceived24hb = $avgValBytesReceived24hb/($numOfFetchedRes-1);
		/* upstream bit rate */
		$avgValUpstreamBitRate24hb = $avgValUpstreamBitRate24hb/($numOfFetchedRes-1);
		/* downstream bit rate */
		$avgValDownstreamBitRate24hb = $avgValDownstreamBitRate24hb/($numOfFetchedRes-1);
		
		/* trend detection, last value against 24hour average */	
		/* bytes sent */
		$lastValBytesSentToAvg24hoursPercent = ($dbRes24hBack[0][cValBytesSent]*100)/$avgValBytesSent24hb;	
		if($lastValBytesSentToAvg24hoursPercent > 100)
		{
			/* up direction */
			if ($lastValBytesSentToAvg24hoursPercent > (100+trendFull))
			{
				$lastValBytesSentTrend = "arrow_up.gif";
			}
			elseif ($lastValBytesSentToAvg24hoursPercent > (100+trendHalf))
			{
				$lastValBytesSentTrend = "arrow_halfup.gif";
			}
			else
			{
				$lastValBytesSentTrend = "arrow_plane.gif";
			}
		}
		else
		{
			/* down direction*/
			if ($lastValBytesSentToAvg24hoursPercent < (100-trendFull))
			{
				$lastValBytesSentTrend = "arrow_down.gif";
			}
			elseif ($lastValBytesSentToAvg24hoursPercent < (100-trendHalf))
			{
				$lastValBytesSentTrend = "arrow_halfdown.gif";
			}
			else
			{
				$lastValBytesSentTrend = "arrow_plane.gif";
			}
		}
		/* bytes received */
		$lastValBytesReceivedToAvg24hoursPercent = ($dbRes24hBack[0][cValBytesReceived]*100)/$avgValBytesReceived24hb;	
		if($lastValBytesReceivedToAvg24hoursPercent > 100)
		{
			/* up direction */
			if ($lastValBytesReceivedToAvg24hoursPercent > (100+trendFull))
			{
				$lastValBytesReceivedTrend = "arrow_up.gif";
			}
			elseif ($lastValBytesReceivedToAvg24hoursPercent > (100+trendHalf))
			{
				$lastValBytesReceivedTrend = "arrow_halfup.gif";
			}
			else
			{
				$lastValBytesReceivedTrend = "arrow_plane.gif";
			}
		}
		else
		{
			/* down direction*/
			if ($lastValBytesReceivedToAvg24hoursPercent < (100-trendFull))
			{
				$lastValBytesReceivedTrend = "arrow_down.gif";
			}
			elseif ($lastValBytesReceivedToAvg24hoursPercent < (100-trendHalf))
			{
				$lastValBytesReceivedTrend = "arrow_halfdown.gif";
			}
			else
			{
				$lastValBytesReceivedTrend = "arrow_plane.gif";
			}
		}
	
		/* upstream bit rate */
		$lastValUpstreamBitRateToAvg24hoursPercent = ($dbRes24hBack[0][cValUpstreamBitRate]*100)/$avgValUpstreamBitRate24hb;	
		if($lastValUpstreamBitRateToAvg24hoursPercent > 100)
		{
			/* up direction */
			if ($lastValUpstreamBitRateToAvg24hoursPercent > (100+trendFull))
			{
				$lastValUpstreamBitRateTrend = "arrow_up.gif";
			}
			elseif ($lastValUpstreamBitRateToAvg24hoursPercent > (100+trendHalf))
			{
				$lastValUpstreamBitRateTrend = "arrow_halfup.gif";
			}
			else
			{
				$lastValUpstreamBitRateTrend = "arrow_plane.gif";
			}
		}
		else
		{
			/* down direction*/
			if ($lastValUpstreamBitRateToAvg24hoursPercent < (100-trendFull))
			{
				$lastValUpstreamBitRateTrend = "arrow_down.gif";
			}
			elseif ($lastValUpstreamBitRateToAvg24hoursPercent < (100-trendHalf))
			{
				$lastValUpstreamBitRateTrend = "arrow_halfdown.gif";
			}
			else
			{
				$lastValUpstreamBitRateTrend = "arrow_plane.gif";
			}
		}
	
		/* downstream bit rate */
		$lastValDownstreamBitRateToAvg24hoursPercent = ($dbRes24hBack[0][cValDownstreamBitRate]*100)/$avgValDownstreamBitRate24hb;	
		if($lastValDownstreamBitRateToAvg24hoursPercent > 100)
		{
			/* up direction */
			if ($lastValDownstreamBitRateToAvg24hoursPercent > (100+trendFull))
			{
				$lastValDownstreamBitRateTrend = "arrow_up.gif";
			}
			elseif ($lastValDownstreamBitRateToAvg24hoursPercent > (100+trendHalf))
			{
				$lastValDownstreamBitRateTrend = "arrow_halfup.gif";
			}
			else
			{
				$lastValDownstreamBitRateTrend = "arrow_plane.gif";
			}
		}
		else
		{
			/* down direction*/
			if ($lastValDownstreamBitRateToAvg24hoursPercent < (100-trendFull))
			{
				$lastValDownstreamBitRateTrend = "arrow_down.gif";
			}
			elseif ($lastValDownstreamBitRateToAvg24hoursPercent < (100-trendHalf))
			{
				$lastValDownstreamBitRateTrend = "arrow_halfdown.gif";
			}
			else
			{
				$lastValDownstreamBitRateTrend = "arrow_plane.gif";
			}
		}
	/* ##########################  calculation, evaluation 24 hours back - end ############################ */
	
	/* ##########################  calculation, evaluation 1 week back - start ############################ */	
		
		$tmpDateTime = $curDateTimeStart1wb_1yb;
		$tmpWeek     = date('W',$tmpDateTime);
		$tmpYear     = date('Y',$tmpDateTime);
		$tmpMnth     = date('m',$tmpDateTime);
		$tmp_Day     = date('d',$tmpDateTime);
		$tmpHour     = date('H',$tmpDateTime);
		$tmp_Min     = date('i',$tmpDateTime);
		$tmpDateTimeMySQL = $tmpYear."-".$tmpMnth."-".$tmp_Day." ".$tmpHour.":".$tmp_Min.":00";	
		/* Stepping back for 1 day */	
		$tmpDateTime1dBack = strtotime("-1 day", $curDateTimeStart1wb_1yb);
		$tmpWeek1db = date('W',$tmpDateTime1dBack);
		$tmpYear1db = date('Y',$tmpDateTime1dBack);
		$tmpMnth1db = date('m',$tmpDateTime1dBack);
		$tmp_Day1db = date('d',$tmpDateTime1dBack);
		$tmpHour1db = date('H',$tmpDateTime1dBack);
		$tmp_Min1db = date('i',$tmpDateTime1dBack);
		$tmpDateTimeMySQL1db = $tmpYear1db."-".$tmpMnth1db."-".$tmp_Day1db." ".$tmpHour1db.":".$tmp_Min1db.":00";
		
		$numOfDays = 0;
		
		/* stepping daywise back for 1 week */
		while($tmpDateTime >= $curDateTime1weekBack)
		{
			$valuesLastWeek[$numOfDays][cValWeekday] = date('D', $tmpDateTime);
			$valuesLastWeek[$numOfDays][cValDate]    = date('Y.m.d', $tmpDateTime);
			$valuesLastWeek[$numOfDays][cXAxisCategories] = $valuesLastWeek[$numOfDays][cValWeekday]."-".$valuesLastWeek[$numOfDays][cValDate];		
			
			/* ### bytes sent/received START ### */
			$sql = "SELECT `valDate`, `valBytesSent`, `valBytesReceived`, `valUptime` FROM `dataVals` WHERE (`valDate` > '".$tmpDateTimeMySQL1db."' AND `valDate` <= '".$tmpDateTimeMySQL."') ORDER BY `valDate` DESC";
			$res = mysqli_query($link, $sql);
			$numOfRes = mysqli_num_rows($res);
			$numOfFetchedRes = 0;
			/* stepping hourwise back for 1 day */
			while($sqlRecord = mysqli_fetch_assoc($res))
			{
				$tmp_dbRes24hBack[$numOfFetchedRes][cValDate]          = $sqlRecord["valDate"];				
				$tmp_dbRes24hBack[$numOfFetchedRes][cValBytesSent]     = $sqlRecord["valBytesSent"];
				$tmp_dbRes24hBack[$numOfFetchedRes][cValBytesReceived] = $sqlRecord["valBytesReceived"];
				$tmp_dbRes24hBack[$numOfFetchedRes][cValUptime]        = $sqlRecord["valUptime"];
				$numOfFetchedRes++;
			}
			/* create differential values for bytes sent and bytes received values */
			for($i=0; $i<($numOfFetchedRes-1); $i++)
			{
				/* calculate the time difference as hours with minutes and seconds as decimal places */
				$sql = "SELECT TIMEDIFF('".$tmp_dbRes24hBack[$i][cValDate]."','".$tmp_dbRes24hBack[$i+1][cValDate]."')";
				/* execute the MYSQL-DB-query, contains the time difference as HH:mm:ss */
	   		$res = mysqli_query($link, $sql);
	   		if ($sqlRecord = mysqli_fetch_array($res))
	   		{
	   			$diffTime = explode(":", $sqlRecord[0]);
	   			$diffTimeInHours  = $diffTime[0] + ($diffTime[1]/cMinsPerHour) + ($diffTime[2]/cSecsPerHour);
	   			$diffValUptime    = ($tmp_dbRes24hBack[$i][cValUptime]        - $tmp_dbRes24hBack[$i+1][cValUptime]);
	   			$diffValBytesSent = ($tmp_dbRes24hBack[$i][cValBytesSent]     - $tmp_dbRes24hBack[$i+1][cValBytesSent])/$diffTimeInHours;
	   			$diffValBytesRcvd = ($tmp_dbRes24hBack[$i][cValBytesReceived] - $tmp_dbRes24hBack[$i+1][cValBytesReceived])/$diffTimeInHours;
	   		}
	   		else
	   		{
	   			$diffTimeInHours = 0;
	   		}
				/* There is no internet reconnection of the Fritz!Box, therefore the later uptimer is bigger than the previous one */			
				if ($diffValUptime > 0)
				{			
					/* the total bytes sent counter overrun at 4.294.967.295 */				
					if ($diffValBytesSent < 0)
					{
						$tmp_dbRes24hBack[$i][cValBytesSent] = ($tmp_dbRes24hBack[$i][cValBytesSent] + (uiMaxValue - $tmp_dbRes24hBack[$i+1][cValBytesSent]))/$diffTimeInHours;
					}
					else
					{
						$tmp_dbRes24hBack[$i][cValBytesSent] = $diffValBytesSent;
					}
					/* the total bytes received counter overrun at 4.294.967.295 */				
					if ($diffValBytesRcvd < 0)
					{
						$tmp_dbRes24hBack[$i][cValBytesReceived] = ($tmp_dbRes24hBack[$i][cValBytesReceived] + (uiMaxValue - $tmp_dbRes24hBack[$i+1][cValBytesReceived]))/$diffTimeInHours;
					}
					else
					{
						$tmp_dbRes24hBack[$i][cValBytesReceived] = $diffValBytesRcvd;
					}
				}
				else
				{
					/* No difference value at reconnection could be calculated */				
					$tmp_dbRes24hBack[$i][cValBytesSent]     = 0;
					$tmp_dbRes24hBack[$i][cValBytesReceived] = 0;
				}
			}
			/* only possible if any data set found */
			if(count($tmp_dbRes24hBack)>1)
			{
				/* remove last line because it is not diff'ed*/	
				array_pop($tmp_dbRes24hBack);	
				/* statistical evaluation */
				$tmp_avgValBytesSent24hb = 0;
				$tmp_avgValBytesReceived24hb = 0;
				for($i=0; $i<count($tmp_dbRes24hBack); $i++)
				{
					/* bytes sent */		
					$tmp_avgValBytesSent24hb += $tmp_dbRes24hBack[$i][cValBytesSent];
					/* bytes received */
					$tmp_avgValBytesReceived24hb += $tmp_dbRes24hBack[$i][cValBytesReceived];
				}
				$valuesLastWeek[$numOfDays][cValBytesSent]     = $tmp_avgValBytesSent24hb/(count($tmp_dbRes24hBack));
				$valuesLastWeek[$numOfDays][cValBytesReceived] = $tmp_avgValBytesReceived24hb/(count($tmp_dbRes24hBack));
			}
			/* ### bytes sent/received END ##### */

			/* ### upstream bit rate START ### */
			/* build the MYSQL-DB-query to list all values 1 day backwards from the day before now */ 
			$sql = "SELECT AVG(`valUpstreamBitRate`) FROM `dataVals` WHERE (`valDate` > '".$tmpDateTimeMySQL1db."' AND `valDate` <= '".$tmpDateTimeMySQL."')";
			/* execute the MYSQL-DB-query */
	   	$res = mysqli_query($link, $sql);
	   	if ($sqlRecord = mysqli_fetch_array($res))
	   	{
	   		$avgUpstreamBitRate = $sqlRecord[0];
	   	}
	   	else
	   	{
	   		$avgUpstreamBitRate = 0;
	   	}
			$valuesLastWeek[$numOfDays][cValUpstreamBitRate] = $avgUpstreamBitRate;  	
			/* ### upstream bit rate END ### */
	   	
			/* ### downstream bit rate START ### */
			$sql = "SELECT AVG(`valDownstreamBitRate`) FROM `dataVals` WHERE (`valDate` > '".$tmpDateTimeMySQL1db."' AND `valDate` <= '".$tmpDateTimeMySQL."')";
			/* execute the MYSQL-DB-query */
	   	$res = mysqli_query($link, $sql);
	   	if ($sqlRecord = mysqli_fetch_array($res))
	   	{
	   		$avgDownstreamBitRate = $sqlRecord[0];
	   	}
	   	else
	   	{
	   		$avgDownstreamBitRate = 0;
	   	}
			$valuesLastWeek[$numOfDays][cValDownstreamBitRate] = $avgDownstreamBitRate;
	   	/* ### downstream bit rate END ### */   	
	   	   	
	   	/* set new start/stop time for getting 1 day of the last week daywise */
	   	$tmpDateTime = $tmpDateTime1dBack;
	   	$tmpWeek     = $tmpWeek1db;
			$tmpYear     = $tmpYear1db;
			$tmpMnth     = $tmpMnth1db;
			$tmp_Day     = $tmp_Day1db;
			$tmpHour     = $tmpHour1db;
			$tmp_Min     = $tmp_Min1db;
			$tmpDateTimeMySQL = $tmpDateTimeMySQL1db;
	   	/* Stepping back for 1 day */
	   	$tmpDateTime1dBack = strtotime("-1 day", $tmpDateTime1dBack);
			$tmpWeek1db = date('W',$tmpDateTime1dBack);
			$tmpYear1db = date('Y',$tmpDateTime1dBack);
			$tmpMnth1db = date('m',$tmpDateTime1dBack);
			$tmp_Day1db = date('d',$tmpDateTime1dBack);
			$tmpHour1db = date('H',$tmpDateTime1dBack);
			$tmp_Min1db = date('i',$tmpDateTime1dBack);
			$tmpDateTimeMySQL1db = $tmpYear1db."-".$tmpMnth1db."-".$tmp_Day1db." ".$tmpHour1db.":".$tmp_Min1db.":00";
			
			$numOfDays++;
		}	
		/* ####### statistical evaluation of the week per day START ####### */
		$avgValBytesSent1wb = 0;
		$avgValBytesReceived1wb = 0;
		$avgValUpstreamBitRate1wb = 0;
		$avgValDownstreamBitRate1wb = 0;	
		/* bytes sent */
		$maxValBytesSent1wb = 0;
		$maxValBytesSent1wbPos = 0;
		$minValBytesSent1wbPos = 0;
		/* bytes received */
		$maxValBytesReceived1wb = 0;
		$maxValBytesReceived1wbPos = 0;
		$minValBytesReceived1wbPos = 0;
		/* upstream bit rate */
		$maxValUpstreamBitRate1wb = 0;
		$maxValUpstreamBitRate1wbPos = 0;
		$minValUpstreamBitRate1wbPos = 0;
		/* downstream bit rate */
		$maxValDownstreamBitRate1wb = 0;
		$maxValDownstreamBitRate1wbPos = 0;
		$minValDownstreamBitRate1wbPos = 0;
		
		for($i=0; $i<count($valuesLastWeek); $i++)
		{
			/* bytes sent */
			$avgValBytesSent1wb += $valuesLastWeek[$i][cValBytesSent];		
			if ($valuesLastWeek[$i][cValBytesSent] > $maxValBytesSent1wb)
			{
				$maxValBytesSent1wb = $valuesLastWeek[$i][cValBytesSent];
				$maxValBytesSent1wbPos = $i;
			}
			/* bytes received */
			$avgValBytesReceived1wb += $valuesLastWeek[$i][cValBytesReceived];		
			if ($valuesLastWeek[$i][cValBytesReceived] > $maxValBytesReceived1wb)
			{
				$maxValBytesReceived1wb = $valuesLastWeek[$i][cValBytesReceived];
				$maxValBytesReceived1wbPos = $i;
			}
			/* upstream rate */
			$avgValUpstreamBitRate1wb += $valuesLastWeek[$i][cValUpstreamBitRate];		
			if ($valuesLastWeek[$i][cValUpstreamBitRate] > $maxValUpstreamBitRate1wb)
			{
				$maxValUpstreamBitRate1wb = $valuesLastWeek[$i][cValUpstreamBitRate];
				$maxValUpstreamBitRate1wbPos = $i;
			}
			/* downstream rate */
			$avgValDownstreamBitRate1wb += $valuesLastWeek[$i][cValDownstreamBitRate];		
			if ($valuesLastWeek[$i][cValDownstreamBitRate] > $maxValDownstreamBitRate1wb)
			{
				$maxValDownstreamBitRate1wb = $valuesLastWeek[$i][cValDownstreamBitRate];
				$maxValDownstreamBitRate1wbPos = $i;
			}
		}
		$avgValBytesSent1wb = $avgValBytesSent1wb/count($valuesLastWeek);
		$avgValBytesReceived1wb = $avgValBytesReceived1wb/count($valuesLastWeek);
		$avgValUpstreamBitRate1wb = $avgValUpstreamBitRate1wb/count($valuesLastWeek);
		$avgValDownstreamBitRate1wb = $avgValDownstreamBitRate1wb/count($valuesLastWeek);
		
		$minValBytesSent1wb         = $maxValBytesSent1wb;
		$minValBytesReceived1wb     = $maxValBytesReceived1wb;
		$minValUpstreamBitRate1wb   = $maxValUpstreamBitRate1wb;
		$minValDownstreamBitRate1wb = $maxValDownstreamBitRate1wb;
	
		for($i=0; $i<count($valuesLastWeek); $i++)
		{
			/* bytes sent */
			if ($valuesLastWeek[$i][cValBytesSent] < $minValBytesSent1wb)
			{
				$minValBytesSent1wb = $valuesLastWeek[$i][cValBytesSent];
				$minValBytesSent1wbPos = $i;
			}
			/* bytes received */
			if ($valuesLastWeek[$i][cValBytesReceived] < $minValBytesReceived1wb)
			{
				$minValBytesReceived1wb = $valuesLastWeek[$i][cValBytesReceived];
				$minValBytesReceived1wbPos = $i;
			}
			/* upstream rate */
			if ($valuesLastWeek[$i][cValUpstreamBitRate] < $minValUpstreamBitRate1wb)
			{
				$minValUpstreamBitRate1wb = $valuesLastWeek[$i][cValUpstreamBitRate];
				$minValUpstreamBitRate1wbPos = $i;
			}
			/* downstream rate */
			if ($valuesLastWeek[$i][cValDownstreamBitRate] < $minValDownstreamBitRate1wb)
			{
				$minValDownstreamBitRate1wb = $valuesLastWeek[$i][cValDownstreamBitRate];
				$minValDownstreamBitRate1wbPos = $i;
			}
		}
		/* ####### statistical evaluation of the week per day END ######### */
	
	/* ##########################  calculation, evaluation 1 week back - end ############################## */
	
	/* ##########################  calculation, evaluation 1 year back - start ############################ */	
		
		$tmpDateTime = $curDateTimeStart1wb_1yb;
		$tmpWeek     = date('W',$tmpDateTime);
		$tmpYear     = date('Y',$tmpDateTime);
		$tmpMnth     = date('m',$tmpDateTime);
		$tmp_Day     = date('d',$tmpDateTime);
		$tmpHour     = date('H',$tmpDateTime);
		$tmp_Min     = date('i',$tmpDateTime);
		$tmpDateTimeMySQL = $tmpYear."-".$tmpMnth."-".$tmp_Day." ".$tmpHour.":".$tmp_Min.":00";
		/* Stepping back for 1 day */	
		$tmpDateTime1dBack = strtotime("-1 day", $curDateTimeStart1wb_1yb);
		$tmpWeek1db = date('W',$tmpDateTime1dBack);
		$tmpYear1db = date('Y',$tmpDateTime1dBack);
		$tmpMnth1db = date('m',$tmpDateTime1dBack);
		$tmp_Day1db = date('d',$tmpDateTime1dBack);
		$tmpHour1db = date('H',$tmpDateTime1dBack);
		$tmp_Min1db = date('i',$tmpDateTime1dBack);
		$tmpDateTimeMySQL1db = $tmpYear1db."-".$tmpMnth1db."-".$tmp_Day1db." ".$tmpHour1db.":".$tmp_Min1db.":00";
		/* Stepping back to the beginning of the actual month */	
		$tmpDateTime1mBack = mktime($curHourStart1wb_1yb, $cur_MinStart1wb_1yb, 0, $curMnthStart1wb_1yb, 1, $curYearStart1wb_1yb);
		$tmpWeek1mb = date('W',$tmpDateTime1mBack);
		$tmpYear1mb = date('Y',$tmpDateTime1mBack);
		$tmpMnth1mb = date('m',$tmpDateTime1mBack);
		$tmp_Day1mb = date('d',$tmpDateTime1mBack);
		$tmpHour1mb = date('H',$tmpDateTime1mBack);
		$tmp_Min1mb = date('i',$tmpDateTime1mBack);
		$tmpDateTimeMySQL1mb = $tmpYear1mb."-".$tmpMnth1mb."-".$tmp_Day1mb." ".$tmpHour1mb.":".$tmp_Min1mb.":00";
		
		$numOfMonthes = 0;
		/* stepping monthwise back for 1 year */
		while($tmpDateTime >= $curDateTime1yearBack)
		{
			$valuesLastYear[$numOfMonthes][cValDate]    = date('Y.m', $tmpDateTime1mBack);
						
			/* ### upstream bit rate START ### */
			/* build the MYSQL-DB-query to list the avg value 1 month backwards from the curent day */ 
			$sql = "SELECT AVG(`valUpstreamBitRate`) FROM `dataVals` WHERE (`valDate` > '".$tmpDateTimeMySQL1mb."' AND `valDate` <= '".$tmpDateTimeMySQL."')";
			/* execute the MYSQL-DB-query */
	   	$res = mysqli_query($link, $sql);
	   	if ($sqlRecord = mysqli_fetch_array($res))
	   	{
	   		$valuesLastYear[$numOfMonthes][cValUpstreamBitRate] = $sqlRecord[0];
	   	}
	   	else
	   	{
	   		$valuesLastYear[$numOfMonthes][cValUpstreamBitRate] = 0;
	   	}
	   	/* ### upstream bit rate END ##### */

			/* ### downstream bit rate START ### */
			/* build the MYSQL-DB-query to list the avg value 1 month backwards from the curent day */ 
			$sql = "SELECT AVG(`valDownstreamBitRate`) FROM `dataVals` WHERE (`valDate` > '".$tmpDateTimeMySQL1mb."' AND `valDate` <= '".$tmpDateTimeMySQL."')";
			/* execute the MYSQL-DB-query */
	   	$res = mysqli_query($link, $sql);
	   	if ($sqlRecord = mysqli_fetch_array($res))
	   	{
	   		$valuesLastYear[$numOfMonthes][cValDownstreamBitRate] = $sqlRecord[0];
	   	}
	   	else
	   	{
	   		$valuesLastYear[$numOfMonthes][cValDownstreamBitRate] = 0;
	   	}
	   	/* ### downstream bit rate END ##### */

			/* ### bytes sent/received START ### */
			/* stepping daywise back for 1 month */
			$numOfDays = 0;
			$valuesLastYear[$numOfMonthes][cValBytesSent]     = 0;
			$valuesLastYear[$numOfMonthes][cValBytesReceived] = 0;
			while($tmpDateTime > $tmpDateTime1mBack)
			{
				$sql = "SELECT `valDate`, `valBytesSent`, `valBytesReceived`, `valUptime` FROM `dataVals` WHERE (`valDate` > '".$tmpDateTimeMySQL1db."' AND `valDate` <= '".$tmpDateTimeMySQL."') ORDER BY `valDate` DESC";
				$res = mysqli_query($link, $sql);
				$numOfRes = mysqli_num_rows($res);
				$numOfFetchedRes = 0;
				unset($tmp_dbRes24hBack);
				/* stepping hourwise back for 1 day */
				while($sqlRecord = mysqli_fetch_assoc($res))
				{
					$tmp_dbRes24hBack[$numOfFetchedRes][cValDate]          = $sqlRecord["valDate"];				
					$tmp_dbRes24hBack[$numOfFetchedRes][cValBytesSent]     = $sqlRecord["valBytesSent"];
					$tmp_dbRes24hBack[$numOfFetchedRes][cValBytesReceived] = $sqlRecord["valBytesReceived"];
					$tmp_dbRes24hBack[$numOfFetchedRes][cValUptime]        = $sqlRecord["valUptime"];
					$numOfFetchedRes++;
				}
				/* create differential values for bytes sent and bytes received values */
				for($i=0; $i<($numOfFetchedRes-1); $i++)
				{
					/* calculate the time difference as hours with minutes and seconds as decimal places */
					$sql = "SELECT TIMEDIFF('".$tmp_dbRes24hBack[$i][cValDate]."','".$tmp_dbRes24hBack[$i+1][cValDate]."')";
					/* execute the MYSQL-DB-query, contains the time difference as HH:mm:ss */
	   			$res = mysqli_query($link, $sql);
	   			if ($sqlRecord = mysqli_fetch_array($res))
	   			{
	   				$diffTime = explode(":", $sqlRecord[0]);
	   				$diffTimeInHours  = $diffTime[0] + ($diffTime[1]/cMinsPerHour) + ($diffTime[2]/cSecsPerHour);
	   				$diffValUptime    = ($tmp_dbRes24hBack[$i][cValUptime]        - $tmp_dbRes24hBack[$i+1][cValUptime]);
	   				$diffValBytesSent = ($tmp_dbRes24hBack[$i][cValBytesSent]     - $tmp_dbRes24hBack[$i+1][cValBytesSent])/$diffTimeInHours;
	   				$diffValBytesRcvd = ($tmp_dbRes24hBack[$i][cValBytesReceived] - $tmp_dbRes24hBack[$i+1][cValBytesReceived])/$diffTimeInHours;
	   			}
	   			else
	   			{
	   				$diffTimeInHours = 0;
	   			}
					/* There is no internet reconnection of the Fritz!Box, therefore the later uptimer is bigger than the previous one */			
					if ($diffValUptime > 0)
					{			
						/* the total bytes sent counter overrun at 4.294.967.295 */				
						if ($diffValBytesSent < 0)
						{
							$tmp_dbRes24hBack[$i][cValBytesSent] = ($tmp_dbRes24hBack[$i][cValBytesSent] + (uiMaxValue - $tmp_dbRes24hBack[$i+1][cValBytesSent]))/$diffTimeInHours;
						}
						else
						{
							$tmp_dbRes24hBack[$i][cValBytesSent] = $diffValBytesSent;
						}
						/* the total bytes received counter overrun at 4.294.967.295 */				
						if ($diffValBytesRcvd < 0)
						{
							$tmp_dbRes24hBack[$i][cValBytesReceived] = ($tmp_dbRes24hBack[$i][cValBytesReceived] + (uiMaxValue - $tmp_dbRes24hBack[$i+1][cValBytesReceived]))/$diffTimeInHours;
						}
						else
						{
							$tmp_dbRes24hBack[$i][cValBytesReceived] = $diffValBytesRcvd;
						}
					}
					else
					{
						/* No difference value at reconnection could be calculated */				
						$tmp_dbRes24hBack[$i][cValBytesSent]     = 0;
						$tmp_dbRes24hBack[$i][cValBytesReceived] = 0;
					}
				}
				if(isset($tmp_dbRes24hBack))
				{				
					if(count($tmp_dbRes24hBack)>1)
					{			
						/* remove last line because it is not diff'ed*/	
						array_pop($tmp_dbRes24hBack);	
						/* statistical evaluation */
						$tmp_avgValBytesSent24hb = 0;
						$tmp_avgValBytesReceived24hb = 0;
						for($i=0; $i<count($tmp_dbRes24hBack); $i++)
						{
							/* bytes sent */		
							$tmp_avgValBytesSent24hb += $tmp_dbRes24hBack[$i][cValBytesSent];
							/* bytes received */
							$tmp_avgValBytesReceived24hb += $tmp_dbRes24hBack[$i][cValBytesReceived];
						}
						$valuesLastYear[$numOfMonthes][cValBytesSent]     += ($tmp_avgValBytesSent24hb/(count($tmp_dbRes24hBack)));
						$valuesLastYear[$numOfMonthes][cValBytesReceived] += ($tmp_avgValBytesReceived24hb/(count($tmp_dbRes24hBack)));
						$numOfDays++;
					}
				}
				/* set new start/stop time for getting 1 day of the last month daywise */
	   		$tmpDateTime = $tmpDateTime1dBack;
	   		$tmpWeek     = $tmpWeek1db;
				$tmpYear     = $tmpYear1db;
				$tmpMnth     = $tmpMnth1db;
				$tmp_Day     = $tmp_Day1db;
				$tmpHour     = $tmpHour1db;
				$tmp_Min     = $tmp_Min1db;
				$tmpDateTimeMySQL = $tmpDateTimeMySQL1db;
	   		/* Stepping back for 1 day */
	   		$tmpDateTime1dBack = strtotime("-1 day", $tmpDateTime1dBack);
				$tmpWeek1db = date('W',$tmpDateTime1dBack);
				$tmpYear1db = date('Y',$tmpDateTime1dBack);
				$tmpMnth1db = date('m',$tmpDateTime1dBack);
				$tmp_Day1db = date('d',$tmpDateTime1dBack);
				$tmpHour1db = date('H',$tmpDateTime1dBack);
				$tmp_Min1db = date('i',$tmpDateTime1dBack);
				$tmpDateTimeMySQL1db = $tmpYear1db."-".$tmpMnth1db."-".$tmp_Day1db." ".$tmpHour1db.":".$tmp_Min1db.":00";

			}
			/* store monthly average to array */
			if($numOfDays)
			{				
				$valuesLastYear[$numOfMonthes][cValBytesSent]     = $valuesLastYear[$numOfMonthes][cValBytesSent]/$numOfDays;
				$valuesLastYear[$numOfMonthes][cValBytesReceived] = $valuesLastYear[$numOfMonthes][cValBytesReceived]/$numOfDays;
			}
			else
			{
				$valuesLastYear[$numOfMonthes][cValBytesSent]     = 0;
				$valuesLastYear[$numOfMonthes][cValBytesReceived] = 0;
			}
			/* ### bytes sent/received END ##### */

			/* Stepping back for 1 month */	
			$tmpDateTime1mBack = strtotime("-1 month", $tmpDateTime1mBack);
			$tmpWeek1mb = date('W',$tmpDateTime1mBack);
			$tmpYear1mb = date('Y',$tmpDateTime1mBack);
			$tmpMnth1mb = date('m',$tmpDateTime1mBack);
			$tmp_Day1mb = date('d',$tmpDateTime1mBack);
			$tmpHour1mb = date('H',$tmpDateTime1mBack);
			$tmp_Min1mb = date('i',$tmpDateTime1mBack);
			$tmpDateTimeMySQL1mb = $tmpYear1mb."-".$tmpMnth1mb."-".$tmp_Day1mb." ".$tmpHour1mb.":".$tmp_Min1mb.":00";
	
			$numOfMonthes++;
		} /* end of while($tmpDateTime >= $curDateTime1yearBack) */
	
		/* ####### statistical evaluation of the year data START ####### */
		$avgValBytesSent1yb = 0;
		$avgValBytesReceived1yb = 0;
		$avgValUpstreamBitRate1yb = 0;
		$avgValDownstreamBitRate1yb = 0;	
		/* bytes sent */
		$maxValBytesSent1yb = 0;
		$maxValBytesSent1ybPos = 0;
		$minValBytesSent1ybPos = 0;
		/* bytes received */
		$maxValBytesReceived1yb = 0;
		$maxValBytesReceived1ybPos = 0;
		$minValBytesReceived1ybPos = 0;
		/* upstream bit rate */
		$maxValUpstreamBitRate1yb = 0;
		$maxValUpstreamBitRate1ybPos = 0;
		$minValUpstreamBitRate1ybPos = 0;
		/* downstream bit rate */
		$maxValDownstreamBitRate1yb = 0;
		$maxValDownstreamBitRate1ybPos = 0;
		$minValDownstreamBitRate1ybPos = 0;
		
		for($i=0; $i<count($valuesLastYear); $i++)
		{
			/* bytes sent */
			$avgValBytesSent1yb += $valuesLastYear[$i][cValBytesSent];		
			if ($valuesLastYear[$i][cValBytesSent] > $maxValBytesSent1yb)
			{
				$maxValBytesSent1yb = $valuesLastYear[$i][cValBytesSent];
				$maxValBytesSent1ybPos = $i;
			}
			/* bytes received */
			$avgValBytesReceived1yb += $valuesLastYear[$i][cValBytesReceived];		
			if ($valuesLastYear[$i][cValBytesReceived] > $maxValBytesReceived1yb)
			{
				$maxValBytesReceived1yb = $valuesLastYear[$i][cValBytesReceived];
				$maxValBytesReceived1ybPos = $i;
			}
			/* upstream rate */
			$avgValUpstreamBitRate1yb += $valuesLastYear[$i][cValUpstreamBitRate];		
			if ($valuesLastYear[$i][cValUpstreamBitRate] > $maxValUpstreamBitRate1yb)
			{
				$maxValUpstreamBitRate1yb = $valuesLastYear[$i][cValUpstreamBitRate];
				$maxValUpstreamBitRate1ybPos = $i;
			}
			/* downstream rate */
			$avgValDownstreamBitRate1yb += $valuesLastYear[$i][cValDownstreamBitRate];		
			if ($valuesLastYear[$i][cValDownstreamBitRate] > $maxValDownstreamBitRate1yb)
			{
				$maxValDownstreamBitRate1yb = $valuesLastYear[$i][cValDownstreamBitRate];
				$maxValDownstreamBitRate1ybPos = $i;
			}
		}
		$avgValBytesSent1yb = $avgValBytesSent1yb/count($valuesLastYear);
		$avgValBytesReceived1yb = $avgValBytesReceived1yb/count($valuesLastYear);
		$avgValUpstreamBitRate1yb = $avgValUpstreamBitRate1yb/count($valuesLastYear);
		$avgValDownstreamBitRate1yb = $avgValDownstreamBitRate1yb/count($valuesLastYear);
		
		$minValBytesSent1yb         = $maxValBytesSent1yb;
		$minValBytesReceived1yb     = $maxValBytesReceived1yb;
		$minValUpstreamBitRate1yb   = $maxValUpstreamBitRate1yb;
		$minValDownstreamBitRate1yb = $maxValDownstreamBitRate1yb;
	
		for($i=0; $i<count($valuesLastYear); $i++)
		{
			/* bytes sent */
			if ($valuesLastYear[$i][cValBytesSent] < $minValBytesSent1yb)
			{
				$minValBytesSent1yb = $valuesLastYear[$i][cValBytesSent];
				$minValBytesSent1ybPos = $i;
			}
			/* bytes received */
			if ($valuesLastYear[$i][cValBytesReceived] < $minValBytesReceived1yb)
			{
				$minValBytesReceived1yb = $valuesLastYear[$i][cValBytesReceived];
				$minValBytesReceived1ybPos = $i;
			}
			/* upstream rate */
			if ($valuesLastYear[$i][cValUpstreamBitRate] < $minValUpstreamBitRate1yb)
			{
				$minValUpstreamBitRate1yb = $valuesLastYear[$i][cValUpstreamBitRate];
				$minValUpstreamBitRate1ybPos = $i;
			}
			/* downstream rate */
			if ($valuesLastYear[$i][cValDownstreamBitRate] < $minValDownstreamBitRate1yb)
			{
				$minValDownstreamBitRate1yb = $valuesLastYear[$i][cValDownstreamBitRate];
				$minValDownstreamBitRate1ybPos = $i;
			}
		}
		/* ####### statistical evaluation of the week per day END ######### */
	
	/* ##########################  calculation, evaluation 1 year back - end ############################## */



	/* %%%%%%%%%%%%%%% DEBUGGING %%%%%%%%%%%%%%%%%%% */
/*		echo "<tr>";
		echo "<td>numOfDays: </td>";
		echo "<td>".$numOfDays."</td>";
		echo "<td>".$valuesLastYear[$numOfMonthes][cValDate]."</td>";
		echo "<td>Mittlere gesendete Bytes/Stunde: </td>";
		echo "<td>".number_format($valuesLastYear[$numOfMonthes][cValBytesSent],0,",",".")."</td>";
		echo "</tr>";*/
	/* %%%%%%%%%%%%%%% DEBUGGING %%%%%%%%%%%%%%%%%%% */


	
	/* ##########################  HTML-output, current evaluation time - start ########################### */
		echo "<tr>\r\n";
		echo "<td><h2>Auswertung am: </h2></td>\r\n";
		echo "<td>".$curDateTimeMySQL."</td>\r\n";
		echo "</tr>\r\n";
	/* ##########################  HTML-output, current evaluation time - end ############################# */
	
	/* ##########################  HTML-output, sent bytes - start ######################################## */
		echo "<tr>\r\n";
		echo "<td colspan=4><br><hr><br></td>\r\n";
		echo "</tr>\r\n";		
		echo "<tr>\r\n";
		echo "<td><h2>Gesendete Bytes</h2></td>\r\n";
		echo "<td colspan=3><img src=\"uplinkdata.png\" width=\"37%\" height=\"30%\" alt=\"uplinkdata.png\"></td>\r\n";
		echo "</tr>\r\n";
	
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - start §§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td><h3>letzter Wert als MB/Stunde:</h3><img src=\"".$lastValBytesSentTrend."\" width=\"30%\" height=\"30%\" alt=\"trend.png\"></td>\r\n";
		echo "<td>".number_format(($dbRes24hBack[0][cValBytesSent]*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am:</td>\r\n";
		echo "<td>".$dbRes24hBack[0][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - end §§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten 24 Stunden als MB/Stunde</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;te Anzahl: </td>\r\n";
		echo "<td>".number_format(($maxValBytesSent24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$maxValBytesSent24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinste Anzahl: </td>\r\n";
		echo "<td>".number_format(($minValBytesSent24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$minValBytesSent24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlere Anzahl: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValBytesSent24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";
		
		plotBarGraph($dbRes24hBack, cXAxisCategories, cValBytesSent, "sendBytes_Daily.png", cScaleFactor2Mega, $minValBytesSent24hbPos, $maxValBytesSent24hbPos);
		echo "<tr>\r\n";
		echo "\r\n";
		echo "\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"sendBytes_Daily.png\" /></p></details></td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last week - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten Woche als MB/Stunde</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;te Anzahl: </td>\r\n";
		echo "<td>".number_format(($maxValBytesSent1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$maxValBytesSent1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinste Anzahl: </td>\r\n";
		echo "<td>".number_format(($minValBytesSent1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$minValBytesSent1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlere Anzahl: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValBytesSent1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
	
		plotBarGraph($valuesLastWeek, cXAxisCategories, cValBytesSent, "sendBytes_Weekly.png", cScaleFactor2Mega, $minValBytesSent1wbPos, $maxValBytesSent1wbPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"sendBytes_Weekly.png\" /></p></details></td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last week - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last year - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>des letzten Jahres als MB/Stunde</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;te Anzahl: </td>\r\n";
		echo "<td>".number_format(($maxValBytesSent1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$maxValBytesSent1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinste Anzahl: </td>\r\n";
		echo "<td>".number_format(($minValBytesSent1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$minValBytesSent1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlere Anzahl: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValBytesSent1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
	
		plotBarGraph($valuesLastYear, cValDate, cValBytesSent, "sendBytes_Yearly.png", cScaleFactor2Mega, $minValBytesSent1ybPos, $maxValBytesSent1ybPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"sendBytes_Yearly.png\" /></p></details></td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last year - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	/* ##########################  HTML-output, sent bytes - end ########################################## */
	
	/* ##########################  HTML-output, received bytes - start #################################### */	
		echo "<tr>\r\n";
		echo "<td colspan=4><br><hr><br></td>\r\n";
		echo "</tr>\r\n";		
		echo "<tr>\r\n";
		echo "<td><h2>Empfangene Bytes</h2></td>\r\n";
		echo "<td colspan=3><img src=\"downlinkdata.png\" width=\"30%\" height=\"30%\" alt=\"downlinkdata.png\"></td>\r\n";
		echo "</tr>\r\n";
	
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - start §§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td><h3>letzter Wert als MB/Stunde:</h3><img src=\"".$lastValBytesReceivedTrend."\" width=\"30%\" height=\"30%\" alt=\"trend.png\"></td>\r\n";
		echo "<td>".number_format(($dbRes24hBack[0][cValBytesReceived]*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am:</td>\r\n";
		echo "<td>".$dbRes24hBack[0][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - end §§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */	
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten 24 Stunden als MB/Stunde</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;te Anzahl: </td>\r\n";
		echo "<td>".number_format(($maxValBytesReceived24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$maxValBytesReceived24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinste Anzahl: </td>\r\n";
		echo "<td>".number_format(($minValBytesReceived24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$minValBytesReceived24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlere Anzahl: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValBytesReceived24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
		
		plotBarGraph($dbRes24hBack, cXAxisCategories, cValBytesReceived, "receivedBytes_Daily.png", cScaleFactor2Mega, $minValBytesReceived24hbPos, $maxValBytesReceived24hbPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"receivedBytes_Daily.png\" /></p></details></td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last week - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten Woche als MB/Stunde</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;te Anzahl: </td>\r\n";
		echo "<td>".number_format(($maxValBytesReceived1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$maxValBytesReceived1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinste Anzahl: </td>\r\n";
		echo "<td>".number_format(($minValBytesReceived1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$minValBytesReceived1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlere Anzahl: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValBytesReceived1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
	
		plotBarGraph($valuesLastWeek, cXAxisCategories, cValBytesReceived, "receivedBytes_Weekly.png", cScaleFactor2Mega, $minValBytesReceived1wbPos, $maxValBytesReceived1wbPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"receivedBytes_Weekly.png\" /></p></details></td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last week - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last year - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>des letzten Jahres als MB/Stunde</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;te Anzahl: </td>\r\n";
		echo "<td>".number_format(($maxValBytesReceived1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$maxValBytesReceived1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinste Anzahl: </td>\r\n";
		echo "<td>".number_format(($minValBytesReceived1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$minValBytesReceived1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlere Anzahl: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValBytesReceived1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
	
		plotBarGraph($valuesLastYear, cValDate, cValBytesReceived, "receivedBytes_Yearly.png", cScaleFactor2Mega, $minValBytesReceived1ybPos, $maxValBytesReceived1ybPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"receivedBytes_Yearly.png\" /></p></details></td>\r\n";		
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last year - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */	
	
	
	/* ##########################  HTML-output, received bytes - end ###################################### */
	
	/* ##########################  HTML-output, upstream bit rate - start ################################# */
		echo "<tr>\r\n";
		echo "<td colspan=4><br><hr><br></td>\r\n";
		echo "</tr>\r\n";		
		echo "<tr>\r\n";
		echo "<td><h2>Uplink-Speed</h2></td>\r\n";
		echo "<td colspan=3><img src=\"uplinkspeed.png\" width=\"22%\" height=\"29%\" alt=\"uplinkspeed.png\"></td>\r\n";
		echo "</tr>\r\n";
	
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - start §§§§§§§§§§§§§§§ */	
		echo "<tr>\r\n";
		echo "<td><h3>letzter Wert als MBit/s:</h3><img src=\"".$lastValUpstreamBitRateTrend."\" width=\"30%\" height=\"30%\" alt=\"trend.png\"></td>\r\n";
		echo "<td>".number_format(($dbRes24hBack[0][cValUpstreamBitRate]*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am:</td>\r\n";
		echo "<td>".$dbRes24hBack[0][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - end §§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten 24 Stunden als MBit/s</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;ter Wert: </td>\r\n";
		echo "<td>".number_format(($maxValUpstreamBitRate24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$maxValUpstreamBitRate24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinster Wert: </td>\r\n";
		echo "<td>".number_format(($minValUpstreamBitRate24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$minValUpstreamBitRate24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlerer Wert: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValUpstreamBitRate24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";
		
		plotLineGraph($dbRes24hBack, cXAxisCategories, cValUpstreamBitRate, "upstreamBitRate_Daily.png", cScaleFactor2Mega, $minValUpstreamBitRate24hbPos, $maxValUpstreamBitRate24hbPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"upstreamBitRate_Daily.png\" /></p></details></td>\r\n";		
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last week - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten Woche als MBit/s</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;ter Wert: </td>\r\n";
		echo "<td>".number_format(($maxValUpstreamBitRate1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$maxValUpstreamBitRate1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinster Wert: </td>\r\n";
		echo "<td>".number_format(($minValUpstreamBitRate1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$minValUpstreamBitRate1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlerer Wert: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValUpstreamBitRate1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
	
		plotLineGraph($valuesLastWeek, cXAxisCategories, cValUpstreamBitRate, "upstreamBitRate_Weekly.png", cScaleFactor2Mega, $minValUpstreamBitRate1wbPos, $maxValUpstreamBitRate1wbPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"upstreamBitRate_Weekly.png\" /></p></details></td>\r\n";		
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last week - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last year - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>des letzten Jahres als MBit/s</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;ter Wert: </td>\r\n";
		echo "<td>".number_format(($maxValUpstreamBitRate1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$maxValUpstreamBitRate1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinster Wert: </td>\r\n";
		echo "<td>".number_format(($minValUpstreamBitRate1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$minValUpstreamBitRate1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlerer Wert: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValUpstreamBitRate1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
	
		plotLineGraph($valuesLastYear, cValDate, cValUpstreamBitRate, "upstreamBitRate_Yearly.png", cScaleFactor2Mega, $minValUpstreamBitRate1ybPos, $maxValUpstreamBitRate1ybPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"upstreamBitRate_Yearly.png\" /></p></details></td>\r\n";		
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last year - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	/* ##########################  HTML-output, upstream bit rate - end ################################### */
	
	/* ##########################  HTML-output, downstream bit rate - start ############################### */
		echo "<tr>\r\n";
		echo "<td colspan=4><br><hr><br></td>\r\n";
		echo "</tr>\r\n";		
		echo "<tr>\r\n";
		echo "<td><h2>Downlink-Speed</h2></td>\r\n";
		echo "<td colspan=3><img src=\"downlinkspeed.png\" width=\"24%\" height=\"30%\" alt=\"downlinkspeed.png\"></td>\r\n";
		echo "</tr>\r\n";
	
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - start §§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td><h3>letzter Wert als MBit/s:</h3><img src=\"".$lastValDownstreamBitRateTrend."\" width=\"30%\" height=\"30%\" alt=\"trend.png\"></td>\r\n";
		echo "<td>".number_format(($dbRes24hBack[0][cValDownstreamBitRate]*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am:</td>\r\n";
		echo "<td>".$dbRes24hBack[0][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last value to 24 hour trend - end §§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten 24 Stunden als MBit/s</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;ter Wert: </td>\r\n";
		echo "<td>".number_format(($maxValDownstreamBitRate24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$maxValDownstreamBitRate24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinster Wert: </td>\r\n";
		echo "<td>".number_format(($minValDownstreamBitRate24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$dbRes24hBack[$minValDownstreamBitRate24hbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlerer Wert: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValDownstreamBitRate24hb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";
		
		plotLineGraph($dbRes24hBack, cXAxisCategories, cValDownstreamBitRate, "downstreamBitRate_Daily.png", cScaleFactor2Mega, $minValDownstreamBitRate24hbPos, $maxValDownstreamBitRate24hbPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"downstreamBitRate_Daily.png\" /></p></details></td>\r\n";		
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last 24 hours - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last week - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>der letzten Woche als MBit/s</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;ter Wert: </td>\r\n";
		echo "<td>".number_format(($maxValDownstreamBitRate1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$maxValDownstreamBitRate1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinster Wert: </td>\r\n";
		echo "<td>".number_format(($minValDownstreamBitRate1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastWeek[$minValDownstreamBitRate1wbPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlerer Wert: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValDownstreamBitRate1wb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
		
		plotLineGraph($valuesLastWeek, cXAxisCategories, cValDownstreamBitRate, "downstreamBitRate_Weekly.png", cScaleFactor2Mega, $minValDownstreamBitRate1wbPos, $maxValDownstreamBitRate1wbPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"downstreamBitRate_Weekly.png\" /></p></details></td>\r\n";
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last week - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	
	/* §§§§§§§§§§§§§§§  HTML-output, last year - start §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
		echo "<tr>\r\n";
		echo "<td colspan=4><h3>des letzten Jahres als MBit/s</h3></td>\r\n";
		echo "</tr>\r\n";	
		
		echo "<tr>\r\n";
		echo "<td>Gr&ouml;&szlig;ter Wert: </td>\r\n";
		echo "<td>".number_format(($maxValDownstreamBitRate1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$maxValDownstreamBitRate1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
	
		echo "<tr>\r\n";
		echo "<td>Kleinster Wert: </td>\r\n";
		echo "<td>".number_format(($minValDownstreamBitRate1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "<td>am: </td>\r\n";
		echo "<td>".$valuesLastYear[$minValDownstreamBitRate1ybPos][cValDate]."</td>\r\n";
		echo "</tr>\r\n";
		
		echo "<tr>\r\n";
		echo "<td>Mittlerer Wert: </td>\r\n";
		echo "<td colspan=3>".number_format(($avgValDownstreamBitRate1yb*cScaleFactor2Mega),0,",",".")."</td>\r\n";
		echo "</tr>\r\n";	
		
		plotLineGraph($valuesLastYear, cValDate, cValDownstreamBitRate, "downstreamBitRate_Yearly.png", cScaleFactor2Mega, $minValDownstreamBitRate1ybPos, $maxValDownstreamBitRate1ybPos);
		echo "<tr>\r\n";
		echo "<td colspan=4><details><summary>Grafik</summary><p><img src=\"downstreamBitRate_Yearly.png\" /></p></details></td>\r\n";		
		echo "</tr>\r\n";
	/* §§§§§§§§§§§§§§§  HTML-output, last week - end §§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§§ */
	/* ##########################  HTML-output, downstream bit rate - end ################################# */
	}
?>
</tr>
</table>
<br>
<hr>
Layout zuletzt aktualisiert am : <!--HTML-FORMAT:AUTO-TEXT-DATUM-->01.03.2020<!--/-->
</body>
</html>
