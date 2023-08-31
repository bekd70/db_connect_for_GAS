<?php
    /**
     * Written by the American Embassy School IT Department.
     * This software is free to use for educational institutions.
     * 
     * Use at your own risk.  There is no warranty.  AES takes no responsibility is your use of the software
     * 
     * The intended use of this software is to provide faster access to database queries than the builtin JDBC
     * driver built into Google Apps Script which is painfully slow.  It should return 15,000 rows of data in a few seconds.
     * It is reccomended to break down your data requests in batches of 15,000 records at a time.
     * 
     * With this API you can utilize {SELECT or WITH RESULTS/SELECT} in MySQL, Oracle, or MSSQL or INSERT in MySQL
     * you will need to install the drivers for Oracle (oci) on the server running the API before being able to use the API to access Oracle DBs
     * 
     * the request should look like the following example for Google Apps Script
     * 
     * 
    let postData: {
      DomainPwd: "YOUR_API_PASSWORD",
      dbtype: "oracle", //or MYSQL, or SQLSERVER
      host: "IP_or_Hostname_to_dB server,
      port: "PORT_OF_DB_SERVER", //Powerschool default is 1521, MySQL is 3306
      user: Utilities.base64Encode('DB_Username'), //default for PS is psnavigator
      password: Utilities.base64Encode('DB_PASSWORD'),
      schema: 'TABLE_YOU_ARE_QUERYING', //FOR PS IT IS PSPRODDB
      query: ''
    }
    postData.query = "SELECT * FROM students WHERE enroll_status = 0";

    let options = {
        'method' : 'post',
        'payload' : JSON.stringify(postData)
    };

    let response = UrlFetchApp.fetch(apiUrl,options);

    response.status will contain either true or false depending on the sucess of the call
    response.message will contain an error mesage if response.status is false
    response.message will contain an array of JSON with the query results if response.message is true
    
     */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
error_reporting(0);
$json = file_get_contents('php://input');
$payload= json_decode($json);
$DomainPwd=$payload->DomainPwd;
$dbtypelowercase=$payload->dbtype;
$dbtype=strtoupper($dbtypelowercase);
$stmt=$payload->query;
$qry_array=explode(" ",$stmt);
$typeofquery=strtoupper($qry_array[0]);
//password the api user will use
//Please use a complex password
if($DomainPwd =="YOUR_API_PASSWORD"){ 
    
    if (($typeofquery =="SELECT") || ($typeofquery=="WITH") ){
        if($dbtype =="MYSQL"){
            $host = $payload->host;
            $port = $payload->port;
            if(!empty($port)){
                $connection = @fsockopen($host, $port);
                if (is_resource($connection)) {
                    $query = $payload->query;
                    $user=base64_decode($payload->user);
                    $password=base64_decode($payload->password);
                    $schema = $payload->schema;
                    $conn = new mysqli($host, $user, $password, $schema, $port);
                    if($conn->connect_error){     
                        echo json_encode(array("status"=>false, "message"=>"Error failed to connect to MySQL: " . $conn->connect_error));
                    }else{
                        $stmt=$payload->query;
                        $result = mysqli_query($conn, $stmt);
                        if ($result== TRUE){
                            while($reponse = mysqli_fetch_assoc($result)){
                                $data[]=$reponse;
                            }
                            echo json_encode(array("status" => true,"message"=>$data)); 
                        }else{
                            echo json_encode(array("status" => false,"message"=>"Error in query" )); 
                        }
                    }
                }else{
                    echo json_encode(array("status" => false,"message"=>"Host:Port is closed or cannot be reached"));
                }
            }else if(empty($port)){
                $host = $payload->host;
                $query = $payload->query;
                $user=base64_decode($payload->user);
                $password=base64_decode($payload->password);
                $schema = $payload->schema;
                $conn = new mysqli($host, $user, $password, $schema);
                if($conn->connect_error){
                    echo json_encode(array("status"=>false, "message"=>"Error failed to connect to MySQL: " . $conn->connect_error));
                }else{
                    $stmt=$payload->query;
                    $result = mysqli_query($conn, $stmt);
                    if ($result== TRUE){
                        while($reponse = mysqli_fetch_assoc($result)){
                            $data[]=$reponse; 
                        }
                        echo json_encode(array("status" => true,"message"=>$data));    
                    }else{
                        echo json_encode(array("status" => false,"message"=>"Error in query" )); 
                    }
                }
            }else{
                echo json_encode(array("status" => false,"message"=>"Host:Port is closed or cannot be reached"));
            }
        }else if($dbtype =="ORACLE"){
            $port = $payload->port;
            $host = $payload->host;
            $user=base64_decode($payload->user);
            $password=base64_decode($payload->password);
            $query = $payload->query;
            $user = $user;
            $password = $password;
            $schema = $payload->schema;
            if((!empty($port)) && (!empty($host)) && (!empty($query)) && (!empty($schema)) && (!empty($user)) && (!empty($password))){
                $dbConnStr = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=" . $host . ")(PORT=" . $port . "))(CONNECT_DATA=(SERVICE_NAME=" . $schema . ")))";
                if (!$conn = oci_connect($user, $password, $dbConnStr)) {
                    $err = oci_error();
                    echo json_encode(array("status"=>false, "message"=>"Error failed to connect to ORACLE: " . $err['message']));
                } else {
                    $result = oci_parse($conn, $query);
                    $result2=oci_execute($result);
                    if($result2 == 1){
                        $data = [];
                        while (($row = oci_fetch_assoc($result))!= false) {
                            $data[] = $row;
                        }
                        echo json_encode(array("status" => true,"message"=>$data));
                    }else{
                        echo json_encode(array("status" => false,"message"=>"Error in query" ));   
                    }     
                }
            }else if((empty($port)) && (!empty($host)) && (!empty($query)) && (!empty($schema)) && (!empty($user)) && (!empty($password))){
                $dbConnStr = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=" . $host . ")(PORT = 1521))(CONNECT_DATA=(SERVICE_NAME=" . $schema . ")))";
                if (!$conn = oci_connect($user, $password, $dbConnStr)) {
                    $err = oci_error();
                    echo json_encode(array("status"=>false, "message"=>"Error failed to connect to ORACLE: " . $err['message']));
                } else {
                    $result = oci_parse($conn, $query);
                    $result2=oci_execute($result);
                    if ($result2 == 1){
                        $data = [];
                        while (($row = oci_fetch_assoc($result))!= false) {
                            $data[] = $row;
                        }
                        echo json_encode(array("status" => true,"message"=>$data));
                    }else{
                        echo json_encode(array("status" => false,"message"=>"Error in query" ));   
                    }  
                }
            }else{
                echo json_encode(array("status" => false,"message"=>"Connection parameter missing"));
            }
        }else if($dbtype =="SQLSERVER"){
            $host = $payload->host;
            $port = $payload->port;
            $user=base64_decode($payload->user);
            $password=base64_decode($payload->password);
            $schema = $payload->schema;
            $query = $payload->query;
            if((!empty($port)) && (!empty($host)) && (!empty($query)) && (!empty($schema)) && (!empty($user)) && (!empty($password))){
                $serverName = $host.",".$port; 
                $connectionInfo = array( "Database"=>$schema, "UID"=>$user, "PWD"=>$password,"TrustServerCertificate"=>"yes");
                $conn = sqlsrv_connect( $serverName, $connectionInfo);
                if( $conn ){
                    $stmt = sqlsrv_query( $conn, $query);
                    if( !$stmt ) {
                        echo json_encode(array("status"=>false, "message"=>"Error failed to connect to SQLSERVER: " .sqlsrv_errors()));
                    }else{
                        while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                            $data[]=$row;
                        }
                        echo json_encode(array("status" => true,"message"=>$data));
                    }
                }else{
                    $errors=(sqlsrv_errors());
                    //print_r($errors[0][2]);
                    echo json_encode(array("status"=>false, "message"=>"Error failed to connect to SQLSERVER: " .$errors[0][2]));
                }
            }else if((empty($port)) && (!empty($host)) && (!empty($query)) && (!empty($schema)) && (!empty($user)) && (!empty($password))){
                $serverName = $host; 
                $connectionInfo = array( "Database"=>$schema, "UID"=>$user, "PWD"=>$password,"TrustServerCertificate"=>"yes");
                $conn = sqlsrv_connect( $serverName, $connectionInfo);
                    if( $conn ){
                        $stmt = sqlsrv_query( $conn, $query);
                        if(!$stmt){
                            //die( print_r( sqlsrv_errors(), true));
                            echo json_encode(array("status"=>false, "message"=>"Error failed to connect to SQLSERVER: " .sqlsrv_errors()));
                        }else{
                            while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ){
                                $data[]=$row;
                            }
                            echo json_encode(array("status" => true,"message"=>$data));
                        }
                    }else{
                        $errors=(sqlsrv_errors());
                        //print_r($errors[0][2]);
                        echo json_encode(array("status"=>false, "message"=>"Error failed to connect to SQLSERVER: " .$errors[0][2]));
                    }
                }else{
                    echo json_encode(array("status" => false,"message"=>"Connection parameter missing"));
                }
        }else{
            echo json_encode(array("status" => false,"message"=>"Only MYSQL,ORACLE,SQLSERVER connect exists"));
        }
    }else if($typeofquery =="INSERT"){
        if($dbtype =="MYSQL"){
            $host = $payload->host;
            $port = $payload->port;
            if(!empty($port)){
                $connection = @fsockopen($host, $port);
                if (is_resource($connection)) {
                    $query = $payload->query;
                    $user=base64_decode($payload->user);
                    $password=base64_decode($payload->password);
                    $schema = $payload->schema;
                    $conn = new mysqli($host, $user, $password, $schema, $port);
                    if($conn->connect_error){     
                        echo json_encode(array("status"=>false, "message"=>"Error failed to connect to MySQL: " . $conn->connect_error));
                    }else{
                        $query=$payload->query;
                        if ($conn->query($query) === TRUE) {
                            $last_id = $conn->insert_id;
                            echo json_encode(array("status"=>true, "message"=>"Record inserted successfully".$last_id));
                        } else {
                            echo json_encode(array("status"=>false, "message"=>"Error:". $query .",". $conn->error));
                        }
                    }
                }else{
                    echo json_encode(array("status" => false,"message"=>"Host:Port is closed or cannot be reached"));
                }
            }else{
                $host = $payload->host;
                $query = $payload->query;
                $user=base64_decode($payload->user);
                $password=base64_decode($payload->password);
                $schema = $payload->schema;
                $conn = new mysqli($host, $user, $password, $schema);
                if($conn->connect_error){
                    echo json_encode(array("status"=>false, "message"=>"Error failed to connect to MySQL: " . $conn->connect_error));
                }else{   
                    $stmt=$payload->query;
                    $query=$payload->query;
                    if ($conn->query($query) === TRUE) {
                        $last_id = $conn->insert_id;
                        echo json_encode(array("status"=>true, "message"=>"Record inserted successfully".$last_id));
                    }else{
                        echo json_encode(array("status"=>false, "message"=>"Error:". $query .",". $conn->error));
                    }
                }
            }
        }else{
            echo json_encode(array("status" => false,"message"=>"Insert restricted to MYSQL Only"));
        }
    }else{
        echo json_encode(array("status" => false,"message"=>"Restricted to Select parameters only and Limited Insert Parameter"));
    }
}else{
    echo json_encode(array("status" => false,"message"=>"Did not get the correct keyword. Please try again."));
}


 ?>