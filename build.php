<?php
$phar = new Phar('dependor.phar');
$phar->startBuffering();
$defaultStub = $phar::createDefaultStub('index.php');
$phar->buildFromDirectory('phar');
$phar->setStub("#!/usr/bin/env php \n" . $defaultStub);
$phar->stopBuffering();
$phar->compressFiles(Phar::GZ);
