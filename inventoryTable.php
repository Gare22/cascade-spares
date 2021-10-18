<!DOCTYPE html>
<html>
<head>
</head>
<body>
<?php
//inventoryTable.php - Handle http GET request for getting Cascade Spare's parts database to return parts list to our web inventory
//Author: Garrett Tallent, https://github.com/Gare22/cascade-spares
//Email: garretttallent@gmail.com (if you need any help)
//Last Modified: 10/7/2021


//TODO Figure out how to prevent SQL Injection

//SQL Database credentials
//Would be better to set these in environment variables or a config file
$server = "SQLServer";
$username = "SQLUsername";
$password = 'SQLPassword'; 
$dbname = "SQLDatabaseName"; //Which database in the server would you like to read from?

// Create connection to SQL database
$conn = new mysqli($server, $username, $password, $dbname);



// determine page number from $_GET (passed from index.html through dbquery function)
$page = intval($_GET['page']);

// determine part number from $_GET (passed from index.html through dbquery function)
$part = strval($_GET['part']);
$part = str_replace("'", "", $part);//remove single quotes from $part for MINOR MINOR MINOR (not good) sql injection protection

// set the number of items to display per page
$items_per_page = 22;


//maxpage number
$sql = "SELECT * FROM Inventory WHERE partnumber LIKE '%$part%'"; //query everything to get count (better way to do this would just do a count query)
$result = $conn->query($sql);
$maxpage = ceil($result->num_rows/$items_per_page); //set the maxpage number to the number of rows from the query result divided by how many items we want to show per row

// build query
$offset = ($page - 1) * $items_per_page; //set the current offset to the page number - 1 (to start at 0) multiplied by the number of items we want per page
$sql = "SELECT * FROM Inventory WHERE partnumber LIKE '%$part%' ORDER BY partnumber LIMIT " . $items_per_page . " OFFSET " . $offset; // select $n items where the PN is the same as our searched pn
$result = $conn->query($sql);//actually query the database

//Input placeholder determines what will be put into the searchbar when no value is set or entered 
$inputplaceholder = "Search Part Number...";

//echo (or add) the searchbar form
echo"<form>
        <input id=\"searchbar\" value=\"" . $part ."\" type=\"search\" onsearch=\"querydb(1)\" style=\"margin-bottom:3px;\" placeholder=\"" . $inputplaceholder . "\">
        <i class = \"fa fa-search\"></i>
    </form>
<table id=\"inventorytable\">";

//echo (or add) the table headers
echo'<tr>
        <th>PARTNUMBER</th>
        <th style="text-align:center">CONDITION</th>
        <th>DESCRIPTION</th>
        <th style="text-align:center">QUANTITY</th>
        <th style="text-align:center">EMAIL US</th>
    </tr>';

//if there is a result from our select query, 
if($result){
    //add each row with respective field values
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["partnumber"]. "</td><td style=\"text-align:center\">" . $row["cond"] . "</td><td>"
        . $row["descript"]. "</td><td style=\"text-align:center\">". $row["qty"] . "</td><td class=\"hflex flexcenter\"><a href=\"mailto:service@cascadespares.com?subject=Offer on ".$row["partnumber"]."&body=Name:%0D%0ACompany:%0D%0APhone #:%0D%0AOffer:\">Make an Offer</a>
        <a class=\"hide_from_mobile\" href=\"mailto:service@cascadespares.com?subject=Requesting Quote for ".$row["partnumber"]."&body=Name:%0D%0ACompany:%0D%0APhone #:%0D%0A\">Request a Quote</a></td></tr>";
    }
}

//close the database connection
$conn->close();
?>
</table>

<?php
//add the previous page, current page, and next page elements.
echo"<div class=\"hflex flexcenter\">";
//if the current page is over 1, add the "previous page" button
if($page > 1){echo"<button style=\"float:left\" onclick=\"querydb(" . $page-1 .")\">Previous Page</button>";}
echo"<p style=\"margin:auto; font-family:'Arial'; font-size:1.3rem\">Page" ;
echo"<input id=\"page\" type=\"search\" onsearch=\"querydb(this.value)\" style=\"text-align:center; width:5rem\" type=\"text\" placeholder=\"" . $page . "\">";
echo" of " . $maxpage . "</p>";
//if the current page is under the max page, add the "next page" button
if($page < $maxpage){echo"<button style=\"float:right\" onclick=\"querydb(" . $page+1 . ")\">Next Page</button>";}
echo"</div>";
?>
</body>
</html>