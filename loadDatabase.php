<?php
//loadDatabase.php - insert CSV records into Cascade Spare's public inventory database
//Author: Garrett Tallent, https://github.com/Gare22/cascade-spares
//Email: garretttallent@gmail.com (if you need any help)
//Last Modified: 10/7/2021
//preconditions:
//the csv's records should only have PN, condition, description, and quantity fields (in that order)

ini_set('max_execution_time', 0); // to get unlimited php script execution time

//CSV File Name
$csv_file = "InventoryList.csv";

//CHANGE THE PASSWORD HERE
$correctPW = 'ExamplePassword';

if($_POST["password"] == $correctPW){
    if (file_exists($csv_file)) {
        unlink($csv_file);
    }
    if (move_uploaded_file($_FILES["csv"]["tmp_name"], $csv_file)) {
        //$csv_file - string containing the path of csv file
        

        //SQL Server Credentials
        $server = "www.sqlserver.com";
        $username = "SQLUsername";
        //TODO replace pw string with getenv();
        $password = 'SQLPassword'; 
        $dbname = "SQLDatabaseName";

        // Create connection
        $conn = new mysqli($server, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error . "\r\n");
        }
        echo "Connected successfully" . "\r\n";

        //Drop the old table
        $droptable = "DROP TABLE IF EXISTS Inventory";
        $conn->query($droptable);

        //create table
        $createtable ="CREATE TABLE Inventory (partnumber VARCHAR(30) NOT NULL, cond VARCHAR(30) NOT NULL, descript VARCHAR(60) NOT NULL, qty INT)";

        if ($conn->query($createtable) === TRUE) {
            echo "Table Inventory created successfully" . "\r\n";
        } else {
            echo "Error creating table: " . $conn->error . "\r\n";
        }

        //open the csv provided
        $file = fopen($csv_file, 'r');
        $line = fgetcsv($file); //dump first line containing headers
        $recordnum = 1;
        $maxrecord = count(file($csv_file)) - 1;

        //get the remaining lines as records and insert them into the inventory table
        while(($line=fgetcsv($file)) !==FALSE){
            $insert = "INSERT INTO Inventory".
            "(partnumber, cond, descript, qty)".
            "VALUES('$line[2]','$line[1]','$line[4]','$line[0]')";
            if($conn->query($insert) === TRUE){
                echo '<script>parent.document.getElementById("progressbar").innerHTML="'. $recordnum . ' of ' . $maxrecord .' records inserted"</script>';
            }
            $recordnum++;
            ob_flush();
            flush();
        }
        fclose($file);

        // Close connection
        $conn->close();
        echo "Your file is uploaded!";
        echo '<script>parent.document.getElementById("progressbar").innerHTML="Process Complete!"</script>';
    } else {
        echo "Sorry, there was an error uploading your file.\r\n";  
    }
}else{
    echo "Password is incorrect. Contact Garrett Tallent if you need the password changed.\r\n";
}
?>