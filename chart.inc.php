<?php
function plotLineGraph($values, $xColumn, $yColumn, $fileName, $scaleFactor, $minVal, $maxVal)
{
	# -------- Const Definition -------------------------------
	$img_width=900;
	$img_height=600;
	$margins = 30;
	$yAxis_label_width = 20;
	$xAxis_label_width = 10;
	$horizontal_lines=20;
	$scaleYMaxFactor=1.2;
	# -------- Calculate dependent values -----------------
	$graph_width=$img_width - ($margins * 2) - $yAxis_label_width;
	$graph_height=$img_height - ($margins * 2) - $xAxis_label_width;
	$total_points=count($values);
	
	$im = imagecreate($img_width,$img_height);

	// Colors
	$grau = imagecolorallocate($im, 192, 192, 192);
	imagefilledrectangle ($im, 0, 0, $img_width, $img_height, $grau);
	# ------ Create the border around the graph ------
	$line_color = imagecolorallocate($im, 0, 0, 0);
	$graph_color=imagecolorallocate($im,0,64,128);
	$graph_min_color=imagecolorallocate($im,0,255,128);
	$graph_max_color=imagecolorallocate($im,255,64,128);

	# ------- Max value is required to adjust the scale -------
	$max_value = 0;	
	for($i=0;$i<$total_points;$i++)
	{
		if(($values[$i][$yColumn] * $scaleFactor) > $max_value)
		{
			$max_value = ($values[$i][$yColumn] * $scaleFactor);
		}
	}

	# -------- Create scale and draw horizontal lines  --------
	#lowest horizontal line = 0
	#highest horizontal line = max_value * scaleYMaxFactor
	$horizontal_scale_value = ($max_value * $scaleYMaxFactor)/$horizontal_lines;
	for($i=0;$i<=$horizontal_lines;$i++)
	{
		$x1=$margins + $yAxis_label_width;
		$x2=$img_width - $margins;
		$y=$img_height - (($margins + $xAxis_label_width) + ($i * ($graph_height / $horizontal_lines)));
		imageline($im,$x1,$y,$x2,$y,$line_color);
		imagestring($im, 0, $margins, $y-5, number_format(($horizontal_scale_value * $i), 1), $line_color);
	}
	
	# ----------- Draw the vertical lines + data points ------
	for($i=0;$i<$total_points;$i++)
	{
		$x=($margins + $yAxis_label_width) + ($i * ($graph_width / ($total_points - 1)));
		$y1=$img_height - ($margins + $xAxis_label_width);
		$y2=$margins;
		$y=$img_height - (($margins + $xAxis_label_width) + ($graph_height * (($values[$i][$yColumn] * $scaleFactor) / ($max_value * $scaleYMaxFactor))));
		imageline($im,$x,$y1,$x,$y2,$line_color);
		imagestring($im,0,$x-15,$img_height-15,$values[$i][$xColumn],$line_color);
		
		if ($i == $minVal)
		{
			imagefilledarc($im, $x, $y, 10, 10, 0, 360, $graph_min_color, IMG_ARC_PIE);
			imagestring($im,0,$x-5,$y-15,number_format(($values[$i][$yColumn] * $scaleFactor),1),$graph_min_color);
		}
		elseif($i == $maxVal)
		{
			imagefilledarc($im, $x, $y, 10, 10, 0, 360, $graph_max_color, IMG_ARC_PIE);
			imagestring($im,0,$x-5,$y-15,number_format(($values[$i][$yColumn] * $scaleFactor),1),$graph_max_color);
		}
		else
		{
			imagefilledarc($im, $x, $y, 10, 10, 0, 360, $graph_color, IMG_ARC_PIE);
			imagestring($im,0,$x-5,$y-15,number_format(($values[$i][$yColumn] * $scaleFactor),1),$graph_color);
		}		
		
		if($i>0)
		{
			imagesetthickness($im, 3);			
			imageline($im,$x_old,$y_old,$x,$y,$graph_color);
			imagesetthickness($im, 1);
		}
		$x_old = $x;
		$y_old = $y;
	}

  	# ----------- Create graphic file ----------------------
  	imagepng($im, $fileName);
  	# ----------- Release the memory -----------------------
  	imagedestroy($im);
}



function plotBarGraph($values, $xColumn, $yColumn, $fileName, $scaleFactor, $minVal, $maxVal)
{
	$img_width=900;
	$img_height=600; 
	$margins=20;
	$horizontal_lines=20;
	$bar_width=20;	

	# ---- Find the size of graph by substracting the size of borders
	$graph_width=$img_width - $margins * 2;
	$graph_height=$img_height - $margins * 2; 
	$img=imagecreate($img_width,$img_height);

	$total_bars=count($values);
	$gap= ($graph_width- $total_bars * $bar_width ) / ($total_bars +1);

	# -------  Define Colors ----------------
	$bar_color=imagecolorallocate($img,0,64,128);
	$bar_min_color=imagecolorallocate($img,0,255,128);
	$bar_max_color=imagecolorallocate($img,255,64,128);
	$background_color=imagecolorallocate($img,240,240,255);
	$border_color=imagecolorallocate($img,200,200,200);
	$line_color=imagecolorallocate($img,220,220,220);

	# ------ Create the border around the graph ------
	imagefilledrectangle($img,1,1,$img_width-2,$img_height-2,$border_color);
	imagefilledrectangle($img,$margins,$margins,$img_width-1-$margins,$img_height-1-$margins,$background_color);

	# ------- Max value is required to adjust the scale -------
	$max_value = 0;	
	for($i=0;$i<$total_bars;$i++)
	{
		if(($values[$i][$yColumn] * $scaleFactor) > $max_value)
		{
			$max_value = ($values[$i][$yColumn] * $scaleFactor);
		}
	}	
	$ratio = $graph_height/$max_value;

	# -------- Create scale and draw horizontal lines  --------
	$horizontal_gap=$graph_height/$horizontal_lines;

	for($i=1;$i<=$horizontal_lines;$i++)
	{
		$y=$img_height - $margins - $horizontal_gap * $i ;
		imageline($img,$margins,$y,$img_width-$margins,$y,$line_color);
	}

	# ----------- Draw the bars here ------
	for($i=0;$i<$total_bars;$i++)
	{ 

		$x1= $margins + $gap + $i * ($gap+$bar_width) ;
		$x2= $x1 + $bar_width; 
		$y1=$margins + $graph_height - intval($values[$i][$yColumn] * $ratio * $scaleFactor) ;
		$y2=$img_height - $margins;
		imagestring($img,0,$x1-10,$img_height-15,$values[$i][$xColumn],$bar_color);        
		if ($i == $minVal)
		{
			imagestring($img,0,$x1+3,$y1-10,number_format(intval($values[$i][$yColumn] * $scaleFactor)),$bar_min_color);
			imagefilledrectangle($img,$x1,$y1,$x2,$y2,$bar_min_color);
		}
		elseif($i == $maxVal)
		{
			imagestring($img,0,$x1+3,$y1-10,number_format(intval($values[$i][$yColumn] * $scaleFactor)),$bar_max_color);
			imagefilledrectangle($img,$x1,$y1,$x2,$y2,$bar_max_color);
		}
		else
		{		
			imagestring($img,0,$x1+3,$y1-10,number_format(intval($values[$i][$yColumn] * $scaleFactor)),$bar_color);
			imagefilledrectangle($img,$x1,$y1,$x2,$y2,$bar_color);
		}
	}
	imagepng($img, $fileName);
	imagedestroy($img);
}
?>


