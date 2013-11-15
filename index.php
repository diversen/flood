<?php

$action = htmlspecialchars($_GET['action']);
echo flood::getFloodedMessage($action);
