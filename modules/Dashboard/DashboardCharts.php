<?php
/*************************************************************************************************
 * Copyright 2016 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
 * Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
 * file except in compliance with the License. You can redistribute it and/or modify it
 * under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
 * granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
 * the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
 * applicable law or agreed to in writing, software distributed under the License is
 * distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing
 * permissions and limitations under the License. You may obtain a copy of the License
 * at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
 *************************************************************************************************
 *  Module       : Dashboard Charts
 *  Version      : 1.0
 *  Author       : JPL TSolucio, S. L.
 *************************************************************************************************/

class DashboardCharts {

	static public function getChartHTML($labels, $values, $graph_title, $target_values, $html_imagename, $width, $height, $left, $right, $top, $bottom, $graph_type, $legend_position='right', $responsive=true) {
		$lbls = implode(',',$labels);
		$vals = str_replace('::',',',$values);
		$lnks = array();
		$cnt=0;
		foreach ($target_values as $value) {
			$lnks[] = $cnt.':'.$value;
			$cnt++;
		}
		$lnks = implode(',',$lnks);
		$bcolor = array();
		for ($cnt=1;$cnt<count($labels);$cnt++) {
			$bcolor[] = 'getRandomColor()';
		}
		$bcolor = implode(',',$bcolor);
		if ($graph_title!='') {
			$gtitle = 'label:"'.$graph_title.'",';
			$display = 'display:true,';
		} else {
			$gtitle = '';
			$display = 'display:false,';
		}
		if ($graph_title=='pie') {
			$display = 'display:true,';
		}
		if ($legend_position!='') {
			$legend_position = 'position: "'.$legend_position.'",';
		}
		if ($responsive) {
			$respproperty = 'true';
		} else {
			$respproperty = 'false';
		}
		$sHTML = <<<EOF
<canvas id="$html_imagename" style="width:{$width}px;height:{$height}px;margin:auto;padding:10px;"></canvas>
<script type="text/javascript">
window.doChart{$html_imagename} = function(charttype) {
	let stuffchart = document.getElementById('{$html_imagename}');
	let chartDataObject = {
		labels: [{$lbls}],
		datasets: [{
			data: [ $vals ],
			$gtitle
			backgroundColor: [ $bcolor ]
		}]
	};
	window.schart{$html_imagename} = new Chart(stuffchart,{
		type: '{$graph_type}',
		data: chartDataObject,
		options: {
			responsive: $respproperty,
			legend: {
				$legend_position
				$display
				labels: {
					fontSize: 11,
					boxWidth: 18
				}
			}
		}
	});
	stuffchart.addEventListener('click',function(evt) {
		let activePoint = schart{$html_imagename}.getElementAtEvent(evt);
		if (activePoint.length == 0) return;
		let clickzone = { $lnks };
		if (clickzone == undefined || clickzone == {} || clickzone.length == 0) return;
		let a = document.createElement("a");
		a.target = "_blank";
		a.href = clickzone[activePoint[0]._index];
		document.body.appendChild(a);
		a.click();
	});
}
doChart{$html_imagename}('{$graph_type}');
</script>
EOF;
		return $sHTML;
	}

	static public function getChartHTMLwithObject($chartObject, $targetObject, $html_imagename, $width, $height, $left, $right, $top, $bottom) {
		$tgt = reset(json_decode($targetObject,true));
		if (is_array($tgt)) {
			$czone = 'clickzone[activePoint[0]._datasetIndex][activePoint[0]._index]';
		} else {
			$czone = 'clickzone[activePoint[0]._index]';
		}
		$sHTML = <<<EOF
<canvas id="$html_imagename" style="width:{$width}px;height:{$height}px;margin:auto;padding:10px;"></canvas>
<script type="text/javascript">
window.doChart{$html_imagename} = function() {
	let stuffchart = document.getElementById('{$html_imagename}');
	window.schart{$html_imagename} = new Chart(stuffchart,$chartObject);
	stuffchart.addEventListener('click',function(evt) {
		let activePoint = schart{$html_imagename}.getElementAtEvent(evt);
		if (activePoint.length == 0) return;
		let clickzone = $targetObject;
		if (clickzone == undefined || clickzone == {} || clickzone.length == 0) return;
		let a = document.createElement("a");
		a.target = "_blank";
		a.href = $czone;
		document.body.appendChild(a);
		a.click();
	});
}
doChart{$html_imagename}();
</script>
EOF;
		return $sHTML;
	}

	static public function convertToArray($values,$translate=false,$withquotes=false) {
		if (strpos($values,'::')===false) $values = urldecode($values);
		$vals = explode('::',$values);
		if ($translate) {
			$vals = array_map(function($v) {
				return getTranslatedString($v,$v);
			}, $vals);
		}
		$ud = $urldecode;
		if ($withquotes) {
			$vals = array_map(function($v) {
				return '"'.urldecode($v).'"';
			}, $vals);
		}
		return $vals;
	}

	static public function pipeline_by_sales_stage($datax, $date_start, $date_end, $user_id, $width, $height){
		global $log, $current_user, $adb, $mod_strings;
		$log->debug("Entering pipeline_by_sales_stage(".$datax.",".$date_start.",".$date_end.",".$user_id.",".$width.",".$height.") method ...");

		$where=' deleted = 0 ';
		$labels = array();
		//build the where clause for the query that matches $datax
		$count = count($datax);
		if ($count>0) {
			$where .= " and sales_stage in ( ";
			$ss_i = 0;
			foreach ($datax as $key=>$value) {
				if($ss_i != 0) {
					$where .= ', ';
				}
				$where .= "'".addslashes($key)."'";
				$labels[] = getTranslatedString($key,'Potentials');
				$ss_i++;
			}
			$where .= ")";
		}

		$count = count($user_id);
		if ($count>0) {
			$where .= ' and smownerid in ( ';
			$ss_i = 0;
			foreach ($user_id as $key=>$value) {
				if($ss_i != 0) $where .= ", ";
				$where .= "'".addslashes($value)."'";
				$ss_i++;
			}
			$where .= ")";
		}

		$date = new DateTimeField($date_start);
		$endDate = new DateTimeField($date_end);
		//build the where clause for the query that matches $date_start and $date_end
		$where .= " AND closingdate >= '$date_start' AND closingdate <= '$date_end'";
		$subtitle = $mod_strings['LBL_DATE_RANGE']." ".$date->getDisplayDate()." ".$mod_strings['LBL_DATE_RANGE_TO']." ".$endDate->getDisplayDate();

		$sql = 'SELECT `sales_stage`,smownerid,count(*) as potcnt,sum(amount) as potsum
			FROM `vtiger_potential`
			INNER JOIN vtiger_crmentity on crmid=potentialid '.
			getNonAdminAccessControlQuery('Potentials', $current_user).
			" WHERE $where
			GROUP BY `sales_stage`,smownerid
			ORDER BY sales_stage,smownerid";
		$rs = $adb->pquery($sql,array());
		//build pipeline by sales stage data
		$total = 0;
		$count = array();
		$sum = array();
		while ($row = $adb->fetch_array($rs)) {
			$amount = CurrencyField::convertFromMasterCurrency($row['potsum'],$current_user->conv_rate);
			$sum[$row['sales_stage']][$row['smownerid']] = $amount;
			$count[$row['sales_stage']][$row['smownerid']] = $row['potcnt'];
			$total = $total + $amount;
		}
		$titlestr = $mod_strings['LBL_TOTAL_PIPELINE'].html_to_utf8($current_user->currency_symbol).$total;
		$datay = array();
		$aTargets = array();
		$aAlts = array();
		$cvid = getCvIdOfAll("Potentials");
		$dsetidx = 0;
		foreach ($user_id as $the_id) {
			$the_user = getEntityName('Users', $the_id);
			$the_user = $the_user[$the_id];
			$dset = array(
				'label' => $the_user,
				'backgroundColor' => sprintf('#%02X%02X%02X', rand(50,255), rand(50,255), rand(50,255) ),
				'data' => array(),
			);
			$lnkidx = 0;
			foreach ($datax as $stage_key=>$stage_translation) {
				$dset['data'][] = (isset($sum[$stage_key][$the_id]) ? $sum[$stage_key][$the_id] : 0);
				if (!isset($aAlts[$the_id])) {
					$aAlts[$the_id] = array();
				}
				if (isset($sum[$stage_key][$the_id])) {
					array_push($aAlts[$the_id], $the_user.' - '.$count[$stage_key][$the_id]." ".$mod_strings['LBL_OPPS_IN_STAGE']." $stage_translation");
				} else {
					array_push($aAlts[$the_id], '');
				}
				if (!isset($aTargets[$dsetidx])) {
					$aTargets[$dsetidx] = array();
				}
				$aTargets[$dsetidx][$lnkidx++] = 'index.php?module=Potentials&action=ListView&sales_stage='.urlencode($stage_key).'&closingdate_start='.urlencode($date_start).'&closingdate_end='.urlencode($date_end).'&query=true&type=dbrd&owner='.$the_user.'&viewname='.$cvid;
			}
			$dsetidx++;
			$datay[] = $dset;
		}
		$dataChartObject = array(
			'labels' => $labels,
			'datasets' => $datay,
		);
		$chartobject = array(
			'type' => 'bar',
			'data' => $dataChartObject,
			'options' => array(
				'responsive' => false,
				'title' => array(
					'display' => true,
					'text' => $titlestr.' - '.$subtitle,
				),
				'legend' => array(
					'position' => 'top',
					'display' => true,
					'labels' => array(
						'fontSize' => 11,
						'boxWidth' => 18,
					),
				),
				'scales' => array(
					'xAxes' => array(array('stacked' => false))
				)
			)
		);
		$log->debug("Exiting pipeline_by_sales_stage method ...");
		return self::getChartHTMLwithObject(json_encode($chartobject), json_encode($aTargets), 'pipeline_by_sales_stage', $width, $height, 0, 0, 0, 0);
	}

	static public function outcome_by_month($date_start, $date_end, $user_id, $width, $height){
		global $log, $current_user, $adb, $current_language, $mod_strings;
		$log->debug("Entering outcome_by_month(".$date_start.",".$date_end.",".$user_id.",".$width.",".$height.") method ...");
		$report_strings = return_module_language($current_language, 'Reports');
		$months = $report_strings['MONTH_STRINGS'];

		$where=' deleted = 0 ';

		$count = count($user_id);
		if ($count>0) {
			$where .= ' and smownerid in ( ';
			$ss_i = 0;
			foreach ($user_id as $key=>$value) {
				if($ss_i != 0) $where .= ", ";
				$where .= "'".addslashes($value)."'";
				$ss_i++;
			}
			$where .= ")";
		}

		$date = new DateTimeField($date_start);
		$endDate = new DateTimeField($date_end);
		//build the where clause for the query that matches $date_start and $date_end
		$where .= " AND closingdate >= '$date_start' AND closingdate <= '$date_end'";
		$subtitle = $mod_strings['LBL_DATE_RANGE']." ".$date->getDisplayDate()." ".$mod_strings['LBL_DATE_RANGE_TO']." ".$endDate->getDisplayDate();

		$total = 0;
		$realwhere = $where." and sales_stage='Closed Won' ";
		$sql = 'SELECT MONTH(closingdate) as potmonth,count(*) as potcnt,sum(amount) as potsum
			FROM `vtiger_potential`
			INNER JOIN vtiger_crmentity on crmid=potentialid '.
			getNonAdminAccessControlQuery('Potentials', $current_user).
			" WHERE $realwhere
			GROUP BY potmonth
			ORDER BY potmonth";
		$rs = $adb->pquery($sql,array());
		$closedWon = array();
		while ($row = $adb->fetch_array($rs)) {
			$closedWon[$row['potmonth']]['count'] = $row['potcnt'];
			$amount = CurrencyField::convertFromMasterCurrency($row['potsum'],$current_user->conv_rate);
			$closedWon[$row['potmonth']]['sum'] = $amount;
			$total = $total + $amount;
		}
		$realwhere = $where." and sales_stage='Closed Lost' ";
		$sql = 'SELECT MONTH(closingdate) as potmonth,count(*) as potcnt,sum(amount) as potsum
			FROM `vtiger_potential`
			INNER JOIN vtiger_crmentity on crmid=potentialid '.
			getNonAdminAccessControlQuery('Potentials', $current_user).
			" WHERE $realwhere
			GROUP BY potmonth
			ORDER BY potmonth";
		$rs = $adb->pquery($sql,array());
		$closedLost = array();
		while ($row = $adb->fetch_array($rs)) {
			$closedLost[$row['potmonth']]['count'] = $row['potcnt'];
			$amount = CurrencyField::convertFromMasterCurrency($row['potsum'],$current_user->conv_rate);
			$closedLost[$row['potmonth']]['sum'] = $amount;
			$total = $total + $amount;
		}
		$realwhere = $where." and sales_stage!='Closed Lost' and sales_stage!='Closed Won' ";
		$sql = 'SELECT MONTH(closingdate) as potmonth,count(*) as potcnt,sum(amount) as potsum
			FROM `vtiger_potential`
			INNER JOIN vtiger_crmentity on crmid=potentialid '.
			getNonAdminAccessControlQuery('Potentials', $current_user).
			" WHERE $realwhere
			GROUP BY potmonth
			ORDER BY potmonth";
		$rs = $adb->pquery($sql,array());
		$notClosed = array();
		$labels = array();
		while ($row = $adb->fetch_array($rs)) {
			$notClosed[$row['potmonth']]['count'] = $row['potcnt'];
			$amount = CurrencyField::convertFromMasterCurrency($row['potsum'],$current_user->conv_rate);
			$notClosed[$row['potmonth']]['sum'] = $amount;
			$labels[$row['potmonth']-1] = $months[$row['potmonth']-1];
			$total = $total + $amount;
		}

		$titlestr = $mod_strings['LBL_TOTAL_PIPELINE'].html_to_utf8($current_user->currency_symbol).$total;
		$datay = array();
		$aTargets = array();
		$aAlts = array(
			'closedWon' => array(),
			'closedLost' => array(),
			'notClosed' => array(),
		);
		// 'Closed Won'
		$the_state = getTranslatedString('Closed Won','Potentials');
		$dset = array(
			'label' => $the_state,
			'backgroundColor' => '#009933',
			'data' => array(),
		);
		$lnkidx = 0;
		foreach ($labels as $mes => $mes_translation) {
			$m = $mes+1;
			if (isset($closedWon[$m]['sum'])) {
				$dset['data'][] = $closedWon[$m]['sum'];
				array_push($aAlts['Closed Won'], $mes_translation.' - '.$closedWon[$m]['sum']);
			} else {
				$dset['data'][] = 0;
				array_push($aAlts['Closed Won'], '');
			}
		}
		$datay[] = $dset;

		// 'Closed Lost'
		$the_state = getTranslatedString('Closed Lost','Potentials');
		$dset = array(
			'label' => $the_state,
			'backgroundColor' => '#FF9900',
			'data' => array(),
		);
		$lnkidx = 0;
		foreach ($labels as $mes => $mes_translation) {
			$m = $mes+1;
			if (isset($closedLost[$m]['sum'])) {
				$dset['data'][] = $closedLost[$m]['sum'];
				array_push($aAlts['Closed Lost'], $mes_translation.' - '.$closedLost[$m]['sum']);
			} else {
				$dset['data'][] = 0;
				array_push($aAlts['Closed Lost'], '');
			}
		}
		$datay[] = $dset;

		// 'Not Closed'
		$the_state = getTranslatedString('LBL_LEAD_SOURCE_OTHER','Dashboard');
		$dset = array(
			'label' => $the_state,
			'backgroundColor' => '#0066CC',
			'data' => array(),
		);
		$lnkidx = 0;
		foreach ($labels as $mes => $mes_translation) {
			$m = $mes+1;
			if (isset($notClosed[$m]['sum'])) {
				$dset['data'][] = $notClosed[$m]['sum'];
				array_push($aAlts['notClosed'], $mes_translation.' - '.$notClosed[$m]['sum']);
			} else {
				$dset['data'][] = 0;
				array_push($aAlts['notClosed'], '');
			}
		}
		$datay[] = $dset;

		$dataChartObject = array(
			'labels' => $labels,
			'datasets' => $datay,
		);
		$chartobject = array(
			'type' => 'bar',
			'data' => $dataChartObject,
			'options' => array(
				'responsive' => false,
				'title' => array(
					'display' => true,
					'text' => $titlestr.' - '.$subtitle,
				),
				'legend' => array(
					'position' => 'top',
					'display' => true,
					'labels' => array(
						'fontSize' => 11,
						'boxWidth' => 18,
					),
				),
				'scales' => array(
					'xAxes' => array(array('stacked' => false))
				)
			)
		);
		$log->debug("Exiting outcome_by_month method ...");
		return self::getChartHTMLwithObject(json_encode($chartobject), json_encode(array()), 'outcome_by_month', $width, $height, 0, 0, 0, 0);
	}

	static public function lead_source_by_outcome($datax, $user_id, $width, $height){
		global $log, $current_user, $adb, $mod_strings;
		$log->debug("Entering lead_source_by_outcome(".$datax.",".$user_id.",".$width.",".$height.") method ...");

		$where=' deleted = 0 ';
		$labels = array();
		//build the where clause for the query that matches $datax
		$count = count($datax);
		if ($count>0) {
			$where .= " and leadsource in ( ";
			$ss_i = 0;
			foreach ($datax as $key=>$value) {
				if($ss_i != 0) {
					$where .= ', ';
				}
				$where .= "'".addslashes($key)."'";
				$labels[] = getTranslatedString($key,'Potentials');
				$ss_i++;
			}
			$where .= ")";
		}

		$count = count($user_id);
		if ($count>0) {
			$where .= ' and smownerid in ( ';
			$ss_i = 0;
			foreach ($user_id as $key=>$value) {
				if($ss_i != 0) $where .= ", ";
				$where .= "'".addslashes($value)."'";
				$ss_i++;
			}
			$where .= ")";
		}

		$sql = 'SELECT `leadsource`,smownerid,count(*) as potcnt,sum(amount) as potsum
			FROM `vtiger_potential`
			INNER JOIN vtiger_crmentity on crmid=potentialid '.
			getNonAdminAccessControlQuery('Potentials', $current_user).
			" WHERE $where
			GROUP BY `leadsource`,smownerid
			ORDER BY leadsource,smownerid";
		$rs = $adb->pquery($sql,array());
		//build pipeline by sales stage data
		$total = 0;
		$count = array();
		$sum = array();
		while ($row = $adb->fetch_array($rs)) {
			$amount = CurrencyField::convertFromMasterCurrency($row['potsum'],$current_user->conv_rate);
			$sum[$row['leadsource']][$row['smownerid']] = $amount;
			$count[$row['leadsource']][$row['smownerid']] = $row['potcnt'];
			$total = $total + $amount;
		}
		$titlestr = $mod_strings['LBL_TOTAL_PIPELINE'].html_to_utf8($current_user->currency_symbol).$total;
		$datay = array();
		$aTargets = array();
		$aAlts = array();
		$cvid = getCvIdOfAll("Potentials");
		$dsetidx = 0;
		foreach ($user_id as $the_id) {
			$the_user = getEntityName('Users', $the_id);
			$the_user = $the_user[$the_id];
			$dset = array(
				'label' => $the_user,
				'backgroundColor' => sprintf('#%02X%02X%02X', rand(50,255), rand(50,255), rand(50,255) ),
				'data' => array(),
			);
			$lnkidx = 0;
			foreach ($datax as $stage_key=>$stage_translation) {
				$dset['data'][] = (isset($sum[$stage_key][$the_id]) ? $sum[$stage_key][$the_id] : 0);
				if (!isset($aAlts[$the_id])) {
					$aAlts[$the_id] = array();
				}
				if (isset($sum[$stage_key][$the_id])) {
					array_push($aAlts[$the_id], $the_user.' - '.$count[$stage_key][$the_id]." ".$mod_strings['LBL_OPPS_OUTCOME']." $stage_translation");
				} else {
					array_push($aAlts[$the_id], '');
				}
				if (!isset($aTargets[$dsetidx])) {
					$aTargets[$dsetidx] = array();
				}
				$aTargets[$dsetidx][$lnkidx++] = 'index.php?module=Potentials&action=ListView&leadsource='.urlencode($stage_key).'&query=true&type=dbrd&owner='.$the_user.'&viewname='.$cvid;
			}
			$dsetidx++;
			$datay[] = $dset;
		}
		$dataChartObject = array(
			'labels' => $labels,
			'datasets' => $datay,
		);
		$chartobject = array(
			'type' => 'bar',
			'data' => $dataChartObject,
			'options' => array(
				'responsive' => false,
				'title' => array(
					'display' => true,
					'text' => $titlestr,
				),
				'legend' => array(
					'position' => 'top',
					'display' => true,
					'labels' => array(
						'fontSize' => 11,
						'boxWidth' => 18,
					),
				),
				'scales' => array(
					'xAxes' => array(array('stacked' => false))
				)
			)
		);
		$log->debug("Exiting lead_source_by_outcome method ...");
		return self::getChartHTMLwithObject(json_encode($chartobject), json_encode($aTargets), 'lead_source_by_outcome', $width, $height, 0, 0, 0, 0);
	}


	static public function pipeline_by_lead_source($datax, $date_start, $date_end, $user_id, $width, $height){
		global $log, $current_user, $adb, $mod_strings;
		$log->debug("Entering pipeline_by_lead_source(".$datax.",".$date_start.",".$date_end.",".$user_id.",".$width.",".$height.") method ...");

		$where=' deleted = 0 ';
		$labels = array();
		//build the where clause for the query that matches $datax
		$count = count($datax);
		if ($count>0) {
			$where .= " and leadsource in ( ";
			$ss_i = 0;
			foreach ($datax as $key=>$value) {
				if($ss_i != 0) {
					$where .= ', ';
				}
				$where .= "'".addslashes($key)."'";
				$labels[] = getTranslatedString($key,'Potentials');
				$ss_i++;
			}
			$where .= ")";
		}

		$count = count($user_id);
		if ($count>0) {
			$where .= ' and smownerid in ( ';
			$ss_i = 0;
			foreach ($user_id as $key=>$value) {
				if($ss_i != 0) $where .= ", ";
				$where .= "'".addslashes($value)."'";
				$ss_i++;
			}
			$where .= ")";
		}

		$sql = 'SELECT `leadsource`,count(*) as potcnt,sum(amount) as potsum
			FROM `vtiger_potential`
			INNER JOIN vtiger_crmentity on crmid=potentialid '.
			getNonAdminAccessControlQuery('Potentials', $current_user).
			" WHERE $where
			GROUP BY `leadsource`
			ORDER BY leadsource";
		$rs = $adb->pquery($sql,array());
		//build pipeline by sales stage data
		$total = 0;
		$count = array();
		$sum = array();
		while ($row = $adb->fetch_array($rs)) {
			$amount = CurrencyField::convertFromMasterCurrency($row['potsum'],$current_user->conv_rate);
			$sum[$row['leadsource']] = $amount;
			$count[$row['leadsource']] = $row['potcnt'];
			$total = $total + $amount;
		}
		$titlestr = $mod_strings['LBL_TOTAL_PIPELINE'].html_to_utf8($current_user->currency_symbol).$total;
		$aTargets = array();
		$cvid = getCvIdOfAll("Potentials");
		$dset = array();
		$lnkidx = 0;
		foreach ($datax as $stage_key=>$stage_translation) {
			$dset['data'][] = (isset($sum[$stage_key]) ? $sum[$stage_key] : 0);
			$dset['backgroundColor'][] = sprintf('#%02X%02X%02X', rand(50,255), rand(50,255), rand(50,255));
			$aTargets[$lnkidx++] = 'index.php?module=Potentials&action=ListView&leadsource='.urlencode($stage_key).'&query=true&type=dbrd&viewname='.$cvid;
		}
		$dataChartObject = array(
			'labels' => $labels,
			'datasets' => array($dset),
		);
		$chartobject = array(
			'type' => 'pie',
			'data' => $dataChartObject,
			'options' => array(
				'responsive' => false,
				'title' => array(
					'display' => true,
					'text' => $titlestr,
				),
				'legend' => array(
					'position' => 'right',
					'display' => true,
					'labels' => array(
						'fontSize' => 11,
						'boxWidth' => 18,
					),
				)
			)
		);
		$log->debug("Exiting pipeline_by_lead_source method ...");
		return self::getChartHTMLwithObject(json_encode($chartobject), json_encode($aTargets), 'pipeline_by_lead_source', $width, $height, 0, 0, 0, 0);
	}

}