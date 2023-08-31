# db_connect_for_GAS
Written by the American Embassy School IT Department.
This software is free to use for educational institutions.

Use at your own risk.  There is no warranty.  AES takes no responsibility is your use of the software

The intended use of this software is to provide faster access to database queries than the builtin JDBC
driver built into Google Apps Script which is painfully slow.  It should return 15,000 rows of data in a few seconds.
It is reccomended to break down your data requests in batches of 15,000 records at a time.

With this API you can utilize {SELECT or WITH RESULTS/SELECT} in MySQL, Oracle, or MSSQL or INSERT in MySQL
you will need to install the drivers for Oracle (oci) on the server running the API before being able to use the API to access Oracle DBs

The request should look like the following example for Google Apps Script


   let postData: {
      DomainPwd: "YOUR_API_PASSWORD",
      dbtype: "oracle", //or MYSQL, or SQLSERVER
      host: "IP_or_Hostname_to_dB_server",
      port: "PORT_OF_DB_SERVER", //Powerschool default is 1521, MySQL is 3306
      user: Utilities.base64Encode("DB_Username"), //default for PS is psnavigator
      password: Utilities.base64Encode("DB_PASSWORD"),
      schema: "TABLE_YOU_ARE_QUERYING", //FOR PS IT IS PSPRODDB
      query: ""
    }
    postData.query = "SELECT * FROM students WHERE enroll_status = 0";

    let options = {
        'method' : 'post',
        'payload' : JSON.stringify(postData)
    };

    let response = UrlFetchApp.fetch(apiUrl,options);`

response.status will contain either true or false depending on the sucess of the call
response.message will contain an error mesage if response.status is false
response.message will contain an array of JSON with the query results if response.message is true
