<?php

namespace App\Http\Controllers;

use App\Configs;
use App\Helpers;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Imagick;

class DiagnosticsController extends Controller
{
    function show()
    {

        // Declare
        $errors = array();


        // PHP Version
        if (floatval(phpversion())<5.5)    $errors += ['Error: Upgrade to PHP 5.5 or higher'];

        // Extensions
        if (!extension_loaded('session'))  $errors += ['Error: PHP session extension not activated'];
        if (!extension_loaded('exif'))     $errors += ['Error: PHP exif extension not activated'];
        if (!extension_loaded('mbstring')) $errors += ['Error: PHP mbstring extension not activated'];
        if (!extension_loaded('gd'))       $errors += ['Error: PHP gd extension not activated'];
        if (!extension_loaded('mysqli'))   $errors += ['Error: PHP mysqli extension not activated'];
        if (!extension_loaded('json'))     $errors += ['Error: PHP json extension not activated'];
        if (!extension_loaded('zip'))      $errors += ['Error: PHP zip extension not activated'];

        // Permissions
        if (Helpers::hasPermissions(Config::get('defines.dirs.LYCHEE_UPLOADS_BIG'))===false)    $errors += ['Error: \'uploads/big\' is missing or has insufficient read/write privileges'];
        if (Helpers::hasPermissions(Config::get('defines.dirs.LYCHEE_UPLOADS_MEDIUM'))===false) $errors += ['Error: \'uploads/medium\' is missing or has insufficient read/write privileges'];
        if (Helpers::hasPermissions(Config::get('defines.dirs.LYCHEE_UPLOADS_THUMB'))===false)  $errors += ['Error: \'uploads/thumb\' is missing or has insufficient read/write privileges'];
        if (Helpers::hasPermissions(Config::get('defines.dirs.LYCHEE_UPLOADS_IMPORT'))===false) $errors += ['Error: \'uploads/import\' is missing or has insufficient read/write privileges'];
        if (Helpers::hasPermissions(Config::get('defines.dirs.LYCHEE_UPLOADS'))===false)        $errors += ['Error: \'uploads/\' is missing or has insufficient read/write privileges'];
//        if (Helpers::hasPermissions(Config::get('defines.dirs.LYCHEE_DATA'))===false)           $errors += ['Error: \'data/\' is missing or has insufficient read/write privileges'];

        // About GD
        $gdVersion = array('GD Version' => '-');
        if (function_exists('gd_info')) {
            $gdVersion = gd_info();
            if (!$gdVersion['JPEG Support'])                                          $errors += ['Error: PHP gd extension without jpeg support'];
            if (!$gdVersion['PNG Support'])                                           $errors += ['Error: PHP gd extension without png support'];
            if (!$gdVersion['GIF Read Support'] || !$gdVersion['GIF Create Support']) $errors += ['Error: PHP gd extension without full gif support'];
        }


        // Load settings
        $settings = Configs::get(false);

        // Settings
        if (!isset($settings['username'])||$settings['username']=='')             $errors += ['Error: Username empty or not set in database'];
        if (!isset($settings['password'])||$settings['password']=='')             $errors += ['Error: Password empty or not set in database'];
        if (!isset($settings['sortingPhotos'])||$settings['sortingPhotos']=='')   $errors += ['Error: Wrong property for sortingPhotos in database'];
        if (!isset($settings['sortingAlbums'])||$settings['sortingAlbums']=='')   $errors += ['Error: Wrong property for sortingAlbums in database'];
        if (!isset($settings['plugins']))                                         $errors += ['Error: No property for plugins in database'];
        if (!isset($settings['imagick'])||$settings['imagick']=='')               $errors += ['Error: No or wrong property for imagick in database'];
        if (!isset($settings['identifier'])||$settings['identifier']=='')         $errors += ['Error: No or wrong property for identifier in database'];
        if (!isset($settings['skipDuplicates'])||$settings['skipDuplicates']=='') $errors += ['Error: No or wrong property for skipDuplicates in database'];
        if (!isset($settings['checkForUpdates'])||($settings['checkForUpdates']!='0'&&$settings['checkForUpdates']!='1')) $errors += ['Error: No or wrong property for checkForUpdates in database'];

        // Check dropboxKey
        if (!$settings['dropboxKey']) $errors += ['Warning: Dropbox import not working. No property for dropboxKey.'];

        // Check php.ini Settings
        if (ini_get('max_execution_time')<200&&ini_set('upload_max_filesize', '20M')===false)
            $errors += ['Warning: You may experience problems when uploading a large amount of photos. Take a look in the FAQ for details.'];
        if (empty(ini_get('allow_url_fopen')))
            $errors += ['Warning: You may experience problems with the Dropbox- and URL-Import. Edit your php.ini and set allow_url_fopen to 1.'];

        // Check imagick
        if (!extension_loaded('imagick')) $errors += ['Warning: Pictures that are rotated lose their metadata! Please install Imagick to avoid that.'];
        else if (!$settings['imagick']) $errors += ['Warning: Pictures that are rotated lose their metadata! Please enable Imagick in settings to avoid that.'];


        $infos = [];
        // Ensure that user is logged in
        if ((Session::has('login') && Session::get('login') === true) &&
            (Session::has('identifier') && Session::get('identifier') === $settings['identifier'])) {

            // Load json
            $json = file_get_contents(Config::get('defines.path.LYCHEE') . 'public/src/package.json');
            $json = json_decode($json, true);

            // About imagick
            $imagick = extension_loaded('imagick');
            if ($imagick===true) $imagickVersion = @Imagick::getVersion();
            else                 $imagick = '-';
            if (!isset($imagickVersion, $imagickVersion['versionNumber'])||$imagickVersion==='') $imagickVersion = '-';
            else                                                                                 $imagickVersion = $imagickVersion['versionNumber'];

            // Output system information
            $infos += ['Lychee Version:  ' . $json['version']];
            $infos += ['DB Version:      ' . $settings['version']];
            $infos += ['System:          ' . PHP_OS];
            $infos += ['PHP Version:     ' . floatval(phpversion())];
//            $infos += ['MySQL Version:   ' . $database->server_version];
            $infos += ['Imagick:         ' . $imagick];
            $infos += ['Imagick Active:  ' . $settings['imagick']];
            $infos += ['Imagick Version: ' . $imagickVersion];
            $infos += ['GD Version:      ' . $gdVersion['GD Version']];
//            $infos += ['Plugins:         ' . implode($settings['plugins'], ', ') . PHP_EOL);

        }
        else
        {
            // Don't go further if the user is not logged in
            $infos += ['You have to be logged in to see more information.'];
        }

        // Show separator
        return view('diagnostics', ['errors' => $errors, 'infos' => $infos]);
    }
}