<?php
header('Content-Type: text/html;charset=UTF-8');
$ulstyle = ' style="list-style:none;margin: 0 0 20px;padding:0;border-top: 1px solid #dde"';
$listyle = ' style="padding:20px;margin:0;border: 1px solid #dde;border-top:0;"';
$morestyle = ' style="padding:3px;margin:2px 0 0;display:inline-block;font-size:11px;font-weight:bold;color:#3369ff"';

$t = emailTemplate('<h3>New tasks on orchestrate</h3><ul' . $ulstyle . '><li ' . $listyle . '>Lorem ipsum dolor sit amet consectetur adipisicing elit. Nisi assumenda deleniti cupiditate laboriosam vel ipsam, maiores labore facere minima sint dolorum quidem sit neque accusantium eum aliquid at corporis perspiciatis.</li><li ' . $listyle . '>Lorem ipsum dolor sit amet consectetur adipisicing elit. Nisi assumenda deleniti cupiditate laboriosam vel ipsam, maiores labore facere minima sint dolorum quidem sit neque accusantium eum aliquid at corporis perspiciatis.</li><li ' . $listyle . '>Lorem ipsum dolor sit amet consectetur adipisicing elit. Nisi assumenda deleniti cupiditate laboriosam vel ipsam, maiores labore facere minima sint dolorum quidem sit neque accusantium eum aliquid at corporis perspiciatis. <div><a  ' . $morestyle . ' href="#">View task</a></div></li></ul>');


echo $t;
