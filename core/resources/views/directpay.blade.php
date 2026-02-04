
<html>

<head>
<title>Direct Pay</title>
<!-- RUM Header -->
<script charset='UTF-8'> window['egrum-start_time'] = new Date().getTime();
window['Site_Name'] = '2878095b-c5e6-4882-928a-1ae14df15ac3-1597744961555';
window['beacon-url'] = 'https://ewine.kbzbank.com';
</script>
<!--<script src='https://ewine.kbzbank.com/rumcollector/egrum.js' async> </script>-->

<!-- RUM Header -->

</head>
<script language="javascript">

function display()
{

var dt=new Date();
var datetime=null;
var day=null;
var month=null;
day=dt.getDay();
month=dt.getMonth();
if(day<=9)
{
day="0"+dt.getDay();
}
if(month<=9)
{
month="0"+dt.getMonth();
}

datetime=day+"/"+month+"/"+dt.getYear()
+" "+dt.getHours()+":"+dt.getMinutes()+":"+dt.getSeconds();

document.frmmain.fldDatTimeTxn.value=datetime;
return false;
}
function damn() {
alert("sandy");
alert(window.location.href+document.frmmain.url.value);
}

</script>
<body onload="return display()">

<center><p><font name=courier color=darkblue size=4><u>Direct Pay Link to KBZ bank.</u></font></p></center>

<?php 
    $api_url = $details->direct_apiurl;
?>

<form name="frmmain" method="post" action="{{ $api_url }}"> @csrf
    
  <p>&nbsp;</p>
 <center>
    <tr>
        
        <?php 
            $appdata = $_GET['encdata'] ?? ''; 
            $appd = str_replace(' ', '+', $appdata); 
        ?>
      
      <td width="180"><input type="hidden" name="encdata" value="{{ $appd }}">
      </td>
	 
	  <td width="29">&nbsp;</td>
    </tr>
  </table>
  <p>
  <input type="submit" value="Proceed" name="B1">

  <input type="hidden" name="merchant_code" value="DIRECTPAY">
  
  </p>
  
</form>

<pre><tr>
<td cellpadding="0" cellspacing="0">
<p align=center>
The html page contains the form to be filled. Click the submit button and
it goes to the Login page of KBZ Bank.
Finish the transaction at KBZ Bank and it should return to the jsp page(return URL)
which will display all the info returned.
</p></td></tr>
</pre>

</body>

</html>
<!-- web8503.mail.in.yahoo.com compressed/chunked Tue Mar 28 14:35:23 BST 2006 -->
