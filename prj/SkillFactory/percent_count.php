<?php

	//ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	
	

	

require_once('q.php');
insertEvent ($database,"percent_count_start","Пересчет процентов, ставок, кешбеков, закрытия инвестиций - старт");	
$cur_date = new DateTime(date('Y-m-d'));




$vars 	= new Vars($database);

$message_down_percent = 'Уважаемый, Партнёр! Для получения более высокой ставки ('.($vars->inv_perc*100).'% в день) по инвестиции, используйте партнерскую программу для привлечения 2 (двух) или более рефералов с инвестициями с такой же или более суммами инвестиций, как у Вас в течение срока Вашей инвестиции!';

$message_up_percent = 'Уважаемый, Партнёр! Ваша ставка изменена на ('.($vars->inv_perc*100).'% в день) по инвестиции, используйте партнерскую программу для привлечения 2 (двух) или более рефералов с инвестициями с такой же или более суммами инвестиций, как у Вас в течение срока Вашей инвестиции для сохранения ставки!';

//check and update percent
$rows = getInvHostUsers($database);
for ($i=0;$i<sizeof($rows);$i++)
{

	//test mode
	// if ($rows[$i]['username']== 'nic')
	// {
	
	// }
	// else
	// {
		// continue;
	// }

	$sum_of_ref_invest = 0;
	$count_of_diff_ref = 0;
	$cur_ref_id		   = 0;
	
	$rows2 = getInvHostUsersRefs($database,$rows[$i]['username']);
	for ($j=0;$j<sizeof($rows2);$j++)
	{	
		
		 if ($cur_ref_id	!= $rows2[$j]['user_id'])
		 {
			 $cur_ref_id = $rows2[$j]['user_id'];
			 $count_of_diff_ref++;
		 }
		 $sum_of_ref_invest += $rows2[$j]['sum_inv_by_user'];
	}
	
	$all_sum_inv 	= getAllInvestSumByUserId($database,$rows[$i]['user_id']);
	
	//if ($sum_of_ref_invest>=$rows[$i]['investment_sum'] &&   $count_of_diff_ref>=2)
	if ($sum_of_ref_invest>=$all_sum_inv*2 &&   $count_of_diff_ref>=1)
	{
		if ($vars->inv_perc != $rows[$i]['percent'])
		{
			echo $rows[$i]['username'].'Рекоммендация к ставке - обычная. Обновить ставку!!! </br>';
			updateActiveInvestPercentByUserId($database,$vars->inv_perc,$rows[$i]['user_id']);
			createMesageForUser ($database,$rows[$i]['user_id'],$message_up_percent,0);
		}
		else
		{
			echo $rows[$i]['username'].'Рекоммендация к ставке - обычная. </br>';
		}
	}
	else
	{
		if ($vars->inv_pperc != $rows[$i]['percent'])
		{
			echo $rows[$i]['username'].'Рекоммендация к ставке - пассивная. Обновить ставку!!! </br>';
			updateActiveInvestPercentByUserId($database,$vars->inv_pperc,$rows[$i]['user_id']);
			createMesageForUser ($database,$rows[$i]['user_id'],$message_down_percent,0);
		}
		else
		{
			echo $rows[$i]['username'].'Рекоммендация к ставке - пассивная. </br>';
		}
	}
}

$rows = getListActiveInvest($database,$cur_date->format('Y-m-d'));

echo '<br/>';
echo 'proceed percent count and update<br/>';
for ($i=0; $i<sizeof($rows); $i++)
{

	//$interval = date_diff($cur_date, $rows[$i]['start_date']);
	//echo $cur_date->diff($rows[$i]['start_date'])->days; // 9 days
	$start_date = new DateTime($rows[$i]['start_date']);
	$timestamp_to= (int) $cur_date->format('U');
	$timestamp_from = (int) $start_date->format('U');	
	
	

	$days_count = (int) round(($timestamp_to - $timestamp_from)/(60*60*24)) + 1;
	//$days_count = $cur_date->diff($rows[$i]['start_date'])->days;
	$new_percent_sum =  $days_count * $rows[$i]['percent']*$rows[$i]['sum']; 
	
	updateInvestPercentSum($database,$rows[$i]['id'],$new_percent_sum);
	
	echo $rows[$i]['id'].' '.$days_count.' '.$timestamp_from.' '.$timestamp_to.'<br/>';
}

echo '<br/>';
echo 'proceed close investment';
$rows = getListActiveInvestToClose($database);
for ($i=0; $i<sizeof($rows); $i++)
{
	echo $rows[$i]['id'].'<br/>';
	
	//check new conditions, sum of all invest of reffs >*2 than sum of all invest of this investor
	$all_sum_refs 	= getAllInvestSumOfRefs($database,$rows[$i]['username']);
	$all_sum_inv 	= getAllInvestSumByUserId($database,$rows[$i]['user_id']);
	
	if ($all_sum_refs >= $all_sum_inv*2)
	{
		//close invest
		
		//return to balance
		doReward ($rows[$i]['user_id'],$rows[$i]['sum']+round($rows[$i]['sum_percent'],2),$database,0);

		////make income record for user
		MakeIncomeRecordForUser ($rows[$i]['user_id'],$rows[$i]['sum'],$database,'возврат тела инвестиции ('.$rows[$i]['sum'].' руб.) по инвестиции id = '.$rows[$i]['id']);		
		MakeIncomeRecordForUser ($rows[$i]['user_id'],round($rows[$i]['sum_percent'],2),$database,'перевод процентов ('.$rows[$i]['sum_percent'].' руб.) по инвестиции id = '.$rows[$i]['id']);	

		//close invest
		setCloseInvest ($database,$rows[$i]['id']);
	}
	else
	{
		
		//set to capitalization
		setInvestCapitalization($database,$rows[$i]['id']);
	}
	
	


}

echo '<br/>';
echo 'proceed count cashbacks';
$rows = getCashbackListForAccruing($database);
for ($i=0; $i<sizeof($rows); $i++)
{
	echo $rows[$i]['id'].'<br/>';
	$_sum  = round($rows[$i]['sum']*$vars->cashb_perc,2); //*0.03
	//cashback income
	doReward ($rows[$i]['user_id'],$_sum,$database,0);
	//make income record for user
	MakeIncomeRecordForUser ($rows[$i]['user_id'],$_sum,$database,'Кэшбэк в размере ('.$_sum .' руб.) по инвестиции id = '.$rows[$i]['investment_id']);	
	//set cashback accrued
	setCashbackAccrued($database,$rows[$i]['id'],$_sum);
}

echo 'end script';
insertEvent ($database,"percent_count_end","Пересчет процентов конец");	




?>