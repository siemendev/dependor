<?php
$arg = 'composer.lock';
if (!empty($argv[1])) {
    $arg = rtrim($argv[1], '/') . '/' . $arg;
}

$composerPath = getcwd() . '/' . $arg;
exec('git --work-tree ' . getcwd() . ' --git-dir ' . getcwd() . '/.git show HEAD:' . $arg . ' > old');
$old = json_decode(file_get_contents('old'), true);
$new = json_decode(file_get_contents($composerPath), true);
exec('rm old');

$oldPackageData = array_merge($old['packages'], $old['packages-dev']);
$oldPackages = array_combine(
    array_map(
        static function($package){
            return $package['name'];
        },
        $oldPackageData
    ), array_map(
        static function($package){
            return ltrim($package['version'], 'v');
        },
        $oldPackageData
    )
);

$newPackageData = array_merge($new['packages'], $new['packages-dev']);
$newPackages = array_combine(
    array_map(
        static function($package){
            return $package['name'];
        },
        $newPackageData
    ), array_map(
        static function($package){
            return ltrim($package['version'], 'v');
        },
        $newPackageData
    )
);

$introduced = [];
$changedMajor = [];
$changedMinor = [];
$removed = [];

function getVersion($string, $version = 1) {
    $matches = [];
    preg_match_all('/([0-9]+)/', $string, $matches);

    return (int) ($matches[0][$version - 1] ?? 0);
}

foreach ($newPackages as $package => $version) {
    if (!array_key_exists($package, $oldPackages)) {
        $introduced[$package] = $version;
    } else {
        if (getVersion($version, 1) !== getVersion($oldPackages[$package], 1)) {
            $changedMajor[$package] = $oldPackages[$package] . ' -> ' . $version;
        } elseif (getVersion($version, 2) !== getVersion($oldPackages[$package], 2)) {
            $changedMinor[$package] = $oldPackages[$package] . ' -> ' . $version;
        }

        unset($oldPackages[$package]);
    }

    unset($newPackages[$package]);
}
foreach ($oldPackages as $package => $version) {
    $removed[$package] = $version;
}

$nothingChanged = true;
if (!empty($introduced)) {
    echo "Introduced dependencies:\n";
    foreach ($introduced as $package => $version) {
        echo "   $package ($version)\n";
    }
    echo "\n";
    $nothingChanged = false;
}
if (!empty($changedMajor)) {
    echo "Changed dependencies (Major version):\n";
    foreach ($changedMajor as $package => $version) {
        echo "   $package: $version\n";
    }
    echo "\n";
    $nothingChanged = false;
}
if (!empty($changedMinor)) {
    echo "Changed dependencies (Minor version):\n";
    foreach ($changedMinor as $package => $version) {
        echo "   $package: $version\n";
    }
    echo "\n";
    $nothingChanged = false;
}
if (!empty($removed)) {
    echo "Removed dependencies:\n";
    foreach ($removed as $package => $version) {
        echo "   $package\n";
    }
    echo "\n";
    $nothingChanged = false;
}
if ($nothingChanged) {
    echo "nothing changed.\n";
}