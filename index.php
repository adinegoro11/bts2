<?php

  function input_tanggal($ket)
  {
    echo "Input bulan ".$ket." (yyyy-mm):";
    $handle = fopen ("php://stdin","r");
    $bulan = fgets($handle);
    if(trim($bulan) == '') die("Input kosong\n");
    else return $bulan;
  }

  function tarik_data($id_sensor, $sdate, $edate)
  {
    $url = "http://202.43.73.157/historicdata_html.htm?id=".$id_sensor."&sdate=".$sdate."-01-00-00-00&edate=".$edate."-01-00-00-00&avg=3600&pctavg=300&pctshow=false&pct=95&pctmode=false&username=isp Telkom&passhash=1024595950";
    $arrContextOptions = array("ssl" => array("verify_peer"=>false, "verify_peer_name"=>false));

    $data = file_get_contents($url, false, stream_context_create($arrContextOptions));
    return $data;
  }

  function get_data($url, $sensor)
  {
    $data = array();

    $pecah_1 = explode(" %",$url[197]);
    $pecah_2 = explode('">',$pecah_1[0]);
    if( preg_match('/\\d/', $pecah_2[1])) $data['persen_up'] = $pecah_2[1];
    else $data['persen_up'] = 'No data';

    $pecah_1 = explode(" %",$url[198]);
    $pecah_2 = explode('">',$pecah_1[0]);
    if( preg_match('/\\d/', $pecah_2[1])) $data['persen_down'] = $pecah_2[1];
    else $data['persen_down'] = 'No data';

    if($sensor == 'traffic')
    {
      $pecah_1 = explode(" kbit/s",$url[207]);
      $pecah_2 = explode('=6>',$pecah_1[0]);
      if( preg_match('/\\d/', $pecah_2[1])) $data['average'] = $pecah_2[1]." kbit/s";
      else $data['average'] = 'No data';

      $pecah_1 = explode(" KByte",$url[211]);
      $pecah_2 = explode('=6>',$pecah_1[0]);
      if( preg_match('/\\d/', $pecah_2[1])) $data['total_traffic'] = $pecah_2[1]." KByte";
      else $data['total_traffic'] = 'No data';
    }

    else
    {
      $pecah_1 = explode(" msec",$url[207]);
      $pecah_2 = explode('=6>',$pecah_1[0]);
      if( preg_match('/\\d/', $pecah_2[1])) $data['average'] = $pecah_2[1]." msec";
      else $data['average'] = 'No data';
    }

    return $data;
  }

  error_reporting(E_ALL);
  $sdate = trim(input_tanggal('awal'));
  $edate = trim(input_tanggal('akhir'));

  $list = '';
  $list = file_get_contents("https://database.metrasat.net/lemon/api_blankspot/get_sensor");
  $list = json_decode($list,true);
  // print_r($list);

  $jumlah = count($list);
  $i = 1;
  foreach($list as $data)
  {
    $hasil_traffic = tarik_data($data['bp3ti_sensor_traffic'], $sdate, $edate);
    file_put_contents("traffic_bulanan/traffic_".$data['id_link']."_".$sdate.".html", $hasil_traffic);

    sleep(3);
    $hasil_ping = tarik_data($data['bp3ti_sensor_ping'], $sdate, $edate);
    file_put_contents("ping_bulanan/ping_".$data['id_link']."_".$sdate.".html", $hasil_ping);

    $persen = round(($i/$jumlah)*100, 2);
    echo $data['link_name']."|".$persen."%\n";
    // echo $output."\n";
    sleep(3);
    $i++;
  }

  $i = 1;
  foreach($list as $data)
  {
    $persen = round(($i/$jumlah)*100, 2);
    echo $data['link_name']."|".$persen."%\n";

    $url = file("ping_bulanan/ping_".$data['id_link']."_".$sdate.".html");//file in to an array  
    $data_ping = get_data($url, 'ping');
    sleep(1);
    $url = file("traffic_bulanan/traffic_".$data['id_link']."_".$sdate.".html");//file in to an array  
    $data_traffic = get_data($url, 'traffic');

    $get = array(
      'id_link' => $data['id_link'],
      'sensor_ping' => $data['bp3ti_sensor_ping'],
      'sensor_traffic' => $data['bp3ti_sensor_traffic'],
      'sdate' => $sdate,
      'edate' => $edate,
      'bp3ti_ping_uptime' => ($data_ping['persen_up']),
      'bp3ti_ping_downtime' => ($data_ping['persen_down']),
      'bp3ti_ping_average' => ($data_ping['average']),
      'bp3ti_traffic_up' => ($data_traffic['persen_up']),
      'bp3ti_traffic_down' => ($data_traffic['persen_down']),
      'bp3ti_traffic_average' => ($data_traffic['average']),
      'bp3ti_traffic_total' => ($data_traffic['total_traffic']),
      'username' => 'blankspot',
      'passhash' => '891f754f82ee7e3e1b7d0b712cf04791' 
    );

    $output = '';
    $output = file_get_contents("https://database.metrasat.net/lemon/api_blankspot/insert_data?".http_build_query($get,'','&'));
    var_dump($get);
    echo $output."\n";
    sleep(1);
    $i++;
  }

  die("selesai\n");

?>


 