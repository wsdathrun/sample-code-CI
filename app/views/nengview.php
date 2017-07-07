<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>


<html>
<head>
	<title>小能测试页</title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="viewport" content="width=device-width, initial-scale=1" />


<!--	<meta http-equiv="keywords" content="疯狂的bug,杨晓,杨晓个人网站,杨晓测试网站">-->
	<meta http-equiv="description" content="this is xiaoneng">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="./css/public.css">

</head>

<body>

<div style="height: 612px;width: 350px;padding: 1px;">
	<div>
		<div style="width: 175px; float: left;">
			<button type="button">crm录入及查询</button>
		</div>
		<div style="width: 155px; float: right; text-align: right;">
			<button type="button">红包信息查询</button>
		</div>
	</div>

	用户身份：姓名:<?php echo $name;?><br>
	电话号码:<?php echo $phone;?><br>
	订单:<br>
	<ul>
		<?php foreach ($orders as $item):?>

			<li><?php echo $item;?></li>

		<?php endforeach;?>
	</ul>
	<br><br><br>
	用户具备发红包的权限
	<br><br><br>
	请选择您要发送的红包金额：
	<select>
		<option value="volvo">1000红包</option>
		<option value="saab">500红包</option>
		<option value="opel" selected="selected">300红包</option>
		<option value="audi">200红包</option>
	</select><br><br><br>
	<button type="button">发送</button>
	<br><br><br>
	2016年12月21：08:00，发送红包100元
	<br>
	2016年12月21：08:00，发送红包100元
	<br>
	2016年12月21：08:00，发送红包100元
	<br>
	2016年12月21：08:00，发送红包100元
	<br>
	2016年12月21：08:00，发送红包100元
	<br>
	2016年12月21：08:00，发送红包100元<br>
	2016年12月21：08:00，发送红包100元
	<br>
	2016年12月21：08:00，发送红包100元







      <div>
        <form action="" method="get">
          <p>姓&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;名: <input type="text" name="name" /></p>
          <p>手&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;机: <input type="text" name="phone" /></p>
          <p>身&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;份: <input type="text" name="name" /></p>
          <p>学&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历: <input type="text" name="phone" /></p>
          <p>订单状态: <input type="text" name="name" /></p>
          <p>发送地区: <input type="text" name="phone" /></p>
          <p>开始时间: <input type="text" name="name" /></p>
          <p>结束时间: <input type="text" name="phone" /></p>
          <input type="submit" value="提交" />
          <input type="submit" value="重置" />
          <input type="submit" value="修改" />
        </form>
      </div>


    <a onclick="openURLToBrowser('http://www.crazybug.cn')" style="color: red;">链接跳出小能客户端打开写法</a>
    <br/><br/>
    <a href="http://www.crazybug.cn"  target="_self">链接在小能客户端内打开写法</a>
    -->
	<br>
	----------------------------------------------







</div>
</body>
</html>
