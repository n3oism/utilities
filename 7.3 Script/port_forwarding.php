<?
set_time_limit(0);

function shutdown()
{
    global $ipsock, $rmsock;
    if ($ipsock) fclose($ipsock);
    if ($rmsock) fclose($rmsock);
}
register_shutdown_function('shutdown');

$target_socket='tcp://localhost:22';//or 'tcp://192.168.0.2:3306'
$ipsock=stream_socket_server('tcp://0.0.0.0:8090', $errno2, $errstr2);
stream_set_blocking($ipsock, 0);

while (true) 
{
    usleep(5000);//0.005s, to reduce cpu consumption
    $c_ipsock=stream_socket_accept($ipsock); //even add '-1', it won't wait
    $rmsock=stream_socket_client($target_socket, $errno, $errstr);
    @stream_set_blocking($rmsock, 1);
    while (($c_ipsock && !feof($c_ipsock)) && ($rmsock && !feof($rmsock)))
    {
        $swrite=$except=null;
        $sread=array($c_ipsock, $rmsock);
        stream_select($sread, $swrite, $except, 5);
        //print_r($sread);echo "    \n";
        if ($sread[0]===$rmsock)
        {
            if ($data=fread($rmsock, 65536))
            {
                //echo 'rmsock:'.strlen($data).'    '.$data."    \n";
                myfwrite($c_ipsock, $data);
            }
        }
        else if ($sread[0]===$c_ipsock)
        {
            if ($data=fread($c_ipsock, 65536))
            {
                //echo 'ipsock:'.strlen($data).'    '.$data."    \n";
                myfwrite($rmsock, $data);
            }
        }
        //var_export(array(feof($c_ipsock), feof($rmsock)));echo "   \n";
    }
    @fclose($c_ipsock);
    @fclose($rmsock);
}

function myfwrite($fd,$buf) 
{
    $i=0;
    while ($buf != "") {
        $i=fwrite ($fd,$buf,strlen($buf));
        if ($i==false) {
            if (!feof($fd)) continue;
            break;
        }
        $buf=substr($buf,$i);
    }
    return $i;
}
?>
