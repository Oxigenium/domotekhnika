<?php

use Illuminate\Database\Schema\Blueprint;


Route::get('/', function () {
	$filters = getFilters();
    return view('transactions',compact('filters','data'));
});

Route::get('/updatedb', function () {
	updateDB();
    return redirect('/filter/year/2015');
});

Route::get('/filter/year/{year}', function ($year,$page = 1) {
	$filters = getFilters();
	$currentFilter = '/filter/year/' . $year;
	$count = $filters[$year]['count'];
	$data = DB::table('data')
                ->whereYear('date', $year)
                ->orderBy('date', 'desc')
                ->limit(100)
                ->get();
    return view('transactions',compact('filters','data','count','page','currentFilter'));
});

Route::get('/filter/year/{year}/page/{page}', function ($year,$page = 1) {
	$filters = getFilters();
	$currentFilter = '/filter/year/' . $year;
	$count = $filters[$year]['count'];
	$data = DB::table('data')
                ->whereYear('date', $year)
                ->orderBy('date', 'desc')
                ->offset(max(($page - 1) * 20,0))
                ->limit(100)
                ->get();
    return view('transactions',compact('filters','data','count','page','currentFilter'));
});

Route::get('/filter/year/{year}/month/{month}', function ($year,$month,$page = 1) {
	$filters = getFilters();
	$currentFilter = '/filter/year/' . $year . '/month/' . $month;
	$count = $filters[$year]['months'][$month]->count;
	$data = DB::table('data')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->orderBy('date', 'desc')
                ->limit(100)
                ->get();
    return view('transactions',compact('filters','data','count','page','currentFilter'));
});

Route::get('/filter/year/{year}/month/{month}/page/{page}', function ($year,$month,$page = 1) {
	$filters = getFilters();
	$currentFilter = '/filter/year/' . $year . '/month/' . $month;
	$count = $filters[$year]['months'][$month]->count;
	$data = DB::table('data')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->orderBy('date', 'desc')
                ->offset(max(($page - 1) * 20,0))
                ->limit(100)
                ->get();
    return view('transactions',compact('filters','data','count','page','currentFilter'));
});
	
	function updateDB(){

		//Приведение входных данных в порядок, исправление аномалий.

		DB::select("UPDATE `data` SET `volume` = 0 - `volume` WHERE card_number in (
		    '7824861010044000605',
		    '7824861010041093223',
		    '7824861010044000621',
		    '7824861010044000597',
		    '7824861010044000589',
		    '7824861010044000613'  );");

		//Подсчёт возвратов
		if (Schema::hasTable('data_new')) {
		    DB::table('data_new')->truncate();
		} else
		{
			Schema::create('data_new', function (Blueprint $table) {
	            $table->increments('id');
	            $table->string('card_number', 20)->default(NULL);
	            $table->dateTime('date')->nullable(false);
	            $table->float('volume', 8, 2)->nullable(false);
	            $table->string('service', 100)->nullable(false);
	            $table->integer('address_id')->default(NULL);
	        });
		}


		DB::select("
			INSERT INTO `data_new`
			SELECT
			    all_consumptions.`id`,
			    all_consumptions.`card_number`,
			    all_consumptions.`date`,
			    ROUND(
			        all_consumptions.`volume` + IFNULL(consumptions_with_refunds.`volume`, 0),
			        2
			    ) AS volume,
			    all_consumptions.`service`,
			    all_consumptions.`address_id`
			FROM
			    `data` all_consumptions
			LEFT JOIN(
			    SELECT
			        refunds.`address_id`,
			        refunds.`service`,
			        refunds.`card_number`,
			        MAX(consumptions.`date`) AS consumption_with_refund_datetime,
			        refunds.`volume`
			    FROM
			        `data` refunds
			    INNER JOIN `data` consumptions ON
			        refunds.`volume` > 0 AND 
			        refunds.`service` = consumptions.`service` AND 
			        refunds.`address_id` = consumptions.`address_id` AND 
			        refunds.`card_number` = consumptions.`card_number` AND 
			        refunds.`date` > consumptions.`date` AND 
			        consumptions.`volume` <= 0
			    GROUP BY
			        refunds.`id`
			) consumptions_with_refunds
			ON
			    consumptions_with_refunds.`service` = all_consumptions.`service` AND 
			    consumptions_with_refunds.`address_id` = all_consumptions.`address_id` AND 
			    consumptions_with_refunds.`card_number` = all_consumptions.`card_number` AND 
			    all_consumptions.`volume` <= 0 AND 
			    consumptions_with_refunds.`consumption_with_refund_datetime` = all_consumptions.`date`
			WHERE
			    all_consumptions.`volume` <= 0
			GROUP BY
			    all_consumptions.`id`;
		");
		Schema::dropIfExists('data');
		Schema::rename('data_new','data');
	}

	function getFilters(){
		$filterMonths = DB::table('data')
                     ->select(DB::raw('YEAR(`date`) as year, MONTH(`date`) as month, COUNT(*) as count'))
                     ->groupBy('year','month')
                     ->orderBy('date','desc')
                     ->get();
	    $filters = array();
	    foreach ($filterMonths as $filterMonth) {
	    	if (count($filters) == 0 || $filterMonth->year != end($filters)['year'])
	    		$filters[$filterMonth->year] = array('year' => $filterMonth->year, 'count' => 0, 'months' => array());
	    	$filters[$filterMonth->year]['months'][$filterMonth->month] = $filterMonth;
	    	$filters[$filterMonth->year]['count'] += $filterMonth->count;
	    }
	    return $filters;
	}