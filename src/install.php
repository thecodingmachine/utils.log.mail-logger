<?php
use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;
/*
 * Copyright (c) 2012 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();
if (!$moufManager->instanceExists("mailLogger")) {
	
	$errorLogLogger = $moufManager->createInstance("Mouf\\Utils\\Log\\MailLogger\\MailLogger");
	// Let's set a name for this instance (otherwise, it would be anonymous)
	$errorLogLogger->setName("mailLogger");
	$errorLogLogger->getProperty("level")->setValue(4);
}

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
?>