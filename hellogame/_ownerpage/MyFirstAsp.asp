<HTML>
<HEAD>
<TITLE>MyFirstAsp</TITLE>
</HEAD>
<BODY>
<%
str = "<br>오늘은 " & date() & " 입니다.<br>"

for i = 1 to 5 
Response.Write str
next
%>
</BODY>
</HTML>
